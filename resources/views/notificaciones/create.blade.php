@extends('layouts.layout')

@section('content')


<h1>Crear Notificación</h1>
@include('notificaciones.form', ['action' => route('notificaciones.store'), 'method' => 'POST'])
@endsection
