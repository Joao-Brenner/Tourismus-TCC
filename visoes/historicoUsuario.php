<?php
$historicos = $_SESSION['historicos_usuario'] ?? [];

$camposEsperados = [
    'id',
    'pesquisa_original',
    'pesquisa_normalizada',
    'estado_normalizado',
    'query_hash',
    'osm_id',
    'osm_type',
    'nominatim_status',
    'overpass_status_alvo',
    'overpass_status_entorno',
    'lat',
    'lon', 
    'boundingbox',
    'data_pesquisa',
    'validade',
];
?>

<div class="historico-container">
  <button class="btn-close-form" onclick="window.location.href='index.php?rota=telaPrincipal'">&times;</button>
  <h2>Histórico de Pesquisas</h2>

  <table class="historico-tabela">
    <thead>
        <tr>
        <th>Pesquisa</th>
        <th>Estado</th>
        <th>Data da Pesquisa</th>
        <th>Pesquisar</th>
        <th>Excluir</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!empty($historicos)): ?>
        <?php foreach ($historicos as $h): ?>
          <?php
          $faltando = [];
          foreach ($camposEsperados as $campo) {
              if (!array_key_exists($campo, $h)) {
                  $faltando[] = $campo;
              }
          }
          ?>
          <?php if (!empty($faltando)): ?>
            <tr>
              <td colspan="7" style="color:red; font-weight:bold;">
                Erro: campos faltando → <?= implode(', ', $faltando) ?>
              </td>
            </tr>
          <?php else: ?>
            <tr>
      <td><?= htmlspecialchars($h['pesquisa_original'] ?? '') ?></td>
      <td data-estado="<?= htmlspecialchars($h['estado_normalizado'] ?? '') ?>"></td>
      <td><?= !empty($h['data_pesquisa'])
              ? (new DateTime($h['data_pesquisa']))->format('d/m/Y H:i:s')
              : '' ?></td>

             <td>
            <?php if ($h['nominatim_status'] === 'VALIDO'): ?>
              <form method="POST" action="index.php?rota=processar_overpass_alvo" style="display:inline;">
                <input type="hidden" name="idPesquisa" value="<?= $h['id'] ?>">
                <svg xmlns="http://www.w3.org/2000/svg"
                    width="20" height="20" fill="currentColor"
                    class="acoes bi bi-search text-primary"
                    viewBox="0 0 16 16" title="Pesquisar"
                    style="cursor:pointer"
                    onclick="this.closest('form').submit();">
                  <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001
                          q.044.06.098.115l3.85 3.85a1 1 0 0 0
                          1.415-1.414l-3.85-3.85a1 1 0 0
                          0-.115-.1zM12 6.5a5.5 5.5 0 1
                          1-11 0 5.5 5.5 0 0 1 11 0"/>
                </svg>
              </form>
            <?php else: ?>
              <form method="POST" action="index.php?rota=processar_pesquisa" style="display:inline;">
                <input type="hidden" name="estado" value="<?= $h['estado_normalizado'] ?>">
                <input type="hidden" name="input_pesquisa" value="<?= $h['pesquisa_normalizada'] ?>">
                <input type="hidden" name="query_hash" value="<?= $h['query_hash'] ?>">
                <input type="hidden" name="veioHistorico" value= "true">
                <svg xmlns="http://www.w3.org/2000/svg"
                    width="20" height="20" fill="currentColor"
                    class="acoes bi bi-search text-primary"
                    viewBox="0 0 16 16" title="Pesquisar"
                    style="cursor:pointer"
                    onclick="this.closest('form').submit();">
                  <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001
                          q.044.06.098.115l3.85 3.85a1 1 0 0 0
                          1.415-1.414l-3.85-3.85a1 1 0 0
                          0-.115-.1zM12 6.5a5.5 5.5 0 1
                          1-11 0 5.5 5.5 0 0 1 11 0"/>
                </svg>
              </form>
            <?php endif; ?>
          </td>


              <td>
                <form method="POST" action="index.php?rota=processar_historico" id="form-excluir-<?= $h['id'] ?>">
                  <input type="hidden" name="acao" value="excluirHistorico">
                  <?php foreach ($camposEsperados as $campo): ?>
                    <input type="hidden" name="<?= $campo ?>" value="<?= htmlspecialchars($h[$campo] ?? '') ?>">
                  <?php endforeach; ?>

                  <button type="button" class="btn-excluir" data-id="<?= $h['id'] ?>">
                    <svg xmlns="http://www.w3.org/2000/svg"
                         width="20" height="20" fill="currentColor"
                         class="acoes bi bi-trash3-fill text-danger"
                         viewBox="0 0 16 16" title="Excluir">
                      <path d="M11 1.5v1h3.5a.5.5 0 0 1 0
                               1h-.538l-.853 10.66A2 2 0 0 1
                               11.115 16h-6.23a2 2 0 0 1-1.994-1.84L2.038
                               3.5H1.5a.5.5 0 0 1 0-1H5v-1A1.5
                               1.5 0 0 1 6.5 0h3A1.5 1.5 0 0
                               1 11 1.5m-5 0v1h4v-1a.5.5 0 0
                               0-.5-.5h-3a.5.5 0 0 0-.5.5M4.5
                               5.029l.5 8.5a.5.5 0 1 0
                               .998-.06l-.5-8.5a.5.5 0 1
                               0-.998.06m6.53-.528a.5.5 0 0
                               0-.528.47l-.5 8.5a.5.5 0 0
                               0 .998.058l.5-8.5a.5.5 0 0
                               0-.47-.528M8 4.5a.5.5 0 0
                               0-.5.5v8.5a.5.5 0 0 0 1
                               0V5a.5.5 0 0 0-.5-.5"/>
                    </svg>
                  </button>
                </form>
              </td>
            </tr>
          <?php endif; ?>
        <?php endforeach; ?>
      <?php else: ?>
        <tr>
          <td colspan="7" class="celula-vazia">Nenhum histórico encontrado.</td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
