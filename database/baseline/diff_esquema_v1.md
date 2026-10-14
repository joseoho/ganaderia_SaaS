# Diff estructural - Fase 1 (esquema original vs redisenado)

Generado: 2026-10-13 21:06

| | Original `ganaderia_saas` | Nuevo `ganaderia_saas_test` |
|---|---|---|
| Tablas | 28 | 28 |
| Claves foraneas | 16 | 33 |
| Indices (sin PK) | 35 | 40 |
| Columnas inquilino_id sin FK | 11 | 0 |
| Columnas inquilino_id sin indice | 1 | 0 |

## Columnas ANADIDAS en el esquema nuevo

```
TABLE_NAME	COLUMN_NAME	COLUMN_TYPE	IS_NULLABLE
inquilinos	created_at	timestamp	YES
inquilinos	updated_at	timestamp	YES
inventario_insumos	ubicacion	varchar(100)	YES
notificaciones	inquilino_id	bigint(20) unsigned	NO
reportes	created_at	timestamp	YES
reportes	updated_at	timestamp	YES
vacunas	created_at	timestamp	YES
vacunas	inquilino_id	bigint(20) unsigned	NO
vacunas	updated_at	timestamp	YES
```

## Columnas ELIMINADAS

```
```

## Cambios de NULABILIDAD

```
TABLE_NAME	COLUMN_NAME	antes	ahora
animales	categoria	YES	NO
animales	estado	YES	NO
animales	fecha_nacimiento	YES	NO
animales	inquilino_id	YES	NO
animales	raza	YES	NO
animales	sexo	YES	NO
compras	descripcion	YES	NO
compras	fecha	YES	NO
compras	inquilino_id	YES	NO
compras	monto_total	YES	NO
compras	proveedor	YES	NO
gastos	inquilino_id	YES	NO
genealogia	observaciones	NO	YES
inquilinos	nombre	YES	NO
inventario_insumos	cantidad	YES	NO
inventario_insumos	fecha_ingreso	YES	NO
inventario_insumos	inquilino_id	YES	NO
inventario_insumos	nombre	YES	NO
inventario_insumos	unidad	YES	NO
movilizacion	fecha	YES	NO
movilizacion	tipo	YES	NO
notificaciones	estado	YES	NO
notificaciones	fecha_envio	YES	NO
notificaciones	mensaje	YES	NO
notificaciones	tipo	YES	NO
notificaciones	usuario_id	YES	NO
potreros	nombre	YES	NO
potreros	tamaño	YES	NO
produccion_carne	fecha	YES	NO
produccion_carne	peso	YES	NO
produccion_leche	fecha	YES	NO
produccion_leche	litros	YES	NO
registro_vacunas	fecha_aplicacion	YES	NO
reportes	inquilino_id	YES	NO
reproduccion	fecha	YES	NO
reproduccion	tipo	YES	NO
reproduccion	toro	NO	YES
tratamientos	fecha	YES	NO
tratamientos	medicamento	YES	NO
tratamientos	motivo	YES	NO
ventas	cliente	YES	NO
ventas	descripcion	YES	NO
ventas	fecha	YES	NO
ventas	inquilino_id	YES	NO
ventas	monto_total	YES	NO
```

## Cambios de TIPO

```
TABLE_NAME	COLUMN_NAME	antes	ahora
animales	id	int(11)	bigint(20) unsigned
animales	inquilino_id	int(11)	bigint(20) unsigned
animal_potrero	animal_id	int(11)	bigint(20) unsigned
animal_potrero	id	int(11)	bigint(20) unsigned
animal_potrero	inquilino_id	int(11)	bigint(20) unsigned
animal_potrero	potrero_id	int(11)	bigint(20) unsigned
compras	id	int(11)	bigint(20) unsigned
compras	inquilino_id	int(11)	bigint(20) unsigned
gastos	inquilino_id	int(11)	bigint(20) unsigned
genealogia	animal_id	int(11)	bigint(20) unsigned
genealogia	id	int(11)	bigint(20) unsigned
genealogia	inquilino_id	int(11)	bigint(20) unsigned
genealogia	madre_id	int(11)	bigint(20) unsigned
genealogia	padre_id	int(11)	bigint(20) unsigned
inquilinos	id	int(11)	bigint(20) unsigned
inventario_insumos	id	int(11)	bigint(20) unsigned
inventario_insumos	inquilino_id	int(11)	bigint(20) unsigned
inventario_insumos	minimo	int(11)	int(10) unsigned
movilizacion	animal_id	int(11)	bigint(20) unsigned
movilizacion	id	int(11)	bigint(20) unsigned
movilizacion	inquilino_id	int(11)	bigint(20) unsigned
notificaciones	id	int(11)	bigint(20) unsigned
notificaciones	usuario_id	int(11)	bigint(20) unsigned
potreros	id	int(11)	bigint(20) unsigned
potreros	inquilino_id	int(11)	bigint(20) unsigned
produccion_carne	animal_id	int(11)	bigint(20) unsigned
produccion_carne	id	int(11)	bigint(20) unsigned
produccion_carne	inquilino_id	int(11)	bigint(20) unsigned
produccion_leche	animal_id	int(11)	bigint(20) unsigned
produccion_leche	id	int(11)	bigint(20) unsigned
produccion_leche	inquilino_id	int(11)	bigint(20) unsigned
registro_vacunas	animal_id	int(11)	bigint(20) unsigned
registro_vacunas	id	int(11)	bigint(20) unsigned
registro_vacunas	inquilino_id	int(11)	bigint(20) unsigned
registro_vacunas	nombre	varchar(50)	varchar(100)
registro_vacunas	vacuna_id	int(11)	bigint(20) unsigned
reportes	id	int(11)	bigint(20) unsigned
reportes	inquilino_id	int(11)	bigint(20) unsigned
reproduccion	animal_id	int(11)	bigint(20) unsigned
reproduccion	id	int(11)	bigint(20) unsigned
reproduccion	inquilino_id	int(11)	bigint(20) unsigned
reproduccion	toro	varchar(10)	varchar(100)
rotacions	carga_animal	decimal(8,2)	decimal(10,2)
rotacions	dias_ocupacion	int(11)	int(10) unsigned
rotacions	id	int(11)	bigint(20) unsigned
rotacions	inquilino_id	int(11)	bigint(20) unsigned
rotacions	potrero_id	int(11)	bigint(20) unsigned
tratamientos	animal_id	int(11)	bigint(20) unsigned
tratamientos	id	int(11)	bigint(20) unsigned
tratamientos	inquilino_id	int(11)	bigint(20) unsigned
users	inquilino_id	int(11)	bigint(20) unsigned
vacunas	id	int(11)	bigint(20) unsigned
ventas	id	int(11)	bigint(20) unsigned
ventas	inquilino_id	int(11)	bigint(20) unsigned
```

