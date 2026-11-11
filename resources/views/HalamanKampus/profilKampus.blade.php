@include('navbar')
@include('chatbot')

<link href="https://fonts.googleapis.com/css2?family=Alike&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<link rel="stylesheet" href="{{ asset('css/style.css') }}">

<!-- ATRIBUT SEMANTIK RDFA: vocab Schema.org dan typeof CollegeOrUniversity -->
<header class="univ-header" vocab="https://schema.org/" typeof="CollegeOrUniversity">
    <div class="univ-logo">
        <img property="logo"
             src="{{ $k['logo'] ?? 'https://placehold.co/220x220/fcebd8/ee7f32?text=Kampus' }}"
             alt="Logo {{ $k['nama'] }}"
             referrerpolicy="no-referrer"
             style="object-fit: contain; padding: 8px; background: #fff;"
             onerror="this.onerror=null;this.src='https://placehold.co/220x220/fcebd8/ee7f32?text=Kampus'">
    </div>

    <div class="univ-info">
        <!-- property="name" : Memberitahu mesin nama resmi institusi -->
        <h1 property="name">{{ $k['nama'] }}</h1>

        <!-- property="address" : Memberitahu mesin data lokasi terstruktur -->
        <div class="univ-location" property="address" typeof="PostalAddress">
            <svg viewBox="0 0 14 20" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M7 0C3.1 0 0.5 2.8 0.5 6.2c0 3.3 3.3 7 6.5 8.6c3.2-1.6 6.5-5.3 6.5-8.6C13.5 2.8 10.9 0 7 0z" fill="#000"/>
                <circle cx="7" cy="6" r="2.4" fill="#fff"/>
                <ellipse cx="7" cy="16.6" rx="4.2" ry="2.3" fill="none" stroke="#000" stroke-width="1.4"/>
            </svg>
            <span>
                <span property="addressLocality">{{ $k['kota'] }}</span>, 
                <span property="addressRegion">{{ $k['provinsi'] }}</span>
            </span>
        </div>

        <div class="univ-chips">
            <span class="univ-chip">{{ $k['label_kat'] }}</span>
            @if($k['akreditasi'])
                <span class="univ-chip">Akreditasi {{ $k['akreditasi'] }}</span>
            @endif
        </div>
    </div>

    <div class="univ-actions" style="display: flex; gap: 8px; flex-wrap: wrap;">
        @if($k['website'])
            <!-- property="url" : Memberitahu mesin alamat website resmi -->
            <a property="url" href="{{ $k['website'] }}" target="_blank" rel="noopener" class="btn-outline btn-website">Website Resmi</a>
        @endif
        @if(!empty($dbpedia['wiki']))
            <a href="{{ $dbpedia['wiki'] }}" target="_blank" rel="noopener" class="btn-outline">Wikipedia</a>
        @endif
    </div>
</header>

<nav class="tabs">
    <a href="#profil">Profil</a>
    <a href="#fakultas">Fakultas &amp; Program Studi</a>
    <a href="#lokasi">Lokasi Kampus</a>
    <a href="#biaya">Biaya UKT</a>
</nav>

<section id="profil">
    <h2>Profil</h2>
    @if(!empty($dbpedia['motto']))
        <div style="margin-bottom: 22px; padding: 14px 22px; border-left: 4px solid var(--o); background: var(--c); border-radius: 0 14px 14px 0;">
            <small style="display: block; font-size: 11px; text-transform: uppercase; font-weight: 700; letter-spacing: 0.8px; color: var(--o); margin-bottom: 4px;">Motto</small>
            <span style="font-size: 16.5px; font-style: italic; color: #1a1a1a; font-weight: 500;">"{{ $dbpedia['motto'] }}"</span>
        </div>
    @endif
    <p class="description">{{ $k['deskripsi'] ?? ($k['nama'].' berlokasi di '.$k['kota'].', '.$k['provinsi'].'.') }}</p>
    <div class="meta" style="grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));">
        <div><small>Jenis Institusi</small>{{ $k['jenis'] }}</div>
        <div><small>Tahun Berdiri</small>{{ $k['tahun'] ?? '-' }}</div>
        <div><small>Akreditasi</small>{{ $k['akreditasi'] ?? '-' }}</div>
        <div><small>Fakultas</small>{{ count($fakultas) }}</div>
        @if(!empty($dbpedia['rector']))
            <div><small>Pimpinan / Rektor</small>{{ $dbpedia['rector'] }}</div>
        @endif
    </div>
