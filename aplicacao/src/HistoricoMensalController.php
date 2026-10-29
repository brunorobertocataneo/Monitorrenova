<?php
declare(strict_types=1);
/** Validates source/month/vendor GET parameters, no SQL or HTML. */
final class HistoricoMensalController {
  public function __construct(private readonly HistoricoMensalRepository $repo){}
  public function index(array $query):array {
    $fonte=($query['fonte']??null)==='manual'?'manual':'planilha';
    $months=$this->repo->months($fonte);$choices=array_column($months,'competencia');
    $month=is_string($query['mes']??null)&&in_array($query['mes'],$choices,true)?$query['mes']:($choices[0]??null);
    $vendors=$month===null?[]:$this->repo->vendors($fonte,$month);
    $vendor=is_string($query['vendedor']??null)?trim($query['vendedor']):'';
    if($vendor!==''&&!in_array($vendor,$vendors,true))$vendor='';
    $data=$this->repo->details($fonte,$month,$vendor);
    return [
      'fonte'=>$fonte,'months'=>$months,'month'=>$month,
      'vendors'=>$vendors,'vendor'=>$vendor,
      'stats'=>$data['stats'],'byVendor'=>$data['byVendor'],
      'byProduct'=>$data['byProduct'],'rows'=>$data['rows'],
      'lots'=>$data['lots'],
      'max'=>max(1,...array_map(static fn($v)=>(int)$v['total'],$data['byVendor']))
    ];
  }
}
