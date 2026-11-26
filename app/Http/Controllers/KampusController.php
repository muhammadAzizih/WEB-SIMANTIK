<?php

namespace App\Http\Controllers;

use App\Services\FusekiService as F;
use App\Services\DbpediaService;
use App\Services\SemanticSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class KampusController extends Controller
{
    public function __construct(
        private F $fuseki,
        private DbpediaService $dbpedia,
        private SemanticSearchService $searchService
    ) {}

    // Ini fungsi utama untuk filter pencarian kampus berdasarkan jenis, provinsi, kota, prodi, dan kueri semantik
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'kategori' => 'nullable|string|max:50',
            'jenis'    => 'nullable|string|max:50',
            'provinsi' => 'nullable|string|max:100',
            'kota'     => 'nullable|string|max:100',
            'jenjang'  => 'nullable|string|max:30',
            'prodi'    => 'nullable|string|max:150',
            'q'        => 'nullable|string|max:100',
            'page'     => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:48',
        ]);

        $filters = [];

        if (!empty($data['kategori'])) $filters[] = '?k ck:kategori ' . F::lit($data['kategori']) . ' .';
        if (!empty($data['jenis']))    $filters[] = '?k ck:jenisInstitusi ' . F::lit($data['jenis']) . ' .';
        if (!empty($data['provinsi'])) $filters[] = '?k ck:provinsi ' . F::lit($data['provinsi']) . ' .';
        if (!empty($data['kota']))     $filters[] = '?k ck:kota ' . F::lit($data['kota']) . ' .';

        if (!empty($data['q'])) {
            $sparqlFilter = $this->searchService->buildSparqlFilter($data['q']);
            if ($sparqlFilter !== '') {
                $filters[] = $sparqlFilter;
            }
        }

        // Ini kueri pola graf untuk filter kampus yang menyelenggarakan prodi aktif (relasi ck:diselenggarakanOleh)
        if (!empty($data['jenjang']) || !empty($data['prodi'])) {
            $cond = '?pf ck:diselenggarakanOleh ?k ; ck:statusProdi "Aktif" .';
            if (!empty($data['jenjang'])) $cond .= ' ?pf ck:jenjang ' . F::lit($data['jenjang']) . ' .';
            if (!empty($data['prodi']))   $cond .= ' ?pf ck:namaProdi ' . F::lit($data['prodi']) . ' .';
            $filters[] = "FILTER EXISTS { $cond }";
        }

        $where = implode("\n  ", $filters);

        // Ini query SPARQL utama ke Fuseki untuk ambil data kartu kampus lengkap dengan hitungan fakultas dan prodi
        $query = <<<SPARQL
PREFIX ck: <https://carikampus.id/ontology/>
SELECT ?kode ?nama ?singkatan ?kategori ?jenis ?provinsi ?kota 
       ?akreditasi ?website ?deskripsi ?gambar ?rentangUKT ?biayaMin ?biayaMax
       (COUNT(DISTINCT ?fakultas) AS ?jmlFakultas)
       (COUNT(DISTINCT ?prodi) AS ?jmlProdi)
