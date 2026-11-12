-- MonitorRenova | RN07 e RF05 - historico de tarefas
-- Aplicar apos 001_tarefa_acompanhamento.sql. Nao apaga tarefas anteriores.
-- O demo-init.sql ja possui esta estrutura na instalacao Docker demonstrativa.
CREATE TABLE IF NOT EXISTS historico_tarefa_acompanhamento (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tarefa_id BIGINT UNSIGNED NOT NULL,
    usuario_id BIGINT UNSIGNED NOT NULL,
    acao ENUM('criacao','edicao','arquivamento') NOT NULL,
    versao INT UNSIGNED NOT NULL,
    dados_antes JSON NULL,
    dados_depois JSON NOT NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_historico_tarefa_versao (tarefa_id,versao),
    KEY ix_historico_tarefa_data (tarefa_id,id),
    CONSTRAINT fk_historico_tarefa FOREIGN KEY (tarefa_id)
        REFERENCES tarefa_acompanhamento(id) ON DELETE RESTRICT,
    CONSTRAINT fk_historico_tarefa_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuario(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
