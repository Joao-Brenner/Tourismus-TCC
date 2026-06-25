const REGEX = {
  NOME: /^[A-Za-zÀ-ÖØ-öø-ÿçÇ\s]{3,}$/,
  EMAIL: /^[^\s@]+@[^\s@]+\.[^\s@]+$/
};

function showSubmitErrors(messages) {
  const prev = document.getElementById("error_balloon_submit");
  if (prev) prev.remove();

  const balloon = document.createElement("div");
  balloon.id = "error_balloon_submit";
  balloon.className = "error_balloon"; 

  const ul = document.createElement("ul");
  (Array.isArray(messages) ? messages : [messages]).forEach((msg) => {
    const li = document.createElement("li");
    li.textContent = msg;
    ul.appendChild(li);
  });

  balloon.appendChild(ul);
  document.body.appendChild(balloon);
}

function clearSubmitErrors() {
  const prev = document.getElementById("error_balloon_submit");
  if (prev) prev.remove();
}

function focusAndSelect(el) {
  if (!el) return;
  el.focus();
  if (el.select) el.select();
  else if (el.setSelectionRange) el.setSelectionRange(0, el.value.length);
  el.scrollIntoView({ behavior: "smooth", block: "center" });
}

function dynamicNameInput(input) {
  input.addEventListener("input", () => {
    const cleaned = input.value.replace(/[^A-Za-zÀ-ÖØ-öø-ÿçÇ\s]/g, "");
    if (cleaned !== input.value) input.value = cleaned;
  });
}

function dynamicEmailInput(input) {
  input.addEventListener("input", () => {
  });
}

function validateOnSubmit(fields) {
  const errors = [];
  let firstInvalid = null;

  const nomeVal = fields.nome.value.trim();
  if (!nomeVal) {
    errors.push("O nome não pode estar vazio.");
    firstInvalid = firstInvalid || fields.nome;
  } else if (nomeVal.length < 3 || !REGEX.NOME.test(nomeVal)) {
    errors.push("Nome inválido. Use apenas letras (com acentos/ç) e espaços, mínimo 3 caracteres.");
    firstInvalid = firstInvalid || fields.nome;
  }

  const emailVal = fields.email.value.trim();
  if (!emailVal) {
    errors.push("O e-mail não pode estar vazio.");
    firstInvalid = firstInvalid || fields.email;
  } else if (!REGEX.EMAIL.test(emailVal)) {
    errors.push("E-mail inválido. Ex.: usuario@dominio.com.");
    firstInvalid = firstInvalid || fields.email;
  }

  return { errors, firstInvalid };
}

document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("form-edicao");
  const nome = document.getElementById("nome");
  const email = document.getElementById("email");


  document.querySelectorAll(".btn-editar").forEach(btn => {
    btn.addEventListener("click", () => {
      const group = btn.closest(".form-group");
      const input = group.querySelector("input");
      const cancelBtn = group.querySelector(".btn-cancelar-edicao");

      if (input) {
  input.removeAttribute("readonly");
}
      btn.style.display = "none";
      if (cancelBtn) cancelBtn.style.display = "inline-block";
    });
  });

  document.querySelectorAll(".btn-cancelar-edicao").forEach(btn => {
    btn.addEventListener("click", () => {
      const group = btn.closest(".form-group");
      const input = group.querySelector("input");
      const editBtn = group.querySelector(".btn-editar");

       if (input) {
          input.value = input.defaultValue;
          input.setAttribute("readonly", "readonly");
      }

      btn.style.display = "none";
      if (editBtn) editBtn.style.display = "inline-block";
    });
  });

  const btnFecharForm = document.getElementById("btn-fechar-form");
  if (btnFecharForm) {
    btnFecharForm.addEventListener("click", () => {
      window.location.href = "index.php?rota=telaPrincipal";
      document.querySelector(".edicao-container").style.display = "none";

    });
  }

  const btnAtualizarSenha = document.getElementById("btn-atualizar-senha");
  if (btnAtualizarSenha) {
    btnAtualizarSenha.addEventListener("click", () => {
      window.location.href = "index.php?rota=telaPrincipal&secao=atualizacaoSenha";
    });
  }

  const btnExcluir = document.getElementById("btn-excluir");
  if (btnExcluir) {
    btnExcluir.addEventListener("click", () => {
      if (confirm("Tem certeza que deseja excluir sua conta? Esta ação é irreversível.")) {
        const acaoField = document.getElementById("acao");
        if (acaoField) {
          acaoField.value = "excluirUsuario";
          form.submit();
        }
      }
    });
  }

  if (nome) dynamicNameInput(nome);
  if (email) dynamicEmailInput(email);

  if (form) {
    form.addEventListener("submit", (e) => {
      clearSubmitErrors();
      const { errors, firstInvalid } = validateOnSubmit({ nome, email});
      if (errors.length > 0) {
        e.preventDefault();
        showSubmitErrors(errors);
        focusAndSelect(firstInvalid);
      }
    });
  }
});
