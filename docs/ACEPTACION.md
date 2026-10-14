# Informe de aceptación (Fase 7)

Aplicación: **Ganadería SaaS** (Laravel 12) · Fecha: 2026-10-14
Carpeta principal: `C:\xampp\htdocs\ganaderia_SaaS`
Commit evaluado: ver `git log -1` (9 commits por delante de `origin/main`)

Este informe se ha redactado ejecutando las comprobaciones, no revisando el
código a ojo. Cada criterio lleva el comando y el resultado obtenido. **Lo que
no está verificado se marca como pendiente**, no como hecho.

---

## Cuadro resumen

| # | Criterio de aceptación | Estado |
|---|---|---|
| 1 | Instalación limpia (`composer install` + build) levanta la aplicación | ✅ Verificado |
| 2 | `migrate:fresh --seed` reproduce el esquema desde cero | ✅ Verificado |
| 3 | Suite de pruebas en verde | ✅ **86 pruebas, 0 fallos** |
| 4 | Análisis estático sin errores | ⛔ **Pendiente: falta aprobar la dependencia** |
| 5 | Cero hallazgos P0/P1 abiertos | ⚠️ Queda 1 P0 (registro público) |
| 6 | Aislamiento multi-tenant verificado | ✅ Verificado (A vs B) |
| 7 | Rutas responden sin errores con el build de producción | ✅ Verificado |
| 8 | Sin secretos en el repositorio | ✅ Verificado |
| 9 | Runbook de despliegue ejecutado de principio a fin | ⛔ **Pendiente: no hay servidor de destino** |
| 10 | Informe de evaluación con evidencia | ✅ Este documento |

---

## 1. Instalación limpia

```
composer install --no-interaction        → OK (123 paquetes)
npm ci && npm run build                  → OK (111 módulos, public/build generado)
php artisan --version                    → Laravel Framework 12.44.0
```

Verificado sobre un clon real del repositorio en `C:\xampp\htdocs\ganaderia_SaaS`,
con PHP 8.4.24. Requisitos que costaron sangre y quedan documentados en el
runbook: **PHP ≥ 8.3** (lo exige `maennchen/zipstream-php`) y las extensiones
**`gd` y `zip`**, que en el entorno original estaban desactivadas.

## 2. Esquema reproducible

```
php artisan migrate:fresh --seed --force → código de salida 0
```

| Métrica | Antes | Ahora |
|---|---|---|
| Tablas | 28 | 28 |
| Claves foráneas | 16 | **33** |
| Columnas `inquilino_id` sin FK | 11 | **0** |
| Columnas `inquilino_id` sin índice | 1 | **0** |
| Identificadores no ASCII | 1 (`tamaño`) | **0** |
| Columnas eliminadas | — | 0 |

Lectura posterior con Eloquent contra el esquema nuevo: **19 modelos, 0 fallos**,
y 0 registros de otra ganadería visibles.

## 3. Suite de pruebas

```
php artisan test → Tests: 188 passed (607 assertions) · Duration: 37.15s
```

| Bloque | Pruebas | Qué cubre |
|---|---|---|
| Aislamiento multi-tenant | 8 | 19/19 modelos con filtro, conteos disjuntos por ganadería, registro ajeno → 404, sin salto de tenant, falla cerrado |
| Edición por módulo | 21 | 9 módulos: el dueño edita, el ajeno recibe 404, no hay IDOR |
| Formularios de edición | 46 | 14 módulos × 3: la página carga, las fechas salen en ISO y el formulario se reenvía tal cual |
| Persistencia al editar | 8 | Lo que se escribe en el formulario se guarda de verdad (no basta con que se acepte) |
| Formularios de creación | 28 | 14 módulos × 2: se rellenan con datos válidos del esquema, se envían y se comprueba que el registro se crea y se guarda |
| Fábricas de modelo | 38 | Cada fábrica crea un registro válido y de la ganadería activa |
| Formato de fechas | 21 | Las fechas visibles se muestran sin hora en 19 páginas y la ficha QR |
| Consultas por página | 3 | La ficha del potrero no hace N+1 ni consulta desde la vista |
| FormRequests | 1 | Ningún FormRequest con ules() vacío se usa con alidated() |
| Invariancia de valores | 4 | Reenviar un formulario no cambia el valor guardado |
| Importación Excel/CSV | 5 | Formato, tamaño, tope de filas y alta real |
| Páginas de error | 2 | 404 propia y las 5 vistas compilan sin Vite |
| Portada | 3 | `/` redirige a login o al panel |

