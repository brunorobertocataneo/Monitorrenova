<?php
declare(strict_types=1);

// QA academico em memoria: nao altera MySQL nem envia mensagens.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit(1);
}
require_once __DIR__ . '/SimuladorRenovacao.php';

$erros = [];
$aprovados = 0;
function confirmar(string $nome, bool $condicao, mixed $observado): void
{
    global $erros, $aprovados;
    if ($condicao) {
        $aprovados++;
        echo '[OK] ' . $nome . PHP_EOL;
        return;
    }
    $erros[] = ['teste' => $nome, 'observado' => $observado];
    echo '[FALHA] ' . $nome . ' = ' . json_encode($observado, JSON_UNESCAPED_UNICODE) . PHP_EOL;
}
function invocarPolimorfico(RegraTriagem $regra, array $seguro, array $contexto): ?string
{
    // O tipo do parametro e sempre a mesma classe MAE.
    // A execucao usa a implementacao sobrescrita na classe filha.
    return $regra->avaliar($seguro, $contexto);
}
function motivo(array $seguro, DateTimeImmutable $data): array
{
    $saida = SimuladorRenovacao::avaliar([$seguro], $data);
    return ['motivo' => $saida['linhas'][0]['motivo_codigo'] ?? null, 'envios' => $saida['envios']];
}

$ref = new DateTimeImmutable('2026-10-20', new DateTimeZone('America/Sao_Paulo'));
$seguro = [
    'id' => 1, 'competencia' => '2026-10', 'linha_origem' => 1,
    'apolice' => null, 'segurado' => 'Cliente Ficticio',
    'vendedor' => 'Ada Lovelace', 'vencimento' => '2026-10-20',
    'produto' => 'AUTOMOVEL', 'status_atual' => 'A', 'status_pdf' => null,
    'renovado' => 0, 'cobrado' => 0, 'nao_vai_renovar' => 0,
    'validacao_id' => null, 'validacao_situacao' => '',
    'validacao_data' => '', 'validacao_fingerprint' => '',
];
$validado = array_replace($seguro, [
    'validacao_id' => 1,
    'validacao_situacao' => 'ativa',
    'validacao_data' => '2026-10-20',
    'validacao_fingerprint' => ValidacaoApolice::origemFingerprint($seguro),
]);
$ctxAtivo = ['situacao_validada' => 'ativa', 'validacao_recente' => true];
$ctxSemConsulta = ['situacao_validada' => '', 'validacao_recente' => false];

// 1. Heranca real, sem instanciar classe abstrata.
$reflection = new ReflectionClass(RegraTriagem::class);
confirmar('classe_mae_abstrata', $reflection->isAbstract(), $reflection->isAbstract());

$regras = [new RegraProduto(), new RegraSituacao(), new RegraConferencia()];
foreach ($regras as $objeto) {
    $tipo = get_class($objeto);
    confirmar(
        'heranca_' . $tipo,
        $objeto instanceof RegraTriagem && is_subclass_of($objeto, RegraTriagem::class),
        $tipo
    );
    $metodo = new ReflectionMethod($objeto, 'avaliar');
    confirmar(
        'sobrescrita_' . $tipo,
        $metodo->getDeclaringClass()->getName() === $tipo,
        $metodo->getDeclaringClass()->getName()
    );
}
$normalizacao = new ReflectionMethod(RegraProduto::class, 'normalizar');
confirmar('metodo_utilitario_herdado', $normalizacao->getDeclaringClass()->getName() === RegraTriagem::class, $normalizacao->getDeclaringClass()->getName());
confirmar('normalizacao_herdada', RegraTriagem::normalizar('  Consórcio ') === 'CONSORCIO', RegraTriagem::normalizar('  Consórcio '));

