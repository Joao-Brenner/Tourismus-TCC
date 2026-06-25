<?php
$senhaAtualDigitada = $_SESSION['senha_atual_digitada'] ?? '';
unset($_SESSION['senha_atual_digitada']);
?>

<?php if (!empty($_SESSION['atualizacao_erros'])): ?>
  <div class="erro-balao">
    <?php foreach ($_SESSION['atualizacao_erros'] as $erro): ?>
      <p><?= htmlspecialchars($erro) ?></p>
    <?php endforeach; ?>
  </div>
  <?php unset($_SESSION['atualizacao_erros']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['flash_sucesso'])): ?>
  <div class="sucesso-balao" id="sucesso-balao">
    <p><?= htmlspecialchars($_SESSION['flash_sucesso']) ?></p>
  </div>
  <?php unset($_SESSION['flash_sucesso']); ?>
<?php endif; ?>



<div class="erro-balao" style="display:none"></div>

<div class="overlay-senha"></div>

<div class="atualizar-senha-container">
  <h2>Atualizar Senha</h2>


<form id="form-atualizar-senha" method="post" action="index.php">
  <input type="hidden" name="rota" value="processar_usuario">
<input type="hidden" id="acao-senha" name="acao" value="atualizarSenha">
<input type="hidden" name="id" value="<?= $_SESSION['usuario']['id_usuario'] ?? 0 ?>">


<div class="form-group-horizontal">
  <div class="form-col">
    <label for="senha_atual">Senha Atual</label>
    <div class="input-with-icon">
      <input type="password"
             id="senha_atual"
             name="senha_atual"
             class="input-field input-enabled"
             placeholder="Senha Atual"
             value="<?= htmlspecialchars($senhaAtualDigitada) ?>"
             required>
      <button type="button" id="btn-validar-senha" title="Validar Senha Atual">
        <span class="icon-lupa">🔍</span>
      </button>
    </div>
  </div>
</div>

    <div class="form-group form-col">
      <label for="nova_senha">Nova Senha</label>
      <input
        type="password"
        id="nova_senha"
        name="nova_senha"
        class="input-field input-disabled"
        placeholder="Nova Senha"
        disabled
        required
      >
      <ul class="senha-requisitos">
        <li id="req-comprimento">• Mínimo 8 caracteres</li>
        <li id="req-maiuscula">• Uma letra maiúscula</li>
        <li id="req-minuscula">• Uma letra minúscula</li>
        <li id="req-numero">• Um número</li>
        <li id="req-especial">• Um caractere especial</li>
      </ul>
    </div>

    <div class="form-group form-col">
      <label for="confirmar_senha">Confirmar Nova Senha</label>
      <input
        type="password"
        id="confirmar_senha"
        name="confirmar_senha"
        class="input-field input-disabled"
        placeholder="Confirmar Nova Senha"
        disabled
        required
      >
    </div>

    <button type="submit" id="btn-salvar-senha">Salvar Nova Senha</button>
    <button type="button" id="btn-cancelar-senha">Cancelar</button>
  </form>
</div>
