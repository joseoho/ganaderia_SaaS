# Guía de despliegue en producción

Aplicación: **Ganadería SaaS** (Laravel 12). Multi-tenant real: varias ganaderías
en la misma instalación, con datos aislados a nivel de framework.

Esta guía se ha redactado a partir del estado verificado del repositorio, no de
suposiciones. Cada requisito marcado como obligatorio se ha comprobado.

---

## 1. Requisitos del servidor

| Componente | Requisito | Por qué |
|---|---|---|
| **PHP** | **8.3 o superior** (verificado en 8.4.24) | `maennchen/zipstream-php` (dependencia de PhpSpreadsheet) exige `php-64bit ^8.3`. Con PHP 8.2 **la instalación de Composer falla** |
| Extensiones PHP | `mbstring`, `pdo_mysql`, `openssl`, `curl`, `fileinfo`, `dom`, `xml`, `intl`, `bcmath` | Uso general del framework |
| **`gd`** | **Obligatoria** | Generación de códigos QR (`simplesoftwareio/simple-qrcode`) |
| **`zip`** | **Obligatoria** | Importación/exportación de Excel (`maatwebsite/excel`) |
| Composer | 2.x | |
| MySQL / MariaDB | **MariaDB 10.11 LTS** o MySQL 8.4 | Colación `utf8mb4_unicode_ci`. No se ha probado SQLite ni PostgreSQL |
| Node.js | 20+ (solo para compilar el frontend) | Vite 7 |

> `gd` y `zip` no son opcionales. En el entorno de desarrollo original estaban
> **desactivadas** en XAMPP, así que ni el QR ni la importación de Excel podían
> funcionar aunque la aplicación arrancara.

---

## 2. Base de datos

El esquema lo crean **las migraciones**. No se importa ningún volcado.

```sql
CREATE DATABASE ganaderia_saas
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

-- Usuario con permisos minimos sobre ESA base (no root)
CREATE USER 'ganaderia'@'localhost' IDENTIFIED BY '<clave-fuerte>';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, DROP, REFERENCES
  ON ganaderia_saas.* TO 'ganaderia'@'localhost';
FLUSH PRIVILEGES;
```

Después:

```bash
php artisan migrate --force      # crea las 28 tablas
php artisan db:seed --force      # OPCIONAL: datos de demostración
```

**Nunca ejecutar `migrate:fresh` en producción**: elimina todas las tablas.

---

## 3. Variables de entorno de producción

Partir de `.env.example` y ajustar. Valores **obligatorios**:

```dotenv
APP_NAME="Ganaderia SaaS"
APP_ENV=production
APP_DEBUG=false                 # imprescindible: no exponer trazas
APP_URL=https://midominio.com
APP_KEY=                        # php artisan key:generate

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ganaderia_saas
DB_USERNAME=ganaderia
DB_PASSWORD=<clave-fuerte>

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true      # solo enviar la cookie por HTTPS
SESSION_SAME_SITE=lax
SESSION_ENCRYPT=true

LOG_LEVEL=warning               # no registrar debug en produccion
QUEUE_CONNECTION=database
CACHE_STORE=database
```

> El archivo `.env` **no se versiona** (está en `.gitignore`). Debe crearse en el
> servidor y no compartirse. `APP_DEBUG=true` en producción expone trazas con
> datos de la base y de la sesión.

---

## 4. Pasos de despliegue

```bash
git pull origin main

composer install --no-dev --optimize-autoloader
npm ci && npm run build          # genera public/build (las vistas usan @vite)

php artisan migrate --force
php artisan optimize             # config + rutas + vistas en caché
php artisan storage:link
```

Permisos de escritura para el usuario del servidor web:

```
storage/            (recursivo)
bootstrap/cache/    (recursivo)
```

Si se cambia cualquier valor de `.env`: `php artisan optimize:clear` y volver a
`php artisan optimize`.

---

## 5. Procesos en segundo plano

