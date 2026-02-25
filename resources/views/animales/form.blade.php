<div class="row g-3">
    <div class="col-md-6">
        <label for="codigo_interno" class="form-label font-weight-bold">Código Interno</label>
        <input type="text" name="codigo_interno" id="codigo_interno" 
               class="form-control @error('codigo_interno') is-invalid @enderror" 
               value="{{ old('codigo_interno', $animal->codigo_interno ?? '') }}" required>
        @error('codigo_interno') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label for="categoria" class="form-label">Categoría</label>
        <select name="categoria" id="categoria" class="form-select @error('categoria') is-invalid @enderror" required>
            <option value="">Seleccione...</option>
            @foreach(['bovino' => 'Bovino', 'ovino' => 'Ovino', 'caprino' => 'Caprino'] as $val => $label)
                <option value="{{ $val }}" {{ old('categoria', $animal->categoria ?? '') == $val ? 'selected' : '' }}>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        @error('categoria') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label for="raza" class="form-label">Raza</label>
        <select name="raza" id="raza" class="form-select @error('raza') is-invalid @enderror" required>
            <option value="">Seleccione...</option>
            <option value="Brahman" {{ old('raza', $animal->raza ?? '') == 'Brahman' ? 'selected' : '' }}>Brahman</option>
            <option value="Holstein" {{ old('raza', $animal->raza ?? '') == 'Holstein' ? 'selected' : '' }}>Holstein</option>
            <option value="Angus" {{ old('raza', $animal->raza ?? '') == 'Angus' ? 'selected' : '' }}>Angus</option>
            <option value="Simmental" {{ old('raza', $animal->raza ?? '') == 'Simmental' ? 'selected' : '' }}>Simmental</option>
            <option value="F1" {{ old('raza', $animal->raza ?? '') == 'F1' ? 'selected' : '' }}>F1</option>
            <option value="Hereford" {{ old('raza', $animal->raza ?? '') == 'Hereford' ? 'selected' : '' }}>Hereford</option>
            <option value="Charolais" {{ old('raza', $animal->raza ?? '') == 'Charolais' ? 'selected' : '' }}>Charolais</option>
            <option value="Limousin" {{ old('raza', $animal->raza ?? '') == 'Limousin' ? 'selected' : '' }}>Limousin</option>
            <option value="Gyr" {{ old('raza', $animal->raza ?? '') == 'Gyr' ? 'selected' : '' }}>Gyr</option>
            <option value="Nelore" {{ old('raza', $animal->raza ?? '') == 'Nelore' ? 'selected' : '' }}>Nelore</option>
            <option value="Cebú" {{ old('raza', $animal->raza ?? '') == 'Cebú' ? 'selected' : '' }}>Cebú</option>
            <option value="Guernsey" {{ old('raza', $animal->raza ?? '') == 'Guernsey' ? 'selected' : '' }}>Guernsey</option>
            <option value="Otras" {{ old('raza', $animal->raza ?? '') == 'Otras' ? 'selected' : '' }}>Otras</option>
        </select>
    </div>

    <div class="col-md-6">
        <label for="tipo" class="form-label">Tipo</label>
        <select name="tipo" id="tipo" class="form-select @error('tipo') is-invalid @enderror" required>
            <option value="">Seleccione...</option>
            <option value="Madre" {{ old('tipo', $animal->tipo ?? '') == 'Madre' ? 'selected' : '' }}>Madre</option>
            <option value="Padre" {{ old('tipo', $animal->tipo ?? '') == 'Padre' ? 'selected' : '' }}>Padre</option>
            <option value="Becerro" {{ old('tipo', $animal->tipo ?? '') == 'Becerro' ? 'selected' : '' }}>Becerro</option>
            <option value="Becerra" {{ old('tipo', $animal->tipo ?? '') == 'Becerra' ? 'selected' : '' }}>Becerra</option>
            <option value="Maute" {{ old('tipo', $animal->tipo ?? '') == 'Maute' ? 'selected' : '' }}>Maute</option>
            <option value="Novilla" {{ old('tipo', $animal->tipo ?? '') == 'Novilla' ? 'selected' : '' }}>Novilla</option>
            <option value="Otras" {{ old('tipo', $animal->tipo ?? '') == 'Otras' ? 'selected' : '' }}>Otras</option>
        </select>
    </div>

    <div class="col-md-6">
        <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento</label>
        <input type="date" name="fecha_nacimiento" id="fecha_nacimiento" 
               class="form-control @error('fecha_nacimiento') is-invalid @enderror" 
               value="{{ old('fecha_nacimiento', $animal->fecha_nacimiento ?? '') }}" required>
    </div>

    <div class="col-md-6">
        <label for="sexo" class="form-label">Sexo</label>
        <select name="sexo" id="sexo" class="form-select @error('sexo') is-invalid @enderror" required>
            <option value="">Seleccione...</option>
            <option value="M" {{ old('sexo', $animal->sexo ?? '') == 'M' ? 'selected' : '' }}>Macho</option>
            <option value="H" {{ old('sexo', $animal->sexo ?? '') == 'H' ? 'selected' : '' }}>Hembra</option>
        </select>
    </div>

    <div class="col-md-6">
        <label for="peso_actual" class="form-label">Peso Actual (kg)</label>
        <input type="number" step="0.01" name="peso_actual" id="peso_actual" 
               class="form-control @error('peso_actual') is-invalid @enderror" 
               value="{{ old('peso_actual', $animal->peso_entrada ?? '') }}">
    </div>

    <div class="col-md-12">
        <label for="estado" class="form-label">Estado</label>
        <select name="estado" id="estado" class="form-select @error('estado') is-invalid @enderror" required>
            <option value="activo" {{ old('estado', $animal->estado ?? '') == 'activo' ? 'selected' : '' }}>Activo</option>
            <option value="vendido" {{ old('estado', $animal->estado ?? '') == 'vendido' ? 'selected' : '' }}>Vendido</option>
            <option value="muerto" {{ old('estado', $animal->estado ?? '') == 'muerto' ? 'selected' : '' }}>Muerto</option>
        </select>
    </div>
</div>