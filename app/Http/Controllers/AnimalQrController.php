<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\Genealogia;
use App\Models\ProduccionCarne;
use App\Models\ProduccionLeche;
use App\Models\RegistroVacuna;
use App\Models\Reproduccion;
use App\Models\Tratamiento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AnimalQrController extends Controller
{
    public function show($id)
    {
        $animal = Animal::findOrFail($id);

        $produccion_leche = ProduccionLeche::where('animal_id', $id)->get();
        $produccion_carne = ProduccionCarne::where('animal_id', $id)->get();
        $tratamientos = Tratamiento::where('animal_id', $id)->get();
        $vacunas = RegistroVacuna::where('animal_id', $id)->get();
        $reproduccion = Reproduccion::where('animal_id', $id)->get();
        $genealogia = Genealogia::where('animal_id', $id)->first();

        return view('animales.qr_show', compact(
            'animal',
            'produccion_leche',
            'produccion_carne',
            'tratamientos',
            'vacunas',
            'reproduccion',
            'genealogia'
        ));
    }

    public function editarTodo($id)
    {
        $animal = Animal::with([
            'produccionLeche',
            'produccionCarne',
            'tratamientos',
            'registrosVacunas',
            'reproducciones',
            'genealogia',
        ])->findOrFail($id);

        // Seguridad multi-tenant
        if (Auth::check() && Auth::user()->inquilino_id !== $animal->inquilino_id) {
            abort(403);
        }

        return view('animales.qr_edita_todo', compact('animal'));
    }

    public function actualizarTodo(Request $request, $id)
    {
        $animal = Animal::with([
            'produccionLeche',
            'produccionCarne',
            'tratamientos',
            'registrosVacunas',
            'reproducciones',
            'genealogia',
        ])->findOrFail($id);

        if (Auth::check() && Auth::user()->inquilino_id !== $animal->inquilino_id) {
            abort(403);
        }

        // Antes no habia NINGUNA validacion: un campo vacio llegaba a una
        // columna NOT NULL y la peticion terminaba en un error 500.
        $datos = $request->validate([
            'codigo_interno' => ['required', 'string', 'max:50',
                Rule::unique('animales', 'codigo_interno')
                    ->where('inquilino_id', $animal->inquilino_id)
                    ->ignore($animal->id)],
            'raza' => 'required|string|max:50',
            'sexo' => 'required|in:M,H',
            'peso_actual' => 'nullable|numeric|min:0',
            'fecha_nacimiento' => 'required|date',
            'estado' => 'required|string|max:50',
            'ultima_leche' => 'nullable|numeric|min:0',
            'ultima_ganancia' => 'nullable|numeric',
            'ultimo_tratamiento' => 'nullable|string|max:50',
            'ultimo_medicamento' => 'nullable|string|max:250',
            'ultima_vacuna' => 'nullable|string|max:100',
            'padre_id' => 'nullable|integer',
            'madre_id' => 'nullable|integer',
        ]);

        // 1) Actualizar datos del animal
        $animal->update([
            'codigo_interno' => $datos['codigo_interno'],
            'raza' => $datos['raza'],
            'sexo' => $datos['sexo'],
            'peso_entrada' => $datos['peso_actual'] ?? $animal->peso_entrada,
            'fecha_nacimiento' => $datos['fecha_nacimiento'],
            'estado' => $datos['estado'],
        ]);

        // 2) Genealogía (padre / madre)
        //
        // Las columnas son `padre_id` y `madre_id`. Antes se hacia
        // `$genealogia->padre = ...`, que es el nombre de la RELACION: Eloquent
        // lo guardaba en memoria y save() no persistia absolutamente nada, asi
        // que elegir el padre no surtia efecto y no avisaba de nada.
        if (! empty($datos['padre_id']) || ! empty($datos['madre_id'])) {
            $genealogia = $animal->genealogia ?: new Genealogia(['animal_id' => $animal->id]);

            if (! empty($datos['padre_id'])) {
                $genealogia->padre_id = $datos['padre_id'];
            }

            if (! empty($datos['madre_id'])) {
                $genealogia->madre_id = $datos['madre_id'];
            }

            $genealogia->save();
        }

        // 3) Última producción de leche
        if ($request->filled('ultima_leche')) {
            $produccionLeche = $animal->produccionLeche->last() ?: new ProduccionLeche([
                'animal_id' => $animal->id,
                'fecha' => now()->toDateString(),
                'inquilino_id' => $animal->inquilino_id,
            ]);

            $produccionLeche->litros = $datos['ultima_leche'];
            $produccionLeche->save();
        }

        // 4) Última producción de carne. `peso` es NOT NULL: al crear el
        //    registro habia que darle valor o la peticion daba error 500.
        if ($request->filled('ultima_ganancia')) {
            $produccionCarne = $animal->produccionCarne->last() ?: new ProduccionCarne([
                'animal_id' => $animal->id,
                'fecha' => now()->toDateString(),
                'peso' => $datos['peso_actual'] ?? $animal->peso_entrada,
                'inquilino_id' => $animal->inquilino_id,
            ]);

            $produccionCarne->ganancia_diaria = $datos['ultima_ganancia'];
            $produccionCarne->save();
        }

        // 5) Último tratamiento. La columna de fecha es `fecha`, no
        //    `fecha_inicio`, y `medicamento` es NOT NULL.
        if ($request->filled('ultimo_tratamiento')) {
            $tratamiento = $animal->tratamientos->last() ?: new Tratamiento([
                'animal_id' => $animal->id,
                'fecha' => now()->toDateString(),
                'medicamento' => $datos['ultimo_medicamento'] ?? 'Sin especificar',
                'inquilino_id' => $animal->inquilino_id,
            ]);

            $tratamiento->motivo = $datos['ultimo_tratamiento'];

            if (! empty($datos['ultimo_medicamento'])) {
                $tratamiento->medicamento = $datos['ultimo_medicamento'];
            }

            $tratamiento->save();
        }

        // 6) Última vacuna. La columna de fecha es `fecha_aplicacion`, no `fecha`.
        if ($request->filled('ultima_vacuna')) {
            $vacuna = $animal->registrosVacunas->last() ?: new RegistroVacuna([
                'animal_id' => $animal->id,
                'fecha_aplicacion' => now()->toDateString(),
                'inquilino_id' => $animal->inquilino_id,
            ]);

            $vacuna->nombre = $datos['ultima_vacuna'];
            $vacuna->save();
        }

        return redirect()
            ->route('animal.qr.show', $animal->id)
            ->with('success', 'Datos del animal actualizados correctamente');
    }
}