| Proceso | Comando | Necesario para |
|---|---|---|
| Cola de trabajos | `php artisan queue:work --tries=3 --timeout=120` | Envíos y tareas diferidas (`QUEUE_CONNECTION=database`) |
| Planificador | `* * * * * cd /ruta && php artisan schedule:run >> /dev/null 2>&1` | Tareas programadas |

La cola debe estar supervisada (systemd o Supervisor) para que se reinicie si
cae. **Actualmente la aplicación no define tareas programadas**: el planificador
queda preparado para cuando se añadan.

---

## 6. Copias de seguridad

```bash
# Diaria, con retencion de 14 dias
mysqldump --single-transaction --routines --triggers \
  -u ganaderia -p ganaderia_saas | gzip > /backups/ganaderia_$(date +%F).sql.gz
```

Respaldar también: `.env`, `storage/app/` (archivos subidos) y `public/build/`
(se puede regenerar).

**Verificar la restauración** de vez en cuando en una base de prueba: una copia
que nunca se ha restaurado no es una copia.

---

## 7. Reversión (rollback)

```bash
git checkout <commit-o-etiqueta-anterior>
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate:rollback --force   # solo si el despliegue añadio migraciones
php artisan optimize
```

Antes de revertir migraciones, tener claro si se pierden datos de columnas
nuevas. Para cambios de esquema delicados, preferir restaurar la copia.

---

## 8. Verificación después de desplegar

```bash
curl -I https://midominio.com/up      # debe responder 200
```

Y en el navegador: `/login`, entrar con un usuario real y recorrer los listados.
Los 20 índices de módulo están verificados como correctos en el entorno de
desarrollo (`/animales`, `/potreros`, `/ventas`, `/reportes`, `/importar`…).

Pruebas funcionales concretas recomendadas:

1. **Aislamiento multi-tenant** (lo más importante): entrar con un usuario de la
   ganadería A y comprobar que no ve nada de la B. Además, intentar abrir por URL
   un registro de la otra ganadería: debe dar **404**.
2. **Edición**: modificar un potrero, una venta y un tratamiento. Deben guardar
   (hubo un fallo que devolvía 403 en 7 módulos; está corregido y cubierto por
   tests).
3. **QR**: abrir `/animales/etiquetas` y generar una etiqueta (requiere `gd`).
4. **Excel**: importar un CSV de animales de menos de 5000 filas (requiere `zip`).

---

## 9. Checklist de seguridad

- [ ] `APP_DEBUG=false` y `APP_ENV=production`
- [ ] HTTPS con certificado válido; `SESSION_SECURE_COOKIE=true`
- [ ] `APP_KEY` generada en el servidor y **no** compartida
- [ ] Usuario de base de datos con permisos mínimos (no root)
- [ ] `.env` fuera del repositorio y sin permisos de lectura para terceros
- [ ] Copias de seguridad automáticas y **restauración probada**
- [ ] Cola supervisada con reinicio automático
- [ ] `LOG_LEVEL=warning` o superior
- [ ] Rate limiting del login activo (Laravel UI lo aplica: 5 intentos/minuto)
- [ ] Acceso al servidor restringido por clave SSH, no por contraseña

---

## 10. Notas específicas de este proyecto

- **Multi-tenant**: el aislamiento lo aplica un `TenantScope` global sobre los 19
  modelos de negocio. Si se añade un modelo nuevo con `inquilino_id`, **debe**
  usar el trait `App\Models\Concerns\BelongsToTenant`; hay un test que lo exige.
- **Portal de clientes**: si se activa un portal público, recordar que las rutas
  protegidas llevan el middleware `tenant` **después** de `auth`.
- **Datos de demostración**: `--seed` crea dos ganaderías con usuarios
  `admin@ganaderia.test` y `admin@esperanza.test` (contraseña `password`). **No
  ejecutar el seeder en producción**, o cambiar esas credenciales de inmediato.
- **Pendientes conocidos** (ver `docs/auditoria_fase3.md`): el registro público de
  usuarios está roto y la ruta `/` falla con el build de producción. Ninguno de
  los dos afecta al panel interno, pero deben resolverse antes de abrir la
  aplicación al público.
