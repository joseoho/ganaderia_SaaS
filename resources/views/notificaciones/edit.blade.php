@extends('layouts.layout')

@section('content')



<h1>Editar Notificación</h1>
@include('notificaciones.form', ['action' => route('notificaciones.update', $notificacion->id), 'method' => 'PUT'])
@endsection
