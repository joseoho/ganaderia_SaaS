# Auditoria de la Fase 3 - por modulo

Generado: 2026-10-14 01:20 | Base de datos: `ganaderia_saas_v2`

Auditoria automatizada por reglas, no por opinion: contraste de los modelos contra
el esquema real y patrones de riesgo en controladores, rutas y vistas.

| Severidad | Hallazgos |
|---|---|
| **P0** | 0 |
| **P1** | 7 |
| **P2** | 7 |

Modelos analizados: 21 | Controladores: 29

## P0 - fallos reales o riesgo de aislamiento

_Sin hallazgos._

## P1 - importantes (perdida silenciosa de datos, asignacion masiva, codigo muerto)

- Modelo RegistroVacuna: columnas NO asignables en `registro_vacunas` (se pierden en silencio): vacuna_id.
- Modelo Usuario: la tabla `usuarios` NO existe -> codigo muerto.
- AnimalController: $request->all() 1x -> usar $request->validated() (asignacion masiva).
- CompraController: $request->all() 1x -> usar $request->validated() (asignacion masiva).
- MovilizacionController: $request->all() 1x -> usar $request->validated() (asignacion masiva).
- NotificacionController: $request->all() 2x -> usar $request->validated() (asignacion masiva).
- PotreroController: $request->all() 1x -> usar $request->validated() (asignacion masiva).

## P2 - mejoras y deuda tecnica

- AnimalEtiquetaController: 6 metodos vacios -> stub / codigo muerto.
- DashboardController: 6 metodos vacios -> stub / codigo muerto.
- InquilinoController: 7 metodos vacios -> stub / codigo muerto.
- UsuarioController: 7 metodos vacios -> stub / codigo muerto.
- VacunaController: 7 metodos vacios -> stub / codigo muerto.
- Ruta 'home' autenticada sin middleware tenant (revisar si consulta datos de negocio).
- Vistas que consultan la BD (N+1 / logica en la vista): `C:\xampp\htdocs\ganaderia_SaaS/resources/views\potreros\show.blade.php` (2).

## Modelos frente al esquema

| Modelo | Tabla | Existe | inquilino_id | Trait tenant | Factory | casts | fillable sin columna | No asignables |
|---|---|---|---|---|---|---|---|---|
| Animal | `animales` | si | si | si | si | no | - | - |
| AnimalPotrero | `animal_potrero` | si | si | si | no | no | - | - |
| Compra | `compras` | si | si | si | no | no | - | - |
| Gastos | `gastos` (conv.) | si | si | si | no | no | - | - |
| Genealogia | `genealogia` | si | si | si | no | no | - | - |
| Inquilino | `inquilinos` (conv.) | si | - | n/a | si | no | - | - |
| InventarioInsumo | `inventario_insumos` | si | si | si | no | no | - | - |
| Movilizacion | `movilizacion` | si | si | si | no | no | - | - |
| Notificacion | `notificaciones` | si | si | si | si | no | - | - |
| Potrero | `potreros` (conv.) | si | si | si | no | no | - | - |
| ProduccionCarne | `produccion_carne` | si | si | si | no | no | - | - |
| ProduccionLeche | `produccion_leche` | si | si | si | no | no | - | - |
| RegistroVacuna | `registro_vacunas` | si | si | si | no | no | - | vacuna_id |
| Reporte | `reportes` | si | si | si | no | no | - | - |
| Reproduccion | `reproduccion` | si | si | si | no | no | - | - |
| Rotacion | `rotacions` (conv.) | si | si | si | no | no | - | - |
| Tratamiento | `tratamientos` | si | si | si | no | no | - | - |
| User | `users` | si | si | si | si | si | - | - |
| Usuario | `usuarios` | **NO** | - | n/a | no | no | - | - |
| Vacuna | `vacunas` | si | si | si | si | no | - | - |
| Venta | `ventas` | si | si | si | no | no | - | - |

## Controladores

