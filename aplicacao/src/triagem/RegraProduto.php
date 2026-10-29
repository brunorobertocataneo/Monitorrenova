<?php
declare(strict_types=1);

require_once __DIR__ . '/RegraTriagem.php';

/** Regra concreta: verifica a presença e o tipo do produto. */
final class RegraProduto extends RegraTriagem
{
    public function avaliar(array $seguro, array $contexto): ?string
    {
        $produto = trim((string) ($seguro['produto'] ?? ''));

        if ($produto === '') {
            return 'sem_produto';
        }

        // normalizar() foi herdado da classe abstrata RegraTriagem.
        if (preg_match(
            '/CONSORCIO|VIDA|ACIDENTES PESSOAIS|(^|\\W)AP($|\\W)/',
            self::normalizar($produto)
        )) {
            return 'produto_excluido';
        }

        return null;
    }
}
