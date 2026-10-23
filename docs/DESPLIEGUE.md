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

- **Multi-tenant**: el aislamiento lo aplica un `TenantScope` global sobre los 18
  modelos de negocio. Si se añade un modelo nuevo con `inquilino_id`, **debe**
  usar el trait `App\Models\Concerns\BelongsToTenant`; hay un test que lo exige.
- **Portal de clientes**: si se activa un portal público, recordar que las rutas
  protegidas llevan el middleware `tenant` **después** de `auth`.
- **Datos de demostración**: `--seed` crea dos ganaderías con usuarios
  `admin@ganaderia.test` y `admin@esperanza.test` (contraseña `password`). **No
  ejecutar el seeder en producción**, o cambiar esas credenciales de inmediato.
- **Pendientes conocidos**: ninguno de los documentados en
  `docs/auditoria_fase3.md`. El registro público funciona (crea la ganadería y
  su administrador) y la portada `/` redirige al panel o al login.

---

## Anexo: proxy inverso y HTTPS

Si la aplicación queda detrás de un proxy inverso (nginx, balanceador,
Cloudflare), definir en el `.env` del servidor:

```dotenv
TRUSTED_PROXIES=10.0.0.1,10.0.0.2     # o "*" si el proxy es de confianza plena
```

Sin esto, Laravel no ve las cabeceras `X-Forwarded-Proto` y puede generar URLs
`http://` y cookies no seguras aunque el usuario esté en `https://`. Está
desactivado por defecto a propósito: si la aplicación está expuesta
directamente, confiar en cualquier proxy permitiría falsear la IP y el esquema.
---

## Anexo: altas, roles y registro publico

### Quien puede hacer que

| Rol | Puede |
|---|---|
| **administrador** | Todo, incluido **borrar** registros y dar de alta al equipo |
| **operario** | El dia a dia: registrar, editar, importar, consultar. **No borra** |

El limite se aplica en un middleware por el nombre de la ruta
(`*.destroy` + verbo DELETE), asi que cualquier modulo que se anada en el futuro
queda protegido sin tocar su controlador. Ante un rol vacio o desconocido se
**deniega**.

### Alta de una ganaderia (via por defecto, registro cerrado)

```bash
php artisan ganaderia:ganaderia "Ganaderia La Esperanza" jefe@ejemplo.com
```

Crea la ganaderia y su administrador en una transaccion y muestra la contrasena
generada una sola vez. Opciones: `--persona="Nombre Apellido"`,
`--password=...`, `--sin-verificar`.

En Windows, para no pelearse con la version de PHP (el proyecto pide 8.4 y el
`php` del PATH suele ser el 8.2 de XAMPP), hay un envoltorio que ademas pide los
datos y comprueba que MariaDB este arriba:

```bat
scripts\crear-finca.bat
scripts\crear-finca.bat /q "Finca El Roble" dueno@correo.com   :: sin preguntas
scripts\crear-finca.bat /demo                                  :: con datos de ejemplo
```

### Finca nueva con datos de ejemplo (`--demo`)

Una finca vacia no se puede enseñar: el dueño abre el panel, ve ceros y no
entiende que compro. Con `--demo` (o `/demo` en el script) nace con potreros,
6 animales, produccion de leche y carne, sanidad, inventario y finanzas.

```bash
php artisan ganaderia:ganaderia "Finca El Roble" dueno@correo.com --demo
```

La finca queda marcada (`inquilinos.es_demo`) y el panel enseña un aviso con el
boton **Borrar datos de ejemplo**: al pulsarlo se borran los datos de negocio de
esa ganaderia (animales, potreros, produccion, finanzas) y se apaga el aviso. Las
cuentas de usuario no se tocan. Una finca que no es demo no muestra el aviso y no
se puede vaciar (responde 404), y solo el administrador puede hacerlo.

El correo del administrador queda verificado: la cuenta la aprovisiona el
operador de la plataforma y entrega la contrasena en mano. Si se prefiere que la
confirme el propio dueno, usar `--sin-verificar` en el comando de artisan.

### Alta de un trabajador dentro de una ganaderia

```bash
php artisan ganaderia:usuario trabajador@ejemplo.com "Nombre Apellido" --inquilino=3
php artisan ganaderia:rol trabajador@ejemplo.com administrador
php artisan ganaderia:usuarios          # listado con ganaderia y rol
```

### Registro publico (auto-servicio)

**Viene cerrado** (`REGISTRO_PUBLICO=false`): `/register` responde 404 y el
login no ofrece crear cuenta. Motivo: sin correo configurado, quien se registra
no puede verificar su cuenta y se queda fuera, y sin limite de altas la puerta
queda abierta al spam.

Para abrirlo, en el `.env` del servidor:

```dotenv
REGISTRO_PUBLICO=true
```

y **asegurate antes de que el correo funciona**. Con el registro abierto, cada
alta crea su propia ganaderia y queda como su administrador, y las peticiones van
limitadas a 10 por minuto.

### Verificacion de correo

