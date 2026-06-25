document.querySelectorAll(".btn-excluir").forEach(btn => {
  btn.addEventListener("click", () => {
    const id = btn.getAttribute("data-id");
    if (confirm("Tem certeza que deseja excluir este histórico? Esta ação é irreversível.")) {
      document.getElementById("form-excluir-" + id).submit();
    }
  });
});

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

document.querySelectorAll("td[data-estado]").forEach(td => {
  const estadoNormalizado = td.getAttribute("data-estado");
  td.textContent = formatarEstado(estadoNormalizado);
});