</section>

<section id="fakultas">
    <h2>Fakultas &amp; Program Studi</h2>

    <input class="radio-hidden" type="radio" name="tampilan" id="tampil-fakultas" checked>
    <input class="radio-hidden" type="radio" name="tampilan" id="tampil-prodi">

    <div class="pills">
        <label class="pill" for="tampil-fakultas">Fakultas</label>
        <label class="pill" for="tampil-prodi">Program Studi</label>
    </div>

    <!-- FITUR SEARCH & FILTER JENJANG -->
    @php
      
        $daftarJenjangUnik = collect($prodi)->pluck('jenjang')->unique()->filter()->values();
    @endphp

    <div style="max-width: 1100px; margin: 20px 0 25px;">
        <!-- Input Search Bar -->
        <div style="position: relative; margin-bottom: 14px;">
            <input type="text" id="search-akademik" placeholder="Cari nama fakultas atau program studi..." 
                   style="width: 100%; padding: 12px 20px; border: 2.5px solid #ffbf5f; border-radius: 30px; font-size: 15px; outline: none; box-sizing: border-box; background: #ffffff; color: #1a1a1a; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
        </div>

        <!-- Tombol Filter Jenjang (Otomatis tampil saat tab Program Studi aktif) -->
        <div id="filter-jenjang-wrapper" style="display: none; gap: 8px; flex-wrap: wrap; align-items: center;">
            <span style="font-size: 14px; font-weight: bold; color: #555; margin-right: 4px;">Jenjang:</span>
            <button type="button" class="btn-filter-jenjang active" data-jenjang="" 
                    style="border: 1.5px solid #ffbf5f; background: #ffbf5f; color: #1a1a1a; padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: bold; cursor: pointer; transition: all .2s ease;">
                Semua
            </button>
            @foreach($daftarJenjangUnik as $j)
                <button type="button" class="btn-filter-jenjang" data-jenjang="{{ $j }}" 
                        style="border: 1.5px solid #e3e3e3; background: #ffffff; color: #555; padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: bold; cursor: pointer; transition: all .2s ease;">
                    {{ $j }}
                </button>
            @endforeach
        </div>
    </div>

    <!-- LIST FAKULTAS -->
    <div class="listFakultas">
        @forelse($fakultas as $f)
            <a class="row row-fakultas" href="{{ route('fakultas', ['id' => $kode, 'nama' => $f['nama']]) }}">
                <div class="row-title">{{ $f['nama'] }}</div>
                <div class="row-arrow">&gt;</div>
            </a>
        @empty
            <p class="kosong">Data fakultas belum tersedia.</p>
        @endforelse
        <p id="empty-fakultas-search" style="display: none; text-align: center; color: #888; padding: 30px; font-size: 16px;">
            Tidak ada fakultas yang cocok dengan pencarian.
        </p>
    </div>

    <!-- LIST PROGRAM STUDI -->
    <div class="listProdi">
        @forelse($prodi as $p)
            <a class="row row-prodi" data-jenjang="{{ $p['jenjang'] }}" href="{{ route('prodi', ['id' => $kode, 'kode' => $p['kode']]) }}">
                <div class="row-title">{{ $p['jenjang'] }} {{ $p['nama'] }}
                    <small>{{ $p['fakultas'] }}</small>
                </div>
                <div class="row-arrow">&gt;</div>
            </a>
        @empty
            <p class="kosong">Data program studi belum tersedia.</p>
        @endforelse
        <p id="empty-prodi-search" style="display: none; text-align: center; color: #888; padding: 30px; font-size: 16px;">
            Tidak ada program studi yang cocok dengan pencarian / filter jenjang.
        </p>
    </div>
