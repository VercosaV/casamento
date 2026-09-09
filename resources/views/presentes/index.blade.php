<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Lista de Presentes</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-3">
    @if (session('mensagem'))
        <div class="alert alert-success">{{ session('mensagem') }}</div>
    @endif

    <h2>Lista de Presentes</h2>

    <div class="row">
        @foreach ($presentes as $presente)
            <div class="col-md-4 mb-3">
                <div class="card {{ $presente->comprado ? 'bg-light text-muted' : '' }}">
                    <div class="card-body">
                        <h5 class="card-title">{{ $presente->nome }}</h5>
                        <p class="card-text">{{ $presente->descricao }}</p>

                        @if ($presente->comprado)
                            <span class="badge bg-secondary">Já escolhido</span>
                        @else
                            <form method="POST" action="{{ route('presentes.reservar', $presente->id) }}">
                                @csrf

                                @unless (session('convidado_id'))
                                    <input type="text" name="nome" class="form-control mb-2" placeholder="Seu nome" required>
                                    <input type="text" name="cpf" class="form-control mb-2" placeholder="Seu CPF" required>
                                    <input type="email" name="email" class="form-control mb-2" placeholder="Seu e-mail (opcional)">
                                @endunless

                                <button type="submit" class="btn btn-primary btn-sm">Escolher este presente</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
</body>
</html>
