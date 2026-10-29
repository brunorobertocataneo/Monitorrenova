<?php
declare(strict_types=1);

require_once __DIR__ . '/RegraTriagem.php';

/**
 * Regra concreta final: mesmo com apolice validada, exige conferencia humana
 * do historico e autorizacao antes de qualquer contato.
 */
final class RegraConferencia extends RegraTriagem
{
    public function avaliar(array $seguro, array $contexto): ?string
    {
        return 'aguardar_historico';
    }
}
