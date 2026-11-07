@extends('layouts.app')

@section('title', 'Lista de presentes')

@section('content')
<div class="pagina">
    <div class="container">
        <h1 class="mb-2">Lista de presentes</h1>
        <p class="text-center text-muted mb-2">Escolha um ou mais presentes e junte tudo em <strong>Minha lista</strong>.</p>
        <p class="text-center small text-muted mb-4">Depois você decide como enviar: comprando o presente, pagando online ou mandando um Pix.</p>
        @if (\App\Support\Carrinho::quantidade() > 0)
            <p class="text-center mb-4">
                <a href="{{ route('carrinho.mostrar') }}" class="btn btn-ouro">Ver minha lista ({{ \App\Support\Carrinho::quantidade() }}) e finalizar</a>
            </p>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger" role="alert">
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
                <button type="submit" class="btn btn-contorno w-100">Aplicar</button>
            </div>
        </form>

        <div class="row">
            @forelse ($presentes as $presente)
                @php
                    $percentual = (int) round($presente->cotas_reservadas / $presente->cotas_total * 100);
                @endphp
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card presente-card h-100 {{ $presente->esgotado ? 'esgotado' : '' }}">
                        @if ($presente->imagem_url)
                            <img src="{{ $presente->imagem_url }}" class="presente-foto"
                                alt="{{ $presente->nome }}" loading="lazy">
                        @endif
                        <div class="card-body d-flex flex-column">
                            @if ($presente->categoria)
                                <span class="badge badge-categoria align-self-start mb-2">{{ $presente->categoria }}</span>
                            @endif
                            <h2 class="h4 card-title">{{ $presente->nome }}</h2>
                            @if ($presente->descricao)
                                <p class="card-text text-muted">{{ $presente->descricao }}</p>
                            @endif
                            <p class="preco mb-1">R$ {{ number_format($presente->valor, 2, ',', '.') }}</p>

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
                                <form method="POST" action="{{ route('carrinho.adicionar', $presente) }}" class="mt-auto">
                                    @csrf

                                    @if ($presente->cotas_total > 1)
                                        <select name="quantidade_cotas" class="form-select mb-2" aria-label="Quantidade de cotas">
                                            @for ($i = 1; $i <= $presente->cotas_restantes; $i++)
                                                <option value="{{ $i }}" @selected((int) (\App\Support\Carrinho::itens()[$presente->id] ?? 1) === $i)>{{ $i }} {{ $i === 1 ? 'cota' : 'cotas' }}</option>
                                            @endfor
                                        </select>
                                    @else
                                        <input type="hidden" name="quantidade_cotas" value="1">
                                    @endif

                                    @if (isset(\App\Support\Carrinho::itens()[$presente->id]))
                                        <button type="submit" class="btn btn-contorno btn-sm w-100">Na sua lista · atualizar</button>
                                    @else
                                        <button type="submit" class="btn btn-ouro btn-sm w-100">Adicionar à minha lista</button>
                                    @endif

                                    @if ($presente->link)
                                        <a href="{{ $presente->link }}" target="_blank" rel="noopener noreferrer"
                                            class="d-block text-center small mt-2">Ver na loja</a>
                                    @endif
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <p class="text-center text-muted">Nenhum presente encontrado com esses filtros.</p>
            @endforelse
        </div>

        {{ $presentes->links() }}
    </div>
</div>
@endsection
