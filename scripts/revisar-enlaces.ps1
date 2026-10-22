# Rastrea enlaces locales de la aplicacion para encontrar botones muertos (404).
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
        Invoke-WebRequest -Uri "$Base/login" -Method POST -Body @{ _token = $t; email = $Email; password = $Password } `
            -WebSession $s -UseBasicParsing -MaximumRedirection 0 -ErrorAction Stop | Out-Null
    } catch { }
    return $s
}

function Estado($sesion, $url) {
    try {
        $r = Invoke-WebRequest -Uri $url -WebSession $sesion -UseBasicParsing -MaximumRedirection 0 -ErrorAction Stop
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
    "/animales/etiquetas", "/reporte-general", "/reporte-porfecha"
)

$enlaces = @{}

foreach ($pagina in $paginas) {
    $codigo = Estado $sesion "$Base$pagina"
    if ($codigo -ne 200) { "{0,-28} -> {1}" -f $pagina, $codigo; continue }

    $html = (Invoke-WebRequest -Uri "$Base$pagina" -WebSession $sesion -UseBasicParsing).Content
    foreach ($m in [regex]::Matches($html, 'href="([^"#]+)"')) {
        $href = $m.Groups[1].Value
        if ($href -notmatch "^(/|https?://)") { continue }
        if ($href -match "logout|\.jpg|\.png|\.svg|\.css|\.js|^mailto:|^javascript:") { continue }
        if ($href -match "^(?i)https?://[^/]+(/.*)$") { $soloRuta = $Matches[1] } elseif ($href.StartsWith("/")) { $soloRuta = $href } else { continue }`n        if ($soloRuta -match "logout|\.jpg|\.png|\.svg|\.css|\.js|^mailto:|^javascript:") { continue }`n        $href = $soloRuta
        if ($href -eq "" -or $href -eq "/") { continue }
        if ($href -notmatch "login$") { $enlaces[$href] = $true }
    }
}

"`n=== enlaces encontrados: " + $enlaces.Count + " ==="
$malos = @()
foreach ($enlace in ($enlaces.Keys | Sort-Object)) {
    $codigo = Estado $sesion "$Base$enlace"
    if ($codigo -eq 404 -or $codigo -eq 500 -or $codigo -eq -1) {
        $malos += ("{0,-50} -> {1}" -f $enlace, $codigo)
    }
}

if ($malos.Count -eq 0) { "No hay enlaces roos." } else { "`n=== ENLACES QUE FALLAN ==="; $malos | ForEach-Object { $_ } }
