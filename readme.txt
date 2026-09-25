=== Custom Admin URL Protector ===
Contributors: senior_wp_security
Tags: security, login, admin, hide login, brute force
Requires at least: 5.8
Tested up to: 6.4
Stable tag: 1.2.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Oculta wp-login.php y /wp-admin/ redirigiendo a una ruta personalizada para proteger tu sitio contra ataques de fuerza bruta.

== Description ==

Custom Admin URL Protector es un plugin liviano, altamente seguro y eficiente para WordPress que permite ocultar los puntos de entrada predeterminados al panel de administración (`wp-login.php` y `/wp-admin/`).

Al sustituir las rutas por defecto con un slug secreto y personalizado definido por el administrador (por ejemplo, `tuweb.com/mi-panel-secreto`), el plugin bloquea los intentos de acceso no autorizado y devuelve una respuesta HTTP 404 No Encontrado, impidiendo que escáneres automatizados y bots identifiquen la arquitectura de WordPress.

=== Características Principales ===

* **Slug de Acceso Personalizado:** Define una ruta de entrada única y secreta.
* **Respuesta 404 Estándar:** Los intentos de acceso a `wp-login.php` o `/wp-admin/` reciben una respuesta 404 idéntica a la del tema activo.
* **Compatibilidad con Multisite:** Funciona perfectamente en instalaciones individuales y redes WordPress Multisite (subdominios o subdirectorios).
* **Registro de Eventos (Security Logging):** Registra cada intento de acceso bloqueado en los logs del servidor con la IP y User-Agent del solicitante.
* **Notificaciones por Correo:** Envía una alerta automática al correo del administrador cada vez que se modifica la URL secreta.
* **Mecanismo de Recuperación de Emergencia:** Incluye una constante de desactivación (`CUSTOM_ADMIN_URL_DISABLE`) para pausar la protección desde `wp-config.php` si se olvida la ruta.
* **Limpieza Total:** Cumple con `uninstall.php` eliminando todas las opciones de la base de datos al ser eliminado.

== Installation ==

1. Descarga el archivo comprimido `.zip` del plugin.
2. Ve al panel de administración de WordPress > **Plugins > Añadir nuevo > Subir plugin**.
3. Selecciona el archivo `.zip` e instálalo.
4. Activa el plugin.
5. Ve a **Ajustes > URL de Administración** (o Ajustes de la Red en Multisite) para definir tu slug secreto.
6. Guarda los cambios y añade tu nueva URL de acceso a los marcadores de tu navegador.

== Frequently Asked Questions ==

= ¿Qué pasa si olvido la URL secreta de acceso? =
Puedes restaurar el acceso estándar agregando la siguiente línea a tu archivo `wp-config.php` antes de `/* That's all, stop editing! */`:

`define( 'CUSTOM_ADMIN_URL_DISABLE', true );`

= ¿Afecta el rendimiento de mi sitio web? =
No. El plugin es extremadamente liviano y únicamente ejecuta sus verificaciones durante la inicialización de peticiones administrativas (`init`), con un impacto cero en el frontend para visitantes.

= ¿Es compatible con WordPress Multisite? =
Sí, es completamente compatible con instalaciones Multisite. Puedes activar el plugin a nivel de red y gestionar la ruta secreta desde el panel del Administrador de la Red (*Network Admin*).

= ¿Qué sucede cuando desinstalo el plugin? =
El archivo `uninstall.php` elimina todas las opciones guardadas en la base de datos (`wp_options` y `wp_sitemeta`) y restaura las reglas de reescritura de WordPress sin dejar ningún rastro.

== Changelog ==

= 1.2.0 =
* Compatibilidad completa con WordPress Multisite (Network Admin).
* Soporte mejorado para instalaciones en subdirectorios.
* Excepciones avanzadas para endpoints internos (`admin-ajax.php`, `admin-post.php`).

= 1.1.0 =
* Añadido registro de eventos de seguridad (`error_log`) con IP y User-Agent.
* Añadida notificación automática por correo electrónico tras cambios de slug.

= 1.0.0 =
* Versión inicial del plugin.
