const REGEX = {
  EMAIL: /^[^\s@]+@[^\s@]+\.[^\s@]+$/
};

document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("form-login");
  const emailInput = document.getElementById("email");
  const senhaInput = document.getElementById("senha");

  function showErrorBalloon(erros) {
    const oldBalloon = document.getElementById("error_balloon_submit");
    if (oldBalloon) oldBalloon.remove();

    const balloon = document.createElement("div");
    balloon.id = "error_balloon_submit";
    balloon.className = "error_balloon";

    const ul = document.createElement("ul");
    erros.forEach((erro) => {
      const li = document.createElement("li");
      li.textContent = erro;
      ul.appendChild(li);
    });

    balloon.appendChild(ul);
    document.body.appendChild(balloon);
  }

  form.addEventListener("submit", (event) => {
    let erros = [];

    const email = emailInput.value.trim();
    if (email === "") {
      erros.push("O email não pode estar vazio.");
    } else if (!REGEX.EMAIL.test(email)) {
      erros.push("E-mail inválido. Ex.: usuario@dominio.com");
    }

    const senha = senhaInput.value.trim();
    if (senha === "") {
      erros.push("A senha não pode estar vazia.");
    }

    if (erros.length > 0) {
      event.preventDefault();
      showErrorBalloon(erros);

      if (email === "" || !REGEX.EMAIL.test(email)) {
        emailInput.focus();
      } else if (senha === "") {
        senhaInput.focus();
      }
    }
  });
});
