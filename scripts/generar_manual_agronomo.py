"""Genera el manual de la app para revision agronomica (DOCX)."""

from docx import Document
from docx.enum.table import WD_TABLE_ALIGNMENT
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.shared import Pt, RGBColor, Cm

VERDE = RGBColor(0x2F, 0x6F, 0x52)
GRIS = RGBColor(0x55, 0x63, 0x5D)

doc = Document()

# --- Estilo base -------------------------------------------------------------
normal = doc.styles["Normal"]
normal.font.name = "Calibri"
normal.font.size = Pt(10.5)
normal.paragraph_format.space_after = Pt(6)
normal.paragraph_format.line_spacing = 1.12

for seccion in doc.sections:
    seccion.top_margin = Cm(2.2)
    seccion.bottom_margin = Cm(2.0)
    seccion.left_margin = Cm(2.4)
    seccion.right_margin = Cm(2.4)


def titulo(texto, nivel=1):
    h = doc.add_heading(texto, level=nivel)
    for run in h.runs:
        run.font.color.rgb = VERDE
    return h


def parrafo(texto, negrita=False, cursiva=False, tam=10.5, color=None, espaciado=6):
    p = doc.add_paragraph()
    run = p.add_run(texto)
    run.bold = negrita
    run.italic = cursiva
    run.font.size = Pt(tam)
    if color is not None:
        run.font.color.rgb = color
    p.paragraph_format.space_after = Pt(espaciado)
    return p


def vinetas(items):
    for item in items:
        p = doc.add_paragraph(item, style="List Bullet")
        p.paragraph_format.space_after = Pt(2)


def numeros(items):
    """Lista numerada que reinicia en 1 en cada bloque.

    El estilo "List Number" de Word sigue la numeracion entre bloques (1,2,3,
    4,5,6...), y aqui cada rutina empieza de nuevo.
    """
    from docx.shared import Cm as _Cm

    for i, item in enumerate(items, start=1):
        p = doc.add_paragraph()
        p.paragraph_format.left_indent = _Cm(0.9)
        p.paragraph_format.first_line_indent = _Cm(-0.6)
        p.paragraph_format.space_after = Pt(2)
        p.add_run(f"{i}.  ").bold = True
        p.add_run(item)


def tabla(encabezados, filas, anchos=None):
    t = doc.add_table(rows=1, cols=len(encabezados))
    t.style = "Table Grid"
    t.alignment = WD_TABLE_ALIGNMENT.CENTER
    cab = t.rows[0].cells
    for i, texto in enumerate(encabezados):
        cab[i].text = ""
        run = cab[i].paragraphs[0].add_run(texto)
        run.bold = True
        run.font.size = Pt(9.5)
    for fila in filas:
        celdas = t.add_row().cells
        for i, valor in enumerate(fila):
            celdas[i].text = ""
            run = celdas[i].paragraphs[0].add_run(str(valor))
            run.font.size = Pt(9.5)
    if anchos:
        for fila in t.rows:
            for i, ancho in enumerate(anchos):
                fila.cells[i].width = Cm(ancho)

    # Acabado: el encabezado se repite si la tabla sigue en otra pagina y
    # ninguna fila se parte a la mitad.
    from docx.oxml import OxmlElement
    from docx.oxml.ns import qn

    cabecera = t.rows[0]._tr.get_or_add_trPr()
    cabecera.append(OxmlElement("w:tblHeader"))

    for fila in t.rows:
        fila._tr.get_or_add_trPr().append(OxmlElement("w:cantSplit"))
    doc.add_paragraph().paragraph_format.space_after = Pt(2)
    return t


# --- Portada -----------------------------------------------------------------
p = doc.add_paragraph()
p.alignment = WD_ALIGN_PARAGRAPH.CENTER
p.paragraph_format.space_before = Pt(140)
run = p.add_run("Control Ganadero")
run.font.size = Pt(34)
run.bold = True
run.font.color.rgb = VERDE

p = doc.add_paragraph()
p.alignment = WD_ALIGN_PARAGRAPH.CENTER
run = p.add_run("Manual de la aplicación para revisión técnica")
run.font.size = Pt(16)
run.font.color.rgb = GRIS

