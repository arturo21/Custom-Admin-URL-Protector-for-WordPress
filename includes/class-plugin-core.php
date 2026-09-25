<?php
/**
 * Lógica principal de protección, interceptación de URLs y registro de eventos de seguridad.
 * Compatible con WordPress 7.x, Multisite e instalaciones en subdirectorios.
 *
 * @package CustomAdminUrl
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Clase encargada de filtrar las solicitudes HTTP y reconstruir las URLs de acceso.
 */
class Custom_Admin_URL_Core {

	/**
	 * Slug personalizado para el acceso.
	 *
	 * @var string
	 */
	private $secret_slug;

	/**
	 * Acción a realizar ante accesos bloqueados ('404' o 'home').
	 *
	 * @var string
	 */
	private $block_action;

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Mecanismo de rescate/emergencia mediante constante en wp-config.php.
		if ( defined( 'CUSTOM_ADMIN_URL_DISABLE' ) && CUSTOM_ADMIN_URL_DISABLE ) {
			return;
		}

		$this->secret_slug  = $this->get_option_value( CUSTOM_ADMIN_URL_OPTION, 'mi-acceso-secreto' );
		$this->block_action = $this->get_option_value( CUSTOM_ADMIN_URL_ACTION_OPTION, '404' );

		if ( empty( $this->secret_slug ) ) {
			return;
		}

		// Interceptación de peticiones en la inicialización temprana de WordPress.
		add_action( 'init', array( $this, 'handle_login_request' ), 1 );

