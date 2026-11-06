-- MonitorRenova: status escolhido no painel sem perder na sincronização n8n.
-- Aplicar uma vez em MySQL 8.4. Não remove nem altera registros existentes.
CREATE TABLE IF NOT EXISTS seguro_status_manual (
 competencia CHAR(7) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 linha_origem INT UNSIGNED NOT NULL,
 renovado TINYINT(1) NOT NULL DEFAULT 0,
 nao_vai_renovar TINYINT(1) NOT NULL DEFAULT 0,
 status_atual VARCHAR(30) NOT NULL,
 versao INT UNSIGNED NOT NULL DEFAULT 1,
 atualizado_por BIGINT UNSIGNED NOT NULL,
 atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 PRIMARY KEY (competencia, linha_origem),
 KEY ix_status_manual_autor (atualizado_por,atualizado_em),
 CONSTRAINT fk_status_manual_usuario FOREIGN KEY (atualizado_por) REFERENCES usuario(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS seguro_status_manual_historico (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 competencia CHAR(7) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 linha_origem INT UNSIGNED NOT NULL,
 situacao_anterior VARCHAR(30) NOT NULL,
 situacao_nova VARCHAR(30) NOT NULL,
 usuario_id BIGINT UNSIGNED NOT NULL,
 criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY ix_manual_hist_seguro (competencia,linha_origem,criado_em),
 CONSTRAINT fk_manual_hist_usuario FOREIGN KEY (usuario_id) REFERENCES usuario(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Uma view única para as telas: prioriza decisões locais quando existem.
-- Os dados vindos da planilha permanecem íntegros em suas tabelas originais.
CREATE OR REPLACE SQL SECURITY INVOKER VIEW seguro_dashboard AS
 SELECT s.id AS id,s.linha_origem,s.segurado,s.vendedor,s.vencimento,
        COALESCE(m.renovado,s.renovado) AS renovado,
        s.cobrado,
        COALESCE(m.nao_vai_renovar,s.nao_vai_renovar) AS nao_vai_renovar,
        s.produto,COALESCE(m.status_atual,s.status_atual) AS status_atual,
        s.status_pdf,s.premio_pdf,s.fonte_pdf,s.atualizado_em,
        COALESCE(m.versao,0) AS versao_manual
 FROM seguro_snapshot s
 LEFT JOIN seguro_status_manual m
   ON m.competencia=DATE_FORMAT(s.vencimento,'%Y-%m') AND m.linha_origem=s.linha_origem
 WHERE NOT EXISTS(SELECT 1 FROM sheet_sync_estado WHERE id=1 AND total_registros>0)
 UNION ALL
 SELECT (1000000000+t.linha_origem) AS id,t.linha_origem,t.segurado,t.vendedor,t.vencimento,
        COALESCE(m.renovado,t.renovado) AS renovado,
        t.cobrado,
        COALESCE(m.nao_vai_renovar,t.nao_vai_renovar) AS nao_vai_renovar,
        t.produto,COALESCE(m.status_atual,t.status_atual) AS status_atual,
        t.status_pdf,t.premio_pdf,t.fonte_pdf,t.atualizado_em,
        COALESCE(m.versao,0) AS versao_manual
 FROM sheet_sync_registro_mensal t
 LEFT JOIN seguro_status_manual m
   ON m.competencia=t.competencia AND m.linha_origem=t.linha_origem
 WHERE t.competencia=(SELECT competencia_ativa FROM sheet_sync_estado WHERE id=1)
   AND EXISTS(SELECT 1 FROM sheet_sync_estado WHERE id=1 AND total_registros>0);
