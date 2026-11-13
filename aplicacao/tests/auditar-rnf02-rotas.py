#!/usr/bin/env python3
"""RNF02: inventário estático de TODOS os pontos de entrada PHP web.
É verificação de cobertura de controles no código; NÃO é pentest.
"""
from pathlib import Path
import sys
root = Path(__file__).resolve().parents[1] / "public"
public = {"login.php", "configurar-acesso.php"}
token_markers = ("HTTP_X_MONITOR_TOKEN", "hash_equals(")
token_endpoints = {"ingest.php", "preview-ledger.php", "resumo-aprovado.php",
                   "sync-sheet.php", "n8n-historico-ingest.php"}
errors = []
rows = []
for p in sorted(root.glob("*.php")):
    text = p.read_text(encoding="utf-8")
    if p.name in public:
        acceptable = ("verifyCsrf(" in text and "password_hash(" in text) if p.name == "configurar-acesso.php" else ("verifyCsrf(" in text and "password_verify(" in text)
        kind = "login/configuracao publica"
    elif p.name == "sair.php":
        acceptable = ("Auth::boot(" in text and "verifyCsrf(" in text)
        kind = "logout protegido por CSRF"
    elif p.name in token_endpoints:
        acceptable = all(marker in text for marker in token_markers)
        kind = "API interna X-Monitor-Token"
    else:
        acceptable = "Auth::requireLogin(" in text
        kind = "autenticacao de sessao"
        if "$_POST" in text or "$_SERVER['REQUEST_METHOD']" in text and "POST" in text:
            # Mutacoes exigem verificação; handlers públicos e APIs token são tratados acima.
            acceptable &= "verifyCsrf(" in text or "hash_equals($_SESSION['csrf']" in text
    rows.append((p.name, kind, acceptable))
    if not acceptable: errors.append(p.name)
for name, kind, passed in rows:
    print(("OK" if passed else "FALHA") + f" RNF02 {name}: {kind}")
print(f"RNF02 inventario={len(rows)} entradas; nao_conformes={len(errors)}")
if len(rows) < 30:
    errors.append("Quantidade inesperada de pontos de entrada")
sys.exit(1 if errors else 0)
