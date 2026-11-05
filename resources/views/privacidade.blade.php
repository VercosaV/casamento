@extends('layouts.app')

@section('title', 'Privacidade')

@section('content')
<div class="pagina">
    <div class="mx-auto px-3" style="max-width: 720px;">
        <h1 class="mb-4">Privacidade</h1>
        <p>Este site é do casamento de {{ $secaoInicio->titulo ?? 'nós dois' }} e guarda apenas os dados que você informa: nome, e-mail, telefone, quantidade de pessoas, observações, mensagens e os presentes que escolher.</p>
        <p><strong>Para que usamos:</strong> organizar a lista de convidados, a festa e os agradecimentos. Não vendemos nem compartilhamos esses dados.</p>
        <p><strong>Quem acessa:</strong> apenas os noivos, por área com senha.</p>
        <p><strong>Por quanto tempo:</strong> até pouco depois do casamento. Depois disso, os dados são apagados.</p>
        <p><strong>Seus direitos:</strong> para ver, corrigir ou apagar seus dados, escreva para <a href="mailto:SEU-EMAIL@exemplo.com">SEU-EMAIL@exemplo.com</a>.</p>
    </div>
</div>
@endsection
