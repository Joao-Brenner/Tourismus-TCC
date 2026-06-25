const redIcon = new L.Icon({
  iconUrl: '../libs/leaflet_color_markers/marker-icon-2x-red.png',
  shadowUrl: '../node_modules/leaflet/dist/images/marker-shadow.png',
  iconSize: [30, 50],
  iconAnchor: [15, 45],
  popupAnchor: [1, -34],
  shadowSize: [45, 45]
});

const blueIcon = new L.Icon({
  iconUrl: '../node_modules/leaflet/dist/images/marker-icon.png',
  shadowUrl: '../node_modules/leaflet/dist/images/marker-shadow.png',
  iconSize: [25, 41],
  iconAnchor: [12, 41],
  popupAnchor: [1, -34],
  shadowSize: [41, 41]
});

const blackIcon = new L.Icon({
  iconUrl: '../libs/leaflet_color_markers/marker-icon-2x-black.png',
  shadowUrl: '../node_modules/leaflet/dist/images/marker-shadow.png',
  iconSize: [25, 41],
  iconAnchor: [12, 41],
  popupAnchor: [1, -34],
  shadowSize: [41, 41]
});


const ICON_NOTAS =  `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-journal-richtext" viewBox="0 0 16 16">
  <path d="M7.5 3.75a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0m-.861 1.542 1.33.886 1.854-1.855a.25.25 0 0 1 .289-.047L11 4.75V7a.5.5 0 0 1-.5.5h-5A.5.5 0 0 1 5 7v-.5s1.54-1.274 1.639-1.208M5 9.5a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5m0 2a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 0 1h-2a.5.5 0 0 1-.5-.5"/>
  <path d="M3 0h10a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2v-1h1v1a1 1 0 0 0 1 1h10a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H3a1 1 0 0 0-1 1v1H1V2a2 2 0 0 1 2-2"/>
  <path d="M1 5v-.5a.5.5 0 0 1 1 0V5h.5a.5.5 0 0 1 0 1h-2a.5.5 0 0 1 0-1zm0 3v-.5a.5.5 0 0 1 1 0V8h.5a.5.5 0 0 1 0 1h-2a.5.5 0 0 1 0-1zm0 3v-.5a.5.5 0 0 1 1 0v.5h.5a.5.5 0 0 1 0 1h-2a.5.5 0 0 1 0-1z"/>
</svg>`;

  function showErrorBalloon(msg, timeout = 3000) {
    const existing = document.querySelector('.error_balloon');
    if (existing) existing.remove();
    const b = document.createElement('div');
    b.className = 'error_balloon';
    b.innerHTML = `<ul><li>${msg}</li></ul>`;
    document.body.appendChild(b);
    setTimeout(() => b.remove(), timeout);
  }

  function validarDatasUnicas() {
  const dias = Array.from(document.querySelectorAll('.dia-bloco'));
  let ultimaData = null;
  let valido = true;

  dias.forEach(dia => {
    const dataVal = dia.querySelector('.dia-data')?.value;
    if (dataVal) {
      if (ultimaData && dataVal <= ultimaData) {
        showErrorBalloon("A data de um dia posterior deve ser maior que a do dia anterior!");
        dia.querySelector('.dia-data').value = "";
        valido = false;
      }
      ultimaData = dataVal;
    }
  });

  return valido;
}

function validarHorarioMesmoPonto(ponto, idx) {
  const entrada = ponto.querySelectorAll('input.hora-ponto')[0]?.value;
  const saida   = ponto.querySelectorAll('input.hora-ponto')[1]?.value;

  if (entrada && saida && saida <= entrada) {
    showErrorBalloon(`A saída deve ser maior que a entrada!`);
    ponto.querySelectorAll('input.hora-ponto')[1].value = "";
    return false;
  }
  return true;
}

function validarHorarioSequencia(pontos) {
  let ultimaSaida = null;
  let valido = true;

  pontos.forEach((ponto, idx) => {
    const entrada = ponto.querySelectorAll('input.hora-ponto')[0]?.value;
    const saida   = ponto.querySelectorAll('input.hora-ponto')[1]?.value;

    if (ultimaSaida && entrada && entrada <= ultimaSaida) {
      showErrorBalloon(`A entrada do próximo ponto  deve ser maior que a saída do ponto anterior!`);
      ponto.querySelectorAll('input.hora-ponto')[0].value = "";
      valido = false;
    }

    if (saida) ultimaSaida = saida;
  });

  return valido;
}


function validarDataNaoAnterior(inputData) {
  const hojeStr = new Date().toISOString().split("T")[0]; 

  if (inputData < hojeStr) {
    showErrorBalloon("A data não pode ser anterior a hoje!");
    return false;
  }
  return true;
}


