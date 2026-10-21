{{--
    Selects del paso de mapeo: uno por campo del sistema.

    Se genera desde $campos (campo => etiqueta) para que el nombre que viaja en
    el formulario (map[campo]) sea SIEMPRE el que el controlador espera. Antes
    cada pantalla escribia sus selects a mano y compras y ventas mandaban
    `map[monto]` mientras el controlador pedia `monto_total`: el mapeo no
    cuadraba nunca y el informe decia "falta asignar monto_total" con la columna
    delante.

    Espera: $campos, $obligatorios, $headers, $sugerencias.
--}}
<div class="row">
    @foreach($campos as $campo => $etiqueta)
        @php($esObligatorio = in_array($campo, $obligatorios ?? [], true))

        <div class="col-md-6 mb-3">
            <label class="form-label fw-bold">
                {{ $etiqueta }} @if($esObligatorio)<span class="text-danger">*</span>@endif
            </label>

            <select name="map[{{ $campo }}]" class="form-select" @if($esObligatorio) required @endif>
                <option value="">-- No usar --</option>
                @foreach($headers as $h)
                    <option value="{{ $h }}"
                        @if(isset($sugerencias[$campo]) && $sugerencias[$campo] == $h) selected @endif>
                        {{ $h }}
                    </option>
                @endforeach
            </select>

            @if(isset($sugerencias[$campo]))
                <small class="text-success">
                    <i class="fas fa-magic me-1"></i> Sugerencia: {{ $sugerencias[$campo] }}
                </small>
            @endif
        </div>
    @endforeach
</div>
