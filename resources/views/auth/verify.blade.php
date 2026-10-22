{{--
    Aviso de verificación de correo.

    Se hace SIN el layout del panel a propósito: quien llega aquí todavía no ha
    verificado su correo y no debe ver el menú de la aplicación. Además así la
    pantalla no depende de los datos del tenant (carga aunque eso falle).

    Variables: $usuario (auth()->user()).
--}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verifica tu correo · {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<main class="container" style="max-width: 38rem">
    <div class="card shadow-sm mt-5">
        <div class="card-body p-4">

            <h1 class="h4 mb-3">Verifica tu correo</h1>

            @php($usuario = auth()->user())

    <p class="text-muted">
                Antes de entrar, confirma tu dirección de correo. Te enviamos un enlace a
                <strong>{{ $usuario?->email }}</strong>.
            </p>

            @if (session('resent'))
                <div class="alert alert-success" role="alert">
                    Te enviamos otro enlace. Revisa tu correo (y la carpeta de spam).
                </div>
            @endif

            <div class="alert alert-info" role="alert">
                <strong>¿No te llega?</strong> Los servidores de correo a veces tardan un par de
                minutos. Si sigue sin aparecer, pulsa el botón y te lo reenviamos.
            </div>

            <form method="POST" action="{{ route('verification.resend') }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-primary">Reenviar el enlace</button>
            </form>

            <form method="POST" action="{{ url('/logout') }}" class="d-inline ms-2">
                @csrf
                <button type="submit" class="btn btn-outline-secondary">Cerrar sesión</button>
            </form>

        </div>
    </div>
</main>
</body>
</html>
