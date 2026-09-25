# Guía de Contribución (CONTRIBUTING.md)

¡Gracias por tu interés en contribuir a **Custom Admin URL Protector**! Nos apasiona mantener este plugin liviano, seguro y alineado con los más altos estándares de calidad.

 Sigue estas directrices para colaborar de manera eficiente.

---

## 🛠️ Estándares de Código

Todo el código PHP debe cumplir estrictamente con los **WordPress Coding Standards (WPCS)**:

1. **Estructura y Nombrado:**
   - Usa minúsculas con guiones para nombres de archivos (`class-plugin-core.php`).
   - Nombres de clases en *CamelCase* con guiones bajos (`Custom_Admin_URL_Core`).
   - Nombres de funciones y métodos en `snake_case`.

2. **Seguridad y Escapado:**
   - **Nunca confíes en datos de entrada:** Sanitiza siempre entradas (`sanitize_text_field`, `sanitize_title`).
   - **Escapa las salidas:** Usa `esc_html`, `esc_attr`, `esc_url` antes de imprimir HTML o atributos.
   - **Comprobación de Privilegios y Nonces:** Valida permisos (`current_user_can`) y tokens de seguridad (`check_admin_referer`).

3. **Verificación de Estilo (PHPCS):**
   Puedes verificar el cumplimiento de WPCS ejecutando:
   ```bash
   vendor/bin/phpcs --standard=WordPress includes/ custom-admin-url.php
   ```

---

## 🧪 Pruebas y Validación

Antes de enviar un Pull Request, asegúrate de que todas las pruebas pasen sin errores:

1. **Ejecutar Pruebas PHPUnit:**
   ```bash
   vendor/bin/phpunit
   ```

2. **Ejecutar Simulación HTTP:**
   ```bash
   chmod +x tests/test-http-endpoints.sh
   ./tests/test-http-endpoints.sh http://localhost mi-acceso-secreto
   ```

---

## 🔀 Flujo de Trabajo para Pull Requests (PR)

1. **Haz un Fork** del repositorio.
2. **Crea una Rama de Trabajo:**
   - Para nuevas características: `git checkout -b feature/nombre-caracteristica`
   - Para corrección de errores: `git checkout -b bugfix/nombre-error`
3. **Realiza Commits Claros:** Sigue la convención de mensajes (*Conventional Commits*), ej.: `feat: add multisite support` o `fix: sanitize custom slug`.
4. **Envía tu PR:** Asegúrate de describir el problema que resuelve y los pasos para probar los cambios.

---

## 🐛 Reporte de Errores

Si encuentras un error o vulnerabilidad de seguridad:
- Para errores generales, abre un **Issue** utilizando nuestra plantilla oficial.
- Para divulgación responsable de vulnerabilidades de seguridad, envía un correo a la dirección de soporte antes de hacer público el hallazgo.