| Controlador | Lineas | FormRequest | validate() inline | $request->all() | filtro manual | raw SQL | raw con tenant | abort(403) | vacios |
|---|---|---|---|---|---|---|---|---|---|
| AnimalController | 145 | 2 | 1 | 1 | 1 | 0 | - | 3 | 0 |
| AnimalEtiquetaController | 52 | 0 | 1 | 0 | 2 | 0 | - | 0 | 6 |
| AnimalQrController | 144 | 0 | 0 | 0 | 0 | 0 | - | 2 | 0 |
| ConfirmPasswordController | 40 | 0 | 0 | 0 | 0 | 0 | - | 0 | 0 |
| ForgotPasswordController | 23 | 0 | 0 | 0 | 0 | 0 | - | 0 | 0 |
| LoginController | 41 | 0 | 0 | 0 | 0 | 0 | - | 0 | 0 |
| RegisterController | 86 | 0 | 0 | 0 | 0 | 0 | - | 0 | 0 |
| ResetPasswordController | 30 | 0 | 0 | 0 | 0 | 0 | - | 0 | 0 |
| VerificationController | 42 | 0 | 0 | 0 | 0 | 0 | - | 0 | 0 |
| CompraController | 102 | 2 | 2 | 1 | 1 | 0 | - | 4 | 0 |
| DashboardController | 80 | 0 | 0 | 0 | 9 | 1 | si | 0 | 6 |
| GastosController | 113 | 2 | 2 | 0 | 1 | 0 | - | 4 | 0 |
| GenealogiController | 93 | 2 | 0 | 0 | 3 | 0 | - | 3 | 0 |
| HomeController | 31 | 0 | 0 | 0 | 0 | 0 | - | 0 | 0 |
| ImportController | 488 | 0 | 4 | 0 | 0 | 0 | - | 0 | 0 |
| InquilinoController | 67 | 2 | 0 | 0 | 0 | 0 | - | 0 | 7 |
| InventarioInsumoController | 83 | 2 | 0 | 0 | 1 | 0 | - | 3 | 0 |
| MovilizacionController | 97 | 0 | 2 | 1 | 3 | 0 | - | 4 | 0 |
| NotificacionController | 117 | 2 | 2 | 2 | 0 | 0 | - | 2 | 0 |
| PotreroController | 130 | 0 | 2 | 1 | 4 | 0 | - | 0 | 0 |
| ProduccionCarneController | 138 | 2 | 0 | 0 | 3 | 0 | - | 3 | 0 |
| ProduccionLecheController | 144 | 2 | 0 | 0 | 3 | 0 | - | 3 | 0 |
| RegistroVacunaController | 87 | 2 | 0 | 0 | 3 | 0 | - | 3 | 0 |
| ReporteController | 297 | 0 | 6 | 0 | 38 | 8 | si | 0 | 0 |
| ReproduccionController | 108 | 2 | 0 | 0 | 3 | 0 | - | 3 | 0 |
| TratamientoController | 91 | 2 | 0 | 0 | 3 | 0 | - | 3 | 0 |
| UsuarioController | 67 | 2 | 0 | 0 | 0 | 0 | - | 0 | 7 |
| VacunaController | 67 | 2 | 0 | 0 | 0 | 0 | - | 0 | 7 |
| VentaController | 89 | 2 | 0 | 0 | 1 | 0 | - | 3 | 0 |

## Notas de metodo

- El filtro manual `where('inquilino_id', ...)` es redundante desde la Fase 2 (el
  TenantScope ya lo aplica) pero se conserva como defensa en profundidad: no es defecto.
- `abort(403)` manual: con el scope global un registro ajeno ya no se encuentra, asi que
  esas comprobaciones son hoy inalcanzables.
- Las consultas raw usan funciones de MySQL/MariaDB (MONTH, DATE_ADD, CURDATE):
  correctas para el objetivo acordado; a revisar solo si se cambia de motor.


## Hallazgos de revision manual (no detectables por reglas)

Se documentan aparte para dejar clara la procedencia de cada hallazgo.

### P0 - El registro de usuarios esta roto

`RegisterController::create()` usa `$data['inquilino']`, una clave que **nunca se valida
ni existe** (la regla esta comentada en la linea 64). Ademas **nada crea el Inquilino**,
que ahora es obligatorio (`users.inquilino_id` NOT NULL + FK). Consecuencia: ningun
usuario nuevo puede registrarse. Hay que decidir el alta de la ganaderia en el registro.

### P0/P1 - La ruta `/` falla en produccion

`welcome.blade.php` pide `resources/css/app.css`, que es **Tailwind v4** y no esta en los
inputs de `vite.config.js` (que tampoco carga el plugin de Tailwind): ese asset no puede
compilarse nunca. Anadir Tailwind activaria su reset y romperia la UI Bootstrap existente,
asi que es una decision de producto; para un panel de gestion lo mas sensato es que `/`
redirija a `/login`. Es el unico test en rojo de la suite.

### P1 - Ningun modelo declara `$casts`

