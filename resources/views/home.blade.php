@extends('layouts.app')

@section('title', $secoes->firstWhere('tipo', 'inicio')?->titulo ?? 'Convite de casamento')

@section('content')
    {{-- A ordem e a visibilidade vêm do painel. Cada tipo tem o seu desenho. --}}
    @foreach ($secoes as $secao)
        @include('home._'.$secao->tipo, ['secao' => $secao, 'alt' => $loop->index % 2 === 0])
    @endforeach
@endsection

@push('scripts')
    <script src="{{ asset('js/contagem.js') }}" defer></script>
@endpush
