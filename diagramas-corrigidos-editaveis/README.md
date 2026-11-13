# MonitorRenova — Diagramas UML finais editáveis

Pasta única dos arquivos `.drawio` para a banca da UNOESC. Os diagramas 01–09 seguem a especificação de Engenharia de Software I e os módulos de Programação II. Todos podem ser abertos e editados em https://app.diagrams.net/.

## Abrir tudo de uma vez

- **`00-MONITORRENOVA-DIAGRAMAS-ROTEIROS-CORRIGIDOS.drawio`** — arquivo consolidado com nove páginas.

## Diagramas individuais

1. `01-casos-de-uso.drawio` — atores, UC01–UC09 e extensões claramente separadas.
2. `02-classes-conceituais.drawio` — entidades do domínio e multiplicidades.
3. `03-classes-php-crud.drawio` — classes PHP, Service, Repository, PDO e CRUD.
4. `04-heranca-polimorfismo.drawio` — interface, herança, polimorfismo, associação, agregação e composição.
5. `05-modelo-relacional.drawio` — tabelas, chaves e histórico de tarefas.
6. `06-sequencia-consulta-uc02.drawio` — consulta de seguros (UC02).
7. `07-sequencia-importacao-uc05.drawio` — validação, prévia, confirmação e caminhos alternativos (UC05).
8. `08-sequencia-cadastro-tarefa-ext.drawio` — CRUD como funcionalidade adicional, sem renumerar UC original.
9. `09-sequencia-edicao-tarefa-ext.drawio` — concorrência, versões e histórico de tarefas.

## Conteúdo complementar preservado

- `10-modelos-status-manual-complementares.drawio` — quatro páginas sobre a atualização manual da situação do seguro. É um arquivo complementar e **não** faz parte das nove páginas consolidadas.

## Correspondência com a especificação

- **RF05 / UC04** = histórico mensal de competências; não confundir com histórico de tarefas.
- **RN07** = exportação respeitando filtros; não renomear como auditoria de tarefa.
- **EXT-T01 / EXT-T02** = CRUD e histórico de tarefas, complementares.
- Diagramas de sequência **UC02** e **UC05** documentam os casos do roteiro de Engenharia de Software I.

## Limites da validação

Os arquivos foram revisados estruturalmente e integrados ao repositório. A aprovação formal das representações gráficas cabe à avaliação docente. O conteúdo técnico do código e as evidências de testes estão documentados separadamente.

Para baixar apenas esta pasta, use o arquivo consolidado individual ou baixe o ZIP do repositório no GitHub e abra `diagramas-corrigidos-editaveis`.