Es **obligatoria para entrar al panel** (`User` implementa `MustVerifyEmail` y las
rutas del panel llevan el middleware `verified`). Quien se registra cae en
`/email/verify`, y desde ahi puede reenviarse el enlace.

Tres cosas que tienen que estar bien en el servidor o nadie podra entrar:

```dotenv
# 1. El correo tiene que salir de verdad (aqui un ejemplo con SMTP)
MAIL_MAILER=smtp
MAIL_HOST=smtp.tu-proveedor.com
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS="no-responder@tudominio.com"

# 2. APP_URL tiene que ser la direccion REAL: el enlace de verificacion se
#    firma con ella. Si dice localhost, el enlace no abre desde el correo.
APP_URL=https://tudominio.com
```

3. El proveedor de correo debe aceptar el remitente y no marcarlo como spam.
   Si el correo cae en spam, el usuario se queda fuera y no lo sabra.

Para **probar en local sin servidor de correo**, deja `MAIL_MAILER=log`: el
mensaje completo (con el enlace) se escribe en `storage/logs/laravel.log` y se
puede abrir pegandolo en el navegador.
---

## Anexo: ensayo de despliegue (comprobado en local)

Los cuatro comandos que deja preparados el despliegue se han ejecutado y
funcionan:

```bash
php artisan config:cache     # OK
php artisan route:cache      # OK (Laravel 12 si serializa rutas con closure)
php artisan view:cache       # OK
php artisan event:cache      # OK
```

Con las cuatro cachés activadas —el estado real de producción— la aplicación
responde: `/login` 200, `/up` 200 y las rutas protegidas redirigen al login.

### Aviso importante: NO ejecutar las pruebas en el servidor

Con `config:cache` activo, Laravel deja de leer el `.env`: la configuración
queda congelada en `bootstrap/cache/config.php`. Si en ese estado se lanzara
`php artisan test`, la suite iría contra la base de datos de PRODUCCIÓN y
`RefreshDatabase` la vaciaría.

Hay una guarda que lo impide: las pruebas abortan con

```
BLOQUEADO: los tests solo pueden ejecutarse contra la base de pruebas
'ganaderia_saas_test'. Base detectada: 'ganaderia_saas_v2'
```

No es una molestia, es el seguro. Si alguna vez hay que ejecutar la suite en el
servidor, se hace `php artisan optimize:clear` antes y se vuelve a `optimize`
después.
---

## Anexo: importacion de datos

El formato que deben tener los archivos (columnas por modulo, fechas, decimales,
limites de tamano y filas) esta en `docs/FORMATO_IMPORTACION.md`, con plantillas
listas para usar en `docs/plantillas/`.

Aviso de despliegue: el limite real de tamano lo pone PHP
(`upload_max_filesize`, 2 MB de fabrica), no la validacion de la aplicacion. Si
se van a importar archivos mayores, hay que subir `upload_max_filesize` y
`post_max_size` en el `php.ini` del servidor.

### Cobro por comprobante

El cobro es manual y asistido, como en las apps de servicios publicos: el
cliente paga (Pago Movil, transferencia, Zelle o USDT) y sube el comprobante;
el operador lo revisa y da luz verde.

```bash
php artisan ganaderia:operador "pagos@tudominio.com" "Nombre Apellido"   # crea la cuenta que cobra
```

- El operador **no pertenece a ninguna ganaderia** y solo entra a
  `/plataforma/pagos`: ve el comprobante de cada pago y aprueba o rechaza con un
  motivo (que el cliente lee en su pantalla).
- Aprobar **extiende la suscripcion** desde el final del periodo actual (o desde
  hoy si ya vencio).
- Cada finca nace con **14 dias de prueba**; si vence, queda en **modo lectura**
  (consulta y exporta, no registra) hasta que se apruebe un pago.
- Los comprobantes se guardan en `storage/app/private/comprobantes/` y se sirven
  por una ruta autenticada: no hay URL publica.
- La **referencia del banco es unica**: el mismo comprobante no se puede subir
  dos veces.
- Planes y precios estan en la tabla `planes` (se siembran Basico 12 USD y
  Profesional 25 USD). Cambiarlos es un `UPDATE` hasta que exista pantalla de
  administracion.

### Limites del plan, planes y avisos (fase B)

- Cada plan tiene **precio, duracion y topes** de animales y usuarios. Al llegar
  al tope, la app avisa y no deja crear mas (los datos existentes no se tocan).
- El operador edita los planes en `/plataforma/planes`. Los cambios valen para las
  **proximas renovaciones**: un periodo ya pagado conserva su precio.
- La pantalla de suscripcion muestra el consumo: «9 de 300 animales · 2 de 6
  usuarios».
- Los avisos de vencimiento (7, 3, 1 y 0 dias) los crea el comando
  `php artisan suscripciones:avisar`, programado a las 08:00. **Necesita el cron
  de Laravel** en el servidor, o no se ejecuta nunca:

```
* * * * * cd /ruta/al/proyecto && php artisan schedule:run >> /dev/null 2>&1
```