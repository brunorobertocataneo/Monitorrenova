<?php
declare(strict_types=1);

/**
 * Classe abstrata: contrato comum para as regras do simulador.
 *
 * As classes filhas sobrescrevem avaliar() e retornam:
 *   - um codigo de motivo, se a regra se aplicar;
 *   - null, para delegar a decisao a proxima regra.
 *
 * Regras apenas classificam; nao gravam no MySQL nem enviam mensagens.
 */
require_once __DIR__ . '/RegraAvaliavel.php';

abstract class RegraTriagem implements RegraAvaliavel
{
    /**
     * @param array<string,mixed> $seguro
     * @param array<string,mixed> $contexto
     */
    abstract public function avaliar(array $seguro, array $contexto): ?string;

    /** Funcionalidade herdada, compartilhada pelas regras concretas. */
    final public static function normalizar(mixed $valor): string
    {
        $texto = preg_replace('/\\s+/u', ' ', trim((string) $valor)) ?? (string) $valor;
        $semAcentos = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
        return strtoupper($semAcentos === false ? $texto : $semAcentos);
    }
}
