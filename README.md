# 🛡️ Custom Admin URL Protector for WordPress

[![WordPress Version](https://img.shields.io/badge/WordPress-7.x%20Ready-blue.svg?logo=wordpress&logoColor=white)](https://wordpress.org)
[![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-777BB4.svg?logo=php&logoColor=white)](https://www.php.net)
[![License](https://img.shields.io/badge/License-GPLv2-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Multisite Compatible](https://img.shields.io/badge/Multisite-Supported-orange.svg)](#-compatibilidad-multisite)
[![Latest Release](https://img.shields.io/badge/Version-1.5.0-brightgreen.svg)](https://github.com)

**Custom Admin URL Protector** es un plugin liviano, altamente seguro y eficiente diseñado para ocultar los puntos de entrada predeterminados de WordPress (`wp-login.php` y `/wp-admin/`).

Sustituye las rutas por defecto con un **slug secreto y personalizado** definido de forma interactiva por el administrador (ej. `tuweb.com/mi-panel-secreto`), bloqueando de forma proactiva ataques de fuerza bruta y escaneos automatizados.

---

## 🌟 Características Destacadas

- 🎛️ **Módulo Admin Interactivo con Pestañas:** Cambia el slug de acceso en tiempo real, prueba URLs y copia la dirección directamente al portapapeles.
- 📋 **Historial de Logs Integrado:** Pestaña dedicada dentro del panel de WordPress para revisar los intentos de acceso no autorizados (IP, hora, User-Agent y ruta) con opción para vaciar los registros.
- ✉️ **Notificaciones por Correo Personalizables:** Edita el asunto y el cuerpo del mensaje utilizando variables dinámicas (`{site_name}`, `{new_url}`, `{date_time}`, `{ip_address}`).
- 🛑 **Acción de Bloqueo Configurable:** Elige responder con una página **404 Not Found** o realizar una redirección 302 a la portada del sitio.
- 🌐 **Soporte Nativo Multisite:** Administra la ruta secreta a nivel de sitio individual o de forma centralizada en el *Network Admin*.
- 🚨 **Recuperación de Emergencia:** Pausa la protección de forma segura añadiendo `define( 'CUSTOM_ADMIN_URL_DISABLE', true );` en `wp-config.php`.
- 🧹 **Desinstalación 100% Limpia:** Cumple con el estándar `uninstall.php`, eliminando todas las opciones guardadas en la base de datos sin dejar rastro.

---

## 📋 Requisitos del Sistema

| Requisito | Versión Mínima / Recomendada |
| :--- | :--- |
| **WordPress** | 6.0 o superior (probado en **WordPress 7.x**) |
| **PHP** | 8.0 o superior |
| **Arquitectura** | Sitio independiente o Red Multisite |
| **Licencia** | MIT |

---

## 🖥️ Módulo de Administración (Pestañas)

### 1. ⚙️ Ajustes Generales
- Cambio interactivo del slug de acceso.
- Selector de acción ante bloqueos (404 vs Redirección Home).
- Campo con botón **📋 Copiar URL** y badge de estado en vivo.

### 2. ✉️ Notificaciones por Correo
- Personalización completa del correo electrónico enviado tras actualizar la URL secreta.
- Soporte para etiquetas automáticas: `{site_name}`, `{new_url}`, `{date_time}`, `{ip_address}`.

### 3. 📋 Historial de Logs
- Tabla detallada con los últimos 100 intentos de acceso bloqueados.
- Botón para vaciar el historial con confirmación de seguridad.

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

## 📜 Historial de Cambios (Changelog)

- **v1.5.0**
  - Interfaz de administración enriquecida con pestañas navegables.
  - Pestaña de historial de registros de eventos de seguridad (logs) directamente en el panel con función de vaciado.
  - Plantilla de correo electrónico personalizable con etiquetas dinámicas.
  - Selector interactivo de respuesta ante accesos no autorizados (404 Not Found vs. Redirección a Portada).
  - Botón de copiado rápido al portapapeles y visualizador de estado en vivo.
- **v1.3.0**
  - Compatibilidad oficial declarada para WordPress 7.x y PHP 8.x.
- **v1.2.0**
  - Compatibilidad completa con WordPress Multisite (*Network Admin*).
