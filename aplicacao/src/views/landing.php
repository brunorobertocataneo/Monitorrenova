<?php
declare(strict_types=1);
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('Cache-Control: no-store');
header("Content-Security-Policy: default-src 'self'; style-src 'self'; base-uri 'none'; frame-ancestors 'none'");
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="theme-color" content="#f3f8f4">
<title>MonitorRenova | Tudo sob controle</title>
<link rel="stylesheet" href="/assets/landing.css?v=1">
</head>
<body class="landing">
<main class="landing-main">
  <section class="hero" aria-label="MonitorRenova">
    <div class="hero-copy">
      <div class="brand"><span class="brand-mark" aria-hidden="true">MR</span><span>MonitorRenova</span></div>
      <h1>Tudo sob<br>controle<span>.</span></h1>
      <h2>Gestão inteligente para corretores de seguros.</h2>
      <p>Centralize renovações, vencimentos e tarefas com mais organização e agilidade.</p>
      <a class="hero-button" href="/login.php">Acessar plataforma <span aria-hidden="true">&#8599;</span></a>
      <a class="admin-link" href="/login.php"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg> Login do administrador</a>
    </div>
    <div class="hero-art" aria-hidden="true">
      <div class="art-glow"></div><div class="floor-shadow"></div>
      <svg class="folder" viewBox="0 0 520 450" xmlns="http://www.w3.org/2000/svg">
        <defs>
          <linearGradient id="back" x1="0" y1="0" x2=".9" y2="1"><stop stop-color="#4e9d72"/><stop offset=".42" stop-color="#216342"/><stop offset="1" stop-color="#123e2b"/></linearGradient>
          <linearGradient id="face" x1=".05" y1="0" x2=".8" y2="1"><stop stop-color="#6aa781"/><stop offset=".22" stop-color="#438b62"/><stop offset=".65" stop-color="#286847"/><stop offset="1" stop-color="#194c34"/></linearGradient>
          <linearGradient id="edge" x1="0" y1="0" x2="1" y2="1"><stop stop-color="#c3dfc9"/><stop offset=".35" stop-color="#6ca47e"/><stop offset="1" stop-color="#123e2b"/></linearGradient>
          <linearGradient id="paper" x1="0" y1="0" x2="1" y2="1"><stop stop-color="#fff"/><stop offset="1" stop-color="#dce9df"/></linearGradient>
          <linearGradient id="paperShade" x1="0" y1="0" x2="0" y2="1"><stop stop-color="#fff"/><stop offset="1" stop-color="#bacdc0"/></linearGradient>
          <filter id="shadow" x="-30%" y="-30%" width="160%" height="170%"><feDropShadow dx="1" dy="9" stdDeviation="8" flood-color="#173d28" flood-opacity=".25"/></filter>
          <filter id="grain"><feTurbulence type="fractalNoise" baseFrequency=".8" numOctaves="3" stitchTiles="stitch"/><feColorMatrix type="saturate" values="0"/></filter>
          <clipPath id="faceClip"><path d="M75 223 Q76 213 93 213 L448 196 Q466 195 461 212 L425 358 Q421 373 402 375 L107 391 Q91 392 87 377 L57 241 Q54 225 75 223Z"/></clipPath>
        </defs>
        <path d="M76 169 Q76 154 94 153 L174 149 Q185 149 194 158 L218 181 L425 170 Q443 169 443 187 L433 347 Q432 365 413 368 L104 383 Q85 383 85 363Z" fill="#113d29" transform="translate(6 5)"/>
        <path d="M76 169 Q76 154 94 153 L174 149 Q185 149 194 158 L218 181 L425 170 Q443 169 443 187 L433 347 Q432 365 413 368 L104 383 Q85 383 85 363Z" fill="url(#back)" stroke="#75a789" stroke-width="1.5"/>
        <g class="sheet-back" filter="url(#shadow)">
          <g transform="rotate(12 345 210)">
            <rect x="270" y="64" width="142" height="236" rx="5" fill="url(#paperShade)" stroke="#c5d2c8"/>
            <path d="M289 93H367M289 104H343" stroke="#4d7b60" stroke-width="5" stroke-linecap="round"/>
            <path d="M289 131H390M289 145H382M289 159H390M289 173H357M289 198H390M289 212H376" stroke="#b9cbbd" stroke-width="4" stroke-linecap="round"/>
          </g>
        </g>
        <g class="sheet-front" filter="url(#shadow)">
          <g transform="rotate(-9 204 198)">
            <rect x="121" y="45" width="166" height="263" rx="6" fill="url(#paper)" stroke="#d0ddd3"/>
            <path d="M140 68H183" stroke="#387252" stroke-width="7" stroke-linecap="round"/>
            <path d="M140 85H222" stroke="#8ba795" stroke-width="4" stroke-linecap="round"/>
            <path d="M140 111H264M140 126H252M140 141H264M140 156H236M140 181H264M140 196H249" stroke="#baccc0" stroke-width="4" stroke-linecap="round"/>
            <rect x="141" y="218" width="122" height="47" rx="4" fill="#e1eee4" stroke="#c3d5c8"/>
            <path d="M153 235l8 8 13-15" stroke="#3e8059" stroke-width="3" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M185 235H251M185 246H230" stroke="#9cbba6" stroke-width="3" stroke-linecap="round"/>
          </g>
        </g>
        <path d="M75 223 Q76 213 93 213 L448 196 Q466 195 461 212 L425 358 Q421 373 402 375 L107 391 Q91 392 87 377 L57 241 Q54 225 75 223Z" fill="#133e2a" transform="translate(4 5)" filter="url(#shadow)"/>
        <path d="M75 223 Q76 213 93 213 L448 196 Q466 195 461 212 L425 358 Q421 373 402 375 L107 391 Q91 392 87 377 L57 241 Q54 225 75 223Z" fill="url(#face)" stroke="url(#edge)" stroke-width="2"/>
        <rect x="50" y="190" width="420" height="210" filter="url(#grain)" opacity=".045" clip-path="url(#faceClip)"/>
        <path d="M75 224L447 207" stroke="#c5dfca" stroke-width="1.4" opacity=".55" fill="none"/>
        <g transform="translate(225 288) rotate(-3)"><rect width="66" height="38" rx="5" fill="#173f2c" opacity=".23"/><text x="33" y="25" fill="#e0eee3" font-size="17" font-weight="600" font-family="system-ui,sans-serif" text-anchor="middle" letter-spacing="2">MR</text></g>
      </svg>
    </div>
  </section>
</main>
</body>
</html>
