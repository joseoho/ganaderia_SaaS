
            <div class="mb-3">
                <label class="form-label">Animal (Hijo)</label>
                <select name="animal_id" class="form-select" required>
                    <option value="">Seleccione el animal</option>
                    @foreach($animales as $a)
                        <option value="{{ $a->id }}">{{ $a->codigo_interno }} — {{ $a->raza }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Padre</label>
                <select name="padre_id" class="form-select">
                    <option value="">No registrado</option>
                    @foreach($animales as $a)
                        <option value="{{ $a->id }}">{{ $a->codigo_interno }} — {{ $a->raza }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Madre</label>
                <select name="madre_id" class="form-select">
                    <option value="">No registrado</option>
                    @foreach($animales as $a)
                        <option value="{{ $a->id }}">{{ $a->codigo_interno }} — {{ $a->raza }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Observaciones</label>
                <textarea name="observaciones" class="form-control" rows="3"></textarea>
            </div>
    </div>
</div>
