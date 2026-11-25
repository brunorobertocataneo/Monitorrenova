<div align="center">

# 📊 MonitorRenova

### Acompanhamento de vencimentos e renovações de seguros

**Aplicação acadêmica em PHP e MySQL para consultar apólices fictícias, organizar pendências e registrar o andamento de renovações.**

![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.4-4479A1?logo=mysql&logoColor=white)
![Docker](https://img.shields.io/badge/Docker-Compose-2496ED?logo=docker&logoColor=white)
![n8n](https://img.shields.io/badge/n8n-Demo-EA4B71?logo=n8n&logoColor=white)
![Status](https://img.shields.io/badge/Projeto-Acad%C3%AAmico-228B22)

**[Funcionalidades](#-funcionalidades) · [Tecnologias](#-tecnologias) · [Instalação](#-como-executar) · [Documentação](#-documentação-técnica)**

</div>

---

## Integrantes

- Bruno Roberto Cataneo
- Alexandre da Silva Rocha

## 💡 Sobre o projeto

O **MonitorRenova** foi desenvolvido como demonstração de um sistema de acompanhamento de seguros. A proposta é reunir os vencimentos em um painel, facilitar a consulta por vendedor e registrar decisões de acompanhamento sem modificar os dados importados originalmente.

O projeto também explora **PHP orientado a objetos, padrão MVC, acesso ao banco com PDO, CRUD, histórico auditável, testes e automações demonstrativas no n8n**.

> [!IMPORTANT]
> **Ambiente demonstrativo, sem dados de clientes reais.** A base contém 127 registros fictícios (personagens de anime e desenhos e nomes sintéticos) e cinco vendedores fictícios (nomes de figuras históricas). A presença desses nomes é apenas textual, sem associação ou endosso de seus titulares.

## ✨ Funcionalidades

| Módulo | O que permite fazer |
| --- | --- |
| **Início** | Visualizar seguros pendentes que vencem no dia, agrupados por vendedor, e copiar a lista |
| **Vendedores** | Consultar seguros por vendedor, com tabela e pesquisa |
| **Consultar seguros** | Filtrar registros por segurado, vendedor, produto e vencimento |
| **Situação dos seguros** | Registrar **Pendente**, **Renovado** ou **Não vai renovar** |
| **Histórico de alterações** | Preservar decisões locais com autoria, versão e trilha de mudanças |
| **Tarefas** | Cadastrar, listar, editar e arquivar tarefas com categorias, responsáveis, prazos, recorrência e indicadores |
| **n8n demonstrativo** | Classificar oito exemplos sintéticos em um fluxo manual e independente |

A navegação principal mantém Início, Vendedores e Consultar seguros. A seção Projeto acadêmico oferece acesso ao CRUD de tarefas. As classes de triagem permanecem apenas como exemplo interno de POO e testes CLI, sem página pública.

### 📅 Base de demonstração

- **Outubro/2026:** 15 seguros fictícios.
- **Novembro/2026:** 50 seguros fictícios.
- **Dezembro/2026:** 62 seguros fictícios.
- **Total:** 127 seguros, distribuídos entre cinco vendedores fictícios.

## 🧰 Tecnologias

| Camada | Tecnologia |
| --- | --- |
| Backend | PHP 8.3, programação orientada a objetos e MVC |
| Banco de dados | MySQL 8.4 |
| Persistência | PDO e consultas parametrizadas |
| Interface | HTML, CSS e páginas PHP |
| Infraestrutura local | Docker e Docker Compose |
| Automação de exemplo | n8n |
| Modelagem | UML e diagramas editáveis no diagrams.net |

## 🚀 Como executar

### Pré-requisitos

- Docker Desktop ou Docker Engine com Docker Compose;
- Git;
- Navegador web;
- Windows, Linux ou macOS com Docker configurado.

### 1. Clonar o repositório

```bash
git clone https://github.com/brunorobertocataneo/Monitorrenova.git
cd Monitorrenova
```

### 2. Configurar as senhas do banco

Copie `aplicacao/.env.example` para `aplicacao/.env` e preencha:

```dotenv
DEMO_DB_PASSWORD=defina_uma_senha_forte
DEMO_ROOT_PASSWORD=defina_outra_senha_forte
```

Use **duas senhas diferentes**, exclusivas para esse ambiente. O arquivo `.env` não deve ser enviado ao GitHub.

### 3. Iniciar o painel

Na raiz do repositório:

```bash
docker compose --env-file aplicacao/.env -f aplicacao/compose.demo.yaml up -d --build
```

Abra **http://localhost:8090/configurar-acesso.php** para configurar a senha do administrador na primeira execução. Depois, acesse o painel em **http://localhost:8090/**.

> A aplicação usa a porta local **8090**, conforme o arquivo `aplicacao/compose.demo.yaml`. Os dados iniciais são carregados quando o volume do MySQL é criado pela primeira vez.

### 4. Executar a demonstração opcional do n8n

```bash
docker compose -f n8n-demo/compose.yaml up -d
```

Acesse **http://localhost:5678/** e importe o arquivo `n8n-demo/workflow-demonstracao.json` para executar manualmente o fluxo de exemplo.

**Importante:** esse fluxo **não se conecta ao MySQL do painel** e **não envia mensagens**. Não existe, nesta demonstração, integração operacional com WhatsApp, Google Sheets ou APIs externas de produção.

### Encerrar sem perder os dados

```bash
docker compose -f n8n-demo/compose.yaml stop
docker compose --env-file aplicacao/.env -f aplicacao/compose.demo.yaml stop
```

Evite `docker compose down -v`: a opção `-v` remove os volumes persistentes e pode apagar o banco local.

## 🏗️ Estrutura do repositório

```text
Monitorrenova/
├── aplicacao/
│   ├── public/              # Páginas, rotas e assets
│   ├── src/                 # Regras de negócio, MVC e testes CLI
│   ├── database/            # Esquema, dados demo e migrações
│   ├── docker/              # Imagem de execução PHP
│   ├── docs/                # Documentação dos módulos
│   ├── tests/               # Testes adicionais
│   ├── compose.demo.yaml    # Serviços web e MySQL
│   └── .env.example         # Exemplo de variáveis sem segredos
├── n8n-demo/
│   ├── compose.yaml
│   └── workflow-demonstracao.json
├── diagramas-corrigidos-editaveis/
├── documentação/
└── README.md
```

## 🧠 Conceitos aplicados

**Orientação a objetos:** a triagem utiliza uma interface `RegraAvaliavel`, uma classe abstrata `RegraTriagem` e implementações específicas como `RegraProduto`, `RegraSituacao` e `RegraConferencia`. O projeto também demonstra **herança, polimorfismo, associação, agregação e composição**.

**CRUD de tarefas:** inclui criação, listagem com filtros, atualização e **arquivamento lógico**. O histórico mantém o registro das mudanças e a autoria, sem apagar fisicamente tarefas ou seguros.

**Persistência e segurança:** consultas com PDO parametrizado, autenticação, proteção CSRF, escape de HTML, controle de versão e histórico de operações. As escolhas manuais sobre renovações são armazenadas separadamente da origem importada.

## 🧪 Testes

Com os contêineres em execução, use os comandos abaixo a partir da raiz do projeto:

```bash
# Verificação geral da demonstração
docker compose --env-file aplicacao/.env -f aplicacao/compose.demo.yaml exec web php /var/www/src/verificar-demo.php

# Validação de filtros
docker compose --env-file aplicacao/.env -f aplicacao/compose.demo.yaml exec web php /var/www/src/testar-filtros-demo.php

# Herança e polimorfismo
docker compose --env-file aplicacao/.env -f aplicacao/compose.demo.yaml exec web php /var/www/src/testar-heranca-polimorfismo.php
```

Há também testes do CRUD, da renderização de tarefas, de relacionamentos POO e de requisitos não funcionais. Consulte os scripts em `aplicacao/src/` e a documentação específica.

> Os resultados relatados na documentação acadêmica incluem **28/28 testes CRUD**, **8/8 testes de renderização** e **30 cenários de herança/polimorfismo** aprovados no ambiente de desenvolvimento. Esses números são registros anteriores, não resultados de uma execução realizada por este README.

## 🗄️ Banco de dados e migrações

Uma instalação nova usa `aplicacao/database/demo-init.sql`. Se o MySQL já possui um volume com dados, modificar os arquivos PHP **não aplica automaticamente** mudanças de esquema.

As migrações de módulos existentes ficam em `aplicacao/database/migrations/`, incluindo:

- `001_tarefa_acompanhamento.sql`: estrutura de tarefas;
- `002_status_manual_seguro.sql`: decisões manuais de acompanhamento;
- `003_historico_tarefas.sql`: histórico auditável das tarefas.

- `004_demo_dezembro_2026.sql`: expansão da base fictícia para dezembro.

**Faça backup antes de aplicar qualquer migração** e respeite a ordem e as dependências descritas na documentação.

## 📚 Documentação técnica

- [CRUD de tarefas](aplicacao/docs/CRUD-TAREFAS.md)
- [Herança e polimorfismo](aplicacao/docs/POO-HERANCA-POLIMORFISMO.md)
- [Interfaces e relacionamentos entre classes](aplicacao/docs/RELACIONAMENTOS-POO-PROGRAMACAO-II.md)
- [Diagramas UML editáveis](diagramas-corrigidos-editaveis/README.md)
- [Arquivos acadêmicos](documentação/)

## 🔒 Segurança e limitações

Este repositório é **acadêmico**, não uma solução pronta para produção.

- Não contém credenciais de produção nem dados reais de clientes.
- As portas do painel são publicadas em `127.0.0.1`; isso **não impede, por si só, que contêineres façam conexões externas**.
- O n8n é executado de forma independente, com dados sintéticos e sem envios.
- O sistema **não sincroniza decisões manuais de volta ao Google Sheets**.
- Antes de qualquer implantação real, seriam necessárias revisões de segurança, gestão de credenciais, backups, proteção de dados e integrações.

## 📄 Licenciamento

O repositório está disponibilizado para **demonstração acadêmica**. Não há licença de código aberto atribuída nesta versão. A disponibilização pública do código, por si só, não concede autorização geral para redistribuição ou reutilização.

---

<div align="center">

**MonitorRenova — aprendizado prático com PHP, MySQL, POO e automação demonstrativa.**

[Voltar ao início](#-monitorrenova)

</div>

## Entrega acadêmica

- [Materiais do seminário e artigo](Entrega-academica/)
- [Diagramas UML editáveis](diagramas-corrigidos-editaveis/)
- A página inicial pública apresenta o sistema; o painel e as tarefas exigem autenticação.
- Migração 005: categoria, responsável e recorrência de tarefas. Aplicar uma vez em bancos existentes; novas instalações já incluem os campos.
