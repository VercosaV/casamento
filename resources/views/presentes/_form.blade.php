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
<button type="submit" class="btn btn-primary">Salvar</button>