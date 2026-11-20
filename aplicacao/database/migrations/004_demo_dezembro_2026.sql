START TRANSACTION;
-- Expansão acadêmica: 62 apólices fictícias com vencimentos até 31/12/2026.
-- Idempotente: mantém competências anteriores e decisões manuais.
INSERT IGNORE INTO sheet_sync_registro_mensal
(competencia,linha_origem,segurado,vendedor,vencimento,renovado,cobrado,nao_vai_renovar,produto,status_atual,status_pdf,premio_pdf,fonte_pdf)
VALUES
 ('2026-12',2,'Segurado Fictício Dezembro 001','Ada Lovelace','2026-12-01',0,0,0,'Seguro Auto','PENDENTE',NULL,669.25,NULL),
 ('2026-12',3,'Segurado Fictício Dezembro 002','Marie Curie','2026-12-01',0,0,0,'Seguro Residencial','PENDENTE',NULL,688.50,NULL),
 ('2026-12',4,'Segurado Fictício Dezembro 003','Leonardo da Vinci','2026-12-02',0,0,0,'Seguro Empresarial','PENDENTE',NULL,707.75,NULL),
 ('2026-12',5,'Segurado Fictício Dezembro 004','Albert Einstein','2026-12-02',0,0,0,'Seguro Vida','PENDENTE',NULL,727.00,NULL),
 ('2026-12',6,'Segurado Fictício Dezembro 005','Nikola Tesla','2026-12-03',0,0,0,'Seguro Auto','PENDENTE',NULL,746.25,NULL),
 ('2026-12',7,'Segurado Fictício Dezembro 006','Ada Lovelace','2026-12-03',0,0,0,'Seguro Residencial','PENDENTE',NULL,765.50,NULL),
 ('2026-12',8,'Segurado Fictício Dezembro 007','Marie Curie','2026-12-04',0,0,0,'Seguro Empresarial','PENDENTE',NULL,784.75,NULL),
 ('2026-12',9,'Segurado Fictício Dezembro 008','Leonardo da Vinci','2026-12-04',0,0,0,'Seguro Vida','PENDENTE',NULL,804.00,NULL),
 ('2026-12',10,'Segurado Fictício Dezembro 009','Albert Einstein','2026-12-05',0,0,0,'Seguro Auto','PENDENTE',NULL,823.25,NULL),
 ('2026-12',11,'Segurado Fictício Dezembro 010','Nikola Tesla','2026-12-05',0,0,0,'Seguro Residencial','PENDENTE',NULL,842.50,NULL),
 ('2026-12',12,'Segurado Fictício Dezembro 011','Ada Lovelace','2026-12-06',0,0,0,'Seguro Empresarial','PENDENTE',NULL,861.75,NULL),
 ('2026-12',13,'Segurado Fictício Dezembro 012','Marie Curie','2026-12-06',0,0,0,'Seguro Vida','PENDENTE',NULL,881.00,NULL),
 ('2026-12',14,'Segurado Fictício Dezembro 013','Leonardo da Vinci','2026-12-07',0,0,0,'Seguro Auto','PENDENTE',NULL,900.25,NULL),
 ('2026-12',15,'Segurado Fictício Dezembro 014','Albert Einstein','2026-12-07',0,0,0,'Seguro Residencial','PENDENTE',NULL,919.50,NULL),
 ('2026-12',16,'Segurado Fictício Dezembro 015','Nikola Tesla','2026-12-08',0,0,0,'Seguro Empresarial','PENDENTE',NULL,938.75,NULL),
 ('2026-12',17,'Segurado Fictício Dezembro 016','Ada Lovelace','2026-12-08',0,0,0,'Seguro Vida','PENDENTE',NULL,958.00,NULL),
 ('2026-12',18,'Segurado Fictício Dezembro 017','Marie Curie','2026-12-09',0,0,0,'Seguro Auto','PENDENTE',NULL,977.25,NULL),
 ('2026-12',19,'Segurado Fictício Dezembro 018','Leonardo da Vinci','2026-12-09',0,0,0,'Seguro Residencial','PENDENTE',NULL,996.50,NULL),
 ('2026-12',20,'Segurado Fictício Dezembro 019','Albert Einstein','2026-12-10',0,0,0,'Seguro Empresarial','PENDENTE',NULL,1015.75,NULL),
 ('2026-12',21,'Segurado Fictício Dezembro 020','Nikola Tesla','2026-12-10',0,0,0,'Seguro Vida','PENDENTE',NULL,1035.00,NULL),
 ('2026-12',22,'Segurado Fictício Dezembro 021','Ada Lovelace','2026-12-11',0,0,0,'Seguro Auto','PENDENTE',NULL,1054.25,NULL),
 ('2026-12',23,'Segurado Fictício Dezembro 022','Marie Curie','2026-12-11',0,0,0,'Seguro Residencial','PENDENTE',NULL,1073.50,NULL),
 ('2026-12',24,'Segurado Fictício Dezembro 023','Leonardo da Vinci','2026-12-12',0,0,0,'Seguro Empresarial','PENDENTE',NULL,1092.75,NULL),
 ('2026-12',25,'Segurado Fictício Dezembro 024','Albert Einstein','2026-12-12',0,0,0,'Seguro Vida','PENDENTE',NULL,1112.00,NULL),
 ('2026-12',26,'Segurado Fictício Dezembro 025','Nikola Tesla','2026-12-13',0,0,0,'Seguro Auto','PENDENTE',NULL,1131.25,NULL),
 ('2026-12',27,'Segurado Fictício Dezembro 026','Ada Lovelace','2026-12-13',0,0,0,'Seguro Residencial','PENDENTE',NULL,1150.50,NULL),
 ('2026-12',28,'Segurado Fictício Dezembro 027','Marie Curie','2026-12-14',0,0,0,'Seguro Empresarial','PENDENTE',NULL,1169.75,NULL),
 ('2026-12',29,'Segurado Fictício Dezembro 028','Leonardo da Vinci','2026-12-14',0,0,0,'Seguro Vida','PENDENTE',NULL,1189.00,NULL),
 ('2026-12',30,'Segurado Fictício Dezembro 029','Albert Einstein','2026-12-15',0,0,0,'Seguro Auto','PENDENTE',NULL,1208.25,NULL),
 ('2026-12',31,'Segurado Fictício Dezembro 030','Nikola Tesla','2026-12-15',0,0,0,'Seguro Residencial','PENDENTE',NULL,1227.50,NULL),
 ('2026-12',32,'Segurado Fictício Dezembro 031','Ada Lovelace','2026-12-16',0,0,0,'Seguro Empresarial','PENDENTE',NULL,1246.75,NULL),
 ('2026-12',33,'Segurado Fictício Dezembro 032','Marie Curie','2026-12-16',0,0,0,'Seguro Vida','PENDENTE',NULL,1266.00,NULL),
 ('2026-12',34,'Segurado Fictício Dezembro 033','Leonardo da Vinci','2026-12-17',0,0,0,'Seguro Auto','PENDENTE',NULL,1285.25,NULL),
 ('2026-12',35,'Segurado Fictício Dezembro 034','Albert Einstein','2026-12-17',0,0,0,'Seguro Residencial','PENDENTE',NULL,1304.50,NULL),
 ('2026-12',36,'Segurado Fictício Dezembro 035','Nikola Tesla','2026-12-18',0,0,0,'Seguro Empresarial','PENDENTE',NULL,1323.75,NULL),
 ('2026-12',37,'Segurado Fictício Dezembro 036','Ada Lovelace','2026-12-18',0,0,0,'Seguro Vida','PENDENTE',NULL,1343.00,NULL),
 ('2026-12',38,'Segurado Fictício Dezembro 037','Marie Curie','2026-12-19',0,0,0,'Seguro Auto','PENDENTE',NULL,1362.25,NULL),
 ('2026-12',39,'Segurado Fictício Dezembro 038','Leonardo da Vinci','2026-12-19',0,0,0,'Seguro Residencial','PENDENTE',NULL,1381.50,NULL),
 ('2026-12',40,'Segurado Fictício Dezembro 039','Albert Einstein','2026-12-20',0,0,0,'Seguro Empresarial','PENDENTE',NULL,1400.75,NULL),
 ('2026-12',41,'Segurado Fictício Dezembro 040','Nikola Tesla','2026-12-20',0,0,0,'Seguro Vida','PENDENTE',NULL,1420.00,NULL),
 ('2026-12',42,'Segurado Fictício Dezembro 041','Ada Lovelace','2026-12-21',0,0,0,'Seguro Auto','PENDENTE',NULL,1439.25,NULL),
 ('2026-12',43,'Segurado Fictício Dezembro 042','Marie Curie','2026-12-21',0,0,0,'Seguro Residencial','PENDENTE',NULL,1458.50,NULL),
 ('2026-12',44,'Segurado Fictício Dezembro 043','Leonardo da Vinci','2026-12-22',0,0,0,'Seguro Empresarial','PENDENTE',NULL,1477.75,NULL),
 ('2026-12',45,'Segurado Fictício Dezembro 044','Albert Einstein','2026-12-22',0,0,0,'Seguro Vida','PENDENTE',NULL,1497.00,NULL),
 ('2026-12',46,'Segurado Fictício Dezembro 045','Nikola Tesla','2026-12-23',0,0,0,'Seguro Auto','PENDENTE',NULL,1516.25,NULL),
 ('2026-12',47,'Segurado Fictício Dezembro 046','Ada Lovelace','2026-12-23',0,0,0,'Seguro Residencial','PENDENTE',NULL,1535.50,NULL),
 ('2026-12',48,'Segurado Fictício Dezembro 047','Marie Curie','2026-12-24',0,0,0,'Seguro Empresarial','PENDENTE',NULL,1554.75,NULL),
 ('2026-12',49,'Segurado Fictício Dezembro 048','Leonardo da Vinci','2026-12-24',0,0,0,'Seguro Vida','PENDENTE',NULL,1574.00,NULL),
 ('2026-12',50,'Segurado Fictício Dezembro 049','Albert Einstein','2026-12-25',0,0,0,'Seguro Auto','PENDENTE',NULL,1593.25,NULL),
 ('2026-12',51,'Segurado Fictício Dezembro 050','Nikola Tesla','2026-12-25',0,0,0,'Seguro Residencial','PENDENTE',NULL,1612.50,NULL),
 ('2026-12',52,'Segurado Fictício Dezembro 051','Ada Lovelace','2026-12-26',0,0,0,'Seguro Empresarial','PENDENTE',NULL,1631.75,NULL),
 ('2026-12',53,'Segurado Fictício Dezembro 052','Marie Curie','2026-12-26',0,0,0,'Seguro Vida','PENDENTE',NULL,1651.00,NULL),
 ('2026-12',54,'Segurado Fictício Dezembro 053','Leonardo da Vinci','2026-12-27',0,0,0,'Seguro Auto','PENDENTE',NULL,1670.25,NULL),
 ('2026-12',55,'Segurado Fictício Dezembro 054','Albert Einstein','2026-12-27',0,0,0,'Seguro Residencial','PENDENTE',NULL,1689.50,NULL),
 ('2026-12',56,'Segurado Fictício Dezembro 055','Nikola Tesla','2026-12-28',0,0,0,'Seguro Empresarial','PENDENTE',NULL,1708.75,NULL),
 ('2026-12',57,'Segurado Fictício Dezembro 056','Ada Lovelace','2026-12-28',0,0,0,'Seguro Vida','PENDENTE',NULL,1728.00,NULL),
 ('2026-12',58,'Segurado Fictício Dezembro 057','Marie Curie','2026-12-29',0,0,0,'Seguro Auto','PENDENTE',NULL,1747.25,NULL),
 ('2026-12',59,'Segurado Fictício Dezembro 058','Leonardo da Vinci','2026-12-29',0,0,0,'Seguro Residencial','PENDENTE',NULL,1766.50,NULL),
 ('2026-12',60,'Segurado Fictício Dezembro 059','Albert Einstein','2026-12-30',0,0,0,'Seguro Empresarial','PENDENTE',NULL,1785.75,NULL),
 ('2026-12',61,'Segurado Fictício Dezembro 060','Nikola Tesla','2026-12-30',0,0,0,'Seguro Vida','PENDENTE',NULL,1805.00,NULL),
 ('2026-12',62,'Segurado Fictício Dezembro 061','Ada Lovelace','2026-12-31',0,0,0,'Seguro Auto','PENDENTE',NULL,1824.25,NULL),
 ('2026-12',63,'Segurado Fictício Dezembro 062','Marie Curie','2026-12-31',0,0,0,'Seguro Residencial','PENDENTE',NULL,1843.50,NULL);

INSERT INTO seguro_mensal
(competencia,linha_origem,segurado,vendedor,vencimento,renovado,cobrado,nao_vai_renovar,produto,status_atual,status_pdf,premio_pdf,fonte_pdf)
SELECT t.competencia,t.linha_origem,t.segurado,t.vendedor,t.vencimento,t.renovado,t.cobrado,t.nao_vai_renovar,t.produto,t.status_atual,t.status_pdf,t.premio_pdf,t.fonte_pdf
FROM sheet_sync_registro_mensal t
WHERE t.competencia='2026-12' AND NOT EXISTS
(SELECT 1 FROM seguro_mensal s WHERE s.competencia=t.competencia AND s.linha_origem=t.linha_origem);
COMMIT;
