document.querySelectorAll(".btn-excluir").forEach(btn => {
  btn.addEventListener("click", () => {
    const id = btn.getAttribute("data-id");
    if (confirm("Tem certeza que deseja excluir este roteiro? Esta ação é irreversível.")) {
      document.getElementById("form-excluir-" + id).submit();
    }
  });
});


document.querySelectorAll(".btn-editar").forEach(btn => {
  btn.addEventListener("click", () => {
    const id = btn.getAttribute("data-id");
      document.getElementById("form-editar-" + id).submit();
  });
});
