let roteiroEnabled = false;
let roteiroTargetDia = null;
const ocorrenciasRemovidas = [];
const ocorrenciasNovas = [];
const ocorrenciasEditadas = [];
const ocorrenciasMantidas = [];
const normalizar = t => (t || "").toString().trim().toLowerCase();


function coletarDadosRoteiro() {
  ocorrenciasNovas.length = 0;
  ocorrenciasEditadas.length = 0;
  ocorrenciasMantidas.length = 0;

  const todosPontos = document.querySelectorAll('.ponto-item');
  todosPontos.forEach(p => {
    const diaBloco = p.closest('.dia-bloco');
    const dia = diaBloco.querySelector('.dia-data')?.value || "";

   const dados = {
  idPoi: p.dataset.pontoId,
  idRoteiroPoi: p.dataset.idRoteiroPoi || null,
  nome: p.querySelector('.ponto-nome')?.innerText || "(sem nome)",
  dia,
  entrada: p.querySelector('input.hora-ponto[title="Entrada"]')?.value || "",
  saida: p.querySelector('input.hora-ponto[title="Saída"]')?.value || "",
  observacoes: p.querySelector('.campo-notas')?.value || "",
  estado: formatarEstado(p.dataset.estado) || "",
  endereco: p.dataset.endereco || "",
  telefone: p.dataset.telefone || "",
  email: p.dataset.email || "",
  website: p.dataset.website || "",
  horarioFuncionamento: p.dataset.horarioFuncionamento || ""
};


  if (dados.idRoteiroPoi) {
  const originalFeature = geojsonData.features.find(
    f => f.properties?.tipo === "RoteiroPOI" &&
         f.properties.ocorrencias?.some(o => String(o.id_roteiro_poi) === String(dados.idRoteiroPoi))
  );

  if (originalFeature) {
    const orig = originalFeature.properties.ocorrencias.find(
      o => String(o.id_roteiro_poi) === String(dados.idRoteiroPoi)
    );


const mudou =
  normalizar(dados.entrada) !== normalizar(orig.entrada) ||
  normalizar(dados.saida) !== normalizar(orig.saida) ||
  normalizar(dados.observacoes) !== normalizar(orig.observacoes) ||
  normalizar(dados.dia) !== normalizar(orig.dia);

    if (mudou) {
      ocorrenciasEditadas.push(dados);
    } else {
      ocorrenciasMantidas.push(dados);
    }
  }
} else {
  ocorrenciasNovas.push(dados);
}


  });

  console.log("=== Ocorrências novas (INSERT) ===", ocorrenciasNovas);
  console.log("=== Ocorrências editadas (UPDATE) ===", ocorrenciasEditadas);
  console.log("=== Ocorrências removidas (DELETE) ===", ocorrenciasRemovidas);
  console.log("=== Ocorrências mantidas (UNCHANGED) ===", ocorrenciasMantidas);

  const roteiroFeature = geojsonData.features.find(f => f.properties?.tipo === "Roteiro");
  const idRoteiro = roteiroFeature?.properties?.id_roteiro || "";
  const codigo = roteiroFeature?.properties?.codigo || "";
  const titulo = document.getElementById('tituloRoteiro')?.value || "";

  const payload = {
    idRoteiro,
    codigo,
    titulo,
    novas: ocorrenciasNovas,
    editadas: ocorrenciasEditadas,
    removidas: ocorrenciasRemovidas,
    mantidas: ocorrenciasMantidas
  };

  return payload;
}

function extrairOcorrenciasOriginais(geojsonData) {
  const orig = [];
  geojsonData.features
    .filter(f => f.properties?.tipo === "RoteiroPOI")
    .forEach(f => {
      const poiId = f.properties.id;
      (f.properties.ocorrencias || []).forEach(o => {
        orig.push({
          idPoi: String(poiId),
          idRoteiroPoi: String(o.id_roteiro_poi),
          dia: o.dia || "",
          entrada: o.entrada || "",
          saida: o.saida || "",
          observacoes: o.observacoes || ""
        });
      });
    });
  return orig;
}

function ocorrenciaIgual(a, b) {
  return String(a.idRoteiroPoi) === String(b.idRoteiroPoi) &&
         (a.dia || "") === (b.dia || "") &&
         (a.entrada || "") === (b.entrada || "") &&
         (a.saida || "") === (b.saida || "") &&
         (a.observacoes || "") === (b.observacoes || "");
}