p = doc.add_paragraph()
p.alignment = WD_ALIGN_PARAGRAPH.CENTER
p.paragraph_format.space_before = Pt(28)
run = p.add_run("Documento de trabajo para el agrónomo colaborador")
run.font.size = Pt(11)
run.italic = True

p = doc.add_paragraph()
p.alignment = WD_ALIGN_PARAGRAPH.CENTER
p.paragraph_format.space_before = Pt(90)
run = p.add_run("Versión 1.0  ·  Noviembre 2026")
run.font.size = Pt(10)
run.font.color.rgb = GRIS

doc.add_page_break()

# --- 1. Para qué sirve ------------------------------------------------------
titulo("1. Para qué sirve este documento", 1)
parrafo(
    "Esta aplicación registra el trabajo diario de una finca de ganadería: animales, potreros, "
    "sanidad, reproducción, producción de leche y carne, inventario y el dinero que entra y sale. "
    "Está pensada para usarse desde el computador de la oficina y, en el campo, desde el celular."
)
parrafo("Lo que necesitamos de usted es criterio de campo, no de informática:")
vinetas([
    "Decir qué le falta para que sirva de verdad en una finca (datos que hoy no se piden y deberían pedirse).",
    "Decir qué le sobra o qué no usaría nunca.",
    "Corregir lo que esté mal planteado desde el punto de vista zootécnico y sanitario.",
    "Contarnos qué reporte pediría un dueño o un veterinario y hoy no está.",
])
parrafo(
    "Al final hay una lista de preguntas concretas. Con sus respuestas priorizamos los cambios.",
    negrita=True,
)

# --- 2. La aplicación en una página ------------------------------------------
titulo("2. La aplicación en una página", 1)
vinetas([
    "Es una aplicación web: se abre en el navegador, no hay que instalar nada.",
    "Cada ganadería es independiente: cada finca ve solo sus datos, aunque el sistema aloje muchas.",
    "Dos roles dentro de la finca: administrador (el dueño: todo, incluido el dinero) y usuario "
    "(trabajo con los animales: no ve dinero, no borra, no importa archivos).",
    "Un tercer rol fuera de la finca, el operador de la plataforma, es el que cobra la suscripción.",
    "Todo lo que se registra queda asociado a un animal y una fecha, que es lo que permite después "
    "sacar informes por período.",
])

# --- 3. Cómo entrar ----------------------------------------------------------
titulo("3. Cómo entrar a probarla", 1)
parrafo("Dirección: http://ganaderia_SaaS.test  (funciona en la red local de la oficina).")
tabla(
    ["Cuenta", "Contraseña", "Qué muestra"],
    [
        ["admin@ganaderia.test", "password", "Dueño de una finca demo con datos cargados (rol administrador)."],
        ["usuario@ganaderia.test", "usuario2026", "Trabajador de campo: lo mismo, sin dinero ni borrados."],
        ["dueno@fincaelroble.test", "roble-2026", "Otra finca, más pequeña: sirve para ver que los datos no se mezclan."],
        ["pagos@ganaderia.test", "operador-2026", "Panel del operador que revisa los comprobantes de pago."],
    ],
    anchos=[5.2, 3.0, 8.2],
)
parrafo(
    "Los datos que verá son de ejemplo. Se pueden borrar de un botón cuando la finca cargue los reales.",
    cursiva=True,
    color=GRIS,
)

# --- 4. Mapa de módulos ------------------------------------------------------
titulo("4. Mapa de módulos", 1)
tabla(
    ["Módulo", "Para qué sirve en la finca"],
    [
        ["Animales", "Ficha de cada animal: código, categoría, raza, sexo, nacimiento, peso de entrada, estado."],
        ["Potreros", "Potreros, tamaño, pasto y estado (ocupado, descanso, disponible), con entradas y salidas de animales."],
        ["Reproducción", "Montas e inseminaciones, toro usado, resultado y fecha probable de parto."],
        ["Vacunas", "Catálogo de vacunas y cada dosis aplicada: lote, proveedor, vía, dosis y próxima dosis."],
        ["Tratamientos", "Motivo, medicamento, vía, dosis, fecha de inicio y de fin."],
        ["Producción de leche", "Litros por animal, por fecha y por turno (mañana/tarde)."],
        ["Producción de carne", "Pesajes por animal, con peso anterior y ganancia diaria."],
        ["Inventario", "Insumos y medicamentos: cantidad, unidad, mínimo de existencia y ubicación."],
        ["Compras / Ventas / Gastos", "El dinero: compras a proveedores, ventas a clientes y gastos operativos por categoría."],
        ["Reportes", "Informes por potrero, por animal, por período, de leche, de carne, de compras, de ventas y financiero."],
        ["Etiquetas QR", "Impresión de etiquetas con código QR para pegar en el animal o en el arete."],
        ["Importar datos", "Cargar desde Excel o CSV: animales, leche, carne, compras y ventas."],
        ["Usuarios", "Dar de alta al personal, cambiarle el rol y desactivarlo cuando ya no trabaja."],
        ["Suscripción", "Estado del pago del servicio y envío del comprobante."],
        ["Notificaciones", "Avisos de vencimiento de la suscripción y del resultado de los pagos."],
    ],
    anchos=[4.2, 12.0],
)

