#!/usr/bin/env python3
"""RNF01: 100 consultas autenticadas em até cinco workers concorrentes.
Executar APENAS após preparar base de demonstração com 500 registros adicionais.
Não usar em produção. Gera falha se houver respostas não-200 ou <95% <=3s.
"""
import concurrent.futures
import http.cookiejar
import os
import re
import sys
import time
import urllib.parse
import urllib.request

BASE = "http://127.0.0.1:8088"
secret = os.environ.get("CI_RNF_PASSWORD")
if os.environ.get("CI_RNF_ISOLATED") != "1" or not secret or len(secret) < 16:
    sys.exit("Ambiente RNF isolado/credencial demo não configurados")

jar = http.cookiejar.CookieJar()
opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar))
page = opener.open(BASE + "/login.php", timeout=10).read().decode("utf-8", "replace")
token = re.search(r'name="csrf"\s+value="([^"]+)"', page)
if not token:
    sys.exit("RNF01: formulário de login sem token CSRF")

data = urllib.parse.urlencode({"csrf": token.group(1), "senha": secret}).encode()
opener.open(urllib.request.Request(BASE + "/login.php", data=data, method="POST"), timeout=10).read()
cookie = "; ".join(c.name + "=" + c.value for c in jar)
if "MONITOR_RENOVA_SESSION=" not in cookie:
    sys.exit("RNF01: cookie autenticado não encontrada")

def worker(index):
    values = []
    for i in range(20):
        query = urllib.parse.urlencode({"busca": "SEGURO-TESTE-" if i % 2 else "", "pagina": "1"})
        req = urllib.request.Request(BASE + "/seguros.php?" + query, headers={"Cookie": cookie})
        t0 = time.perf_counter()
        with urllib.request.urlopen(req, timeout=10) as res:
            status = res.status
            res.read()
        values.append((status, time.perf_counter() - t0))
    return values

try:
    with concurrent.futures.ThreadPoolExecutor(max_workers=5) as pool:
        results = [record for batch in pool.map(worker, range(5)) for record in batch]
except Exception as exc:
    sys.exit("RNF01 FALHA transporte: " + str(exc))

success = sum(code == 200 and seconds <= 3 for code, seconds in results)
times = sorted(seconds for _, seconds in results)
passed = len(results) == 100 and success >= 95
print("RNF01 " + ("APROVADO" if passed else "REPROVADO") +
      f" consultas_ate_3s={success}/{len(results)} p95={times[94]:.3f}s max={times[-1]:.3f}s" +
      " workers=5 apolices_adicionais=500")
sys.exit(0 if passed else 1)
