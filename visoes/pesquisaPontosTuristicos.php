<?php

$usuario = $_SESSION['usuario'] ?? null;
if (!$usuario) {
    header("Location: index.php?rota=login");
    exit;
}

if (isset($_SESSION['pesquisa_erros'])) {
    $erros_para_view = $_SESSION['pesquisa_erros'];
    unset($_SESSION['pesquisa_erros']);
}

if (isset($_SESSION['pesquisa_notificacoes'])) {
    $notificacoes_para_view = $_SESSION['pesquisa_notificacoes'];
    unset($_SESSION['pesquisa_notificacoes']);
}

?>

<form id="form_pesquisa" method="post" action="index.php?rota=processar_pesquisa">
            <?php $usuario = $_SESSION['usuario']; ?>
            <input type="hidden" name="id_usuario" value="<?= htmlspecialchars($usuario['id_usuario']) ?>">

<div class="pesquisa_container">
  <div id="feedback_container">
    <?php if (!empty($erros_para_view)): ?>
        <div id="error_balloon_submit" class="error_balloon show">
            <ul>
                <?php foreach ((array)$erros_para_view as $erro): ?>
                    <li><?= htmlspecialchars($erro) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (!empty($notificacoes_para_view)): ?>
        <?php foreach ((array)$notificacoes_para_view as $msg): ?>
            <div class="pesquisa_balloon show"><?= htmlspecialchars($msg) ?></div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>


    <div class="search_bar">
        
      <select id="estado" name="estado">
          <option value="" selected disabled>Estado</option>
        <option value="acre">Acre</option>
        <option value="alagoas">Alagoas</option>
        <option value="amazonas">Amazonas</option>
        <option value="amapa">Amapá</option>
        <option value="bahia">Bahia</option>
        <option value="ceara">Ceará</option>
        <option value="distrito_federal">Distrito Federal</option>
        <option value="espirito_santo">Espírito Santo</option>
        <option value="goias">Goiás</option>
        <option value="maranhao">Maranhão</option>
        <option value="minas_gerais">Minas Gerais</option>
        <option value="mato_grosso_do_sul">Mato Grosso do Sul</option>
        <option value="mato_grosso">Mato Grosso</option>
        <option value="para">Pará</option>
        <option value="paraiba">Paraíba</option>
        <option value="pernambuco">Pernambuco</option>
        <option value="piaui">Piauí</option>
        <option value="parana">Paraná</option>
        <option value="rio_de_janeiro">Rio de Janeiro</option>
        <option value="rio_grande_do_norte">Rio Grande do Norte</option>
        <option value="rondonia">Rondônia</option>
        <option value="roraima">Roraima</option>
        <option value="rio_grande_do_sul">Rio Grande do Sul</option>
        <option value="santa_catarina">Santa Catarina</option>
        <option value="sergipe">Sergipe</option>
        <option value="sao_paulo">São Paulo</option>
        <option value="tocantins">Tocantins</option>
        </select>


        <input type="search" id="input_pesquisa"name="input_pesquisa" placeholder="Pesquisar pontos turísticos.">

        <input type="hidden" id="query_hash" name="query_hash" value="" />


        
        <button id="btn_pesquisar"type="submit" ><svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" fill="currentColor" class="bi bi-search" viewBox="0 0 16 16">
  <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0"/>
</svg></button>
    </div>
</div>

</form>

