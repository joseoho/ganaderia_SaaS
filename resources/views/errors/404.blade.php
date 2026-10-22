@php
    // El aviso genérico hablaba de "el registro que buscas" y hacía pensar que
    // el problema era el registro público. Solo en /register tiene sentido
    // decir eso; en cualquier otra página, un 404 es un enlace roto.
    $esRegistro = trim(request()->path(), '/') === 'register';
@endphp

@if ($esRegistro)
    <x-error-page
        code="404"
        title="El registro está cerrado"
        mensaje="Las cuentas las crea el administrador de tu ganadería. Pídele que te dé de alta desde Usuarios."
        detalle="Si ya tienes cuenta, entra por la pantalla de inicio de sesión."
    />
@else
    <x-error-page
        code="404"
        title="Esa página no existe"
        mensaje="La dirección no existe o el enlace está viejo. Vuelve al panel y sigue desde ahí."
        detalle="{{ request()->path() }}"
    />
@endif
