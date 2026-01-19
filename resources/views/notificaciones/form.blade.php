<form action="{{ $action }}" method="POST">
    @csrf
    @if($method === 'PUT')
        @method('PUT')
    @endif

      <div class="mb-3">
        <label for="usuario_id" class="form-label">usuario_id</label>
        <input type="text" name="usuario_id" value="{{Auth::user()->id}}"class="form-control" value="{{ old('mensaje', $notificacion->mensaje ?? '') }}">
    </div>
    <div class="mb-3">
        <label for="mensaje" class="form-label">Mensaje</label>
        <input type="text" name="mensaje" class="form-control" value="{{ old('mensaje', $notificacion->mensaje ?? '') }}">
    </div>

    <div class="md-3">
        <select name="tipo" class="form-select">
            <option value="">-- Seleccione --</option>
            <option value="alerta" {{ request('tipo')=='alerta' ? 'selected' : '' }}>Alerta</option>
            <option value="info" {{ request('tipo')=='info' ? 'selected' : '' }}>Info</option>
            <option value="recordatorio" {{ request('tipo')=='recordatorio' ? 'selected' : '' }}>Recordatorio</option>
        </select>
    </div>

    <div class="mb-3">
        <label for="fecha_envio" class="form-label">Fecha de envío</label>
        <input type="date" name="fecha_envio" class="form-control" value="{{ old('fecha_envio', $notificacion->fecha_envio ?? '') }}">
    </div>

    <div class="mb-3">
        <label for="estado" class="form-label">Estado</label>
        <select name="estado" class="form-select">
            <option value="pendiente" {{ old('estado', $notificacion->estado ?? '') == 'pendiente' ? 'selected' : '' }}>Pendiente</option>
            <option value="leído" {{ old('estado', $notificacion->estado ?? '') == 'leído' ? 'selected' : '' }}>Leído</option>
        </select>
    </div>

    <button type="submit" class="btn btn-success">Guardar</button>
</form>
