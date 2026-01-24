<?php
/*
Template Name: Doctor Details
*/
if (!defined('ABSPATH')) exit;

get_header();
global $wpdb;

if (!is_user_logged_in()) {
    wp_safe_redirect(wp_login_url(get_permalink()));
    exit;
}

$current_user_id = get_current_user_id();


// Prefix-safe tables
$table_doctors = 'wp_doctors';
$table_clinics = 'wp_doctor_clinics';

// 1) Try to find doctor by wp_user_id column (BEST)
// If your table doesn't have wp_user_id, this will return null.
$doctor = $wpdb->get_row(
    $wpdb->prepare("SELECT * FROM {$table_doctors} WHERE wp_user_id = %d LIMIT 1", $current_user_id)
);

// 2) Fallback: try by usermeta doctor_id
if (!$doctor) {
    $doctor_id_meta = (int) get_user_meta($current_user_id, 'doctor_id', true);
    if ($doctor_id_meta > 0) {
        $doctor = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_doctors} WHERE id = %d LIMIT 1", $doctor_id_meta));
    }
}

// 3) If still not found, show error (don’t guess by name/email)
?>
<style>
    body {
        font-family: 'Segoe UI', sans-serif;
        background: linear-gradient(to right, #e0f7fa, #ffffff);
        margin: 0
    }

    .wrap {
        max-width: 1000px;
        margin: 30px auto;
        padding: 0 15px
    }

    .card {
        background: #fff;
        border: 1px solid #e6eef5;
        border-radius: 14px;
        box-shadow: 0 6px 20px rgba(0, 0, 0, .06);
        padding: 18px;
        margin-bottom: 16px
    }

    .row {
        display: flex;
        gap: 16px;
        flex-wrap: wrap;
        align-items: flex-start
    }

    .photo {
        width: 120px;
        height: 120px;
        border-radius: 12px;
        object-fit: cover;
        border: 1px solid #ddd;
        background: #fafafa
    }

    .title {
        font-size: 22px;
        font-weight: 800;
        margin: 0;
        color: #0d47a1
    }

    .meta {
        margin: 6px 0 0;
        color: #444
    }

    .badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
        margin-top: 8px
    }

    .badge.admin {
        background: #e8f5e9;
        color: #1b5e20;
        border: 1px solid #c8e6c9
    }

    .badge.doc {
        background: #e3f2fd;
        color: #0d47a1;
        border: 1px solid #bbdefb
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px
    }

    th,
    td {
        border: 1px solid #e6eef5;
        padding: 10px;
        text-align: left;
        font-size: 14px
    }

    th {
        background: #f7fbff
    }

    .warn {
        background: #fff3cd;
        border: 1px solid #ffeeba;
        color: #856404;
        padding: 14px;
        border-radius: 12px
    }

    .topbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
        margin-bottom: 14px
    }

    .btn {
        display: inline-block;
        background: #1976d2;
        color: #fff;
        padding: 10px 14px;
        border-radius: 10px;
        text-decoration: none;
        font-weight: 700
    }

    .plans-table th,
    .plans-table td {
        vertical-align: top;
        line-height: 1.35;
    }

    .plans-table ul li {
        margin-bottom: 6px;
    }
</style>