</section>

<section id="lokasi">
    <h2>Lokasi Kampus</h2>
    @php
        $semuaLokasi = array_merge([[
            'nama'     => count($lokasi) ? $k['singkatan'] . ' Kampus 1' : $k['nama'],
            'kota'     => $k['kota'],
            'provinsi' => $k['provinsi'],
            'alamat'   => $k['alamat'],
            'lat'      => $k['lat'],
            'lng'      => $k['lng'],
        ]], $lokasi);

        $lokasiUtama = $semuaLokasi[0];
        $hasCoords = !empty($lokasiUtama['lat']) && !empty($lokasiUtama['lng']);
    @endphp

    <div class="lokasi-wrapper">
        <div class="lokasi-info-card">
            <div>
                <span class="lokasi-badge">Kampus Utama</span>
                <h3 class="lokasi-title">{{ $lokasiUtama['nama'] }}</h3>
                
                <div class="lokasi-detail">
                    <div class="lokasi-item">
                        <small>Wilayah</small>
                        <p>{{ $lokasiUtama['kota'] }}, {{ $lokasiUtama['provinsi'] }}</p>
                    </div>

                    @if(!empty($lokasiUtama['alamat']))
                        <div class="lokasi-item">
                            <small>Alamat Lengkap</small>
                            <p>{{ $lokasiUtama['alamat'] }}</p>
                        </div>
                    @endif

                    @if($hasCoords)
                        <div class="lokasi-item">
                            <small>Koordinat Geografis</small>
                            <p class="lokasi-coords">{{ $lokasiUtama['lat'] }}, {{ $lokasiUtama['lng'] }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <div class="lokasi-actions" style="margin-top: 18px;">
                @if($hasCoords)
                    <a href="https://www.google.com/maps/search/?api=1&query={{ $lokasiUtama['lat'] }},{{ $lokasiUtama['lng'] }}" target="_blank" rel="noopener" class="btn-peta-google">
                        Buka di Google Maps &nearr;
                    </a>
                @else
                    <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($lokasiUtama['nama'].' '.$lokasiUtama['kota']) }}" target="_blank" rel="noopener" class="btn-peta-google">
                        Buka di Google Maps &nearr;
                    </a>
                @endif
            </div>
        </div>

        <div class="lokasi-map-container">
            <div id="mini-map-kampus" class="mini-map"></div>
        </div>
    </div>

    @if(count($semuaLokasi) > 1)
        <div class="lokasi-cabang-grid">
            @foreach(array_slice($semuaLokasi, 1) as $idx => $cabang)
                <div class="lokasi-cabang-card">
                    <span class="lokasi-badge" style="background:#f4f4f5;color:#52525b;border-color:#e4e4e7;">Kampus {{ $idx + 2 }}</span>
                    <h4 style="margin:6px 0 10px;font-size:17px;color:#1a1a1a;">{{ $cabang['nama'] }}</h4>
                    <p style="margin:0 0 10px;font-size:13.5px;color:#444;">
                        <b>{{ $cabang['kota'] }}, {{ $cabang['provinsi'] }}</b>
                        @if(!empty($cabang['alamat']))<br>{{ $cabang['alamat'] }}@endif
                    </p>
                    @if(!empty($cabang['lat']) && !empty($cabang['lng']))
                        <a href="https://www.google.com/maps/search/?api=1&query={{ $cabang['lat'] }},{{ $cabang['lng'] }}" target="_blank" rel="noopener" style="color:var(--o);font-size:13px;font-weight:700;text-decoration:none;">Buka Google Maps &nearr;</a>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</section>

