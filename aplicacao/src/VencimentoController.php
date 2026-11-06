<?php
declare(strict_types=1);

require_once __DIR__.'/VencimentoRepository.php';

/**
 * Controller do dashboard de vencimentos (POO / MVC).
 *
 * Responsável exclusivamente por validar os filtros HTTP, definir o
 * intervalo padrão e preparar os números para a View. Não executa SQL
 * diretamente nem produz HTML.
 */
final class VencimentoController
{
    public function __construct(private readonly VencimentoRepository $repository)
    {
    }

    /**
     * @param array<string,mixed> $query Entradas da URL (não confiáveis).
     * @return array<string,mixed> Dados prontos para a View.
     */
    public function index(array $query, ?DateTimeImmutable $agora=null): array
    {
        // O mês atual deve avançar automaticamente na virada do calendário.
        // Utilizamos o mesmo fuso do agendamento de mensagens das 9h.
        $tz=new DateTimeZone('America/Sao_Paulo');
        $hoje=($agora ?? new DateTimeImmutable('now',$tz))->setTimezone($tz);
        $inicioPadrao=$hoje->format('Y-m-01');
        $fimPadrao=$hoje->format('Y-m-t');

        $de=self::data($query['de']??null) ?? $inicioPadrao;
        $ate=self::data($query['ate']??null) ?? $fimPadrao;
        if($de>$ate)[$de,$ate]=[$ate,$de];

        // Arrays, objetos e parâmetros exageradamente grandes nunca
        // chegam ao Repository ou ao SQL.
        $vendedor=is_string($query['vendedor']??null)
            ? substr(trim($query['vendedor']),0,180)
            : '';

        $retorno=$this->repository->consultar($de,$ate,$vendedor);
        $vencimentosHoje=$this->repository->vencimentosDeHoje($hoje->format('Y-m-d'));
        $stats=$retorno['stats'];
        $bySeller=$retorno['bySeller'];
        $byProduct=$retorno['byProduct'];
        $byDate=$retorno['byDate'];

        return [
            'de'=>$de,'ate'=>$ate,'vendedor'=>$vendedor,
            'dataHoje'=>$hoje->format('Y-m-d'),
            'vencimentosHoje'=>$vencimentosHoje,
            'atalhos'=>[
    ['label'=>'Hoje','de'=>$hoje->format('Y-m-d'),'ate'=>$hoje->format('Y-m-d')],
    ['label'=>'Próximos 7 dias','de'=>$hoje->format('Y-m-d'),'ate'=>$hoje->modify('+6 days')->format('Y-m-d')],
    ['label'=>'Mês atual','de'=>$inicioPadrao,'ate'=>$fimPadrao]
],
            'sellers'=>$retorno['sellers'],
            'stats'=>$stats,
            'bySeller'=>$bySeller,
            'byProduct'=>$byProduct,
            'byDate'=>$byDate,
            'import'=>$retorno['import'],
            'estadoSheet'=>$retorno['estadoSheet'],
            'total'=>(int)($stats['total']??0),
            // Denominadores nunca podem ser zero, mesmo quando o filtro
            // retorna uma lista vazia: evitamos divisão por zero no gráfico.
            'maxSeller'=>self::maximo($bySeller),
            'maxProduct'=>self::maximo($byProduct),
            'maxDate'=>self::maximo($byDate)
        ];
    }

    private static function data(mixed $entrada): ?string
    {
        if(!is_string($entrada)||!preg_match('/^\d{4}-\d{2}-\d{2}$/D',$entrada)){
            return null;
        }
        $data=DateTimeImmutable::createFromFormat('!Y-m-d',$entrada);
        return $data && $data->format('Y-m-d')===$entrada?$entrada:null;
    }

    /**
     * Retorna o maior total para calcular as barras proporcionais.
     * Mínimo 1 impede divisão por zero em períodos sem vencimentos.
     *
     * @param array<int,array<string,mixed>> $linhas
     */
    private static function maximo(array $linhas): int
    {
        $m=1;
        foreach($linhas as $r){
            $m=max($m,(int)($r['total']??0));
        }
        return $m;
    }
}
