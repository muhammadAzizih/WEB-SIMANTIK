<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FusekiService as F;
use App\Services\DbpediaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ChatbotController extends Controller
{
    private static ?array $cachedCampuses = null;
    private array $places = [];
    private array $lastRows = [];
    private array $lastUktGroups = [];
    private array $lastFaculties = [];
    private array $lastProdiList = [];
    private array $lastBranches = [];
    private array $lastProdi = [];
    private bool $lastOutOfScope = false;

    // Inisialisasi service Fuseki, DBpedia, & SemanticSearchService
    public function __construct(
        private F $fuseki,
        private DbpediaService $dbpedia,
        private \App\Services\SemanticSearchService $searchService
    ) {
        $this->ensureCampusesCached();
    }

    // Cache daftar 201 PTN ke memori untuk resolusi cepat dan penanganan typo
    private function ensureCampusesCached(): void
    {
        if (self::$cachedCampuses === null) {
            try {
                self::$cachedCampuses = Cache::remember('all_ptn_catalog_v2', 86400, function () {
                    return $this->fuseki->select('PREFIX ck: <https://carikampus.id/ontology/> SELECT ?kode ?nama ?singkatan ?kota ?prov WHERE { ?pt a ck:Institusi ; ck:kodePT ?kode ; ck:namaInstitusi ?nama . OPTIONAL { ?pt ck:singkatan ?singkatan } OPTIONAL { ?pt ck:kota ?kota } OPTIONAL { ?pt ck:provinsi ?prov } }');
                }) ?: [];
            } catch (\Throwable $e) {
                report($e);
                self::$cachedCampuses = [];
            }
        }
    }

    // Ini endpoint utama chatbot jiBot (menerima pesan user dan memproses Semantic RAG)
    public function reply(Request $request)
    {
        // Reset state request
        $this->places = [];
        $this->lastRows = [];
        $this->lastUktGroups = [];
        $this->lastFaculties = [];
        $this->lastProdiList = [];
        $this->lastBranches = [];
        $this->lastProdi = [];
        $this->lastOutOfScope = false;

        $message = trim((string) $request->input('message', ''));
        $history = (array) $request->input('history', []);
        $campusId = $request->input('campus_id');
        $prodiKode = $request->input('prodi_kode');

        // Cek jika pesan kosong
        if ($message === '') {
            return response()->json([
                'reply' => 'Halo! Ada yang bisa aku bantu seputar kampus impianmu?',
                'places' => []
            ]);
        }

        try {
            // 1. Ambil data fakta lengkap dari Fuseki via SPARQL
            $fusekiContext = $this->retrieveFusekiContext($message, $history, $campusId, $prodiKode);

            // 2. Olah pesan dan fakta Fuseki menggunakan Gemini AI
            $reply = $this->askGemini($message, $fusekiContext, $history);
        } catch (\Throwable $e) {
            report($e);
            // Fallback cerdas berbasis aturan fakta jika AI offline/gangguan kuota
            $reply = $this->formatSmartFallback($message, $this->lastRows);
        }

        // Pastikan tidak ada emoji sama sekali di respons
        $reply = $this->stripEmojis($reply);

        // Return respons JSON ke frontend
        return response()->json([
            'reply' => $reply,
            'places' => $this->places,
        ]);
    }

    // Ini fungsi untuk ambil data fakta resmi dari Apache Jena Fuseki via SPARQL agar AI tidak halusinasi
    private function retrieveFusekiContext(string $query, array $history = [], ?string $campusId = null, ?string $prodiKode = null): string
    {
        // Bersihkan tanda baca
        $cleaned = trim(preg_replace('/[?!.,]+/u', ' ', Str::lower($query)));

        // 1. Ambil Kosakata Semantik Dinamis dari Fuseki via SemanticSearchService
        $provMap = $this->searchService->getProvinceMap();
        $cityMap = $this->searchService->getCityMap();
        $prodiMap = $this->searchService->getProdiMap();

        // 2. Deteksi Intent Khusus (Komparasi & Out-Of-Scope)
        $isComparison = (bool) preg_match('/\b(atau|vs|banding|bandingkan|dibanding|dibandingkan|lebih\s+(bagus|murah|mahal|tinggi|rendah|unggul|baik)|mana\s+yang\s+lebih|bagusan\s+mana|murahan\s+mana)\b/i', $cleaned);
        $isOutOfScope = (bool) preg_match('/\b(daya\s*tampung|kuota|kuotanya|passing\s*grade|snbp|snbt|mandiri|ujian\s*mandiri|jalur\s*mandiri|jadwal\s*pendaftaran|kapan\s*buka|kapan\s*daftar|dekan)\b/i', $cleaned);
        $this->lastOutOfScope = $isOutOfScope;

        // 3. Ekstraksi Kata-kata dari Pertanyaan
        $words = preg_split('/\s+/u', $cleaned, -1, PREG_SPLIT_NO_EMPTY);

        // 4. Deteksi Disiplin Ilmu / Pencarian Prodi via Kosakata Semantik Fuseki (ck:BidangIlmu & skos:altLabel)
        $prodiKeywords = [];
        foreach ($prodiMap as $slangOrName => $canonical) {
            if (preg_match('/\b' . preg_quote($slangOrName, '/') . '\b/i', $cleaned)) {
                $prodiKeywords[] = $canonical;
            }
        }
        $prodiKeywords = array_values(array_unique($prodiKeywords));

        // 5. Deteksi Wilayah via Kosakata Semantik Fuseki (ck:Provinsi, ck:Kota & skos:altLabel)
        $detectedRegion = null;
        foreach ($provMap as $reg => $resmi) {
            if (preg_match('/\b' . preg_quote($reg, '/') . '\b/i', $cleaned)) {
                $detectedRegion = preg_replace('/^prov\.\s+/i', '', $resmi);
                break;
            }
        }
        if (!$detectedRegion) {
            foreach ($cityMap as $alt => $resmi) {
                if (preg_match('/\b' . preg_quote($alt, '/') . '\b/i', $cleaned)) {
                    $detectedRegion = $resmi;
                    break;
                }
            }
        }

        // 6. Resolusi Target Kampus (Pencegah False Negative & Penangan Typo)
        $resolvedCampuses = [];
        $all = self::$cachedCampuses ?? [];

        if (!empty($campusId) && !$isComparison) {
            // A. PENGGUNA BERADA DI HALAMAN KAMPUS TERTENTU
            // Kunci pertanyaan ke kampus aktif KECUALI secara eksplisit menyebutkan singkatan kampus lain
            $attributeWords = [
                'rentang', 'termahal', 'termurah', 'tertinggi', 'terendah', 'ukt', 'biaya', 'uang', 'kuliah',
                'fakultas', 'jurusan', 'prodi', 'akreditasi', 'alamat', 'jalan', 'lokasi', 'tempat', 'kota',
                'provinsi', 'rektor', 'pimpinan', 'motto', 'tahun', 'berdiri', 'kapan', 'cabang', 'psdku',
                'website', 'web', 'link', 'berapa', 'mana', 'dimana', 'apa', 'ada', 'dong', 'ya', 'kak', 'bang',
                'paling', 'mahal', 'murah', 'bagus', 'unggul', 'baik', 'jumlah', 'kode', 'nya', 'sih', 'daftar',
                'kelompok', 'golongan', 'sejarah', 'visi', 'misi', 'rekomendasi', 'ini', 'itu', 'jenis', 'institusi',
                'jenjang', 'kalo', 'kalau', 'bagaimana', 'gimana', 'info', 'profil', 'tentang'
            ];

            // Dapatkan informasi kampus aktif
            $currCampus = null;
            foreach ($all as $c) {
                if (($c['kode'] ?? '') === $campusId) {
                    $currCampus = $c;
                    break;
                }
            }

            // Kata-kata yang merujuk pada kampus aktif itu sendiri
            $activeCampusTokens = [];
            if ($currCampus) {
                $activeNameTokens = preg_split('/\s+/u', Str::lower($currCampus['nama'] ?? ''));
                $activeSingkTokens = preg_split('/\s+/u', Str::lower($currCampus['singkatan'] ?? ''));
                $activeCampusTokens = array_unique(array_merge($activeNameTokens, $activeSingkTokens));
            }

            // Saring kata-kata yang bukan atribut dan bukan bagian dari nama kampus aktif
            $potentialOther = array_values(array_filter($words, fn($w) => 
                !in_array($w, $attributeWords, true) && 
                !in_array($w, $activeCampusTokens, true) &&
                mb_strlen($w) >= 2
            ));

            $otherCampus = null;
            if (!empty($potentialOther)) {
                // Cek apakah ada token yang secara tepat merujuk ke singkatan kampus LAIN
                foreach ($potentialOther as $ow) {
                    foreach ($all as $c) {
                        if (($c['kode'] ?? '') === $campusId) continue;
                        $singk = Str::lower(trim($c['singkatan'] ?? ''));
                        if ($singk !== '' && $singk === $ow) {
                            $otherCampus = $c;
                            break 2;
                        }
                    }
                }
            }

            if ($otherCampus !== null) {
                $resolvedCampuses = [$otherCampus];
            } elseif ($currCampus !== null) {
                $resolvedCampuses = [$currCampus];
            }
        } elseif ($isComparison) {
            // B. KOMPARASI BEBERAPA KAMPUS
            $matched = [];
            foreach ($words as $w) {
                if (mb_strlen($w) < 2) continue;
                foreach ($all as $c) {
                    $singk = Str::lower(trim($c['singkatan'] ?? ''));
                    $nama = Str::lower(trim($c['nama'] ?? ''));
                    if (($singk !== '' && $singk === $w) || str_contains($nama, $w)) {
                        $matched[$c['kode']] = $c;
                    }
                }
            }
            if (!empty($matched)) {
                $resolvedCampuses = array_values($matched);
            }
        } elseif (!empty($detectedRegion) && empty($prodiKeywords)) {
            // C. PENCARIAN DAFTAR KAMPUS DI SUATU WILAYAH
            $resolvedCampuses = [];
        } else {
            // D. PENCARIAN BIASA (Singkatan, Nama Eksak, Substring, dan Fuzzy Typo)
            // 1. Cek Singkatan Eksak
            foreach ($words as $w) {
                if (mb_strlen($w) < 2) continue;
                foreach ($all as $c) {
                    $singk = Str::lower(trim($c['singkatan'] ?? ''));
                    if ($singk !== '' && $singk === $w) {
                        $resolvedCampuses = [$c];
                        break 2;
                    }
                }
            }

            // 2. Substring Nama Kampus
            if (empty($resolvedCampuses)) {
                $stopWords = ['apa', 'berapa', 'dimana', 'mana', 'kapan', 'siapa', 'gimana', 'bagaimana', 'rekomendasi', 'info', 'tentang', 'profil', 'kampus', 'universitas', 'institut', 'politeknik', 'negeri', 'ptn', 'dong', 'ya', 'sih', 'di', 'ke', 'dari', 'yang', 'dan', 'ini', 'itu', 'nya', 'ukt', 'akreditasi', 'jurusan', 'prodi', 'fakultas', 'biaya'];
                $meaningful = array_values(array_filter($words, fn($w) => !in_array($w, $stopWords, true) && mb_strlen($w) > 2));
                $core = implode(' ', $meaningful);

                if ($core !== '') {
                    foreach ($all as $c) {
                        if (str_contains(Str::lower($c['nama']), $core)) {
                            $resolvedCampuses = [$c];
                            break;
                        }
                    }
                }

                // 3. Fuzzy Matching untuk Mengatasi Typo Nama Kampus (hanya jika bukan pertanyaan prodi/wilayah)
                if (empty($resolvedCampuses) && empty($prodiKeywords) && empty($detectedRegion)) {
                    $bestMatch = null;
                    $bestScore = 0;
                    foreach ($all as $c) {
                        $nama = Str::lower($c['nama']);
                        $cleanNama = trim(preg_replace('/^(universitas|institut|politeknik|akademi|sekolah tinggi)\s+/i', '', $nama));
                        similar_text($core, $cleanNama, $pct);
                        if ($pct > $bestScore) {
                            $bestScore = $pct;
                            $bestMatch = $c;
                        }
                        foreach ($meaningful as $mw) {
                            similar_text($mw, $cleanNama, $p2);
                            if ($p2 > $bestScore && $p2 >= 80) {
                                $bestScore = $p2;
                                $bestMatch = $c;
                            }
                        }
                    }
                    if ($bestScore >= 78 && $bestMatch !== null) {
                        $resolvedCampuses = [$bestMatch];
                    }
                }
            }
        }

        // Jika belum menemukan kampus, cek riwayat chat sebelumnya (Multi-Turn Context Memory)
        if (empty($resolvedCampuses) && empty($detectedRegion) && !empty($history)) {
            for ($i = count($history) - 1; $i >= 0; $i--) {
                $pastText = Str::lower($history[$i]['text'] ?? '');
                if ($pastText === '') continue;
                foreach ($all as $c) {
                    $singk = Str::lower(trim($c['singkatan'] ?? ''));
                    $nama  = Str::lower(trim($c['nama'] ?? ''));
                    if (($singk !== '' && preg_match('/\b' . preg_quote($singk, '/') . '\b/i', $pastText)) ||
                        str_contains($pastText, $nama)) {
                        $resolvedCampuses = [$c];
                        break 2;
                    }
                }
            }
        }

        // Susun Kondisi Filter SPARQL
        $filterCondition = '';
        $limitCount = 6;

        if (!empty($resolvedCampuses)) {
            $kodes = array_map(fn($c) => F::lit($c['kode']), $resolvedCampuses);
            $filterCondition = '?pt ck:kodePT ?kode . FILTER(?kode IN (' . implode(', ', $kodes) . '))';
        } elseif (!empty($prodiKeywords)) {
            // PENCARIAN KAMPUS-KAMPUS YANG MENYELENGGARAKAN PROGRAM STUDI TERKAIT
            $prodiSub = [];
            foreach ($prodiKeywords as $pk) {
                $prodiSub[] = 'CONTAINS(LCASE(?namaProdi), ' . F::lit(strtolower($pk)) . ')';
            }
            $pCond = 'FILTER(' . implode(' || ', $prodiSub) . ')';

            $regCond = '';
            if (!empty($detectedRegion)) {
                $searchRegion = trim(preg_replace('/^(prov\.|d\.i\.|d\.k\.i\.)\s*/i', '', $detectedRegion));
                $searchRegion = trim(preg_replace('/[^a-zA-Z0-9\s]/', '', $searchRegion));
                $regCond = "FILTER(
                    REGEX(?prov, \"$searchRegion\", \"i\") ||
                    REGEX(?kota, \"$searchRegion\", \"i\") ||
                    EXISTS { ?lokCbg a ck:LokasiKampus ; ck:lokasiDari ?pt ; ck:kota ?lokKota . FILTER(REGEX(?lokKota, \"$searchRegion\", \"i\")) } ||
                    EXISTS { ?lokCbg a ck:LokasiKampus ; ck:lokasiDari ?pt ; ck:provinsi ?lokProv . FILTER(REGEX(?lokProv, \"$searchRegion\", \"i\")) }
                )";
            }

            // Ini query SPARQL untuk cari kampus yang punya prodi tertentu (misal: teknologi informasi)
            $sparqlProdi = <<<SPARQL
            PREFIX ck: <https://carikampus.id/ontology/>
            SELECT ?kode ?nama ?singkatan ?kota ?prov ?akr ?namaProdi ?jenjang ?akrProdi ?ukt
            WHERE {
              ?pt a ck:Institusi ;
                  ck:kodePT ?kode ;
                  ck:namaInstitusi ?nama ;
                  ck:kota ?kota ;
                  ck:provinsi ?prov .
              OPTIONAL { ?pt ck:singkatan ?singkatan }
              OPTIONAL { ?pt ck:statusAkreditasi ?akr }
              OPTIONAL { ?pt ck:rentangUKT ?ukt }
              ?pr ck:diselenggarakanOleh ?pt ;
                  ck:statusProdi "Aktif" ;
                  ck:namaProdi ?namaProdi .
              OPTIONAL { ?pr ck:jenjang ?jenjang }
              OPTIONAL { ?pr ck:akreditasiProdi ?akrProdi }
              $pCond
              $regCond
            }
            ORDER BY ?nama ?namaProdi
            LIMIT 25
            SPARQL;

            $prodiResults = $this->fuseki->select($sparqlProdi);
            if (!empty($prodiResults)) {
                $this->lastProdiList = $prodiResults;
                $context = "DATA FAKTA RESMI DARI DATABASE FUSEKI (carikampus.ttl):\n";
                $context .= "Berikut daftar Perguruan Tinggi Negeri resmi yang menyelenggarakan program studi terkait (\"" . implode(', ', $prodiKeywords) . "\")" . (!empty($detectedRegion) ? " di wilayah " . $detectedRegion : "") . ":\n\n";

                $seenCampuses = [];
                foreach ($prodiResults as $pr) {
                    $cKode = $pr['kode'] ?? '';
                    if (!empty($cKode)) {
                        $this->places[] = $cKode;
                    }
                    $cKey = $pr['nama'] . (!empty($pr['singkatan']) ? ' (' . $pr['singkatan'] . ')' : '') . ' - ' . ($pr['kota'] ?? '-') . ', ' . ($pr['prov'] ?? '-');
                    $seenCampuses[$cKey][] = "[" . ($pr['jenjang'] ?? 'S1') . "] " . $pr['namaProdi'] . " (Akreditasi: " . ($pr['akrProdi'] ?? 'Terakreditasi') . ")";
                }

                foreach ($seenCampuses as $campName => $prList) {
                    $context .= "- Kampus: " . $campName . "\n";
                    $context .= "  Program Studi: " . implode(', ', $prList) . "\n";
                }
                return $context;
            }
        } elseif (!empty($detectedRegion)) {
            $searchRegion = trim(preg_replace('/^(prov\.|d\.i\.|d\.k\.i\.)\s*/i', '', $detectedRegion));
            $searchRegion = trim(preg_replace('/[^a-zA-Z0-9\s]/', '', $searchRegion));
            $filterCondition = "FILTER(
                REGEX(?prov, \"$searchRegion\", \"i\") ||
                REGEX(?kota, \"$searchRegion\", \"i\") ||
                EXISTS { ?lokCbg a ck:LokasiKampus ; ck:lokasiDari ?pt ; ck:kota ?lokKota . FILTER(REGEX(?lokKota, \"$searchRegion\", \"i\")) } ||
                EXISTS { ?lokCbg a ck:LokasiKampus ; ck:lokasiDari ?pt ; ck:provinsi ?lokProv . FILTER(REGEX(?lokProv, \"$searchRegion\", \"i\")) }
            )";
            $limitCount = 10;
        } else {
            return 'PERCAKAPAN UMUM SEPUTAR PERKULIAHAN & PENDIDIKAN TINGGI. Tidak ada kampus atau program studi spesifik yang diminta. Jawablah pertanyaan pengguna secara cerdas, ramah, dan berwawasan luas seperti asisten AI perkuliahan profesional.';
        }

        // Ini query SPARQL ke Fuseki untuk ambil profil lengkap kampus (akreditasi, UKT, alamat, fakultas)
        $sparql = <<<SPARQL
        PREFIX ck: <https://carikampus.id/ontology/>
        SELECT ?kode ?nama ?singkatan ?jenis ?berdiri ?kota ?prov ?alamat ?akr ?web ?ukt ?biayaMin ?biayaMax
               (COUNT(DISTINCT ?fak) AS ?jmlFakultas)
               (COUNT(DISTINCT ?pr) AS ?jmlProdi)
        WHERE {
          ?pt a ck:Institusi ;
              ck:namaInstitusi ?nama .
          OPTIONAL { ?pt ck:kodePT ?kode }
          OPTIONAL { ?pt ck:singkatan ?singkatan }
          OPTIONAL { ?pt ck:jenisInstitusi ?jenis }
          OPTIONAL { ?pt ck:tanggalBerdiri ?berdiri }
          OPTIONAL { ?pt ck:kota ?kota }
          OPTIONAL { ?pt ck:provinsi ?prov }
          OPTIONAL { ?pt ck:alamatLengkap ?alamat }
          OPTIONAL { ?pt ck:statusAkreditasi ?akr }
          OPTIONAL { ?pt ck:websiteResmi ?web }
          OPTIONAL { ?pt ck:rentangUKT ?ukt }
          OPTIONAL { ?pt ck:biayaKuliahMin ?biayaMin }
          OPTIONAL { ?pt ck:biayaKuliahMax ?biayaMax }
          OPTIONAL {
            ?pr ck:diselenggarakanOleh ?pt ;
                ck:statusProdi "Aktif" ;
                ck:fakultas ?fak .
          }
          $filterCondition
        }
        GROUP BY ?kode ?nama ?singkatan ?jenis ?berdiri ?kota ?prov ?alamat ?akr ?web ?ukt ?biayaMin ?biayaMax
        ORDER BY ?nama
        LIMIT $limitCount
        SPARQL;

        $rows = $this->fuseki->select($sparql);
        if (empty($rows)) {
            return 'Tidak ada data spesifik kampus yang cocok di dataset Fuseki untuk pertanyaan ini.';
        }

        $this->lastRows = $rows;
        $context = "DATA FAKTA RESMI DARI DATABASE FUSEKI (carikampus.ttl):\n";

        foreach ($rows as $r) {
            if (!empty($r['kode'])) {
                $this->places[] = $r['kode'];
            }
            $cKode = $r['kode'] ?? '';
            $uktMin = !empty($r['biayaMin']) ? 'Rp ' . number_format((float)$r['biayaMin'], 0, ',', '.') : 'Rp 500.000';
            $uktMax = !empty($r['biayaMax']) ? 'Rp ' . number_format((float)$r['biayaMax'], 0, ',', '.') : 'Rp 10.000.000';
            $tahunBerdiri = !empty($r['berdiri']) ? substr($r['berdiri'], 0, 4) : 'Belum tercatat';
            $jmlFakultas = !empty($r['jmlFakultas']) && (int)$r['jmlFakultas'] > 0 ? $r['jmlFakultas'] . ' fakultas' : 'Belum tercatat';
            $jmlProdi = !empty($r['jmlProdi']) && (int)$r['jmlProdi'] > 0 ? $r['jmlProdi'] . ' program studi aktif' : 'Belum tercatat';

            $context .= "- Kampus: " . ($r['nama'] ?? '-') . " (" . ($r['singkatan'] ?? '-') . ")\n";
            $context .= "  Kode PT: " . ($r['kode'] ?? '-') . "\n";
            $context .= "  Jenis Institusi: " . ($r['jenis'] ?? 'Perguruan Tinggi Negeri') . "\n";
            $context .= "  Tahun Berdiri: " . $tahunBerdiri . " (" . ($r['berdiri'] ?? '-') . ")\n";
            $context .= "  Akreditasi: " . ($r['akr'] ?? 'Belum ada data') . "\n";
            $context .= "  Wilayah: " . ($r['kota'] ?? '-') . ", " . ($r['prov'] ?? '-') . "\n";
            $context .= "  Alamat Lengkap / Jalan: " . ($r['alamat'] ?? 'Belum ada data') . "\n";
            $context .= "  UKT Termurah / Terendah: " . $uktMin . " per semester (Kelompok 1)\n";
            $context .= "  UKT Termahal / Tertinggi: " . $uktMax . " per semester\n";
            $context .= "  Rentang UKT: " . ($r['ukt'] ?? ($uktMin . ' - ' . $uktMax)) . " per semester\n";
            $context .= "  Jumlah Fakultas: " . $jmlFakultas . "\n";
            $context .= "  Jumlah Program Studi: " . $jmlProdi . "\n";
            $context .= "  Website Resmi: " . ($r['web'] ?? '-') . "\n";

            // Ini query untuk panggil data eksternal dari DBpedia (ambil nama rektor dan motto kampus)
            $needsDbpedia = count($rows) <= 2 || str_contains($cleaned, 'rektor') || str_contains($cleaned, 'pimpinan') || str_contains($cleaned, 'motto');
            if ($needsDbpedia && !empty($r['nama'])) {
                try {
                    $dbpediaInfo = $this->dbpedia->getInfo($r['nama']);
                    if (!empty($dbpediaInfo['rector'])) {
                        $context .= "  Pimpinan / Rektor (DBpedia): " . $dbpediaInfo['rector'] . "\n";
                    }
                    if (!empty($dbpediaInfo['motto'])) {
                        $context .= "  Motto (DBpedia): \"" . $dbpediaInfo['motto'] . "\"\n";
                    }
                } catch (\Throwable $e) {
                    // Abaikan gangguan koneksi DBpedia agar respons tetap cepat
                }
            }

            // Tarik Rincian Semua Kelompok UKT dari ck:DataUKT
            if (!empty($cKode)) {
                $qUkt = "PREFIX ck: <https://carikampus.id/ontology/> SELECT ?kelompok ?minRp ?maxRp WHERE { ?pt ck:kodePT " . F::lit($cKode) . " . ?pt ck:memilikiUKT ?u . OPTIONAL { ?u ck:kelompokUKT ?kelompok } OPTIONAL { ?u ck:nominalMinRp ?minRp } OPTIONAL { ?u ck:nominalMaxRp ?maxRp } } ORDER BY ?kelompok";
                $ukts = $this->fuseki->select($qUkt);
                if (!empty($ukts)) {
                    $this->lastUktGroups = $ukts;
                    $context .= "  Rincian Kelompok UKT:\n";
                    foreach ($ukts as $u) {
                        $min = !empty($u['minRp']) ? $u['minRp'] : 'Rp 0';
                        $max = !empty($u['maxRp']) ? $u['maxRp'] : $min;
                        $context .= "    * " . ($u['kelompok'] ?? 'Kelompok') . ": {$min} s/d {$max}\n";
                    }
                }
            }

            // Tarik Daftar Nama Fakultas Lengkap
            if (!empty($cKode)) {
                $qFak = "PREFIX ck: <https://carikampus.id/ontology/> SELECT DISTINCT ?fakultas WHERE { ?pt ck:kodePT " . F::lit($cKode) . " . ?pr ck:diselenggarakanOleh ?pt ; ck:fakultas ?fakultas . } ORDER BY ?fakultas";
                $faks = $this->fuseki->select($qFak);
                if (!empty($faks)) {
                    $this->lastFaculties = $faks;
                    $fakNames = array_map(fn($f) => $f['fakultas'], $faks);
                    $context .= "  Daftar Nama Fakultas: " . implode(', ', $fakNames) . "\n";
                }
            }

            // Tarik Kampus Cabang / Lokasi Lain / PSDKU
            if (!empty($cKode)) {
                $lokRows = $this->fuseki->select('PREFIX ck: <https://carikampus.id/ontology/> SELECT ?namaLokasi ?jenisLokasi ?kota ?alamat WHERE { ?pt ck:kodePT ' . F::lit($cKode) . ' . ?lok ck:lokasiDari ?pt ; ck:namaLokasi ?namaLokasi . OPTIONAL { ?lok ck:jenisLokasi ?jenisLokasi } OPTIONAL { ?lok ck:kota ?kota } OPTIONAL { ?lok ck:alamatLengkap ?alamat } } LIMIT 3');
                if (!empty($lokRows)) {
                    $this->lastBranches = $lokRows;
                    $context .= "  Kampus Cabang / Lokasi Lain / PSDKU:\n";
                    foreach ($lokRows as $lr) {
                        $context .= "    * " . $lr['namaLokasi'] . " (" . ($lr['jenisLokasi'] ?? 'Kampus Cabang') . ") di " . ($lr['kota'] ?? '-') . " [Alamat: " . ($lr['alamat'] ?? '-') . "]\n";
                    }
                }
            }
        }

        // Jika Pengguna Mencari Program Studi Tertentu ATAU Menanyakan Daftar Prodi/Jurusan Kampus Ini
        $isProdiInquiry = (bool) preg_match('/\b(prodi|program\s*studi|jurusan|pilihan\s*jurusan|ada\s*jurusan|ada\s*prodi|daftar\s*(prodi|jurusan)|apa\s*aja\s*(prodi|jurusan|program\s*studi)nya?)\b/i', $cleaned);

        if (!empty($rows)) {
            $firstKode = $rows[0]['kode'] ?? '';
            if (!empty($firstKode)) {
                if (!empty($prodiKeywords)) {
                    // Pencarian berdasarkan disiplin ilmu tertentu (misal: "kedokteran", "informatika")
                    $patternProdi = implode('|', array_map(fn($w) => preg_quote($w, '/'), $prodiKeywords));
                    $pSparql = 'PREFIX ck: <https://carikampus.id/ontology/> SELECT ?kodeProdi ?namaProdi ?jenjang ?akrProdi ?fakultas WHERE {'
                        . ' ?pt ck:kodePT ' . F::lit($firstKode) . ' .'
                        . ' ?pr ck:diselenggarakanOleh ?pt ; ck:kodeProdi ?kodeProdi ; ck:namaProdi ?namaProdi ; ck:jenjang ?jenjang .'
                        . ' OPTIONAL { ?pr ck:akreditasiProdi ?akrProdi }'
                        . ' OPTIONAL { ?pr ck:fakultas ?fakultas }'
                        . ' FILTER(REGEX(?namaProdi, "\\\\b(' . $patternProdi . ')\\\\b", "i"))'
                        . ' } ORDER BY DESC(?jenjang = "S1") ?namaProdi LIMIT 10';
                    $pRows = $this->fuseki->select($pSparql);
                    if (!empty($pRows)) {
                        $this->lastProdiList = $pRows;
                        $context .= "\nPROGRAM STUDI YANG DITEMUKAN DI KAMPUS TERKAIT SESUAI PERTANYAAN:\n";
                        foreach ($pRows as $pr) {
                            $context .= "- [" . $pr['jenjang'] . "] " . $pr['namaProdi'] . " | Akreditasi: " . ($pr['akrProdi'] ?? 'Baik') . " | Fakultas: " . ($pr['fakultas'] ?? '-') . " (Kode Prodi: " . $pr['kodeProdi'] . ")\n";
                        }
                    } else {
                        $context .= "\nCATATAN PROGRAM STUDI: Program studi terkait kata kunci (\"" . implode(', ', $prodiKeywords) . "\") TIDAK DITEMUKAN di kampus ini pada basis data Fuseki.\n";
                    }
                } elseif ($isProdiInquiry || (isset($rows[0]['jmlProdi']) && (int)$rows[0]['jmlProdi'] <= 25)) {
                    // Pengguna menanyakan daftar prodi secara umum ATAU kampus memiliki jumlah prodi sedikit (seperti AKN Aceh Barat yang memiliki 3 prodi)
                    $pSparql = 'PREFIX ck: <https://carikampus.id/ontology/> SELECT ?kodeProdi ?namaProdi ?jenjang ?akrProdi ?fakultas WHERE {'
                        . ' ?pt ck:kodePT ' . F::lit($firstKode) . ' .'
                        . ' ?pr ck:diselenggarakanOleh ?pt ; ck:statusProdi "Aktif" ; ck:kodeProdi ?kodeProdi ; ck:namaProdi ?namaProdi ; ck:jenjang ?jenjang .'
                        . ' OPTIONAL { ?pr ck:akreditasiProdi ?akrProdi }'
                        . ' OPTIONAL { ?pr ck:fakultas ?fakultas }'
                        . ' } ORDER BY ?jenjang ?namaProdi LIMIT 30';
                    $pRows = $this->fuseki->select($pSparql);
                    if (!empty($pRows)) {
                        $this->lastProdiList = $pRows;
                        $context .= "\nDAFTAR PROGRAM STUDI AKTIF DI KAMPUS INI (DATABASE FUSEKI):\n";
                        foreach ($pRows as $pr) {
                            $context .= "- [" . $pr['jenjang'] . "] " . $pr['namaProdi'] . " | Akreditasi: " . ($pr['akrProdi'] ?? 'Baik') . " | Fakultas: " . ($pr['fakultas'] ?? '-') . " (Kode Prodi: " . $pr['kodeProdi'] . ")\n";
                        }
                    }
                }
            }
        }

        // Jika Pengguna Berada di Halaman Program Studi Tertentu
        if (!empty($prodiKode) && !empty($campusId)) {
            $pSparql = 'PREFIX ck: <https://carikampus.id/ontology/> SELECT ?namaProdi ?jenjang ?akrProdi ?fakultas WHERE {'
                . ' ?pt ck:kodePT ' . F::lit($campusId) . ' .'
                . ' ?pr ck:diselenggarakanOleh ?pt ; ck:kodeProdi ' . F::lit($prodiKode) . ' ;'
                . ' ck:namaProdi ?namaProdi ; ck:jenjang ?jenjang .'
                . ' OPTIONAL { ?pr ck:akreditasiProdi ?akrProdi }'
                . ' OPTIONAL { ?pr ck:fakultas ?fakultas }'
                . ' } LIMIT 1';
            $pRows = $this->fuseki->select($pSparql);
            if (!empty($pRows)) {
                $pr = $pRows[0];
                $this->lastProdi = [
                    'kodeProdi'  => $prodiKode,
                    'namaProdi'  => $pr['namaProdi'] ?? '',
                    'jenjang'    => $pr['jenjang'] ?? '',
                    'akreditasi' => $pr['akrProdi'] ?? 'Baik',
                    'fakultas'   => $pr['fakultas'] ?? '',
                ];
                $context .= "\nINFORMASI PROGRAM STUDI YANG SEDANG DIKUNJUNGI PENGGUNA DI HALAMAN INI:\n";
                $context .= "- Nama Program Studi: " . ($pr['namaProdi'] ?? '-') . "\n";
                $context .= "  Kode Program Studi (Kode Prodi): " . $prodiKode . "\n";
                $context .= "  Jenjang: " . ($pr['jenjang'] ?? '-') . "\n";
                $context .= "  Fakultas: " . ($pr['fakultas'] ?? '-') . "\n";
                $context .= "  Akreditasi Program Studi: " . ($pr['akrProdi'] ?? 'Baik') . "\n";
            }
        }

        // Peringatan Khusus untuk Pertanyaan Out-Of-Scope
        if ($isOutOfScope) {
            $context .= "\nPERINGATAN KHUSUS (OUT-OF-SCOPE):\n";
            $context .= "- Pertanyaan mengenai daya tampung (kuota penerimaan mahasiswa), passing grade, jadwal ujian mandiri, atau dekan TIDAK TERCATAT dalam basis data profil institusi SIMANTIK.\n";
            $context .= "- JANGAN MENGARANG ANGKA KUOTA ATAU JADWAL! Arahkan pengguna ke website resmi kampus atau portal SNPMB.\n";
        }

        $this->places = array_values(array_unique($this->places));
        return $context;
    }

    // Ini fungsi untuk kirim fakta Fuseki dan pesan user ke Google Gemini AI (Semantic RAG)
    private function askGemini(string $userMessage, string $fusekiContext, array $history = []): string
    {
        $apiKey = config('services.gemini.key') ?? env('GEMINI_API_KEY');

        // Validasi keberadaan API key
        if (empty($apiKey)) {
            return "Kunci GEMINI_API_KEY belum dikonfigurasi di file .env.";
        }

        $systemInstruction = <<<TEXT
Kamu adalah jiBot, asisten AI cerdas, ramah, dan berwawasan luas untuk eksplorasi kampus, perkuliahan, dan pendidikan tinggi di Indonesia (khususnya PTN & PTKN).

ATURAN PALING UTAMA (SANGAT KETAT):
- DILARANG MENGGUNAKAN EMOJI SAMA SEKALI. Jangan pernah menggunakan emotikon, emoji gambar wajah, bintang, roket, tangan melambai, atau simbol emoji apa pun (misalnya dilarang memakai emoji apa pun dalam teks).
- Seluruh jawaban harus bersih, rapi, profesional, dan 100% murni teks tanpa ada karakter emoji.

KEPRIBADIAN & GAYA KOMUNIKASI (LAYAKNYA AI MODERN SEPERTI CHATGPT / GEMINI):
1. Ramah, Luwes, dan Natural:
   - Berbicaralah dengan nada yang hangat, suportif, komunikatif, dan enak diajak berdiskusi.
   - Pahami bahasa Indonesia sehari-hari, singkatan, dan istilah perkuliahan anak muda (seperti: "maba", "prodi", "jurusan", "ukt", "passing grade", "snbt", "snbp", "kip-k", "rekomendasi", "bagusan mana", "susah gak").
   - Jika pengguna menyapa ("Halo", "Pagi", "Hai jiBot"), memperkenalkan diri, atau sekadar mengobrol santai, balaslah dengan ramah dan tanyakan apa yang bisa kamu bantu seputar kampus impian mereka.
   - Jika pengguna bertanya hal umum seputar perkuliahan (misalnya: tips memilih jurusan, prospek karier masa depan, perbedaan kurikulum keilmuan, persiapan masuk kuliah), berikan penjelasan yang cerdas, mendalam, dan terstruktur dengan rapi (gunakan poin-poin/markdown).

2. INTEGRASI FAKTA SEMANTIK RESMI (DATA GROUNDING):
   - Jika di bawah terdapat bagian "DATA FAKTA RESMI DARI DATABASE FUSEKI", jadikan data tersebut sebagai rujukan utama fakta (nama kampus, akreditasi, biaya UKT, daftar prodi, fakultas, rektor, lokasi).
   - Sampaikan fakta tersebut secara mengalir dan natural dalam percakapan. Jangan terdengar kaku seperti membaca mentah database.
   - JIKA DATA TERCANTUM DI DATA FAKTA RESMI, JAWAB DENGAN JELAS DAN PASTI. Jangan pernah mengatakan data tidak ada jika sudah tertera di data fakta resmi!
   - Jika data fakta tidak memuat informasi teknis seleksi musiman tahun berjalan (seperti kuota seleksi spesifik atau tanggal pendaftaran mandiri tahun ini), berikan penjelasan umum yang solutif dan arahkan pengguna untuk memeriksa website resmi kampus terkait atau portal SNPMB (snpmb.bppp.kemdikbud.go.id).

3. FORMAT PENYAJIAN:
   - Gunakan format Markdown yang rapi: tebalkan (**bold**) nama kampus, poin penting, dan nominal biaya agar enak dibaca di layar HP maupun laptop.
   - Jangan menyertakan emoji sama sekali.
TEXT;

        // Susun riwayat percakapan untuk Gemini
        $contents = [];
        $recentHistory = array_slice($history, -4);
        foreach ($recentHistory as $chat) {
            if (!empty($chat['role']) && !empty($chat['text'])) {
                $contents[] = [
                    'role' => $chat['role'] === 'user' ? 'user' : 'model',
                    'parts' => [['text' => (string) $chat['text']]]
                ];
            }
        }

        // Masukkan pertanyaan terbaru beserta fakta Fuseki
        $contents[] = [
            'role' => 'user',
            'parts' => [
                ['text' => "{$fusekiContext}\n\nPertanyaan Pengguna:\n\"{$userMessage}\""]
            ]
        ];

        // Daftar model Gemini resmi & stabil (cepat dan cerdas)
        $models = ['gemini-flash-lite-latest', 'gemini-3.1-flash-lite', 'gemini-flash-latest'];

        foreach ($models as $model) {
            try {
                $response = Http::withOptions([
                        'curl' => [
                            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
                            CURLOPT_SSL_VERIFYPEER => false,
                        ],
                    ])
                    ->withHeaders(['Content-Type' => 'application/json'])
                    ->timeout(15)
                    ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}", [
                        'systemInstruction' => [
                            'parts' => [
                                ['text' => $systemInstruction]
                            ]
                        ],
                        'contents' => $contents,
                        'generationConfig' => [
                            'temperature' => 0.7,
                            'maxOutputTokens' => 1500,
                        ]
                    ]);

                if ($response->successful()) {
                    $json = $response->json();
                    $text = $json['candidates'][0]['content']['parts'][0]['text'] ?? null;
                    if (!empty($text)) {
                        return $text;
                    }
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        // Fallback cerdas lokal jika API Google offline
        return $this->formatSmartFallback($userMessage, $this->lastRows);
    }

    // Fallback cerdas spesifik jika server AI sedang offline/gangguan
    private function formatSmartFallback(string $userMessage, array $rows): string
    {
        if (empty($rows)) {
            return 'Halo! Aku jiBot. Mau cari info kampus apa nih? (contoh: akreditasi USU, UKT UGM, kampus di Medan)';
        }

        $r = $rows[0];
        $nama = $r['nama'] ?? 'Kampus';
        $singkatan = !empty($r['singkatan']) ? " ({$r['singkatan']})" : '';
        $msg = Str::lower($userMessage);

        // 1. Jika Pertanyaan Out-Of-Scope (Daya Tampung, Kuota, Passing Grade, Pendaftaran)
        if ($this->lastOutOfScope || str_contains($msg, 'daya tampung') || str_contains($msg, 'kuota') || str_contains($msg, 'passing grade') || str_contains($msg, 'pendaftaran') || str_contains($msg, 'mandiri') || str_contains($msg, 'snbp') || str_contains($msg, 'snbt') || str_contains($msg, 'dekan')) {
            $web = $r['web'] ?? 'portal resmi kampus';
            return "Informasi mengenai daya tampung, kuota seleksi, jadwal pendaftaran, atau dekan untuk {$nama}{$singkatan} tidak tercatat dalam basis data profil institusi kami. Silakan kunjungi website resmi kampus di {$web} atau portal SNPMB (snpmb.bppp.kemdikbud.go.id) untuk informasi seleksi penerimaan terbaru.";
        }

        // 2. Jika Komparasi 2 Kampus
        if (count($rows) >= 2 && (str_contains($msg, 'atau') || str_contains($msg, 'vs') || str_contains($msg, 'bagus') || str_contains($msg, 'murah'))) {
            $r1 = $rows[0];
            $r2 = $rows[1];
            if (str_contains($msg, 'akreditasi') || str_contains($msg, 'bagus')) {
                return "Berdasarkan data resmi, **{$r1['nama']}** terakreditasi **{$r1['akr']}**, sedangkan **{$r2['nama']}** terakreditasi **{$r2['akr']}**.";
            }
            if (str_contains($msg, 'ukt') || str_contains($msg, 'biaya') || str_contains($msg, 'murah') || str_contains($msg, 'mahal')) {
                return "Rentang UKT di **{$r1['nama']}** adalah **{$r1['ukt']}**, sedangkan di **{$r2['nama']}** adalah **{$r2['ukt']}**.";
            }
        }

        // 3. Jika Menanyakan Kode Prodi Spesifik
        if (str_contains($msg, 'kode prodi') || str_contains($msg, 'kode')) {
            if (!empty($this->lastProdi['kodeProdi'])) {
                $namaPr = $this->lastProdi['namaProdi'] ?? 'program studi ini';
                return "Kode program studi untuk **{$namaPr}** adalah **{$this->lastProdi['kodeProdi']}**.";
            }
        }

        // 4. Jika Menanyakan Program Studi Spesifik
        if (!empty($this->lastProdiList)) {
            $res = "Berikut program studi di {$nama}{$singkatan} yang sesuai dengan pencarianmu:\n";
            foreach ($this->lastProdiList as $p) {
                $res .= "• **[{$p['jenjang']}] {$p['namaProdi']}** - Akreditasi: **" . ($p['akrProdi'] ?? 'Baik') . "** (" . ($p['fakultas'] ?? '-') . ")\n";
            }
            return trim($res);
        }

        // 5. Jika Menanyakan Kampus Cabang / PSDKU
        if (str_contains($msg, 'cabang') || str_contains($msg, 'psdku') || str_contains($msg, 'lokasi lain') || str_contains($msg, 'kampus 2') || str_contains($msg, 'kampus ii') || str_contains($msg, 'nias') || str_contains($msg, 'karo')) {
            if (!empty($this->lastBranches)) {
                $res = "Selain kampus utama di {$r['kota']}, {$nama}{$singkatan} memiliki kampus cabang:\n";
                foreach ($this->lastBranches as $b) {
                    $res .= "• **{$b['namaLokasi']}** (" . ($b['jenisLokasi'] ?? 'Cabang') . ") di " . ($b['kota'] ?? '-') . "\n";
                }
                return trim($res);
            }
            return "{$nama}{$singkatan} beralamat di {$r['alamat']}, {$r['kota']}. Belum ada data kampus cabang tambahan yang tercatat.";
        }

        // 6. Jika Menanyakan Tahun Berdiri
        if (str_contains($msg, 'tahun') || str_contains($msg, 'berdiri') || str_contains($msg, 'didirikan') || str_contains($msg, 'kapan')) {
            $berdiri = !empty($r['berdiri']) ? substr($r['berdiri'], 0, 4) : 'Belum tercatat';
            $tgl = !empty($r['berdiri']) ? " (tanggal {$r['berdiri']})" : "";
            return "{$nama}{$singkatan} didirikan pada tahun **{$berdiri}**{$tgl}.";
        }

        // 7. Jika Menanyakan Fakultas / Jumlah Prodi / Daftar Prodi
        if (str_contains($msg, 'fakultas') || str_contains($msg, 'jurusan') || str_contains($msg, 'prodi')) {
            if (!empty($this->lastProdiList) && (str_contains($msg, 'prodi') || str_contains($msg, 'jurusan'))) {
                $res = "Berikut daftar program studi aktif di {$nama}{$singkatan} berdasarkan database resmi:\n";
                foreach ($this->lastProdiList as $p) {
                    $res .= "• **[{$p['jenjang']}] {$p['namaProdi']}** - Akreditasi: **" . ($p['akrProdi'] ?? 'Baik') . "** (" . ($p['fakultas'] ?? '-') . ")\n";
                }
                return trim($res);
            }

            $jmlFak = !empty($r['jmlFakultas']) && (int)$r['jmlFakultas'] > 0 ? $r['jmlFakultas'] : '1';
            $jmlPr = !empty($r['jmlProdi']) && (int)$r['jmlProdi'] > 0 ? " dengan **{$r['jmlProdi']} program studi aktif**" : "";
            
            if (!empty($this->lastFaculties)) {
                $fakList = array_map(fn($f) => "• " . $f['fakultas'], $this->lastFaculties);
                return "{$nama}{$singkatan} memiliki **{$jmlFak} fakultas**{$jmlPr}:\n" . implode("\n", $fakList);
            }
            return "Saat ini {$nama}{$singkatan} memiliki **{$jmlFak} fakultas**{$jmlPr}.";
        }

        // 8. Jika Menanyakan Rektor / Pimpinan
        if (str_contains($msg, 'rektor') || str_contains($msg, 'pimpinan')) {
            $dbp = $this->dbpedia->getInfo($nama);
            $rektor = $dbp['rector'] ?? 'Belum tercatat';
            return "Pimpinan / Rektor dari {$nama}{$singkatan} adalah **{$rektor}**.";
        }

        // 9. Jika Menanyakan Motto
        if (str_contains($msg, 'motto')) {
            $dbp = $this->dbpedia->getInfo($nama);
            $motto = $dbp['motto'] ?? 'Belum tercatat';
            return "Motto resmi dari {$nama}{$singkatan} adalah *\"{$motto}\"*.";
        }

        // 10. Jika Menanyakan UKT / Biaya
        if (str_contains($msg, 'ukt') || str_contains($msg, 'biaya') || str_contains($msg, 'termurah') || str_contains($msg, 'termahal') || str_contains($msg, 'tertinggi') || str_contains($msg, 'terendah') || str_contains($msg, 'rentang') || str_contains($msg, 'kelompok')) {
            $uktMin = !empty($r['biayaMin']) ? 'Rp ' . number_format((float)$r['biayaMin'], 0, ',', '.') : 'Rp 500.000';
            $uktMax = !empty($r['biayaMax']) ? 'Rp ' . number_format((float)$r['biayaMax'], 0, ',', '.') : 'Rp 10.000.000';
            
            if (str_contains($msg, 'termurah') || str_contains($msg, 'terendah')) {
                return "UKT termurah di {$nama}{$singkatan} adalah **{$uktMin}** per semester (Kelompok 1).";
            }
            if (str_contains($msg, 'termahal') || str_contains($msg, 'tertinggi')) {
                return "UKT tertinggi di {$nama}{$singkatan} mencapai **{$uktMax}** per semester.";
            }
            if (str_contains($msg, 'kelompok') && !empty($this->lastUktGroups)) {
                $res = "Rincian kelompok UKT di {$nama}{$singkatan}:\n";
                foreach ($this->lastUktGroups as $u) {
                    $min = !empty($u['minRp']) ? $u['minRp'] : 'Rp 0';
                    $max = !empty($u['maxRp']) ? $u['maxRp'] : $min;
                    $res .= "• **" . ($u['kelompok'] ?? 'Kelompok') . "**: {$min} s/d {$max}\n";
                }
                return trim($res);
            }
            $ukt = $r['ukt'] ?? "{$uktMin} - {$uktMax}";
            return "Rentang UKT di {$nama}{$singkatan} adalah **{$ukt}** per semester, mulai dari **{$uktMin}** hingga **{$uktMax}**.";
        }

        // 11. Jika Menanyakan Alamat / Jalan / Lokasi
        if (str_contains($msg, 'jalan') || str_contains($msg, 'alamat') || str_contains($msg, 'lokasi') || str_contains($msg, 'dimana') || str_contains($msg, 'mana')) {
            $alamat = $r['alamat'] ?? 'Belum ada alamat spesifik';
            $kota = $r['kota'] ?? '';
            $prov = $r['prov'] ?? '';
            return "{$nama}{$singkatan} beralamat di **{$alamat}**, {$kota}, {$prov}.";
        }

        // 12. Jika Menanyakan Akreditasi
        if (str_contains($msg, 'akreditasi') || str_contains($msg, 'akreditasinya')) {
            $akr = $r['akr'] ?? 'Baik';
            return "Status akreditasi resmi untuk {$nama}{$singkatan} saat ini adalah **{$akr}**.";
        }

        // 13. Jika Menanyakan Website
        if (str_contains($msg, 'web') || str_contains($msg, 'website') || str_contains($msg, 'situs')) {
            $web = $r['web'] ?? '-';
            return "Website resmi {$nama}{$singkatan} dapat dikunjungi di {$web}.";
        }

        // 14. Jika Menanyakan Daftar Rekomendasi Jamak
        if (count($rows) > 1) {
            $res = "Berikut perguruan tinggi negeri yang ditemukan sesuai pencarianmu:\n";
            foreach ($rows as $i => $row) {
                $singk = !empty($row['singkatan']) ? " ({$row['singkatan']})" : '';
                $res .= ($i + 1) . ". **{$row['nama']}{$singk}** - Akreditasi: **" . ($row['akr'] ?? 'Baik') . "** di " . ($row['kota'] ?? $row['prov'] ?? '') . "\n";
            }
            return trim($res);
        }

        // Default ringkasan lengkap
        return "Berdasarkan data resmi di database kami:\n"
            . "• **Kampus:** {$nama}{$singkatan}\n"
            . "• **Wilayah:** " . ($r['kota'] ?? '-') . ", " . ($r['prov'] ?? '-') . "\n"
            . "• **Alamat:** " . ($r['alamat'] ?? '-') . "\n"
            . "• **Akreditasi:** " . ($r['akr'] ?? '-') . "\n"
            . "• **Rentang UKT:** " . ($r['ukt'] ?? '-') . "\n"
            . "• **Website:** " . ($r['web'] ?? '-');
    }

    // Hapus seluruh karakter emoji / simbol bergambar agar teks bersih dan profesional
    private function stripEmojis(string $text): string
    {
        // 1. Bersihkan karakter emoji dari berbagai blok Unicode
        $clean = preg_replace('/[\x{1F600}-\x{1F64F}\x{1F300}-\x{1F5FF}\x{1F680}-\x{1F6FF}\x{1F700}-\x{1F77F}\x{1F780}-\x{1F7FF}\x{1F800}-\x{1F8FF}\x{1F900}-\x{1F9FF}\x{1FA00}-\x{1FA6F}\x{1FA70}-\x{1FAFF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}\x{2300}-\x{23FF}\x{2B50}\x{2B55}\x{FE0F}\x{200D}]/u', '', $text);

        // 2. Bersihkan spasi ganda sisa penghapusan emoji
        $clean = preg_replace('/[ \t]{2,}/', ' ', (string) $clean);

        return trim((string) $clean);
    }
}
