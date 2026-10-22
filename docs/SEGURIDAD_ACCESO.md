# Control de acceso

Diseño del control de acceso de la aplicación, por fases. Los dos roles son
**administrador** y **usuario**, y no habrá más: para una finca, un sistema de
permisos fino es burocracia; lo que separa a los dos roles es el dinero.

## La regla que manda

> Todo el control de acceso sale de **una tabla**: `app/Support/MatrizDePermisos.php`.
> Lo que no está concedido, está negado.

No hay condiciones repartidas por controladores ni vistas comparando nombres de
rol. Si hay que abrir o cerrar algo, se cambia en la matriz y se ejecutan las
pruebas.

## Los roles

| Rol | Quién es | Qué puede |
|---|---|---|
| **administrador** | El dueño de la ganadería | Todo: dinero, borrados, importación, usuarios |
| **usuario** | Quien trabaja con los animales | Animales, producción, sanidad, potreros e informes de producción |

Un `rol` desconocido (una fila vieja, un POST manipulado) se trata **siempre**
como `usuario`, nunca como administrador: un error de dedo no puede dar permiso
para borrar.

## La matriz

Permisos de la **Fase 1** (implementados):

| Permiso | administrador | usuario | Qué protege |
|---|:---:|:---:|---|
| `dinero.ver` | ✅ | ❌ | Compras, ventas, gastos, balance y sus informes |
| `registros.eliminar` | ✅ | ❌ | Cualquier `destroy` (animales, potreros, producción…) |
| `datos.importar` | ✅ | ❌ | Importación CSV/Excel (escribe en masa y puede pisar datos) |
| `usuarios.gestionar` | ✅ | ❌ | Crear usuarios, cambiar rol, desactivar |
| `reportes.ver` | ✅ | ✅ | Informes de producción, potreros y animales |

Permisos previstos para la **Fase 2** (se añaden cuando se vayan a comprobar de
verdad; un permiso que nadie comprueba da falsa sensación de control):

| Permiso | administrador | usuario | Qué protege |
|---|:---:|:---:|---|
| `animales.editar_historico` | ✅ | ❌ | Corregir datos de un animal ya cerrado |
| `inventario.ajustar` | ✅ | ❌ | Ajustes de inventario (no el consumo del día) |
| `auditoria.ver` | ✅ | ❌ | Bitácora de acciones críticas |

## Las cuatro capas

1. **Ruta** — `can:dinero.ver` en el grupo o recurso. Es la puerta: si falla,
   responde 403 antes de ejecutar nada.
2. **Controlador** — `$this->authorize(...)` cuando la acción no va atada a una
   ruta (por ejemplo, un POST de una acción especial).
3. **Vista** — `@can('dinero.ver')` para **no ofrecer** lo que no se puede.
   Ocultar no protege: las rutas responden 403 por su cuenta.
4. **Middleware de borrados** — `SoloAdministradorPuedeBorrar` traduce cualquier
   verbo DELETE + ruta `*.destroy` al permiso `registros.eliminar`, para no
   tocar los catorce controladores.

Además, el aislamiento entre ganaderías **no es un permiso**: lo sigue haciendo
el middleware `tenant` y el filtro global de los modelos. Un administrador de la
ganadería A no puede tocar los datos de la B, aunque tenga todos los permisos.

## Reglas del juego (para que no se degrade)

- **Una ruta nueva nace cerrada.** Si no lleva `can:`, tiene que estar
  justificada en la lista de rutas abiertas de `PermisosTest`. Ese test recorre
  las rutas reales y falla si una ruta de dinero o de importación se queda sin
  su permiso.
- **El rol no se escala solo.** El formulario de usuarios (Fase 2) solo acepta
  los valores de `App\Enums\Rol` y exige `usuarios.gestionar` para cambiarlos.
- **El 403 explica el motivo**, no muestra un error genérico: la pantalla dice
  qué puede hacer un usuario y que pida el cambio de rol.
- **Toda decisión tiene prueba**: la matriz (`PermisosTest`), las rutas
  (`test_las_rutas_de_dinero_e_importacion_llevan_su_permiso`) y la interfaz
  (`test_al_usuario_no_se_le_ofrece_el_dinero…`).

## Cómo añadir un permiso (receta)

1. Añadir el caso en `app/Enums/Permiso.php` (con su etiqueta).
2. Concederlo en `app/Support/MatrizDePermisos.php` al rol que corresponda.
3. Ponerlo en la ruta (`can:...`) y, si aplica, en la vista (`@can`).

