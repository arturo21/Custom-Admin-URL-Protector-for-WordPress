<?php
/**
 * Limpieza de datos en sitios individuales y redes Multisite al desinstalar.
 *
 * @package CustomAdminUrl
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Eliminar opciones en sitio independiente.
delete_option( 'custom_admin_url_slug' );
delete_option( 'custom_admin_url_action' );
delete_option( 'custom_admin_url_email_subject' );
delete_option( 'custom_admin_url_email_body' );
delete_option( 'custom_admin_url_logs' );

// Eliminar opciones en red Multisite si aplica.
if ( is_multisite() ) {
	delete_site_option( 'custom_admin_url_slug' );
	delete_site_option( 'custom_admin_url_action' );
	delete_site_option( 'custom_admin_url_email_subject' );
	delete_site_option( 'custom_admin_url_email_body' );
	delete_site_option( 'custom_admin_url_logs' );
}

// Restablecer reglas de reescritura.
flush_rewrite_rules();
