# Evidencias da verificacao local - 24/11/2026

Ambiente: DESKTOP-6FBJ4U3; PHP 8.3.36; Docker e MySQL 8.4.
Base Git: c0441f1, com ajustes locais de documentacao, menu e testes.
Banco isolado monitorrenova-entrega: 127 seguros ficticios; 50 na competencia ativa de novembro.

| Suite | Resultado observado |
|---|---|
| Relacionamentos POO | 25/25 |
| Heranca e polimorfismo | 30/30 |
| Simulador | 8/8 |
| CRUD de tarefas | 28/28 apos corrigir a expectativa fixa de 65 seguros |
| Historico de tarefas | 10/10 |
| Filtros | 13/13 |
| Renderizacao de tarefas | 8/8 |
| Verificacao de registros ficticios | 8/8 |
| Filtro historico | Exit 0 |
| Interface simples | Exit 0 |
| Configuracao inicial HTTP | 200 |

A verificacao foi executada com dados ficticios. Nao houve disparos externos.
Nao equivale a homologacao em producao, teste completo de seguranca ou validacao com usuarios.
O script de carga RNF foi ajustado para 127 + 500 = 627 registros, mas a suite de carga nao foi reexecutada nesta verificacao.

## Regressao final
12 suites CLI executadas com codigo de saida 0, incluindo gestao de tarefas (11/11), CRUD (28/28), historico (10/10), renderizacao (8/8), POO (25/25), heranca (30/30), simulador, filtros, historico mensal, interface, telas e registros. Pagina publica HTTP 200; tarefas sem sessao HTTP 303. Suites RNF completas nao reexecutadas.
