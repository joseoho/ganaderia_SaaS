

<div class="container">

    <h2 class="mb-4">Crear Potrero</h2>

    <div class="card shadow-sm p-4">


<div class="mb-3">
                <label class="form-label">Nombre del potrero</label>
                <input type="text" name="nombre" class="form-control" value="" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Tamaño (hectáreas)</label>
                <input type="number" step="0.01" name="tamaño" class="form-control" value="" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Tipo de pasto</label>
                <input type="text" name="tipo_pasto" class="form-control" value="">
            </div>

            <div class="mb-3">
                <label class="form-label">Tipo</label>
                <select name="estado" class="form-select" required>
                    <option value="descanso">Descanso</option>
                    <option value="ocupado">Ocupado</option>
                    <option value="recuperacion">Recuperación</option>
                </select>
            </div>


    </div>

</div>


