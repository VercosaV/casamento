{{-- resources/views/convidados/edit.blade.php --}}
@extends('layouts.app')
@section('title', 'Editar convidado')
@section('content')
<div class="container py-5" style="max-width: 640px;">
    <h1 class="mb-4">Editar convidado</h1>
    <form method="POST" action="{{ route('convidados.update', $convidado->id) }}">
        @csrf
        @method('PUT')
        @include('convidados._form')
    </form>
</div>
@endsection