
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
                <label class="form-label">Tipo</label>
                <select name="tipo" class="form-select" required>
                    <option value="entrada">Entrada</option>
                    <option value="salida">Salida</option>
                    <option value="interna">Interna</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Origen</label>
                <input type="text" name="origen" class="form-control">
            </div>

            <div class="mb-3">
                <label class="form-label">Destino</label>
                <input type="text" name="destino" class="form-control">
            </div>

            <div class="mb-3">
                <label class="form-label">Fecha</label>
                <input type="date" name="fecha" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Descripción</label>
                <textarea name="descripcion" class="form-control"></textarea>
            </div>
  </div>
</div>