function validarHorariosHoje(diaBloco) {
  const dataVal = diaBloco.querySelector('.dia-data')?.value;
  if (!dataVal) return true;

  const hojeStr = new Date().toISOString().split("T")[0];

  if (dataVal !== hojeStr) return true;

  const agora = new Date();
  const horaAtual = agora.toTimeString().slice(0,5); 

  let valido = true;
  const pontos = diaBloco.querySelectorAll('.ponto-item');
  pontos.forEach(p => {
    const entrada = p.querySelectorAll('input.hora-ponto')[0]?.value;
    const saida   = p.querySelectorAll('input.hora-ponto')[1]?.value;

    if ((entrada && entrada < horaAtual) || (saida && saida < horaAtual)) {
      showErrorBalloon("Horários de hoje não podem ser anteriores à hora atual!");
      if (entrada && entrada < horaAtual) p.querySelectorAll('input.hora-ponto')[0].value = "";
      if (saida && saida < horaAtual) p.querySelectorAll('input.hora-ponto')[1].value = "";
      valido = false;
    }
  });

  return valido;
}

function validarHorariosDia(diaBloco) {
  const pontos = Array.from(diaBloco.querySelectorAll('.ponto-item'));
  let valido = true;

  pontos.forEach((ponto, idx) => {
    if (!validarHorarioMesmoPonto(ponto, idx)) valido = false;
  });

  if (!validarHorarioSequencia(pontos)) valido = false;

  if (!validarHorariosHoje(diaBloco)) valido = false;

  return valido;
}

function validarDatasCompletas() {
  const dias = Array.from(document.querySelectorAll('.dia-bloco'));
  let valido = true;

  if (!validarDatasUnicas()) valido = false;

  dias.forEach(dia => {
    const dataVal = dia.querySelector('.dia-data')?.value;
    if (dataVal && !validarDataNaoAnterior(dataVal)) {
      dia.querySelector('.dia-data').value = "";
      valido = false;
    }
  });

  return valido;
}

function validarTitulo() {
  const titulo = document.getElementById('tituloRoteiro')?.value.trim();
  if (!titulo) {
    showErrorBalloon("O título do roteiro não pode estar vazio!");
    return false;
  }
  return true;
}



function atualizarBotaoDia(diaBloco) {
  if (!diaBloco) return;

  const wrapper = diaBloco.querySelector('.pontos-wrapper');
  const btnAddPontoDia = diaBloco.querySelector('.btn-add-ponto');
  const novoPontoLabel = diaBloco.querySelector('.novo-ponto');

  const totalPontos = wrapper.querySelectorAll('.ponto-item').length;

  if (btnAddPontoDia) {
    if (totalPontos > 0) {
      btnAddPontoDia.style.display = 'inline-flex';
      if (novoPontoLabel) novoPontoLabel.style.display = 'none';
    } else {
      btnAddPontoDia.style.display = 'none';
      if (novoPontoLabel) novoPontoLabel.style.display = 'block';
    }
  }
}

function obterMarkerPorId(id, map, markers) {
  if (!id) return null;

  let encontrado = null;

  markers.eachLayer(layer => {
    if (layer.dados && String(layer.dados.id) === String(id)) {
      encontrado = layer;
    }
  });

  if (!encontrado) {
    map.eachLayer(l => {
      if (l instanceof L.Marker && l.dados && String(l.dados.id) === String(id)) {
        encontrado = l;
      }
    });
  }

  return encontrado;
}


function pontoAindaNoRoteiro(id) {
  if (!id) return false;
  const todosPontos = document.querySelectorAll('.ponto-item');
  return Array.from(todosPontos).some(p => String(p.dataset.pontoId) === String(id));
}

function atualizarIconePonto(marker, emRoteiro) {
  if (!marker || !marker.tipo) return;

  if (emRoteiro) {
    marker.setIcon(blackIcon);
  } else {
    if (!pontoAindaNoRoteiro(marker.dados?.id)) {
      if (marker.tipo === "Alvo") {
        marker.setIcon(redIcon);
      } else if (marker.tipo === "Entorno") {
        marker.setIcon(blueIcon);
      } else if (marker.tipo === "RoteiroPOI") {
        marker.setIcon(blueIcon);
      }
    }
  }
}


function validarDatasPreenchidas() {
  const dias = document.querySelectorAll('.dia-bloco');
  for (const dia of dias) {
    const data = dia.querySelector('.dia-data')?.value;
    if (!data) {
      showErrorBalloon("Há dia sem data definida.");
      return false;
    }
  }
  return true;
}

function validarHorariosPreenchidos() {
  const pontos = document.querySelectorAll('.ponto-item');
  for (const p of pontos) {
    const entrada = p.querySelectorAll('input.hora-ponto')[0]?.value;
    const saida = p.querySelectorAll('input.hora-ponto')[1]?.value;
    if (!entrada || !saida) {
      showErrorBalloon("Há ponto sem horário de entrada/saída definido.");
      return false;
    }
  }
  return true;
}