# --- 5. Los módulos, uno por uno --------------------------------------------
titulo("5. Los módulos, uno por uno", 1)

titulo("5.1 Animales", 2)
parrafo(
    "Es el centro del sistema: casi todo lo demás se cuelga de un animal. Cada animal tiene un código "
    "interno único dentro de la finca (el arete, la chapeta, el número que use la finca)."
)
tabla(
    ["Dato", "Observación"],
    [
        ["Código interno", "Obligatorio y único en la finca. Es la clave para buscar y para importar."],
        ["Categoría", "Bovino, ovino, caprino…"],
        ["Raza y tipo", "Texto libre (Holstein, Brahman, Cebú…). El tipo se usa para Madre, Padre, Maute, etc."],
        ["Sexo", "Macho o hembra."],
        ["Fecha de nacimiento", "Obligatoria; de ella salen la edad y los informes por edad."],
        ["Peso de entrada", "En kilos."],
        ["Estado", "Activo, vendido o muerto. Un animal vendido sigue en el sistema para el histórico."],
    ],
    anchos=[4.0, 12.2],
)
parrafo(
    "De cada animal se puede ver su ficha con: vacunas, tratamientos, pesajes, producción de leche, "
    "reproducción, genealogía (padre y madre) y movilizaciones. También su código QR.",
)
parrafo(
    "Un animal con historial no se puede borrar: el sistema se niega y dice qué historial lo impide. "
    "Se cambia su estado a vendido o muerto.",
)

titulo("5.2 Potreros y rotación", 2)
tabla(
    ["Dato", "Observación"],
    [
        ["Nombre", "Potrero Norte, El Alto…"],
        ["Tamaño", "En hectáreas."],
        ["Tipo de pasto", "Brachiaria, Estrella, Kikuyo…"],
        ["Estado", "Ocupado, descanso o disponible."],
        ["Último ingreso / último descanso", "Fechas para saber cuánto lleva descansando."],
    ],
    anchos=[5.0, 11.2],
)
parrafo(
    "Desde el potrero se asignan y se sacan animales, y queda el registro de ese movimiento. "
    "Hoy no se calcula la carga animal (animales por hectárea) ni los días de ocupación: es una de "
    "las cosas que queremos que nos diga si hace falta."
)

titulo("5.3 Reproducción", 2)
tabla(
    ["Dato", "Observación"],
    [
        ["Animal", "La vaca o novilla."],
        ["Tipo", "Monta o inseminación."],
        ["Fecha", "Fecha del servicio."],
        ["Toro / pajilla", "Identificación del reproductor."],
        ["Resultado", "Preñada, vacía, pendiente."],
        ["Observaciones", "Texto libre."],
    ],
    anchos=[4.2, 12.0],
)
parrafo(
    "El sistema marca los partos previstos a 283 días de la monta y los muestra en el panel cuando "
    "faltan 30 días o menos."
)

