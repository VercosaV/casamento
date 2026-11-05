@extends('layouts.admin')

@section('title', 'Novo presente')

@section('content')
<div style="max-width: 680px;">
    <h1 class="mb-4">Novo presente</h1>
    {{-- Antes havia @method('PUT') aqui: a rota de criar é POST, então dava erro 405. --}}
    <form method="POST" action="{{ route('presentes.store') }}" enctype="multipart/form-data" class="card card-body">
        @csrf
        @include('presentes._form')
    </form>
</div>
@endsection
