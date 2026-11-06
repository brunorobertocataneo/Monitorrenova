# MonitorRenova — demonstração acadêmica

Projeto de demonstração desenvolvido em **PHP 8.3 + MySQL 8.4 + Docker + n8n**, com foco em consulta e acompanhamento de vencimentos de seguros.

**Este repositório contém exclusivamente dados fictícios.** Os 65 nomes de segurados são personagens de anime/desenhos, e os 5 vendedores usam nomes de figuras históricas. Ninguém listado representa um cliente ou vendedor real. As marcas, personagens e personalidades citadas pertencem a seus respectivos titulares e aparecem apenas como exemplos textuais, sem endosso.

## Funcionalidades

- Autenticação com senha armazenada como hash.
- Painel com indicadores e consultas usando PHP/MVC e PDO.
- Filtros por segurado, vendedor, produto e vencimento.
- Histórico de duas competências: outubro/2026 (15 seguros) e novembro/2026 (50 seguros).
- Workflow n8n **independente**, que classifica 8 registros sintéticos com execução manual e sem envio de mensagens.

> O workflow n8n **não** está conectado ao MySQL do painel. Não há integração real com WhatsApp, Google Sheets ou APIs de produção.

## Interface simplificada (novembro/2026)

A interface principal do MonitorRenova tem visual **verde e branco** e um menu reduzido para facilitar a apresentação acadêmica:

1. **Início** (`/`): mostra somente os seguros que vencem hoje e ainda estão para acompanhar, agrupados por vendedor. Renovados e marcados como **Não vai renovar** não aparecem na lista nem no texto do botão **Copiar lista**.
2. **Vendedores** (`/vendedores.php`): subpastas geradas pelos vendedores do MySQL. Cada pasta apresenta os seguros em tabela, com pesquisa.
3. **Consultar seguros** (`/seguros.php`): pesquisa e filtros.

Na pasta de cada vendedor, a coluna **Situação do seguro** permite selecionar **Pendente**, **Renovado** ou **Não vai renovar** e clicar em **Salvar alteração**. O salvamento é autenticado, protegido por CSRF e usa transação PDO/MySQL com controle de versão e histórico. As decisões ficam em `seguro_status_manual` e `seguro_status_manual_historico`, sem sobrescrever a origem importada.

A `VIEW seguro_dashboard` prioriza as decisões locais, preservando-as durante a sincronização de dados. **Não há sincronização de volta para o Google Sheets nem envio automático de mensagens.**

**Instalação nova:** `aplicacao/database/demo-init.sql` já inclui as novas tabelas e a view. **Banco existente:** aplicar `aplicacao/database/migrations/002_status_manual_seguro.sql` antes de executar a nova versão (por exemplo, no MySQL Workbench conectado à instância de demonstração). O script é destinado a MySQL 8.4; faça backup do banco antes de aplicar migrações. Os arquivos PHP sozinhos não substituem uma migração no volume MySQL existente.

Os módulos adicionais exigidos pela especificação acadêmica, como histórico, importação com prévia, competências, revisão e CRUD, continuam no código, mas não aparecem no menu principal. A versão simplificada não altera a lógica desses módulos.

## Orientação a Objetos: Herança e Polimorfismo

O simulador de renovações agora utiliza uma classe abstrata, RegraTriagem, e três
classes filhas: RegraProduto, RegraSituacao e RegraConferencia. Cada uma
sobrescreve avaliar(), chamado de forma polimórfica por SimuladorRenovacao.

- [Explicação e diagrama de classes](aplicacao/docs/POO-HERANCA-POLIMORFISMO.md)
- Código: aplicacao/src/triagem/
- Testes: aplicacao/src/testar-heranca-polimorfismo.php (30 cenários aprovados)
- Regressão: aplicacao/src/testar-simulador-validacao.php

Para executar os testes com a aplicação iniciada:

    docker compose --env-file aplicacao/.env -f aplicacao/compose.demo.yaml exec web php /var/www/src/testar-heranca-polimorfismo.php

Essas regras são somente leitura e não realizam envios ou cobranças.
## CRUD de Tarefas (Programação II)

Uma tela com CRUD completo de **tarefas de acompanhamento** está disponível em
http://localhost:8088/tarefas.php (após login).

