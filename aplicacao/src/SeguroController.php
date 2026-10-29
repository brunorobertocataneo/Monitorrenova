<?php
declare(strict_types=1);

require_once __DIR__ . '/SeguroRepository.php';

/**
 * Controller da página de consulta de seguros (MVC).
 *
 * Sua responsabilidade é receber parâmetros da requisição HTTP, validar os
 * filtros e pedir os dados necessários ao Repository (Model). NÃO possui SQL
 * e NÃO monta HTML: isso permite testar a lógica sem executar o navegador.
 *
 * A consulta é somente leitura; em especial, status vazio significa "não
 * verificado" e nunca equivale a "seguro pendente de cobrança".
 */
final class SeguroController
{
    private const POR_PAGINA = 30;
    private const MAX_PAGINA = 10000;

    /**
     * Injeção de dependência: o controller recebe o repositório pronto.
     * Assim, não precisa conhecer credenciais, tabelas ou detalhes do PDO.
     */
    public function __construct(private readonly SeguroRepository $seguros)
    {
    }

    /**
     * @param array<string,mixed> $query Parâmetros de GET, não confiáveis.
     * @return array<string,mixed> Dados e filtros que serão usados pela View.
     */
    public function index(array $query): array
    {
        $filtros = [
            'vendedor' => self::texto($query['vendedor'] ?? null, 120),
            'produto' => self::texto($query['produto'] ?? null, 120),
            'de' => self::data($query['de'] ?? null),
            'ate' => self::data($query['ate'] ?? null),
            'busca' => self::texto($query['busca'] ?? null, 100),
        ];

        // Um parâmetro como ?pagina[]=2 não deve causar avisos nem exceções.
        $paginaBruta = $query['pagina'] ?? null;
        $pagina = 1;
        if (is_string($paginaBruta) || is_int($paginaBruta)) {
            $p = (string) $paginaBruta;
            if (preg_match('/^[0-9]{1,6}$/D', $p)) {
                $pagina = max(1, min(self::MAX_PAGINA, (int) $p));
            }
        }

        $consulta = $this->seguros->consultar($filtros, $pagina, self::POR_PAGINA);

        // Valores preparados especificamente para a View, sem acesso direto
        // ao MySQL a partir do arquivo de apresentação.
        return [
            ...$filtros,
            'pagina' => $consulta['pagina'],
            'limite' => self::POR_PAGINA,
            'vendors' => $consulta['vendedores'],
            'products' => $consulta['produtos'],
            'total' => $consulta['total'],
            'totalPages' => $consulta['totalPaginas'],
            'rows' => $consulta['registros'],
            'ultimaImportacao' => $consulta['ultimaImportacao'],
            'stats' => $consulta['estatisticas'],
            'paramsUrl' => $filtros,
        ];
    }

    /**
     * Aceita somente valores textuais simples, limitando o tamanho dos filtros
     * e ignorando arrays enviados indevidamente na URL.
     */
    private static function texto(mixed $valor, int $tamanhoMaximo): string
    {
        return is_string($valor) ? substr(trim($valor), 0, $tamanhoMaximo) : '';
    }

    /**
     * Exige uma data de calendário real no padrão HTML yyyy-mm-dd.
     * Datas inválidas retornam null, preservando o comportamento do painel.
     */
    private static function data(mixed $valor): ?string
    {
        if (!is_string($valor) || !preg_match('/^\d{4}-\d{2}-\d{2}$/D', $valor)) {
            return null;
        }
        $data = DateTimeImmutable::createFromFormat('!Y-m-d', $valor);
        return $data && $data->format('Y-m-d') === $valor ? $valor : null;
    }
}