titulo("5.4 Sanidad: vacunas y tratamientos", 2)
parrafo("Vacunas: hay un catálogo por finca y luego el registro de cada aplicación.")
tabla(
    ["Dato (vacuna aplicada)", "Observación"],
    [
        ["Animal y vacuna", "Puede escribirse el nombre o elegirlo del catálogo."],
        ["Lote y proveedor", "Para trazabilidad y para reclamar al proveedor."],
        ["Vía y dosis", "Intramuscular, subcutánea… y mililitros."],
        ["Fecha de aplicación", "Obligatoria."],
        ["Próxima dosis", "Con esto se pueden generar recordatorios."],
    ],
    anchos=[5.4, 10.8],
)
parrafo("Tratamientos: la enfermedad y el medicamento, con fecha de inicio y de fin.")
tabla(
    ["Dato (tratamiento)", "Observación"],
    [
        ["Animal y motivo", "Mastitis, cojera, parasitosis…"],
        ["Medicamento", "Nombre comercial o principio activo."],
        ["Vía, dosis", "Cómo y cuánto se aplicó."],
        ["Fecha y fecha de fin", "Permite saber si sigue en tratamiento."],
    ],
    anchos=[5.4, 10.8],
)
parrafo(
    "Falta lo que usted seguramente va a pedir: el tiempo de retiro (días que hay que esperar para "
    "vender leche o carne después del tratamiento). Hoy no se registra.",
    negrita=True,
)

titulo("5.5 Producción de leche", 2)
tabla(
    ["Dato", "Observación"],
    [
        ["Animal y fecha", "Obligatorios."],
        ["Turno", "Mañana o tarde (texto)."],
        ["Litros", "Obligatorio y por turno, no por día."],
        ["Litros anteriores / variación", "Los calcula el sistema comparando con el registro anterior del animal."],
    ],
    anchos=[5.6, 10.6],
)
parrafo(
    "Hoy se registra por animal. Si la finca mide por tanque o por lote, no encaja: díganos cómo se "
    "mide en la práctica."
)

titulo("5.6 Producción de carne", 2)
tabla(
    ["Dato", "Observación"],
    [
        ["Animal y fecha", "Obligatorios."],
        ["Peso", "En kilos."],
        ["Peso anterior / ganancia diaria", "Los calcula el sistema entre pesajes."],
    ],
    anchos=[5.6, 10.6],
)
parrafo(
    "Está pensado para pesajes periódicos (mensuales). No hay todavía peso al nacer, peso al destete "
    "ni conversión alimenticia."
)

titulo("5.7 Inventario de insumos", 2)
tabla(
    ["Dato", "Observación"],
    [
        ["Nombre y categoría", "Alimentación, veterinaria, otros."],
        ["Cantidad, unidad y mínimo", "Con el mínimo, el panel avisa cuando el stock está bajo."],
        ["Ubicación", "Bodega principal, botiquín…"],
        ["Fecha de ingreso y descripción", "Para saber desde cuándo está."],
    ],
    anchos=[5.4, 10.8],
)
parrafo("No maneja lotes ni fechas de vencimiento. Es una de las mejoras que tenemos anotadas.")

titulo("5.8 El dinero: compras, ventas y gastos", 2)
tabla(
    ["Módulo", "Qué guarda"],
    [
        ["Compras", "Fecha, proveedor, descripción y monto total."],
        ["Ventas", "Fecha, cliente, descripción y monto total."],
        ["Gastos", "Fecha, categoría (nómina, mantenimiento, servicios…), monto y descripción."],
    ],
    anchos=[3.6, 12.6],
)
parrafo(
    "Son tres cosas distintas a propósito: una compra tiene proveedor y una venta tiene cliente; un "
    "gasto es el costo de operar la finca. El panel muestra el balance: ventas menos compras y gastos."
)

titulo("5.9 Reportes", 2)
parrafo(
    "Hay informes de potreros (qué animales hay en cada uno), por animal, por rango de fechas, de "
    "producción de leche, de producción de carne, de compras, de ventas, financiero y un tablero "
    "general. Todos se pueden imprimir. Hoy no se descargan a Excel ni a PDF."
)

titulo("5.10 Códigos QR y etiquetas", 2)
parrafo(
    "Cada animal tiene un QR. Se pueden imprimir etiquetas para pegar en el arete o en la ficha. Al "
    "escanearlo desde el celular se abre la ficha del animal, que se puede consultar y editar."
)

