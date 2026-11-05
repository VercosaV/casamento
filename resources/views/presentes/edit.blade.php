@extends('layouts.admin')

@section('title', 'Editar presente')

@section('content')
<div style="max-width: 680px;">
    <h1 class="mb-4">Editar presente</h1>
    <form method="POST" action="{{ route('presentes.update', $presente) }}" enctype="multipart/form-data" class="card card-body">
        @csrf
        @method('PUT')
        @include('presentes._form')
    </form>
</div>
@endsection
