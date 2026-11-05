{{-- resources/views/convidados/create.blade.php --}}
@extends('layouts.admin')
@section('title', 'Novo convidado')
@section('content')
<div class="py-2" style="max-width: 640px;">
    <h1 class="mb-4">Novo convidado</h1>
    <form method="POST" action="{{ route('convidados.store') }}">
        @csrf
        @include('convidados._form')
    </form>
</div>
@endsection