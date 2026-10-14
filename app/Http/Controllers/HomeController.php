<?php

namespace App\Http\Controllers;

class HomeController extends Controller
{
    /**
     * Show the application dashboard.
     *
     * El middleware 'auth' y 'tenant' se declara ahora en la ruta
     * (routes/web.php). El estilo antiguo $this->middleware() en el
     * constructor ya no es la forma recomendada en Laravel 11+.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        return view('index');

    }
}
