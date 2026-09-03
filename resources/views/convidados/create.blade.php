<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Confirmar Presença</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-3">
        <h1>Confirmar Presença</h1>

        @if ($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('convidados.store') }}">
            @csrf
            <div class="mb-3">
                <label for="nome" class="form-label">Seu nome</label>
                <input type="text" id="nome" name="nome" value="{{ old('nome') }}" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="cpf" class="form-label">Seu CPF</label>
                <input type="text" id="cpf" name="cpf" value="{{ old('cpf') }}" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="telefone" class="form-label">Telefone</label>
                <input type="text" id="telefone" name="telefone" value="{{ old('telefone') }}" class="form-control">
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">E-mail</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" class="form-control">
            </div>
            <button type="submit" class="btn btn-primary">Confirmar presença</button>
        </form>
    </div>
</body>
</html>








<!--
<!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Novo Convidado</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container py-3">
        <h1>Novo Convidado</h1>
        <form method="post" action='/convidados'>
            @csrf
            <div class="mb-3">
                <label for="nome" class="form-label">Informe o nome do convidado</label>
                <input type="text" id="nome" name="nome" class="form-control" required="">
            </div>
            <div class="mb-3">
                <label for="descricao" class="form-label">Informe a descrição do convidado</label>
                <input type="text" id="descricao" name="descricao" class="form-control" required="">
            </div>
            <button type="submit" class="btn btn-primary">Enviar</button>
        </form>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous">
        </script>
    </div>
</body>

</html>
-->