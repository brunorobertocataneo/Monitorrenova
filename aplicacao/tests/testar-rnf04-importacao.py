#!/usr/bin/env python3
"""RNF04: prévia e cancelamento com CSV sintético em banco isolado.
A verificação de zero registros gravados é feita no workflow, via SQL.
"""
import http.cookiejar
import os
import re
import sys
import urllib.parse
import urllib.request

if os.getenv("CI_RNF_ISOLATED") != "1" or len(os.getenv("CI_RNF_PASSWORD", "")) < 16:
    sys.exit("Somente ambiente isolado")
base = "http://127.0.0.1:8088"
opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
password = os.environ["CI_RNF_PASSWORD"]
login = opener.open(base + "/login.php").read().decode("utf-8", "replace")
token = re.search(r'name="csrf"\s+value="([^"]+)"', login)
if not token:
    sys.exit("Token login indisponível")
opener.open(urllib.request.Request(base + "/login.php",
    data=urllib.parse.urlencode({"csrf": token.group(1), "senha": password}).encode(), method="POST")).read()
html = opener.open(base + "/importar.php").read().decode("utf-8", "replace")
token = re.search(r'name="csrf"\s+value="([^"]+)"', html)
if not token:
    sys.exit("Token importação indisponível")
csrf=token.group(1)
header="linha_origem;segurado;vendedor;vencimento;produto;renovado;cobrado;nao_vai_renovar\n"
valid="2;SEGURO RNF04 FICTICIO;VENDEDOR TESTE;2027-10-12;Auto;0;0;0\n"
invalid="3;SEGURO RNF04 INVALIDO;VENDEDOR TESTE;2027-10-99;Auto;0;0;0\n"

def upload(csv):
    boundary="----MonitorRenovaRNF04"
    body=b""
    for key,value in (("csrf",csrf),("acao","prever"),("competencia","2027-10")):
        body+=(f"--{boundary}\r\nContent-Disposition: form-data; name=\"{key}\"\r\n\r\n{value}\r\n").encode()
    body+=(f"--{boundary}\r\nContent-Disposition: form-data; name=\"arquivo\"; filename=\"rnf04.csv\"\r\nContent-Type: text/csv\r\n\r\n").encode()
    body+=csv.encode() + f"\r\n--{boundary}--\r\n".encode()
    req=urllib.request.Request(base+"/importar.php",data=body,
        headers={"Content-Type":f"multipart/form-data; boundary={boundary}"},method="POST")
    return opener.open(req).read().decode("utf-8","replace")

out=upload(header+valid+invalid)
if "inválid" not in out and "inval" not in out.lower():
    print("RNF04 diagnóstico da resposta (sem credenciais):", re.sub(r"<[^>]+>", " ", out)[-1000:])
    sys.exit("RNF04: não houve erro visível de linha inválida")
print("RNF04 arquivo parcialmente válido rejeitado na prévia")

out=upload(header+valid)
if "Arquivo conferido" not in out:
    sys.exit("RNF04: prévia válida não foi exibida")
print("RNF04 prévia válida apresentada sem confirmação")

data=urllib.parse.urlencode({"csrf":csrf,"acao":"cancelar"}).encode()
out=opener.open(urllib.request.Request(base+"/importar.php",data=data,method="POST")).read().decode("utf-8","replace")
if "Prévia descartada" not in out:
    sys.exit("RNF04: cancelamento não confirmado")
print("RNF04 cancelamento confirmado; conferir zero gravações pelo SQL")
