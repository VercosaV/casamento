@extends('layouts.app')

@section('title', 'Confirme sua presença')

@section('content')
<div class="mx-auto px-3 py-5" style="max-width: 640px;">
    <h1 class="mb-4">Confirme sua presença</h1>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $erro)
                    <li>{{ $erro }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('rsvp.store') }}" id="form-rsvp">
        @csrf

        <div class="mb-3">
            <label for="nome" class="form-label">Nome completo</label>
            <input type="text" id="nome" name="nome" class="form-control" required value="{{ old('nome') }}">
        </div>

        <div class="mb-3">
            <span class="form-label d-block">Você irá ao evento?</span>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="confirma_presenca" id="vai-sim" value="1"
                       @checked(old('confirma_presenca', '1') === '1')>
                <label class="form-check-label" for="vai-sim">Sim</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="confirma_presenca" id="vai-nao" value="0"
                       @checked(old('confirma_presenca', '1') === '0')>
                <label class="form-check-label" for="vai-nao">Não</label>
            </div>
        </div>

        <div id="bloco-quantidade" class="row">
            <div class="col-6 mb-3">
                <label for="adultos" class="form-label">Adultos (incluindo você)</label>
                <input type="number" id="adultos" name="adultos" class="form-control" min="1" max="10"
                       value="{{ old('adultos', 1) }}">
            </div>
            <div class="col-6 mb-3">
                <label for="criancas" class="form-label">Crianças</label>
                <input type="number" id="criancas" name="criancas" class="form-control" min="0" max="10"
                       value="{{ old('criancas', 0) }}">
            </div>
        </div>

        <div class="mb-3">
            <label for="email" class="form-label">E-mail</label>
            <input type="email" id="email" name="email" class="form-control" required value="{{ old('email') }}">
        </div>

        <div class="mb-3">
            <label for="telefone" class="form-label">Telefone (opcional)</label>
            <input type="text" id="telefone" name="telefone" class="form-control" value="{{ old('telefone') }}">
        </div>

        <div class="mb-3">
            <label for="observacoes" class="form-label">Observações (opcional)</label>
            <textarea id="observacoes" name="observacoes" class="form-control" rows="3"
                      placeholder="Restrição alimentar, necessidade de acessibilidade...">{{ old('observacoes') }}</textarea>
        </div>

        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="consentimento" id="consentimento" value="1" required>
            <label class="form-check-label" for="consentimento">
                Concordo que meus dados sejam usados apenas para a organização do casamento.
            </label>
        </div>

        <button type="submit" class="btn btn-dark">Enviar resposta</button>
    </form>
</div>
@endsection

@push('scripts')
<script>
    const form = document.getElementById('form-rsvp');
    const bloco = document.getElementById('bloco-quantidade');

    function atualizar() {
        const vai = form.querySelector('input[name="confirma_presenca"]:checked').value === '1';
        bloco.hidden = !vai;
        bloco.querySelectorAll('input').forEach((campo) => { campo.disabled = !vai; });
    }

    form.querySelectorAll('input[name="confirma_presenca"]').forEach((radio) => {
        radio.addEventListener('change', atualizar);
    });

    atualizar(); // estado inicial (inclusive quando a página volta com erro de validação)
</script>
@endpush