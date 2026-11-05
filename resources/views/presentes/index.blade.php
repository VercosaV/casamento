@extends('layouts.app')

@section('title', 'Lista de presentes')

@section('content')
<div class="container py-5">
    <h1 class="mb-4">Lista de presentes</h1>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $erro)
                    <li>{{ $erro }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="GET" action="{{ route('presentes.index') }}" class="row g-2 align-items-center mb-4">
        <div class="col-md-3">
            <select name="ordem" class="form-select" aria-label="Ordenar por">
                <option value="nome" @selected($ordem === 'nome')>Nome (A-Z)</option>
                <option value="menor-preco" @selected($ordem === 'menor-preco')>Menor preço</option>
                <option value="maior-preco" @selected($ordem === 'maior-preco')>Maior preço</option>
            </select>
        </div>
        <div class="col-md-3">
            <select name="categoria" class="form-select" aria-label="Categoria">
                <option value="">Todas as categorias</option>
                @foreach ($categorias as $cat)
                    <option value="{{ $cat }}" @selected($categoria === $cat)>{{ $cat }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="disponiveis" id="disponiveis"
                       value="1" @checked($disponiveis)>
                <label class="form-check-label" for="disponiveis">Só disponíveis</label>
            </div>
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-dark w-100">Aplicar</button>
        </div>
    </form>

    <div class="row">
        @forelse ($presentes as $presente)
            @php
                $percentual = (int) round($presente->cotas_reservadas / $presente->cotas_total * 100);
            @endphp
            <div class="col-md-4 mb-4">
                <div class="card h-100 {{ $presente->esgotado ? 'bg-light text-muted' : '' }}">
                    @if ($presente->imagem_url)
                        <img src="{{ $presente->imagem_url }}" class="card-img-top"
                             alt="{{ $presente->nome }}" loading="lazy">
                    @endif
                    <div class="card-body d-flex flex-column">
                        @if ($presente->categoria)
                            <span class="badge bg-secondary align-self-start mb-2">{{ $presente->categoria }}</span>
                        @endif
                        <h5 class="card-title">{{ $presente->nome }}</h5>
                        <p class="card-text">{{ $presente->descricao }}</p>
                        <p class="fw-bold mb-1">R$ {{ number_format($presente->valor, 2, ',', '.') }}</p>

                        @if ($presente->cotas_total > 1)
                            <p class="small mb-1">
                                Cota de R$ {{ number_format($presente->valor_cota, 2, ',', '.') }}
                                — {{ $presente->cotas_reservadas }} de {{ $presente->cotas_total }} reservadas
                            </p>
                            <div class="progress mb-3" role="progressbar" aria-label="Cotas reservadas"
                                 aria-valuenow="{{ $percentual }}" aria-valuemin="0" aria-valuemax="100">
                                <div class="progress-bar" style="width: {{ $percentual }}%"></div>
                            </div>
                        @endif

                        @if ($presente->esgotado)
                            <span class="badge bg-secondary align-self-start mt-auto">Já escolhido</span>
                        @else
                            <form method="POST" action="{{ route('presentes.contribuir', $presente) }}" class="mt-auto">
                                @csrf

                                @unless (session('convidado_id'))
                                    <input type="text" name="nome" class="form-control mb-2" placeholder="Seu nome" required>
                                    <input type="email" name="email" class="form-control mb-2" placeholder="Seu e-mail" required>
                                @endunless

                                @if ($presente->cotas_total > 1)
                                    <select name="quantidade_cotas" class="form-select mb-2" aria-label="Quantidade de cotas">
                                        @for ($i = 1; $i <= $presente->cotas_restantes; $i++)
                                            <option value="{{ $i }}">{{ $i }} {{ $i === 1 ? 'cota' : 'cotas' }}</option>
                                        @endfor
                                    </select>
                                @else
                                    <input type="hidden" name="quantidade_cotas" value="1">
                                @endif

                                <textarea name="mensagem" class="form-control mb-2" rows="2" maxlength="400"
                                          placeholder="Mensagem para os noivos (opcional)"></textarea>

                                <button type="submit" class="btn btn-dark btn-sm">
                                    {{ $presente->cotas_total > 1 ? 'Contribuir' : 'Escolher este presente' }}
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <p>Nenhum presente encontrado com esses filtros.</p>
        @endforelse
    </div>
</div>
@endsection