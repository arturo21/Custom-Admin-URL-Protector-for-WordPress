<?php
/**
 * Panel de ajustes e interfaz de usuario interactiva con pestañas, notificaciones e historial de logs.
 * Compatible con WordPress 7.x y Multisite.
 *
 * @package CustomAdminUrl
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Clase para registrar la página de opciones, pestañas, notificaciones e historial.
 */
class Custom_Admin_URL_Settings {

	/**
	 * Constructor.
	 */
	public function __construct() {
		if ( is_multisite() ) {
			add_action( 'network_admin_menu', array( $this, 'add_network_settings_page' ) );
			add_action( 'network_admin_edit_custom_admin_url_save', array( $this, 'save_network_settings' ) );
			add_action( 'network_admin_edit_custom_admin_url_clear_logs', array( $this, 'clear_network_logs' ) );
		}

		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_post_custom_admin_url_clear_logs', array( $this, 'clear_logs' ) );

		add_action( 'update_option_' . CUSTOM_ADMIN_URL_OPTION, array( $this, 'notify_admin_on_slug_change' ), 10, 2 );
		add_action( 'update_site_option_' . CUSTOM_ADMIN_URL_OPTION, array( $this, 'notify_admin_on_slug_change' ), 10, 2 );
	}

	/**
	 * Agrega una subpágina dentro del menú 'Ajustes' de WordPress.
	 */
	public function add_settings_page() {
		add_options_page(
			__( 'Protector de URL de Administración', 'custom-admin-url' ),
			__( 'URL de Administración', 'custom-admin-url' ),
			'manage_options',
			'custom-admin-url',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Agrega una subpágina dentro del menú 'Ajustes' de la Red (Multisite).
	 */
	public function add_network_settings_page() {
		add_submenu_page(
			'settings.php',
			__( 'Protector de URL de Administración de la Red', 'custom-admin-url' ),
			__( 'URL de Administración', 'custom-admin-url' ),
			'manage_network_options',
			'custom-admin-url',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Registra las opciones en la API de Settings de WordPress.
	 */
	public function register_settings() {
		// 1. Slug
		register_setting(
			'custom_admin_url_group',
			CUSTOM_ADMIN_URL_OPTION,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_slug' ),
				'default'           => 'mi-acceso-secreto',
			)
		);

		// 2. Acción al bloquear (404 o home)
		register_setting(
			'custom_admin_url_group',
			CUSTOM_ADMIN_URL_ACTION_OPTION,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '404',
			)
		);

		// 3. Notificación - Asunto
		register_setting(
			'custom_admin_url_group',
			CUSTOM_ADMIN_URL_EMAIL_SUBJECT_OPTION,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '[{site_name}] Alerta de Seguridad: URL de administración actualizada',
			)
		);

		// 4. Notificación - Cuerpo
		register_setting(
			'custom_admin_url_group',
			CUSTOM_ADMIN_URL_EMAIL_BODY_OPTION,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_textarea_field',
				'default'           => "Hola,\n\nTe informamos que la URL secreta de acceso a la administración en {site_name} ha sido modificada.\n\n• Nueva URL de acceso: {new_url}\n• Fecha y hora: {date_time}\n• Realizado desde la IP: {ip_address}\n\nRECUERDA:\nGuarda esta nueva URL en tus marcadores. Si llegas a olvidar tu ruta de acceso, puedes desactivar la protección agregando la siguiente línea en tu archivo wp-config.php:\n\ndefine( 'CUSTOM_ADMIN_URL_DISABLE', true );\n\nAtentamente,\nCustom Admin URL Protector",
			)
		);
	}

