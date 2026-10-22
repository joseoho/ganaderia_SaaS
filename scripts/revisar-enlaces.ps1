# Revisa enlaces muertos: entra como un usuario, recorre las pantallas
# principales y pide cada enlace local que encuentra, avisando de los 404/500.
#
# Uso:
#   powershell -ExecutionPolicy Bypass -File scripts\revisar-enlaces.ps1
#   powershell -ExecutionPolicy Bypass -File scripts\revisar-enlaces.ps1 -Email usuario@ganaderia.test -Password usuario2026
param(
    [string]$Base = "http://ganaderia_SaaS.test",
    [string]$Email = "admin@ganaderia.test",
    [string]$Password = "password"
)

$ErrorActionPreference = "Continue"

function Login {
    $s = New-Object Microsoft.PowerShell.Commands.WebRequestSession
    $r = Invoke-WebRequest -Uri "$Base/login" -WebSession $s -UseBasicParsing
    $t = ([regex]'name="_token"\s+value="([^"]+)"').Match($r.Content).Groups[1].Value
    try {
        Invoke-WebRequest -Uri "$Base/login" -Method POST -WebSession $s -UseBasicParsing -MaximumRedirection 0 -ErrorAction Stop `
            -Body @{ _token = $t; email = $Email; password = $Password } | Out-Null
    } catch { }
    return $s
}

function Estado($sesion, $ruta) {
    try {
        $r = Invoke-WebRequest -Uri "$Base$ruta" -WebSession $sesion -UseBasicParsing -MaximumRedirection 0 -ErrorAction Stop
        return [int]$r.StatusCode
    } catch {
        $resp = $_.Exception.Response
        if ($resp) { return [int]$resp.StatusCode }
        return -1
    }
}

$sesion = Login

$paginas = @(
    "/dashboard", "/animales", "/animales/create", "/produccion", "/produccion/create",
    "/produccion_leche", "/produccion_leche/create", "/potreros", "/potreros/create",
    "/gastos", "/gastos/create", "/compras", "/compras/create", "/ventas", "/ventas/create",
    "/reproduccion", "/reproduccion/create", "/inventario", "/inventario/create",
    "/tratamientos", "/tratamientos/create", "/registro_vacunas", "/registro_vacunas/create",
    "/genealogia", "/notificaciones", "/reportes", "/importar", "/dashboard-reportes",
    "/animales/etiquetas", "/reporte-general", "/reporte-porfecha", "/usuarios"
)

$enlaces = @{}
$sinRespuesta = @()

foreach ($pagina in $paginas) {
    $codigo = Estado $sesion $pagina
    if ($codigo -ne 200) { $sinRespuesta += ("{0,-28} -> {1}" -f $pagina, $codigo); continue }

    $html = (Invoke-WebRequest -Uri "$Base$pagina" -WebSession $sesion -UseBasicParsing).Content

    foreach ($m in [regex]::Matches($html, 'href="([^"]+)"')) {
        $href = $m.Groups[1].Value

        if ($href -match '^(?i)https?://[^/]+(/.*)$') { $ruta = $Matches[1] }
        elseif ($href -match '^/') { $ruta = $href }
        else { continue }

        if ($ruta -match 'logout|^/login|\.(jpg|jpeg|png|svg|css|js|ico)$') { continue }

        $enlaces[$ruta] = $true
    }
}

if ($sinRespuesta.Count -gt 0) {
    "=== pantallas que no responden 200 ==="
    $sinRespuesta | ForEach-Object { $_ }
    ""
}

"=== enlaces revisados: $($enlaces.Count) ==="

$rotos = @()
foreach ($ruta in ($enlaces.Keys | Sort-Object)) {
    $codigo = Estado $sesion $ruta
    if ($codigo -eq 404 -or $codigo -eq 500 -or $codigo -eq -1) {
        $rotos += ("{0,-52} -> {1}" -f $ruta, $codigo)
    }
}

if ($rotos.Count -eq 0) {
    "No hay enlaces rotos."
} else {
    "=== enlaces que fallan ==="
    $rotos | ForEach-Object { $_ }
}
