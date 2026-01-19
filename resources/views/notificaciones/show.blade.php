@extends('layouts.layout')

@section('content')


<h1>Detalle de Notificación</h1>
<p><strong>Mensaje:</strong> {{ $notificacion->mensaje }}</p>
<p><strong>Tipo:</strong> {{ $notificacion->tipo }}</p>
<p><strong>Fecha:</strong> {{ $notificacion->fecha_envio }}</p>
<p><strong>Estado:</strong> {{ $notificacion->estado }}</p>
<a href="{{ route('notificaciones.index') }}" class="btn btn-secondary">Volver</a>
@endsection
