<?php
declare(strict_types=1);

// Teste sem banco e sem HTTP: nenhuma linha e gravada e nenhum contato e enviado.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit(1);
}
require_once __DIR__ . '/SimuladorRenovacao.php';
require_once __DIR__ . '/TarefaService.php';

$ok = 0;
function verificarPoo(string $nome, bool $condicao): void
{
    global $ok;
    if (!$condicao) {
        throw new RuntimeException('FALHA: ' . $nome);
    }
    ++$ok;
    echo '[OK] ' . $nome . PHP_EOL;
}
function falhaEsperadaPoo(callable $acao): bool
{
    try {
        $acao();
    } catch (InvalidArgumentException) {
        return true;
    }
    return false;
}

// INTERFACE + abstracao + heranca polimorfica.
verificarPoo('interface declarada', interface_exists(RegraAvaliavel::class));
verificarPoo('mae implementa contrato', in_array(RegraAvaliavel::class, class_implements(RegraTriagem::class), true));
verificarPoo('mae continua abstrata', (new ReflectionClass(RegraTriagem::class))->isAbstract());
foreach ([RegraProduto::class, RegraSituacao::class, RegraConferencia::class] as $classe) {
    verificarPoo('classe concreta implementa interface: ' . $classe, is_a($classe, RegraAvaliavel::class, true));
}

// AGREGACAO: objetos passados ao todo continuam identicos e independentes.
$produto = new RegraProduto();
$situacao = new RegraSituacao();
$conferencia = new RegraConferencia();
$grupo = new ConjuntoRegrasTriagem([$produto, $situacao, $conferencia]);
verificarPoo('agregacao contem tres regras', $grupo->quantidade() === 3);
verificarPoo('agregacao preserva identidade dos objetos', $grupo->contem($produto) && $grupo->contem($conferencia));
verificarPoo('agregacao recusa lista vazia', falhaEsperadaPoo(static fn () => new ConjuntoRegrasTriagem([])));
verificarPoo('agregacao recusa objetos sem interface', falhaEsperadaPoo(static fn () => new ConjuntoRegrasTriagem([new stdClass()])));
$contexto = ['situacao_validada' => 'ativa', 'validacao_recente' => true];
verificarPoo('prioridade da primeira regra', $grupo->avaliar(['produto' => 'Vida', 'status_atual' => 'C'], $contexto) === 'produto_excluido');
verificarPoo('segunda regra recebe seguro valido', $grupo->avaliar(['produto' => 'Automovel', 'status_atual' => 'C'], $contexto) === 'cancelado_recusado');
verificarPoo('ultima regra mantem conferencia humana', $grupo->avaliar(['produto' => 'Automovel', 'status_atual' => 'A'], $contexto) === 'aguardar_historico');
unset($grupo);
verificarPoo('objeto agregado sobrevive ao todo', $produto->avaliar(['produto' => 'Vida'], $contexto) === 'produto_excluido');

// COMPOSICAO: o resultado cria e controla suas proprias linhas.
$dados = ['segurado' => 'Pessoa ficticia', 'motivo_codigo' => 'aguardar_historico'];
$resultado = new ResultadoTriagem();
$resultado->adicionar($dados);
verificarPoo('composicao cria uma linha', $resultado->total() === 1);
verificarPoo('composicao preserva valores', $resultado->linhas()[0] === $dados);
$linhas = $resultado->linhas();
$linhas[0]['segurado'] = 'Edicao fora do objeto';
verificarPoo('linhas sao copia e nao referencia interna', $resultado->linhas()[0]['segurado'] === 'Pessoa ficticia');
$prop = (new ReflectionClass(ResultadoTriagem::class))->getProperty('linhas');
$prop->setAccessible(true);
$internas = $prop->getValue($resultado);
verificarPoo('partes internas sao objetos LinhaResultadoTriagem', $internas[0] instanceof LinhaResultadoTriagem);

// ASSOCIACAO: TarefaService depende de TarefaRepository via construtor.
// O mesmo repository pode existir sem service. Nao e heranca nem composicao.
$param = (new ReflectionMethod(TarefaService::class, '__construct'))->getParameters()[0];
verificarPoo('associacao Service usa Repository', (string) $param->getType() === TarefaRepository::class);
verificarPoo('Repository nao herda Service', !is_subclass_of(TarefaRepository::class, TarefaService::class));

// REGRESSAO: o formato publico e os codigos do simulador permanecem iguais.
$data = new DateTimeImmutable('2026-10-20', new DateTimeZone('America/Sao_Paulo'));
$base = [
    'id' => 1, 'linha_origem' => 1, 'segurado' => 'Pessoa ficticia',
    'vendedor' => 'Vendedor ficticio', 'vencimento' => '2026-10-20',
    'produto' => 'Automovel', 'status_atual' => 'A',
    'renovado' => 0, 'cobrado' => 0, 'nao_vai_renovar' => 0,
    'validacao_id' => null, 'validacao_situacao' => '',
    'validacao_data' => '', 'validacao_fingerprint' => '',
];
$simulado = SimuladorRenovacao::avaliar(
    [$base, array_replace($base, ['id' => 2, 'linha_origem' => 2, 'renovado' => 1])],
    $data
);
verificarPoo('formato publico preservado', array_keys($simulado) === ['janela', 'contagens', 'linhas', 'total', 'envios']);
verificarPoo('total corresponde a duas linhas', $simulado['total'] === 2 && count($simulado['linhas']) === 2);
verificarPoo('situacao sem consulta nao foi liberada', $simulado['linhas'][0]['motivo_codigo'] === 'status_nao_comprovado');
verificarPoo('seguro renovado mantem motivo', $simulado['linhas'][1]['motivo_codigo'] === 'ja_renovado');
verificarPoo('nenhum envio externo', $simulado['envios'] === 0);

echo 'RESULTADO POO: ' . $ok . ' testes aprovados; nenhum banco ou mensagem alterados.' . PHP_EOL;
