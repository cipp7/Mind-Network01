<?php
/**
 * Plugin Name: SS Admin Dashboard
 * Description: Custom admin + front-end analytics dashboard (Clients, Members, Appointments, Gender, Invoices).
 * Version:     1.1.0
 * Author:      Your Name
 */

if (!defined('ABSPATH')) exit;

class SS_Admin_Dashboard {
  public function __construct() {
    // Admin page
    add_action('admin_menu', [$this, 'add_menu']);
    add_action('admin_enqueue_scripts', [$this, 'enqueue_assets_admin']);

    // Front-end
    add_action('wp_enqueue_scripts', [$this, 'register_front_assets']); // register only
    add_shortcode('ss_dashboard', [$this, 'shortcode_render']);
  }

  /* ---------------- Admin menu ---------------- */
  public function add_menu() {
    add_menu_page(
      'Shraddha Sidhwani',
      'SS Dashboard',
      'manage_options',
      'ss-admin-dashboard',
      [$this, 'render_admin_page'],
      'dashicons-chart-pie',
      2
    );
  }

  public function enqueue_assets_admin($hook) {
    if ($hook !== 'toplevel_page_ss-admin-dashboard') return;
    wp_enqueue_style('ss-admin-dashboard', plugins_url('styles.css', __FILE__), [], '1.1.0');
  }

  public function register_front_assets() {
    // Register (don’t enqueue globally). The shortcode will enqueue when used.
    wp_register_style('ss-admin-dashboard', plugins_url('styles.css', __FILE__), [], '1.1.0');
  }

