<?php

namespace App\Services;

use App\Services\FusekiService as F;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class SemanticSearchService
{
    private static ?array $catalog = null;
    private static ?array $provinceMap = null;
    private static ?array $cityMap = null;
    private static ?array $prodiMap = null;
    private static ?array $jenisMap = null;

    public function __construct(private F $fuseki)
    {
        $this->ensureCatalogLoaded();
        $this->ensureVocabularyLoaded();
    }

    // Ini fungsi untuk ambil kamus semantik (singkatan daerah, jenis kampus, prodi) dari Fuseki via W3C SKOS
    public function ensureVocabularyLoaded(): void
    {
        if (self::$provinceMap !== null && self::$cityMap !== null && self::$prodiMap !== null && self::$jenisMap !== null) {
            return;
        }

        try {
            $cachedVocab = Cache::remember('semantic_vocab_v5', 86400, function () {
                $sparql = '
                    PREFIX ck: <https://carikampus.id/ontology/>
                    PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
                    PREFIX skos: <http://www.w3.org/2004/02/skos/core#>

                    SELECT ?entitas ?tipe ?resmi ?alt WHERE {
                     VALUES ?tipe { ck:Provinsi ck:Kota ck:BidangIlmu ck:JenisInstitusi ck:ProgramStudi }
                        ?entitas a ?tipe ;
                                 rdfs:label ?resmi .
                        OPTIONAL { ?entitas skos:altLabel ?alt }
                        OPTIONAL { ?entitas ck:namaProdi ?alt }
                    }
                ';
                return $this->fuseki->select($sparql);
            });

            // Muat juga seluruh 2.700+ nama program studi unik resmi yang diselenggarakan seluruh kampus di Fuseki
            $cachedProdiNames = Cache::remember('semantic_all_prodi_names_v2', 86400, function () {
                $sparql = '
                    PREFIX ck: <https://carikampus.id/ontology/>
                    SELECT DISTINCT ?namaProdi WHERE {
                        ?p a ck:ProgramStudi ;
                           ck:namaProdi ?namaProdi .
                    }
                ';
                return $this->fuseki->select($sparql);
            });

            $provMap = [];
            $cityMap = [];
            $prodiMap = [];
            $jenisMap = [];

            foreach ($cachedVocab as $r) {
                $tipe = $r['tipe'] ?? '';
                $resmi = $r['resmi'] ?? '';
                $alt = $r['alt'] ?? '';

                $labels = array_filter([mb_strtolower(trim($resmi)), mb_strtolower(trim($alt))]);
                $cleanResmi = mb_strtolower(trim(preg_replace('/^prov\.\s+/i', '', $resmi)));
                if ($cleanResmi !== '') {
                    $labels[] = $cleanResmi;
                    if (str_contains($cleanResmi, 'sumatera')) {
                        $labels[] = str_replace('sumatera', 'sumatra', $cleanResmi);
                    }
                }

                foreach ($labels as $lbl) {
                    if ($lbl === '') continue;
                    if (str_contains($tipe, 'Provinsi')) {
                        $provMap[$lbl] = $resmi;
                    } elseif (str_contains($tipe, 'Kota')) {
                        $cityMap[$lbl] = $resmi;
                    } elseif (str_contains($tipe, 'BidangIlmu')) {
                        $prodiMap[$lbl] = $resmi;
                    } elseif (str_contains($tipe, 'JenisInstitusi')) {
                        $jenisMap[$lbl] = $resmi;
                    }
                }
            }

            // Gabungkan seluruh nama prodi resmi dari Fuseki ke prodiMap
            foreach ($cachedProdiNames as $r) {
                $nama = trim($r['namaProdi'] ?? '');
                if ($nama !== '' && mb_strlen($nama) >= 3) {
                    $lower = mb_strtolower($nama);
                    if (!isset($prodiMap[$lower])) {
                        $prodiMap[$lower] = $nama;
                    }
                }
            }

            // Urutkan key dari yang terpanjang agar frasa majemuk didahulukan sebelum substring pendek
            uksort($provMap, fn($a, $b) => mb_strlen($b) <=> mb_strlen($a));
            uksort($cityMap, fn($a, $b) => mb_strlen($b) <=> mb_strlen($a));
            uksort($prodiMap, fn($a, $b) => mb_strlen($b) <=> mb_strlen($a));
            uksort($jenisMap, fn($a, $b) => mb_strlen($b) <=> mb_strlen($a));

            self::$provinceMap = $provMap;
            self::$cityMap = $cityMap;
            self::$prodiMap = $prodiMap;
            self::$jenisMap = $jenisMap;
        } catch (\Throwable $e) {
            report($e);
            self::$provinceMap = self::$provinceMap ?? [];
            self::$cityMap = self::$cityMap ?? [];
            self::$prodiMap = self::$prodiMap ?? [];
            self::$jenisMap = self::$jenisMap ?? [];
        }
    }

    public function getProvinceMap(): array
    {
        $this->ensureVocabularyLoaded();
        return self::$provinceMap ?? [];
    }

    public function getCityMap(): array
    {
        $this->ensureVocabularyLoaded();
        return self::$cityMap ?? [];
    }

    public function getProdiMap(): array
    {
        $this->ensureVocabularyLoaded();
        return self::$prodiMap ?? [];
    }

    public function getJenisMap(): array
    {
        $this->ensureVocabularyLoaded();
        return self::$jenisMap ?? [];
    }

    // Ini fungsi untuk ambil katalog 201 kampus dari Fuseki ke memori agar pencarian cepat
    private function ensureCatalogLoaded(): void
    {
        if (self::$catalog === null || empty(self::$catalog)) {
            try {
                $cached = Cache::get('semantic_search_catalog_v3');
                if (!empty($cached)) {
                    self::$catalog = $cached;
                } else {
                    $rows = Cache::get('semantic_search_catalog_v2');
                    if (empty($rows)) {
                        $rows = $this->fuseki->select('PREFIX ck: <https://carikampus.id/ontology/> 
                            SELECT ?k ?kode ?nama ?singkatan ?jenis ?kota ?provinsi ?akreditasi
                            WHERE {
                                ?k a ck:Institusi ;
                                   ck:kodePT ?kode ;
                                   ck:namaInstitusi ?nama .
                                OPTIONAL { ?k ck:singkatan ?singkatan }
                                OPTIONAL { ?k ck:jenisInstitusi ?jenis }
                                OPTIONAL { ?k ck:kota ?kota }
                                OPTIONAL { ?k ck:provinsi ?provinsi }
                                OPTIONAL { ?k ck:statusAkreditasi ?akreditasi }
                            }');
                    }

                    if (!empty($rows)) {
                        $branchFile = storage_path('framework/cache/campus_branches.json');
                        $branches = file_exists($branchFile) ? json_decode(file_get_contents($branchFile), true) : [];

                        $catalog = [];
                        foreach ($rows as $r) {
                            $k = $r['k'];
                            $kode = $r['kode'] ?? '';
                            $item = $r;
                            $item['cabang'] = $branches[$kode] ?? [];
                            $catalog[$k] = $item;
                        }

                        $catalogValues = array_values($catalog);
                        Cache::put('semantic_search_catalog_v3', $catalogValues, 86400);
                        self::$catalog = $catalogValues;
                    } else {
                        self::$catalog = [];
                    }
                }
            } catch (\Throwable $e) {
                report($e);
                self::$catalog = [];
            }
        }
    }

    // Ini fungsi untuk normalisasi teks dan ubah singkatan ('univ', 'poltek') jadi istilah resmi ontologi
    public function tokenize(string $input): array
    {
        $raw = Str::lower(trim($input));
        if ($raw === '') {
            return [];
        }

        // 1. Normalisasi bentuk singkat tipe institusi dari Fuseki (ck:JenisInstitusi)
        $this->ensureVocabularyLoaded();
        $norm = $raw;
        foreach (self::$jenisMap ?? [] as $alt => $resmi) {
            $norm = preg_replace('/\b' . preg_quote($alt, '/') . '\b/i', mb_strtolower($resmi), $norm);
        }

        // 1b. Lindungi frasa majemuk (provinsi & istilah) agar tidak terpecah menjadi kata parsial
        $this->ensureVocabularyLoaded();
        foreach (array_keys(self::$provinceMap ?? []) as $kp) {
            if (str_contains($kp, ' ')) {
                $norm = preg_replace('/\b' . preg_quote($kp, '/') . '\b/i', str_replace(' ', '_', $kp), $norm);
            }
        }
        $norm = preg_replace('/\b(baik\s*sekali)\b/i', 'baik_sekali', $norm);

        // 2. Bersihkan tanda baca (pertahankan underscore untuk frasa majemuk)
        $norm = trim(preg_replace('/[?!.,\-]+/u', ' ', $norm));

        // 3. Normalisasi typo kata kunci struktural penting via Levenshtein
        $parts = preg_split('/\s+/u', $norm, -1, PREG_SPLIT_NO_EMPTY);
        $structural = [
            'cabang'      => 1,
            'psdku'       => 1,
            'universitas' => 2,
            'politeknik'  => 2,
            'institut'    => 1,
        ];
        $normalizedParts = [];
        foreach ($parts as $p) {
            $matchedStruct = false;
            $pLen = mb_strlen($p);
            foreach ($structural as $target => $maxLev) {
                if (abs($pLen - mb_strlen($target)) <= $maxLev && levenshtein($p, $target) <= $maxLev) {
                    $normalizedParts[] = $target;
                    $matchedStruct = true;
                    break;
                }
            }
            if (!$matchedStruct) {
                $normalizedParts[] = $p;
            }
        }
        $parts = $normalizedParts;

        // 4. Eliminasi HANYA kata penghubung/tanya murni non-semantik (stopwords sejati)
        $stopwords = [
            'di', 'ke', 'dari', 'pada', 'untuk', 'yang', 'dan', 'atau', 'ada',
            'dong', 'ya', 'sih', 'ini', 'itu', 'tolong', 'coba', 'apakah'
        ];
        $clean = array_values(array_filter($parts, fn($w) => !in_array($w, $stopwords, true) && mb_strlen($w) > 1));

        return !empty($clean) ? $clean : $parts;
    }

    // Ini fungsi untuk cek apakah pencarian user berupa kata umum (misal: 'kampus', 'ptn', 'semua')
    public function isGenericQuery(string $input): bool
    {
        $raw = Str::lower(trim($input));
        if ($raw === '') return false;

        $generic = [
            'kampus', 'ptn', 'negeri', 'kuliah', 'cari', 'daftar', 'rekomendasi',
            'terbaik', 'semua', 'perguruan', 'tinggi', 'institusi', 'indonesia'
        ];
        $words = preg_split('/\s+/u', preg_replace('/[?!.,\-_]+/u', ' ', $raw), -1, PREG_SPLIT_NO_EMPTY);
        if (empty($words)) return false;

        foreach ($words as $w) {
            if (!in_array($w, $generic, true)) {
                return false;
            }
        }
        return true;
    }

    // Ini fungsi untuk deteksi maksud pencarian user (nama prodi, wilayah, akreditasi, atau kampus cabang)
    public function detectIntent(string $input): array
    {
        $raw = trim($input);
        $norm = Str::lower($raw);
        $this->ensureVocabularyLoaded();
        foreach (self::$jenisMap ?? [] as $alt => $resmi) {
            $norm = preg_replace('/\b' . preg_quote($alt, '/') . '\b/i', mb_strtolower($resmi), $norm);
        }

        $intents = [];
        $entities = [
            'jenjang'            => null,
            'akreditasi'         => null,
            'kategori'           => null,
            'jenis_institusi'    => null,
            'provinsi'           => null,
            'kota'               => null,
            'is_cabang'          => false,
            'cabang_nama'        => null,
            'prodi_keywords'     => [],
            'fakultas_keywords'  => [],
            'biaya'              => [
                'type'      => null,
                'max_value' => null,
            ],
            'institusi_uris'     => [],
            'campus_name_tokens' => [],
        ];

        // 1. Cek Generic Query
        if ($this->isGenericQuery($norm)) {
            return [
                'raw_query'  => $raw,
                'intents'    => ['GENERIC'],
                'entities'   => $entities,
                'is_generic' => true,
            ];
        }

        // 2. Deteksi Jenjang (S1, S2, S3, D4, D3, Profesi, Spesialis)
        if (preg_match('/\b(s1|sarjana)\b/i', $norm)) {
            $entities['jenjang'] = 'S1';
            $intents[] = 'JENJANG';
        } elseif (preg_match('/\b(d4|sarjana\s*terapan|diploma\s*4|diploma\s*iv)\b/i', $norm)) {
            $entities['jenjang'] = 'D4';
            $intents[] = 'JENJANG';
        } elseif (preg_match('/\b(d3|diploma\s*3|diploma\s*iii|diploma)\b/i', $norm)) {
            $entities['jenjang'] = 'D3';
            $intents[] = 'JENJANG';
        } elseif (preg_match('/\b(s2|magister)\b/i', $norm)) {
            $entities['jenjang'] = 'S2';
            $intents[] = 'JENJANG';
        } elseif (preg_match('/\b(s3|doktor)\b/i', $norm)) {
            $entities['jenjang'] = 'S3';
            $intents[] = 'JENJANG';
        } elseif (preg_match('/\bprofesi\b/i', $norm)) {
            $entities['jenjang'] = 'Profesi';
            $intents[] = 'JENJANG';
        } elseif (preg_match('/\bspesialis\b/i', $norm)) {
            $entities['jenjang'] = 'Spesialis';
            $intents[] = 'JENJANG';
        }

        // 3. Deteksi Akreditasi (Unggul, Baik Sekali, Baik, A, B, C)
        if (preg_match('/\b(unggul)\b/i', $norm) || preg_match('/\bakreditasi\s*(?:nya)?\s*(unggul|a)\b/i', $norm)) {
            $entities['akreditasi'] = 'Unggul';
            $intents[] = 'AKREDITASI';
        } elseif (preg_match('/\b(baik\s*sekali)\b/i', $norm) || preg_match('/\bakreditasi\s*(?:nya)?\s*(baik\s*sekali|b)\b/i', $norm)) {
            $entities['akreditasi'] = 'Baik Sekali';
            $intents[] = 'AKREDITASI';
        } elseif (preg_match('/\bakreditasi\s*(?:nya)?\s*(baik|c)\b/i', $norm)) {
            $entities['akreditasi'] = 'Baik';
            $intents[] = 'AKREDITASI';
        }

        // 4. Deteksi UKT & Biaya
        if (preg_match('/\b(murah|termurah|terjangkau|gratis)\b/i', $norm) || preg_match('/\b(ukt|biaya)\b/i', $norm)) {
            $intents[] = 'UKT_BIAYA';
            if (preg_match('/(?:di bawah|kurang dari|<|maksimal|max|diatas)?\s*(\d+(?:[.,]\d+)?)\s*(juta|jt)?/i', $norm, $m)) {
                $val = (float) str_replace(',', '.', $m[1]);
                if (!empty($m[2])) $val *= 1000000;
                $entities['biaya'] = ['type' => 'max', 'max_value' => (int) $val];
            } elseif (preg_match('/\b(murah|termurah|terjangkau)\b/i', $norm)) {
                $entities['biaya'] = ['type' => 'murah', 'max_value' => 1000000];
            } else {
                $entities['biaya'] = ['type' => 'info', 'max_value' => null];
            }
        }

        // 5. Deteksi Kategori & Jenis Institusi dari Fuseki (ck:JenisInstitusi)
        if (preg_match('/\b(ptkn|kemenag|islam|keagamaan)\b/i', $norm)) {
            $entities['kategori'] = 'PTKN';
            $intents[] = 'KATEGORI';
        } elseif (preg_match('/\b(ptn\s*umum|kemendikbud)\b/i', $norm)) {
            $entities['kategori'] = 'PTN_UMUM';
            $intents[] = 'KATEGORI';
        }

        foreach (self::$jenisMap ?? [] as $altJenis => $resmiJenis) {
            if (preg_match('/\b' . preg_quote($altJenis, '/') . '\b/i', $norm)) {
                $entities['jenis_institusi'] = $resmiJenis;
                $intents[] = 'JENIS_INSTITUSI';
                break;
            }
        }

        // 6. Deteksi Lokasi (Provinsi, Kota, Cabang) via Kosakata Semantik Fuseki
        foreach (self::$provinceMap ?? [] as $kp => $fullProv) {
            if (preg_match('/\b' . preg_quote($kp, '/') . '\b/i', $norm)) {
                $entities['provinsi'] = $fullProv;
                $intents[] = 'LOKASI';
                break;
            }
        }

        // Deteksi kota dari Fuseki & katalog yang terindeks
        foreach (self::$cityMap ?? [] as $altKota => $resmiKota) {
            if (preg_match('/\b' . preg_quote($altKota, '/') . '\b/i', $norm)) {
                $entities['kota'] = $resmiKota;
                $intents[] = 'LOKASI';
                break;
            }
        }

        if (empty($entities['kota'])) {
            $catalog = self::$catalog ?? [];
            foreach ($catalog as $c) {
                $cKota = Str::lower($c['kota'] ?? '');
                $cleanKota = trim(preg_replace('/^(kota|kabupaten|kab\.)\s+/i', '', $cKota));
                if ($cleanKota !== '' && mb_strlen($cleanKota) >= 4) {
                    if (preg_match('/\b' . preg_quote($cleanKota, '/') . '\b/i', $norm)) {
                        $entities['kota'] = $c['kota'];
                        $intents[] = 'LOKASI';
                        break;
                    }
                }
            }
        }

        // Deteksi cabang dan nama lokasi cabang spesifik
        if (preg_match('/\b(cabang|psdku)\b/i', $norm)) {
            $entities['is_cabang'] = true;
            $intents[] = 'CABANG';
        }

        $catalog = self::$catalog ?? [];
        foreach ($catalog as $c) {
            if (!empty($c['cabang'])) {
                foreach ($c['cabang'] as $cb) {
                    $cbNama = Str::lower($cb['nama'] ?? '');
                    if ($cbNama !== '' && mb_strlen($cbNama) >= 4 && preg_match('/\b' . preg_quote($cbNama, '/') . '\b/i', $norm)) {
                        $entities['is_cabang'] = true;
                        $entities['cabang_nama'] = $cb['nama'];
                        $intents[] = 'CABANG';
                        break 2;
                    }
                }
            }
        }

        // 7. Deteksi Institusi Tertentu (Nama spesifik kampus / singkatan resmi)
        foreach ($catalog as $c) {
            $singk = Str::lower($c['singkatan'] ?? '');
            $namaKampus = Str::lower($c['nama'] ?? '');
            $distinctive = trim(preg_replace('/^(universitas|institut|politeknik|akademi|sekolah tinggi|uin|iain|stain)\s+/i', '', $namaKampus));

            $matchedInstitusi = false;
            if ($singk !== '' && preg_match('/\b' . preg_quote($singk, '/') . '\b/i', $norm)) {
                $matchedInstitusi = true;
            } elseif ($distinctive !== '' && mb_strlen($distinctive) >= 4) {
                // Jangan anggap sebagai institusi jika kata yang cocok identik dengan nama provinsi/wilayah/indonesia
                // kecuali jika kata kunci tipe kampus (misal: "universitas sumatera utara") secara eksplisit diketik
                $isRegionName = isset(self::$provinceMap[$distinctive]) || $distinctive === 'indonesia';
                if ($isRegionName) {
                    if (preg_match('/\b(universitas|institut|politeknik|uin|iain)\s+' . preg_quote($distinctive, '/') . '\b/i', $norm)) {
                        $matchedInstitusi = true;
                    }
                } elseif (preg_match('/\b' . preg_quote($distinctive, '/') . '\b/i', $norm)) {
                    $matchedInstitusi = true;
                }
            }

            if ($matchedInstitusi) {
                $entities['institusi_uris'][] = $c['k'];
                $entities['campus_name_tokens'][] = $singk ?: $distinctive;
                $intents[] = 'INSTITUSI';
            }
        }

        // 8. Deteksi Program Studi & Fakultas dari Fuseki (ck:BidangIlmu & 2.700+ ck:ProgramStudi)
        $matchedProdiKeys = [];
        foreach (self::$prodiMap ?? [] as $slangOrName => $canonicalProdi) {
            if (preg_match('/\b' . preg_quote($slangOrName, '/') . '\b/i', $norm)) {
                $isSubOfLonger = false;
                foreach ($matchedProdiKeys as $longerKey) {
                    if (str_contains($longerKey, $slangOrName)) {
                        $isSubOfLonger = true;
                        break;
                    }
                }

                if (!$isSubOfLonger) {
                    $entities['prodi_keywords'][] = $canonicalProdi;
                    $matchedProdiKeys[] = $slangOrName;
                    $intents[] = 'PRODI';
                }
            }
        }

        // Ekstraksi prodi eksplisit jika didahului "prodi <nama>", "jurusan <nama>", "fakultas <nama>"
        if (preg_match('/\b(?:prodi|jurusan|program\s*studi|fakultas)\s+([a-z0-9\s]+?)(?=\s+(?:di|ke|dari|pada|untuk|yang|akreditasi|unggul|baik|ukt|biaya|murah|s1|s2|s3|d4|d3|provinsi|kota)|$)/i', $norm, $matchProdi)) {
            $extractedProdi = trim($matchProdi[1]);
            if ($extractedProdi !== '' && mb_strlen($extractedProdi) >= 3) {
                $resolvedName = self::$prodiMap[$extractedProdi] ?? $extractedProdi;
                $entities['prodi_keywords'][] = $resolvedName;
                $intents[] = 'PRODI';
            }
        }

        $entities['prodi_keywords'] = array_values(array_unique($entities['prodi_keywords']));

        if (empty($intents)) {
            $intents[] = 'INSTITUSI';
        }

        return [
            'raw_query'  => $raw,
            'intents'    => array_values(array_unique($intents)),
            'entities'   => $entities,
            'is_generic' => false,
        ];
    }

    // Ini query pola graf untuk buat potongan WHERE di SPARQL (relasi ck:diselenggarakanOleh dan ck:lokasiDari)
    public function buildSparqlFilter(string $input): string
    {
        $info = $this->detectIntent($input);
        if ($info['is_generic']) {
            return ''; // Query umum => tampilkan semua kampus tanpa batasan
        }

        $e = $info['entities'];
        $clauses = [];

        // 1. Relasi Institusi -> Program Studi (ck:diselenggarakanOleh, ck:namaProdi, ck:jenjang, ck:akreditasiProdi)
        if (!empty($e['prodi_keywords']) || !empty($e['jenjang']) || (!empty($e['akreditasi']) && in_array('PRODI', $info['intents'], true))) {
            $conds = [
                '?pSem a ck:ProgramStudi ; ck:diselenggarakanOleh ?k ; ck:statusProdi "Aktif" .'
            ];

            if (!empty($e['prodi_keywords'])) {
                $prodiSubFilters = [];
                foreach ($e['prodi_keywords'] as $pk) {
                    $prodiSubFilters[] = 'CONTAINS(LCASE(?npSem), ' . F::lit(strtolower($pk)) . ')';
                }
                $conds[] = '?pSem ck:namaProdi ?npSem . FILTER(' . implode(' || ', $prodiSubFilters) . ')';
            }

            if (!empty($e['jenjang'])) {
                $conds[] = '?pSem ck:jenjang ' . F::lit($e['jenjang']) . ' .';
            }

            if (!empty($e['akreditasi']) && in_array('PRODI', $info['intents'], true)) {
                $conds[] = 'OPTIONAL { ?pSem ck:akreditasiProdi ?akrProdiSem } FILTER(REGEX(STR(?akrProdiSem), ' . F::lit($e['akreditasi']) . ', "i") || REGEX(STR(?akreditasi), ' . F::lit($e['akreditasi']) . ', "i"))';
            }

            $clauses[] = 'FILTER EXISTS { ' . implode(' ', $conds) . ' }';
        }

        // 2. Relasi Akreditasi Institusi (jika dicari secara umum di tingkat kampus)
        if (!empty($e['akreditasi']) && !in_array('PRODI', $info['intents'], true)) {
            $clauses[] = 'FILTER EXISTS { ?k ck:statusAkreditasi ?akrSem . FILTER(REGEX(STR(?akrSem), ' . F::lit($e['akreditasi']) . ', "i")) }';
        }

        // 3. Relasi Kategori & Jenis Institusi
        if (!empty($e['kategori'])) {
            $clauses[] = '?k ck:kategori ' . F::lit($e['kategori']) . ' .';
        }
        if (!empty($e['jenis_institusi'])) {
            $clauses[] = '?k ck:jenisInstitusi ' . F::lit($e['jenis_institusi']) . ' .';
        }

        // 4. Relasi Lokasi (Mencakup Kampus Utama dan Kampus Cabang ck:LokasiKampus)
        if (!empty($e['provinsi'])) {
            $pLit = F::lit($e['provinsi']);
            $clauses[] = "FILTER EXISTS { { ?k ck:provinsi $pLit } UNION { ?lokSem a ck:LokasiKampus ; ck:lokasiDari ?k ; ck:provinsi $pLit } }";
        }

        if (!empty($e['kota'])) {
            $kLit = F::lit($e['kota']);
            $clauses[] = "FILTER EXISTS { { ?k ck:kota $kLit } UNION { ?lokSem a ck:LokasiKampus ; ck:lokasiDari ?k ; ck:kota $kLit } }";
        }

        if ($e['is_cabang']) {
            if (!empty($e['cabang_nama'])) {
                $cNama = F::lit($e['cabang_nama']);
                $clauses[] = "FILTER EXISTS { ?lokSem a ck:LokasiKampus ; ck:lokasiDari ?k ; ck:namaLokasi $cNama }";
            } else {
                $clauses[] = "FILTER EXISTS { ?lokSem a ck:LokasiKampus ; ck:lokasiDari ?k }";
            }
        }

        // 5. Relasi UKT / Biaya Kuliah
        if (!empty($e['biaya']['type'])) {
            if ($e['biaya']['type'] === 'murah') {
                $clauses[] = 'FILTER((BOUND(?biayaMin) && ?biayaMin <= 1000000) || CONTAINS(LCASE(STR(?rentangUKT)), "500.000") || CONTAINS(LCASE(STR(?rentangUKT)), "1.000.000"))';
            } elseif ($e['biaya']['type'] === 'max' && !empty($e['biaya']['max_value'])) {
                $maxVal = (int) $e['biaya']['max_value'];
                $clauses[] = "FILTER(BOUND(?biayaMin) && ?biayaMin <= $maxVal)";
            }
        }

        // 6. Batasan Spesifik Institusi jika query menyebutkan nama/singkatan kampus tertentu
        if (!empty($e['institusi_uris'])) {
            $urisList = implode(' ', array_map(fn($u) => '<' . trim($u, '<>') . '>', array_unique($e['institusi_uris'])));
            $clauses[] = "VALUES ?k { $urisList }";
        }

        // Fallback: Jika tidak ada pola relasional spesifik yang terdeteksi, gunakan resolusi catalog
        if (empty($clauses)) {
            $uris = $this->resolveCampusUris($input);
            if (empty($uris)) {
                return 'VALUES ?k { }';
            }
            $urisList = implode(' ', array_map(fn($u) => '<' . trim($u, '<>') . '>', $uris));
            return "VALUES ?k { $urisList }";
        }

        return implode("\n  ", $clauses);
    }

    // Ini query SPARQL 1.1 lengkap ke Apache Jena Fuseki untuk eksekusi pencarian semantik
    public function buildSemanticQuery(string $input): string
    {
     $info = $this->detectIntent($input);
        $sparqlFilter = $this->buildSparqlFilter($input);
        $filterSection = $sparqlFilter !== '' ? "  $sparqlFilter" : "";
          // Jika mencari prodi, kita ingin tahu prodi apa saja yang cocok di kampus tersebut
        $prodiSelection = "";
        $prodiJoin = "";
        if (!empty($info['entities']['prodi_keywords'])) {
            $prodiSelection = " (GROUP_CONCAT(DISTINCT ?namaProdi; SEPARATOR=', ') AS ?prodiCocok)";
            $prodiJoin = "OPTIONAL { 
                ?pMatch a ck:ProgramStudi ; 
                        ck:diselenggarakanOleh ?k ; 
                        ck:namaProdi ?namaProdi .
                FILTER(" . implode(' || ', array_map(fn($pk) => "CONTAINS(LCASE(?namaProdi), ".F::lit(strtolower($pk)).")", $info['entities']['prodi_keywords'])) . ")
            }";
        } 

        return <<<SPARQL
PREFIX ck: <https://carikampus.id/ontology/>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>

SELECT DISTINCT ?k ?kode ?nama ?singkatan ?jenis ?kategori ?kota ?provinsi ?akreditasi ?rentangUKT ?biayaMin ?biayaMax $prodiSelection
WHERE {
$filterSection

  ?k a ck:Institusi ;
     ck:kodePT ?kode ;
       ck:namaInstitusi ?nama .
     
  OPTIONAL { ?k ck:singkatan ?singkatan }
  OPTIONAL { ?k ck:jenisInstitusi ?jenis }
  OPTIONAL { ?k ck:kategori ?kategori }
  OPTIONAL { ?k ck:kota ?kota }
  OPTIONAL { ?k ck:provinsi ?provinsi }

  OPTIONAL { ?k ck:statusAkreditasi ?akreditasi }
  OPTIONAL { ?k ck:rentangUKT ?rentangUKT }
  OPTIONAL { ?k ck:biayaKuliahMin ?biayaMin }
  OPTIONAL { ?k ck:biayaKuliahMax ?biayaMax }
  $prodiJoin
}
GROUP BY ?k ?kode ?nama ?singkatan ?jenis ?kategori ?kota ?provinsi ?akreditasi ?rentangUKT ?biayaMin ?biayaMax

ORDER BY DESC(?prodiCocok) ?nama
LIMIT 7  
SPARQL;
    }

    // Ini fungsi untuk mencocokkan skor relevansi kampus (termasuk relasi terbalik prodi ke kampus induk)
    public function resolveCampusMatches(string $input): array
    {
        $tokens = $this->tokenize($input);
        if (empty($tokens)) {
            return [];
        }

        $info = $this->detectIntent($input);
        $catalog = self::$catalog ?? [];
        $campusMatches = [];

        $genericWords = ['universitas', 'politeknik', 'institut', 'akademi', 'sekolah_tinggi', 'sekolah tinggi', 'negeri', 'kampus', 'ptn', 'ptkn', 'swasta', 'kuliah'];
        $specificTokens = array_filter($tokens, fn($t) => !in_array($t, $genericWords, true) && !in_array(str_replace('_', ' ', $t), $genericWords, true));
        $hasSpecific = !empty($specificTokens);

        $rawNorm = Str::lower(trim($input));
        $rawNorm = preg_replace('/\buniv\b/i', 'universitas', $rawNorm);
        $rawNorm = preg_replace('/\bkampus\b/i', 'universitas', $rawNorm);
        $rawNorm = trim(preg_replace('/[?!.,\-]+/u', ' ', $rawNorm));

        foreach ($catalog as $c) {
            $k     = $c['k'];
            $nama  = Str::lower($c['nama'] ?? '');
            $singk = Str::lower($c['singkatan'] ?? '');
            $kota  = Str::lower($c['kota'] ?? '');
            $prov  = Str::lower($c['provinsi'] ?? '');
            $jenis = Str::lower($c['jenis'] ?? '');

            $matchedTokens = [];
            $matchedCabang = null;
            $score = 0;
            $matchedSpecificCount = 0;

            // 0. Cek Phrase / Awalan Frasa Lengkap (Misal "universitas jam..." langsung cocok ke "Universitas Jambi")
            if ($rawNorm !== '' && mb_strlen($rawNorm) >= 4) {
                if (str_starts_with($nama, $rawNorm)) {
                    $score += 150;
                } elseif (stripos($nama, $rawNorm) !== false) {
                    $score += 100;
                } elseif ($singk !== '' && str_starts_with($singk, $rawNorm)) {
                    $score += 120;
                }
            }

            foreach ($tokens as $t) {
                $tMatch = str_replace('_', ' ', $t);
                $tLen = mb_strlen($tMatch);
                $hit = false;
                $isSpecific = !in_array($t, $genericWords, true) && !in_array($tMatch, $genericWords, true);

                // 1. Cek Kecocokan Singkatan (Eksak & Prefix Regex)
                if ($singk !== '') {
                    if ($singk === $t || preg_match('/\b' . preg_quote($t, '/') . '\b/i', $singk)) {
                        $score += 80;
                        $hit = true;
                    } elseif ($tLen >= 2 && preg_match('/\b' . preg_quote($t, '/') . '/i', $singk)) {
                        $score += 50;
                        $hit = true;
                    }
                }

                // 2. Cek Kecocokan Kata dalam Nama Kampus (Eksak & Prefix Regex untuk autocomplete)
                if (preg_match('/\b' . preg_quote($tMatch, '/') . '\b/i', $nama)) {
                    $score += ($isSpecific ? 60 : 20);
                    $hit = true;
                } elseif ($tLen >= 2 && preg_match('/\b' . preg_quote($tMatch, '/') . '/i', $nama)) {
                    // REGEX PREFIX: Cocok jika tMatch adalah awalan dari suatu kata (misal 'jam' cocok awalan 'Jambi')
                    $score += ($isSpecific ? 45 : 15);
                    $hit = true;
                }

                // 3. Cek Kecocokan Kota Kampus Utama
                if ($kota !== '') {
                    if (preg_match('/\b' . preg_quote($tMatch, '/') . '\b/i', $kota)) {
                        $score += 40;
                        $hit = true;
                    } elseif ($tLen >= 2 && preg_match('/\b' . preg_quote($tMatch, '/') . '/i', $kota)) {
                        $score += 30;
                        $hit = true;
                    }
                }

                // 4. Cek Kecocokan Provinsi Kampus Utama
                if ($prov !== '') {
                    if (preg_match('/\b' . preg_quote($tMatch, '/') . '\b/i', $prov)) {
                        $score += 30;
                        $hit = true;
                    } elseif ($tLen >= 2 && preg_match('/\b' . preg_quote($tMatch, '/') . '/i', $prov)) {
                        $score += 20;
                        $hit = true;
                    }
                }

                // 5. Cek Kecocokan Jenis Kampus
                if ($jenis !== '' && preg_match('/\b' . preg_quote($tMatch, '/') . '/i', $jenis)) {
                    $score += 15;
                    $hit = true;
                }

                // 6. Cek Kecocokan Kampus Cabang / PSDKU
                if (!empty($c['cabang'])) {
                    if ($t === 'cabang' || $t === 'psdku') {
                        $score += 25;
                        $hit = true;
                        if (!$matchedCabang) {
                            $matchedCabang = $c['cabang'][0]['nama'] ?? 'Kampus Cabang';
                        }
                    } elseif ($isSpecific) {
                        foreach ($c['cabang'] as $cb) {
                            $cbNama = Str::lower($cb['nama'] ?? '');
                            $cbKota = Str::lower($cb['kota'] ?? '');
                            $cbProv = Str::lower($cb['provinsi'] ?? '');

                            if ($cbNama !== '' && (preg_match('/\b' . preg_quote($tMatch, '/') . '\b/i', $cbNama) || ($tLen >= 2 && preg_match('/\b' . preg_quote($tMatch, '/') . '/i', $cbNama)))) {
                                $score += 35;
                                $hit = true;
                                $matchedCabang = $cb['nama'];
                                break;
                            } elseif ($cbKota !== '' && (preg_match('/\b' . preg_quote($tMatch, '/') . '\b/i', $cbKota) || ($tLen >= 2 && preg_match('/\b' . preg_quote($tMatch, '/') . '/i', $cbKota)))) {
                                $score += 25;
                                $hit = true;
                                $matchedCabang = $cb['nama'] ?: $cb['kota'];
                                break;
                            } elseif ($cbProv !== '' && (preg_match('/\b' . preg_quote($tMatch, '/') . '\b/i', $cbProv) || ($tLen >= 2 && preg_match('/\b' . preg_quote($tMatch, '/') . '/i', $cbProv)))) {
                                $score += 20;
                                $hit = true;
                                $matchedCabang = $cb['nama'] ?: $cb['provinsi'];
                                break;
                            }
                        }
                    }
                }

                // 7. Cek Typo / Fuzzy Matching
                if (!$hit && $tLen >= 5) {
                    $words = preg_split('/\s+/', preg_replace('/^(universitas|institut|politeknik|akademi|sekolah tinggi)\s+/i', '', $nama));
                    foreach ($words as $w) {
                        $wLen = mb_strlen($w);
                        if ($wLen >= 5) {
                            $maxLev = ($tLen <= 7 && $wLen <= 7) ? 1 : 2;
                            if (levenshtein($tMatch, $w) <= $maxLev) {
                                similar_text($tMatch, $w, $pct);
                                if ($pct >= 80) {
                                    $score += 15;
                                    $hit = true;
                                    break;
                                }
                            }
                        }
                    }
                }

                if ($hit) {
                    $matchedTokens[$t] = true;
                    if ($isSpecific) {
                        $matchedSpecificCount++;
                    }
                }
            }

            // FILTER RELEVANSI: Jika query berisi kata spesifik (misal 'jam'),
            // abaikan kampus yang HANYA cocok kata generik ('universitas') tapi TIDAK cocok kata spesifik sama sekali.
            if ($hasSpecific && $matchedSpecificCount === 0) {
                continue;
            }

            if (!empty($matchedTokens)) {
                // Bonus jika seluruh token query cocok
                if (count($matchedTokens) === count($tokens)) {
                    $score += 60;
                }

                $campusMatches[$k] = [
                    'campus'         => $c,
                    'matched_tokens' => $matchedTokens,
                    'matched_cabang' => $matchedCabang,
                    'score'          => $score,
                ];
            }
        }

        // Relasi Program Studi ke Fuseki jika terdeteksi intent PRODI
        if (!empty($info['entities']['prodi_keywords'])) {
            $prodiFilters = [];
            foreach ($info['entities']['prodi_keywords'] as $pk) {
                $prodiFilters[] = "CONTAINS(LCASE(?np), " . F::lit(strtolower($pk)) . ")";
            }
            $prodiFilterStr = implode(" || ", $prodiFilters);

            $jenjangFilter = !empty($info['entities']['jenjang'])
                ? " ?p ck:jenjang " . F::lit($info['entities']['jenjang']) . " ."
                : "";

            $prodiSparql = <<<SPARQL
PREFIX ck: <https://carikampus.id/ontology/>
SELECT DISTINCT ?k WHERE {
    ?p a ck:ProgramStudi ;
       ck:diselenggarakanOleh ?k ;
       ck:statusProdi "Aktif" ;
       ck:namaProdi ?np .
    $jenjangFilter
    FILTER($prodiFilterStr)
}
SPARQL;

            try {
                $prodiCampuses = $this->fuseki->select($prodiSparql);
                $prodiCampusUris = array_column($prodiCampuses, 'k');
                foreach ($prodiCampusUris as $u) {
                    if (isset($campusMatches[$u])) {
                        $campusMatches[$u]['score'] = max(
                        $campusMatches[$u]['score'],
                         1000
                        );
                    } else {
                        // Tambahkan dari catalog jika belum ada
                        $catItem = collect($catalog)->firstWhere('k', $u);
                        if ($catItem) {
                            $campusMatches[$u] = [
                                'campus'         => $catItem,
                                'matched_tokens' => ['prodi' => true],
                                'matched_cabang' => null,
                                'score'          => 70,
                            ];
                        }
                    }
                }
            } catch (\Throwable $e) {
                // Graceful fallback jika Fuseki sedang offline
            }
        }

        uasort($campusMatches, function ($a, $b) {
            if ($a['score'] === $b['score']) {
                $aIsMain = empty($a['matched_cabang']) ? 1 : 0;
                $bIsMain = empty($b['matched_cabang']) ? 1 : 0;
                return $bIsMain <=> $aIsMain;
            }
            return $b['score'] <=> $a['score'];
        });

        return $campusMatches;
    }

    // Ini fungsi untuk ambil daftar URI resmi (Uniform Resource Identifier) kampus dari hasil pencarian
    public function resolveCampusUris(string $input): array
    {
        $matches = $this->resolveCampusMatches($input);
        return array_keys($matches);
    }

    // Ini fungsi untuk tampilkan saran autocomplete pencarian cepat langsung dari data semantik Fuseki
    public function getSuggestions(string $input, int $limit = 20): array
    {
        $raw = trim($input);
        if ($raw === '') {
            return [];
        }

        if ($this->isGenericQuery($raw)) {
            $featuredKodes = ['001002', '001001', '002001', '001005', '002002'];
            $catalog = self::$catalog ?? [];
            $results = [];
            foreach ($catalog as $c) {
                if (in_array($c['kode'], $featuredKodes, true)) {
                    $results[] = [
                        'id'        => $c['kode'],
                        'nama'      => $c['nama'],
                        'singkatan' => $c['singkatan'] ?? '',
                        'kota'      => $c['kota'] ?? '',
                        'provinsi'  => Str::of($c['provinsi'] ?? '')->replaceFirst('Prov. ', '')->toString(),
                        'cabang'    => null,
                    ];
                }
            }
            return array_slice($results, 0, $limit);
        }

        $matches = $this->resolveCampusMatches($raw);
        if (empty($matches)) {
            return [];
        }

        $results = [];
        foreach ($matches as $u => $info) {
            $c = $info['campus'] ?? null;
            if (!$c) continue;

            $results[] = [
                'id'        => $c['kode'],
                'nama'      => $c['nama'],
                'singkatan' => $c['singkatan'] ?? '',
                'kota'      => $c['kota'] ?? '',
                'provinsi'  => Str::of($c['provinsi'] ?? '')->replaceFirst('Prov. ', '')->toString(),
                'cabang'    => $info['matched_cabang'] ?? null,
            ];
            if (count($results) >= $limit) {
                break;
            }
        }

        return $results;
    }
}
