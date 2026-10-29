# MonitorRenova: Herança e Polimorfismo em PHP

**Disciplina:** Programação II - UNOESC
**Módulo:** Simulador de Renovações
**Dados:** 100% fictícios; sem envios de mensagens.

## Objetivo

Aplicar herança, abstração e polimorfismo a uma funcionalidade existente, sem mudar a prioridade das regras de triagem. O módulo apenas simula classificações: não altera apólices, não realiza cobrança e não envia mensagens.

## Diagrama de classes

                    +-----------------------+
                    | <<abstract>>          |
                    | RegraTriagem          |
                    | +avaliar(...)         |
                    | +normalizar(...)      |
                    +-----------+-----------+
                                ^
                ----------------+----------------
                |               |               |
        +-------+------+ +------+-------+ +-----+-----------+
        | RegraProduto | | RegraSituacao | | RegraConferencia |
        | +avaliar()   | | +avaliar()    | | +avaliar()       |
        +--------------+ +--------------+ +-----------------+

O SimuladorRenovacao mantém objetos das três classes filhas e chama o mesmo método avaliar() em cada objeto. A classe abstrata não é instanciada diretamente.

## Conceito 1: HERANÇA

Arquivo: src/triagem/RegraTriagem.php

    abstract class RegraTriagem
    {
        abstract public function avaliar(array $seguro, array $contexto): ?string;
        final public static function normalizar(mixed $valor): string
        {
            // Implementação compartilhada entre as regras.
        }
    }

Arquivos das classes filhas:

    final class RegraProduto extends RegraTriagem { ... }
    final class RegraSituacao extends RegraTriagem { ... }
    final class RegraConferencia extends RegraTriagem { ... }

O comando extends indica herança. Todas as filhas herdam normalizar(). Como avaliar() é abstrato, as classes concretas precisam definir a implementação.

## Conceito 2: POLIMORFISMO

Arquivo: src/SimuladorRenovacao.php

    $regras = [
        new RegraProduto(),
        new RegraSituacao(),
        new RegraConferencia(),
    ];

    foreach ($regras as $regra) {
        $codigo = $regra->avaliar($s, $contexto);
        if ($codigo !== null) {
            $motivo = $codigo;
            break;
        }
    }

O mesmo comando avaliar() produz comportamentos diferentes conforme o tipo concreto do objeto. Quando uma regra retorna null, a execução passa para a próxima. Isso é polimorfismo por sobrescrita.

## Exemplos de resultados

| Classe | Exemplo | Resultado |
| --- | --- | --- |
| RegraProduto | Seguro de Vida | produto_excluido |
| RegraProduto | Automóvel | null, delega à próxima regra |
| RegraSituacao | Cancelado | cancelado_recusado |
| RegraSituacao | Sem validação recente | status_nao_comprovado |
| RegraSituacao | Ativa, validada recentemente | null, delega |
| RegraConferencia | Caso sem bloqueios anteriores | aguardar_historico |

A ordem de checagem original é preservada. Antes dessas três regras, o simulador continua verificando dados obrigatórios, renovação marcada, decisão de não renovar, janela de vencimento, duplicidade e cobrança marcada.

## Benefícios para o projeto

- Organização: regras separadas em classes.
- Reutilização: assinatura do método comum e normalização herdada.
- Extensibilidade: novas regras podem herdar de RegraTriagem.
- Testabilidade: subclasses podem ser validadas isoladamente.
- Segurança: o simulador continua sem envios reais (envios = 0).

## Como demonstrar na aula

1. Mostrar src/triagem/RegraTriagem.php: abstract class e abstract public function.
2. Mostrar src/triagem/RegraProduto.php: extends e método avaliar().
3. Mostrar src/triagem/RegraSituacao.php: mesmo nome de método, lógica diferente.
4. Mostrar src/SimuladorRenovacao.php: laço foreach chamando avaliar().
5. Executar no PowerShell:

    & 'C:\Program Files\Docker\Docker\resources\bin\docker.exe' exec aplicacao-web-1 php /var/www/src/testar-heranca-polimorfismo.php

Resultado verificado: 30 testes aprovados, 0 falhas. Também passaram os testes antigos do simulador.

## Limites

Esta implementação comprova herança, abstração e polimorfismo reais no código PHP do simulador. Não é um CRUD completo; não envia WhatsApp; não integra o fluxo independente de oito clientes do n8n ao MySQL.
