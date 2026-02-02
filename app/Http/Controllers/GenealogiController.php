<?php

namespace App\Http\Controllers;

use App\Models\Genealogia;
use App\Models\Animal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StoreGenealogiaRequest;
use App\Http\Requests\UpdateGenealogiaRequest;

class GenealogiController extends Controller
{
    public function index(Request $request)
    {
        $query = Genealogia::where('inquilino_id', Auth::user()->inquilino_id);

        if ($request->filled('search')) {
            $query->whereHas('animal', function($q) use ($request) {
                $q->where('codigo_interno', 'like', '%' . $request->search . '%');
            });
        }

        $genealogias = $query->orderBy('id', 'desc')->paginate(10);

        return view('genealogia.index', compact('genealogias'));
    }

    public function create()
    {
        $inquilino = Auth::user()->inquilino_id;

        $animales = Animal::where('inquilino_id', $inquilino)->get();

        return view('genealogia.create', compact('animales'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'animal_id' => 'required',
        ]);

        Genealogia::create([
            'inquilino_id' => Auth::user()->inquilino_id,
            'animal_id' => $request->animal_id,
            'padre_id' => $request->padre_id,
            'madre_id' => $request->madre_id,
            'observaciones' => $request->observaciones,
        ]);

        return redirect()->route('genealogia.index')->with('success', 'Genealogía registrada');
    }

    public function show(Genealogia $genealogium)
    {
        if ($genealogium->inquilino_id !== Auth::user()->inquilino_id) {
            abort(403);
        }

        return view('genealogia.show', compact('genealogium'));
    }

    public function edit(Genealogia $genealogium)
    {
        if ($genealogium->inquilino_id !== Auth::user()->inquilino_id) {
            abort(403);
        }

        $animales = Animal::where('inquilino_id', Auth::user()->inquilino_id)->get();

        return view('genealogia.edit', compact('genealogium', 'animales'));
    }

    public function update(Request $request, Genealogia $genealogium)
    {
        if ($genealogium->inquilino_id !== Auth::user()->inquilino_id) {
            abort(403);
        }

        $genealogium->update($request->all());

        return redirect()->route('genealogia.index')->with('success', 'Genealogía actualizada');
    }

    public function destroy(Genealogia $genealogium)
    {
        if ($genealogium->inquilino_id !== Auth::user()->inquilino_id) {
            abort(403);
        }

        $genealogium->delete();

        return redirect()->route('genealogia.index')->with('success', 'Genealogía eliminada');
    }
}