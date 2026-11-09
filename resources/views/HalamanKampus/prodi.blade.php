<link href="https://fonts.googleapis.com/css2?family=Alike&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/style.css') }}">

<div class="container">
    <a class="back" href="{{ route('fakultas', ['id' => $kode, 'nama' => $p['fakultas']]) }}">
        ← Kembali ke Daftar Prodi
    </a>
    <h1>{{ $p['jenjang'] }} {{ $p['nama'] }}</h1>
    <p class="subjudul">{{ $p['fakultas'] }} - {{ $k['nama'] }}</p>

    <div class="meta" style="margin:0 0 18px">
        <div><small>Jenjang</small>{{ $p['jenjang'] }}</div>
        <div><small>Fakultas</small>{{ $p['fakultas'] }}</div>
        <div><small>Kode Prodi</small>{{ $p['kode'] }}</div>
        <div><small>Akreditasi</small>{{ $p['akreditasi'] ?? 'Belum tersedia' }}</div>
    </div>

    <div class="box">
        <h4>Tentang Program Studi</h4>
        <p>
            {{ $p['jenjang'] }} {{ $p['nama'] }} adalah program studi jenjang {{ $p['jenjang'] }}
            di {{ $p['fakultas'] }}, {{ $k['nama'] }} ({{ $k['kota'] }}, {{ $k['provinsi'] }}).
            @if($p['akreditasi']) Program studi ini terakreditasi {{ $p['akreditasi'] }}. @endif
        </p>
    </div>

    <!-- @if($k['rentang'])
        <div class="box">
            <h4>Biaya Kuliah</h4>
            <p>
                Rentang UKT {{ $k['singkatan'] }}: <b>{{ $k['rentang'] }}</b>.
                <a href="{{ route('kampus', ['id' => $kode]) }}#biaya">Lihat rincian per kelompok</a>
            </p>
        </div>
    @endif -->
</div>