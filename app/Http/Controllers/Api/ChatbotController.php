<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FusekiService as F;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ChatbotController extends Controller
{
    private array $places = [];
    private array $lastRows = [];

    // Inisialisasi service Fuseki
    public function __construct(private F $fuseki) {}

    // Endpoint utama POST /api/chatbot
    public function reply(Request $request)
    {
      $message = trim((string) $request->input('message', ''));
      $history = (array) $request->input('history', []); 

        // Cek jika pesan kosong
        if ($message === '') {
            return response()->json([
                'reply' => 'Halo! Ada yang bisa aku bantu seputar kampus impianmu?',
                'places' => []
            ]);
        }

        try {
            // 1. Ambil data fakta dari Fuseki via SPARQL
            $fusekiContext = $this->retrieveFusekiContext($message,$history);

            // 2. Olah pesan dan fakta Fuseki 
            $reply = $this->askGemini($message, $fusekiContext,$history);
        } catch (\Throwable $e) {
            report($e);
            // Fallback cerdas spesifik sesuai pertanyaan jika AI offline/error
            $reply = $this->formatSmartFallback($message, $this->lastRows);
        }

        // Return respons JSON ke frontend
        return response()->json([
            'reply' => $reply,
            'places' => $this->places,
        ]);
    }

    // Ambil data kampus dari Fuseki secara dinamis
    private function retrieveFusekiContext(string $query, array $history = []): string
    {
        // Bersihkan tanda baca dan kata umum
        $cleaned = trim(preg_replace('/[?!.,]+/u', ' ', Str::lower($query)));
        $words = preg_split('/\s+/u', $cleaned, -1, PREG_SPLIT_NO_EMPTY);
        $stopWords = [
            'di', 'ke', 'dari', 'yang', 'dan', 'kampus', 'ptn', 'universitas', 'apa', 'ada', 'dong', 'ya', 'kak', 'bang', 'info',
            'mana', 'dimana', 'berapa', 'gimana', 'bagaimana', 'alamat', 'jalan', 'jalannya', 'ukt', 'uktnya', 'biaya', 'biayanya',
            'akreditasi', 'akreditasinya', 'web', 'website', 'websitenya', 'situs', 'nya', 'kasi', 'kasih', 'rekomendasi', 'bagus',
            'mau', 'kuliah', 'jurusan', 'ak', 'aku', 'saya', 'kamu', 'bisa', 'gak', 'nggak', 'tidak', 'termurah', 'termurahnya',
            'termahal', 'termahalnya', 'terendah', 'tertinggi'
        ];
        $keywords = array_filter($words, fn($w) => mb_strlen($w) > 1 && !in_array($w, $stopWords, true));

        // Jika tidak ada kata kunci kampus spesifik (misal pengguna hanya tanya "berapa uktnya?" atau "jalannya di mana?")
        if (empty($keywords)) {
            if (!empty($history)) {
                for ($i = count($history) - 1; $i >= 0; $i--) {
                    if (($history[$i]['role'] ?? '') === 'user' && !empty($history[$i]['text'])) {
                        $prevContext = $this->retrieveFusekiContext($history[$i]['text'], []);
                        if ($prevContext !== 'Tidak ada kata kunci spesifik.' && !str_starts_with($prevContext, 'Tidak ada data')) {
                            return $prevContext;
                        }
                    }
                }
            }
            return 'Tidak ada data spesifik kampus yang cocok di dataset Fuseki untuk pertanyaan ini.';
        }

        $terms = $keywords;

        // Susun pola regex untuk SPARQL
        $regexPattern = implode('|', array_map(fn($w) => preg_quote($w, '/'), $terms));
        if ($regexPattern === '') {
            return 'Tidak ada kata kunci spesifik.';
        }

        // Query SPARQL multi-parameter ke Fuseki
        $sparql = <<<SPARQL
        SELECT DISTINCT ?kode ?nama ?singkatan ?kota ?prov ?alamat ?akr ?web ?ukt ?biayaMin ?biayaMax
        WHERE {
          ?pt a ck:Institusi ;
              ck:namaInstitusi ?nama .
          OPTIONAL { ?pt ck:kodePT ?kode }
          OPTIONAL { ?pt ck:singkatan ?singkatan }
          OPTIONAL { ?pt ck:kota ?kota }
          OPTIONAL { ?pt ck:provinsi ?prov }
          OPTIONAL { ?pt ck:alamatLengkap ?alamat }
          OPTIONAL { ?pt ck:statusAkreditasi ?akr }
          OPTIONAL { ?pt ck:websiteResmi ?web }
          OPTIONAL { ?pt ck:rentangUKT ?ukt }
          OPTIONAL { ?pt ck:biayaKuliahMin ?biayaMin }
          OPTIONAL { ?pt ck:biayaKuliahMax ?biayaMax }
          FILTER(
            REGEX(?nama, "\\b($regexPattern)\\b", "i") ||
            REGEX(STR(COALESCE(?singkatan, "")), "^($regexPattern)$", "i") ||
            REGEX(STR(COALESCE(?kota, "")), "\\b($regexPattern)\\b", "i") ||
            EXISTS {
              ?pr ck:diselenggarakanOleh ?pt ;
                  ck:namaProdi ?np .
              FILTER(REGEX(?np, "\\b($regexPattern)\\b", "i"))
            }
          )
        }
        LIMIT 5
        SPARQL;

        try {
            $rows = $this->fuseki->select($sparql);
            if (empty($rows)) {
                // Kondisi nanya lokasi/alamat tapi nama kampus tidak disebutkan di pertanyaan saat ini
                if (!empty($history)) {
                    for ($i = count($history) - 1; $i >= 0; $i--) {
                        if (($history[$i]['role'] ?? '') === 'user') {
                            return $this->retrieveFusekiContext($history[$i]['text'] ?? '', []);
                        }
                    }
                }
                return 'Tidak ada data spesifik kampus yang cocok di dataset Fuseki untuk pertanyaan ini.';
            }

            // Simpan baris data kampus untuk fallback cerdas
            $this->lastRows = $rows;

            // Format data fakta Fuseki 
            $context = "DATA FAKTA RESMI DARI DATABASE FUSEKI (carikampus.ttl):\n";
            foreach ($rows as $r) {
                if (!empty($r['kode'])) {
                    $this->places[] = $r['kode'];
                }
                $uktMin = !empty($r['biayaMin']) ? 'Rp ' . number_format((float)$r['biayaMin'], 0, ',', '.') : 'Rp 500.000';
                $context .= "- Kampus: " . ($r['nama'] ?? '-') . " (" . ($r['singkatan'] ?? '-') . ")\n";
                $context .= "  Wilayah: " . ($r['kota'] ?? '-') . ", " . ($r['prov'] ?? '-') . "\n";
                $context .= "  Alamat Lengkap / Jalan: " . ($r['alamat'] ?? 'Belum ada data') . "\n";
                $context .= "  Akreditasi: " . ($r['akr'] ?? 'Belum ada data') . "\n";
                $context .= "  UKT Termurah / Terendah: " . $uktMin . "\n";
                $context .= "  Rentang UKT: " . ($r['ukt'] ?? 'Belum ada data') . "\n";
                $context .= "  Website: " . ($r['web'] ?? '-') . "\n";
            }
            $this->places = array_values(array_unique($this->places));
            return $context;
        } catch (\Throwable) {
            return 'Gagal mengambil data dari Fuseki.';
        }
    }

    // Panggil Google Gemini API untuk menyusun jawaban ramah
    private function askGemini(string $userMessage, string $fusekiContext, array $history = []): string
    {
        $apiKey = config('services.gemini.key') ?? env('GEMINI_API_KEY');

        // Validasi keberadaan API key
        if (empty($apiKey)) {
            return "Kunci GEMINI_API_KEY belum dikonfigurasi di file .env.";
        }

        // Instruksi kepribadian bot (spesifik sesuai pertanyaan, no yapping)
        $systemInstruction = <<<TEXT
Kamu adalah jiBot, asisten pencarian kampus PTN Impian.

PANDUAN MENJAWAB (SANGAT PENTING):
1. JAWAB SPESIFIK SESUAI APA YANG DITANYAKAN:
   - Jika pengguna bertanya UKT termurah (misal: "berapa ukt termurahnya?", "ukt termurahnya berapa?"), jawab HANYA nominal UKT termurahnya (contoh: "UKT termurah di USU adalah Rp 500.000 per semester (Kelompok 1)."). JANGAN sebutkan alamat, website, atau akreditasi jika tidak diminta!
   - Jika pengguna bertanya jalan/alamat (misal: "jalannya di mana?"), jawab HANYA nama jalan/lokasinya. JANGAN sebutkan biaya UKT atau website.
   - Jika pengguna bertanya akreditasi, jawab HANYA status akreditasinya.
   - Jika pengguna bertanya website, jawab HANYA tautan websitenya.
   - HANYA berikan rangkuman lengkap jika pengguna meminta info umum/profil lengkap (misal: "ceritakan tentang USU", "info lengkap USU").
2. TETAP NYAMBUNG: Jika pengguna menggunakan kata ganti "-nya" (contoh: "berapa uktnya?", "jalannya di mana?"), otomatis kaitkan dengan kampus yang sedang dibahas di riwayat obrolan sebelumnya.
3. REKOMENDASI & PERTANYAAN UMUM: Jika pengguna meminta rekomendasi jurusan atau kampus (misal: "kuliah IT di jawa yang bagus"), berikan rekomendasi kampus PTN terkemuka beserta alasan singkatnya secara ramah, terstruktur, dan mudah dibaca.
4. JANGAN BASA-BASI (NO YAPPING). Langsung jawab inti pertanyaannya dengan bahasa Indonesia santai, ramah, dan tidak bertele-tele.
TEXT;

        // Susun riwayat percakapan untuk Gemini
        $contents = [];
        $recentHistory = array_slice($history, -4); // Ambil 4 percakapan terakhir
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

        // Daftar model stabil dengan kuota tinggi (1.500 RPD pada Free Tier)
        $models = ['gemini-flash-lite-latest', 'gemini-3.1-flash-lite'];

        foreach ($models as $model) {
            try {
                $response = Http::withOptions([
                        'curl' => [
                            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
                            CURLOPT_SSL_VERIFYPEER => false,
                        ],
                    ])
                    ->withHeaders(['Content-Type' => 'application/json'])
                    ->timeout(20)
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
                            'thinkingConfig' => [
                                'thinkingBudget' => 0,
                            ],
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
                // Lanjut coba model cadangan berikutnya
                continue;
            }
        }

        // Jika semua pemanggilan API gagal, gunakan fallback cerdas lokal
        return $this->formatSmartFallback($userMessage, $this->lastRows);
    }

    // Fallback cerdas spesifik jika server AI sedang gangguan
    private function formatSmartFallback(string $userMessage, array $rows): string
    {
        if (empty($rows)) {
            return 'Halo! Aku jiBot. Mau cari info kampus apa nih? (contoh: akreditasi USU, UKT UGM, kampus di Medan)';
        }

        $r = $rows[0];
        $nama = $r['nama'] ?? 'Kampus';
        $singkatan = !empty($r['singkatan']) ? " ({$r['singkatan']})" : '';
        $msg = Str::lower($userMessage);

        // Jika menanyakan UKT / Biaya
        if (str_contains($msg, 'ukt') || str_contains($msg, 'biaya') || str_contains($msg, 'termurah') || str_contains($msg, 'termahal')) {
            $uktMin = !empty($r['biayaMin']) ? 'Rp ' . number_format((float)$r['biayaMin'], 0, ',', '.') : 'Rp 500.000';
            if (str_contains($msg, 'termurah') || str_contains($msg, 'terendah')) {
                return "UKT termurah di {$nama}{$singkatan} adalah **{$uktMin}** per semester (Kelompok 1).";
            }
            if (str_contains($msg, 'termahal') || str_contains($msg, 'tertinggi')) {
                $uktMax = !empty($r['biayaMax']) ? 'Rp ' . number_format((float)$r['biayaMax'], 0, ',', '.') : 'Rp 10.000.000';
                return "UKT tertinggi di {$nama}{$singkatan} mencapai **{$uktMax}** per semester.";
            }
            $ukt = $r['ukt'] ?? 'Rp 500.000 - Rp 10.000.000';
            return "Rentang UKT di {$nama}{$singkatan} adalah **{$ukt}**, dengan UKT termurah mulai dari **{$uktMin}** per semester.";
        }

        // Jika menanyakan Alamat / Jalan / Lokasi
        if (str_contains($msg, 'jalan') || str_contains($msg, 'alamat') || str_contains($msg, 'lokasi') || str_contains($msg, 'dimana') || str_contains($msg, 'mana')) {
            $alamat = $r['alamat'] ?? 'Belum ada alamat spesifik';
            $kota = $r['kota'] ?? '';
            $prov = $r['prov'] ?? '';
            return "{$nama}{$singkatan} beralamat di **{$alamat}**, {$kota}, {$prov}.";
        }

        // Jika menanyakan Akreditasi
        if (str_contains($msg, 'akreditasi') || str_contains($msg, 'akreditasinya')) {
            $akr = $r['akr'] ?? 'Belum terakreditasi';
            return "Status akreditasi resmi untuk {$nama}{$singkatan} saat ini adalah **{$akr}**.";
        }

        // Jika menanyakan Website
        if (str_contains($msg, 'web') || str_contains($msg, 'website') || str_contains($msg, 'situs')) {
            $web = $r['web'] ?? '-';
            return "Website resmi {$nama}{$singkatan} dapat dikunjungi di {$web}.";
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
}