Las puertas de Laravel se declaran solas: `AppServiceProvider` recorre
`Permiso::cases()`, así que no hay que registrar nada a mano.

## Fases

### Fase 0 — Base (hecha)

- `App\Enums\Rol` y `App\Enums\Permiso`.
- `App\Support\MatrizDePermisos` como única fuente de verdad.
- Puertas de Laravel declaradas en `AppServiceProvider`.
- `User::puede(Permiso)` y `User::rolActual()` (con caída segura a `usuario`).
- Renombrado del rol `operario` a `usuario` (migración de datos incluida).
- Comandos `ganaderia:usuario` y `ganaderia:rol` alineados con el enum.

### Fase 1 — Lo crítico (esta entrega)

- `dinero.ver` en compras, ventas, gastos y sus informes.
- `datos.importar` en todo `/importar`.
- `registros.eliminar` en el middleware de borrados.
- Menú y dashboard sin cifras para el usuario.
- Página 403 que explica el motivo.
- `PermisosTest` + guardarraíl de rutas.

### Fase 2 — Gestión de la gente (hecha)

Ya no hace falta la consola para dar de alta a un trabajador:

- Pantalla `Usuarios` (`usuarios.gestionar`), en el menú solo para el administrador.
- Listar, crear, cambiar rol y **desactivar** (nunca borrar: los registros
  históricos —quién registró una venta, quién hizo un pesaje— apuntan a esa cuenta).
- Al crear se elige la contraseña y se puede dar el correo por verificado (el
  administrador responde por esa persona); si no se marca, la cuenta espera al
  correo de verificación.
- Una cuenta desactivada no inicia sesión: el filtro está en las credenciales, así
  que el error es el mismo que con una contraseña equivocada.
- Cambiar el correo de una cuenta invalida su verificación (el correo nuevo no es
  el que se confirmó).

Guardas que evitan quedarse fuera de la propia ganadería:

1. Nadie se cambia el rol a sí mismo.
2. Nadie desactiva su propia cuenta.
3. La ganadería no puede quedarse sin administrador (ni al degradar ni al
   desactivar al último activo). Es defensa en profundidad: por HTTP no se
   alcanza, porque el único que podría dispararla es el propio administrador.
4. Un administrador de A no puede tocar cuentas de B ni sabiendo el id: la
   búsqueda va siempre dentro de su ganadería y responde 404, no 403.

Pendiente para más adelante: auditoría de quién creó/cambió a quién, invitación
por correo con caducidad (hoy la contraseña se comparte a mano), y cierre de las
sesiones abiertas de una cuenta en el momento de desactivarla.

### Fase 3 — Cuando haya clientes grandes

- Multi-finca por cuenta (el veterinario que atiende varios hatos).
- Roles por finca, no por cuenta.
- 2FA para el administrador.

### Fase 1.5 — Verificación de correo (hecha)

Estaba apagada de facto: `User` no implementaba `MustVerifyEmail` y el
middleware `verified` no bloqueaba nada. Ahora:

- `User` implementa el contrato y usa el trait `MustVerifyEmail`.
- Rutas de verificación activas (`Auth::routes(['verify' => true])`): aviso,
  enlace firmado y reenvío.
- `/home` exige `verified`, así que quien se registra cae en el aviso.
- La pantalla de aviso es propia (no usa el layout del panel: quien no está
  verificado no debe ver el menú).
- El enlace lleva caducidad (60 min) y firma; un enlace manipulado responde 403.

Pendiente de infraestructura, no de código: en producción hay que configurar
**SMTP** de verdad. Con `MAIL_MAILER=log` el enlace solo se escribe en
`storage/logs/laravel.log`, que sirve para probar pero no para un cliente.

## Decisiones abiertas (revisar antes de comercializar)

1. **El QR público de edición** (`animal/qr/{id}/editar-todo`) permite editar los
   datos de un animal desde un enlace sin sesión. Hoy queda fuera de esta matriz
   por ser una ruta pública; hay que decidir si se firma el enlace, se limita en
   el tiempo o se exige sesión.
2. **El usuario no ve el dinero.** Si en la práctica el operario necesita
   registrar gastos de campo, se parte el permiso en `dinero.ver` y
   `dinero.registrar` (la matriz ya está preparada para eso).
3. **Contraseñas y sesiones:** falta decidir si se exige una contraseña mínima
   más fuerte (hoy 8 caracteres) y si se cierran las sesiones al cambiar la
   contraseña.
