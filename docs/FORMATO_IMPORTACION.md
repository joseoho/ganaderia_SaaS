# Formato de los archivos de importación

La aplicación importa cuatro tipos de datos, cada uno con su propia pantalla:

| Módulo | Dónde | Qué crea |
|---|---|---|
| **Animales** | Importar → Animales | Fichas de animal |
| **Producción** | Importar → Producción | Producción de leche y/o de carne |
| **Compras** | Importar → Compras | Compras |
| **Ventas** | Importar → Ventas | Ventas |

## Cómo funciona (3 pasos)

1. **Subir** el archivo.
2. **Mapear**: la aplicación muestra las columnas de tu archivo y propone a qué
   campo del sistema corresponde cada una. Si las columnas se llaman como en las
   plantillas, la propuesta ya viene hecha y solo hay que confirmar.
3. **Confirmar**: se importan los datos y se muestra cuántos registros entraron
   y cuántas filas se omitieron.

## Formatos aceptados

- **CSV**, TXT, **XLSX** (Excel) y **XLS** (Excel antiguo).
- En los CSV se acepta tanto la **coma** como el **punto y coma** como separador
  de columnas: la aplicación lo detecta solo. Esto importa porque Excel, con el
  sistema en español, guarda los CSV con **punto y coma**. Si tu archivo se
  abriera entero en una sola columna, era por esto.
- La **primera fila** deben ser los **nombres de las columnas**. No se importa
  como dato.
- Máximo **5.000 filas** por archivo.
- **Tamaño máximo: 2 MB.** Es el límite que trae PHP de fábrica
  (`upload_max_filesize = 2M`). Si necesitas archivos más grandes hay que subir
  ese valor y `post_max_size` en el `php.ini` del servidor. Ojo: la aplicación
  valida hasta 10 MB, pero **el límite que manda es el de PHP**, porque rechaza
  el archivo antes de que la aplicación lo lea.
- Guarda el CSV en **UTF-8** para que los acentos salgan bien.

## Reglas de los datos

- **Fechas**: se aceptan `AAAA-MM-DD` (`2026-02-01`) y **`DD/MM/AAAA`**
  (`01/02/2026`), que se interpreta **como el 1 de febrero**, al estilo de toda
  la vida en español. También se admiten las fechas que Excel guarda como número.
  Si puedes, usa `AAAA-MM-DD`: no deja lugar a dudas.
- **Importes**: se aceptan las dos formas. `1500.50` (punto decimal) y
  **`1.500,50`** (punto de miles y coma decimal, lo normal en español), incluso
  con el símbolo de moneda: `$ 2.500.000` se entiende como 2500000.
- Se admiten **celdas vacías**. Una fila a la que le falte un campo obligatorio
  **se omite y se cuenta**; no se inventa nada y no se detiene la importación.

## Columnas de cada módulo

### Animales

| Columna | ¿Obligatoria? | Ejemplo |
|---|---|---|
| `codigo_interno` | **Sí** — es la clave del animal | `VACA-001` |
| `categoria` | Sí | `bovino` |
| `raza` | Sí | `Holstein` |
| `tipo` | No | `leche` |
| `fecha_nacimiento` | Sí | `2023-04-15` |
| `sexo` | Sí | `M` o `H` |
| `peso_entrada` | No | `480.50` |
| `estado` | Sí | `activo` |

**Volver a importar el mismo archivo no duplica nada**: el animal se busca por
su `codigo_interno` y, si ya existe, **se actualiza**. Es la forma de corregir
datos en bloque.

### Producción

| Columna | ¿Obligatoria? | Ejemplo |
|---|---|---|
| `animal_id` | **Sí** — el id del animal en la aplicación | `1` |
| `fecha` | Sí | `2026-02-01` |
| `litros` | No | `12.5` |
| `turno` | No | `manana` |
| `ganancia_diaria` | No | `0.750` |
| `peso` | No | `485.20` |
| `observaciones` | No | `Producción de la mañana` |

**Una misma fila puede crear dos registros**: si trae `litros`, crea producción
de leche; si trae `ganancia_diaria` o `peso`, crea producción de carne. Si no
trae ninguno de los dos, la fila se omite.

> `animal_id` es el **id interno**, no el `codigo_interno`. Para saberlo, mira la
> dirección al abrir la ficha de un animal (`/animales/12` → id 12) o expórtalo
> desde el listado.

### Compras

| Columna | ¿Obligatoria? | Ejemplo |
|---|---|---|
| `fecha` | Sí | `2026-02-01` |
| `proveedor` | Sí | `AgroSur` |
| `descripcion` | Sí | `Compra de sal mineral` |
| `monto_total` | Sí | `150000.50` |

### Ventas

| Columna | ¿Obligatoria? | Ejemplo |
|---|---|---|
| `cliente` | Sí | `Finca El Roble` |
| `fecha` | Sí | `2026-02-01` |
| `descripcion` | Sí | `Venta de dos novillos` |
| `monto_total` | Sí | `2500000` |

## Plantillas listas para usar

En `docs/plantillas/` hay un archivo de ejemplo por módulo, con las cabeceras
exactas que la aplicación espera. Se pueden abrir en Excel, sustituir los datos
de ejemplo por los reales y subirlos:

- `plantilla_animales.csv`
- `plantilla_produccion.csv`
- `plantilla_compras.csv`
- `plantilla_ventas.csv`

## Si algo no entra

El informe final dice cuántos registros entraron y cuántas filas se omitieron, y
**enumera el motivo de cada fila omitida** (por ejemplo: «Fila 4: le falta
fecha»). Así se corrige el archivo sin adivinar. El detalle completo queda
además en `storage/logs/laravel.log`.
