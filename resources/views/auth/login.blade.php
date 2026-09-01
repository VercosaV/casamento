<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Login - Área dos Noivos</title>
</head>
<body>
    <h1>Login</h1>

    @if ($errors->any())
        <div style="color: red">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf
        <label>Email</label>
        <input type="email" name="email" value="{{ old('email') }}" required>

        <label>Senha</label>
        <input type="password" name="password" required>

        <button type="submit">Entrar</button>
    </form>
</body>
</html>