	/**
	 * Procesamiento manual de opciones en la Red (Multisite).
	 */
	public function save_network_settings() {
		check_admin_referer( 'custom_admin_url_network_save' );

		if ( isset( $_POST[ CUSTOM_ADMIN_URL_OPTION ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$raw_slug   = sanitize_text_field( wp_unslash( $_POST[ CUSTOM_ADMIN_URL_OPTION ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$clean_slug = $this->sanitize_slug( $raw_slug );
			update_site_option( CUSTOM_ADMIN_URL_OPTION, $clean_slug );
		}

		if ( isset( $_POST[ CUSTOM_ADMIN_URL_ACTION_OPTION ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$action = sanitize_text_field( wp_unslash( $_POST[ CUSTOM_ADMIN_URL_ACTION_OPTION ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			update_site_option( CUSTOM_ADMIN_URL_ACTION_OPTION, in_array( $action, array( '404', 'home' ), true ) ? $action : '404' );
		}

		if ( isset( $_POST[ CUSTOM_ADMIN_URL_EMAIL_SUBJECT_OPTION ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$subject = sanitize_text_field( wp_unslash( $_POST[ CUSTOM_ADMIN_URL_EMAIL_SUBJECT_OPTION ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			update_site_option( CUSTOM_ADMIN_URL_EMAIL_SUBJECT_OPTION, $subject );
		}

		if ( isset( $_POST[ CUSTOM_ADMIN_URL_EMAIL_BODY_OPTION ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$body = sanitize_textarea_field( wp_unslash( $_POST[ CUSTOM_ADMIN_URL_EMAIL_BODY_OPTION ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			update_site_option( CUSTOM_ADMIN_URL_EMAIL_BODY_OPTION, $body );
		}

		$tab = isset( $_POST['current_tab'] ) ? sanitize_text_field( wp_unslash( $_POST['current_tab'] ) ) : 'general'; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		wp_safe_redirect( add_query_arg( array( 'page' => 'custom-admin-url', 'tab' => $tab, 'updated' => 'true' ), network_admin_url( 'settings.php' ) ) );
		exit;
	}

	/**
	 * Limpiar historial de logs para sitios individuales.
	 */
	public function clear_logs() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'No tienes permisos suficientes.', 'custom-admin-url' ) );
		}

		check_admin_referer( 'custom_admin_url_clear_logs_action' );
		update_option( CUSTOM_ADMIN_URL_LOGS_OPTION, array() );

		wp_safe_redirect( add_query_arg( array( 'page' => 'custom-admin-url', 'tab' => 'logs', 'logs_cleared' => 'true' ), admin_url( 'options-general.php' ) ) );
		exit;
	}

	/**
	 * Limpiar historial de logs para la red Multisite.
	 */
	public function clear_network_logs() {
		if ( ! current_user_can( 'manage_network_options' ) ) {
			wp_die( esc_html__( 'No tienes permisos suficientes.', 'custom-admin-url' ) );
		}

		check_admin_referer( 'custom_admin_url_clear_logs_action' );
		update_site_option( CUSTOM_ADMIN_URL_LOGS_OPTION, array() );

		wp_safe_redirect( add_query_arg( array( 'page' => 'custom-admin-url', 'tab' => 'logs', 'logs_cleared' => 'true' ), network_admin_url( 'settings.php' ) ) );
		exit;
	}

	/**
	 * Sanitización del slug.
	 *
	 * @param string $input Slug introducido.
	 * @return string Slug sanitizado.
	 */
	public function sanitize_slug( $input ) {
		$slug = sanitize_title( trim( $input ) );

		$forbidden_slugs = array(
			'wp-admin',
			'wp-content',
			'wp-includes',
			'wp-login.php',
			'admin',
			'login',
			'dashboard',
			'feed',
			'embed',
			'robots.txt',
		);

		if ( empty( $slug ) || in_array( $slug, $forbidden_slugs, true ) ) {
			add_settings_error(
				CUSTOM_ADMIN_URL_OPTION,
				'invalid_slug',
				__( 'El slug ingresado no es válido o está reservado por el sistema. Se ha conservado la configuración previa.', 'custom-admin-url' ),
				'error'
			);
			return is_multisite() ? get_site_option( CUSTOM_ADMIN_URL_OPTION, 'mi-acceso-secreto' ) : get_option( CUSTOM_ADMIN_URL_OPTION, 'mi-acceso-secreto' );
		}

		return $slug;
	}

	/**
	 * Enviar un correo electrónico personalizado al administrador tras modificar el slug.
	 *
	 * @param string $old_slug Slug anterior.
	 * @param string $new_slug Nuevo slug.
	 */
	public function notify_admin_on_slug_change( $old_slug, $new_slug ) {
		if ( $old_slug === $new_slug || empty( $new_slug ) ) {
			return;
		}

		$admin_email = is_multisite() ? get_site_option( 'admin_email' ) : get_option( 'admin_email' );
		if ( ! $admin_email ) {
			$admin_email = get_option( 'admin_email' );
		}

		$site_name  = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
		$new_url    = home_url( '/' . $new_slug );
		$ip_address = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'Desconocida';
		$date_time  = current_time( 'Y-m-d H:i:s' );

		// Obtener plantillas personalizadas.
		$default_subject = '[{site_name}] Alerta de Seguridad: URL de administración actualizada';
		$default_body    = "Hola,\n\nTe informamos que la URL secreta de acceso a la administración en {site_name} ha sido modificada.\n\n• Nueva URL de acceso: {new_url}\n• Fecha y hora: {date_time}\n• Realizado desde la IP: {ip_address}\n\nRECUERDA:\nGuarda esta nueva URL en tus marcadores. Si llegas a olvidar tu ruta de acceso, puedes desactivar la protección agregando la siguiente línea en tu archivo wp-config.php:\n\ndefine( 'CUSTOM_ADMIN_URL_DISABLE', true );\n\nAtentamente,\nCustom Admin URL Protector";

		$raw_subject = is_multisite() ? get_site_option( CUSTOM_ADMIN_URL_EMAIL_SUBJECT_OPTION, $default_subject ) : get_option( CUSTOM_ADMIN_URL_EMAIL_SUBJECT_OPTION, $default_subject );
		$raw_body    = is_multisite() ? get_site_option( CUSTOM_ADMIN_URL_EMAIL_BODY_OPTION, $default_body ) : get_option( CUSTOM_ADMIN_URL_EMAIL_BODY_OPTION, $default_body );

		// Reemplazar etiquetas variables.
		$replacements = array(
			'{site_name}'  => $site_name,
			'{new_url}'    => $new_url,
			'{date_time}'  => $date_time,
			'{ip_address}' => $ip_address,
		);

		$subject = str_replace( array_keys( $replacements ), array_values( $replacements ), $raw_subject );
		$message = str_replace( array_keys( $replacements ), array_values( $replacements ), $raw_body );

		wp_mail( $admin_email, $subject, $message );
	}

	/**
	 * Renderiza la interfaz gráfica del módulo con pestañas.
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'manage_network_options' ) ) {
			return;
		}

		$is_network  = is_network_admin();
		$active_tab  = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'general';
		$slug_value  = $is_network ? get_site_option( CUSTOM_ADMIN_URL_OPTION, 'mi-acceso-secreto' ) : get_option( CUSTOM_ADMIN_URL_OPTION, 'mi-acceso-secreto' );
		$action_val  = $is_network ? get_site_option( CUSTOM_ADMIN_URL_ACTION_OPTION, '404' ) : get_option( CUSTOM_ADMIN_URL_ACTION_OPTION, '404' );
		$full_access_url = home_url( '/' . $slug_value );
		$is_disabled = defined( 'CUSTOM_ADMIN_URL_DISABLE' ) && CUSTOM_ADMIN_URL_DISABLE;

		$page_url = $is_network ? network_admin_url( 'settings.php?page=custom-admin-url' ) : admin_url( 'options-general.php?page=custom-admin-url' );
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<?php if ( isset( $_GET['updated'] ) && 'true' === $_GET['updated'] ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Ajustes guardados correctamente.', 'custom-admin-url' ); ?></p></div>
			<?php endif; ?>

			<?php if ( isset( $_GET['logs_cleared'] ) && 'true' === $_GET['logs_cleared'] ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'El historial de eventos ha sido vaciado.', 'custom-admin-url' ); ?></p></div>
			<?php endif; ?>

			<!-- Indicador de Estado -->
			<div class="notice <?php echo $is_disabled ? 'notice-warning' : 'notice-info'; ?>" style="margin-top: 15px; padding: 10px 15px;">
				<p style="margin: 0; font-size: 14px;">
					<?php if ( $is_disabled ) : ?>
						<strong>⚠️ <?php esc_html_e( 'ESTADO: PROTECCIÓN PAUSADA', 'custom-admin-url' ); ?></strong> — <?php esc_html_e( 'La constante CUSTOM_ADMIN_URL_DISABLE está activa en wp-config.php. Las rutas /wp-login.php y /wp-admin/ se encuentran accesibles.', 'custom-admin-url' ); ?>
					<?php else : ?>
						<strong>🔒 <?php esc_html_e( 'ESTADO: PROTECCIÓN ACTIVA', 'custom-admin-url' ); ?></strong> — <?php esc_html_e( 'Ruta actual de acceso:', 'custom-admin-url' ); ?> <code><?php echo esc_url( $full_access_url ); ?></code>
					<?php endif; ?>
				</p>
			</div>

			<!-- Pestañas de Navegación -->
			<h2 class="nav-tab-wrapper">
				<a href="<?php echo esc_url( add_query_arg( 'tab', 'general', $page_url ) ); ?>" class="nav-tab <?php echo 'general' === $active_tab ? 'nav-tab-active' : ''; ?>">
					⚙️ <?php esc_html_e( 'Ajustes Generales', 'custom-admin-url' ); ?>
				</a>
				<a href="<?php echo esc_url( add_query_arg( 'tab', 'email', $page_url ) ); ?>" class="nav-tab <?php echo 'email' === $active_tab ? 'nav-tab-active' : ''; ?>">
					✉️ <?php esc_html_e( 'Notificaciones por Correo', 'custom-admin-url' ); ?>
				</a>
				<a href="<?php echo esc_url( add_query_arg( 'tab', 'logs', $page_url ) ); ?>" class="nav-tab <?php echo 'logs' === $active_tab ? 'nav-tab-active' : ''; ?>">
					📋 <?php esc_html_e( 'Historial de Logs', 'custom-admin-url' ); ?>
				</a>
			</h2>

			<?php if ( 'general' === $active_tab ) : ?>
				<form action="<?php echo $is_network ? esc_url( network_admin_url( 'edit.php?action=custom_admin_url_save' ) ) : 'options.php'; ?>" method="post">
					<?php
					if ( $is_network ) {
						wp_nonce_field( 'custom_admin_url_network_save' );
						echo '<input type="hidden" name="current_tab" value="general" />';
					} else {
						settings_fields( 'custom_admin_url_group' );
					}
					?>
					<table class="form-table">
						<tbody>
							<tr>
								<th scope="row"><label for="<?php echo esc_attr( CUSTOM_ADMIN_URL_OPTION ); ?>"><?php esc_html_e( 'Slug de Acceso Personalizado', 'custom-admin-url' ); ?></label></th>
								<td>
									<code><?php echo esc_url( home_url( '/' ) ); ?></code>
									<input type="text" id="<?php echo esc_attr( CUSTOM_ADMIN_URL_OPTION ); ?>" name="<?php echo esc_attr( CUSTOM_ADMIN_URL_OPTION ); ?>" value="<?php echo esc_attr( $slug_value ); ?>" class="regular-text" required />
									<p class="description"><?php esc_html_e( 'Escriba la palabra o slug secreto. Se aplicará inmediatamente al guardar.', 'custom-admin-url' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Acción ante Accesos No Autorizados', 'custom-admin-url' ); ?></th>
								<td>
									<label>
										<input type="radio" name="<?php echo esc_attr( CUSTOM_ADMIN_URL_ACTION_OPTION ); ?>" value="404" <?php checked( $action_val, '404' ); ?> />
										<strong><?php esc_html_e( 'Mostrar Página 404 Not Found (Recomendado)', 'custom-admin-url' ); ?></strong>
									</label><br/>
									<label>
										<input type="radio" name="<?php echo esc_attr( CUSTOM_ADMIN_URL_ACTION_OPTION ); ?>" value="home" <?php checked( $action_val, 'home' ); ?> />
										<strong><?php esc_html_e( 'Redirigir a la Página de Inicio (Home)', 'custom-admin-url' ); ?></strong>
									</label>
									<p class="description"><?php esc_html_e( 'Seleccione la respuesta que recibirán los atacantes o escáneres que intenten acceder por /wp-login.php o /wp-admin/.', 'custom-admin-url' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'URL Completa Resultante', 'custom-admin-url' ); ?></th>
								<td>
									<input type="text" id="custom-admin-url-copy-input" readonly value="<?php echo esc_url( $full_access_url ); ?>" class="large-text code" style="background: #f0f0f1;" />
									<button type="button" class="button button-secondary" onclick="navigator.clipboard.writeText(document.getElementById('custom-admin-url-copy-input').value); alert('URL copiada al portapapeles');">
										📋 <?php esc_html_e( 'Copiar URL', 'custom-admin-url' ); ?>
									</button>
								</td>
							</tr>
						</tbody>
					</table>
					<?php submit_button( __( 'Guardar Cambios de URL', 'custom-admin-url' ) ); ?>
				</form>

			<?php elseif ( 'email' === $active_tab ) : ?>
				<?php
				$default_subject = '[{site_name}] Alerta de Seguridad: URL de administración actualizada';
				$default_body    = "Hola,\n\nTe informamos que la URL secreta de acceso a la administración en {site_name} ha sido modificada.\n\n• Nueva URL de acceso: {new_url}\n• Fecha y hora: {date_time}\n• Realizado desde la IP: {ip_address}\n\nRECUERDA:\nGuarda esta nueva URL en tus marcadores. Si llegas a olvidar tu ruta de acceso, puedes desactivar la protección agregando la siguiente línea en tu archivo wp-config.php:\n\ndefine( 'CUSTOM_ADMIN_URL_DISABLE', true );\n\nAtentamente,\nCustom Admin URL Protector";

				$subject_val = $is_network ? get_site_option( CUSTOM_ADMIN_URL_EMAIL_SUBJECT_OPTION, $default_subject ) : get_option( CUSTOM_ADMIN_URL_EMAIL_SUBJECT_OPTION, $default_subject );
				$body_val    = $is_network ? get_site_option( CUSTOM_ADMIN_URL_EMAIL_BODY_OPTION, $default_body ) : get_option( CUSTOM_ADMIN_URL_EMAIL_BODY_OPTION, $default_body );
				?>
				<form action="<?php echo $is_network ? esc_url( network_admin_url( 'edit.php?action=custom_admin_url_save' ) ) : 'options.php'; ?>" method="post">
					<?php
					if ( $is_network ) {
						wp_nonce_field( 'custom_admin_url_network_save' );
						echo '<input type="hidden" name="current_tab" value="email" />';
					} else {
						settings_fields( 'custom_admin_url_group' );
					}
					?>
					<table class="form-table">
						<tbody>
							<tr>
								<th scope="row"><label for="<?php echo esc_attr( CUSTOM_ADMIN_URL_EMAIL_SUBJECT_OPTION ); ?>"><?php esc_html_e( 'Asunto del Correo', 'custom-admin-url' ); ?></label></th>
								<td>
									<input type="text" id="<?php echo esc_attr( CUSTOM_ADMIN_URL_EMAIL_SUBJECT_OPTION ); ?>" name="<?php echo esc_attr( CUSTOM_ADMIN_URL_EMAIL_SUBJECT_OPTION ); ?>" value="<?php echo esc_attr( $subject_val ); ?>" class="large-text" required />
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="<?php echo esc_attr( CUSTOM_ADMIN_URL_EMAIL_BODY_OPTION ); ?>"><?php esc_html_e( 'Cuerpo del Mensaje', 'custom-admin-url' ); ?></label></th>
								<td>
									<textarea id="<?php echo esc_attr( CUSTOM_ADMIN_URL_EMAIL_BODY_OPTION ); ?>" name="<?php echo esc_attr( CUSTOM_ADMIN_URL_EMAIL_BODY_OPTION ); ?>" rows="12" class="large-text code" required><?php echo esc_textarea( $body_val ); ?></textarea>
									<p class="description">
										<strong><?php esc_html_e( 'Etiquetas dinámicas disponibles:', 'custom-admin-url' ); ?></strong><br/>
										<code>{site_name}</code> — Nombre del sitio | <code>{new_url}</code> — Nueva URL de acceso | <code>{date_time}</code> — Fecha y hora | <code>{ip_address}</code> — Dirección IP
									</p>
								</td>
							</tr>
						</tbody>
					</table>
					<?php submit_button( __( 'Guardar Plantilla de Correo', 'custom-admin-url' ) ); ?>
				</form>

			<?php elseif ( 'logs' === $active_tab ) : ?>
				<?php
				$logs = $is_network ? get_site_option( CUSTOM_ADMIN_URL_LOGS_OPTION, array() ) : get_option( CUSTOM_ADMIN_URL_LOGS_OPTION, array() );
				if ( ! is_array( $logs ) ) {
					$logs = array();
				}
				?>
				<div style="margin-top: 15px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center;">
					<p style="font-size: 14px; margin: 0;">
						<?php printf( esc_html__( 'Registros de accesos no autorizados bloqueados (Mostrando %d eventos recientes, máximo 100):', 'custom-admin-url' ), count( $logs ) ); ?>
					</p>
					<?php if ( ! empty( $logs ) ) : ?>
						<form action="<?php echo $is_network ? esc_url( network_admin_url( 'edit.php?action=custom_admin_url_clear_logs' ) ) : esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
							<?php wp_nonce_field( 'custom_admin_url_clear_logs_action' ); ?>
							<?php if ( ! $is_network ) : ?>
								<input type="hidden" name="action" value="custom_admin_url_clear_logs" />
							<?php endif; ?>
							<button type="submit" class="button button-secondary" onclick="return confirm('¿Está seguro de que desea vaciar todo el historial de logs?');">
								🗑️ <?php esc_html_e( 'Vaciar Historial de Logs', 'custom-admin-url' ); ?>
							</button>
						</form>
					<?php endif; ?>
				</div>

				<table class="wp-list-table widefat fixed striped table-view-list">
					<thead>
						<tr>
							<th style="width: 170px;"><?php esc_html_e( 'Fecha y Hora', 'custom-admin-url' ); ?></th>
							<th style="width: 140px;"><?php esc_html_e( 'Dirección IP', 'custom-admin-url' ); ?></th>
							<th style="width: 200px;"><?php esc_html_e( 'Ruta Solicitada', 'custom-admin-url' ); ?></th>
							<th><?php esc_html_e( 'User-Agent (Navegador/Bot)', 'custom-admin-url' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $logs ) ) : ?>
							<tr>
								<td colspan="4" style="text-align: center; padding: 20px; color: #666;">
									✨ <?php esc_html_e( 'No hay intentos de acceso bloqueados registrados en el historial.', 'custom-admin-url' ); ?>
								</td>
							</tr>
						<?php else : ?>
							<?php foreach ( $logs as $entry ) : ?>
								<tr>
									<td><code><?php echo esc_html( isset( $entry['time'] ) ? $entry['time'] : '—' ); ?></code></td>
									<td><code><?php echo esc_html( isset( $entry['ip'] ) ? $entry['ip'] : '—' ); ?></code></td>
									<td><strong style="color: #d63638;"><?php echo esc_html( isset( $entry['uri'] ) ? $entry['uri'] : '—' ); ?></strong></td>
									<td style="font-size: 12px; color: #50575e;"><?php echo esc_html( isset( $entry['user_agent'] ) ? $entry['user_agent'] : '—' ); ?></td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>

			<?php endif; ?>

			<div class="card" style="margin-top: 30px; max-width: 800px;">
				<h2><?php esc_html_e( 'Mecanismo de Recuperación de Emergencia', 'custom-admin-url' ); ?></h2>
				<p>
					<?php esc_html_e( 'Si olvida su slug de acceso y no puede ingresar al panel, agregue la siguiente línea en su archivo wp-config.php para pausar la protección temporalmente:', 'custom-admin-url' ); ?>
				</p>
				<code>define( 'CUSTOM_ADMIN_URL_DISABLE', true );</code>
			</div>
		</div>
		<?php
	}
}
