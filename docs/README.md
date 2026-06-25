# 🌎 Projeto Tourismus — Guia de Instalação e Dependências

Este repositório contém o código-fonte do sistema turístico desenvolvido em PHP, utilizando APIs públicas, bibliotecas JavaScript, bibliotecas PHP  e recursos de mapas.  
As pastas `vendor/` e `node_modules/` **não são incluídas no GitHub**, mas podem ser instaladas automaticamente a partir dos arquivos de manifesto (composer.json, composer-lock.json, package.json, package-lock.json).

---

# 📦 1. Dependências do Projeto

O projeto utiliza ferramentas, bibliotecas e APIs externas.  
**As versões e licenças estão descritas no arquivo `LICENÇAS.txt`**.

Antes de começar, certifique-se de que você tem os seguintes softwares instalados e configurados:

### 🔧 Ferramentas de Desenvolvimento
- **XAMPP**  
- **PHP**  
- **MySQL**
- **Composer** 
- **Node.js**  
- **npm**  
- **VS Code Studio**

---

# 📚 2. Dependências Instaladas Automaticamente

As seguintes bibliotecas **não estão incluídas no repositório**, mas serão baixadas automaticamente:

### 📦 PHP (via Composer - composer install)
- `vlucas/phpdotenv` — gerenciamento de variáveis de ambiente.  
- `dompdf/dompdf` — geração de PDFs.  

### 🌐 JavaScript (via npm - npm install)
- **Leaflet** — exibição de mapas.  
- **Leaflet.markercluster** — agrupamento de marcadores.  
- **dom-to-image-more** - captura/conversão de elementos de qualquer nó DOM em imagem.
---

### 🌐 JavaScript/CSS (via CDN)

Diferentemente das bibliotecas anteriores, o consumo desta já é automático.  
- **Choices** — personalização de elementos HTML/DOM.
---

# 🛰 3. APIs/Serviços Externos Públicos Utilizados (sem chaves de API)
- **Nominatim** — geocodificação direta.
- **Overpass API** — consultas ao banco do OpenStreetMap.
- **Tile Server do OpenStreetMap** - fornece tiles raster (imagens de mapa cortadas em quadrados).
---

# 🔐 4. Configuração do Arquivo `.env`
O projeto usa o vlucas/phpdotenv para gerenciar variáveis de ambiente, mantendo credenciais fora do controle de versão.
Crie uma cópia do arquivo de exemplo fornecido e renomeie-o:
cp .env.exemplo mude p/ .env
Edite o novo arquivo .env preenchendo-o de acordo com o arquivo de exemplo.

---

# 🛢️ 5. Configuração do Banco de Dados

Acesse o phpMyAdmin ou outro cliente MySQL.

Importe o arquivo `.sql` presente na pasta `/docs/` para criar o banco e suas tabelas.

---

# 📂 6. Estrutura de Pastas para Arquivos Gerados

- **visoes/pdfs/** → pasta destinada ao armazenamento dos documentos PDF gerados pelo sistema.
 
> Recomenda-se não versionar arquivos pessoais ou PDFs gerados no GitHub, mantendo apenas a estrutura de pastas.

---

#  7.Inventário de Arquivos Copiados

O Projeto Tourismus utilizou marcadores de mapa personalizados da biblioteca **leaflet-color-markers**, armazenados no caminho  
**libs/leaflet_color_markers**. Para cumprir a licença dos marcadores, o texto integral da licença foi incluído no arquivo **LICENSE.txt**.
