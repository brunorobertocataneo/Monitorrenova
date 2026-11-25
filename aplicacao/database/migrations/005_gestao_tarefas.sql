ALTER TABLE tarefa_acompanhamento
 ADD COLUMN categoria ENUM('geral','comercial','financeiro','administrativo','renovacoes') NOT NULL DEFAULT 'geral',
 ADD COLUMN responsavel VARCHAR(150) NULL,
 ADD COLUMN recorrencia ENUM('nenhuma','diaria','semanal','mensal') NOT NULL DEFAULT 'nenhuma';
