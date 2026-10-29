<?php
declare(strict_types=1);
require_once __DIR__ . '/Database.php';
final class MonitorRepository {
    public function __construct(private readonly PDO $db) {}
    public function indicadores(): array {
        return [
            'automacoes' => (int) $this->db->query('SELECT COUNT(*) FROM automacao')->fetchColumn(),
            'execucoes' => (int) $this->db->query('SELECT COUNT(*) FROM execucao')->fetchColumn(),
            'sucessos' => (int) $this->db->query("SELECT COUNT(*) FROM execucao WHERE status='sucesso'")->fetchColumn(),
            'falhas' => (int) $this->db->query("SELECT COUNT(*) FROM execucao WHERE status='falha'")->fetchColumn(),
        ];
    }
    public function automacoes(): array {
        return $this->db->query('SELECT id,nome,sistema_origem,referencia_externa,descricao,ativo FROM automacao ORDER BY id DESC')->fetchAll();
    }
    public function execucoes(?string $status = null, ?int $automacaoId = null): array {
        if ($status !== null && !in_array($status, ['iniciada','sucesso','falha','cancelada'], true)) $status = null;
        if ($automacaoId !== null && $automacaoId < 1) $automacaoId = null;
        $sql = 'SELECT e.id,a.nome AS automacao,e.status,e.iniciada_em,e.terminada_em,e.total_processados,e.total_elegiveis,e.total_pendentes,e.total_enviados,e.mensagem_erro FROM execucao e JOIN automacao a ON a.id=e.automacao_id';
        $conditions = []; $params = [];
        if ($status !== null) {$conditions[] = 'e.status = :status';$params['status']=$status;}
        if ($automacaoId !== null) {$conditions[] = 'e.automacao_id = :automacao';$params['automacao']=$automacaoId;}
        if ($conditions) $sql .= ' WHERE '.implode(' AND ',$conditions);
        $sql .= ' ORDER BY e.iniciada_em DESC,e.id DESC LIMIT 50';
        $stmt=$this->db->prepare($sql);$stmt->execute($params);
        return $stmt->fetchAll();
    }
    public function eventos(): array {
        return $this->db->query('SELECT v.nivel,v.tipo,v.descricao,v.criado_em,a.nome AS automacao FROM evento_execucao v JOIN execucao e ON e.id=v.execucao_id JOIN automacao a ON a.id=e.automacao_id ORDER BY v.id DESC LIMIT 25')->fetchAll();
    }
    public function resumoPorStatus(): array {
        $rows=$this->db->query('SELECT status,COUNT(*) AS total FROM execucao GROUP BY status')->fetchAll();
        $result=['iniciada'=>0,'sucesso'=>0,'falha'=>0,'cancelada'=>0];
        foreach($rows as $r){if(array_key_exists($r['status'],$result))$result[$r['status']]=(int)$r['total'];}
        return $result;
    }
    public function ultimosDias(int $dias=7): array {
        $dias=max(1,min(31,$dias));
        $sql='SELECT DATE(iniciada_em) AS dia,COUNT(*) AS execucoes,SUM(total_processados) AS processados,SUM(total_elegiveis) AS elegiveis,SUM(total_pendentes) AS pendentes,SUM(total_enviados) AS enviados FROM execucao WHERE iniciada_em >= CURRENT_DATE - INTERVAL '.($dias-1).' DAY GROUP BY DATE(iniciada_em) ORDER BY dia ASC';
        $map=[];foreach($this->db->query($sql)->fetchAll() as $r)$map[$r['dia']]=$r;
        $data=[];$today=new DateTimeImmutable('today');
        for($i=$dias-1;$i>=0;$i--){
            $day=$today->modify("-$i days")->format('Y-m-d');
            $r=$map[$day]??[];
            $data[]=['dia'=>$day,'execucoes'=>(int)($r['execucoes']??0),'processados'=>(int)($r['processados']??0),'elegiveis'=>(int)($r['elegiveis']??0),'pendentes'=>(int)($r['pendentes']??0),'enviados'=>(int)($r['enviados']??0)];
        }
        return $data;
    }
}
