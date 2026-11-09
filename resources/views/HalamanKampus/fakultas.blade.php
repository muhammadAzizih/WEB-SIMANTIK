<link href="https://fonts.googleapis.com/css2?family=Alike&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/style.css') }}">

<div class="container">
    <a class="back" href="{{ route('kampus', ['id' => $kode]) }}#fakultas">
        ← Kembali ke Fakultas
    </a>
    <h1>{{ $nama }}</h1>
    <p class="subjudul">{{ $k['nama'] }}</p>

    <h2>Program Studi</h2>
    @foreach($prodi as $p)
        <a class="row" href="{{ route('prodi', ['id' => $kode, 'kode' => $p['kode']]) }}">
            <div class="row-title">{{ $p['jenjang'] }} {{ $p['nama'] }}</div>
            <div class="row-arrow">&gt;</div>
        </a>
    @endforeach
</div>