## 4. Análisis estático

**No realizado.** Requiere `larastan/larastan`, una dependencia de desarrollo que
no se ha instalado porque el plan acordado prohíbe tocar dependencias sin
justificarlo y sin aprobación. **No se declara como hecho.** El CI tiene el hueco
reservado y comentado: sin la dependencia, ese paso sería un falso verde.

Pint sí está aplicado: **148 archivos, 0 problemas de estilo**.

## 5. Hallazgos abiertos

**P0 abierto:**

- **El registro público de usuarios está roto.** `RegisterController::create()`
  usa `$data['inquilino']`, una clave que nunca se valida (la regla está
  comentada) y que además no existe; y nada crea el `Inquilino`, obligatorio
  desde la Fase 1. Editar esto requiere una decisión de producto: ¿el registro
  crea la ganadería (auto-servicio) o se desactiva y los usuarios los crea un
  administrador? **No afecta al panel interno.**

**P1/P2 abiertos, documentados en `docs/auditoria_fase3.md`:**

- Policies de autorización por roles (falta decidir el modelo de roles).
- Código muerto: modelo `Usuario` + 3 controladores stub (borrar requiere
  autorización explícita).
- Las filas de una importación se guardan en la sesión entre el mapeo y la
  confirmación; con archivos grandes penaliza cada petición.

## 6. Aislamiento multi-tenant

Verificado en tres capas:

1. **Esquema**: `inquilino_id` NOT NULL + FK en las 19 tablas de negocio.
2. **Framework**: `TenantScope` global sobre los 19 modelos, resolución perezosa
   del tenant desde la sesión, y **falla cerrado** si no hay tenant.
3. **Pruebas**: conteos disjuntos por ganadería, registro ajeno inaccesible por
   URL (404) y escritura ajena bloqueada.

Punto clave encontrado y corregido: el *route-model binding* se ejecuta **antes**
que los middleware de ruta, así que sin resolución perezosa una ficha de otra
ganadería se encontraba y devolvía 403 en lugar de 404.

## 7. Rutas y build de producción

```
Smoke test de los 20 índices de módulo con el esquema nuevo → 0 errores
http://ganaderia_SaaS.test/login → 200    /up → 200    / → redirección
```

El único fallo que quedaba (la portada pedía un asset Tailwind que no se compila)
está corregido: `/` redirige al panel o al login.

## 8. Secretos

`.env` y `.env.testing` están en `.gitignore` (verificado). `.env.example`
documenta las variables reales. No hay claves ni contraseñas versionadas.

## 9. Despliegue

El runbook `docs/DESPLIEGUE.md` está escrito y contrastado con el estado real del
repositorio, pero **no se ha ejecutado en un servidor de destino**, así que no se
declara verificado. Incluye requisitos, base de datos, variables, pasos, colas,
copias de seguridad, reversión, verificación posterior y checklist de seguridad.

---

## Conclusión

La aplicación **no está lista para producción todavía**, y por dos motivos
concretos, no por impresión general:

1. **El registro público está roto** y necesita una decisión de producto.
2. **Falta el análisis estático** y la **ejecución del runbook en un servidor
   real**, que dependen de una aprobación y de un destino de despliegue.

Todo lo demás —esquema versionado, aislamiento multi-tenant, suite de pruebas en
verde, estilo, endurecimiento de la importación, páginas de error y CI— está
hecho **y verificado con evidencia**.

Las cinco cosas que desbloquean el cierre están listadas en el informe de cada
ronda y resumidas aquí: decisión sobre el registro, sobre los roles, aprobación
de Larastan, autorización para borrar el código muerto y reapertura de DSH en la
carpeta del proyecto para poder usar el fan-out de agentes.
