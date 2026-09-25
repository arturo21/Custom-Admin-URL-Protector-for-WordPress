<?php
/**
 * Panel de ajustes e interfaz de usuario (compatible con Multisite).
 *
 * @package CustomAdminUrl
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Clase para registrar la página de opciones y sanitizar los campos.
 */
class Custom_Admin_URL_Settings {

	/**
	 * Constructor.
	 */
	public function __construct() {
		if ( is_multisite() ) {
			add_action( 'network_admin_menu', array( $this, 'add_network_settings_page' ) );
			add_action( 'network_admin_edit_custom_admin_url_save', array( $this, 'save_network_settings' ) );
		}

		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );

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
	 * Registra la opción en la API de Settings de WordPress.
	 */
	public function register_settings() {
		register_setting(
			'custom_admin_url_group',
			CUSTOM_ADMIN_URL_OPTION,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_slug' ),
				'default'           => 'mi-acceso-secreto',
			)
		);

		add_settings_section(
			'custom_admin_url_section',
			__( 'Configuración de la Ruta Secreta', 'custom-admin-url' ),
			array( $this, 'render_section_info' ),
			'custom-admin-url'
		);

		add_settings_field(
			'custom_admin_url_slug_field',
			__( 'Slug de Acceso Personalizado', 'custom-admin-url' ),
			array( $this, 'render_slug_field' ),
			'custom-admin-url',
			'custom_admin_url_section'
		);
	}

	/**
	 * Procesamiento manual del guardado de opciones en la Red (Multisite).
	 */
	public function save_network_settings() {
		check_admin_referer( 'custom_admin_url_network_save' );

		if ( isset( $_POST[ CUSTOM_ADMIN_URL_OPTION ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$raw_slug   = sanitize_text_field( wp_unslash( $_POST[ CUSTOM_ADMIN_URL_OPTION ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$clean_slug = $this->sanitize_slug( $raw_slug );
			update_site_option( CUSTOM_ADMIN_URL_OPTION, $clean_slug );
		}

		wp_safe_redirect( add_query_arg( array( 'page' => 'custom-admin-url', 'updated' => 'true' ), network_admin_url( 'settings.php' ) ) );
		exit;
	}

	/**
	 * Sanitización estricta para prevenir colisiones o caracteres inválidos.
	 *
	 * @param string $input Valor introducido por el usuario.
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
	 * Enviar un correo electrónico al administrador al cambiar la URL de acceso.
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

		$subject = sprintf( '[%s] Alerta de Seguridad: URL de administración actualizada', $site_name );

		$message  = sprintf( "Hola,\n\n" );
		$message .= sprintf( "Te informamos que la URL secreta de acceso a la administración en %s ha sido modificada.\n\n", $site_name );
		$message .= sprintf( "• Nueva URL de acceso: %s\n", $new_url );
		$message .= sprintf( "• Fecha y hora: %s\n", current_time( 'Y-m-d H:i:s' ) );
		$message .= sprintf( "• Realizado desde la IP: %s\n\n", $ip_address );
		$message .= "RECUERDA:\n";
		$message .= "Guarda esta nueva URL en tus marcadores. Si llegas a olvidar tu ruta de acceso, puedes desactivar la protección agregando la siguiente línea en tu archivo wp-config.php:\n\n";
		$message .= "define( 'CUSTOM_ADMIN_URL_DISABLE', true );\n\n";
		$message .= "Atentamente,\nCustom Admin URL Protector";

		wp_mail( $admin_email, $subject, $message );
	}

	/**
	 * Texto explicativo de la sección de ajustes.
	 */
	public function render_section_info() {
		echo '<p>' . esc_html__( 'Defina una ruta secreta para acceder al panel de control. Se enviará una alerta por correo electrónico tras cada modificación.', 'custom-admin-url' ) . '</p>';
	}

	/**
	 * Renderiza el campo de texto en el formulario.
	 */
	public function render_slug_field() {
		$value = is_multisite() ? get_site_option( CUSTOM_ADMIN_URL_OPTION, 'mi-acceso-secreto' ) : get_option( CUSTOM_ADMIN_URL_OPTION, 'mi-acceso-secreto' );
		echo '<code style="padding: 6px;">' . esc_url( home_url( '/' ) ) . '</code> ';
		echo '<input type="text" id="' . esc_attr( CUSTOM_ADMIN_URL_OPTION ) . '" name="' . esc_attr( CUSTOM_ADMIN_URL_OPTION ) . '" value="' . esc_attr( $value ) . '" class="regular-text" required />';
	}

	/**
	 * Renderiza la interfaz gráfica del panel de control.
	 */
	public function render_settings_page() {
		$is_network = is_network_admin();
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			
			<?php if ( isset( $_GET['updated'] ) && 'true' === $_GET['updated'] ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Ajustes de red guardados correctamente.', 'custom-admin-url' ); ?></p></div>
			<?php endif; ?>

			<form action="<?php echo $is_network ? esc_url( network_admin_url( 'edit.php?action=custom_admin_url_save' ) ) : 'options.php'; ?>" method="post">
				<?php
				if ( $is_network ) {
					wp_nonce_field( 'custom_admin_url_network_save' );
					echo '<table class="form-table"><tbody><tr><th scope="row">' . esc_html__( 'Slug de Acceso Personalizado', 'custom-admin-url' ) . '</th><td>';
					$this->render_slug_field();
					echo '</td></tr></tbody></table>';
				} else {
					settings_fields( 'custom_admin_url_group' );
					do_settings_sections( 'custom_admin_url' );
				}
				submit_button( __( 'Guardar Cambios', 'custom-admin-url' ) );
				?>
			</form>

			<div class="card" style="margin-top: 20px; max-width: 800px;">
				<h2><?php esc_html_e( 'Mecanismo de Recuperación de Emergencia', 'custom-admin-url' ); ?></h2>
				<p>
					<?php esc_html_e( 'Si olvida su slug de acceso y no puede entrar al sitio, agregue la siguiente línea en su archivo wp-config.php para desactivar la protección temporalmente:', 'custom-admin-url' ); ?>
				</p>
				<code>define( 'CUSTOM_ADMIN_URL_DISABLE', true );</code>
			</div>
		</div>
		<?php
	}
}
