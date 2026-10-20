# Formato de los archivos de importación

La aplicación importa cinco tipos de datos, cada uno con su propia pantalla:

| Módulo | Dónde | Qué crea |
|---|---|---|
| **Animales** | Importar → Animales | Fichas de animal |
| **Producción de leche** | Importar → Producción de leche | Litros por turno |
| **Producción de carne** | Importar → Producción de carne | Pesajes y ganancia diaria |
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

### Producción de leche

| Columna | ¿Obligatoria? | Ejemplo |
|---|---|---|
| `animal_id` | **Sí** — el id del animal en la aplicación | `1` |
| `fecha` | **Sí** | `2026-02-01` |
| `litros` | **Sí** | `12.5` |
| `turno` | No | `manana` |
| `observaciones` | No | `Producción de la mañana` |

Cada fila crea **un ordeño**. Si el mismo día se ordeña dos veces, van dos filas
con su turno. Si un archivo trae una fila sin `litros`, esa fila se omite y se
cuenta: no se guarda un ordeño de 0 litros.

### Producción de carne

| Columna | ¿Obligatoria? | Ejemplo |
|---|---|---|
| `animal_id` | **Sí** — el id del animal en la aplicación | `1` |
| `fecha` | **Sí** | `2026-02-01` |
| `peso` | **Sí** | `420.50` |
| `ganancia_diaria` | No | `0.750` |
| `observaciones` | No | `Pesaje de febrero` |

Cada fila es **un pesaje**. `ganancia_diaria` es la ganancia media diaria desde el
pesaje anterior; si no la traes, se deja vacía y los informes la calculan cuando
hay dos pesajes del mismo animal.

> Las dos pantallas de producción son **independientes**: un archivo de leche no
> crea carne aunque traiga una columna de peso, y al revés. Es a propósito: cada
> tabla tiene sus columnas obligatorias y así una plantilla no obliga a rellenar
> la mitad de las celdas en blanco.

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
- `plantilla_leche.csv`
- `plantilla_carne.csv`
- `plantilla_compras.csv`
- `plantilla_ventas.csv`

## Si algo no entra

El informe final dice cuántos registros entraron y cuántas filas se omitieron, y
**enumera el motivo de cada fila omitida**. El mensaje distingue los tres casos:

| Mensaje | Qué significa | Qué hacer |
|---|---|---|
| «No se pudo importar: falta asignar monto_total (en tu archivo parece «Total»)» | Esa columna no está asignada en el paso de mapeo | Volver al mapeo y elegirla |
| «Fila 3: la celda está vacía en monto_total» | La fila existe pero esa celda está en blanco | Rellenarla en el archivo |
| «Fila 3: el valor «sin dato» de monto_total no es un número» | El valor está, pero no se puede leer | Corregirlo en el archivo |

El detalle completo queda además en `storage/logs/laravel.log`.
