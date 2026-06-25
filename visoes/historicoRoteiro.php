<?php
$roteiros = $_SESSION['roteiros'] ?? [];

$camposEsperados = [
    'id_roteiro',
    'titulo',
    'codigo',
    'id_usuario',
    'data_r',
    'centro',
];
?>

<div class="roteiro-container">
  <button class="btn-close-form" onclick="window.location.href='index.php?rota=telaPrincipal'">&times;</button>
  <h2>Histórico de Roteiros</h2>

  <table class="roteiro-tabela">
    <thead>
        <tr>
          <th>Título</th>
          <th>Última Modificação</th>
          <th>Abrir</th>
          <th>Editar</th>
        <th>Excluir</th>
        </tr>
    </thead>
    <tbody>
      <?php if (!empty($roteiros)): ?>
        <?php foreach ($roteiros as $r): ?>
          <?php
          $faltando = [];
          foreach ($camposEsperados as $campo) {
              if (!array_key_exists($campo, $r)) {
                  $faltando[] = $campo;
              }
          }
          ?>
          <?php if (!empty($faltando)): ?>
            <tr>
              <td colspan="5" style="color:red; font-weight:bold;">
                Erro: campos faltando → <?= implode(', ', $faltando) ?>
              </td>
            </tr>
          <?php else: ?>
            <tr>
              <td><?= htmlspecialchars($r['titulo'] ?? '') ?></td>
              <td><?= !empty($r['data_r'])
                      ? (new DateTime($r['data_r']))->format('d/m/Y H:i:s')
                      : '' ?></td>
                  <td>
                    <a href="index.php?rota=abrir_pdf&codigo=<?= urlencode($r['codigo']) ?>" target="_blank">
                        <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30"
                            fill="currentColor" class="acoes bi bi-filetype-pdf"
                            viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M14 4.5V14a2 2 0 0 1-2 2h-1v-1h1a1 1 0 0 0 1-1V4.5h-2A1.5 1.5 0 0 1 9.5 3V1H4a1 1 0 0 0-1 1v9H2V2a2 2 0 0 1 2-2h5.5zM1.6 11.85H0v3.999h.791v-1.342h.803q.43 0 .732-.173.305-.175.463-.474a1.4 1.4 0 0 0 .161-.677q0-.375-.158-.677a1.2 1.2 0 0 0-.46-.477q-.3-.18-.732-.179m.545 1.333a.8.8 0 0 1-.085.38.57.57 0 0 1-.238.241.8.8 0 0 1-.375.082H.788V12.48h.66q.327 0 .512.181.185.183.185.522m1.217-1.333v3.999h1.46q.602 0 .998-.237a1.45 1.45 0 0 0 .595-.689q.196-.45.196-1.084 0-.63-.196-1.075a1.43 1.43 0 0 0-.589-.68q-.396-.234-1.005-.234zm.791.645h.563q.371 0 .609.152a.9.9 0 0 1 .354.454q.118.302.118.753a2.3 2.3 0 0 1-.068.592 1.1 1.1 0 0 1-.196.422.8.8 0 0 1-.334.252 1.3 1.3 0 0 1-.483.082h-.563zm3.743 1.763v1.591h-.79V11.85h2.548v.653H7.896v1.117h1.606v.638z"/>
                        </svg>
                    </a>
                    </td>  
                    <td>
                      <form id="form-editar-<?= htmlspecialchars($r['codigo']) ?>" 
                      action="index.php?rota=processar_roteiro" method="post" style="display:inline;">
                      <input type="hidden" name="acao" value="listarEditarRoteiro">
                      <input type="hidden" name="id_roteiro" value="<?= htmlspecialchars($r['id_roteiro']) ?>">
                      <input type="hidden" name="titulo" value="<?= htmlspecialchars($r['titulo']) ?>">
                      <input type="hidden" name="centro" value="<?= htmlspecialchars($r['centro']) ?>">
                      <input type="hidden" name="codigo" value="<?= htmlspecialchars($r['codigo']) ?>">
                      <button type="button" class="btn-editar" data-id="<?= htmlspecialchars($r['codigo']) ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="currentColor" class="bi bi-journal-bookmark-fill" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M6 1h6v7a.5.5 0 0 1-.757.429L9 7.083 6.757 8.43A.5.5 0 0 1 6 8z"/>
                        <path d="M3 0h10a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2v-1h1v1a1 1 0 0 0 1 1h10a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H3a1 1 0 0 0-1 1v1H1V2a2 2 0 0 1 2-2"/>
                        <path d="M1 5v-.5a.5.5 0 0 1 1 0V5h.5a.5.5 0 0 1 0 1h-2a.5.5 0 0 1 0-1zm0 3v-.5a.5.5 0 0 1 1 0V8h.5a.5.5 0 0 1 0 1h-2a.5.5 0 0 1 0-1zm0 3v-.5a.5.5 0 0 1 1 0v.5h.5a.5.5 0 0 1 0 1h-2a.5.5 0 0 1 0-1z"/>
                        </svg>
                     </button>
                      </form>
                    </td>      

                    <td>
  <form id="form-excluir-<?= htmlspecialchars($r['id_roteiro']) ?>" 
      action="index.php?rota=processar_roteiro" method="post" style="display:inline;">
  <input type="hidden" name="acao" value="excluirRoteiro">
  <input type="hidden" name="id_roteiro" value="<?= htmlspecialchars($r['id_roteiro']) ?>">
   <input type="hidden" name="codigo" value="<?= htmlspecialchars($r['codigo']) ?>">
  <button type="button" class="btn-excluir" data-id="<?= htmlspecialchars($r['id_roteiro']) ?>">
    <svg xmlns="http://www.w3.org/2000/svg"
         width="30" height="30" fill="currentColor"
         class="bi bi-trash3-fill text-danger"
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
          <td colspan="7" class="celula-vazia">Nenhum roteiro encontrado.</td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>