- **Create:** cadastra tarefas e permite vincular um seguro **fictício**;
- **Read:** lista e filtra por título/situação, com paginação;
- **Update:** modifica os dados com controle de versão;
- **Delete:** **arquiva logicamente**, sem apagar tarefas ou apólices fisicamente.

Há proteção CSRF nas operações, escape de HTML, PDO parametrizado e autoria.
O módulo é isolado dos registros históricos e **não envia mensagens**.

- [Documentação e demonstração](aplicacao/docs/CRUD-TAREFAS.md).
- [Migração](aplicacao/database/migrations/001_tarefa_acompanhamento.sql)
  para bancos já existentes.
- O demo-init.sql já inclui a tabela para instalações novas.

Testes com rollback (não deixam registros persistidos):

    docker exec aplicacao-web-1 php /var/www/src/testar-crud-tarefas.php
    docker exec aplicacao-web-1 php /var/www/src/testar-renderizacao-tarefas.php

Resultado no ambiente de desenvolvimento: **28/28 testes CRUD e 8/8 testes
de renderização**, sem alterar os 65 seguros fictícios.
## Requisitos

- Docker Desktop com Linux containers e Docker Compose.
- Windows 10/11, Linux ou macOS com Docker disponível.
- Navegador web.

## Executar localmente

1. Faça o clone deste repositório.
2. Na pasta `aplicacao`, copie `.env.example` para `.env` e defina duas **senhas fortes, diferentes e exclusivas**, uma em `DEMO_DB_PASSWORD` e outra em `DEMO_ROOT_PASSWORD`. Não publique o arquivo `.env`.
3. Para iniciar PHP e MySQL, no diretório raiz do repositório:

   ```bash
   docker compose --env-file aplicacao/.env -f aplicacao/compose.demo.yaml up -d --build
   ```

4. Para iniciar o n8n local:

   ```bash
   docker compose -f n8n-demo/compose.yaml up -d
   ```

5. Abra **http://localhost:8088/configurar-acesso.php**, crie a senha de administrador na primeira execução e faça login.
6. Abra **http://localhost:5678** para acessar o n8n. Importe o arquivo `n8n-demo/workflow-demonstracao.json` pela interface do n8n, caso deseje executar a demonstração manualmente.

O banco é inicializado com registros fictícios **somente quando seu volume MySQL é criado pela primeira vez**. A senha do administrador não é fornecida neste repositório.

### Parar sem excluir os dados

```bash
docker compose -f n8n-demo/compose.yaml stop
docker compose --env-file aplicacao/.env -f aplicacao/compose.demo.yaml stop
```

**Não use `down -v`**, que exclui os volumes persistentes.

### Testes

Os scripts CLI selecionados ficam em `aplicacao/src`: `verificar-demo.php`, `testar-telas-demo.php`, `testar-filtros-demo.php` e `verificar-personagens-demo.php`. Após iniciar o serviço:

```bash
docker compose --env-file aplicacao/.env -f aplicacao/compose.demo.yaml exec web php /var/www/src/verificar-demo.php
docker compose --env-file aplicacao/.env -f aplicacao/compose.demo.yaml exec web php /var/www/src/testar-filtros-demo.php
```

## Estrutura

```text
aplicacao/
  public/             Rotas PHP e estilos
  src/                Controllers, repositories, views e testes CLI demonstrativos
  database/           Esquema e dados SQL totalmente fictícios
  docker/             Imagem PHP
  compose.demo.yaml   Serviços web e MySQL
  .env.example        Modelo de configuração SEM senhas
n8n-demo/
  compose.yaml        Serviço n8n local
  workflow-demonstracao.json  Workflow manual e independente (8 exemplos)
```

## Segurança e limitações

Este projeto é **educacional**, não um serviço pronto para produção. Ele não inclui credenciais de integração, backups operacionais, dados de clientes reais, nem tokens de produção. As portas são publicadas somente em `127.0.0.1`, mas os contêineres utilizam rede Docker normal; **isso não significa bloqueio completo de acesso à internet**. Não conecte credenciais ou contatos reais sem uma revisão de segurança adequada.

Apenas nomes de personagens aparecem nos registros; nenhuma imagem, episódio ou arte protegida foi incorporada.

## Licença

Uso demonstrativo acadêmico. Nenhuma licença de código aberto foi atribuída nesta versão; defina uma licença apropriada antes de autorizar redistribuição ou reutilização por terceiros.
