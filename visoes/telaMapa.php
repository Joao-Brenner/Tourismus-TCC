<?php
header("Referrer-Policy: no-referrer-when-downgrade");
$geojson = $_SESSION['geojson_telaMapa'] ?? null;
$openTiles = $_ENV['OPEN_STREET_MAP_TILES'] ?: null;
?>
<script>
  const geojsonData = <?= $geojson ? $geojson : 'null'; ?>;
  const openMapTiles = <?= $openTiles ? json_encode($openTiles) : 'null'; ?>;
</script>


<div class="modal-wrapper">
    <div class="mapa-modal" id="mapaModal">
        <button class="btn-close-form" onclick="window.location.href='index.php?rota=telaPrincipal'">&times;</button>
        <div class="mapa-conteudo" id="map"></div>                
</div>
</div>

<div id="infoModal" class="modal">
  <div class="modal-content"></div>
</div>

<button class="btn-roteiro" id="abrirRoteiro">Roteiro</button>

<div id="roteiroModal" class="roteiro-modal oculto" aria-hidden="true">
  <span class="close-btn" id="fecharRoteiro">&times;</span>

  <input type="text" id="tituloRoteiro" maxlength="100" placeholder="Título" class="titulo-roteiro">

  <button class="btn-novo-dia" id="btnNovoDia">Novo Dia</button>

  <div id="diasContainer" class="dias-container">
  </div>

  <div class="rodape-roteiro">
    <button id="btnSalvarRoteiro" class="btn-salvar-roteiro">Salvar</button>
  </div>
</div>





