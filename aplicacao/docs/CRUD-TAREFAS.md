# MonitorRenova — CRUD de Tarefas de Acompanhamento

**Disciplina:** Programação II (UNOESC)
**Contexto:** extensão acadêmica demonstrativa, usando somente seguros fictícios.
**Rota:** /tarefas.php — autenticação administrativa obrigatória.

## Objetivo

Demonstrar as **quatro operações do CRUD** de forma real no PHP 8.3 e no MySQL 8.4, preservando as apólices e seu histórico.

| CRUD | Operação real | Tela ou classe |
| --- | --- | --- |
| **C — Create** | INSERT INTO tarefa_acompanhamento | Formulário "Cadastrar nova tarefa" |
| **R — Read** | SELECT com filtros, JOIN e paginação | Listagem de tarefas e seguros vinculados |
| **U — Update** | UPDATE com verificação da coluna versao | Link "Editar" |
| **D — Delete** | **Exclusão lógica**, UPDATE excluido_em e excluido_por | "Arquivar" → "Confirmar" |

**Por que não usar DELETE FROM?** O histórico do sistema de seguros precisa ser preservado. A exclusão lógica esconde a tarefa das consultas comuns, mas conserva data, autoria e o registro para auditoria. O CRUD fica completo em termos de comportamento de negócio, embora o D seja implementado com SQL UPDATE, não com remoção física.

## Camadas do módulo (MVC e POO)

- public/tarefas.php: rota HTTP; exige login e token CSRF; trata GET e POST; controla redirecionamentos.
- src/TarefaService.php: classe de negócio que valida título, descrição, prioridade, situação, datas e vínculo com seguro.
- src/TarefaRepository.php: concentra consultas parametrizadas com PDO; implementa INSERT/SELECT/UPDATE e exclusão lógica.
- src/views/tarefas.php: interface HTML, formulários, filtros e tabelas com escape de HTML.
- src/views/partials/sidebar.php: menu "Tarefas (CRUD)".
- database/migrations/001_tarefa_acompanhamento.sql: esquema para atualizar um banco já existente.
- database/demo-init.sql: contém o mesmo CREATE TABLE para instalações novas.

### Modelo relacional resumido

    usuario 1 -------- * tarefa_acompanhamento * -------- 0..1 seguro_mensal
       (criador, atualizador, excluiu)                    (vínculo opcional)

Tabela principal:

- id: identificador automático e chave primária.
- seguro_mensal_id: chave estrangeira opcional; aponta para um dos seguros fictícios.
- titulo, descricao: texto validado pelo PHP.
- prioridade: baixa, media ou alta.
- situacao: pendente, em_andamento ou concluida.
- vencimento: prazo da tarefa, opcional.
- versao: contador para impedir atualizações concorrentes silenciosas.
- criado_por, atualizado_por, excluido_por: chaves estrangeiras para usuario.
- criado_em, atualizado_em, excluido_em: trilha de registro e arquivamento.

As FKs usam ON DELETE RESTRICT: excluir tarefas logicamente **não exclui seguros**.

## Fluxo da apresentação

1. Acesse http://localhost:8088/tarefas.php, com login admin.
2. **CREATE:** cadastre "Conferir vencimento de Itachi Uchiha" e vincule o seguro fictício correspondente, com situação "Pendente".
3. **READ:** busque pelo título, selecione o filtro de situação e mostre a linha no MySQL.
4. **UPDATE:** clique em "Editar", altere para "Em andamento" e confirme a atualização.
5. **DELETE:** clique em "Arquivar" e depois em "Confirmar". A tarefa desaparecerá da lista, mas continuará armazenada com excluido_em e excluido_por.

### Consulta SQL demonstrativa (somente leitura)

    SELECT id, titulo, situacao, versao, excluido_em, excluido_por
    FROM tarefa_acompanhamento
    ORDER BY id DESC;

Assim é possível comprovar que o registro arquivado permanece fisicamente no banco, com data e responsável.

## Segurança

- Exige sessão autenticada usando a classe Auth existente.
- CREATE, UPDATE e DELETE exigem POST e token CSRF.
- PDO com parâmetros, sem concatenar entradas do formulário no SQL.
- htmlspecialchars na View, inclusive textos do usuário.
- Checagem de data real, limites de caracteres e enumerações permitidas.
- UPDATE e DELETE exigem ID, versao atual e não arquivado.
- O registro guarda a autoria e não usa SQL DELETE para remover tarefas.
- Referências a seguros são verificadas por chave estrangeira e por consulta prévia.

## Testes e instalação

**Teste automatizado, com rollback sem deixar registros:**

    docker exec aplicacao-web-1 php /var/www/src/testar-crud-tarefas.php

Resultado verificado no notebook Cataneo: **28 de 28 testes aprovados**, zero alterações definitivas nos dados durante o teste.

Para criar a tabela em um banco **existente**, execute a migração em um usuário com permissão de CREATE TABLE:

    aplicacao/database/migrations/001_tarefa_acompanhamento.sql

Para uma instalação **nova**, o script aplicacao/database/demo-init.sql já inclui a tabela.

**Limite:** módulo demonstrativo local, sem autorização para uso com segurados reais, cobrança, WhatsApp ou outros serviços externos. Não é apresentado como sistema pronto para produção.
