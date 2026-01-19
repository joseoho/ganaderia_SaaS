<?php

namespace App\Http\Controllers;

use App\Models\Notificacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StoreNotificacionRequest;
use App\Http\Requests\UpdateNotificacionRequest;

class NotificacionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Notificacion::where('usuario_id', Auth::id()); // 👈 filtro por usuario logueado


        // $query = Notificacion::query(); 
        // 🔍 Filtro por búsqueda en mensaje 
         if ($request->filled('search')) 
         { 
            $query->where('mensaje', 'like', '%' . $request->search . '%'); } 
            // 📅 Filtro por fecha 
            if ($request->filled('fecha')) 
                {
                     $query->whereDate('fecha_envio', $request->fecha); 
                } 
            //  Filtro por tipo 
            if ($request->filled('tipo')) 
            {
               $query->where('tipo', $request->tipo);
            } 
                     // Traer resultados paginados 
                     $notificaciones = $query->orderBy('fecha_envio', 'desc')
                     ->paginate(10); 
                     return view('notificaciones.index', compact('notificaciones'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('notificaciones.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreNotificacionRequest $request)
    {
       $request->validate([ 'usuario_id' => 'required|integer',
        'mensaje' => 'required|string', 
        'tipo' => 'required|string', 
        'fecha_envio' => 'required|date', 
        'estado' => 'required|string', ]);

       Notificacion::create($request->all());
       return redirect()->route('notificaciones.index')->with('success', 'Notificación creada correctamente');
    }

    /**
     * Display the specified resource.
     */
    public function show(Notificacion $notificacion)
    {
        // return view('notificaciones.show', compact('notificacion'));
         // 👇 aseguramos que solo pueda ver sus propias notificaciones
        if ($notificacion->usuario_id !== Auth::id()) {
            abort(403, 'No autorizado');
        }

        return view('notificaciones.show', compact('notificacion'));

    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Notificacion $notificacion)
    {
        
      return view('notificaciones.edit', compact('notificacion'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateNotificacionRequest $request, Notificacion $notificacion)
    {
        $request->validate([ 'mensaje' => 'required|string', 
        'tipo' => 'required|string', 
        'fecha_envio' => 'required|date', 
        'estado' => 'required|string', ]); 
        $notificacion->update($request->all()); 
        return redirect()->route('notificaciones.index')->with('success', 'Notificación actualizada correctamente');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Notificacion $notificacion)
    {
        // $notificacion->delete(); return redirect()->route('notificaciones.index')->with('success', 'Notificación eliminada correctamente');
        if ($notificacion->usuario_id !== Auth::id()) {
            abort(403, 'No autorizado');
        }

        $notificacion->delete();
        return redirect()->route('notificaciones.index')->with('success', 'Notificación eliminada');

    }
}
