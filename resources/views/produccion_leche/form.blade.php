
            <div class="mb-3">
                <label class="form-label">Animal</label>
                <select name="animal_id" class="form-select" required>
                    <option value="">Seleccione</option>
                    @foreach($animales as $a)
                        <option value="{{ $a->id }}">{{ $a->codigo_interno }} — {{ $a->raza }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Fecha</label>
                <input type="date" name="fecha" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Turno</label>
                <select name="turno" class="form-select">
                    <option value="mañana">Mañana</option>
                    <option value="tarde">Tarde</option>
                    <option value="noche">Noche</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Litros</label>
                <input type="number" step="0.01" name="litros" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Observaciones</label>
                <textarea name="observaciones" class="form-control"></textarea>
            </div>

          

    </div>
</div>
