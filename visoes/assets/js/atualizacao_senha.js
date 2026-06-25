document.addEventListener("DOMContentLoaded", () => {
  const btnValidar = document.getElementById("btn-validar-senha");
  const novaSenha = document.getElementById("nova_senha");
  const confirmarSenha = document.getElementById("confirmar_senha");
  const senhaAtual = document.getElementById("senha_atual");

  const btnCancelar = document.getElementById("btn-cancelar-senha");
  const overlaySenha = document.querySelector(".overlay-senha");
  const containerSenha = document.querySelector(".atualizar-senha-container");

  const campoAcao = document.getElementById("acao-senha");
  const form = document.getElementById("form-atualizar-senha");

  const erroBalao = document.querySelector(".erro-balao");

  const REGEX = {
    SENHA: {
      MIN_LENGTH: /.{8,}/,
      UPPER: /[A-Z]/,
      LOWER: /[a-z]/,
      NUMBER: /\d/,
      SPECIAL: /[^a-zA-Z0-9]/,
    },
  };

  function setEnabled(el, enabled) {
    if (!el) return;
    if (enabled) {
      el.removeAttribute("disabled");
      el.classList.remove("input-disabled");
      el.classList.add("input-enabled");
    } else {
      el.setAttribute("disabled", "disabled");
      el.classList.remove("input-enabled");
      el.classList.add("input-disabled");
    }
  }
 
  function showSubmitErrors(messages) {
    if (!erroBalao) return;
    erroBalao.innerHTML = "";
    erroBalao.style.display = "block";
    (Array.isArray(messages) ? messages : [messages]).forEach((msg) => {
      const p = document.createElement("p");
      p.textContent = msg;
      erroBalao.appendChild(p);
    });
  }

  function clearSubmitErrors() {
    if (erroBalao) {
      erroBalao.innerHTML = "";
      erroBalao.style.display = "none";
    }
  }

  function focusAndSelect(el) {
    if (!el) return;
    el.focus();
    if (el.select) el.select();
    else if (el.setSelectionRange) el.setSelectionRange(0, el.value.length);
    el.scrollIntoView({ behavior: "smooth", block: "center" });
  }

  function updatePasswordRequirements(senhaValue, refs) {
    const { MIN_LENGTH, UPPER, LOWER, NUMBER, SPECIAL } = REGEX.SENHA;
    const checks = [
      { ok: MIN_LENGTH.test(senhaValue), el: refs.comprimento },
      { ok: UPPER.test(senhaValue), el: refs.maiuscula },
      { ok: LOWER.test(senhaValue), el: refs.minuscula },
      { ok: NUMBER.test(senhaValue), el: refs.numero },
      { ok: SPECIAL.test(senhaValue), el: refs.especial },
    ];
    checks.forEach(({ ok, el }) => {
      if (!el) return;
      el.style.color = ok ? "yellow" : "#ffffffff";
    });
  }

  function dynamicPasswordInput(input, refs) {
    input.addEventListener("input", () => {
      const cleaned = input.value.replace(
        /[\u{1F600}-\u{1F64F}\u{1F300}-\u{1F5FF}\u{1F680}-\u{1F6FF}\u{2600}-\u{26FF}]/gu,
        ""
      );
      if (cleaned !== input.value) input.value = cleaned;

      updatePasswordRequirements(input.value, refs);
    });
  }

  function validateOnSubmit(fields) {
    const errors = [];
    let firstInvalid = null;

    if (!fields.senhaAtual.value.trim()) {
      errors.push("A senha atual não pode estar vazia.");
      firstInvalid = firstInvalid || fields.senhaAtual;
    }

    const novaVal = fields.novaSenha.value;
    if (!novaVal) {
      errors.push("A nova senha não pode estar vazia.");
      firstInvalid = firstInvalid || fields.novaSenha;
    } else {
      const req = REGEX.SENHA;
      if (!req.MIN_LENGTH.test(novaVal)) errors.push("Nova senha: mínimo 8 caracteres.");
      if (!req.UPPER.test(novaVal)) errors.push("Nova senha: ao menos uma letra maiúscula.");
      if (!req.LOWER.test(novaVal)) errors.push("Nova senha: ao menos uma letra minúscula.");
      if (!req.NUMBER.test(novaVal)) errors.push("Nova senha: ao menos um número.");
      if (!req.SPECIAL.test(novaVal)) errors.push("Nova senha: ao menos um caractere especial.");
      if (errors.length) firstInvalid = firstInvalid || fields.novaSenha;
    }

    const confVal = fields.confirmarSenha.value;
    if (!confVal) {
      errors.push("A confirmação da senha não pode estar vazia.");
      firstInvalid = firstInvalid || fields.confirmarSenha;
    } else if (confVal !== novaVal) {
      errors.push("A confirmação deve ser igual à nova senha.");
      firstInvalid = firstInvalid || fields.confirmarSenha;
    }

    return { errors, firstInvalid };
  }

  setEnabled(novaSenha, false);
  setEnabled(confirmarSenha, false);

  const params = new URLSearchParams(window.location.search);
  if (params.get("validado") === "1") {
    setEnabled(novaSenha, true);
    setEnabled(confirmarSenha, true);
  }

  if (btnValidar && campoAcao && form) {
    btnValidar.addEventListener("click", () => {
      campoAcao.value = "validarSenhaAtual";
      form.submit();
    });
  }

  if (btnCancelar) {
    btnCancelar.addEventListener("click", () => {
      if (overlaySenha) overlaySenha.remove();
      if (containerSenha) containerSenha.remove();
    });
  }

  const sucessoBalao = document.getElementById("sucesso-balao");
  if (sucessoBalao) {
    setTimeout(() => {
      sucessoBalao.style.opacity = "0";
      setTimeout(() => sucessoBalao.remove(), 500);
    }, 3000);
  }

  const senhaRefs = {
    comprimento: document.getElementById("req-comprimento"),
    maiuscula: document.getElementById("req-maiuscula"),
    minuscula: document.getElementById("req-minuscula"),
    numero: document.getElementById("req-numero"),
    especial: document.getElementById("req-especial"),
  };

  if (novaSenha) {
    dynamicPasswordInput(novaSenha, senhaRefs);
    updatePasswordRequirements(novaSenha.value, senhaRefs);
  }
  if (confirmarSenha) dynamicPasswordInput(confirmarSenha, {});

  if (form) {
    form.addEventListener("submit", (e) => {
      clearSubmitErrors();
      const { errors, firstInvalid } = validateOnSubmit({ senhaAtual, novaSenha, confirmarSenha });
      if (errors.length > 0) {
        e.preventDefault();
        showSubmitErrors(errors);
        focusAndSelect(firstInvalid);
      }
    });
  }
  
});
