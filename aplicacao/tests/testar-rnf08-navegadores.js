'use strict';
/**
 * RNF08: ensaio automatizado de navegação visual em Chromium, Firefox e Edge.
 * SOMENTE ambiente temporário CI com credenciais sintéticas.
 * Mantém screenshots para revisão visual humana posterior.
 */
const { chromium, firefox } = require('playwright');
const fs = require('fs');
const path = require('path');

const BASE = 'http://127.0.0.1:8088';
const pw = process.env.CI_RNF_PASSWORD;
if (process.env.CI_RNF_ISOLATED !== '1' || !pw || pw.length < 16) {
  throw new Error('RNF08 exige ambiente isolado com credenciais sintéticas');
}
const browsers = [
  { name: 'Chrome-Chromium', engine: chromium },
  { name: 'Firefox', engine: firefox },
  { name: 'Microsoft-Edge', engine: chromium, channel: 'msedge' },
];
const viewports = [
  { name: 'desktop', width: 1366, height: 768 },
  { name: 'mobile', width: 375, height: 812 },
];
const pages = [
  { name: 'inicio', route: '/' },
  { name: 'seguros', route: '/seguros.php' },
  { name: 'vendedores', route: '/vendedores.php' },
  { name: 'tarefas', route: '/tarefas.php' },
];
const outDir = path.join(process.cwd(), 'rnf08-evidencias');
fs.mkdirSync(outDir, { recursive: true });
let failed = 0, completed = 0;
const report = [];
(async () => {
  for (const config of browsers) {
    let browser;
    try {
      browser = await config.engine.launch({
        headless: true,
        ...(config.channel ? { channel: config.channel } : {}),
      });
      for (const vp of viewports) {
        const context = await browser.newContext({ viewport: { width: vp.width, height: vp.height } });
        const page = await context.newPage();
        try {
          const response = await page.goto(BASE + '/login.php', { waitUntil: 'domcontentloaded' });
          if (!response || response.status() !== 200) throw new Error('Login não abriu');
          await page.locator('input[name="senha"]').fill(pw);
          await page.locator('button[type="submit"], button').first().click();
          await page.waitForURL(url => !url.pathname.endsWith('login.php'), { timeout: 10000 });
          for (const item of pages) {
            const res = await page.goto(BASE + item.route, { waitUntil: 'networkidle' });
            if (!res || res.status() !== 200) throw new Error(item.route + ' HTTP ' + (res && res.status()));
            const info = await page.evaluate(() => ({
              width: document.documentElement.scrollWidth,
              client: document.documentElement.clientWidth,
              bodyText: document.body.innerText.slice(0, 600),
            }));
            if (!info.bodyText.includes('Monitor') && info.bodyText.trim().length < 10) {
              throw new Error('Conteúdo vazio ou tela inesperada em ' + item.route);
            }
            const filename = config.name + '-' + vp.name + '-' + item.name + '.png';
            await page.screenshot({ path: path.join(outDir, filename), fullPage: true });
            const overflow = info.width - info.client;
            report.push({ browser: config.name, viewport: vp.name, page: item.name,
              status: res.status(), overflow_px: overflow, screenshot: filename });
            completed++;
            // Um overflow global relevante indica que a interface precisa de conferência visual.
            if (overflow > 40) throw new Error('Transbordamento horizontal de ' + overflow + 'px em ' + item.route);
          }
        } catch (error) {
          failed++;
          report.push({ browser: config.name, viewport: vp.name, error: String(error) });
          console.error('RNF08 FAIL:', config.name, vp.name, String(error));
        } finally {
          await context.close();
        }
      }
    } catch (error) {
      failed++;
      report.push({ browser: config.name, error: String(error) });
      console.error('RNF08 LAUNCH FAIL:', config.name, String(error));
    } finally {
      if (browser) await browser.close();
    }
  }
  fs.writeFileSync(path.join(outDir, 'relatorio.json'), JSON.stringify(report, null, 2));
  console.log('RNF08 navegacoes=' + completed + ' falhas=' + failed +
    ' capturas=' + report.filter(x => x.screenshot).length);
  if (completed !== browsers.length * viewports.length * pages.length || failed !== 0) process.exitCode = 1;
})().catch(error => { console.error(error); process.exitCode = 1; });
