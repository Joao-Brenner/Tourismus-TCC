<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Tourismus - Login</title>
    <link rel="stylesheet" href="../visoes/assets/css/cadastro.css">
</head>
<body>

<?php

if (isset($_SESSION['usuario']) && is_array($_SESSION['usuario']) && !empty($_SESSION['usuario'])) {
    $_SESSION['flash_sucesso'] = "Você já está logado!";
    header("Location: index.php?rota=telaPrincipal");
    exit;
}

if (isset($_SESSION['flash_erro'])) {
    $erros_para_view = $_SESSION['flash_erro'];
    unset($_SESSION['flash_erro']);
}


if (isset($_SESSION['login_erros'])) {
    $erros_para_view = $_SESSION['login_erros'];
    unset($_SESSION['login_erros']);
}

if (isset($_SESSION['login_dados'])) {
    $dados = $_SESSION['login_dados'];
    unset($_SESSION['login_dados']);
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

        <form id="form-login" method="post" action="index.php">
            <input type="hidden" name="rota" value="processar_usuario">
            <input type="hidden" name="acao" value="logar">
            <div class="form-row">
                <input type="email" id="email" name="email" class="input-field" placeholder="E-mail" required autocomplete="email">
                <input type="password" id="senha" name="senha" class="input-field" placeholder="Senha" required autocomplete="current-password">
            </div>

            <button type="submit">Logar</button>

            <p class="cadastro-link">
    Não tem conta?  <a href="index.php?rota=cadastro">Cadastre-se aqui</a>
</p>

        </form>
    </div>
<script>
  sessionStorage.clear();
  console.log("SessionStorage limpo ao carregar login.");
</script>

    <script src="../visoes/assets/js/login.js?v=<?= time() ?>"></script>
</body>
</html>
