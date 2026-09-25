<?php
/**
 * Limpieza de datos en sitios individuales y redes Multisite al desinstalar.
 *
 * @package CustomAdminUrl
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Eliminar opción en sitio independiente.
delete_option( 'custom_admin_url_slug' );

// Eliminar opción en red Multisite si aplica.
if ( is_multisite() ) {
	delete_site_option( 'custom_admin_url_slug' );
}

// Restablecer reglas de reescritura.
flush_rewrite_rules();
