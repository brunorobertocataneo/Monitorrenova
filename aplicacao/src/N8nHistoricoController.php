<?php
declare(strict_types=1);

/**
 * Controller do histórico n8n (POO/MVC). Filtra apenas IDs de workflows
 * permitidos e não consulta payloads, mensagens ou credenciais.
 */
final class N8nHistoricoController
{
    public function __construct(private readonly N8nHistoricoRepository $repo) {}

    /** @param array<string,mixed> $query */
    public function index(array $query):array
    {
        $workflow=null;
        if(is_string($query['workflow']??null) &&
            isset(N8nHistoricoRepository::WORKFLOW_NOMES[$query['workflow']])){
            $workflow=$query['workflow'];
        }
        return [
            'workflow'=>$workflow,
            'resumo'=>$this->repo->resumo(),
            'execucoes'=>$this->repo->ultimas(60,$workflow)
        ];
    }
}