const estadosBrasil = {
  "mato grosso do sul": "Mato Grosso do Sul",
  "mato grosso": "Mato Grosso",
  "rio grande do sul": "Rio Grande do Sul",
  "rio grande do norte": "Rio Grande do Norte",
  "rio de janeiro": "Rio de Janeiro",
  "distrito federal": "Distrito Federal",
  "espirito santo": "Espírito Santo",
  "minas gerais": "Minas Gerais",
  "santa catarina": "Santa Catarina",
  "sao paulo": "São Paulo",
  "acre": "Acre",
  "alagoas": "Alagoas",
  "amapa": "Amapá",
  "amazonas": "Amazonas",
  "bahia": "Bahia",
  "ceara": "Ceará",
  "goias": "Goiás",
  "maranhao": "Maranhão",
  "para": "Pará",
  "paraiba": "Paraíba",
  "pernambuco": "Pernambuco",
  "piaui": "Piauí",
  "parana": "Paraná",
  "rondonia": "Rondônia",
  "roraima": "Roraima",
  "sergipe": "Sergipe",
  "tocantins": "Tocantins"
};



function formatarEstado(estadoNormalizado) {
  if (!estadoNormalizado) return "";
  const chave = estadoNormalizado.toLowerCase(); 
  return estadosBrasil[chave] || estadoNormalizado;
}
function mostrarErro(container, mensagem) {
  container.innerHTML = `
    <div style="display:flex;align-items:center;justify-content:center;height:100%;text-align:center;">
      <h1 style="color:red; font-size:2rem;">
        ${mensagem}<br>
        Tente novamente!!
      </h1>
    </div>
  `;
}


function restaurarMapa({
  map,
  markers,
  btnSalvar,
  originalTooltips,
  selectedMarkers = [],
  hiddenMarkers = [],
  rectsRemovidos = [],
  centerLatLon,
  mode = "tela" 
}) {
  try {
    if (Array.isArray(rectsRemovidos)) {
      rectsRemovidos.forEach(r => { try { r.addTo(map); } catch(e) {} });
    }

    const restoreMarker = (m) => {
      try { if (map.hasLayer(m)) map.removeLayer(m); } catch(e) {}
      const tipo = String(m.tipo || "").toLowerCase();

      const isSpecial =
        (mode === "tela" && tipo === "alvo") ||
        (mode === "edicao" && tipo === "roteiropoi");

      if (isSpecial) {
        try { markers.removeLayer(m); } catch(e) {}
        try { m.addTo(map); } catch(e) {}
      } else {
        try { markers.addLayer(m); } catch(e) {}
      }

      const orig = originalTooltips?.get(m._leaflet_id);
      try { if (m.getTooltip()) m.unbindTooltip(); } catch(e) {}
      if (orig) m.bindTooltip(orig, { direction: "top" });
    };

    if (Array.isArray(selectedMarkers)) selectedMarkers.forEach(restoreMarker);
    if (Array.isArray(hiddenMarkers)) hiddenMarkers.forEach(restoreMarker);

    try {
      if (map.zoomControl && map.zoomControl.getContainer)
        map.zoomControl.getContainer().style.display = "";
    } catch(e) {}

    try {
      if (Array.isArray(centerLatLon) && centerLatLon.length === 2)
        map.setView(centerLatLon, 15);
      else
        map.setZoom(15);
    } catch(e) {}

  } catch(err) {
    console.error("restaurarMapa erro geral", err);
  } finally {
    try { if (btnSalvar) btnSalvar.disabled = false; } catch(e) {}
  }
}

function validarGeojsonData(geojsonData) {
  if (!geojsonData || !geojsonData.features || geojsonData.features.length === 0) {
    return { valido: false, mensagem: "GeoJSON vazio ou inválido" };
  }

  const alvo = geojsonData.features.find(f => f.properties?.Tipo === "Alvo");
  const roteiro = geojsonData.features.find(f => f.properties?.tipo === "Roteiro");

  if (alvo) {
    const props = alvo.properties || {};
    const coords = alvo.geometry?.coordinates || [];
    if (!props.id || !props.Nome || coords.length < 2 || coords[0] == null || coords[1] == null) {
      return { valido: false, mensagem: "Dados insuficientes para renderizar o mapa" };
    }
    return { valido: true, modo: "tela", alvo };
  } else if (roteiro) {
    const props = roteiro.properties || {};
    const coords = roteiro.geometry?.coordinates || [];
    if (!props.id_roteiro || !props.codigo || !props.titulo || coords.length < 2 || coords[0] == null || coords[1] == null)  {
      return { valido: false, mensagem: "Roteiro sem metadados obrigatórios" };
    }
    return { valido: true, modo: "edicao", roteiro };
  }

  return { valido: false, mensagem: "Nenhum 'Alvo' ou 'Roteiro' encontrado" };
}
