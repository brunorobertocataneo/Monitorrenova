-- MonitorRenova: esquema e massa TOTALMENTE FICTICIOS para notebook.
-- Nao inclui dump/credenciais/segurados reais da producao.
SET NAMES utf8mb4;

CREATE TABLE usuario (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 login VARCHAR(80) NOT NULL UNIQUE,
 senha_hash VARCHAR(255) NOT NULL,
 criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE seguro_snapshot (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 linha_origem INT UNSIGNED NOT NULL,
 segurado VARCHAR(255) NOT NULL,
 vendedor VARCHAR(180) NOT NULL,
 vencimento DATE NOT NULL,
 renovado TINYINT(1) NOT NULL DEFAULT 0,
 cobrado TINYINT(1) NOT NULL DEFAULT 0,
 nao_vai_renovar TINYINT(1) NOT NULL DEFAULT 0,
 produto VARCHAR(120) NULL,
 status_atual VARCHAR(30) NULL,
 status_pdf VARCHAR(30) NULL,
 premio_pdf DECIMAL(14,2) NULL,
 fonte_pdf VARCHAR(255) NULL,
 atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY uq_snapshot_linha (linha_origem),
 KEY ix_snapshot_vencimento (vencimento),
 KEY ix_snapshot_vendedor (vendedor),
 KEY ix_snapshot_produto (produto)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE importacao_snapshot (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 fonte VARCHAR(120) NOT NULL,
 hash_sha256 CHAR(64) NOT NULL,
 total_registros INT UNSIGNED NOT NULL,
 iniciado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uk_importacao_hash (hash_sha256)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sheet_sync_estado (
 id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
 spreadsheet_id VARCHAR(100) NOT NULL,
 hash_sha256 CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 total_registros INT UNSIGNED NOT NULL,
 ultima_sincronizacao TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 sincronizacoes INT UNSIGNED NOT NULL DEFAULT 1,
 competencia_ativa CHAR(7) CHARACTER SET ascii COLLATE ascii_bin NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sheet_sync_registro (
 linha_origem INT UNSIGNED NOT NULL PRIMARY KEY,
 segurado VARCHAR(255) NOT NULL,
 vendedor VARCHAR(180) NOT NULL,
 vencimento DATE NOT NULL,
 renovado TINYINT(1) NOT NULL DEFAULT 0,
 cobrado TINYINT(1) NOT NULL DEFAULT 0,
 nao_vai_renovar TINYINT(1) NOT NULL DEFAULT 0,
 produto VARCHAR(120) NULL,
 status_atual VARCHAR(30) NULL,
 status_pdf VARCHAR(30) NULL,
 premio_pdf DECIMAL(14,2) NULL,
 fonte_pdf VARCHAR(255) NULL,
 atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 INDEX ix_sync_vencimento (vencimento),
 INDEX ix_sync_vendedor (vendedor)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sheet_sync_registro_mensal (
 competencia CHAR(7) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 linha_origem INT UNSIGNED NOT NULL,
 segurado VARCHAR(255) NOT NULL,
 vendedor VARCHAR(180) NOT NULL,
 vencimento DATE NOT NULL,
 renovado TINYINT(1) NOT NULL DEFAULT 0,
 cobrado TINYINT(1) NOT NULL DEFAULT 0,
 nao_vai_renovar TINYINT(1) NOT NULL DEFAULT 0,
 produto VARCHAR(120) NULL,
 status_atual VARCHAR(30) NULL,
 status_pdf VARCHAR(30) NULL,
 premio_pdf DECIMAL(14,2) NULL,
 fonte_pdf VARCHAR(255) NULL,
 atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 PRIMARY KEY (competencia,linha_origem),
 INDEX ix_mes_vencimento (competencia,vencimento),
 INDEX ix_mes_vendedor (competencia,vendedor)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sheet_sync_transicao (
 competencia CHAR(7) CHARACTER SET ascii COLLATE ascii_bin NOT NULL PRIMARY KEY,
 estado ENUM('pendente','autorizada','concluida') NOT NULL DEFAULT 'pendente',
 hash_observado CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 total_detectado INT UNSIGNED NOT NULL,
 leituras_iguais INT UNSIGNED NOT NULL DEFAULT 1,
 primeiro_aviso TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 ultima_leitura TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 aprovada_em TIMESTAMP NULL,
 concluida_em TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE lote_importacao (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 competencia CHAR(7) NOT NULL,
 origem VARCHAR(150) NOT NULL,
 hash_arquivo CHAR(64) NOT NULL,
 registros INT UNSIGNED NOT NULL,
 criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_lote_comp_hash (competencia,hash_arquivo),
 KEY ix_lote_competencia (competencia)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE seguro_mensal (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 competencia CHAR(7) NOT NULL,
 linha_origem INT UNSIGNED NOT NULL,
 apolice VARCHAR(100) NULL,
 segurado VARCHAR(255) NOT NULL,
 vendedor VARCHAR(180) NOT NULL,
 vencimento DATE NOT NULL,
 produto VARCHAR(120) NULL,
 renovado TINYINT(1) NOT NULL DEFAULT 0,
 cobrado TINYINT(1) NOT NULL DEFAULT 0,
 nao_vai_renovar TINYINT(1) NOT NULL DEFAULT 0,
 status_atual VARCHAR(30) NULL,
 status_pdf VARCHAR(30) NULL,
 premio_pdf DECIMAL(14,2) NULL,
 fonte_pdf VARCHAR(255) NULL,
 lote_id BIGINT UNSIGNED NULL,
 atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY uq_mensal_linha (competencia,linha_origem),
 KEY ix_mensal_competencia (competencia),
 KEY ix_mensal_vencimento (vencimento),
 KEY ix_mensal_vendedor (vendedor),
 KEY ix_mensal_apolice (apolice),
 CONSTRAINT fk_mensal_lote FOREIGN KEY (lote_id) REFERENCES lote_importacao(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE revisao_seguro (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 seguro_mensal_id BIGINT UNSIGNED NOT NULL UNIQUE,
 situacao ENUM('pendente','em_analise','conferido','divergencia') NOT NULL DEFAULT 'pendente',
 observacao VARCHAR(500) NOT NULL DEFAULT '',
 atualizado_por BIGINT UNSIGNED NULL,
 atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 CONSTRAINT fk_revisao_seguro FOREIGN KEY (seguro_mensal_id) REFERENCES seguro_mensal(id),
 CONSTRAINT fk_revisao_usuario FOREIGN KEY (atualizado_por) REFERENCES usuario(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE historico_revisao (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 seguro_mensal_id BIGINT UNSIGNED NOT NULL,
 situacao_anterior VARCHAR(30) NULL,
 situacao_nova VARCHAR(30) NOT NULL,
 observacao_anterior VARCHAR(500) NULL,
 observacao_nova VARCHAR(500) NOT NULL,
 usuario_id BIGINT UNSIGNED NULL,
 criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY ix_hist_seguro (seguro_mensal_id,criado_em),
 CONSTRAINT fk_hist_seguro FOREIGN KEY (seguro_mensal_id) REFERENCES seguro_mensal(id),
 CONSTRAINT fk_hist_usuario FOREIGN KEY (usuario_id) REFERENCES usuario(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE validacao_apolice (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 seguro_mensal_id BIGINT UNSIGNED NOT NULL,
 situacao ENUM('ativa','cancelada','recusada','indeterminada') NOT NULL,
 fonte ENUM('portal_seguradora','sistema_corretora','documento_atual') NOT NULL,
 data_consulta DATE NOT NULL,
 observacao VARCHAR(400) NOT NULL DEFAULT '',
 fingerprint_origem CHAR(64) NOT NULL,
 registrado_por BIGINT UNSIGNED NOT NULL,
 criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY ix_validacao_seguro (seguro_mensal_id,id),
 KEY ix_validacao_data (data_consulta),
 CONSTRAINT fk_validacao_seguro_restrict FOREIGN KEY (seguro_mensal_id) REFERENCES seguro_mensal(id) ON DELETE RESTRICT,
 CONSTRAINT fk_validacao_usuario FOREIGN KEY (registrado_por) REFERENCES usuario(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE auditoria_prontidao (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 competencia CHAR(7) NOT NULL,
 hash_amostra CHAR(64) NOT NULL,
 registros INT UNSIGNED NOT NULL,
 sem_status_atual INT UNSIGNED NOT NULL,
 sem_produto INT UNSIGNED NOT NULL,
 com_status_historico INT UNSIGNED NOT NULL,
 marcados_renovados INT UNSIGNED NOT NULL,
 marcados_cobrados INT UNSIGNED NOT NULL,
 marcados_nao_renovam INT UNSIGNED NOT NULL,
 sinais_conflitantes INT UNSIGNED NOT NULL,
 liberados_envio INT UNSIGNED NOT NULL DEFAULT 0,
 mensagens_enviadas INT UNSIGNED NOT NULL DEFAULT 0,
 origem VARCHAR(100) NOT NULL DEFAULT 'Base mensal local (MySQL)',
 criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_auditoria_mes_hash (competencia,hash_amostra),
 KEY ix_auditoria_competencia (competencia,criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sheet_sync_execucao (
 workflow_id VARCHAR(90) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 execution_id BIGINT UNSIGNED NOT NULL,
 total_registros INT UNSIGNED NOT NULL,
 sem_alteracoes TINYINT(1) NOT NULL,
 confirmado_em TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
 PRIMARY KEY (workflow_id,execution_id),
 INDEX ix_sheet_sync_confirmado (confirmado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE n8n_execucao_resumo (
 workflow_id VARCHAR(90) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 execution_id BIGINT UNSIGNED NOT NULL,
 situacao ENUM('success','error','running','waiting','canceled','unknown') NOT NULL,
 origem ENUM('trigger','cli','manual','other') NOT NULL,
 resultado ENUM('ACEITO_API','DUPLICATA_BLOQUEADA','ERRO','SUCESSO_TECNICO','SEM_CONFIRMACAO','EM_ANDAMENTO') NOT NULL,
 iniciado_em DATETIME NOT NULL,
 terminado_em DATETIME NULL,
 consultado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 PRIMARY KEY(workflow_id,execution_id),
 INDEX ix_n8n_recente (iniciado_em,execution_id),
 INDEX ix_n8n_status (situacao,iniciado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE automacao (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 nome VARCHAR(120) NOT NULL,
 sistema_origem VARCHAR(50) NOT NULL DEFAULT 'n8n',
 referencia_externa VARCHAR(120) NULL,
 descricao TEXT NULL,
 ativo TINYINT(1) NOT NULL DEFAULT 0,
 criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uk_automacao_referencia (referencia_externa)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE execucao (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 automacao_id BIGINT UNSIGNED NOT NULL,
 identificador_externo VARCHAR(120) NULL,
 status ENUM('iniciada','sucesso','falha','cancelada') NOT NULL DEFAULT 'iniciada',
 iniciada_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 terminada_em DATETIME NULL,
 total_processados INT UNSIGNED NOT NULL DEFAULT 0,
 total_elegiveis INT UNSIGNED NOT NULL DEFAULT 0,
 total_pendentes INT UNSIGNED NOT NULL DEFAULT 0,
 total_enviados INT UNSIGNED NOT NULL DEFAULT 0,
 mensagem_erro TEXT NULL,
 criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_execucao_automacao FOREIGN KEY (automacao_id) REFERENCES automacao(id),
 UNIQUE KEY uk_execucao_origem (automacao_id,identificador_externo),
 INDEX ix_execucao_inicio (iniciada_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE evento_execucao (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 execucao_id BIGINT UNSIGNED NOT NULL,
 nivel ENUM('info','aviso','erro') NOT NULL DEFAULT 'info',
 tipo VARCHAR(80) NOT NULL,
 descricao TEXT NOT NULL,
 criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_evento_execucao FOREIGN KEY (execucao_id) REFERENCES execucao(id) ON DELETE CASCADE,
 INDEX ix_evento_data (criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE VIEW seguro_dashboard AS
 SELECT s.id,s.linha_origem,s.segurado,s.vendedor,s.vencimento,
  s.renovado,s.cobrado,s.nao_vai_renovar,s.produto,s.status_atual,
  s.status_pdf,s.premio_pdf,s.fonte_pdf,s.atualizado_em
 FROM seguro_snapshot s
 WHERE NOT EXISTS (SELECT 1 FROM sheet_sync_estado WHERE id=1 AND total_registros>0)
 UNION ALL
 SELECT CAST(1000000000 AS UNSIGNED)+t.linha_origem AS id,
  t.linha_origem,t.segurado,t.vendedor,t.vencimento,
  t.renovado,t.cobrado,t.nao_vai_renovar,t.produto,t.status_atual,
  t.status_pdf,t.premio_pdf,t.fonte_pdf,t.atualizado_em
 FROM sheet_sync_registro_mensal t
 WHERE t.competencia=(SELECT competencia_ativa FROM sheet_sync_estado WHERE id=1)
   AND EXISTS (SELECT 1 FROM sheet_sync_estado WHERE id=1 AND total_registros>0);

-- Somente registro de estado sintetico, nenhuma planilha real.
INSERT INTO sheet_sync_estado (id,spreadsheet_id,hash_sha256,total_registros,ultima_sincronizacao,sincronizacoes,competencia_ativa)
 VALUES (1,'demo-offline-sem-planilha',REPEAT('d',64),50,'2026-01-01 00:00:00',1,'2026-11');

INSERT INTO sheet_sync_registro_mensal (competencia,linha_origem,segurado,vendedor,vencimento,renovado,cobrado,nao_vai_renovar,produto,status_atual,status_pdf,premio_pdf,fonte_pdf) VALUES
 ('2026-10',2,'Tanjiro Kamado','Ada Lovelace','2026-10-08',0,0,0,'Seguro Auto','PENDENTE',NULL,381.15,NULL),
 ('2026-10',3,'Nezuko Kamado','Marie Curie','2026-10-15',0,0,0,'Seguro Residencial','PENDENTE',NULL,412.30,NULL),
 ('2026-10',4,'Zenitsu Agatsuma','Leonardo da Vinci','2026-10-22',0,0,0,'Seguro Empresarial','PENDENTE',NULL,443.45,NULL),
 ('2026-10',5,'Inosuke Hashibira','Albert Einstein','2026-10-29',0,0,0,'Seguro Vida','PENDENTE',NULL,474.60,NULL),
 ('2026-10',6,'Kyojuro Rengoku','Nikola Tesla','2026-10-05',1,0,0,'Seguro Auto','RENOVADO',NULL,505.75,NULL),
 ('2026-10',7,'Giyu Tomioka','Ada Lovelace','2026-10-12',0,0,0,'Seguro Residencial','PENDENTE',NULL,536.90,NULL),
 ('2026-10',8,'Shinobu Kocho','Marie Curie','2026-10-19',0,1,0,'Seguro Empresarial','PENDENTE',NULL,568.05,NULL),
 ('2026-10',9,'Mitsuri Kanroji','Leonardo da Vinci','2026-10-26',0,0,0,'Seguro Vida','PENDENTE',NULL,599.20,NULL),
 ('2026-10',10,'Tengen Uzui','Albert Einstein','2026-10-02',0,0,0,'Seguro Auto','PENDENTE',NULL,630.35,NULL),
 ('2026-10',11,'Muichiro Tokito','Nikola Tesla','2026-10-09',1,0,0,'Seguro Residencial','RENOVADO',NULL,661.50,NULL),
 ('2026-10',12,'Naruto Uzumaki','Ada Lovelace','2026-10-16',0,0,0,'Seguro Empresarial','PENDENTE',NULL,692.65,NULL),
 ('2026-10',13,'Sasuke Uchiha','Marie Curie','2026-10-23',0,0,0,'Seguro Vida','PENDENTE',NULL,723.80,NULL),
 ('2026-10',14,'Sakura Haruno','Leonardo da Vinci','2026-10-30',0,0,1,'Seguro Auto','NAO_RENOVA',NULL,754.95,NULL);

INSERT INTO sheet_sync_registro_mensal (competencia,linha_origem,segurado,vendedor,vencimento,renovado,cobrado,nao_vai_renovar,produto,status_atual,status_pdf,premio_pdf,fonte_pdf) VALUES
 ('2026-10',15,'Kakashi Hatake','Albert Einstein','2026-10-06',0,1,0,'Seguro Residencial','PENDENTE',NULL,786.10,NULL),
 ('2026-10',16,'Hinata Hyuga','Nikola Tesla','2026-10-13',1,0,0,'Seguro Empresarial','RENOVADO',NULL,817.25,NULL),
 ('2026-11',2,'Itachi Uchiha','Ada Lovelace','2026-11-08',0,0,0,'Seguro Vida','PENDENTE',NULL,848.40,NULL),
 ('2026-11',3,'Monkey D. Luffy','Marie Curie','2026-11-15',0,0,0,'Seguro Auto','PENDENTE',NULL,879.55,NULL),
 ('2026-11',4,'Roronoa Zoro','Leonardo da Vinci','2026-11-22',0,0,0,'Seguro Residencial','PENDENTE',NULL,910.70,NULL),
 ('2026-11',5,'Nami','Albert Einstein','2026-11-29',0,0,0,'Seguro Empresarial','PENDENTE',NULL,941.85,NULL),
 ('2026-11',6,'Sanji','Nikola Tesla','2026-11-06',1,0,0,'Seguro Vida','RENOVADO',NULL,973.00,NULL),
 ('2026-11',7,'Nico Robin','Ada Lovelace','2026-11-13',0,1,0,'Seguro Auto','PENDENTE',NULL,1004.15,NULL),
 ('2026-11',8,'Tony Tony Chopper','Marie Curie','2026-11-20',0,0,0,'Seguro Residencial','PENDENTE',NULL,1035.30,NULL),
 ('2026-11',9,'Son Goku','Leonardo da Vinci','2026-11-27',0,0,0,'Seguro Empresarial','PENDENTE',NULL,1066.45,NULL),
 ('2026-11',10,'Vegeta','Albert Einstein','2026-11-04',0,0,0,'Seguro Vida','PENDENTE',NULL,1097.60,NULL),
 ('2026-11',11,'Bulma','Nikola Tesla','2026-11-11',1,0,0,'Seguro Auto','RENOVADO',NULL,1128.75,NULL),
 ('2026-11',12,'Gohan','Ada Lovelace','2026-11-18',0,0,1,'Seguro Residencial','NAO_RENOVA',NULL,1159.90,NULL);

INSERT INTO sheet_sync_registro_mensal (competencia,linha_origem,segurado,vendedor,vencimento,renovado,cobrado,nao_vai_renovar,produto,status_atual,status_pdf,premio_pdf,fonte_pdf) VALUES
 ('2026-11',13,'Piccolo','Marie Curie','2026-11-25',0,0,0,'Seguro Empresarial','PENDENTE',NULL,1191.05,NULL),
 ('2026-11',14,'Kuririn','Leonardo da Vinci','2026-11-02',0,1,0,'Seguro Vida','PENDENTE',NULL,1222.20,NULL),
 ('2026-11',15,'Edward Elric','Albert Einstein','2026-11-09',0,0,0,'Seguro Auto','PENDENTE',NULL,1253.35,NULL),
 ('2026-11',16,'Alphonse Elric','Nikola Tesla','2026-11-16',1,0,0,'Seguro Residencial','RENOVADO',NULL,1284.50,NULL),
 ('2026-11',17,'Winry Rockbell','Ada Lovelace','2026-11-23',0,0,0,'Seguro Empresarial','PENDENTE',NULL,1315.65,NULL),
 ('2026-11',18,'Roy Mustang','Marie Curie','2026-11-30',0,0,0,'Seguro Vida','PENDENTE',NULL,1346.80,NULL),
 ('2026-11',19,'Pikachu','Leonardo da Vinci','2026-11-07',0,0,0,'Seguro Auto','PENDENTE',NULL,1377.95,NULL),
 ('2026-11',20,'Ash Ketchum','Albert Einstein','2026-11-14',0,0,0,'Seguro Residencial','PENDENTE',NULL,1409.10,NULL),
 ('2026-11',21,'Misty','Nikola Tesla','2026-11-21',1,1,0,'Seguro Empresarial','RENOVADO',NULL,1440.25,NULL),
 ('2026-11',22,'Brock','Ada Lovelace','2026-11-28',0,0,0,'Seguro Vida','PENDENTE',NULL,1471.40,NULL),
 ('2026-11',23,'Doraemon','Marie Curie','2026-11-05',0,0,0,'Seguro Auto','PENDENTE',NULL,1502.55,NULL),
 ('2026-11',24,'Nobita Nobi','Leonardo da Vinci','2026-11-12',0,0,0,'Seguro Residencial','PENDENTE',NULL,1533.70,NULL),
 ('2026-11',25,'Sailor Moon','Albert Einstein','2026-11-19',0,0,1,'Seguro Empresarial','NAO_RENOVA',NULL,1564.85,NULL);

INSERT INTO sheet_sync_registro_mensal (competencia,linha_origem,segurado,vendedor,vencimento,renovado,cobrado,nao_vai_renovar,produto,status_atual,status_pdf,premio_pdf,fonte_pdf) VALUES
 ('2026-11',26,'Tuxedo Mask','Nikola Tesla','2026-11-26',1,0,0,'Seguro Vida','RENOVADO',NULL,1596.00,NULL),
 ('2026-11',27,'Light Yagami','Ada Lovelace','2026-11-03',0,0,0,'Seguro Auto','PENDENTE',NULL,1627.15,NULL),
 ('2026-11',28,'L Lawliet','Marie Curie','2026-11-10',0,1,0,'Seguro Residencial','PENDENTE',NULL,1658.30,NULL),
 ('2026-11',29,'Eren Yeager','Leonardo da Vinci','2026-11-17',0,0,0,'Seguro Empresarial','PENDENTE',NULL,1689.45,NULL),
 ('2026-11',30,'Mikasa Ackerman','Albert Einstein','2026-11-24',0,0,0,'Seguro Vida','PENDENTE',NULL,1720.60,NULL),
 ('2026-11',31,'Armin Arlert','Nikola Tesla','2026-11-01',1,0,0,'Seguro Auto','RENOVADO',NULL,1751.75,NULL),
 ('2026-11',32,'Satoru Gojo','Ada Lovelace','2026-11-08',0,0,0,'Seguro Residencial','PENDENTE',NULL,1782.90,NULL),
 ('2026-11',33,'Yuji Itadori','Marie Curie','2026-11-15',0,0,0,'Seguro Empresarial','PENDENTE',NULL,1814.05,NULL),
 ('2026-11',34,'Megumi Fushiguro','Leonardo da Vinci','2026-11-22',0,0,0,'Seguro Vida','PENDENTE',NULL,1845.20,NULL),
 ('2026-11',35,'Nobara Kugisaki','Albert Einstein','2026-11-29',0,1,0,'Seguro Auto','PENDENTE',NULL,1876.35,NULL),
 ('2026-11',36,'Denji','Nikola Tesla','2026-11-06',1,0,0,'Seguro Residencial','RENOVADO',NULL,1907.50,NULL),
 ('2026-11',37,'Power','Ada Lovelace','2026-11-13',0,0,0,'Seguro Empresarial','PENDENTE',NULL,1938.65,NULL),
 ('2026-11',38,'Aki Hayakawa','Marie Curie','2026-11-20',0,0,1,'Seguro Vida','NAO_RENOVA',NULL,1969.80,NULL);

INSERT INTO sheet_sync_registro_mensal (competencia,linha_origem,segurado,vendedor,vencimento,renovado,cobrado,nao_vai_renovar,produto,status_atual,status_pdf,premio_pdf,fonte_pdf) VALUES
 ('2026-11',39,'Bob Esponja','Leonardo da Vinci','2026-11-27',0,0,0,'Seguro Auto','PENDENTE',NULL,2000.95,NULL),
 ('2026-11',40,'Patrick Estrela','Albert Einstein','2026-11-04',0,0,0,'Seguro Residencial','PENDENTE',NULL,2032.10,NULL),
 ('2026-11',41,'Lula Molusco','Nikola Tesla','2026-11-11',1,0,0,'Seguro Empresarial','RENOVADO',NULL,2063.25,NULL),
 ('2026-11',42,'Finn, o Humano','Ada Lovelace','2026-11-18',0,1,0,'Seguro Vida','PENDENTE',NULL,2094.40,NULL),
 ('2026-11',43,'Jake, o Cão','Marie Curie','2026-11-25',0,0,0,'Seguro Auto','PENDENTE',NULL,2125.55,NULL),
 ('2026-11',44,'Princesa Jujuba','Leonardo da Vinci','2026-11-02',0,0,0,'Seguro Residencial','PENDENTE',NULL,2156.70,NULL),
 ('2026-11',45,'Ben Tennyson','Albert Einstein','2026-11-09',0,0,0,'Seguro Empresarial','PENDENTE',NULL,2187.85,NULL),
 ('2026-11',46,'Gwen Tennyson','Nikola Tesla','2026-11-16',1,0,0,'Seguro Vida','RENOVADO',NULL,2219.00,NULL),
 ('2026-11',47,'Scooby-Doo','Ada Lovelace','2026-11-23',0,0,0,'Seguro Auto','PENDENTE',NULL,2250.15,NULL),
 ('2026-11',48,'Salsicha Rogers','Marie Curie','2026-11-30',0,0,0,'Seguro Residencial','PENDENTE',NULL,2281.30,NULL),
 ('2026-11',49,'Homer Simpson','Leonardo da Vinci','2026-11-07',0,1,0,'Seguro Empresarial','PENDENTE',NULL,2312.45,NULL),
 ('2026-11',50,'Bart Simpson','Albert Einstein','2026-11-14',0,0,0,'Seguro Vida','PENDENTE',NULL,2343.60,NULL),
 ('2026-11',51,'Lisa Simpson','Nikola Tesla','2026-11-21',1,0,1,'Seguro Auto','RENOVADO',NULL,2374.75,NULL);

-- O historico mensal tambem contem apenas registros sinteticos.
INSERT INTO seguro_mensal (competencia,linha_origem,segurado,vendedor,vencimento,renovado,cobrado,nao_vai_renovar,produto,status_atual,status_pdf,premio_pdf,fonte_pdf)
 SELECT competencia,linha_origem,segurado,vendedor,vencimento,renovado,cobrado,nao_vai_renovar,produto,status_atual,status_pdf,premio_pdf,fonte_pdf FROM sheet_sync_registro_mensal;

INSERT INTO revisao_seguro (seguro_mensal_id,situacao,observacao)
 SELECT id,'em_analise','Registro ficticio para demonstracao em sala'
 FROM seguro_mensal WHERE competencia='2026-11' AND linha_origem BETWEEN 2 AND 6;

-- Marcador ficticio (nao representa execucao real do n8n).
INSERT INTO automacao (nome,sistema_origem,referencia_externa,descricao,ativo)
 VALUES ('Demonstração acadêmica (desativada)','simulacao','demo-local','Nenhuma integracao real habilitada',0);


-- Modulo CRUD educacional de tarefas; preserva os seguros.
-- MonitorRenova: modulo CRUD academico de tarefas (dados ficticios).
-- Nao altera nem exclui seguros ja existentes.
CREATE TABLE IF NOT EXISTS tarefa_acompanhamento (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    seguro_mensal_id BIGINT UNSIGNED NULL,
    titulo VARCHAR(120) NOT NULL,
    descricao VARCHAR(500) NOT NULL DEFAULT '',
    prioridade ENUM('baixa','media','alta') NOT NULL DEFAULT 'media',
    situacao ENUM('pendente','em_andamento','concluida') NOT NULL DEFAULT 'pendente',
    vencimento DATE NULL,
    versao INT UNSIGNED NOT NULL DEFAULT 1,
    criado_por BIGINT UNSIGNED NOT NULL,
    atualizado_por BIGINT UNSIGNED NOT NULL,
    excluido_por BIGINT UNSIGNED NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    excluido_em DATETIME NULL,
    KEY ix_tarefa_ativa (excluido_em,situacao,vencimento),
    KEY ix_tarefa_seguro (seguro_mensal_id),
    CONSTRAINT fk_tarefa_seguro FOREIGN KEY (seguro_mensal_id)
        REFERENCES seguro_mensal(id) ON DELETE RESTRICT,
    CONSTRAINT fk_tarefa_criador FOREIGN KEY (criado_por)
        REFERENCES usuario(id) ON DELETE RESTRICT,
    CONSTRAINT fk_tarefa_atualizador FOREIGN KEY (atualizado_por)
        REFERENCES usuario(id) ON DELETE RESTRICT,
    CONSTRAINT fk_tarefa_exclusao FOREIGN KEY (excluido_por)
        REFERENCES usuario(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
