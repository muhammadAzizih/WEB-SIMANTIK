<link href="https://fonts.googleapis.com/css2?family=Alike&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/style.css') }}">

<div class="container">
    <a class="back" href="{{ route('kampus') }}#fakultas">
        ← Kembali ke Fakultas
    </a>
    <h1>Fakultas Ilmu Komputer dan Teknologi Informasi</h1>
    <p class="subjudul">Universitas Sumatera Utara</p>
    
    <h2>Program Studi</h2>
    <a class="row" href="{{ route('prodi') }}">
        <div class="row-title">S1 Teknologi Informasi</div>
        <div class="row-arrow">&gt;</div>
    </a>
    <a class="row" href="#">
        <div class="row-title">S1 Ilmu Komputer</div>
        <div class="row-arrow">&gt;</div>
    </a>
    <a class="row" href="#">
        <div class="row-title">S2 Kecerdasan Buatan</div>
        <div class="row-arrow">&gt;</div>
    </a>
    <a class="row" href="#">
        <div class="row-title">S3 Teknik Informatika</div>
        <div class="row-arrow">&gt;</div>
    </a>
</div>