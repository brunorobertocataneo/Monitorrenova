<?php
declare(strict_types=1);

require_once __DIR__ . '/RegraTriagem.php';

/** Regra concreta: exige confirmacao recente e valida da situacao da apolice. */
final class RegraSituacao extends RegraTriagem
{
    public function avaliar(array $seguro, array $contexto): ?string
    {
        $status = self::normalizar($seguro['status_atual'] ?? '');
        $situacao = (string) ($contexto['situacao_validada'] ?? '');
        $recente = ($contexto['validacao_recente'] ?? false) === true;

        if (
            in_array($status, ['C', 'CANCELADO', 'CANCELADA', 'R', 'RECUSADO', 'RECUSADA'], true)
            || ($recente && in_array($situacao, ['cancelada', 'recusada'], true))
        ) {
            return 'cancelado_recusado';
        }

        if (!$recente || $situacao !== 'ativa') {
            return 'status_nao_comprovado';
        }

        return null;
    }
}
