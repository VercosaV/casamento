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
    <label for="nome" class="form-label">Nome</label>
    <input type="text" id="nome" name="nome" class="form-control" required
        value="{{ old('nome', $convidado->nome ?? '') }}">
</div>
<div class="mb-3">
    <label for="email" class="form-label">E-mail</label>
    <input type="email" id="email" name="email" class="form-control"
        value="{{ old('email', $convidado->email ?? '') }}">
</div>
<div class="mb-3">
    <label for="telefone" class="form-label">Telefone</label>
    <input type="text" id="telefone" name="telefone" class="form-control"
        value="{{ old('telefone', $convidado->telefone ?? '') }}">
</div>
<div class="mb-3">
    <label for="confirma_presenca" class="form-label">Presença</label>
    @php $presenca = (int) old('confirma_presenca', $convidado->confirma_presenca ?? 1); @endphp
    <select id="confirma_presenca" name="confirma_presenca" class="form-select">
        <option value="1" @selected($presenca === 1)>Confirmada</option>
        <option value="0" @selected($presenca === 0)>Não vai</option>
    </select>
</div>
<div class="row">
    <div class="col-6 mb-3">
        <label for="adultos" class="form-label">Adultos</label>
        <input type="number" id="adultos" name="adultos" class="form-control" min="0" max="10" required
            value="{{ old('adultos', $convidado->adultos ?? 1) }}">
    </div>
    <div class="col-6 mb-3">
        <label for="criancas" class="form-label">Crianças</label>
        <input type="number" id="criancas" name="criancas" class="form-control" min="0" max="10" required
            value="{{ old('criancas', $convidado->criancas ?? 0) }}">
    </div>
</div>
<div class="mb-3">
    <label for="observacoes" class="form-label">Observações</label>
    <textarea id="observacoes" name="observacoes" class="form-control" rows="3">{{ old('observacoes', $convidado->observacoes ?? '') }}</textarea>
</div>
<button type="submit" class="btn btn-primary">Salvar</button>
