document.addEventListener("DOMContentLoaded", () => {
  
  const form = document.getElementById("form_pesquisa");
  const select = document.getElementById("estado");
  const input = document.getElementById("input_pesquisa");
  const btnPesquisar = document.getElementById("btn_pesquisar");
  const hiddenHash = document.getElementById("query_hash");
  const feedbackContainer = document.getElementById("feedback_container");

  btnPesquisar.classList.add("is_disabled");

  if (!sessionStorage.getItem("delayNivel")) {
    sessionStorage.setItem("delayNivel", "5");
    console.log("Inicialização → nível definido em 5s");
  }
  
  
const mapaEstados = {
    "MS": ["mato grosso do sul", "ms"],
    "MT": ["mato grosso", "mt"],
    "RS": ["rio grande do sul", "rs"],
    "RN": ["rio grande do norte", "rn"],
    "RJ": ["rio de janeiro", "rj"],
    "DF": ["distrito federal", "df"],
    "ES": ["espirito santo", "es"],
    "MG": ["minas gerais", "mg"],
    "SC": ["santa catarina", "sc"],
    "SP": ["sao paulo", "sp"],
    "AC": ["acre", "ac"],
    "AL": ["alagoas", "al"],
    "AP": ["amapa", "ap"],
    "AM": ["amazonas", "am"],
    "BA": ["bahia", "ba"],
    "CE": ["ceara", "ce"],
    "GO": ["goias", "go"],
    "MA": ["maranhao", "ma"],
    "PA": ["para", "pa"],
    "PB": ["paraiba", "pb"],
    "PE": ["pernambuco", "pe"],
    "PI": ["piaui", "pi"],
    "PR": ["parana", "pr"],
    "RO": ["rondonia", "ro"],
    "RR": ["roraima", "rr"],
    "SE": ["sergipe", "se"],
    "TO": ["tocantins", "to"]
};

  let choicesInstance;
  try {
    choicesInstance = new Choices(select, {
      searchEnabled: true,
      itemSelectText: '',
      shouldSort: false,
      position: "bottom",
      placeholder: true,
      placeholderValue: "Estado",
      removeItemButton: true
    });
  } catch (error) {
    console.error("Erro ao inicializar Choices.js:", error);
  }

  const MAX_CARACTERES = 50;

  const SESSION_KEYS = {
    hash: "ultimaQueryHash",
    expira: "pesquisaExpira",
    emAndamento: "pesquisaEmAndamento",
    ultimoFimBloqueio: "ultimoFimBloqueio",
    delayNivel: "delayNivel",
    delayExpira: "delayExpira",
    ultimoTempo: "ultimoTempoPesquisa"
  };

  let errorBalloonEl = null;

function pushNotificacao(msg, ttlMs = 2000) {
  const agora = Date.now();
  const notificacoes = JSON.parse(sessionStorage.getItem("notificacoes") || "[]");
  notificacoes.push({ msg, expira: agora + ttlMs });
  sessionStorage.setItem("notificacoes", JSON.stringify(notificacoes));
}

function renderNotificacao(msg, ttlMs) {
  const balloon = document.createElement("div");
  balloon.className = "pesquisa_balloon";
  balloon.textContent = msg;
  feedbackContainer.appendChild(balloon);

  setTimeout(() => balloon.classList.add("show"), 10);
  setTimeout(() => {
    balloon.classList.remove("show");
    setTimeout(() => balloon.remove(), 300);
  }, ttlMs);
}

function restaurarNotificacoesPersistidas() {
  const agora = Date.now();
  const notificacoes = JSON.parse(sessionStorage.getItem("notificacoes") || "[]");
  const ativas = [];

  notificacoes.forEach(n => {
    const restante = n.expira - agora;
    if (restante > 150) { 
      renderNotificacao(n.msg, restante);
      ativas.push(n);
    }
  });

  sessionStorage.setItem("notificacoes", JSON.stringify(ativas));
}

function mostrarPesquisaBalloon(msg, tempo = 2000) {
  pushNotificacao(msg, tempo);
  renderNotificacao(msg, tempo);
}


let errorHideTimer = null;

function mostrarErrorBalloon(msg) {
  console.warn("[DEBUG] mostrarErrorBalloon:", msg);

  if (!errorBalloonEl) {
    errorBalloonEl = document.createElement("div");
    errorBalloonEl.className = "error_balloon";

    const ul = document.createElement("ul");
    errorBalloonEl.appendChild(ul);

    feedbackContainer.appendChild(errorBalloonEl);
    setTimeout(() => errorBalloonEl.classList.add("show"), 10);
  }

  const ul = errorBalloonEl.querySelector("ul");

  const existing = Array.from(ul.querySelectorAll("li")).map(li => li.textContent.trim());
  if (!existing.includes(msg)) {
    const li = document.createElement("li");
    li.textContent = msg;
    ul.appendChild(li);
  }

  if (errorHideTimer) {
    clearTimeout(errorHideTimer);
    errorHideTimer = null;
  }

  errorHideTimer = setTimeout(() => {
    if (errorBalloonEl) {
      errorBalloonEl.classList.remove("show");
      setTimeout(() => {
        if (errorBalloonEl) {
          errorBalloonEl.remove();
          errorBalloonEl = null;
        }
      }, 300);
    }
    errorHideTimer = null;
  }, 3000);
}

  function limparErrorBalloon(force = false) {
    console.warn("[DEBUG] limparErrorBalloon called, force:", force);
    if (!errorBalloonEl) return;

    if (!force && errorHideTimer) {
      return;
    }

    if (errorHideTimer) {
      clearTimeout(errorHideTimer);
      errorHideTimer = null;
    }

    errorBalloonEl.classList.remove("show");
    setTimeout(() => {
      if (errorBalloonEl) {
        errorBalloonEl.remove();
        errorBalloonEl = null;
      }
    }, 300);
  }

  function contemEmoji(texto) {
    return /\p{Emoji_Presentation}/u.test(texto) || /\p{Extended_Pictographic}/u.test(texto);
  }

    function contarLetras(str) {
      const matches = str.match(/[a-zA-ZÀ-ÿ]/g);
      return matches ? matches.length : 0;
    }

function validarHash(hiddenHash) {
  if (!hiddenHash.value || hiddenHash.value.length < 10) {
    mostrarErrorBalloon("Erro interno: identificador da pesquisa ausente.");
    console.error("Envio cancelado → hidden query_hash inválido.");
    return false; 
  }
  return true; 
}


 const stopwords = new Set([
    "de", "do", "da", "dos", "das", "em", "no", "na", "a", "o", "as", "os",
    "um", "uma", "uns", "umas", "por", "pelo", "pela", "pelos", "pelas",
    "e", "ou", "para", "com", "sem", "cujo", "cuja", "cujos", "cujas", "que", "sob", "sobre",
    "rua", "avenida", "estrada", "rodovia", "travessa", "alameda", "bairro", "quadra", "lote",
    "principal", "geral", "vila", "cidade", "municipio", "setor", "largo", "beco", "via",
    "mercado", "supermercado", "shopping", "posto", "farmacia", "hospital", "delegacia",
    "rodoviaria", "aeroporto", "terminal", "estacao", "galeria", "posto de gasolina",
    "hotel", "restaurante", "bar", "lanchonete", "padaria", "pizzaria", "churrascaria",
    "cafeteria", "lojinha", "quitanda", "mercearia", "conveniencia", "espetaria", "boteco", "pub",
    "escola", "faculdade", "universidade", "biblioteca", "prefeitura", "cartorio",
    "tribunal", "forum", "secretaria", "ministerio", "departamento", "policia", "bombeiros",
    "correios", "posto de saude",
    "igreja", "templo", "capela", "mesquita", "sinagoga", "diocese", "paroquia", "catedral",
    "praia", "praca", "parque", "campo", "clube", "reserva", "jardim", "canto", "ponto",
    "balneario", "ginasio",
    "teste", "abc", "123", "xxx", "lorem", "ipsum", "query", "busca", "pesquisa", "local", "lugar", "nome"
]);
  

function normalizarTexto(texto) {
  if (!texto) return "";

  let t = String(texto).replace(/[_\,]/g, ' ').toLowerCase();

  const acentos = {
    'á':'a','à':'a','ã':'a','â':'a','ä':'a',
    'é':'e','è':'e','ê':'e','ë':'e',
    'í':'i','ì':'i','î':'i','ï':'i',
    'ó':'o','ò':'o','õ':'o','ô':'o','ö':'o',
    'ú':'u','ù':'u','û':'u','ü':'u',
    'ç':'c'
  };
  t = t.split('').map(ch => acentos[ch] || ch).join('');


  t = t.replace(/[^\p{L}\p{N}\s]/gu, ' ');

  Object.values(mapaEstados).flat().forEach(ref => {
    const parts = ref.split(/\s+/);
    const regex = new RegExp('\\b' + parts.join('\\s+') + '\\b', 'gu');
    t = t.replace(regex, ' ');
    t = t.replace(/\s+/gu, ' ').trim();
  });

  return t.replace(/\s+/gu, ' ').trim();
}

  function ehGenerica(texto) {
    const t = normalizarTexto(texto);
    if (!t) return true; 
    const tokens = t.split(" ").filter(Boolean);
    const temRelevante = tokens.some(tok => !stopwords.has(tok));
    return !temRelevante;
  }

  async function sha256(str) {
    const msgBuffer = new TextEncoder().encode(str);
    const hashBuffer = await crypto.subtle.digest("SHA-256", msgBuffer);
    const hashArray = Array.from(new Uint8Array(hashBuffer));
    return hashArray.map(b => b.toString(16).padStart(2, "0")).join("");
  }

  function calcularSimilaridade(p1, p2) {
    if (!p1 || !p2) return 0;
    const t1 = normalizarTexto(p1).split(" ").filter(Boolean);
    const t2 = normalizarTexto(p2).split(" ").filter(Boolean);
    if (!t1.length || !t2.length) return 0;
    if (t1.join(" ") === t2.join(" ")) return 1;
    const s1 = new Set(t1);
    const s2 = new Set(t2);
    const inter = [...s1].filter(x => s2.has(x)).length;
    const uniao = new Set([...s1, ...s2]).size;
    return inter / uniao;
  }
  
 let debounceTimer;
input.addEventListener("input", () => {
  clearTimeout(debounceTimer);

  debounceTimer = setTimeout(() => {
    let valor = input.value.trim(); 

    if (!valor) {
      btnPesquisar.classList.add("is_disabled");
      limparErrorBalloon(true);
      return;
    }

    if (valor.length > MAX_CARACTERES) {
      if (!errorBalloonEl) mostrarErrorBalloon(`Pesquisa muito longa. Use até ${MAX_CARACTERES} caracteres.`);
      btnPesquisar.classList.add("is_disabled");
      return;
    }

    if (contarLetras(valor) < 5) {
      if (!errorBalloonEl) mostrarErrorBalloon("Entrada inválida. Deve conter pelo menos 5 letras.");
      btnPesquisar.classList.add("is_disabled");
      return;
    }

    if (contemEmoji(valor)) {
      if (!errorBalloonEl) mostrarErrorBalloon("Entrada inválida. Emojis não são permitidos.");
      btnPesquisar.classList.add("is_disabled");
      return;
    }

    if (ehGenerica(valor)) {
      if (!errorBalloonEl) mostrarErrorBalloon("Pesquisa genérica demais. Especifique melhor.");
      btnPesquisar.classList.add("is_disabled");
      return;
    }

    btnPesquisar.classList.remove("is_disabled");
    limparErrorBalloon(true);
    console.log("Input válido → botão habilitado");
    mostrarPesquisaBalloon("Verifique se a pesquisa é realmente a que você deseja", 2000);
  }, 1000);
});



input.addEventListener("beforeinput", (e) => {
  const valorInserido = e.data;

  if (valorInserido && !/[\p{L}\p{N}\s]/u.test(valorInserido)) {
    e.preventDefault();
    mostrarErrorBalloon("Caracteres especiais não são permitidos.");
  }
});



  const DALY_STEPS = [5, 10, 15, 20];

    function getdelayNivel() {
    const raw = sessionStorage.getItem(SESSION_KEYS.delayNivel);
    if (!raw) {
      sessionStorage.setItem(SESSION_KEYS.delayNivel, "5");
      return 5;
    }
    const n = parseInt(raw, 10);
    return DALY_STEPS.includes(n) ? n : 5;
  }

  function setdelayNivel(nivel) {
    const n = DALY_STEPS.includes(nivel) ? nivel : 5;
    const duracaoPersistenciaMs = 60000; 
    const agora = Date.now();
    sessionStorage.setItem(SESSION_KEYS.delayNivel, n.toString());
    sessionStorage.setItem(SESSION_KEYS.delayExpira, (agora + duracaoPersistenciaMs).toString());
  }

  function nextStep(nivel) {
    const idx = DALY_STEPS.indexOf(nivel);
    return DALY_STEPS[Math.min(idx + 1, DALY_STEPS.length - 1)];
  }

  function prevStep(nivel) {
    const idx = DALY_STEPS.indexOf(nivel);
    return DALY_STEPS[Math.max(idx - 1, 0)];
  }
  

function registrarComportamentoRapidoSeAplicavel() {
  const fimBloqueioTS = parseInt(
    sessionStorage.getItem(SESSION_KEYS.ultimoFimBloqueio) || "0",
    10
  );

  if (!fimBloqueioTS || Number.isNaN(fimBloqueioTS)) return false;

  const agora = Date.now();
  const delta = agora - fimBloqueioTS;

  return delta > 0 && delta <= 10000;
}

  function ajustarDelayProgressivo() {
    const nivelAtual = getdelayNivel();

    if (!sessionStorage.getItem(SESSION_KEYS.hash)) {
      console.log("Primeira pesquisa → nível fixo em 5s");
      setdelayNivel(5);
      return 5;
    }

    const foiRapido = registrarComportamentoRapidoSeAplicavel();
    const novoNivel = foiRapido ? nextStep(nivelAtual) : prevStep(nivelAtual);

    setdelayNivel(novoNivel);
    console.log("Daly nível atualizado:", novoNivel, "| rápido:", foiRapido);
    return novoNivel;
  }

  function bloquearInputs(duracao = 5000, forcar = false) {
    if (choicesInstance) choicesInstance.disable();
    input.disabled = true;
    btnPesquisar.disabled = true;

    let tempoRestante = Math.ceil(duracao / 1000);
    const originalHTML = btnPesquisar.innerHTML;
    btnPesquisar.classList.add("countdown");
    btnPesquisar.textContent = `Aguarde ${tempoRestante}s`;

    console.log(`Bloqueio iniciado por ${tempoRestante}s`);

    const interval = setInterval(() => {
      tempoRestante--;
      if (tempoRestante > 0) {
        btnPesquisar.textContent = `Aguarde ${tempoRestante}s`;
      } else {
        clearInterval(interval);
        btnPesquisar.classList.remove("countdown");
        btnPesquisar.innerHTML = originalHTML;

        if (choicesInstance) choicesInstance.enable();
        input.disabled = false;
        btnPesquisar.disabled = false;

        sessionStorage.removeItem(SESSION_KEYS.emAndamento);
        sessionStorage.removeItem(SESSION_KEYS.expira);

      const fim = Date.now();
      sessionStorage.setItem(SESSION_KEYS.ultimoFimBloqueio, fim.toString());
      sessionStorage.setItem(SESSION_KEYS.ultimoTempo, fim.toString());


        console.log("Bloqueio liberado. Hash mantido:", sessionStorage.getItem(SESSION_KEYS.hash));
        limparErrorBalloon(true);
      }
    }, 1000);
  }

    restaurarNotificacoesPersistidas();


  const expira = sessionStorage.getItem(SESSION_KEYS.expira);
  if (expira && Date.now() < parseInt(expira, 10)) {
    const restante = parseInt(expira, 10) - Date.now();
    console.warn("Refresh detectado: bloqueio ainda ativo por", Math.ceil(restante / 1000), "s");
    bloquearInputs(restante, true);
    mostrarPesquisaBalloon("Já existe uma pesquisa em andamento", Math.max(1500, restante));
  } else {
    sessionStorage.removeItem(SESSION_KEYS.emAndamento);
    sessionStorage.removeItem(SESSION_KEYS.expira);
    console.log("Nenhum bloqueio ativo.");
  }

  (function verificarDalyNoLoad() {
    const delayExpira = parseInt(sessionStorage.getItem(SESSION_KEYS.delayExpira) || "0", 10);
    const agora = Date.now();

    if (delayExpira && agora >= delayExpira) {
      console.log("Daly expirado no load → resetando para 5s");
      sessionStorage.setItem(SESSION_KEYS.delayNivel, "5");
      sessionStorage.removeItem(SESSION_KEYS.delayExpira);
    } else if (delayExpira && agora < delayExpira) {
      console.log("Daly persistente ativo até:", delayExpira);
    } else {
      console.log("Sem delayExpira definido → manter nível atual ou 5s padrão");
      if (!sessionStorage.getItem(SESSION_KEYS.delayNivel)) {
        sessionStorage.setItem(SESSION_KEYS.delayNivel, "5");
      }
    }
  })();



  btnPesquisar.addEventListener("click", async (e) => { 
    e.preventDefault();

    if (btnPesquisar.classList.contains("is_disabled")) {
      mostrarErrorBalloon("Digite uma pesquisa válida primeiro.");
      return;
    }

    const valorEstado = choicesInstance ? choicesInstance.getValue(true) : select.value;
    if (!valorEstado) {
      mostrarErrorBalloon("Selecione um estado válido.");
      return;
    }

    const valorAtual = input.value.trim();
    if (!contarLetras(valorAtual)) {
      mostrarErrorBalloon("Entrada inválida. O nome deve conter letras.");
      return;
    }
    if (contemEmoji(valorAtual)) {
      mostrarErrorBalloon("Entrada inválida. Emojis não são permitidos.");
      return;
    }
    if (ehGenerica(valorAtual)) {
      mostrarErrorBalloon("Pesquisa genérica demais. Especifique melhor.");
      return;
    }


const estadoNormalizado = String(valorEstado).toLowerCase().replace(/_/g, ' ').trim();

const normalizedStr = normalizarTexto(valorAtual);

const combinado = `${estadoNormalizado}|${normalizedStr}`;
const currentHash = await sha256(combinado);

const lastHash = sessionStorage.getItem(SESSION_KEYS.hash);
const ultimaPesquisaEstado = sessionStorage.getItem("ultimaPesquisaEstado") || "";

const ultimaPesquisa = sessionStorage.getItem("ultimaPesquisa") || "";

const similaridade = calcularSimilaridade(normalizedStr, ultimaPesquisa);

if ((similaridade >= 0.8 && estadoNormalizado === ultimaPesquisaEstado) || (lastHash && lastHash === currentHash)) {
  mostrarErrorBalloon("Você já pesquisou algo muito semelhante no mesmo estado. Altere a pesquisa para continuar.");
  return;
}


hiddenHash.value = currentHash;

if (!validarHash(hiddenHash)) {
  return; 
}


      sessionStorage.setItem("ultimaPesquisa", normalizedStr);
      sessionStorage.setItem("ultimaPesquisaEstado", estadoNormalizado);

if (!sessionStorage.getItem(SESSION_KEYS.hash)) {
  console.log("Hidden query_hash atualizado:", hiddenHash.value);


      sessionStorage.setItem(SESSION_KEYS.hash, currentHash);
      sessionStorage.setItem("ultimaPesquisa", normalizedStr);
      sessionStorage.setItem("ultimaPesquisaEstado", estadoNormalizado);

      sessionStorage.setItem(SESSION_KEYS.ultimoTempo, Date.now().toString());

      sessionStorage.setItem(SESSION_KEYS.delayNivel, "5");
      sessionStorage.setItem(SESSION_KEYS.delayExpira, (Date.now() + 60000).toString());

      const bloqueioTotal = 5000;
      sessionStorage.setItem(SESSION_KEYS.emAndamento, "true");
      sessionStorage.setItem(SESSION_KEYS.expira, (Date.now() + bloqueioTotal).toString());

      console.log("Primeira pesquisa → bloqueio fixo em 5s (delayExpira definido +60s)");
      limparErrorBalloon(true);

   
    form.requestSubmit();

    setTimeout(() => {
        bloquearInputs(bloqueioTotal);
    }, 50);
      return;
    }



  console.log("Hidden query_hash atualizado:", hiddenHash.value);

    sessionStorage.setItem(SESSION_KEYS.hash, currentHash);
    sessionStorage.setItem("ultimaPesquisa", normalizedStr);
    sessionStorage.setItem(SESSION_KEYS.ultimoTempo, Date.now().toString());

const delayExpira = parseInt(sessionStorage.getItem(SESSION_KEYS.delayExpira) || "0", 10);
const agora = Date.now();
if (delayExpira && agora >= delayExpira) {
  console.log("Delay expirado após 60s sem uso → resetando para 5s");
  sessionStorage.setItem(SESSION_KEYS.delayNivel, "5");
  sessionStorage.removeItem(SESSION_KEYS.delayExpira);
  mostrarPesquisaBalloon("Delay expirado após 60s sem uso → resetado para 5s.", 3000);
}


    const dalyTotalSegundos = ajustarDelayProgressivo();
    const bloqueioTotal = dalyTotalSegundos * 1000;

    sessionStorage.setItem(SESSION_KEYS.delayExpira, (Date.now() + 60000).toString());
    sessionStorage.setItem(SESSION_KEYS.emAndamento, "true");
    sessionStorage.setItem(SESSION_KEYS.expira, (Date.now() + bloqueioTotal).toString());

    console.log("Bloqueio total:", dalyTotalSegundos, "s", "| Estado:", valorEstado, "| delayExpira +60s");
   limparErrorBalloon(true);

   
    form.requestSubmit();

    setTimeout(() => {
        bloquearInputs(bloqueioTotal);
    }, 50);
  });

}); 