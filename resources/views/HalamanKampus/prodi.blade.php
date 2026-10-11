<style>

    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
        font-family: Georgia, "Times New Roman", serif;
        color: #111827;
        background: #fff;
    }

    .container {
        max-width: none;
        margin: 0 auto;
        padding: 34px 100px 40px;
    }

    .back-link {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 0.95rem;
        color: #111827;
        text-decoration: none;
        padding: 4px 0;
    }

    .back-link span { 
        transition: transform .2s; 
        display: inline-block; 
    }

    .back-link:hover span { 
        transform: translateX(-4px); 
    }

    .back-link:hover { 
        text-decoration: underline; 
    }

    .page-head { 
        margin: 0 0 26px; 
    }

    .page-head h1 { 
        font-size: 1.9rem; 
        line-height: 1.25; 
    }

    .page-head p  { 
        margin-top: 6px; 
        color: #4A4F58; 
        font-size: 1.05rem; 
    }

    .tabs { 
        display: flex; 
        flex-wrap: wrap; 
        gap: 27px; 
        margin-bottom: 29px; 
    }

    .tab {
        padding: 12px 30px;
        border-radius: 14px;
        background: #C9D1DB;
        color: #111827;
        font-weight: 700;
        font-size: 1rem;
        text-decoration: none;
        transition: background .2s;
    }
    .tab:hover { 
        background: #B7C1CE; 
    } 

    .tab.active { 
        background: #FFBC5C; 
    }
    
    .tab.active:hover { 
        background: #F5A83A; 
    }

    .toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 18px;
        margin-right: 0;
    }
    .filters { 
        display: flex; 
        flex-wrap: wrap; 
        gap: 8px; 
    }

    .chip {
        border: 2px solid #C9D1DB;
        background: #fff;
        color: #111827;
        font-family: inherit;
        font-size: 0.92rem;
        padding: 6px 16px;
        border-radius: 999px;
        cursor: pointer;
        transition: background .2s, border-color .2s;
    }

    .chip:hover { 
        border-color: 
        #FF7822; 
    }

    .chip.active { 
        background: #FF7822; 
        border-color: #FF7822; 
        color: #fff; 
    }

    .chip:focus-visible, .row:focus-visible, .tab:focus-visible, .back-link:focus-visible {
        outline: 3px solid #FF7822;
        outline-offset: 3px;
    }

    .count { 
        color: #4A4F58; 
        font-size: 0.92rem; 
    }

    .list { 
        display: flex; 
        flex-direction: column; 
        gap: 16px; 
        margin-right: 0; 
    }

    .row {
        min-height: 58px;
        display: flex;
        align-items: stretch;
        border-radius: 10px;
        overflow: hidden;
        text-decoration: none;
        color: #111827;
        background: #C9D1DB;
        transition: transform .15s, box-shadow .15s;
    }
  
    .row.alt { 
        background: #FFBC5C; 
    }

    .row:hover { 
        transform: translateY(-2px); 
        box-shadow: 0 6px 14px rgba(0,0,0,.12); 
    }

    .row-body {
        flex: 1;
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 0 28px;
        min-width: 0;
    }

    .jenjang {
        flex-shrink: 0;
        min-width: 40px;
        text-align: center;
        background: rgba(255,255,255,.65);
        border-radius: 8px;
        padding: 3px 8px;
        font-size: 0.85rem;
        font-weight: 700;
    }

    .nama { 
        font-size: 1.1rem; 
    }

    .row-arrow {
        width: 70px;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #FF7822;
        color: #fff;
        border-radius: 10px;
        transition: background .2s;
    }

    .row:hover .row-arrow { 
        background: #F5A83A; 
    }   

    .row-arrow svg { 
        width: 26px; 
        height: 26px; 
        transition: transform .2s; 
    }

    .row:hover .row-arrow svg { 
        transform: translateX(4px); 
    }

    .empty {
        display: none;
        padding: 28px;
        text-align: center;
        color: #4A4F58;
        border: 2px dashed #C9D1DB;
        border-radius: 14px;
    }

    @media (max-width: 600px) {
        .container { 
            padding: 24px 16px 32px; 
        }
        .list {
            margin-right: 0;
        }
        .page-head h1 { 
            font-size: 1.4rem; 
        }
        .tab { 
            padding: 10px 18px; 
            font-size: .92rem; 
        }
        .row-body { 
            padding: 10px 16px; 
            gap: 10px; 
        }
        .nama { 
            font-size: 1rem; 
        }
        .row-arrow { 
            width: 70px; 
        }
        .tabs {
            gap: 12px;
        }
        .row {
            min-height: 64px;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        * { transition: none !important; }
    }
</style>

@include('navbar')

<main class="container">

    <a href="#" class="back-link"><span>&larr;</span> Kembali ke Fakultas</a>

    <header class="page-head">
        <h1>Fakultas Ilmu Komputer dan Teknologi Informasi</h1>
        <p>Universitas Sumatera Utara</p>
    </header>

    <nav class="tabs" aria-label="Menu fakultas">
        <a href="#" class="tab active" aria-current="page">Program Studi</a>
        <a href="#" class="tab">Profil Fakultas</a>
        <a href="#" class="tab">Akreditasi</a>
    </nav>

    <div class="toolbar">
        <div class="filters" role="group" aria-label="Filter jenjang">
            <button class="chip active" data-filter="all">Semua</button>
            <button class="chip" data-filter="S1">S1</button>
            <button class="chip" data-filter="S2">S2</button>
            <button class="chip" data-filter="S3">S3</button>
        </div>
        <p class="count" id="count" aria-live="polite"></p>
    </div>

    <section class="list" id="list">
        <a href="#" class="row" data-jenjang="S1">
            <div class="row-body"><span class="jenjang">S1</span><span class="nama">Teknologi Informasi</span></div>
            <span class="row-arrow" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 5l7 7-7 7"/></svg>
            </span>
        </a>

        <a href="#" class="row" data-jenjang="S1">
            <div class="row-body"><span class="jenjang">S1</span><span class="nama">Ilmu Komputer</span></div>
            <span class="row-arrow" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 5l7 7-7 7"/></svg>
            </span>
        </a>

        <a href="#" class="row" data-jenjang="S2">
            <div class="row-body"><span class="jenjang">S2</span><span class="nama">Kecerdasan Buatan</span></div>
            <span class="row-arrow" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 5l7 7-7 7"/></svg>
            </span>
        </a>

        <a href="#" class="row" data-jenjang="S3">
            <div class="row-body"><span class="jenjang">S3</span><span class="nama">Teknik Informatika</span></div>
            <span class="row-arrow" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 5l7 7-7 7"/></svg>
            </span>
        </a>

        <p class="empty" id="empty">Belum ada program studi untuk jenjang ini.</p>
    </section>
</main>

<script>
    const chips = document.querySelectorAll('.chip');
    const rows  = document.querySelectorAll('.row');
    const count = document.getElementById('count');
    const empty = document.getElementById('empty');

    function apply(filter) {
    let shown = 0;
        rows.forEach(row => {
        const match = filter === 'all' || row.dataset.jenjang === filter;
        row.style.display = match ? 'flex' : 'none';
        row.classList.remove('alt');
        if (match) {
        if (shown % 2 === 0) row.classList.add('alt'); // baris ganjil = amber
        shown++;
        }
    });        
    count.textContent = shown + ' program studi';
    empty.style.display = shown ? 'none' : 'block';
    }

    chips.forEach(chip => chip.addEventListener('click', () => {
    chips.forEach(c => c.classList.remove('active'));
    chip.classList.add('active');
    apply(chip.dataset.filter);
    }));

    apply('all');
</script>
