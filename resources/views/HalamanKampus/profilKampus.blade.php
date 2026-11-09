@include('navbar')
@include('chatbot')

<link href="https://fonts.googleapis.com/css2?family=Alike&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/style.css') }}">

<header class="univ-header">
    <div class="univ-logo">
        <img src="{{ $k['logo'] ?? 'https://placehold.co/220x220/fcebd8/ee7f32?text=Kampus' }}"
             alt="Logo {{ $k['nama'] }}"
             referrerpolicy="no-referrer"
             style="object-fit: contain; padding: 8px; background: #fff;"
             onerror="this.onerror=null;this.src='https://placehold.co/220x220/fcebd8/ee7f32?text=Kampus'">
    </div>

    <div class="univ-info">
        <h1>{{ $k['nama'] }}</h1>

        <div class="univ-location">
            <svg viewBox="0 0 14 20" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M7 0C3.1 0 0.5 2.8 0.5 6.2c0 3.3 3.3 7 6.5 8.6c3.2-1.6 6.5-5.3 6.5-8.6C13.5 2.8 10.9 0 7 0z" fill="#000"/>
                <circle cx="7" cy="6" r="2.4" fill="#fff"/>
                <ellipse cx="7" cy="16.6" rx="4.2" ry="2.3" fill="none" stroke="#000" stroke-width="1.4"/>
            </svg>
            <span>{{ $k['kota'] }}, {{ $k['provinsi'] }}</span>
        </div>

        <div class="univ-chips">
            <span class="univ-chip">{{ $k['label_kat'] }}</span>
            @if($k['akreditasi'])
                <span class="univ-chip">Akreditasi {{ $k['akreditasi'] }}</span>
            @endif
        </div>
    </div>

    <div class="univ-actions">
        @if($k['website'])
            <a href="{{ $k['website'] }}" target="_blank" rel="noopener" class="btn-outline btn-website">Website Resmi</a>
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
    <p class="content">{{ $k['deskripsi'] ?? ($k['nama'].' berlokasi di '.$k['kota'].', '.$k['provinsi'].'.') }}</p>

    <div class="meta">
        <div><small>Jenis Institusi</small>{{ $k['jenis'] }}</div>
        <div><small>Tahun Berdiri</small>{{ $k['tahun'] ?? '-' }}</div>
        <div><small>Akreditasi</small>{{ $k['akreditasi'] ?? '-' }}</div>
        <div><small>Fakultas</small>{{ count($fakultas) }}</div>
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

    <div class="listFakultas">
        @forelse($fakultas as $f)
            <a class="row" href="{{ route('fakultas', ['id' => $kode, 'nama' => $f['nama']]) }}">
                <div class="row-title">{{ $f['nama'] }}</div>
                <div class="row-arrow">&gt;</div>
            </a>
        @empty
            <p class="kosong">Data fakultas belum tersedia.</p>
        @endforelse
    </div>

    <div class="listProdi">
        @forelse($prodi as $p)
            <a class="row" href="{{ route('prodi', ['id' => $kode, 'kode' => $p['kode']]) }}">
                <div class="row-title">{{ $p['jenjang'] }} {{ $p['nama'] }}
                    <small>{{ $p['fakultas'] }}</small>
                </div>
                <div class="row-arrow">&gt;</div>
            </a>
        @empty
            <p class="kosong">Data program studi belum tersedia.</p>
        @endforelse
    </div>
</section>

<section id="lokasi">
    <h2>Lokasi Kampus</h2>
    @php
        // lokasi utama + lokasi tambahan (kalau ada di TTL)
        $semuaLokasi = array_merge([[
            'nama'     => count($lokasi) ? $k['singkatan'] . ' Kampus 1' : $k['nama'],
            'kota'     => $k['kota'],
            'provinsi' => $k['provinsi'],
            'alamat'   => $k['alamat'],
            'lat'      => $k['lat'],
            'lng'      => $k['lng'],
        ]], $lokasi);
    @endphp
    <div class="lokasi-list" style="grid-template-columns: repeat(auto-fit, minmax(250px, 340px)); justify-content: center;">
        @foreach($semuaLokasi as $l)
            <div class="lokasi-card">
                <h3>📍 {{ $l['nama'] }}</h3>
                <p><b>{{ $l['kota'] }}, {{ $l['provinsi'] }}</b>@if($l['alamat'])<br>{{ $l['alamat'] }}@endif</p>
                @if($l['lat'] !== null && $l['lng'] !== null)
                    <a href="https://www.google.com/maps/search/?api=1&query={{ $l['lat'] }},{{ $l['lng'] }}" target="_blank" rel="noopener">Lihat Peta</a>
                @else
                    <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($l['nama'].' '.$l['kota']) }}" target="_blank" rel="noopener">Lihat Peta</a>
                @endif
            </div>
        @endforeach
    </div>
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