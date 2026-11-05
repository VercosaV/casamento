@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $erro)
                <li>{{ $erro }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="mb-3">
    <label for="nome" class="form-label">Nome do presente</label>
    <input type="text" id="nome" name="nome" class="form-control" required
        value="{{ old('nome', $presente->nome ?? '') }}">
</div>
<div class="mb-3">
    <label for="descricao" class="form-label">Descrição</label>
    <textarea id="descricao" name="descricao" class="form-control" rows="3">{{ old('descricao', $presente->descricao ?? '') }}</textarea>
</div>
<div class="mb-3">
    <label for="valor" class="form-label">Valor (R$)</label>
    <input type="number" step="0.01" min="0" id="valor" name="valor" class="form-control" required
        value="{{ old('valor', $presente->valor ?? '') }}">
</div>
<div class="mb-3">
    <label for="link" class="form-label">Link da loja (opcional)</label>
    <input type="url" id="link" name="link" class="form-control"
        value="{{ old('link', $presente->link ?? '') }}">
</div>
<div class="row">
    <div class="col-md-6 mb-3">
        <label for="categoria" class="form-label">Categoria (opcional)</label>
        <input type="text" id="categoria" name="categoria" class="form-control"
            value="{{ old('categoria', $presente->categoria ?? '') }}">
    </div>
    <div class="col-md-6 mb-3">
        <label for="cotas_total" class="form-label">Número de cotas</label>
        <input type="number" id="cotas_total" name="cotas_total" class="form-control" min="1" max="100"
            required value="{{ old('cotas_total', $presente->cotas_total ?? 1) }}">
        <div class="form-text">1 = presente inteiro. Mais que 1 = os convidados dividem em cotas.</div>
    </div>
</div>
<div class="mb-3">
    <label for="imagem" class="form-label">Imagem (até 2 MB)</label>
    <input type="file" id="imagem" name="imagem" class="form-control" accept="image/*">
    @if (!empty($presente->imagem_url))
        <img src="{{ $presente->imagem_url }}" alt="Imagem atual" class="img-thumbnail mt-2" style="max-width: 160px;">
    @endif
</div>
<button type="submit" class="btn btn-ouro">Salvar</button>
