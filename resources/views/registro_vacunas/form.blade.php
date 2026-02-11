
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
                <label class="form-label">Vacuna</label>
                <select name="nombre" class="form-select" required>
                    <option value="">Seleccione</option>
                    <option value="fiebre_aftosa">Fiebre Aftosa</option>
                    <option value="brucelosis">Brucelosis</option>
                    <option value="rabia_silvestre">Rabia Silvestre</option>
                    <option value="paratuberculosis">Paratuberculosis</option>
                    <option value="leptospirosis">Leptospirosis</option>
                    <option value="clostridiales">Clostridiales</option>    
                    <option value="otra">Otra</option>

                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Lote</label>
                <input type="text" name="lote" class="form-control">
            </div>

            <div class="mb-3">
                <label class="form-label">Proveedor</label>
                <input type="text" name="proveedor" class="form-control">
            </div>

            <div class="mb-3">
                <label class="form-label">Vía de aplicación</label>
                <select name="via" class="form-select">
                    <option value="">Seleccione</option>
                    <option value="subcutánea">Subcutánea</option>
                    <option value="intramuscular">Intramuscular</option>
                    <option value="oral">Oral</option>
                </select>
            </div>

             <div class="mb-3">
                <label class="form-label">Fecha</label>
                <input type="date" name="fecha" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Dosis (ml)</label>
                <input type="number" step="0.01" name="dosis" class="form-control">
            </div>

           <div class="mb-3">
                <label class="form-label">Próxima dosis</label>
                <input type="date" name="proxima_dosis" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Observaciones</label>
                <textarea name="observaciones" class="form-control"></textarea>
            </div>
    </div>
</div>
