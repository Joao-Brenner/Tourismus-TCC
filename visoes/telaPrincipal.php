<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tourismus - Tela Principal</title>
    <?php
    $secao = $_GET['secao'] ?? null;
    $carregarChoices = ($secao === null);
    ?>

    <?php if ($carregarChoices): ?>
      <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
    <?php endif; ?>

    <link rel="stylesheet" href="../visoes/assets/css/tela_principal.css?v=<?= time() ?>">

    <?php if ($secao === null): ?>
    <link rel="stylesheet" href="../visoes/assets/css/pesquisa_pon_tur.css?v=<?= time() ?>">
  <?php endif; ?>

  <?php if ($secao === 'telaMapa' || $secao === 'editarRoteiroMapa' ): ?>
     <link rel="stylesheet" href="../visoes/assets/css/tela_mapa.css?v=<?= time() ?>">
  <link rel="stylesheet" href="/PADS/node_modules/leaflet/dist/leaflet.css" />
  <link rel="stylesheet" href="/PADS/node_modules/leaflet.markercluster/dist/MarkerCluster.css" />
  <link rel="stylesheet" href="/PADS/node_modules/leaflet.markercluster/dist/MarkerCluster.Default.css" />
<?php endif; ?>


    <?php if ($secao === 'edicaoUsuario'): ?>
      <link rel="stylesheet" href="../visoes/assets/css/edicao_usuario.css?v=<?= time() ?>">
    <?php endif; ?>

    <?php if ($secao === 'atualizacaoSenha'): ?>
      <link rel="stylesheet" href="../visoes/assets/css/edicao_usuario.css?v=<?= time() ?>">
      <link rel="stylesheet" href="../visoes/assets/css/atualizacao_senha.css?v=<?= time() ?>">
    <?php endif; ?>

    <?php if ($secao === 'historicoUsuario'): ?>
     <link rel="stylesheet" href="../visoes/assets/css/historico_usuario.css?v=<?= time() ?>">
    <?php endif; ?>

     <?php if ($secao === 'historicoRoteiro'): ?>
     <link rel="stylesheet" href="../visoes/assets/css/historico_roteiro.css?v=<?= time() ?>">
    <?php endif; ?>


</head>
<body>

<?php
$usuario = $_SESSION['usuario'] ?? null;
if (!$usuario) {
    header("Location: index.php?rota=login");
    exit;
}

$mensagem = $_SESSION['flash_sucesso'] ?? $_SESSION['flash_erro'] ?? null;
$classe = isset($_SESSION['flash_sucesso']) ? 'flash-success' : 'flash-error';
unset($_SESSION['flash_sucesso'], $_SESSION['flash_erro']);
?>

<?php if ($mensagem): ?>
  <div id="flash-msg" class="flash-balloon <?= $classe ?>">
    <?= htmlspecialchars($mensagem) ?>
  </div>
<?php endif; ?>

<div class="topo">
  <div class="menu-icone" id="hamburger">
    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor"
         class="bi bi-list" viewBox="0 0 16 16">
      <path fill-rule="evenodd"
            d="M2.5 12a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5m0-4
               a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5m0-4
               a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5"/>
    </svg>
  </div>
  <div class="titulo">Tourismus</div>
</div>

<div class="menu" id="menu">
  <ul>
    <li><a href="index.php?rota=edicaoUsuario">Meus Dados</a></li>
        <li><a href="index.php?rota=historicoUsuario">Histórico</a></li>
<li>
  <form action="index.php?rota=processar_roteiro" method="post" style="display:inline;">
    <input type="hidden" name="acao" value="listarRoteiro">
    <button type="submit" class="menu-link">Roteiros</button>
  </form>
</li>

    <li><a href="index.php?rota=deslogar">Deslogar</a></li>
  </ul>
</div>

<div id="overlay" class="overlay"></div>

<div class="tela-principal-container">

<?php if ($secao === null): ?>
    <?php include 'pesquisaPontosTuristicos.php'; ?>
<?php endif; ?>


  <div class="secao-container">
    <?php
    if (isset($_GET['secao'])) {

        switch ($_GET['secao']) {
            case 'edicaoUsuario':
                include 'edicaoUsuario.php';
                break;

            case 'atualizacaoSenha':
                include 'edicaoUsuario.php';

                include 'atualizacaoSenha.php';
                break;
             
             case 'historicoUsuario':
    include 'historicoUsuario.php';
    break;

    case 'telaMapa':
    include 'telaMapa.php';
    break;

    case 'historicoRoteiro':
    include 'historicoRoteiro.php';
    break;

     case 'editarRoteiroMapa':
    include 'telaMapa.php';
    break;


        }
    }
    ?>
  </div>
</div>

<?php if ($carregarChoices): ?>
  <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<?php endif; ?>

<script src="../visoes/assets/js/tela_principal.js?v=<?= time() ?>"></script>

<?php if ($secao === null): ?>
  <script src="../visoes/assets/js/pesquisa_pon_tur.js?v=<?= time() ?>"></script>
<?php endif; ?>

<?php if ($secao === 'telaMapa' || $secao === 'editarRoteiroMapa'): ?>
  <script src="/PADS/node_modules/dom-to-image-more/dist/dom-to-image-more.min.js"></script>
  <script src="/PADS/node_modules/leaflet/dist/leaflet.js"></script>
  <script src="/PADS/node_modules/leaflet.markercluster/dist/leaflet.markercluster.js"></script>
  <script src="../visoes/assets/js/utilitarios_mapa.js?v=<?= time() ?>"></script>
  <?php if ($secao === 'editarRoteiroMapa'): ?>
    <script src="../visoes/assets/js/mapa_edicao_roteiro.js?v=<?= time() ?>"></script>
  <?php else: ?>
    <script src="../visoes/assets/js/tela_mapa.js?v=<?= time() ?>"></script>
  <?php endif; ?>
<?php endif; ?>


<?php if ($secao === 'edicaoUsuario' || $secao === 'atualizacaoSenha'): ?>
  <script src="../visoes/assets/js/edicao_usuario.js?v=<?= time() ?>"></script>
<?php endif; ?>

<?php if ($secao === 'atualizacaoSenha'): ?>
  <script src="../visoes/assets/js/atualizacao_senha.js?v=<?= time() ?>"></script>
<?php endif; ?>

<?php if ($secao === 'historicoUsuario'): ?>
<script src="../visoes/assets/js/historico_usuario.js?v=<?= time() ?>"></script>
<?php endif; ?>

<?php if ($secao === 'historicoRoteiro'): ?>
<script src="../visoes/assets/js/historico_roteiro.js?v=<?= time() ?>"></script>
<?php endif; ?>

</body>
</html>