@php
    // tab jenjang (sama seperti desain asli) => jenjang di data TTL
    $tabUkt = [
        'd1' => ['Diploma 1', ['D1']],
        'd2' => ['Diploma 2', ['D2']],
        'd3' => ['Diploma 3', ['D3']],
        'd4' => ['Diploma 4', ['D4']],
        's1' => ['Sarjana', ['S1']],
        's2' => ['Magister', ['S2', 'S2 Terapan']],
        's3' => ['Pendidikan Doktor', ['S3', 'S3 Terapan']],
    ];
    // kolom UKT 1..UKT 5+ diambil dari data kelompok UKT kampus
    $kolomUkt = array_map(fn ($u) => [
        'label'   => 'UKT ' . trim(str_replace('Kelompok', '', $u['kelompok'])),
        'rentang' => $u['rentang'],
    ], $ukt);
@endphp

<section id="biaya">
    <h2>Biaya UKT</h2>

    <input class="radio-hidden" type="radio" name="jenjang" id="j-d1">
    <input class="radio-hidden" type="radio" name="jenjang" id="j-d2">
    <input class="radio-hidden" type="radio" name="jenjang" id="j-d3">
    <input class="radio-hidden" type="radio" name="jenjang" id="j-d4">
    <input class="radio-hidden" type="radio" name="jenjang" id="j-s1" checked>
    <input class="radio-hidden" type="radio" name="jenjang" id="j-s2">
    <input class="radio-hidden" type="radio" name="jenjang" id="j-s3">

    <div class="tab-jenjang">
        @foreach($tabUkt as $key => $tab)
            <label class="pill" for="j-{{ $key }}">{{ $tab[0] }}</label>
        @endforeach
    </div>

    @foreach($tabUkt as $key => $tab)
        @php $daftar = array_values(array_filter($prodi, fn ($p) => in_array($p['jenjang'], $tab[1], true))); @endphp
        <div class="panel panel-{{ $key }}">
            <div class="tabel-wrapper">
                <table>
                    <tr>
                        <th>Program Studi</th>
                        @foreach($kolomUkt as $kol)
                            <th>{{ $kol['label'] }}</th>
                        @endforeach
                    </tr>
                    @forelse($daftar as $p)
                        <tr>
                            <td>{{ $p['nama'] }}</td>
                            @foreach($kolomUkt as $kol)
                                <td>{{ $kol['rentang'] }}</td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($kolomUkt) + 1 }}" class="kosong">Belum tersedia</td>
                        </tr>
                    @endforelse
                </table>
            </div>
        </div>
    @endforeach

    <!-- <p style="max-width:1200px;font-size:14px;color:#6b7280;margin-top:12px;">
        Catatan: rentang biaya per kelompok berlaku untuk seluruh program studi di kampus ini. Tarif kelompok 3 ke atas dapat berbeda per program studi.
    </p> -->
</section>

