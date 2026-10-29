<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli')exit(1);
require_once __DIR__.'/Database.php';
$db=Database::connection();
$tests=[
 'historico_65'=>(int)$db->query('SELECT COUNT(*) FROM seguro_mensal')->fetchColumn()===65,
 'nomes_unicos_65'=>(int)$db->query('SELECT COUNT(DISTINCT segurado) FROM seguro_mensal')->fetchColumn()===65,
 'vendedores_historicos_5'=>(int)$db->query('SELECT COUNT(DISTINCT vendedor) FROM seguro_mensal')->fetchColumn()===5,
 'novembro_50'=>(int)$db->query('SELECT COUNT(*) FROM seguro_dashboard')->fetchColumn()===50,
 'sem_nomes_antigos'=>(int)$db->query("SELECT COUNT(*) FROM seguro_mensal WHERE segurado LIKE 'Cliente Exemplo %' OR vendedor LIKE 'Vendedor %'")->fetchColumn()===0,
 'fontes_sincronizadas'=>(int)$db->query('SELECT COUNT(*) FROM seguro_mensal m JOIN sheet_sync_registro_mensal s ON m.competencia=s.competencia AND m.linha_origem=s.linha_origem WHERE m.segurado<>s.segurado OR m.vendedor<>s.vendedor')->fetchColumn()===0,
 'itachi_pesquisavel'=>(int)$db->query("SELECT COUNT(*) FROM seguro_dashboard WHERE segurado='Itachi Uchiha'")->fetchColumn()===1,
 'bob_esponja_pesquisavel'=>(int)$db->query("SELECT COUNT(*) FROM seguro_dashboard WHERE segurado='Bob Esponja'")->fetchColumn()===1
];
foreach($tests as $key=>$value) echo ($value?'OK ':'FALHA ').$key.PHP_EOL;
exit(in_array(false,$tests,true)?1:0);
