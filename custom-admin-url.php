<?php
/**
 * Plugin Name: Custom Admin URL Protector
 * Plugin URI:  https://example.com/custom-admin-url
 * Description: Oculta wp-login.php y /wp-admin/ redirigiendo a una ruta personalizada para mitigar ataques de fuerza bruta, compatible con WordPress 7.x y Multisite. Incluye notificaciones personalizables e historial de logs.
 * Version:     1.5.0
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
define( 'CUSTOM_ADMIN_URL_VERSION', '1.5.0' );
define( 'CUSTOM_ADMIN_URL_PATH', plugin_dir_path( __FILE__ ) );
define( 'CUSTOM_ADMIN_URL_URL', plugin_dir_url( __FILE__ ) );

define( 'CUSTOM_ADMIN_URL_OPTION', 'custom_admin_url_slug' );
define( 'CUSTOM_ADMIN_URL_ACTION_OPTION', 'custom_admin_url_action' );
define( 'CUSTOM_ADMIN_URL_EMAIL_SUBJECT_OPTION', 'custom_admin_url_email_subject' );
define( 'CUSTOM_ADMIN_URL_EMAIL_BODY_OPTION', 'custom_admin_url_email_body' );
define( 'CUSTOM_ADMIN_URL_LOGS_OPTION', 'custom_admin_url_logs' );

// Carga de clases.
require_once CUSTOM_ADMIN_URL_PATH . 'includes/class-plugin-core.php';
require_once CUSTOM_ADMIN_URL_PATH . 'includes/class-settings.php';

/**
 * Clase principal del plugin (Singleton).
 */
class Custom_Admin_URL_Protector {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->init_hooks();
	}

	private function init_hooks() {
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

		add_action( 'plugins_loaded', array( $this, 'load_components' ) );
	}

	public function load_components() {
		new Custom_Admin_URL_Core();
		if ( is_admin() || is_network_admin() ) {
			new Custom_Admin_URL_Settings();
		}
	}

	public function activate() {
		$default_body = "Hola,\n\nTe informamos que la URL secreta de acceso a la administración en {site_name} ha sido modificada.\n\n• Nueva URL de acceso: {new_url}\n• Fecha y hora: {date_time}\n• Realizado desde la IP: {ip_address}\n\nRECUERDA:\nGuarda esta nueva URL en tus marcadores. Si llegas a olvidar tu ruta de acceso, puedes desactivar la protección agregando la siguiente línea en tu archivo wp-config.php:\n\ndefine( 'CUSTOM_ADMIN_URL_DISABLE', true );\n\nAtentamente,\nCustom Admin URL Protector";
		$default_subject = '[{site_name}] Alerta de Seguridad: URL de administración actualizada';

		if ( is_multisite() ) {
			if ( ! get_site_option( CUSTOM_ADMIN_URL_OPTION ) ) {
				add_site_option( CUSTOM_ADMIN_URL_OPTION, 'mi-acceso-secreto' );
			}
			if ( ! get_site_option( CUSTOM_ADMIN_URL_ACTION_OPTION ) ) {
				add_site_option( CUSTOM_ADMIN_URL_ACTION_OPTION, '404' );
			}
			if ( ! get_site_option( CUSTOM_ADMIN_URL_EMAIL_SUBJECT_OPTION ) ) {
				add_site_option( CUSTOM_ADMIN_URL_EMAIL_SUBJECT_OPTION, $default_subject );
			}
			if ( ! get_site_option( CUSTOM_ADMIN_URL_EMAIL_BODY_OPTION ) ) {
				add_site_option( CUSTOM_ADMIN_URL_EMAIL_BODY_OPTION, $default_body );
			}
			if ( false === get_site_option( CUSTOM_ADMIN_URL_LOGS_OPTION ) ) {
				add_site_option( CUSTOM_ADMIN_URL_LOGS_OPTION, array() );
			}
		} else {
			if ( ! get_option( CUSTOM_ADMIN_URL_OPTION ) ) {
				add_option( CUSTOM_ADMIN_URL_OPTION, 'mi-acceso-secreto' );
			}
			if ( ! get_option( CUSTOM_ADMIN_URL_ACTION_OPTION ) ) {
				add_option( CUSTOM_ADMIN_URL_ACTION_OPTION, '404' );
			}
			if ( ! get_option( CUSTOM_ADMIN_URL_EMAIL_SUBJECT_OPTION ) ) {
				add_option( CUSTOM_ADMIN_URL_EMAIL_SUBJECT_OPTION, $default_subject );
			}
			if ( ! get_option( CUSTOM_ADMIN_URL_EMAIL_BODY_OPTION ) ) {
				add_option( CUSTOM_ADMIN_URL_EMAIL_BODY_OPTION, $default_body );
			}
			if ( false === get_option( CUSTOM_ADMIN_URL_LOGS_OPTION ) ) {
				add_option( CUSTOM_ADMIN_URL_LOGS_OPTION, array() );
			}
		}
		flush_rewrite_rules();
	}

	public function deactivate() {
		flush_rewrite_rules();
	}
}

// Inicializar el plugin.
Custom_Admin_URL_Protector::get_instance();
