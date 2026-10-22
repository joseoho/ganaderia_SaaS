{{--
    Formulario compartido por create y edit.
    Espera: $usuario, $esEdicion (bool), $roles (valor => etiqueta).
--}}
<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label fw-bold">Nombre <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control" required
               value="{{ old('name', $usuario->name) }}">
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label fw-bold">Correo <span class="text-danger">*</span></label>
        <input type="email" name="email" class="form-control" required
               value="{{ old('email', $usuario->email) }}"
               @unless($esEdicion) autocomplete="off" @endunless>
        @if($esEdicion)
            <small class="text-muted">Si cambias el correo, la cuenta tendrá que verificarlo otra vez.</small>
        @endif
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label fw-bold">Rol <span class="text-danger">*</span></label>
        <select name="rol" class="form-select" required>
            @foreach($roles as $valor => $etiqueta)
                <option value="{{ $valor }}"
                    @if(old('rol', $usuario->rol) === $valor) selected @endif>
                    {{ $etiqueta }}
                </option>
            @endforeach
        </select>
        <small class="text-muted">
            <strong>Administrador</strong>: todo, incluido dinero y borrados.
            <strong>Usuario</strong>: animales, producción y sanidad; sin dinero.
        </small>
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label fw-bold">
            Contraseña @unless($esEdicion)<span class="text-danger">*</span>@endunless
        </label>
        <input type="password" name="password" class="form-control" @unless($esEdicion) required @endunless
               autocomplete="new-password">
        <small class="text-muted">
            @if($esEdicion)
                Déjala vacía para no cambiarla. Mínimo 8 caracteres.
            @else
                Mínimo 8 caracteres. Compártela con la persona; podrá cambiarla después.
            @endif
        </small>
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label fw-bold">Repetir contraseña</label>
        <input type="password" name="password_confirmation" class="form-control" autocomplete="new-password">
    </div>

    @unless($esEdicion)
        <div class="col-md-6 mb-3 d-flex align-items-end">
            <div class="form-check">
                <input type="checkbox" name="verificado" value="1" class="form-check-input" id="verificado"
                       @if(old('verificado', true)) checked @endif>
                <label class="form-check-label" for="verificado">
                    Dar el correo por verificado
                </label>
                <div class="small text-muted">
                    Márcalo si respondes por esta persona: entra sin esperar al correo de verificación.
                </div>
            </div>
        </div>
    @endunless
</div>
