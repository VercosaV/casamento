<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Categorias</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" >
</head>
<body> 
 <div class="container py-3"> 
          @if (session('mensagem'))
            <p>{{ session('mensagem') }}</p>
          @endif
          
          <h2>Categorias</h2>
          <a href="/convidados/create" class="btn btn-success mb-3">Novo Registro</a>
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
              @foreach($convidados as $convidado)
                <tr>
                    <td>{{ $convidado->id }}</td>
                    <td>{{ $convidado->nome }}</td>
                    <td>{{ $convidado->descricao }}</td>
                    <td class="d-flex gap-2">
                        <a href="/convidados/{{ $convidado->id }}/edit" class="btn btn-sm btn-warning">Editar</a>
                        <a href="/convidados/{{ $convidado->id }}" class="btn btn-sm btn-info">Consultar</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
          </table>
        
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>
</div>
</body>
</html>