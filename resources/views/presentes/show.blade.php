<!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nova Categoria</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container py-3">
        <h1>Dados do Presente</h1>
        <form method="post" action='/presentes/{{ $presente->id }}'>
            @CSRF
            @method('DELETE')
            <div class="mb-3">
                <label for="nome" class="form-label">nome do presente</label>
                <input type="text" id="nome" name="nome" class="form-control" disabled value="{{ $presente->nome }}">
            </div>
            <div class="mb-3">
                <label for="descricao" class="form-label">descrição do presente</label>
                <input type="text" id="descricao" name="descricao" class="form-control" disabled value="{{ $presente->descricao }}" >
            </div>
            <a href="/presentes" class="btn btn-secondary">Voltar</a>
            <p>Deseja excluir esse registro ?</p>
            <button type="submit" class="btn btn-danger">Excluir</button>
        </form>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous">
        </script>
    </div>
</body>

</html>