		// Filtrar funciones nativas de generación de URLs.
		add_filter( 'site_url', array( $this, 'filter_site_url' ), 10, 4 );
		add_filter( 'network_site_url', array( $this, 'filter_site_url' ), 10, 4 );
		add_filter( 'wp_redirect', array( $this, 'filter_wp_redirect' ), 10, 2 );
	}

	/**
	 * Obtener valor de opción respetando entorno Multisite.
	 *
	 * @param string $option_name Nombre de la opción.
	 * @param mixed  $default     Valor por defecto.
	 * @return mixed
	 */
	private function get_option_value( $option_name, $default = false ) {
		if ( is_multisite() ) {
			return get_site_option( $option_name, $default );
		}
		return get_option( $option_name, $default );
	}

	/**
	 * Procesa y valida la petición HTTP entrante.
	 */
	public function handle_login_request() {
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		$req_path    = wp_parse_url( $request_uri, PHP_URL_PATH );
		if ( ! $req_path ) {
			$req_path = '';
		}

		// Ajustar el path removiendo el subdirectorio de instalación si aplica.
		$site_path = wp_parse_url( home_url(), PHP_URL_PATH );
		if ( $site_path && '/' !== $site_path && 0 === strpos( $req_path, $site_path ) ) {
			$path_only = substr( $req_path, strlen( $site_path ) );
		} else {
			$path_only = $req_path;
		}

		$parsed_path = trim( $path_only, '/' );

		// 1. Acceso mediante la URL secreta personalizada.
		if ( $parsed_path === $this->secret_slug ) {
			$this->render_custom_login_page();
			exit;
		}

		// 2. Interceptar intentos directos no autorizados a wp-login.php o /wp-admin/.
		if ( $this->is_default_login_path( $parsed_path ) ) {
			if ( $this->is_allowed_request( $parsed_path ) ) {
				return;
			}

			// Registrar el intento bloqueado en los logs del servidor y base de datos.
			$this->log_unauthorized_access( $request_uri );

			// Aplicar acción configurada por el usuario (404 o Redirección a la portada).
			if ( 'home' === $this->block_action ) {
				wp_safe_redirect( home_url( '/' ), 302 );
				exit;
			} else {
				$this->block_access_with_404();
			}
		}
	}

	/**
	 * Determina si la ruta intentada corresponde a las rutas por defecto de administración.
	 *
	 * @param string $path Ruta solicitada.
	 * @return bool
	 */
	private function is_default_login_path( $path ) {
		return ( 'wp-login.php' === $path || 'wp-admin' === $path || 'wp-admin/' === substr( $path, 0, 9 ) );
	}

	/**
	 * Comprueba si la solicitud es una excepción legítima.
	 *
	 * @param string $parsed_path Ruta procesada.
	 * @return bool
	 */
	private function is_allowed_request( $parsed_path ) {
		if ( is_user_logged_in() ) {
			return true;
		}

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return true;
		}

		if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
			return true;
		}

		if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
			return true;
		}

		$basename = wp_basename( $parsed_path );
		if ( in_array( $basename, array( 'admin-ajax.php', 'admin-post.php', 'async-upload.php' ), true ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Carga internamente el formulario estándar de wp-login.php.
	 */
	private function render_custom_login_page() {
		global $error, $interim_login, $action, $user_login;

		if ( ! defined( 'LOGIN_COOKIE_PARAM' ) ) {
			define( 'LOGIN_COOKIE_PARAM', 'wordpress_logged_in_' );
		}

		require_once ABSPATH . 'wp-login.php';
	}

	/**
	 * Registrar en los logs de seguridad y guardar evento en el historial del módulo.
	 *
	 * @param string $requested_uri Ruta solicitada.
	 */
	private function log_unauthorized_access( $requested_uri ) {
		$ip_address = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'Desconocida';
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : 'No especificado';
		$timestamp  = current_time( 'mysql' );

		// Log en el servidor web (error_log).
		$log_entry = sprintf(
			'[Custom Admin URL Protector] Bloqueo de acceso no autorizado -> Ruta: %s | IP: %s | User-Agent: %s | Fecha: %s',
			$requested_uri,
			$ip_address,
			$user_agent,
			$timestamp
		);
		error_log( $log_entry );

		// Guardar en la opción de logs del plugin (máximo 100 registros).
		$logs = $this->get_option_value( CUSTOM_ADMIN_URL_LOGS_OPTION, array() );
		if ( ! is_array( $logs ) ) {
			$logs = array();
		}

		$new_entry = array(
			'time'       => $timestamp,
			'uri'        => $requested_uri,
			'ip'         => $ip_address,
			'user_agent' => $user_agent,
		);

		array_unshift( $logs, $new_entry );
		$logs = array_slice( $logs, 0, 100 );

		if ( is_multisite() ) {
			update_site_option( CUSTOM_ADMIN_URL_LOGS_OPTION, $logs );
		} else {
			update_option( CUSTOM_ADMIN_URL_LOGS_OPTION, $logs );
		}
	}

	/**
	 * Retorna una respuesta 404 No Encontrado sin revelar la presencia de WordPress.
	 */
	private function block_access_with_404() {
		global $wp_query;

		status_header( 404 );
		nocache_headers();

		if ( isset( $wp_query ) && is_object( $wp_query ) ) {
			$wp_query->set_404();
		}

		$template_404 = get_404_template();
		if ( $template_404 && file_exists( $template_404 ) ) {
			include $template_404;
		} else {
			echo '<h1>404 Not Found</h1>';
		}
		exit;
	}

	/**
	 * Reemplaza referencias a wp-login.php con el slug secreto en site_url() y network_site_url().
	 *
	 * @param string      $url    URL generada.
	 * @param string      $path   Ruta enviada a la función.
	 * @param string|null $scheme Esquema utilizado.
	 * @param int|null    $blog_id ID del sitio en Multisite.
	 * @return string
	 */
	public function filter_site_url( $url, $path, $scheme, $blog_id = null ) {
		if ( strpos( $url, 'wp-login.php' ) !== false ) {
			$query    = wp_parse_url( $url, PHP_URL_QUERY );
			$fragment = wp_parse_url( $url, PHP_URL_FRAGMENT );

			if ( is_multisite() && 'network_site_url' === current_filter() ) {
				$base = network_home_url( '/' . $this->secret_slug, $scheme );
			} else {
				$base = home_url( '/' . $this->secret_slug, $scheme );
			}

			if ( $query ) {
				$base .= '?' . $query;
			}
			if ( $fragment ) {
				$base .= '#' . $fragment;
			}

			return $base;
		}

		return $url;
	}

	/**
	 * Filtra las redirecciones de la función wp_redirect().
	 *
	 * @param string $location URL de destino.
	 * @param int    $status   Código de estado HTTP.
	 * @return string
	 */
	public function filter_wp_redirect( $location, $status ) {
		if ( strpos( $location, 'wp-login.php' ) !== false ) {
			return str_replace( 'wp-login.php', $this->secret_slug, $location );
		}

		return $location;
	}
}
