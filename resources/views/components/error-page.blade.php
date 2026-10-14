{{--
    Plantilla de pagina de error.

    A proposito NO usa @vite ni consulta la base de datos: una pagina de error
    debe poder renderizarse aunque el build de assets o la base fallen, que es
    justo cuando mas falta hace que se vea algo decente.

    Props: code (codigo HTTP), title, mensaje (opcional), detalle (opcional).
--}}
@props(['code', 'title', 'mensaje' => null, 'detalle' => null])

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $code }} · {{ $title }}</title>
    <style>
        :root { color-scheme: light dark; }
        * { box-sizing: border-box; }
        body {
            margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center;
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            background: #f4f6f5; color: #1f2d28; padding: 1.5rem;
        }
        .tarjeta {
            max-width: 34rem; width: 100%; background: #fff; border-radius: .75rem;
            padding: 2.5rem 2rem; text-align: center; border: 1px solid #e2e8e5;
            box-shadow: 0 1px 3px rgba(0,0,0,.06);
        }
        .codigo { font-size: 3.5rem; font-weight: 700; line-height: 1; margin: 0; color: #2f6f52; }
        h1 { font-size: 1.25rem; margin: 1rem 0 .5rem; }
        p { margin: 0 0 1.5rem; color: #55635d; line-height: 1.6; }
        .detalle { font-size: .85rem; color: #8a938f; word-break: break-word; }
        a {
            display: inline-block; padding: .6rem 1.25rem; border-radius: .4rem;
            background: #2f6f52; color: #fff; text-decoration: none; font-weight: 600;
        }
        a:hover { background: #275c44; }
        @media (prefers-color-scheme: dark) {
            body { background: #141a17; color: #e8eeea; }
            .tarjeta { background: #1c2420; border-color: #2c3733; }
            p { color: #a9b5b0; }
            .detalle { color: #7d8a85; }
        }
    </style>
</head>
<body>
    <main class="tarjeta">
        <p class="codigo">{{ $code }}</p>
        <h1>{{ $title }}</h1>

        @if ($mensaje)
            <p>{{ $mensaje }}</p>
        @endif

        @if ($detalle)
            <p class="detalle">{{ $detalle }}</p>
        @endif

        <a href="{{ url('/') }}">Volver al inicio</a>
    </main>
</body>
</html>