function houveAlteracaoRoteiro(payload, geojsonData) {
  const roteiroFeature = geojsonData.features.find(f => f.properties?.tipo === "Roteiro");
  const tituloOriginal = roteiroFeature?.properties?.titulo || "";
  const tituloMudou = normalizar(payload.titulo) !== normalizar(tituloOriginal);

  const novos = payload.novas;
  const editados = payload.editadas;
  const removidos = payload.removidas; 

  const houve = tituloMudou || novos.length > 0 || editados.length > 0 || removidos.length > 0;

  console.log("DEBUG alteração -> tituloMudou:", tituloMudou,
              "novos:", novos.length, "editados:", editados.length, "removidos:", removidos.length);

  return houve;
}


document.addEventListener("DOMContentLoaded", () => {

  const mapContainer = document.getElementById("map");

  const roteiroModal = document.getElementById('roteiroModal');
  const wrapper = document.querySelector('.modal-wrapper');
  const btnNovoDia = document.getElementById('btnNovoDia');
  const diasContainer = document.getElementById('diasContainer');
  const fecharRoteiroBtn = document.getElementById('fecharRoteiro');
  const btnSalvar = document.getElementById('btnSalvarRoteiro');
  const tituloInput = document.getElementById('tituloRoteiro');
  
   roteiroEnabled = true;
  roteiroModal.classList.remove('oculto');
  roteiroModal.setAttribute('aria-hidden', 'false');
  wrapper.classList.add('roteiro-aberto');


const resultado = validarGeojsonData(geojsonData);
if (!resultado.valido) {
  mostrarErro(mapContainer, resultado.mensagem);
  return;
}

  if (typeof L === 'undefined') {
    console.error("Leaflet não carregado!");
    mostrarErro(mapContainer, "Leaflet não carregado!");
    return;
  }

  const roteiro = resultado.roteiro;
  const [lon, lat] = roteiro.geometry.coordinates;
  tituloInput.value = roteiro.properties.titulo ;

  const map = L.map('map', {
    center: [lat, lon],
    zoom: 15,
    minZoom: 14,
    maxZoom: 16
  });

let zoomCooldown = false;
const ZOOM_DELAY = 1100; 

map.on('zoomend', () => {
  if (zoomCooldown) return;

  zoomCooldown = true;

  map.scrollWheelZoom.disable();
  map.doubleClickZoom.disable();
  map.touchZoom.disable();
  map.boxZoom.disable();
  if (map.zoomControl) map.zoomControl.disable();

  setTimeout(() => {
    map.scrollWheelZoom.enable();
    map.doubleClickZoom.enable();
    map.touchZoom.enable();
    map.boxZoom.enable();
    if (map.zoomControl) map.zoomControl.enable();

    zoomCooldown = false;
  }, ZOOM_DELAY);
});


 
  if (openMapTiles) {
    L.tileLayer(openMapTiles, {
    attribution: '<a href="https://www.openstreetmap.org/copyright">© OpenStreetMap contributors</a>',
    minZoom: 14,
    maxZoom: 16,
    tileSize: 256,
    noWrap: true,
    updateWhenIdle: true
    }).addTo(map);
  } else {
    console.error("OPEN_STREET_MAP_TILES não definido no .env");
}


  const markers = L.markerClusterGroup({
    disableClusteringAtZoom: 16,
    spiderfyOnMaxZoom: true,
    zoomToBoundsOnClick: true
  });

  markers.on('clusterclick', function (a) {
    if (map.getZoom() === map.getMaxZoom()) {
      a.layer.spiderfy();
      a.originalEvent.preventDefault();
      a.originalEvent.stopPropagation();
    }
  });

  const allCoords = [];
  
  geojsonData.features.forEach(f => {
    const coords = f.geometry.coordinates;
    const props = f.properties;

  allCoords.push([coords[1], coords[0]]);

    if (props.tipo === "RoteiroPOI") {
      const marker = L.marker([coords[1], coords[0]], { icon: blackIcon }).addTo(map);

      marker.dados = props;
      marker.tipo = props.tipo;

      marker.bindPopup(`
        <div style="background-color:black; padding:5px; border-radius:8px; text-align:center;">
          <button class="details-btn" 
                  style="background:none; border:none; color:white; font-weight:bold; cursor:pointer;"
                  data-id="${props.id}" 
                  data-nome="${props.nome}">
            Detalhes
          </button>
        </div>
      `);

      marker.bindTooltip(`${props.nome}`, {
        permanent: false,
        direction: 'top'
          });
          
 } else if (props.tipo === "Entorno") {
      const marker = L.marker([coords[1], coords[0]], { icon: blueIcon });

      marker.dados = props;
      marker.tipo = props.tipo;
      

      marker.bindPopup(`
        <div style="background-color:blue; padding:5px; border-radius:8px; text-align:center;">
          <button class="details-btn" 
                  style="background:none; border:none; color:white; font-weight:bold; cursor:pointer;"
                  data-id="${props.id}" 
                  data-nome="${props.nome}">
            Detalhes
          </button>
        </div>
      `);

      marker.bindTooltip(`${props.nome}`, {
        permanent: false,
        direction: 'top'
          });
      markers.addLayer(marker);

    }
  });

  
  map.addLayer(markers);
  map.setView([lat, lon], 15);

const bounds = L.latLngBounds(allCoords);

const customBounds = L.latLngBounds(
  [bounds.getSouth() - 0.004, bounds.getWest() - 0.004], 
  [bounds.getNorth() + 0.004, bounds.getEast() + 0.004]  
);

map.setMaxBounds(customBounds);
map.options.maxBoundsViscosity = 1.0; 

reconstruirRoteiro(diasContainer, geojsonData);

document.addEventListener("click", e => {
  if (e.target && e.target.classList.contains("details-btn")) {
    const id = e.target.getAttribute("data-id"); 
    const nome = e.target.getAttribute("data-nome");

    const feature = geojsonData.features.find(f => f.properties.id == id);
    if (!feature) {
      console.error("Feature não encontrada para id_poi:", id);
      return;
    }

    const coords = feature.geometry.coordinates;

    const params = { id, nome, lat: coords[1], lon: coords[0] };

    fetch(window.location.origin + '/PADS/publico/index.php?rota=processar_mapa', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams(params)
    })
    .then(response => response.json())
    .then(data => {
      const modal = document.getElementById("infoModal");
      const modalContent = modal.querySelector(".modal-content");

      if (data.success) {
        const d = data.dados;
        let html = `<span class="close-btn">&times;</span>`;
        if (nome) html += `<p><b>Nome:</b> ${nome}</p>`;
        if (d.estado) html += `<p><b>Estado:</b> ${formatarEstado(d.estado)}</p>`;
        if (d.endereco) html += `<p><b>Endereço:</b> ${d.endereco}</p>`;
        if (d.horarioFuncionamento) html += `<p><b>Horário:</b> ${d.horarioFuncionamento}</p>`;
        if (d.email) html += `<p><b>Email:</b> ${d.email}</p>`;
        if (d.telefone) html += `<p><b>Telefone:</b> ${d.telefone}</p>`;
        if (d.website) html += `<p><b>Site:</b> ${d.website}</p>`;

        html += `<button class="btn-add-roteiro">+ Roteiro</button>`
       modalContent.innerHTML = html;


        const btnAddRoteiro = modalContent.querySelector('.btn-add-roteiro');

        if (btnAddRoteiro) {
          btnAddRoteiro.addEventListener('click', () => {
            document.dispatchEvent(new CustomEvent('addToRoteiro', { 
              detail: { id, nome, dados: d } 
            }));
          });
        }

        } else {
          modalContent.innerHTML = `
            <span class="close-btn">&times;</span>
            <p>${data.mensagem || "Não foi possível carregar os detalhes."}</p>
          `;
        }

        modal.style.display = "block";
        modal.querySelector(".close-btn").onclick = () => { modal.style.display = "none"; };
        window.onclick = event => { if (event.target === modal) modal.style.display = "none"; };
      })
      .catch(err => { console.error("Erro ao buscar detalhes:", err); });
    }
  });


function fecharRoteiro() {
  diasContainer.innerHTML = '';
  document.getElementById('tituloRoteiro').value = '';
  roteiroModal.classList.add('oculto');
  roteiroModal.setAttribute('aria-hidden', 'true');
  wrapper.classList.remove('roteiro-aberto');
  roteiroEnabled = false;
  const mapaModal = document.getElementById('mapaModal');
  if (mapaModal) {
    mapaModal.style.display = 'none';
  }
  window.location.href = 'index.php?rota=telaPrincipal';
}


fecharRoteiroBtn.addEventListener('click', () => {
  const ok = confirm('Cancelar edição do roteiro?');
  if (!ok) return;
  fecharRoteiro();
});

    btnNovoDia.addEventListener('click', () => {
    criarDia();
    diasContainer.scrollTop = diasContainer.scrollHeight;
  });

  function criarDia(dataInicial = "") {
  try {
    const diaIndex = diasContainer.querySelectorAll('.dia-bloco').length + 1;
    const diaBloco = document.createElement('div');
    diaBloco.className = 'dia-bloco';

    diaBloco.dataset.dia = dataInicial || diaIndex;

    const btnDiaUp = document.createElement('button');
    btnDiaUp.innerText = '↑';
    btnDiaUp.title = 'Mover dia para cima';
    btnDiaUp.className = 'btn-mover';
    btnDiaUp.addEventListener('click', () => {
      const prev = diaBloco.previousElementSibling;
      if (prev) diasContainer.insertBefore(diaBloco, prev);
      validarDatasUnicas(); 
    });

    const btnDiaDown = document.createElement('button');
    btnDiaDown.innerText = '↓';
    btnDiaDown.title = 'Mover dia para baixo';
    btnDiaDown.className = 'btn-mover';
    btnDiaDown.addEventListener('click', () => {
      const next = diaBloco.nextElementSibling;
      if (next) {
        if (next.nextElementSibling) {
          diasContainer.insertBefore(diaBloco, next.nextElementSibling);
        } else {
          diasContainer.appendChild(diaBloco);
        }
      }
      validarDatasUnicas(); 
    });

    const botoesMoverDia = document.createElement('div');
    botoesMoverDia.className = 'botoes-mover';
    botoesMoverDia.appendChild(btnDiaUp);
    botoesMoverDia.appendChild(btnDiaDown);

    diaBloco.insertBefore(botoesMoverDia, diaBloco.firstChild);

    const h = document.createElement('input');
    h.type = 'date';
    h.className = 'dia-data';
    h.title = 'Dia';

    
    if (dataInicial) h.value = dataInicial;

    h.addEventListener('change', () => {
      validarDatasCompletas();
    });

    const novoPonto = document.createElement('div');
    novoPonto.className = 'novo-ponto';
    novoPonto.innerText = 'Escolha um ponto';

    const btnRemDia = document.createElement('button');
    btnRemDia.className = 'btn-remover-dia';
    btnRemDia.title = 'Remover este dia';
    btnRemDia.innerHTML = '×';

    btnRemDia.addEventListener('click', () => {
  const pontosDoDia = Array.from(diaBloco.querySelectorAll('.ponto-item'));
  const idsDoDia = pontosDoDia.map(p => p.dataset.pontoId);
  const idsRoteiroPoiRemovidos = pontosDoDia.map(p => p.dataset.idRoteiroPoi).filter(id => id);

  ocorrenciasRemovidas.push(...idsRoteiroPoiRemovidos);
  console.log("Ocorrências removidas do dia:", idsRoteiroPoiRemovidos);

  diaBloco.remove();

  idsDoDia.forEach(id => {
    const marker = obterMarkerPorId(id, map, markers);
    atualizarIconePonto(marker, false);
    console.log(`Ponto removido -> id: ${id}`);
  });

  diasContainer.querySelectorAll('.dia-bloco').forEach((bloco, i) => {
    bloco.dataset.dia = i + 1;
    const dataInput = bloco.querySelector('.dia-data');
    console.log(`Reindexado -> dia ${i + 1}, data: ${dataInput?.value || "(sem data)"}`);
  });
});


    const pontosWrapper = document.createElement('div');
    pontosWrapper.className = 'pontos-wrapper';

    const btnAddPontoDia = document.createElement('button');
    btnAddPontoDia.className = 'btn-add-ponto';
    btnAddPontoDia.title = 'Adicionar novo ponto neste dia';
    btnAddPontoDia.innerText = '+';
    btnAddPontoDia.style.display = 'none';

    btnAddPontoDia.addEventListener('click', () => {
      roteiroTargetDia = diaBloco;
      alert('O próximo "+ Roteiro" será adicionado neste dia.');
    });

    diaBloco.appendChild(h);
    diaBloco.appendChild(btnRemDia);
    diaBloco.appendChild(novoPonto);
    diaBloco.appendChild(pontosWrapper);
    diaBloco.appendChild(btnAddPontoDia);

    diasContainer.appendChild(diaBloco);

    return diaBloco; 
  } catch (err) {
    console.error("Erro ao criar dia:", err);
    return null;
  }
}

function adicionarPontoAoUltimoDia(pontoData, diaBloco = null) {
  try {
    let targetDiaBloco = diaBloco || roteiroTargetDia || diasContainer.querySelector('.dia-bloco:last-child');
    let targetWrapper = targetDiaBloco?.querySelector('.pontos-wrapper');
    let novoPontoLabel = targetDiaBloco?.querySelector('.novo-ponto');

    if (!targetWrapper) {
      targetDiaBloco = criarDia(pontoData.dia); 
      targetWrapper = targetDiaBloco.querySelector('.pontos-wrapper');
      novoPontoLabel = targetDiaBloco.querySelector('.novo-ponto');
    }

    const item = document.createElement('div');
    item.className = 'ponto-item';

    const topRow = document.createElement('div');
    topRow.className = 'ponto-top';

    const btnUp = document.createElement('button');
    btnUp.innerText = '↑';
    btnUp.title = 'Mover para cima';
    btnUp.className = 'btn-mover';
    btnUp.addEventListener('click', () => {
      const prev = item.previousElementSibling;
      if (prev) item.parentNode.insertBefore(item, prev);
      validarHorariosDia(item.closest('.dia-bloco')); 
    });

    const btnDown = document.createElement('button');
    btnDown.innerText = '↓';
    btnDown.title = 'Mover para baixo';
    btnDown.className = 'btn-mover';
    btnDown.addEventListener('click', () => {
      const next = item.nextElementSibling;
      if (next) item.parentNode.insertBefore(next, item);
      validarHorariosDia(item.closest('.dia-bloco')); 
    });

    const botoesMover = document.createElement('div');
    botoesMover.className = 'botoes-mover';
    botoesMover.appendChild(btnUp);
    botoesMover.appendChild(btnDown);

    topRow.appendChild(botoesMover);

    const nome = document.createElement('div');
    nome.className = 'ponto-nome';

    const btnNotas = document.createElement('button');
    btnNotas.className = 'btn-notas';
    btnNotas.title = 'Adicionar notas';
    btnNotas.innerHTML = ICON_NOTAS;

    const campoNotas = document.createElement('textarea');
    campoNotas.className = 'campo-notas';
    campoNotas.placeholder = 'Suas observações...';
    campoNotas.maxLength = 200;

    btnNotas.addEventListener('click', () => {
      if (campoNotas.style.display === 'none' || campoNotas.style.display === '') {
        campoNotas.style.display = 'block';
        btnNotas.innerText = '--';
      } else {
        campoNotas.style.display = 'none';
        btnNotas.innerHTML = ICON_NOTAS;
      }
    });

    const entrada = document.createElement('input');
    entrada.type = 'time';
    entrada.className = 'hora-ponto';
    entrada.title = 'Entrada';

    const saida = document.createElement('input');
    saida.type = 'time';
    saida.className = 'hora-ponto';
    saida.title = 'Saída';

    [entrada, saida].forEach(input => {
      input.addEventListener('change', () => {
        const diaBloco = item.closest('.dia-bloco');
        validarHorariosDia(diaBloco);
      });
    });

    const btnRem = document.createElement('button');
    btnRem.className = 'btn-remover-ponto';
    btnRem.title = 'Remover ponto';
    btnRem.innerHTML = '×';

    btnRem.addEventListener('click', () => {
  const diaBloco = item.closest('.dia-bloco');
  const idPoi = item.dataset.pontoId;
  const idRoteiroPoi = item.dataset.idRoteiroPoi; 

  item.remove();

  if (idRoteiroPoi) {
    ocorrenciasRemovidas.push(idRoteiroPoi);
    console.log(`Ocorrência marcada para exclusão -> id_roteiro_poi: ${idRoteiroPoi}`);
  }

  if (diaBloco) {
    const marker = obterMarkerPorId(idPoi, map, markers);
    atualizarIconePonto(marker, false);
    atualizarBotaoDia(diaBloco);

      } else {
        console.warn("DiaBloco não encontrado ao remover ponto");
      }
    });
  
    
    nome.innerText = pontoData.nome;
    entrada.value = pontoData.entrada;
    saida.value = pontoData.saida;
    campoNotas.value = pontoData.observacoes || "";

    item.dataset.pontoId = pontoData.id;
    item.dataset.idRoteiroPoi = pontoData.id_roteiro_poi || '';
    item.dataset.estado = pontoData.estado || pontoData.dados?.estado || '';
    item.dataset.email = pontoData.email || pontoData.dados?.email || '';
    item.dataset.telefone = pontoData.telefone || pontoData.dados?.telefone || '';
    item.dataset.website = pontoData.website || pontoData.dados?.website || '';
    item.dataset.endereco = pontoData.endereco || pontoData.dados?.endereco || '';
    item.dataset.horarioFuncionamento = pontoData.horario_funcionamento || pontoData.dados?.horarioFuncionamento || '';


    console.log("Ponto adicionado:", {
      idPoi: item.dataset.pontoId,
      idRoteiroPoi: item.dataset.idRoteiroPoi, 
      nome: pontoData.nome,
      entrada: pontoData.entrada,
      saida: pontoData.saida,
      observacoes: pontoData.observacoes,
      estado: item.dataset.estado,
      endereco: item.dataset.endereco,
      email: item.dataset.email,
      telefone: item.dataset.telefone,
      website: item.dataset.website,
      horarioFuncionamento: item.dataset.horarioFuncionamento
    });


    topRow.appendChild(nome);
    topRow.appendChild(btnNotas);
    topRow.appendChild(entrada);
    topRow.appendChild(saida);
    topRow.appendChild(btnRem);

    item.appendChild(topRow);
    item.appendChild(campoNotas);

    targetWrapper.appendChild(item);

    const marker = obterMarkerPorId(pontoData.id, map, markers);
    atualizarIconePonto(marker, true);
    atualizarBotaoDia(targetDiaBloco);

    if (novoPontoLabel) {
      novoPontoLabel.style.display = 'none';
    }

    targetWrapper.scrollIntoView({ behavior: 'smooth', block: 'end' });

    roteiroTargetDia = null;
  } catch (err) {
    console.error("Erro ao criar dia:", err);
    return;
  }
}
function reconstruirRoteiro(diasContainer, geojsonData) {
  const ocorrencias = [];

  geojsonData.features
    .filter(f => f.properties.tipo === "RoteiroPOI")
    .forEach(f => {
      const poi = f.properties;
     f.properties.ocorrencias.forEach(o => {
      ocorrencias.push({
        id: poi.id,
        nome: poi.nome,
        estado: poi.estado,
        endereco: poi.endereco,
        horario_funcionamento: poi.horario_funcionamento,
        telefone: poi.telefone,
        email: poi.email,
        website: poi.website,
        dia: o.dia,
        entrada: o.entrada,
        saida: o.saida,
        observacoes: o.observacoes,
        id_roteiro_poi: o.id_roteiro_poi 
      });
      });
    });

  ocorrencias.sort((a, b) => {
    if (a.dia === b.dia) return a.entrada.localeCompare(b.entrada);
    return a.dia.localeCompare(b.dia);
  });

  ocorrencias.forEach(o => {
    let diaBloco = document.querySelector(`.dia-bloco[data-dia="${o.dia}"]`);

    if (!diaBloco) {
      diaBloco = criarDia(o.dia); 
      diasContainer.appendChild(diaBloco);
    }

    adicionarPontoAoUltimoDia(o, diaBloco);
  });
}


document.addEventListener('addToRoteiro', (e) => {
  if (!roteiroEnabled) {
    showErrorBalloon('Clique em Roteiro primeiro');
    return;
  }

  const detalhe = e.detail;
if (!detalhe || !detalhe.id || !detalhe.nome || !detalhe.dados || Object.keys(detalhe.dados).length === 0) {
  showErrorBalloon('Dados do ponto não disponíveis');
  return;
}
  adicionarPontoAoUltimoDia(detalhe);
});


btnSalvar.addEventListener('click', async () => {
  try{

  if (!validarTitulo() || !validarDatasPreenchidas() || !validarHorariosPreenchidos()) {
    return;
  }

  const payload = coletarDadosRoteiro();


if (!houveAlteracaoRoteiro(payload, geojsonData)) {
  showErrorBalloon("Nenhuma alteração significativa no roteiro para salvar.");
  btnSalvar.disabled = false;
  return;
}

const idsSelecionados = new Set([
  ...payload.novas.map(p => String(p.idPoi)),
  ...payload.editadas.map(p => String(p.idPoi)),
  ...payload.mantidas.map(p => String(p.idPoi))        
]);

const originalTooltips = new Map();
const selectedMarkers = [];
const hiddenMarkers = [];

markers.eachLayer(layer => {
  if (!layer.dados || !layer.dados.id) return;
  const mid = String(layer.dados.id);

  originalTooltips.set(
    layer._leaflet_id,
    layer.getTooltip ? (layer.getTooltip() ? layer.getTooltip().getContent() : null) : null
  );

  if (idsSelecionados.has(mid)) {
    selectedMarkers.push(layer);
  } else {
    hiddenMarkers.push(layer);
  }
});

map.eachLayer(l => {
  if (l instanceof L.Marker && l.dados && l.dados.id) {
    const mid = String(l.dados.id);

    if (!originalTooltips.has(l._leaflet_id)) {
      originalTooltips.set(
        l._leaflet_id,
        l.getTooltip ? (l.getTooltip() ? l.getTooltip().getContent() : null) : null
      );

      if (idsSelecionados.has(mid)) {
        selectedMarkers.push(l);
      } else {
        hiddenMarkers.push(l);
      }
    }
  }
});

hiddenMarkers.forEach(m => {
  try { markers.removeLayer(m); } catch(e) {}
  try { if (map.hasLayer(m)) map.removeLayer(m); } catch(e) {}
});

selectedMarkers.forEach(m => {
  try { markers.removeLayer(m); } catch(e) {}
  if (!map.hasLayer(m)) m.addTo(map);
});

      map.closePopup();


  
const todosPontosPayload = [
  ...payload.novas,
  ...payload.editadas,
  ...payload.mantidas
];

const normalized = todosPontosPayload.map((p, idx) => {
  return {
    ...p,
    _idx: idx,
    _dataSort: p.dia || null,
    _entradaSort: p.entrada || "",
    _saidaSort: p.saida || ""
  };
});

normalized.sort((a, b) => {
  if (a._dataSort !== b._dataSort) return a._dataSort.localeCompare(b._dataSort);
  if (a._entradaSort !== b._entradaSort) return a._entradaSort.localeCompare(b._entradaSort);
  return a._saidaSort.localeCompare(b._saidaSort);
});

const posicoesPorId = {};
let contador = 1;
normalized.forEach(n => {
  const id = String(n.idPoi); 
  if (!posicoesPorId[id]) posicoesPorId[id] = [];
  posicoesPorId[id].push(contador);
  contador++;
});

selectedMarkers.forEach(m => {
  const mid = String(m.dados.id);
  const posArr = posicoesPorId[mid] || [];
  const posText = posArr.length ? posArr.join(', ') : '';

  try { if (m.getTooltip()) m.unbindTooltip(); } catch(e) {}

  m.bindTooltip(posText, { permanent: true, direction: 'top' });

  try { m.openTooltip(); } catch(e) {}
});


if (map.zoomControl && map.zoomControl.getContainer) {
  map.zoomControl.getContainer().style.display = 'none';
}

try {
  const latlngs = selectedMarkers.map(m => m.getLatLng());

  const MIN_ZOOM = 13;
  const MAX_ZOOM = 16;
  const ANIM_WAIT_MS = 700;

  if (!latlngs.length) {
  restaurarMapa({
  map,
  markers,
  btnSalvar,
  originalTooltips,
  selectedMarkers,
  hiddenMarkers,
  centerLatLon: [lat, lon],
  mode: "edicao"
});
  return;
}


  if (latlngs.length === 1) {
    map.setView(latlngs[0], MAX_ZOOM, { animate: true });
    await new Promise(r => setTimeout(r, ANIM_WAIT_MS));
  } else {
    const bounds = L.latLngBounds(latlngs);

    map.fitBounds(bounds, {
      paddingTopLeft: [30, 90],     
      paddingBottomRight: [30, 50], 
      maxZoom: MAX_ZOOM,
      animate: true
    });

    if (map.getZoom() < MIN_ZOOM) {
      map.setZoom(MIN_ZOOM);
    }

    await new Promise(r => setTimeout(r, ANIM_WAIT_MS));
  }

} catch (err) {
  console.error("Erro ao ajustar bounds do mapa:", err);
 restaurarMapa({
  map,
  markers,
  btnSalvar,
  originalTooltips,
  selectedMarkers,
  hiddenMarkers,
  centerLatLon: [lat, lon],
  mode: "edicao"
});

  showErrorBalloon("A renderização do mapa para conversão em imagem falhou.");
  return;
}


const mapNode = document.getElementById('map');
if (!mapNode) {
  showErrorBalloon('Elemento do mapa não encontrado para gerar imagem.');
  btnSalvar.disabled = false;
  return;
}

const width = mapNode.offsetWidth;
const height = mapNode.offsetHeight;

let dataUrl = null;
try {
  dataUrl = await domtoimage.toPng(mapNode, { width, height, bgcolor: null });

} catch (err) {
  console.error('Erro ao gerar imagem:', err);
  restaurarMapa({
  map,
  markers,
  btnSalvar,
  originalTooltips,
  selectedMarkers,
  hiddenMarkers,
  centerLatLon: [lat, lon],
  mode: "edicao"
});
  showErrorBalloon('Erro ao gerar imagem do mapa.');
  return;
}

await new Promise(r => setTimeout(r, 800));
restaurarMapa({
  map,
  markers,
  btnSalvar,
  originalTooltips,
  selectedMarkers,
  hiddenMarkers,
  centerLatLon: [lat, lon],
  mode: "edicao"
});


try {

const roteiroFeature = geojsonData.features.find(f => f.properties?.tipo === "Roteiro");
const tituloOriginal = roteiroFeature?.properties?.titulo || "";
const tituloMudou = normalizar(payload.titulo) !== normalizar(tituloOriginal);

const roteiroPayload = {
  novas: payload.novas,
  editadas: payload.editadas,
  removidas: payload.removidas,
  mantidas: payload.mantidas,
  titulo: tituloMudou ? payload.titulo : tituloOriginal 
};


const form = new FormData();
form.append('acao', 'editarRoteiro');
form.append('roteiro', JSON.stringify(roteiroPayload));
form.append('id_roteiro', payload.idRoteiro);
form.append('codigo', payload.codigo);
form.append('titulo', roteiroPayload.titulo);


  if (!dataUrl) {
    showErrorBalloon("Imagem do mapa não disponível para envio.");
    return;
  }

  let blob;
  try {
    blob = await (await fetch(dataUrl)).blob();
  } catch (err) {
    console.error("Erro ao converter imagem em Blob:", err);
    showErrorBalloon("Falha na conversão da imagem do mapa.");
    return;
  }

  if (!blob) {
    showErrorBalloon("Imagem do mapa não pôde ser processada.");
    return;
  }

  form.append('imagem', blob, 'mapa.png');

  const resp = await fetch(
    window.location.origin + '/PADS/publico/index.php?rota=processar_roteiro',
    {
      method: 'POST',
      body: form
    }
  );

 const text = await resp.text();
  const data = JSON.parse(text);
  if (data.status === 'erro') {
    showErrorBalloon(data.mensagem || "Erro inesperado ao processar roteiro.");
} else {
  console.log("Resposta processar_roteiro:", data);

    window.open(data.pdfUrl, "_blank");

  setTimeout(() => {
      fecharRoteiro();
  }, 800);
}


} catch (err) {
  console.error("Erro no envio do roteiro:", err);
  showErrorBalloon("Erro ao preparar ou enviar o roteiro.");
  return;
}

  } catch (err) {
    console.error('Erro no fluxo de salvar roteiro:', err);
    showErrorBalloon('Erro ao salvar roteiro.');
    return;

  }


});

});
