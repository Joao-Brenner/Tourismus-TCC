<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tourismus - Cadastro</title>
    <link rel="stylesheet" href="../visoes/assets/css/cadastro.css?v=<?= time() ?>">
</head>
<body>
    
<?php

if (isset($_SESSION['usuario']) && is_array($_SESSION['usuario']) && !empty($_SESSION['usuario'])) {
    $_SESSION['flash_sucesso'] = "Você já está logado!";
    header("Location: index.php?rota=telaPrincipal");
    exit;
}

if (isset($_SESSION['cadastro_erros'])) {
    $erros_para_view = $_SESSION['cadastro_erros'];
    unset($_SESSION['cadastro_erros']);
}

if (isset($_SESSION['cadastro_dados'])) {
    $dados = $_SESSION['cadastro_dados'];
    unset($_SESSION['cadastro_dados']);
}
?>

<?php if (!empty($erros_para_view)): ?>
  <div id="error_balloon_submit" class="error_balloon">
    <ul>
      <?php foreach ((array)$erros_para_view as $erro): ?>
        <li><?= htmlspecialchars($erro) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>


    <div class="login-container">
        <h1>Tourismus</h1>

        <form id="form-cadastro" method="post" action="index.php">
            <input type="hidden" name="rota" value="processar_usuario">
            <input type="hidden" name="acao" value="cadastrarUsuario">
            <input type="hidden" name="id_usuario" id="id_usuario">

            <div class="form-row">
                <input type="text" id="nome" name="nome" class="input-field" placeholder="Nome" required
                       value="<?= isset($dados['nome']) && is_string($dados['nome']) ? htmlspecialchars($dados['nome']) : '' ?>">
                
            </div>
            
            <div class="form-row" style="align-items: flex-start; margin-bottom: 20px;">
                
                <input type="email" id="email" name="email" class="input-field" placeholder="E-mail" required
                       value="<?= isset($dados['email']) && is_string($dados['email']) ? htmlspecialchars($dados['email']) : '' ?>">

                <div style="flex: 1; margin-right: 10px;  margin-left: 7px;">
                    <input type="password" id="senha" name="senha" 
                           class="input-field" style="width: 100%; margin: 0;" placeholder="Senha" required>

                    <ul style="margin: 5px 0 0 10px; padding: 0; list-style: none; font-size: 11px; text-align: left; color: #555;">
                        <li id="req-comprimento">• Mínimo 8 caracteres</li>
                        <li id="req-maiuscula">• Uma letra maiúscula</li>
                        <li id="req-minuscula">• Uma letra minúscula</li>
                        <li id="req-numero">• Um número</li>
                        <li id="req-especial">• Um caractere especial</li>
                    </ul>
                </div>

</div>

            <button type="submit">Cadastrar</button>

            <p class="cadastro-link">
                Já tem conta? <a href="index.php?rota=login">Faça login aqui</a>
            </p>
            
        </form>
    </div>

    <script src="../visoes/assets/js/cadastro.js?v=<?= time() ?>"></script>
 
</body>
</html>