Solo `User` los tiene. El resto devuelve fechas y decimales como string, lo que hace
fragiles los calculos y comparaciones (`ganancia_diaria`, `litros`, `fecha`, `monto`).

### P1 - IDOR dentro de la ganaderia en notificaciones

`NotificacionController@edit` y `@update` no comprueban que la notificacion sea del
usuario autenticado, a diferencia de `@show` y `@destroy` que si lo hacen. El aislamiento
por ganaderia lo cubre el TenantScope, pero un usuario del mismo tenant puede editar
notificaciones de otro usuario.

### P2 - Columna `tamaño` con enie

Se conserva para no romper 7 archivos (modelo, 2 FormRequest, 1 controlador, 4 vistas).
Renombrarla a `tamano` es tarea de Fase 4, con tests que la cubran.

### P2 - Importacion Excel sin limites ni pruebas

`ImportController` (488 lineas) no limita tamano de archivo, numero de filas ni tiempo de
ejecucion: riesgo de agotar memoria o tiempo en produccion.

### P2 - Rutas y codigo muerto

`/home` esta autenticada pero sin middleware `tenant`. `DashboardController` y
`AnimalEtiquetaController` tienen metodos vacios, y `UsuarioController`,
`InquilinoController` y `VacunaController` son stubs completos, sin rutas que los usen.

### Verificado y descartado

Las consultas raw de `ReporteController` y `DashboardController` **si filtran por
`inquilino_id`**: no hay fuga entre ganaderias, solo dependencia de funciones de
MySQL/MariaDB (MONTH, DATE_ADD, CURDATE), correctas para el motor acordado.
## Hallazgos posteriores a este informe (encontrados al corregir los anteriores)

Se documentan aqui por honestidad: las reglas automatizadas NO los detectaron.

### P0 - 403 al editar en 7 modulos (encontrado y corregido en el commit 130ce5e)

Siete FormRequest (UpdateGenealogia, UpdateInventarioInsumo, UpdatePotrero,
UpdateRegistroVacuna, UpdateReproduccion, UpdateTratamiento y UpdateVenta)
comprobaban la propiedad con `Auth::user()->inquilino->inquilino_id`. El PK de
`inquilinos` es `id`, no `inquilino_id`, asi que esa propiedad devuelve null,
la comparacion era siempre false y `authorize()` denegaba la peticion: **el dueno
legitimo recibia 403 al editar**. Evidencia recogida antes del arreglo:

```
Auth::user()->inquilino->inquilino_id = NULL
potrero->inquilino_id === (ese null)  = false   -> 403
potrero->inquilino_id === Auth::user()->inquilino_id = true  -> correcto
```

Por que la auditoria automatica no lo vio: el patron estaba en los FormRequest,
y el auditor solo inspeccionaba controladores. Leccion aplicada: la revision
manual de flujos es imprescindible; las reglas acotan, no sustituyen.

### P0 - Salto de ganaderia por asignacion masiva (corregido en 130ce5e)

Cinco usos de `$request->all()` permitian enviar `inquilino_id` en un POST y
mover un registro a otra ganaderia. Sustituidos por datos validados.
## Correccion de un hallazgo propio (Fase 6)

El hallazgo P2 "Importacion Excel sin limites ni pruebas" era **parcialmente
erroneo**: el controlador ya validaba `required|file|max:10240`, es decir, si
habia limite de 10 MB. Lo que faltaba de verdad era otra cosa:

- **Sin lista blanca de formato**: cualquier archivo de hasta 10 MB se aceptaba
  (un PDF, por ejemplo) y acababa en un mensaje generico.
- **Sin tope de filas**: un CSV de 9 MB podia tener decenas de miles de filas y
  disparar miles de escrituras.
- **Errores silenciados**: `ImportService::leerArchivo()` capturaba cualquier
  excepcion y devolvia una coleccion vacia, de modo que la causa real solo
  quedaba en el log y el usuario veia "el archivo esta vacio o no se pudo leer".

Corregido: lista blanca `csv,txt,xlsx,xls`, tope de `MAX_FILAS = 5000` con
mensaje explicito, y los errores de validacion se propagan en lugar de
silenciarse. Cubierto con 5 tests en `tests/Feature/ImportacionTest.php`.

Queda anotado como deuda (no corregido): las filas se guardan **en la sesion**
entre el paso de mapeo y el de confirmacion, lo que con archivos grandes infla
la sesion y penaliza cada peticion. Lo correcto seria un almacenamiento temporal
en disco o en base de datos.