WHERE {
  $where
  ?k a ck:Institusi ;
     ck:kodePT ?kode ;
     ck:namaInstitusi ?nama ;
     ck:singkatan ?singkatan ;
     ck:kategori ?kategori ;
     ck:jenisInstitusi ?jenis ;
     ck:provinsi ?provinsi ;
     ck:kota ?kota .

  OPTIONAL { ?k ck:statusAkreditasi ?akreditasi }
  OPTIONAL { ?k ck:websiteResmi ?website }
  OPTIONAL { ?k ck:deskripsi ?deskripsi }
  OPTIONAL { ?k ck:gambarKampus ?gambar }
  OPTIONAL { ?k ck:rentangUKT ?rentangUKT }
  OPTIONAL { ?k ck:biayaKuliahMin ?biayaMin }
  OPTIONAL { ?k ck:biayaKuliahMax ?biayaMax }
  OPTIONAL {
    ?prodi ck:diselenggarakanOleh ?k ;
           ck:statusProdi "Aktif" ;
           ck:fakultas ?fakultas .
  }
}
GROUP BY ?kode ?nama ?singkatan ?kategori ?jenis ?provinsi ?kota ?akreditasi ?website ?deskripsi ?gambar ?rentangUKT ?biayaMin ?biayaMax
ORDER BY ?nama
SPARQL;

        $rows = $this->cached('kampus:v4:' . md5($query), 600, fn () => array_map(
            fn ($r) => collect($r)->mapWithKeys(fn ($v, $key) => [preg_replace('/X$/', '', $key) => $v])->all(),
            $this->fuseki->select($query)
        ));

        // Satu kode PT = satu card. Kalau Fuseki punya 2 node (?k) berbeda untuk kampus yang sama,
        // gabungkan: ambil baris pertama, jumlah fakultas/prodi diambil yang terbesar.
        $rows = collect($rows)->groupBy('kode')->map(function ($g) {
            $r = $g->first();
            $r['jmlFakultas'] = (string) $g->max(fn ($x) => (int) $x['jmlFakultas']);
            $r['jmlProdi']    = (string) $g->max(fn ($x) => (int) $x['jmlProdi']);
            return $r;
        })->values()->all();

        $matchedCabangMap = [];
        if (!empty($data['q'])) {
            $matches = $this->searchService->resolveCampusMatches($data['q']);
            foreach ($matches as $info) {
                if (!empty($info['matched_cabang']) && !empty($info['campus']['kode'])) {
                    $matchedCabangMap[$info['campus']['kode']] = $info['matched_cabang'];
                }
            }
        }

        $perPage = (int) ($data['per_page'] ?? 12);
        $page    = (int) ($data['page'] ?? 1);
        $total   = count($rows);

        $items = collect($rows)
            ->slice(($page - 1) * $perPage, $perPage)
            ->map(fn ($r) => $this->toCard($r, $matchedCabangMap[$r['kode']] ?? null))
            ->values();

        return response()->json([
            'data' => $items,
            'meta' => [
                'total'     => $total,
                'page'      => $page,
                'per_page'  => $perPage,
                'last_page' => max(1, (int) ceil($total / $perPage)),
            ],
        ]);
    }

    // Ini fungsi autocomplete pencarian cepat (bisa cari nama kampus, nama prodi, maupun kampus cabang PSDKU)
    public function suggestions(Request $request): JsonResponse
    {
        $q = (string) $request->query('q', '');
        $results = $this->searchService->getSuggestions($q, 7);

        return response()->json($results);
    }

    // Ini query SPARQL ke Fuseki untuk isi pilihan dropdown filter (jenis institusi, provinsi, kota, jenjang)
    public function options(Request $request): JsonResponse
    {
        $provinsi = $request->query('provinsi');

        $data = $this->cached('opt:v2:' . md5((string) $provinsi), 3600, function () use ($provinsi) {
            $jenis = $this->fuseki->select('SELECT DISTINCT ?v WHERE { ?k a ck:Institusi ; ck:jenisInstitusi ?v } ORDER BY ?v');
            $prov  = $this->fuseki->select('SELECT DISTINCT ?v WHERE { ?k a ck:Institusi ; ck:provinsi ?v } ORDER BY ?v');

            $kotaFilter = $provinsi ? 'ck:provinsi ' . F::lit($provinsi) . ' ;' : '';
            $kota = $this->fuseki->select("SELECT DISTINCT ?v WHERE { ?k a ck:Institusi ; $kotaFilter ck:kota ?v } ORDER BY ?v");

            $jenjang = $this->fuseki->select('SELECT DISTINCT ?v WHERE { ?p a ck:ProgramStudi ; ck:jenjang ?v } ORDER BY ?v');

            return [
                'jenis'    => array_column($jenis, 'v'),
                'provinsi' => array_map(fn ($r) => ['value' => $r['v'], 'label' => Str::of($r['v'])->replaceFirst('Prov. ', '')->toString()], $prov),
                'kota'     => array_map(fn ($r) => ['value' => $r['v'], 'label' => $r['v']], $kota),
                'jenjang'  => array_column($jenjang, 'v'),
            ];
        });

        return response()->json($data);
    }

    // Ini query SPARQL ke Fuseki untuk cari nama program studi aktif buat pilihan filter prodi
    public function prodi(Request $request): JsonResponse
    {
        $q       = Str::lower(trim((string) $request->query('q', '')));
        $jenjang = $request->query('jenjang');

        $conds = ['?p a ck:ProgramStudi ; ck:namaProdi ?v .'];
        if ($jenjang)  $conds[] = '?p ck:jenjang ' . F::lit($jenjang) . ' .';
        if ($q !== '') $conds[] = 'FILTER(CONTAINS(LCASE(?v), ' . F::lit($q) . '))';

        $rows = $this->fuseki->select(
            'SELECT DISTINCT ?v WHERE { ' . implode(' ', $conds) . ' } ORDER BY ?v LIMIT 30'
        );

        return response()->json(array_column($rows, 'v'));
    }

    // Ini query SPARQL ke Fuseki untuk ambil koordinat lokasi (ck:latitude & ck:longitude) seluruh kampus ke peta Leaflet.js
    public function peta(): JsonResponse
    {
        $rows = $this->cached('kampus:v2:peta', 3600, fn () => $this->fuseki->select(<<<'SPARQL'
SELECT ?kode ?nama ?singkatan ?kota ?provinsi ?lat ?lng WHERE {
  ?k a ck:Institusi ;
     ck:kodePT ?kode ;
     ck:namaInstitusi ?nama ;
     ck:singkatan ?singkatan ;
     ck:kota ?kota ;
     ck:provinsi ?provinsi ;
     ck:latitude ?lat ;
     ck:longitude ?lng .
} ORDER BY ?nama
SPARQL));

        return response()->json(array_map(fn ($r) => [
            'id'        => $r['kode'],
            'nama'      => $r['nama'],
            'singkatan' => $r['singkatan'],
            'kota'      => $r['kota'],
            'provinsi'  => Str::of($r['provinsi'])->replaceFirst('Prov. ', '')->toString(),
            'lat'       => (float) $r['lat'],
            'lng'       => (float) $r['lng'],
        ], $rows));
    }

    // Ini fungsi untuk menampilkan halaman profil detail kampus
    public function showKampus(Request $request)
    {
        $kode  = $this->kodeDariRequest($request);
        $k     = $this->institusi($kode);
        $prodi = $this->prodiKampus($kode);

        $fakultas = collect($prodi)
            ->groupBy('fakultas')
            ->map(fn ($g, $nama) => ['nama' => $nama, 'jumlah' => $g->count()])
            ->sortKeys()
            ->values()
            ->all();

        return view('HalamanKampus.profilKampus', [
            'k'        => $k,
            'kode'     => $kode,
            'fakultas' => $fakultas,
            'prodi'    => $prodi,
            'ukt'      => $this->uktKampus($kode),
            'lokasi'   => $this->lokasiTambahan($kode, $k),
            // Ini panggil data eksternal dari DBpedia untuk ambil nama rektor dan motto kampus
            'dbpedia'  => $this->dbpedia->getInfo($k['nama'] ?? ''),
        ]);
    }

    public function showFakultas(Request $request)
    {
        $kode = $this->kodeDariRequest($request);
        $k    = $this->institusi($kode);
        $nama = (string) $request->query('nama', '');

        $prodi = array_values(array_filter(
            $this->prodiKampus($kode),
            fn ($p) => $p['fakultas'] === $nama
        ));
        abort_if(empty($prodi), 404, 'Fakultas tidak ditemukan');

        return view('HalamanKampus.fakultas', compact('k', 'kode', 'nama', 'prodi'));
    }

    public function showProdi(Request $request)
    {
        $kode      = $this->kodeDariRequest($request);
        $k         = $this->institusi($kode);
        $kodeProdi = (string) $request->query('kode', '');

        $p = collect($this->prodiKampus($kode))->firstWhere('kode', $kodeProdi);
        abort_if(!$p, 404, 'Program studi tidak ditemukan');

        return view('HalamanKampus.prodi', compact('k', 'kode', 'p'));
    }

    // Ini query SPARQL ke Fuseki untuk ambil data kampus cabang / PSDKU (relasi ck:lokasiDari)
    private function lokasiTambahan(string $kode, array $k): array
    {
        $query = 'SELECT ?nama ?alamat ?kota ?provinsi ?lat ?lng ?gambar WHERE {'
            . ' ?k ck:kodePT ' . F::lit($kode) . ' .'
            . ' ?l a ck:LokasiKampus ; ck:lokasiDari ?k .'
            . ' OPTIONAL { ?l ck:namaLokasi ?nama } OPTIONAL { ?l ck:alamatLengkap ?alamat }'
            . ' OPTIONAL { ?l ck:kota ?kota } OPTIONAL { ?l ck:provinsi ?provinsi }'
            . ' OPTIONAL { ?l ck:latitude ?lat } OPTIONAL { ?l ck:longitude ?lng }'
            . ' OPTIONAL { ?l ck:gambarLokasi ?gambar }'
            . ' } ORDER BY ?nama';

        $rows = $this->cached('lokasi:v2:' . $kode, 600, fn () => $this->fuseki->select($query));

        return array_map(fn ($r, $i) => [
            'nama'     => $r['nama'] ?? ($k['singkatan'] . ' Kampus ' . ($i + 2)),
            'kota'     => $r['kota'] ?? $k['kota'],
            'provinsi' => isset($r['provinsi'])
                ? Str::of($r['provinsi'])->replaceFirst('Prov. ', '')->toString()
                : $k['provinsi'],
            'alamat'   => $r['alamat'] ?? null,
            'lat'      => isset($r['lat']) ? (float) $r['lat'] : null,
            'lng'      => isset($r['lng']) ? (float) $r['lng'] : null,
            'gambar'   => $r['gambar'] ?? null,
        ], $rows, array_keys($rows));
    }

    /** Ambil & validasi ?id= (kode PT). */
    private function kodeDariRequest(Request $request): string
    {
        $id = $request->query('id');
        abort_if(!is_string($id) || !preg_match('/^[A-Za-z0-9_-]{1,20}$/', $id), 404, 'Kampus tidak ditemukan');

        return $id;
    }

    // Ini query SPARQL ke Fuseki untuk ambil profil lengkap 1 kampus berdasarkan kode PT
    private function institusi(string $kode): array
    {
        $query = 'SELECT ?nama ?singkatan ?kategori ?jenis ?provinsi ?kota ?kecamatan ?alamat ?kodePos ?lat ?lng'
            . ' ?akreditasi ?lembaga ?masa ?berdiri ?website ?logo ?deskripsi ?rentang ?email ?telp WHERE {'
            . ' ?k a ck:Institusi ; ck:kodePT ' . F::lit($kode) . ' ; ck:namaInstitusi ?nama ; ck:singkatan ?singkatan ;'
            . ' ck:kategori ?kategori ; ck:jenisInstitusi ?jenis ; ck:provinsi ?provinsi ; ck:kota ?kota .'
            . ' OPTIONAL { ?k ck:kecamatan ?kecamatan } OPTIONAL { ?k ck:alamatLengkap ?alamat }'
            . ' OPTIONAL { ?k ck:kodePos ?kodePos } OPTIONAL { ?k ck:latitude ?lat } OPTIONAL { ?k ck:longitude ?lng }'
            . ' OPTIONAL { ?k ck:statusAkreditasi ?akreditasi } OPTIONAL { ?k ck:lembagaAkreditasi ?lembaga }'
            . ' OPTIONAL { ?k ck:masaBerlakuAkreditasi ?masa } OPTIONAL { ?k ck:tanggalBerdiri ?berdiri }'
            . ' OPTIONAL { ?k ck:websiteResmi ?website } OPTIONAL { ?k ck:logo ?logo }'
            . ' OPTIONAL { ?k ck:deskripsi ?deskripsi } OPTIONAL { ?k ck:rentangUKT ?rentang }'
            . ' OPTIONAL { ?k ck:email ?email } OPTIONAL { ?k ck:noTelepon ?telp }'
            . ' } LIMIT 1';

        $rows = $this->cached('detail:v2:' . $kode, 600, fn () => $this->fuseki->select($query));
        abort_if(empty($rows), 404, 'Kampus tidak ditemukan');
        $r = $rows[0];

        
            $provinsi = Str::of($r['provinsi'])->replaceFirst('Prov. ', '')->toString();
        $fallbackLogo = 'https://placehold.co/220x220/fcebd8/ee7f32?text=' . urlencode($r['singkatan'] ?? 'Logo');

        return [
            'nama'       => $r['nama'],
            'singkatan'  => $r['singkatan'] ?? 'PTN',
            'kategori'   => $r['kategori'] ?? 'PTN_UMUM',
            'label_kat'  => ($r['kategori'] ?? '') === 'PTKN' ? 'PTKN' : 'PTN',
            'jenis'      => $r['jenis'] ?? 'Universitas',
            'kota'       => $r['kota'] ?? '-',
            'provinsi'   => $provinsi ?: '-',
            'kecamatan'  => $r['kecamatan'] ?? '-',
            'alamat'     => $r['alamat'] ?? 'Alamat belum tercatat',
            'kode_pos'   => $r['kodePos'] ?? '-',
            'lat'        => isset($r['lat']) ? (float) $r['lat'] : null,
            'lng'        => isset($r['lng']) ? (float) $r['lng'] : null,
            'akreditasi' => $r['akreditasi'] ?? 'Belum Terakreditasi',
            'lembaga'    => $r['lembaga'] ?? 'BAN-PT',
            'masa'       => $r['masa'] ?? 'Masih Berlaku',
            'tahun'      => isset($r['berdiri']) ? substr($r['berdiri'], 0, 4) : '-',
            'website'    => $r['website'] ?? null,
            'logo'       => !empty($r['logo']) ? $this->httpsUrl($r['logo']) : $fallbackLogo,
            'deskripsi'  => !empty($r['deskripsi']) 
                ? $r['deskripsi'] 
                : sprintf('%s adalah perguruan tinggi negeri yang berlokasi di %s, %s.', $r['nama'], $r['kota'], $provinsi),
            'rentang'    => $r['rentang'] ?? 'Menyesuaikan kebijakan PTN',
            'email'      => $r['email'] ?? '-',
            'telp'       => $r['telp'] ?? '-',
        ];
  
    }

    // Ini query SPARQL ke Fuseki untuk ambil seluruh daftar program studi aktif di kampus terkait
    private function prodiKampus(string $kode): array
    {
        $query = 'SELECT ?kode ?nama ?jenjang ?fakultas ?akreditasi WHERE {'
            . ' ?k ck:kodePT ' . F::lit($kode) . ' .'
            . ' ?p ck:diselenggarakanOleh ?k ; ck:statusProdi "Aktif" ; ck:kodeProdi ?kode ;'
            . ' ck:namaProdi ?nama ; ck:jenjang ?jenjang ; ck:fakultas ?fakultas .'
            . ' OPTIONAL { ?p ck:akreditasiProdi ?akreditasi } }';

        $rows = $this->cached('prodi-kampus:v1:' . $kode, 600, fn () => $this->fuseki->select($query));

        $urut = ['S1', 'D4', 'D3', 'D2', 'D1', 'S2', 'S2 Terapan', 'S3', 'S3 Terapan', 'Sp-1', 'Sp-2', 'Profesi'];
        $rank = fn ($j) => (($i = array_search($j, $urut, true)) === false) ? 99 : $i;

        usort($rows, fn ($a, $b) =>
            [$a['fakultas'], $rank($a['jenjang']), $a['nama']] <=> [$b['fakultas'], $rank($b['jenjang']), $b['nama']]
        );

        return array_map(fn ($r) => [
            'kode'       => $r['kode'],
            'nama'       => $r['nama'],
            'jenjang'    => $r['jenjang'],
            'fakultas'   => $r['fakultas'],
            'akreditasi' => $r['akreditasi'] ?? null,
        ], $rows);
    }

    // Ini query SPARQL ke Fuseki untuk ambil rincian matriks biaya UKT (kelompok 1 sampai 5+)
    private function uktKampus(string $kode): array
    {
        $query = 'SELECT ?kelompok ?rentang ?dasar WHERE {'
            . ' ?k ck:kodePT ' . F::lit($kode) . ' .'
            . ' ?u ck:berlakuDi ?k ; ck:kelompokUKT ?kelompok ; ck:rentangUKT ?rentang .'
            . ' OPTIONAL { ?u ck:keteranganDasarHukum ?dasar } } ORDER BY ?kelompok';

        $rows = $this->cached('ukt:v1:' . $kode, 600, fn () => $this->fuseki->select($query));

        return array_map(fn ($r) => [
            'kelompok' => $r['kelompok'],
            'rentang'  => $r['rentang'],
            'dasar'    => $r['dasar'] ?? null,
        ], $rows);
    }

    /** Cache hasil query; dimatikan saat APP_DEBUG=true supaya data baru langsung terlihat. */
    private function cached(string $key, int $ttl, \Closure $fn): mixed
    {
        if (config('app.debug')) {
            return $fn();
        }

        return Cache::remember($key, $ttl, $fn);
    }
  //fallback untuk data kampus yang kosong
    private function toCard(array $r, ?string $cabang = null): array
    {
        $provinsi = Str::of($r['provinsi'])->replaceFirst('Prov. ', '')->toString();

        //  Fallback Deskripsi
        $deskripsi = !empty($r['deskripsi']) 
            ? $r['deskripsi'] 
            : sprintf('%s adalah perguruan tinggi negeri terkemuka yang berlokasi di %s, %s.', $r['nama'], $r['kota'], $provinsi);

        //  Fallback UKT
        $ukt = isset($r['biayaMin'], $r['biayaMax'])
            ? 'Rp. ' . $this->rupiahSingkat((float) $r['biayaMin']) . ' - ' . $this->rupiahSingkat((float) $r['biayaMax'])
            : ($r['rentangUKT'] ?? 'Menyesuaikan UKT');

        //. Fallback Gambar zi
        $fallbackGambar = 'https://placehold.co/600x300/fcebd8/ee7f32?text=' . urlencode($r['singkatan'] ?? 'Kampus');
        $gambar = !empty($r['gambar']) ? $this->httpsUrl($r['gambar']) : $fallbackGambar;

        return [
            'id'                => $r['kode'],
            'nama'              => $r['nama'],
            'singkatan'         => $r['singkatan'] ?? 'PTN',
            'kategori'          => $r['kategori'] ?? 'PTN_UMUM',
            'jenis'             => $r['jenis'] ?? 'Universitas',
            'kota'              => $r['kota'] ?? '-',
            'provinsi'          => $provinsi ?: '-',
            'cabang'            => $cabang,
            'akreditasi'        => $r['akreditasi'] ?? 'Belum Terakreditasi',
            'website'           => $r['website'] ?? null,
            'fakultas'          => (int) ($r['jmlFakultas'] ?? 0),
            'prodi'             => (int) ($r['jmlProdi'] ?? 0),
            'deskripsi'         => Str::limit($deskripsi, 110),
            'deskripsi_lengkap' => $deskripsi,
            'ukt'               => $ukt,
            'ukt_lengkap'       => $r['rentangUKT'] ?? $ukt,
            'gambar'            => $gambar,
            'url'               => url('/kampus?id=' . $r['kode']),
        ];
    }

    /** http:// -> https:// supaya gambar tidak diblokir browser (mixed content). */
    private function httpsUrl(?string $url): ?string
    {
        return $url ? preg_replace('#^http://#i', 'https://', $url) : null;
    }

    /** 500000 -> "500rb", 10000000 -> "10Jt", 7500000 -> "7,5Jt" */
    private function rupiahSingkat(float $n): string
    {
        if ($n >= 1_000_000) {
            return rtrim(rtrim(number_format($n / 1_000_000, 1, ',', ''), '0'), ',') . 'Jt';
        }
        if ($n >= 1_000) {
            return round($n / 1_000) . 'rb';
        }
        return (string) (int) $n;
    }
}