// 2. Mesmo contrato: resultados dependem do tipo concreto de objeto.
confirmar('polimorfismo_produto', invocarPolimorfico($regras[0], ['produto' => 'Seguro Vida'], $ctxAtivo) === 'produto_excluido', invocarPolimorfico($regras[0], ['produto' => 'Seguro Vida'], $ctxAtivo));
confirmar('polimorfismo_situacao', invocarPolimorfico($regras[1], ['status_atual' => 'C'], $ctxAtivo) === 'cancelado_recusado', invocarPolimorfico($regras[1], ['status_atual' => 'C'], $ctxAtivo));
confirmar('polimorfismo_conferencia', invocarPolimorfico($regras[2], [], $ctxAtivo) === 'aguardar_historico', invocarPolimorfico($regras[2], [], $ctxAtivo));
confirmar('produto_valido_delega', invocarPolimorfico($regras[0], ['produto' => 'AUTOMOVEL'], $ctxAtivo) === null, invocarPolimorfico($regras[0], ['produto' => 'AUTOMOVEL'], $ctxAtivo));
confirmar('situacao_ativa_delega', invocarPolimorfico($regras[1], ['status_atual' => 'A'], $ctxAtivo) === null, invocarPolimorfico($regras[1], ['status_atual' => 'A'], $ctxAtivo));
confirmar('situacao_sem_consulta_bloqueia', invocarPolimorfico($regras[1], ['status_atual' => 'A'], $ctxSemConsulta) === 'status_nao_comprovado', invocarPolimorfico($regras[1], ['status_atual' => 'A'], $ctxSemConsulta));

// 3. Regressao de ponta a ponta: todas as justificativas principais.
$cenarios = [
    'sem_dados' => [array_replace($seguro, ['segurado' => '']), 'sem_dados'],
    'ja_renovado' => [array_replace($seguro, ['renovado' => 1]), 'ja_renovado'],
    'nao_renova' => [array_replace($seguro, ['nao_vai_renovar' => 1]), 'nao_renova'],
    'fora_janela' => [array_replace($seguro, ['vencimento' => '2026-10-25']), 'fora_janela'],
    'cobrado_conferir' => [array_replace($seguro, ['cobrado' => 1]), 'cobrado_conferir'],
    'sem_produto' => [array_replace($seguro, ['produto' => '']), 'sem_produto'],
    'produto_excluido' => [array_replace($seguro, ['produto' => 'Consórcio']), 'produto_excluido'],
    'cancelado_recusado' => [array_replace($seguro, ['status_atual' => 'C']), 'cancelado_recusado'],
    'status_nao_comprovado' => [$seguro, 'status_nao_comprovado'],
    'aguardar_historico' => [$validado, 'aguardar_historico'],
    'prioridade_cobrado_sobre_produto' => [array_replace($seguro, ['cobrado' => 1, 'produto' => 'Vida']), 'cobrado_conferir'],
    'prioridade_renovado_sobre_cobrado' => [array_replace($seguro, ['renovado' => 1, 'cobrado' => 1]), 'ja_renovado'],
];
foreach ($cenarios as $nome => [$entrada, $esperado]) {
    $out = motivo($entrada, $ref);
    confirmar('simulador_' . $nome, $out['motivo'] === $esperado && $out['envios'] === 0, $out);
}

// 4. Precedencia de duplicidade e janela de fim de semana.
$duplicados = SimuladorRenovacao::avaliar(
    [$seguro, array_replace($seguro, ['id' => 2, 'linha_origem' => 2])], $ref
);
confirmar('simulador_duplicado', $duplicados['linhas'][1]['motivo_codigo'] === 'duplicado' && $duplicados['envios'] === 0, $duplicados['linhas'][1]['motivo_codigo']);
$sexta = new DateTimeImmutable('2026-10-23', new DateTimeZone('America/Sao_Paulo'));
confirmar('sexta_inclui_sabado_domingo', SimuladorRenovacao::janela($sexta) === ['2026-10-23', '2026-10-24', '2026-10-25'], SimuladorRenovacao::janela($sexta));
$domingo = new DateTimeImmutable('2026-10-25', new DateTimeZone('America/Sao_Paulo'));
confirmar('domingo_sem_janela', SimuladorRenovacao::janela($domingo) === [], SimuladorRenovacao::janela($domingo));

echo 'RESULTADO: ' . $aprovados . ' aprovados, ' . count($erros) . ' falhas; nenhum dado alterado e nenhum envio realizado.' . PHP_EOL;
exit($erros === [] ? 0 : 1);