<div class="wrap">
    <div class="topbar">
        <h2 style="margin:0;color:#0d47a1;">Doctor Dashboard</h2>
        <a class="btn" href="<?php echo esc_url(wp_logout_url(home_url())); ?>">Logout</a>
    </div>

    <?php if (!$doctor) : ?>
        <div class="warn">
            <b>No doctor record linked to your account.</b><br>
            This means your WP user is not mapped to the doctors table.<br>
            Fix: store <code>wp_user_id</code> in doctors table OR set usermeta <code>doctor_id</code> at registration.
        </div>
    <?php else : ?>

        <?php
        $doctor_id = (int) $doctor->id;

        $clinics = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$table_clinics} WHERE doctor_id = %d ORDER BY id DESC", $doctor_id)
        );

        $photo = !empty($doctor->profile_photo_url) ? esc_url($doctor->profile_photo_url) : '';
        $is_admin = isset($doctor->is_clinic_admin) ? (int)$doctor->is_clinic_admin : 0;

        // Email comes from WP user (correct place)
        $wp_user = wp_get_current_user();
        $email = $wp_user ? $wp_user->user_email : '';
        ?>

        <div class="card">
            <div class="row">
                <div>
                    <?php if ($photo) : ?>
                        <img class="photo" src="<?php echo $photo; ?>" alt="Profile photo">
                    <?php else : ?>
                        <div class="photo"></div>
                    <?php endif; ?>
                </div>

                <div style="flex:1;min-width:250px;">
                    <p class="title"><?php echo esc_html($doctor->doctor_name ?? ''); ?></p>

                    <p class="meta"><b>Email:</b> <?php echo esc_html($email); ?></p>
                    <p class="meta"><b>Designation:</b> <?php echo esc_html($doctor->designation ?? ''); ?></p>
                    <p class="meta"><b>Speciality:</b> <?php echo esc_html($doctor->speciality ?? ''); ?></p>
                    <p class="meta"><b>Workplace:</b> <?php echo esc_html($doctor->workplace ?? ''); ?></p>
                    <p class="meta"><b>Degree:</b> <?php echo esc_html($doctor->degree ?? ''); ?></p>
                    <p class="meta"><b>Department:</b> <?php echo esc_html($doctor->department ?? ''); ?></p>

                    <span class="badge <?php echo $is_admin ? 'admin' : 'doc'; ?>">
                        <?php echo $is_admin ? 'Clinic Admin' : 'Doctor'; ?>
                    </span>

                    <p class="meta" style="margin-top:10px;">
                        <b>Doctor ID:</b> <?php echo (int)$doctor->id; ?>
                        <?php if (isset($doctor->created_at) && $doctor->created_at) : ?>
                            | <b>Created:</b> <?php echo esc_html($doctor->created_at); ?>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>

        <div class="card">
            <h3 style="margin:0;color:#0d47a1;">Clinics</h3>

            <?php if (empty($clinics)) : ?>
                <p class="meta">No clinics found.</p>
            <?php else : ?>
                <table>
                    <thead>
                        <tr>
                            <th>Clinic Name</th>
                            <th>Clinic Phone</th>
                            <th>Holiday</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clinics as $c) : ?>
                            <tr>
                                <td><?php echo esc_html($c->clinic_name ?? ''); ?></td>
                                <td><?php echo esc_html($c->clinic_phone ?? ''); ?></td>
                                <td><?php echo (!empty($c->is_holiday) && (int)$c->is_holiday === 1) ? 'Yes' : 'No'; ?></td>
                                <td><?php echo esc_html($c->created_at ?? ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3 style="margin:0;color:#0d47a1;">Payment Details</h3>

            <?php
            // Payment Criteria table data (from doc)
            $payment_plans = [
                [
                    'tier' => 'FREE / BASIC',
                    'price' => 'Free',

                    'includes' => [
                        'Profile listing in therapist directory',
                        'Access to limited client inquiries',
                        'In-app calendar & scheduling',
                        'Basic note-taking (limited storage)',
                        'WhatsApp/email booking notifications',
                    ],
                    'limitations' => [
                        'Limited referrals (e.g., 5/mo)',
                        'Notes storage cap (e.g., 20 notes)',
                    ],
                    'ideal_for' => 'New therapists building clientele',
                ],
                [
                    'tier' => 'PROFESSIONAL',
                    'price' => '₹499 – ₹999/mo',
                    'includes' => [
                        'Unlimited referrals',
                        'Unlimited session notes',
                        'PDF note export',
                        'Payment collection & invoicing',
                        'Weekly payouts to bank accounts',
                        'Basic analytics (hours booked/week, revenue)',
                    ],
                    'limitations' => [
                        'Good selling point: “Grow your private practice online.”',
                        'A low-cost tier Indian therapists find acceptable.',
                    ],
                    'ideal_for' => '—',
                ],
                [
                    'tier' => 'PREMIUM',
                    'price' => '₹1,499 – ₹2,999/mo',
                    'includes' => [
                        'Everything in Professional, plus:',
                        'AI-assisted note-taking (transcription → summary)',
                        'Client progress trackers (mood logs, worksheets)',
                        'Automated reminders + follow-ups',
                        'Data encryption vault (HIPAA-style)',
                        'Advanced analytics (retention, revenue trends, cancellations)',
                        'Priority therapist directory listing',
                    ],
                    'limitations' => [
                        'Major value: Saves time + boosts client retention',
                    ],
                    'ideal_for' => 'Established practitioners / small clinics',
                ],
                [
                    'tier' => 'GROUP PRACTICE',
                    'price' => '₹4,999 – ₹12,000/mo',
                    'includes' => [
                        'Multiple therapist accounts',
                        'Team scheduling & permission-based shared notes',
                        'Unified billing & financial dashboard',
                        'Clinic branding on profiles',
                        'Staff trainings & workshops on platform',
                        'Risk-flag alerts (with therapist approval)',
                        'Treatment-plan suggestions (non-diagnostic)',
                    ],
                    'limitations' => [
                        '—',
                    ],
                    'ideal_for' => 'Schools, counselling centres, nursing homes, group practices',
                ],
            ];

            $plan_amounts = [
                'PROFESSIONAL' => 99900, // ₹999
                'PREMIUM' => 299900, // ₹2,999
                'GROUP PRACTICE' => 1200000, // ₹12,000
            ];
            ?>

            <h3 style="margin-top:0;color:#0d47a1;">Payment Plans</h3>
            

            <table class="plans-table">
                <thead>
                    <tr>
                        <th>Tier</th>
                        <th>Price</th>
                        <th>Includes</th>
                        <th>Limitations / Extra Notes</th>
                        <th>Ideal For</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payment_plans as $p) : ?>
                        <tr>

                            <td><b><?php echo esc_html($p['tier']); ?></b></td>



                            <td>
                                <?php echo esc_html($p['price']); ?>

                                <?php
                                if (in_array($p['tier'], ['PROFESSIONAL', 'PREMIUM', 'GROUP PRACTICE'], true)) :
                                ?>
                                    <div style="margin-top:6px;">
                                        <a href="https://razorpay.me/@shrradhaviveksidhwani?amount=<?php echo $plan_amounts[$p['tier']]; ?>"
                                            target="_blank"
                                            class="btn"
                                            style="padding:6px 12px;font-size:13px;border-radius:8px;">
                                            Buy Now
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <td>
                                <ul style="margin:0;padding-left:18px;">
                                    <?php foreach ($p['includes'] as $item) : ?>
                                        <li><?php echo esc_html($item); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </td>
                            <td>
                                <ul style="margin:0;padding-left:18px;">
                                    <?php foreach ($p['limitations'] as $item) : ?>
                                        <li><?php echo esc_html($item); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </td>
                            <td><?php echo esc_html($p['ideal_for']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <hr style="border:none;border-top:1px solid #e6eef5;margin:14px 0;">


            <div style="margin-top:10px;">
                <p class="meta"><b>Name:</b>Shrradha Sidhwani</p>

                <p class="meta"><b>Account Details:</b><br>
                    Account Name: Reality and You <br>
                    Bank: HDFC BANK<br>
                    Account No: 50200113558259 <br>
                    IFSC: HDFC0000016 <br>
                    Branch: Bandra West<br>
                    UPI:<br>
                    <img style="width:30% " src="https://newapp2025.shrradhasidhwani.com/wp-content/uploads/2026/01/WhatsApp-Image-2026-01-12-at-6.34.17-PM.jpeg">
                </p>

                <?php
                $payment_proof_url = !empty($doctor->payment_proof_url) ? esc_url($doctor->payment_proof_url) : '';
                ?>

                <div style="margin-top:12px;">
                    <p class="meta"><b>Uploaded Payment Proof:</b></p>

                    <?php if ($payment_proof_url) : ?>
                        <a href="<?php echo $payment_proof_url; ?>" target="_blank" rel="noopener">
                            <img src="<?php echo $payment_proof_url; ?>" style="max-width:220px;border:1px solid #e6eef5;border-radius:12px;padding:6px;background:#fafafa;" alt="Payment proof">
                        </a>
                        <p class="meta" style="font-size:12px;color:#666;">Click image to open full size.</p>
                    <?php else : ?>
                        <p class="meta" style="color:#666;">No payment proof uploaded yet.</p>
                    <?php endif; ?>
                </div>

                <hr style="border:none;border-top:1px solid #e6eef5;margin:14px 0;">

                <form id="paymentProofForm" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="save_doctor_payment_proof">
                    <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('doctor_payment_proof_nonce')); ?>">

                    <label class="meta" style="display:block;margin-bottom:6px;"><b>Upload payment proof image</b> (JPG/PNG/WEBP)</label>
                    <input type="file" name="payment_proof" accept="image/jpeg,image/png,image/webp" required>

                    <div style="margin-top:10px;">
                        <button type="submit" class="btn" style="border:none;cursor:pointer;">Upload</button>
                        <span id="paymentUploadStatus" class="meta" style="margin-left:10px;"></span>
                    </div>
                </form>
            </div>
        </div>

        <script>
            (function() {
                const form = document.getElementById('paymentProofForm');
                const statusEl = document.getElementById('paymentUploadStatus');

                if (!form) return;

                form.addEventListener('submit', async function(e) {
                    e.preventDefault();

                    statusEl.textContent = 'Uploading...';

                    const fd = new FormData(form);

                    try {
                        const res = await fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
                            method: 'POST',
                            body: fd,
                            credentials: 'same-origin'
                        });

                        const data = await res.json();

                        if (data && data.success) {
                            statusEl.textContent = 'Uploaded successfully. Reloading...';
                            window.location.reload();
                        } else {
                            statusEl.textContent = (data && data.data && data.data.message) ? data.data.message : 'Upload failed.';
                        }
                    } catch (err) {
                        statusEl.textContent = 'Upload error.';
                    }
                });
            })();
        </script>


    <?php endif; ?>
</div>

<?php get_footer(); ?>