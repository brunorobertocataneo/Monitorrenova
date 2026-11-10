<?php
declare(strict_types=1);

/**
 * Contrato das regras de triagem. Retorna o motivo que bloqueia/encaminha
 * o registro, ou null para permitir a proxima regra. Nunca envia mensagens.
 */
interface RegraAvaliavel
{
    /** @param array<string,mixed> $seguro @param array<string,mixed> $contexto */
    public function avaliar(array $seguro, array $contexto): ?string;
}
