-- Criar o banco de dados (somente cria se ainda não existir)
CREATE DATABASE IF NOT EXISTS Projeto_PIDA
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE Projeto_PIDA;

/* ============================================================
   TABELA: Usuario
   ============================================================ */
CREATE TABLE IF NOT EXISTS Usuario (
    id INT AUTO_INCREMENT PRIMARY KEY, 
    
    nome VARCHAR(150) NOT NULL,

    email VARCHAR(255) NOT NULL UNIQUE,

    senha VARCHAR(255) NOT NULL

)ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

/* ============================================================
   TABELA: Pontos_Interesse
   ============================================================ */
CREATE TABLE IF NOT EXISTS Pontos_Interesse (
    id INT AUTO_INCREMENT PRIMARY KEY,

    osm_id BIGINT NOT NULL,

    osm_type VARCHAR(20) NOT NULL,

    nome VARCHAR(255) NOT NULL,
	
    estado VARCHAR(100) DEFAULT NULL,
	
    email VARCHAR(255) DEFAULT NULL,

    telefone VARCHAR(30) DEFAULT NULL,
	
    website VARCHAR(255) DEFAULT NULL,
	
    endereco TEXT DEFAULT NULL,

    horario_funcionamento VARCHAR(200) DEFAULT NULL,
        
       indice_espacial_overpass POINT SRID 4326 NOT NULL,

    SPATIAL INDEX (indice_espacial_overpass),
    
    UNIQUE KEY uk_osm_id_type (osm_id, osm_type)

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

/* ============================================================
    TABELA: Pesquisa
    ============================================================ */
CREATE TABLE IF NOT EXISTS Pesquisa (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pesquisa_original VARCHAR(255) NOT NULL,
    pesquisa_normalizada VARCHAR(150) NOT NULL,
    estado_normalizado VARCHAR(100) NOT NULL,
    query_hash CHAR(64) NOT NULL UNIQUE,
    osm_id BIGINT,
    osm_type VARCHAR(20),
    indice_espacial_nominatim  POINT SRID 4326 NOT NULL,
    boundingbox POLYGON NOT NULL SRID 4326,
    validade DATETIME NOT NULL,
    nominatim_status ENUM('NULO','VALIDO') NOT NULL,
	overpass_status_alvo ENUM('PENDENTE','NULO','VALIDO') NOT NULL DEFAULT 'PENDENTE',
    overpass_status_entorno ENUM('PENDENTE','NULO','VALIDO') NOT NULL DEFAULT 'PENDENTE',

    SPATIAL INDEX (indice_espacial_nominatim),
    SPATIAL INDEX (boundingbox),
    
	INDEX idx_query_hash (query_hash),
	INDEX idx_semantica (pesquisa_normalizada, estado_normalizado),
	INDEX idx_hash_validade (query_hash, validade)
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

/* ============================================================
    TABELA: Historico
    ============================================================ */
CREATE TABLE IF NOT EXISTS Historico (
    id_usuario INT NOT NULL,
	id_pesquisa INT NOT NULL,
    data_pesquisa DATETIME DEFAULT CURRENT_TIMESTAMP,
    
	FOREIGN KEY (id_usuario) REFERENCES Usuario(id) ON DELETE CASCADE,
	FOREIGN KEY (id_pesquisa) REFERENCES Pesquisa(id) ON DELETE CASCADE,
	UNIQUE KEY uq_usuario_pesquisa (id_usuario, id_pesquisa)
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

/* ============================================================
   TABELA: Roteiro
   ============================================================ */
CREATE TABLE IF NOT EXISTS Roteiro (
    id INT AUTO_INCREMENT PRIMARY KEY,

    data_r DATETIME DEFAULT CURRENT_TIMESTAMP,

    titulo VARCHAR(100) NOT NULL,

	codigo CHAR(32) NOT NULL UNIQUE,
    
    centro  POINT SRID 4326 NOT NULL,
    
    id_usuario INT NOT NULL,

	SPATIAL INDEX (centro),
  
    FOREIGN KEY (id_usuario) REFERENCES Usuario(id) ON DELETE CASCADE,

     UNIQUE idx_rot_user(id, id_usuario)  
     
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

/* ============================================================
   TABELA Associativa: Roteiro_POI
   ============================================================ */
CREATE TABLE IF NOT EXISTS Roteiro_POI (
	id INT AUTO_INCREMENT PRIMARY KEY,
     
    id_roteiro INT NOT NULL,

    id_poi INT NOT NULL,

    dia DATE NOT NULL,

    entrada TIME NOT NULL,

    saida TIME NOT NULL,

    observacoes varchar (200),
       
	CHECK (saida > entrada),
     
	UNIQUE (id_roteiro, dia, entrada, saida),
    
    FOREIGN KEY (id_roteiro) REFERENCES Roteiro(id) ON DELETE CASCADE,
    FOREIGN KEY (id_poi) REFERENCES Pontos_Interesse(id) ON DELETE CASCADE
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;