  /* ---------------- Data helpers ---------------- */
  private function count_cpt($post_type) {
    if (!post_type_exists($post_type)) return 0;
    $count = wp_count_posts($post_type);
    return isset($count->publish) ? intval($count->publish) : 0;
  }
  private function count_users_by_role($role = 'subscriber') {
    $q = new WP_User_Query(['role'=>$role,'fields'=>'ID','number'=>1,'count_total'=>true]);
    return intval($q->get_total());
  }
  private function count_users_by_gender_value($value_slug) {
    global $wpdb;
    $sql = $wpdb->prepare("
      SELECT COUNT(*) FROM {$wpdb->usermeta}
      WHERE meta_key=%s AND LOWER(TRIM(meta_value))=%s
    ", 'gender', strtolower(trim($value_slug)));
    return (int) $wpdb->get_var($sql);
  }
  private function count_invoices_by_status($status_value) {
    if (!post_type_exists('invoice')) return 0;
    $q = new WP_Query([
      'post_type'=>'invoice','post_status'=>'publish',
      'posts_per_page'=>1,'fields'=>'ids','no_found_rows'=>true,
      'meta_query'=>[['key'=>'invoice_status','value'=>$status_value]],
    ]);
    return (int) $q->found_posts;
  }
  private function get_stats() {
    $clients      = $this->count_cpt('client');
    $members      = $this->count_users_by_role('subscriber');
    $appointments = $this->count_cpt('appointment');

    $g_slugs = ['cis','female','gender-fluid','male','na','non-binary','prefer-not-to-say','transgender'];
    $gender_counts = [];
    foreach ($g_slugs as $slug) $gender_counts[$slug] = $this->count_users_by_gender_value($slug);

    $pending_invoices = $this->count_invoices_by_status('pending');
    $paid_invoices    = $this->count_invoices_by_status('paid');

    return compact('clients','members','appointments','gender_counts','pending_invoices','paid_invoices');
  }

  /* ---------------- Renderers ---------------- */

  // Admin page wrapper
  public function render_admin_page() {
    $this->render_dashboard('admin');
  }

  // Shortcode: [ss_dashboard restrict="0" links="admin|none"]
  public function shortcode_render($atts = []) {
    $atts = shortcode_atts([
      'restrict' => '0', // 1 = must be logged in (shows login form if not)
      'links'    => 'admin', // 'admin' keeps admin URLs in sidebar; 'none' uses '#'
    ], $atts, 'ss_dashboard');

    if ($atts['restrict'] === '1' && !is_user_logged_in()) {
      // Minimal login form (returns HTML)ss-layout
      return wp_login_form(['echo'=>false]);
    }

    // Enqueue CSS only when shortcode is used
    wp_enqueue_style('ss-admin-dashboard');

    wp_add_inline_style(
  'ss-admin-dashboard',
  '.ss-dashboard-page .entry-title, .ss-dashboard-page .main_title, .ss-dashboard-page h1.entry-title {display:none !important;}'
);


    ob_start();
    $this->render_dashboard($atts['links'] === 'admin' ? 'admin' : 'frontend');
    return ob_get_clean();
  }

  // Shared dashboard markup (context = 'admin' or 'frontend')
  private function render_dashboard($context = 'admin') {
    $s = $this->get_stats();

    // Build sidebar links
    $link = function($admin_path) use ($context) {
      return ($context === 'admin') ? admin_url($admin_path) : '#';
    };
    ?>
    <div class="ss-layout">
      <!-- Sidebar -->
      <aside class="ss-sidebar">
        <div class="ss-brand">
          <span class="ss-brand-title">Shrradha Sidhwani</span>
          <span class="ss-brand-sub">Application</span>
        </div>
        <nav class="ss-nav">
          <a class="ss-nav-item active" href="<?php echo esc_url( site_url('/ss-dashboard/') ); ?>">
  <span class="dashicons dashicons-dashboard"></span> Dashboard
</a>

          <a class="ss-nav-item" href="<?php echo esc_url( $link('#') ); ?>">
            <span class="dashicons dashicons-archive"></span> Repository
          </a>
          <a class="ss-nav-item" href="<?php echo esc_url( $link('edit.php?post_type=appointment') ); ?>">
            <span class="dashicons dashicons-calendar-alt"></span> Appointments
          </a>
          <a class="ss-nav-item" href="<?php echo esc_url( $link('edit.php?post_type=client') ); ?>">
            <span class="dashicons dashicons-groups"></span> Client Management
          </a>
         <a class="ss-nav-item" href="<?php echo esc_url( home_url('/patient-list-page/') ); ?>">
    <span class="dashicons dashicons-admin-users"></span> User Management
</a>

          <a class="ss-nav-item" href="<?php echo esc_url( $link('profile.php') ); ?>">
            <span class="dashicons dashicons-admin-settings"></span> Profile Setting
          </a>
          <a class="ss-nav-item" href="#">
            <span class="dashicons dashicons-list-view"></span> User Log
          </a>
          <a class="ss-nav-item" href="#">
            <span class="dashicons dashicons-megaphone"></span> Request Payment
          </a>
          <a class="ss-nav-item" href="<?php echo esc_url( $link('post-new.php?post_type=invoice') ); ?>">
            <span class="dashicons dashicons-media-spreadsheet"></span> Add Invoice
          </a>
          <?php if ($context==='admin') : ?>
            <a class="ss-nav-item logout" href="<?php echo esc_url( wp_logout_url( admin_url() ) ); ?>">
              <span class="dashicons dashicons-migrate"></span> Logout
            </a>
          <?php endif; ?>
        </nav>
      </aside>

      <!-- Main -->
      <main class="ss-main">
        <div class="ss-wrap">
          <div class="ss-header">
            <h1>Dashboard</h1>
            <div class="ss-breadcrumb">Shraddha Sidhwani / Dashboard</div>
          </div>

          <div class="ss-grid ss-grid-3">
            <div class="ss-card ss-soft-green">
              <div class="ss-card-icon">👤</div>
              <div class="ss-card-title">Clients</div>
              <div class="ss-card-value"><?php echo esc_html($s['clients']); ?></div>
            </div>
            <div class="ss-card ss-soft-yellow">
              <div class="ss-card-icon">👥</div>
              <div class="ss-card-title">Members</div>
              <div class="ss-card-value"><?php echo esc_html($s['members']); ?></div>
            </div>
            <div class="ss-card ss-soft-orange">
              <div class="ss-card-icon">📅</div>
              <div class="ss-card-title">Appointments</div>
              <div class="ss-card-value"><?php echo esc_html($s['appointments']); ?></div>
            </div>
          </div>

          <h3 class="ss-section-title">User Activity by Gender</h3>

          <div class="ss-grid ss-grid-3">
            <?php
              $boxes = [
                ['cis','Cis','ss-mint'],
                ['female','Female',''],
                ['gender-fluid','Gender fluid','ss-pale'],
                ['male','Male',''],
                ['na','NA',''],
                ['non-binary','Non-binary',''],
                ['prefer-not-to-say','Prefer not to say',''],
                ['prefer-not-to-say','Prefer Not To Say','ss-pale'],
                ['transgender','Transgender',''],
              ];
              foreach ($boxes as $b) {
                [$slug, $label, $extra] = $b;
                $val = isset($s['gender_counts'][$slug]) ? $s['gender_counts'][$slug] : 0;
                echo '<div class="ss-card ss-soft ss-card-min '.$extra.'">
                        <div class="ss-chip">🟣</div>
                        <div class="ss-card-line">
                          <span class="ss-line-label">'.esc_html($label).'</span>
                          <span class="ss-line-value">'.esc_html($val).'</span>
                        </div>
                      </div>';
              }
            ?>
          </div>

          <div class="ss-grid ss-grid-2 mt-16">
            <div class="ss-card ss-soft-orange">
              <div class="ss-card-icon">🧾</div>
              <div class="ss-card-title">Pending Invoice</div>
              <div class="ss-card-value"><?php echo esc_html($s['pending_invoices']); ?></div>
            </div>
            <div class="ss-card ss-soft-green">
              <div class="ss-card-icon">🧾</div>
              <div class="ss-card-title">Paid Invoice</div>
              <div class="ss-card-value"><?php echo esc_html($s['paid_invoices']); ?></div>
            </div>
          </div>

          <div class="ss-footer-row">
            <div class="ss-muted">Gender Wise Visiting report</div>
            <div class="ss-filter">
              <label>Search By Year</label>
              <select disabled><option selected>2023</option></select>
            </div>
          </div>
        </div>
      </main>
    </div>
    <?php
  }
}

new SS_Admin_Dashboard();
