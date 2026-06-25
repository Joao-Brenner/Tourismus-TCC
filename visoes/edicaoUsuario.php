<?php

$usuario = $_SESSION['usuario'] ?? null;
if (!$usuario) {
    header("Location: index.php?rota=login");
    exit;
}

if (isset($_SESSION['edicao_erros'])) {
    $erros_para_view = $_SESSION['edicao_erros'];
    unset($_SESSION['edicao_erros']);
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


<div class="edicao-container">

<button type="button" class="btn-close-form" id="btn-fechar-form">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
             fill="currentColor" viewBox="0 0 16 16">
            <path d="M2.146 2.146a.5.5 0 0 1 .708 0L8 7.293l5.146-5.147a.5.5 0 0 1 
                     .708.708L8.707 8l5.147 5.146a.5.5 0 0 1-.708.708L8 
                     8.707l-5.146 5.147a.5.5 0 0 1-.708-.708L7.293 
                     8 2.146 2.854a.5.5 0 0 1 0-.708z"/>
        </svg>
    </button>

    <h2>Editar Usuário</h2>
        <form id="form-edicao" method="post" action="index.php?rota=processar_usuario">
            <input type="hidden" name="acao" id="acao" value="atualizarPerfil">
            <?php $usuario = $_SESSION['usuario']; ?>
            <input type="hidden" name="id_usuario" value="<?= htmlspecialchars($usuario['id_usuario']) ?>">

    <div class="form-group">
        <input type="text" name="nome" id="nome"value="<?= htmlspecialchars($usuario['nome']); ?>" readonly>

            <button type="button" class="btn btn-link btn-editar">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pencil-square" viewBox="0 0 16 16">
  <path d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z"/>
  <path fill-rule="evenodd" d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5z"/>
</svg>
            </button>

            <button type="button" class="btn btn-link btn-cancelar-edicao" style="display:none;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-sign-stop-fill" viewBox="0 0 16 16">
  <path d="M10.371 8.277v-.553c0-.827-.422-1.234-.987-1.234-.572 0-.99.407-.99 1.234v.553c0 .83.418 1.237.99 1.237.565 0 .987-.408.987-1.237m2.586-.24c.463 0 .735-.272.735-.744s-.272-.741-.735-.741h-.774v1.485z"/>
  <path d="M4.893 0a.5.5 0 0 0-.353.146L.146 4.54A.5.5 0 0 0 0 4.893v6.214a.5.5 0 0 0 .146.353l4.394 4.394a.5.5 0 0 0 .353.146h6.214a.5.5 0 0 0 .353-.146l4.394-4.394a.5.5 0 0 0 .146-.353V4.893a.5.5 0 0 0-.146-.353L11.46.146A.5.5 0 0 0 11.107 0zM3.16 10.08c-.931 0-1.447-.493-1.494-1.132h.653c.065.346.396.583.891.583.524 0 .83-.246.83-.62 0-.303-.203-.467-.637-.572l-.656-.164c-.61-.147-.978-.51-.978-1.078 0-.706.597-1.184 1.444-1.184.853 0 1.386.475 1.436 1.087h-.645c-.064-.32-.352-.542-.797-.542-.472 0-.77.246-.77.6 0 .261.196.437.553.522l.654.161c.673.164 1.06.487 1.06 1.11 0 .736-.574 1.228-1.544 1.228Zm3.427-3.51V10h-.665V6.57H4.753V6h3.006v.568H6.587Zm4.458 1.16v.544c0 1.131-.636 1.805-1.661 1.805-1.026 0-1.664-.674-1.664-1.805V7.73c0-1.136.638-1.807 1.664-1.807s1.66.674 1.66 1.807ZM11.52 6h1.535c.82 0 1.316.55 1.316 1.292 0 .747-.501 1.289-1.321 1.289h-.865V10h-.665V6.001Z"/>
</svg>
            </button>
        </div>

        <div class="form-group">
            <input type="email" name="email" id="email"
                   value="<?= htmlspecialchars($usuario['email']); ?>" readonly>

            <button type="button" class="btn btn-link btn-editar">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pencil-square" viewBox="0 0 16 16">
  <path d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z"/>
  <path fill-rule="evenodd" d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5z"/>
</svg>
            </button>

            <button type="button" class="btn btn-link btn-cancelar-edicao" style="display:none;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-sign-stop-fill" viewBox="0 0 16 16">
  <path d="M10.371 8.277v-.553c0-.827-.422-1.234-.987-1.234-.572 0-.99.407-.99 1.234v.553c0 .83.418 1.237.99 1.237.565 0 .987-.408.987-1.237m2.586-.24c.463 0 .735-.272.735-.744s-.272-.741-.735-.741h-.774v1.485z"/>
  <path d="M4.893 0a.5.5 0 0 0-.353.146L.146 4.54A.5.5 0 0 0 0 4.893v6.214a.5.5 0 0 0 .146.353l4.394 4.394a.5.5 0 0 0 .353.146h6.214a.5.5 0 0 0 .353-.146l4.394-4.394a.5.5 0 0 0 .146-.353V4.893a.5.5 0 0 0-.146-.353L11.46.146A.5.5 0 0 0 11.107 0zM3.16 10.08c-.931 0-1.447-.493-1.494-1.132h.653c.065.346.396.583.891.583.524 0 .83-.246.83-.62 0-.303-.203-.467-.637-.572l-.656-.164c-.61-.147-.978-.51-.978-1.078 0-.706.597-1.184 1.444-1.184.853 0 1.386.475 1.436 1.087h-.645c-.064-.32-.352-.542-.797-.542-.472 0-.77.246-.77.6 0 .261.196.437.553.522l.654.161c.673.164 1.06.487 1.06 1.11 0 .736-.574 1.228-1.544 1.228Zm3.427-3.51V10h-.665V6.57H4.753V6h3.006v.568H6.587Zm4.458 1.16v.544c0 1.131-.636 1.805-1.661 1.805-1.026 0-1.664-.674-1.664-1.805V7.73c0-1.136.638-1.807 1.664-1.807s1.66.674 1.66 1.807ZM11.52 6h1.535c.82 0 1.316.55 1.316 1.292 0 .747-.501 1.289-1.321 1.289h-.865V10h-.665V6.001Z"/>
</svg>
            </button>
        </div>

            </button>

        <div class="form-group">
        <button type="button" id="btn-atualizar-senha">
            Atualizar Senha
        </button>
    </div>
    
        <div class="form-actions">
            <button type="button" class="btn btn-danger btn-danger-small" id="btn-excluir"> Excluir conta </button>
            <button type="submit" class="btn btn-primary">Salvar alterações</button>

        </div>


        </div>
    </form>
</div>


