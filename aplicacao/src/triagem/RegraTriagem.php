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
        // Mapeamento UTF-8 previsivel no Windows e no Linux, independente do locale.
        $semAcentos = strtr($texto, [
            'á'=>'a', 'à'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a',
            'é'=>'e', 'è'=>'e', 'ê'=>'e', 'ë'=>'e',
            'í'=>'i', 'ì'=>'i', 'î'=>'i', 'ï'=>'i',
            'ó'=>'o', 'ò'=>'o', 'ô'=>'o', 'õ'=>'o', 'ö'=>'o',
            'ú'=>'u', 'ù'=>'u', 'û'=>'u', 'ü'=>'u', 'ç'=>'c',
            'Á'=>'A', 'À'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A',
            'É'=>'E', 'È'=>'E', 'Ê'=>'E', 'Ë'=>'E',
            'Í'=>'I', 'Ì'=>'I', 'Î'=>'I', 'Ï'=>'I',
            'Ó'=>'O', 'Ò'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O',
            'Ú'=>'U', 'Ù'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ç'=>'C',
        ]);
        return strtoupper($semAcentos);
    }
}