<div class="copyright">
    © 2026 PTN Impian. Seluruh Hak Cipta Dilindungi Undang-Undang
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('search-akademik');
    const radioFakultas = document.getElementById('tampil-fakultas');
    const radioProdi = document.getElementById('tampil-prodi');
    const filterJenjangWrapper = document.getElementById('filter-jenjang-wrapper');
    const jenjangButtons = document.querySelectorAll('.btn-filter-jenjang');
    const fakultasRows = document.querySelectorAll('.row-fakultas');
    const prodiRows = document.querySelectorAll('.row-prodi');
    const emptyFakultas = document.getElementById('empty-fakultas-search');
    const emptyProdi = document.getElementById('empty-prodi-search');

    let activeJenjang = '';

    function updateJenjangVisibility() {
        if (!filterJenjangWrapper) return;
        if (radioProdi && radioProdi.checked) {
            filterJenjangWrapper.style.display = 'flex';
        } else {
            filterJenjangWrapper.style.display = 'none';
        }
    }

    function applyFilter() {
        const keyword = searchInput ? searchInput.value.toLowerCase().trim() : '';
        const isProdiActive = radioProdi && radioProdi.checked;

        if (!isProdiActive) {
            let visibleCount = 0;
            fakultasRows.forEach(function (row) {
                const titleEl = row.querySelector('.row-title');
                const text = titleEl ? titleEl.innerText.toLowerCase() : '';
                if (text.includes(keyword)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            if (emptyFakultas) {
                emptyFakultas.style.display = (visibleCount === 0 && fakultasRows.length > 0) ? 'block' : 'none';
            }
        } else {
            let visibleCount = 0;
            prodiRows.forEach(function (row) {
                const text = row.innerText.toLowerCase();
                const jenjang = (row.getAttribute('data-jenjang') || '').trim();
                const matchKeyword = text.includes(keyword);
                const matchJenjang = !activeJenjang || jenjang === activeJenjang;

                if (matchKeyword && matchJenjang) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            if (emptyProdi) {
                emptyProdi.style.display = (visibleCount === 0 && prodiRows.length > 0) ? 'block' : 'none';
            }
        }
    }

    if (searchInput) {
        searchInput.addEventListener('input', applyFilter);
    }

    const radios = document.querySelectorAll('input[name="tampilan"]');
    radios.forEach(function (radio) {
        radio.addEventListener('change', function () {
            updateJenjangVisibility();
            applyFilter();
        });
    });

    jenjangButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            jenjangButtons.forEach(function (b) {
                b.style.borderColor = '#e3e3e3';
                b.style.background = '#ffffff';
                b.style.color = '#555';
            });
            this.style.borderColor = '#ffbf5f';
            this.style.background = '#ffbf5f';
            this.style.color = '#1a1a1a';
            activeJenjang = this.getAttribute('data-jenjang') || '';
            applyFilter();
        });
    });

    updateJenjangVisibility();

    const tabLinks = document.querySelectorAll('.tabs a');
    const sections = document.querySelectorAll('section[id]');

    function updateActiveTab() {
        let current = '';
        sections.forEach(function (sec) {
            const top = sec.offsetTop - 140;
            if (window.scrollY >= top) {
                current = sec.getAttribute('id');
            }
        });
        tabLinks.forEach(function (link) {
            link.classList.remove('active');
            if (link.getAttribute('href') === '#' + current) {
                link.classList.add('active');
            }
        });
    }

    window.addEventListener('scroll', updateActiveTab);
    updateActiveTab();

    const miniMapEl = document.getElementById('mini-map-kampus');
    if (miniMapEl && typeof L !== 'undefined') {
        const defaultLat = {{ !empty($lokasiUtama['lat']) ? (float)$lokasiUtama['lat'] : -0.7893 }};
        const defaultLng = {{ !empty($lokasiUtama['lng']) ? (float)$lokasiUtama['lng'] : 113.9213 }};
        const hasCoords = {{ $hasCoords ? 'true' : 'false' }};
        const zoomLevel = hasCoords ? 15 : 5;

        const mapInstance = L.map('mini-map-kampus', {
            scrollWheelZoom: false
        }).setView([defaultLat, defaultLng], zoomLevel);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: 'OpenStreetMap'
        }).addTo(mapInstance);

        const lokasiData = {!! json_encode($semuaLokasi) !!};
        const markers = [];

        lokasiData.forEach(function (loc) {
            if (loc.lat && loc.lng) {
                const popupHtml = '<div style="font-family: sans-serif; min-width: 180px; padding: 4px;">' +
                    '<span style="background: #fcebd8; color: #ee7f3e; font-size: 11px; font-weight: bold; padding: 2px 8px; border-radius: 4px; text-transform: uppercase;">Kampus</span>' +
                    '<h4 style="margin: 8px 0 4px; font-size: 14px; color: #1a1a1a; line-height: 1.3;">' + loc.nama + '</h4>' +
                    '<p style="margin: 0; font-size: 12px; color: #666;">' + loc.kota + ', ' + loc.provinsi + '</p>' +
                    '</div>';

                const m = L.marker([parseFloat(loc.lat), parseFloat(loc.lng)]).addTo(mapInstance);
                m.bindPopup(popupHtml);
                markers.push(m);
            }
        });

        if (markers.length > 0) {
            markers[0].openPopup();
        }
    }
});
</script>