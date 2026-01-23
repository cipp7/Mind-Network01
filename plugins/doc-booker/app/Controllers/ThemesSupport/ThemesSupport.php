<?php
/**
 * @package WpdDocBooker
 */
namespace WpDreamers\WPDDB\Controllers\ThemesSupport;



use WpDreamers\WPDDB\Traits\SingletonTrait;
use WpDreamers\WPDDB\Controllers\ThemesSupport\Astra\ThemeSupport as AstraThemeSupport;


if ( ! defined( 'ABSPATH' ) ) {
	exit( 'This script cannot be accessed directly.' );
}
class ThemesSupport{

	use SingleTonTrait;
	public $current_theme;

	public function __construct() {

		$theme = $this->get_theme_name();
		if ('Astra' === $theme){
			AstraThemeSupport::instance();
		}

	}

	public  function get_theme_name(  ) {
		$theme = wp_get_theme();
		$this->current_theme = $theme->name;
		return $this->current_theme;
	}

}