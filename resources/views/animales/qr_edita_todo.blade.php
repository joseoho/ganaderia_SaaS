<!DOCTYPE html>
<html>
<head>
    <title>Editar Animal Completo</title>

    <style>
        body { font-family: Arial; margin: 20px; }
        .card { border: 1px solid #ccc; padding: 20px; border-radius: 8px; max-width: 700px; margin: auto; }
        label { font-weight: bold; margin-top: 10px; display: block; }
        input, select, textarea { width: 100%; padding: 8px; margin-top: 5px; }
        button { margin-top: 20px; padding: 10px 20px; }
        h3 { margin-top: 30px; }
    </style>
</head>

<body>

<h2 class="text-center">Editar Datos del Animal</h2>

<div class="card">

    <form action="{{ route('animal.qr.actualizar.todo', $animal->id) }}" method="POST">
        @csrf

        <h3>Datos Generales</h3>

        <label>Código interno</label>
        <input type="text" name="codigo_interno" value="{{ $animal->codigo_interno }}">

        <label>Raza</label>
        <input type="text" name="raza" value="{{ $animal->raza }}">

        <label>Sexo</label>
        <select name="sexo">
            <option value="M" {{ $animal->sexo == 'M' ? 'selected' : '' }}>Macho</option>
            <option value="H" {{ $animal->sexo == 'H' ? 'selected' : '' }}>Hembra</option>
        </select>

        <label>Peso actual (kg)</label>
        <input type="number" name="peso_actual" value="{{ $animal->peso_entrada }}">

        <label>Fecha de nacimiento</label>
        <input type="date" name="fecha_nacimiento" value="{{ $animal->fecha_nacimiento }}">

        <label>Estado</label>
        <select name="estado">
            <option value="activo" {{ $animal->estado == 'activo' ? 'selected' : '' }}>Activo</option>
            <option value="vendido" {{ $animal->estado == 'vendido' ? 'selected' : '' }}>Vendido</option>
            <option value="muerto" {{ $animal->estado == 'muerto' ? 'selected' : '' }}>Muerto</option>
        </select>

        <h3>Datos Productivos</h3>

        <label>Última producción de leche (litros)</label>
        <input type="number" name="ultima_leche" value="{{ optional($animal->produccionLeche->last())->litros }}">

        <label>Último peso ganado (carne)</label>
        <input type="number" name="ultima_ganancia" value="{{ optional($animal->produccionCarne->last())->ganancia_diaria }}">

        <h3>Datos Sanitarios</h3>

        <label>Último tratamiento</label>
        <textarea name="ultimo_tratamiento">{{ optional($animal->tratamientos->last())->motivo }}</textarea>

        <label>Última vacuna</label>
        <input type="text" name="ultima_vacuna" value="{{ optional($animal->registrosVacunas->last())->nombre }}">

        <h3>Datos Reproductivos</h3>

        <label>Padre</label>
        <input type="text" name="padre" value="{{ optional($animal->genealogia)->padre }}">

        <label>Madre</label>
        <input type="text" name="madre" value="{{ optional($animal->genealogia)->madre }}">

        <button type="submit">Guardar Cambios</button>

    </form>

</div>

</body>
</html>