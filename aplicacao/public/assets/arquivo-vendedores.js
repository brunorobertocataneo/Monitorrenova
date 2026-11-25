(() => {
  const cabinet = document.getElementById('vendor-folder-list');
  const search = document.getElementById('buscar-vendedor');
  const counter = document.getElementById('vendedores-visiveis');
  const empty = document.getElementById('vendor-no-results');
  const drawers = [...cabinet.querySelectorAll('a[data-vendor-name]')];
  const reducedMotion = matchMedia('(prefers-reduced-motion: reduce)');
  const normalize = text => text.normalize('NFD').replace(/\p{Diacritic}/gu, '').toLocaleLowerCase('pt-BR');
  const names = drawers.map(drawer => normalize(drawer.dataset.vendorName));
  let opening = null;

  function filterDrawers() {
    const term = normalize(search.value.trim());
    let count = 0;
    drawers.forEach((drawer, index) => {
      const matches = names[index].includes(term);
      drawer.hidden = !matches;
      if (matches) count++;
    });
    cabinet.hidden = count === 0;
    counter.textContent = count === 1 ? '1 pasta encontrada' : `${count} pastas encontradas`;
    if (empty) empty.hidden = count !== 0;
  }

  search.addEventListener('input', filterDrawers);

  cabinet.addEventListener('click', event => {
    const drawer = event.target.closest('a[data-vendor-name]');
    if (!drawer || reducedMotion.matches || event.defaultPrevented ||
        event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
    event.preventDefault();
    if (opening) return;
    opening = drawer;
    drawer.classList.add('is-opening');
    window.setTimeout(() => window.location.assign(drawer.href), 260);
  });

  window.addEventListener('pageshow', () => {
    if (opening) opening.classList.remove('is-opening');
    opening = null;
    filterDrawers();
  });
})();
