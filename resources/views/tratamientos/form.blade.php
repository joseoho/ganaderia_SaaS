<div class="row g-3">
            <div class="row">
                <div class="col-md-12 mb-3">
                    <label class="form-label font-weight-bold-label">Animal</label>
                    <select name="animal_id" class="form-select" required>
                        <option value="">Seleccione un animal</option>
                        @foreach($animales as $animal)
                            <option value="{{ $animal->id }}">
                                {{ $animal->codigo_interno }} — {{ $animal->nombre }} ({{ $animal->raza }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label font-weight-bold-label">Motivo del Tratamiento</label>
                    <input type="text" name="motivo" class="form-control" 
                           placeholder="Ej: Infección respiratoria, desparasitación, etc." required>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label font-weight-bold-label">Medicamento</label>
                    <input type="text" name="medicamento" class="form-control" 
                           placeholder="Nombre del medicamento" required>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label font-weight-bold-label">Vía de Administración</label>
                    <select name="via" class="form-select">
                        <option value="">Seleccione una vía</option>
                        <option value="oral">Oral</option>
                        <option value="intramuscular">Intramuscular</option>
                        <option value="subcutánea">Subcutánea</option>
                        <option value="intravenosa">Intravenosa</option>
                        <option value="tópica">Tópica</option>
                    </select>
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label font-weight-bold-label">Dosis</label>
                    <input type="text" name="dosis" class="form-control" 
                           placeholder="Ej: 5.5ml, 1 tableta, etc.">
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label font-weight-bold-label">Fecha de Inicio</label>
                    <input type="date" name="fecha_inicio" class="form-control" 
                           value="{{ date('Y-m-d') }}" required>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label font-weight-bold-label">Fecha de Fin</label>
                    <input type="date" name="fecha_fin" class="form-control">
                    <small class="text-muted">Dejar en blanco si es una dosis única o aún no finaliza</small>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label font-weight-bold-label">Observaciones</label>
                    <textarea name="observaciones" class="form-control" rows="3" 
                              placeholder="Notas adicionales sobre el tratamiento..."></textarea>
                </div>
            </div>

            
        
    </div>
