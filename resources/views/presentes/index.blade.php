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






<!--
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Presentes</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" >
</head>
<body> 
 <div class="container py-3"> 
          @if (session('mensagem'))
            <p>{{ session('mensagem') }}</p>
          @endif
          
          <h2>Presentes</h2>
          <a href="/presentes/create" class="btn btn-success mb-3">Novo Registro</a>
          <table class="table table-hover table-striped">
            <thead>
              <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Descrição</th>
                <th>Ações</th>
              </tr>
            </thead>
            <tbody>
              @foreach($presentes as $p)
                <tr>
                    <td>{{ $p->id }}</td>
                    <td>{{ $p->nome }}</td>
                    <td>{{ $p->descricao }}</td>
                    <td class="d-flex gap-2">
                        <a href="/presentes/{{ $p->id }}/edit" class="btn btn-sm btn-warning">Editar</a>
                        <a href="/presentes/{{ $p->id }}" class="btn btn-sm btn-info">Consultar</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
          </table>
        
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>
</div>
</body>
</html>

-->