# Diagramas UML — MonitorRenova

**Pasta única para arquivos editáveis do diagrams.net.**

As versões originais foram organizadas para evitar arquivos duplicados na raiz ou na antiga pasta `aplicacao/docs/uml`. A pasta `documentação` guarda relatórios acadêmicos e não é uma duplicação desta pasta.

## Diagramas principais (versão integrada atualmente)

1. `01-casos-de-uso.drawio` — casos de uso acadêmicos.
2. `02-classes-conceituais.drawio` — classes conceituais.
3. `03-classes-php-crud.drawio` — classes PHP e CRUD de tarefas.
4. `04-heranca-polimorfismo.drawio` — interface, herança e relacionamentos POO.
5. `05-modelo-relacional.drawio` — modelo relacional de tarefas e histórico.
6. `06-sequencia-cadastro.drawio` — sequência de cadastro de tarefa (versão histórica).
7. `07-sequencia-edicao-alt.drawio` — sequência de edição de tarefa com `alt` (versão histórica).

## Modelos complementares preservados

- `10-modelos-status-manual-complementares.drawio`: quatro páginas de modelos PHP/POO, sequência da atualização da situação do seguro, banco de decisões/status e casos de uso da interface simplificada. **Preservado por ter conteúdo distinto** dos sete diagramas principais.

## Revisão acadêmica em andamento

O Pull Request [#5](https://github.com/brunorobertocataneo/Monitorrenova/pull/5) alinha os casos de uso e sequências à especificação original da professora, com UC02 para consulta e UC05 para importação, além de preservar cadastro/edição como extensões. Quando essa revisão for aprovada e integrada, usar apenas os nomes atuais definidos pelo PR #5 e o arquivo consolidado dentro desta pasta.

## Como abrir

Entre em [diagrams.net](https://app.diagrams.net/), selecione **Arquivo → Abrir de → Dispositivo** e escolha um arquivo `.drawio`. Os diagramas continuam editáveis.

**Observação de rastreabilidade:** RF05 e UC04 na especificação V7 tratam de histórico mensal, não de histórico de tarefas. A revisão do PR #5 esclarece essa distinção.