titulo("5.11 Importar desde Excel", 2)
parrafo(
    "Permite cargar de una vez animales, producción de leche, producción de carne, compras y ventas "
    "desde un archivo de Excel o CSV. El proceso es: subir el archivo, mapear columnas (la aplicación "
    "propone el mapeo si los encabezados coinciden) y confirmar. Al final dice cuántos registros "
    "entraron y, fila por fila, por qué se omitió cada una que falló."
)
parrafo(
    "Además de cargar, sirve para corregir en bloque: si se importa un animal con un código que ya "
    "existe, se actualiza en lugar de duplicarse.",
    negrita=True,
)

titulo("5.12 Usuarios, roles y suscripción", 2)
tabla(
    ["Rol", "Qué puede"],
    [
        ["Administrador", "Todo lo de su finca: dinero, borrados, importación y gestión de usuarios."],
        ["Usuario", "Animales, potreros, sanidad, producción e informes de producción. No ve dinero, no borra, no importa."],
    ],
    anchos=[3.8, 12.4],
)
parrafo(
    "El dueño da de alta a su personal desde Usuarios, le entrega la contraseña y puede desactivarlo "
    "cuando deja de trabajar (la cuenta no se borra: el histórico de esa persona se conserva)."
)
parrafo(
    "El servicio se cobra por suscripción: hay planes con límite de animales y de usuarios, se paga "
    "por Pago Móvil, transferencia, Zelle o USDT, se sube el comprobante y el operador lo aprueba. Si "
    "vence el pago, la finca entra en modo lectura: se consulta y se exporta todo, pero no se registra "
    "nada nuevo hasta que se apruebe el pago siguiente."
)

# --- 6. Rutinas del campo ----------------------------------------------------
titulo("6. Rutinas del campo, paso a paso", 1)

titulo("Ordeño del día", 2)
numeros([
    "Producción de leche → Registrar: se elige el animal, la fecha, el turno y los litros.",
    "Se repite por cada animal ordeñado (o se carga el mes completo desde Excel).",
    "El sistema compara con el registro anterior del mismo animal y marca la variación.",
])

titulo("Pesaje mensual", 2)
numeros([
    "Producción de carne → Registrar: animal, fecha y peso.",
    "El sistema calcula la ganancia diaria contra el pesaje anterior.",
    "El informe de carne muestra la evolución por animal y por período.",
])

titulo("Jornada de vacunación", 2)
numeros([
    "Vacunas → Registrar: animal, vacuna, lote, proveedor, vía, dosis, fecha y próxima dosis.",
    "Si la vacuna no está en el catálogo, se escribe el nombre y queda registrada igual.",
])

titulo("Entrada y salida de un potrero", 2)
numeros([
    "Potreros → asignar animales al potrero que corresponde.",
    "Al sacarlos, se registra la salida; el potrero cambia a descanso.",
    "Queda el histórico de qué animales pasaron por cada potrero y cuándo.",
])

titulo("Venta de un animal", 2)
numeros([
    "Ventas → Registrar: fecha, cliente, descripción y monto.",
    "En Animales, ese animal se cambia de estado a «vendido» (no se borra).",
])

# --- 7. Qué falta ------------------------------------------------------------
titulo("7. Lo que ya sabemos que falta", 1)
parrafo(
    "No es una lista de deseos: son cosas que hoy no están y que creemos importantes. Queremos su "
    "opinión sobre el orden."
)
tabla(
    ["Falta", "Por qué importa"],
    [
        ["Tiempo de retiro de leche y carne", "Después de un tratamiento hay días en que no se puede vender la leche ni sacrificar el animal. Hoy no se registra y es información sanitaria crítica."],
        ["Costo por animal y por lote", "Hay compras, gastos y producción, pero no se responde «¿esta vaca me deja dinero?»."],
        ["Lotes o grupos de animales", "Manejar por lote (ordeño, levante, ceba) en lugar de animal por animal."],
        ["Peso al nacer, destete y conversión", "Para evaluar el crecimiento de los mautes."],
        ["Uso en el celular sin señal", "En el potrero no hay datos: hoy hay que anotar y cargar después en la oficina."],
        ["Avisos por WhatsApp", "Que al dueño le llegue el resumen y los avisos sin abrir la aplicación."],
        ["Exportar informes a Excel o PDF", "Hoy solo se imprimen."],
        ["Inventario con lote y vencimiento", "Para medicamentos y alimentos."],
        ["Precio por kilo o por litro en ventas", "Hoy las ventas son un monto total, sin detalle de kilos ni precio unitario."],
    ],
    anchos=[5.6, 10.6],
)

