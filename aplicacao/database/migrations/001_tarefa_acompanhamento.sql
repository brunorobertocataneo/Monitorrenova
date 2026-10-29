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
