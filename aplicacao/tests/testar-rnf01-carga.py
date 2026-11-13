#!/usr/bin/env python3
"""RNF01: 5 sessões independentes, 100 consultas autenticadas concorrentes.
Somente com banco descartável e 500 seguros sintéticos adicionais.
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

def login():
    jar = http.cookiejar.CookieJar()
    opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar))
    page = opener.open(BASE + "/login.php", timeout=10).read().decode("utf-8", "replace")
    token = re.search(r'name="csrf"\s+value="([^"]+)"', page)
    if not token:
        raise RuntimeError("formulário de login sem token CSRF")
    data = urllib.parse.urlencode({"csrf": token.group(1), "senha": secret}).encode()
    opener.open(urllib.request.Request(BASE + "/login.php", data=data, method="POST"), timeout=10).read()
    cookies = [c.value for c in jar if c.name == "MONITOR_RENOVA_SESSION"]
    if len(cookies) != 1:
        raise RuntimeError("sessão autenticada não encontrada")
    page = opener.open(BASE + "/seguros.php", timeout=10)
    if page.status != 200:
        raise RuntimeError("sessão não autorizou consulta")
    page.close()
    return opener, cookies[0]

def worker(index):
    opener, session_id = login()  # cookie jar exclusivo por worker (não compartilhado)
    values = []
    for i in range(20):
        query = urllib.parse.urlencode({"busca": "SEGURO-TESTE-" if i % 2 else "", "pagina": "1"})
        req = urllib.request.Request(BASE + "/seguros.php?" + query)
        t0 = time.perf_counter()
        with opener.open(req, timeout=10) as res:
            status = res.status
            res.read()
        values.append((status, time.perf_counter() - t0))
    return (session_id, values)

try:
    with concurrent.futures.ThreadPoolExecutor(max_workers=5) as pool:
        batches = list(pool.map(worker, range(5)))
except Exception as exc:
    sys.exit("RNF01 FALHA transporte/login: " + str(exc))

unique_sessions = len({session for session, _ in batches})
results = [rec for _, batch in batches for rec in batch]
success = sum(code == 200 and seconds <= 3 for code, seconds in results)
times = sorted(seconds for _, seconds in results)
passed = len(results) == 100 and unique_sessions == 5 and success >= 95
print("RNF01 " + ("APROVADO" if passed else "REPROVADO") +
      f" consultas_ate_3s={success}/{len(results)} p95={times[94]:.3f}s max={times[-1]:.3f}s" +
      f" sessoes_distintas={unique_sessions}/5 workers=5 apolices_adicionais=500")
sys.exit(0 if passed else 1)
