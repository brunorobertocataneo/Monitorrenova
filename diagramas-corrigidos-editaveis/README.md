# Diagramas UML — revisão para os roteiros UNOESC (13/11/2026)

**Arquivo único para abrir no diagrams.net:** [00-MONITORRENOVA-DIAGRAMAS-ROTEIROS-CORRIGIDOS.drawio](00-MONITORRENOVA-DIAGRAMAS-ROTEIROS-CORRIGIDOS.drawio).

Abra o diagrams.net → **Arquivo → Abrir de → Dispositivo** e escolha o arquivo `.drawio`. O documento consolidado contém **nove abas/páginas**, todas editáveis. Os arquivos individuais também são disponibilizados nesta pasta.

## Diagramas principais da revisão

| Página/arquivo | Escopo e ajuste |
|---|---|
| 01 — Casos de uso | UC01 autenticação, UC02 consulta, UC03 vencimentos, UC04 histórico mensal, UC05 importação, UC06 revisão, UC07 validação, UC08 aprovação e UC09 exportação, conforme V7. Extensões de tarefas separadas. |
| 02 — Classes conceituais | Modelo de análise V7 com usuário, analista, gestor, seguro, competência, revisão, validação, importação e itens, além do recorte de tarefas; heranças **conceituais**, não classes PHP alegadas. |
| 03 — Classes PHP/CRUD | Implementação real de TarefaService, TarefaRepository, PDO e arquivos de rotas como boundaries, sem desenhar as rotas como classes PHP. |
| 04 — Herança, polimorfismo e POO | RegraAvaliavel, RegraTriagem, subclasses, ConjuntoRegrasTriagem (agregação) e ResultadoTriagem (composição) conferidos com o código. |
| 05 — Modelo relacional | Recorte das tabelas de tarefa, evento, usuário e seguro mensal, FKs e multiplicidades. NÃO é o esquema completo da aplicação. |
| 06 — Sequência UC02 | Consultar seguros; corresponde à descrição do relatório de Engenharia de Software I e ao requisito RF03/US02. |
| 07 — Sequência UC05 | Importar lote com prévia, confirmação e tratamento alternativo de erro/cancelamento; corresponde ao RF06/US05. |

## Páginas complementares de implementação

- **08 — EXT-UC01: cadastro de tarefa.** Antiga sequência de cadastro, preservada e renomeada como extensão.
- **09 — EXT-UC01: edição de tarefa.** Antiga sequência com `alt`, preservada; não confundir com **UC04** do roteiro V7 (histórico mensal).

## Nota de consistência

A numeração UC01–UC09 e RF01–RF12 pertence à **especificação acadêmica V7**. O módulo implementado de tarefas tem rótulos adicionais **EXT-T01/EXT-T02** no Anexo D da documentação. O antigo `RF05` local de histórico de tarefa NÃO substitui o RF05 da professora, relativo a histórico de competências. A RN07 original trata da exportação respeitando filtros.

O diagrama 07 ilustra uma **sequência conceitual** correspondente ao requisito do professor, e NÃO declara, por si só, que todos os cenários de importação foram testados no software. Da mesma forma, a herança entre Analista/Gestor/Usuario no diagrama 02 é uma abstração de domínio, não a implementação PHP literal.

## Verificação e entrega

- Arquivos XML de diagrams.net validados estruturalmente quanto a células únicas, geometrias e ligações.
- Conferência visual final em diagrams.net, enquadramento de página, sobreposições e paginação em PDF ainda são recomendados antes da submissão.
- Branch criada apenas para revisão; a `main` permanece inalterada até aprovação do PR.
- Diagrama 03–05 foi cotejado com os métodos reais de TarefaService, TarefaRepository, RegraTriagem, ConjuntoRegrasTriagem e ResultadoTriagem.

**Evite usar os arquivos antigos `06-sequencia-cadastro.drawio` e `07-sequencia-edicao-alt.drawio`**; foram substituídos por nomes que identificam corretamente UC02 e UC05. As sequências de tarefas continuam disponíveis nas páginas 08 e 09.
