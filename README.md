# 🛡️ Custom Admin URL Protector for WordPress

[![WordPress Version](https://img.shields.io/badge/WordPress-5.8%2B-blue.svg?logo=wordpress&logoColor=white)](https://wordpress.org)
[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-777BB4.svg?logo=php&logoColor=white)](https://www.php.net)
[![License](https://img.shields.io/badge/License-GPLv2-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Multisite Compatible](https://img.shields.io/badge/Multisite-Supported-orange.svg)](#-compatibilidad-multisite)
[![Latest Release](https://img.shields.io/badge/Version-1.2.0-brightgreen.svg)](https://github.com)

**Custom Admin URL Protector** es un plugin liviano, altamente seguro y eficiente diseñado para ocultar los puntos de entrada predeterminados de WordPress (`wp-login.php` y `/wp-admin/`). 

Sustituye las rutas por defecto con un **slug secreto y personalizado** definido por el administrador (ej. `tuweb.com/mi-panel-secreto`), bloqueando de forma proactiva ataques de fuerza bruta y escaneos automatizados al devolver un código de respuesta **HTTP 404 Not Found**.

---

## 🌟 Características Destacadas

- 🔑 **Slug de Acceso Personalizado:** Configura una ruta única y secreta para iniciar sesión.
- 🚫 **Respuesta 404 Estándar:** Oculta la presencia de WordPress ante escáneres devolviendo la plantilla 404 nativa del tema.
- 🌐 **Soporte Nativo Multisite:** Administra la ruta secreta a nivel individual o centralizada en el *Network Admin*.
- 📝 **Registro de Auditoría de Seguridad:** Registra cada intento de acceso denegado en los logs del servidor con la IP y el `User-Agent`.
- 📧 **Alertas por Correo Electrónico:** Envía una notificación inmediata al administrador cada vez que se actualiza el slug de acceso.
- 🚨 **Recuperación de Emergencia:** Pausa la protección de forma segura añadiendo la constante `CUSTOM_ADMIN_URL_DISABLE` en `wp-config.php`.
- 🧹 **Desinstalación 100% Limpia:** Cumple con el estándar `uninstall.php`, eliminando todas las opciones guardadas en la base de datos sin dejar rastro.

---

## 📋 Requisitos del Sistema

| Requisito | Versión Mínima / Recomendada |
| :--- | :--- |
| **WordPress** | 5.8 o superior (probado hasta 6.4) |
| **PHP** | 7.4 o superior (compatible con PHP 8.x) |
| **Arquitectura** | Sitio independiente o Red Multisite |
| **Licencia** | GPLv2 o posterior |

---

## 🚀 Instalación y Configuración

### 1. Instalación
1. Descarga el archivo comprimido [`custom-admin-url.zip`](./custom-admin-url.zip).
2. Dirígete a tu panel de WordPress: **Plugins > Añadir nuevo > Subir plugin**.
3. Selecciona el archivo `.zip` y presiona **Instalar ahora**.
4. Haz clic en **Activar plugin**.

### 2. Configuración del Slug
1. Ve a **Ajustes > URL de Administración** (o *Ajustes de la Red* en Multisite).
2. Introduce tu slug personalizado (ejemplo: `acceso-privado-2026`).
3. Guarda los cambios.
4. **¡Importante!** Añade inmediatamente la nueva URL a los marcadores de tu navegador.

---

## ⚡ Mecanismo de Emergencia (Anti-Bloqueo)

> [!IMPORTANT]
> Si olvidas la URL secreta de acceso o necesitas deshabilitar la protección sin acceder al panel, agrega la siguiente constante a tu archivo `wp-config.php` antes de la línea `/* That's all, stop editing! */`:
>
> ```php
> define( 'CUSTOM_ADMIN_URL_DISABLE', true );
> ```
>
> Esto pausará la interceptación de inmediato y restaurará el acceso tradicional a `wp-login.php` de forma temporal.

---

## 🔒 Reglas Opcionales a Nivel de Servidor Web

Para rechazar solicitudes no autorizadas en la capa de red antes de ejecutar PHP, puedes agregar las siguientes reglas según tu servidor web:

<details>
<summary><b>Ver reglas para Apache (.htaccess)</b></summary>

```apache
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteBase /

# Mapear el slug secreto a wp-login.php
RewriteRule ^mi-acceso-secreto/?$ /wp-login.php [QSA,L]

# Bloquear acceso directo a wp-login.php
RewriteCond %{THE_REQUEST} ^[A-Z]{3,9}\ /wp-login\.php [NC]
RewriteCond %{QUERY_STRING} !^action=logout [NC]
RewriteRule ^wp-login\.php$ - [R=404,L]
</IfModule>
```
</details>

<details>
<summary><b>Ver reglas para Nginx (nginx.conf)</b></summary>

```nginx
# Mapear slug secreto
location = /mi-acceso-secreto {
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME $document_root/wp-login.php;
    fastcgi_pass 127.0.0.1:9000;
}

# Bloquear acceso directo a wp-login.php
location = /wp-login.php {
    if ($request_uri ~* "^/wp-login\.php") {
        return 404;
    }
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    fastcgi_pass 127.0.0.1:9000;
}
```
</details>

---

## ❓ Preguntas Frecuentes (FAQ)

<details>
<summary><b>¿Afecta el rendimiento del sitio web?</b></summary>
No. El plugin es ultraliviano y solo ejecuta sus verificaciones durante la inicialización de peticiones administrativas (hook <code>init</code>), sin afectar el rendimiento del frontend para los visitantes.
</details>

<details>
<summary><b>¿Es compatible con WordPress Multisite?</b></summary>
Sí. Puedes activarlo a nivel de red y administrar la URL secreta centralizadamente desde el menú <i>Network Admin</i>.
</details>

<details>
<summary><b>¿Qué sucede al desinstalar el plugin?</b></summary>
El script <code>uninstall.php</code> borra automáticamente las opciones registradas en <code>wp_options</code> / <code>wp_sitemeta</code> y limpia las reglas de reescritura de WordPress.
</details>

---

## 📜 Historial de Cambios (Changelog)

- **v1.2.0**
  - Compatibilidad completa con WordPress Multisite (*Network Admin*).
  - Soporte mejorado para instalaciones en subdirectorios.
  - Excepciones avanzadas para endpoints internos (`admin-ajax.php`, `admin-post.php`).
- **v1.1.0**
  - Registro de auditoría de seguridad (`error_log`) con IP y `User-Agent`.
  - Notificaciones automáticas por correo electrónico tras modificar el slug.
- **v1.0.0**
  - Lanzamiento inicial del plugin.

---

<p align="center">
  Desarrollado siguiendo los <b>WordPress Coding Standards (WPCS)</b> y buenas prácticas de seguridad.
</p>
