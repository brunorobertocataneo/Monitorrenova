<?php
declare(strict_types=1);

/**
 * Controller de competências (POO/MVC).
 * A rota HTTP cuida de autenticação, sessão, CSRF e códigos HTTP.
 * O Repository cuida das transações de aprovação e leitura do MySQL.
 */
final class CompetenciaController
{
    public function __construct(private readonly CompetenciaRepository $repo) {}

    public function index():array
    {
        return $this->repo->resumo();
    }

    /**
     * Apenas autoriza a competência previamente observada e estável.
     * Não troca o mês aqui nem altera a lista de segurados.
     */
    public function autorizar(string $competencia):void
    {
        $this->repo->autorizar($competencia);
    }
}
