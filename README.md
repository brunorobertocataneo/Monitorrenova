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
