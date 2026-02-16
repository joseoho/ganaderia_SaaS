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
                    <option value="monta">Monta</option>
                    <option value="inseminación">Inseminación</option>
                    <option value="diagnóstico">Diagnóstico</option>
                    <option value="parto">Parto</option>
                    <option value="aborto">Aborto</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Fecha</label>
                <input type="date" name="fecha" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Toro / Pajilla</label>
                <input type="text" name="toro" class="form-control">
            </div>

            <div class="mb-3">
                <label class="form-label">Resultado</label>
                <select name="resultado" class="form-select">
                    <option value="">Seleccione</option>
                    <option value="preñada">Preñada</option>
                    <option value="vacía">Vacía</option>
                    <option value="exitoso">Exitoso</option>
                    <option value="fallido">Fallido</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Observaciones</label>
                <textarea name="observaciones" class="form-control"></textarea>
            </div>

     

    </div>
</div>
