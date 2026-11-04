{{-- resources/views/convidados/create.blade.php --}}
@extends('layouts.app')
@section('title', 'Novo convidado')
@section('content')
<div class="container py-5" style="max-width: 640px;">
    <h1 class="mb-4">Novo convidado</h1>
    <form method="POST" action="{{ route('convidados.store') }}">
        @csrf
        @include('convidados._form')
    </form>
</div>
@endsection