(function () {
  const state = { q: '', kategori: '', jenis: '', provinsi: '', kota: '', jenjang: '', prodi: '', page: 1 };

  const grid = document.getElementById('uni-grid');
  const empty = document.getElementById('uni-empty');
  const pager = document.getElementById('uni-pagination');
  const items = document.querySelectorAll('.dropdown-item');
  const FALLBACK_IMG = 'https://placehold.co/600x300/fcebd8/ee7f32?text=Foto+Kampus';

  const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const $item = (name) => document.querySelector(`.dropdown-item[data-filter="${name}"]`);
  const api = async (url) => {
    const r = await fetch(url, { headers: { Accept: 'application/json' } });
    if (!r.ok) {
      let detail = '';
      try { const j = await r.json(); detail = j.message || JSON.stringify(j); } catch (_) {}
      throw new Error('HTTP ' + r.status + ' - ' + detail);
    }
    return r.json();
  };

  /* ---------- Dropdown ---------- */
  function fillDropdown(name, options) {
    // options: [{value,label}]
    const box = $item(name).querySelector('.dd-options');
    box.innerHTML = '';
    const all = document.createElement('a');
    all.href = '#'; all.dataset.value = ''; all.textContent = 'Semua';
    box.appendChild(all);
    options.forEach((o) => {
      const a = document.createElement('a');
      a.href = '#'; a.dataset.value = o.value; a.textContent = o.label;
      if (state[name] === o.value) a.classList.add('active');
      box.appendChild(a);
    });
  }

  function setValue(name, value, label) {
    state[name] = value;
    const it = $item(name);
    const lbl = it.querySelector('.dd-label');
    lbl.textContent = value ? label : lbl.dataset.default;
    it.querySelector('.dropdown-btn').classList.toggle('has-value', !!value);
  }

  async function loadOptions() {
    const o = await api('/api/filter-options' + (state.provinsi ? '?provinsi=' + encodeURIComponent(state.provinsi) : ''));
    fillDropdown('jenis', o.jenis.map((v) => ({ value: v, label: v })));
    fillDropdown('provinsi', o.provinsi);
    fillDropdown('kota', o.kota);
    fillDropdown('jenjang', o.jenjang.map((v) => ({ value: v, label: v })));
    loadProdi('');
  }

  let prodiTimer;
  async function loadProdi(q) {
    const p = new URLSearchParams({ q });
    if (state.jenjang) p.set('jenjang', state.jenjang);
    const list = await api('/api/prodi?' + p);
    fillDropdown('prodi', list.map((v) => ({ value: v, label: v })));
  }

  items.forEach((it) => {
    const btn = it.querySelector('.dropdown-btn');
    const menu = it.querySelector('.dropdown-menu');
    const name = it.dataset.filter;

    btn.addEventListener('click', () => {
      document.querySelectorAll('.dropdown-menu').forEach((m) => { if (m !== menu) m.style.display = 'none'; });
      menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
    });

    menu.addEventListener('click', async (e) => {
      const a = e.target.closest('a');
      if (!a) return;
      e.preventDefault();
      setValue(name, a.dataset.value, a.textContent);
      menu.style.display = 'none';

      if (name === 'provinsi') {            // kota mengikuti provinsi
        setValue('kota', '', '');
        await loadOptions();
      }
      if (name === 'jenjang') loadProdi('');
    });
  });

  const search = document.querySelector('.dd-search');
  if (search) {
    search.addEventListener('input', () => {
      clearTimeout(prodiTimer);
      prodiTimer = setTimeout(() => loadProdi(search.value.trim()), 300);
    });
  }

  document.addEventListener('click', (e) => {
    if (!e.target.closest('.dropdown-item'))
      document.querySelectorAll('.dropdown-menu').forEach((m) => (m.style.display = 'none'));
  });

  /* ---------- Card ---------- */
  function card(k) {
    const ukt = k.ukt
      ? `<p style="color:#6b7280;">Rentang UKT</p><p class="ukt-price">${esc(k.ukt)}</p>`
      : `<p style="color:#6b7280;">Akreditasi</p><p class="ukt-price">${esc(k.akreditasi || '-')}</p>`;
    return `
      <div class="uni-card">
        <div>
          <img src="${esc(k.gambar || FALLBACK_IMG)}" alt="${esc(k.nama)}" class="card-img" loading="lazy" referrerpolicy="no-referrer">
          <div class="card-body">
            <div class="card-header">
              <h3 class="uni-name">${esc(k.nama)}</h3>
              <span class="badge">${esc(k.singkatan)}</span>
            </div>
            <div class="info-box">
              <div class="info-left">
                <p style="font-weight:bold;">${k.fakultas} Fakultas</p>
                <p style="color:#6b7280;">${k.prodi} Prodi</p>
              </div>
              <div class="info-right" style="text-align:right;">${ukt}</div>
            </div>
            <p class="card-desc">${esc(k.deskripsi)}</p>
          </div>
        </div>
        <div style="padding:0 20px 20px;">
          <a href="${esc(k.url)}" class="btn-detail">Lihat Detail</a>
        </div>
      </div>`;
  }

  // contoh hasil: [1, 2, 3, '…', 17] atau [1, '…', 8, 9, 10, '…', 17]
  function pageList(c, l) {
    if (l <= 7) return Array.from({ length: l }, (_, i) => i + 1);
    let s = Math.max(2, c - 1), e = Math.min(l - 1, c + 1);
    if (c <= 3) { s = 2; e = 4; }
    if (c >= l - 2) { s = l - 3; e = l - 1; }
    const out = [1];
    if (s > 2) out.push('…');
    for (let i = s; i <= e; i++) out.push(i);
    if (e < l - 1) out.push('…');
    out.push(l);
    return out;
  }

  function goPage(n) {
    state.page = n;
    loadKampus();
    grid.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  function renderPager(meta) {
    pager.innerHTML = '';
    if (meta.last_page <= 1) return;

    const mk = (label, { page = null, active = false, dots = false, disabled = false, aria = '' } = {}) => {
      const b = document.createElement('button');
      b.type = 'button';
      b.className = 'pg-btn' + (active ? ' active' : '') + (dots ? ' dots' : '');
      b.textContent = label;
      if (aria) b.setAttribute('aria-label', aria);
      if (active) b.setAttribute('aria-current', 'page');
      if (disabled || dots) b.disabled = true;
      if (page !== null && !disabled && !active) b.addEventListener('click', () => goPage(page));
      pager.appendChild(b);
    };

    mk('‹', { page: meta.page - 1, disabled: meta.page <= 1, aria: 'Halaman sebelumnya' });
    pageList(meta.page, meta.last_page).forEach((n) =>
      n === '…' ? mk('…', { dots: true }) : mk(n, { page: n, active: n === meta.page, aria: 'Halaman ' + n })
    );
    mk('›', { page: meta.page + 1, disabled: meta.page >= meta.last_page, aria: 'Halaman berikutnya' });
  }

  async function loadKampus() {
    const p = new URLSearchParams();
    Object.entries(state).forEach(([k, v]) => { if (v) p.set(k, v); });
    grid.style.opacity = 0.5;
    try {
      const res = await api('/api/kampus?' + p);
      grid.innerHTML = res.data.map(card).join('');
      empty.style.display = res.data.length ? 'none' : 'block';
      renderPager(res.meta);
    } catch (err) {
      console.error(err);
      grid.innerHTML = '';
      empty.textContent = 'Gagal memuat data: ' + err.message;
      empty.style.display = 'block';
    } finally {
      grid.style.opacity = 1;
    }
  }

  // kalau logo gagal dimuat (link mati), pakai placeholder
  grid.addEventListener('error', (e) => {
    if (e.target.tagName === 'IMG' && !e.target.dataset.fb) {
      e.target.dataset.fb = '1';
      e.target.src = FALLBACK_IMG;
    }
  }, true);

  /* ---------- Kolom cari (atas) ---------- */
  const searchInput = document.getElementById('search-input');
  if (searchInput) {
    const doSearch = () => { state.q = searchInput.value.trim(); state.page = 1; loadKampus(); };
    searchInput.addEventListener('keydown', (e) => { if (e.key === 'Enter') doSearch(); });
    const iconBox = document.querySelector('.search-icon-box');
    if (iconBox) { iconBox.style.cursor = 'pointer'; iconBox.addEventListener('click', doSearch); }
  }

  /* ---------- Tombol ---------- */
  document.getElementById('btn-filter').addEventListener('click', () => { state.page = 1; loadKampus(); });

  document.getElementById('btn-clear').addEventListener('click', async () => {
    ['jenis', 'provinsi', 'kota', 'jenjang', 'prodi'].forEach((n) => setValue(n, '', ''));
    state.kategori = ''; state.q = ''; state.page = 1;
    if (searchInput) searchInput.value = '';
    document.querySelectorAll('.cat-btn').forEach((b, i) => b.classList.toggle('active', i === 0));
    await loadOptions();
    loadKampus();
  });

  document.querySelectorAll('.cat-btn').forEach((b) =>
    b.addEventListener('click', () => {
      document.querySelectorAll('.cat-btn').forEach((x) => x.classList.remove('active'));
      b.classList.add('active');
      state.kategori = b.dataset.kategori; state.page = 1;
      loadKampus();
    })
  );

  loadOptions().catch(console.error);
  loadKampus();
})();