# --- 8. Qué sobra ------------------------------------------------------------
titulo("8. Lo que puede sobrar", 1)
parrafo("Estos módulos existen, pero no sabemos si se usan en una finca real. Su opinión nos ahorra mantener cosas inútiles:")
vinetas([
    "Genealogía (padre y madre de cada animal).",
    "Movilizaciones (traslados entre potreros o fincas).",
    "Inventario de insumos.",
    "Notificaciones internas de la aplicación.",
    "El registro de producción por animal (si en la práctica se mide por tanque).",
])

# --- 9. Preguntas ------------------------------------------------------------
titulo("9. Preguntas para usted", 1)
parrafo("Marque lo que aplique y escriba al lado lo que falte. Con esto armamos el plan.")

def bloque_preguntas(titulo_bloque, preguntas):
    titulo(titulo_bloque, 2)
    for pregunta in preguntas:
        p = doc.add_paragraph()
        p.paragraph_format.space_after = Pt(3)
        run = p.add_run("☐  ")
        run.font.size = Pt(12)
        run2 = p.add_run(pregunta)
        run2.font.size = Pt(10.5)

bloque_preguntas("Animales y hatos", [
    "¿Qué datos de un animal faltan en la ficha?",
    "¿Se maneja por animal o por lote? Si es por lote, ¿cómo se identifica el lote?",
    "¿Qué categorías y tipos usa la finca en la práctica?",
    "¿Se registra el hierro, la marca o el arete del propietario?",
])

bloque_preguntas("Sanidad", [
    "¿Hace falta el tiempo de retiro de leche y carne por tratamiento?",
    "¿Se controla el calendario de vacunación por campaña (aftosa, brucelosis, rabia)?",
    "¿Se registran los exámenes (brucelosis, tuberculosis) y sus resultados?",
    "¿Se controlan los parásitos externos e internos por separado?",
])

bloque_preguntas("Reproducción", [
    "¿Se registra la condición corporal al servicio y al parto?",
    "¿Se lleva control de días abiertos, intervalo entre partos y número de partos?",
    "¿Se registra el parto (fecha, cría, sexo, peso al nacer) o solo la monta?",
    "¿Se descarta por mastitis o por problemas reproductivos?",
])

bloque_preguntas("Potreros y alimentación", [
    "¿Hace falta calcular la carga animal (animales por hectárea)?",
    "¿Se registran los días de ocupación y de descanso de cada potrero?",
    "¿Se controla el suministro de sal, concentrado o ensilaje por lote?",
    "¿Se mide la disponibilidad de pasto o se hace aforo?",
])

bloque_preguntas("Producción y dinero", [
    "¿La leche se mide por animal, por lote o por tanque?",
    "¿Se registra la calidad de la leche (grasa, proteína, células somáticas)?",
    "¿Las ventas necesitan precio por kilo o por litro, además del total?",
    "¿Se quiere ver el costo por animal, por litro y por kilo producido?",
])

bloque_preguntas("Trabajo en el campo", [
    "¿El personal tiene celular con datos en el potrero?",
    "¿Qué se anota hoy en papel y habría que poder registrar desde el celular?",
    "¿Qué reporte le piden los dueños o el veterinario que hoy no exista?",
    "¿Qué módulo de los que vio no usaría nunca?",
])

# --- 10. Cómo mandar los cambios --------------------------------------------
titulo("10. Cómo nos puede mandar los cambios", 1)
parrafo("Con una línea por cambio nos alcanza. Por ejemplo:")
parrafo(
    "Sanidad — agregar tiempo de retiro — «al terminar un tratamiento con antibiótico hay que saber "
    "cuántos días faltan para poder vender la leche». Prioridad: alta.",
    cursiva=True,
)
parrafo("Formato sugerido: módulo · qué cambia · por qué · prioridad (alta, media, baja).")
parrafo(
    "No hace falta que sea técnico: con sus palabras y un ejemplo real de la finca es suficiente.",
        negrita=True,
)

doc.add_paragraph()
parrafo("Gracias por el tiempo. Su lectura crítica vale más que cualquier lista de funciones.", cursiva=True, color=GRIS)

doc.save("docs/MANUAL_REVISION_AGRONOMO.docx")
print("DOCX generado")
