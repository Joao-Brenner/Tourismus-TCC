const REGEX = {
  NOME: /^[A-Za-zÀ-ÖØ-öø-ÿçÇ\s]{3,}$/,
  EMAIL: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
  SENHA: {
    MIN_LENGTH: /.{8,}/,
    UPPER: /[A-Z]/,
    LOWER: /[a-z]/,
    NUMBER: /\d/,
    SPECIAL: /[^a-zA-Z0-9]/,
  },
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
    el.style.color = ok ? "#2ecc71" : "#000000";
  });
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
    errors.push("E-mail inválido. Ex.: usuario@dominio.com");
    firstInvalid = firstInvalid || fields.email;
  }

  const senhaVal = fields.senha.value;
  if (!senhaVal) {
    errors.push("A senha não pode estar vazia.");
    firstInvalid = firstInvalid || fields.senha;
  } else {
    const req = REGEX.SENHA;
    const senhaErros = [];
    if (!req.MIN_LENGTH.test(senhaVal)) senhaErros.push("Senha: mínimo 8 caracteres.");
    if (!req.UPPER.test(senhaVal)) senhaErros.push("Senha: ao menos uma letra maiúscula.");
    if (!req.LOWER.test(senhaVal)) senhaErros.push("Senha: ao menos uma letra minúscula.");
    if (!req.NUMBER.test(senhaVal)) senhaErros.push("Senha: ao menos um número.");
    if (!req.SPECIAL.test(senhaVal)) senhaErros.push("Senha: ao menos um caractere especial.");
    if (senhaErros.length) {
      errors.push(...senhaErros);
      firstInvalid = firstInvalid || fields.senha;
    }
  }

  return { errors, firstInvalid };
}

document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("form-cadastro");
  if (!form) return;

  const nome = document.getElementById("nome");
  const email = document.getElementById("email");
  const senha = document.getElementById("senha");

  const senhaRefs = {
    comprimento: document.getElementById("req-comprimento"),
    maiuscula: document.getElementById("req-maiuscula"),
    minuscula: document.getElementById("req-minuscula"),
    numero: document.getElementById("req-numero"),
    especial: document.getElementById("req-especial"),
  };

  if (nome) dynamicNameInput(nome);
  if (email) dynamicEmailInput(email);
  if (senha) {
    dynamicPasswordInput(senha, senhaRefs);
    updatePasswordRequirements(senha.value, senhaRefs);
  }
  
  form.addEventListener("submit", (e) => {
    clearSubmitErrors();
    const { errors, firstInvalid } = validateOnSubmit({ nome, email, senha });
    if (errors.length > 0) {
      e.preventDefault();
      showSubmitErrors(errors);
      focusAndSelect(firstInvalid);
    }
  });
});
