<?php
/**
 * Plugin Name: Custom Admin URL Protector
 * Plugin URI:  https://example.com/custom-admin-url
 * Description: Oculta wp-login.php y /wp-admin/ redirigiendo a una ruta personalizada para mitigar ataques de fuerza bruta. Totalmente compatible con WordPress 7.x y Multisite.
 * Version:     1.3.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author:      Senior WordPress Security Developer
 * Author URI:  https://example.com
 * License:     GPL-2.0+
 * Text Domain: custom-admin-url
 * Network:     true
 *
 * @package CustomAdminUrl
 */

// Evitar el acceso directo por seguridad.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Constantes globales.
define( 'CUSTOM_ADMIN_URL_VERSION', '1.3.0' );
define( 'CUSTOM_ADMIN_URL_PATH', plugin_dir_path( __FILE__ ) );
define( 'CUSTOM_ADMIN_URL_URL', plugin_dir_url( __FILE__ ) );
define( 'CUSTOM_ADMIN_URL_OPTION', 'custom_admin_url_slug' );

// Carga de clases.
require_once CUSTOM_ADMIN_URL_PATH . 'includes/class-plugin-core.php';
require_once CUSTOM_ADMIN_URL_PATH . 'includes/class-settings.php';

/**
 * Clase principal del plugin (Singleton).
 */
class Custom_Admin_URL_Protector {

	/**
	 * Instancia única de la clase.
	 *
	 * @var Custom_Admin_URL_Protector|null
	 */
	private static $instance = null;

	/**
	 * Obtener la instancia del Singleton.
	 *
	 * @return Custom_Admin_URL_Protector
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->init_hooks();
	}

	/**
	 * Inicializa los hooks de WordPress.
	 */
	private function init_hooks() {
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

		add_action( 'plugins_loaded', array( $this, 'load_components' ) );
	}

	/**
	 * Carga el núcleo y el panel de administración según el contexto.
	 */
	public function load_components() {
		new Custom_Admin_URL_Core();
		if ( is_admin() || is_network_admin() ) {
			new Custom_Admin_URL_Settings();
		}
	}

	/**
	 * Acciones de activación del plugin.
	 */
	public function activate() {
		if ( is_multisite() ) {
			if ( ! get_site_option( CUSTOM_ADMIN_URL_OPTION ) ) {
				add_site_option( CUSTOM_ADMIN_URL_OPTION, 'mi-acceso-secreto' );
			}
		} else {
			if ( ! get_option( CUSTOM_ADMIN_URL_OPTION ) ) {
				add_option( CUSTOM_ADMIN_URL_OPTION, 'mi-acceso-secreto' );
			}
		}
		flush_rewrite_rules();
	}

	/**
	 * Acciones de desactivación del plugin.
	 */
	public function deactivate() {
		flush_rewrite_rules();
	}
}

// Inicializar el plugin.
Custom_Admin_URL_Protector::get_instance();