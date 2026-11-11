<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FusekiService as F;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ChatbotController extends Controller
{
    // kata yang dibuang untuk mengambil nama kampus dari kalimat
    private const STOP = [
        'akreditasi', 'ukt', 'biaya', 'kuliah', 'website', 'situs', 'web', 'alamat',
        'kontak', 'telepon', 'telp', 'email', 'berapa', 'jumlah', 'daftar', 'prodi',
        'program', 'studi', 'apa', 'saja', 'di', 'dari', 'untuk', 'milik', 'yang',
        'nya', 'ada', 'kampus', 'ptn', 'tolong', 'dong', 'ya', 'info', 'informasi',
    ];

    public function __construct(private F $fuseki) {}

    // POST /api/chatbot   { "message": "akreditasi usu" }
    public function reply(Request $request)
    {
        $raw = mb_substr(trim((string) $request->input('message', '')), 0, 200);
        $msg = Str::lower(preg_replace('/[?!.,]+/u', ' ', $raw));
        $msg = trim(preg_replace('/\s+/u', ' ', $msg));

        if ($msg === '') {
            return response()->json(['reply' => 'Ketik pertanyaanmu dulu ya.']);
        }

        try {
            $reply = $this->answer($msg);
        } catch (\Throwable $e) {
            report($e);
            $reply = 'Maaf, data kampus belum bisa diakses sekarang. Coba lagi sebentar ya.';
        }

        return response()->json(['reply' => $reply]);
    }

    private function answer(string $msg): string
    {
        if (preg_match('/^(halo|hai|hi|hello|hey|assalamualaikum|selamat (pagi|siang|sore|malam))\b/u', $msg)) {
            return "Halo! Aku jiBot.\n" . $this->contoh();
        }
        if (preg_match('/\b(terima kasih|makasih|thanks)\b/u', $msg)) {
            return 'Sama-sama! Kalau ada yang mau ditanyakan lagi, silakan.';
        }
        if (preg_match('/\b(bantuan|help|menu|bisa apa)\b/u', $msg)) {
            return $this->contoh();
        }
        if (preg_match('/\b(ukt|biaya kuliah|biaya)\b/u', $msg)) {
            return $this->ukt($msg);
        }
        if (preg_match('/\b(?:kampus|ptn|universitas)\b.*\b(?:punya|ada|memiliki|dengan)\b.*\bprodi\s+(.+)$/u', $msg, $m)) {
            return $this->kampusDenganProdi($m[1]);
        }
        if (preg_match('/\b(berapa|jumlah)\b.*\bprodi\b/u', $msg)) {
            return $this->prodi($msg, false);
        }
        if (preg_match('/\b(daftar prodi|prodi apa|program studi)\b/u', $msg)) {
            return $this->prodi($msg, true);
        }
        if (preg_match('/\bakreditasi\b/u', $msg)) {
            return $this->akreditasi($msg);
        }
        if (preg_match('/\b(website|situs|web|alamat|kontak|telepon|telp|email)\b/u', $msg)) {
            return $this->kontak($msg);
        }
        if (preg_match('/\b(?:kampus|ptn|universitas)\b.*?\bdi\s+(.+)$/u', $msg, $m)) {
            return $this->kampusDi($m[1]);
        }

        // pesan pendek yang hanya berisi nama kampus, misal "usu"
        if (count(explode(' ', $msg)) <= 5 && ($c = $this->findCampus($msg))) {
            return $this->profil($c);
        }

        return "Maaf, aku belum paham maksudmu.\n" . $this->contoh();
    }

    /* ---------------- Intent ---------------- */

    private function kampusDi(string $place): string
    {
        $place = $this->cleanTerm($place, false);
        if ($place === '') return 'Kamu mau cari kampus di mana?';

        $p = F::lit('\b' . $this->esc($place));

        $sparql = <<<SPARQL
        SELECT DISTINCT ?nama
        WHERE {
          ?pt a ck:Institusi ; ck:namaInstitusi ?nama .
          OPTIONAL { ?pt ck:kota ?kota }
          OPTIONAL { ?pt ck:provinsi ?provinsi }
          FILTER(
            REGEX(STR(COALESCE(?kota, "")), $p, "i") ||
            REGEX(STR(COALESCE(?provinsi, "")), $p, "i")
          )
        }
        ORDER BY ?nama
        LIMIT 10
        SPARQL;

        $names = collect($this->fuseki->select($sparql))->pluck('nama')->filter()->values();

        if ($names->isEmpty()) return "Aku tidak menemukan kampus di \"{$place}\".";

        $tambah = $names->count() >= 10 ? ' (10 pertama)' : '';
        return "Kampus di {$place}{$tambah}:\n- " . $names->implode("\n- ");
    }

    private function kampusDenganProdi(string $prodi): string
    {
        $prodi = trim($prodi);
        if ($prodi === '') return 'Prodi apa yang kamu cari?';

        $p = F::lit('\b' . $this->esc($prodi));

        $sparql = <<<SPARQL
        SELECT DISTINCT ?nama
        WHERE {
          ?prodi ck:namaProdi ?np ; ck:diselenggarakanOleh ?pt .
          ?pt ck:namaInstitusi ?nama .
          FILTER(REGEX(?np, $p, "i"))
        }
        ORDER BY ?nama
        LIMIT 10
        SPARQL;

        $names = collect($this->fuseki->select($sparql))->pluck('nama')->filter()->values();

        if ($names->isEmpty()) return "Aku tidak menemukan kampus dengan prodi \"{$prodi}\".";

        $tambah = $names->count() >= 10 ? ' (10 pertama)' : '';
        return "Kampus yang punya prodi {$prodi}{$tambah}:\n- " . $names->implode("\n- ");
    }

    private function akreditasi(string $msg): string
    {
        $c = $this->findCampus($msg);
        if (!$c) return 'Kampus mana yang kamu maksud? Contoh: "akreditasi usu".';

        if (empty($c['akr'])) {
            return "Data akreditasi {$c['nama']} belum tersedia di dataset kami.";
        }
        $lembaga = !empty($c['lembaga']) ? " (oleh {$c['lembaga']})" : '';
        return "Akreditasi {$c['nama']}: {$c['akr']}{$lembaga}.";
    }

    private function kontak(string $msg): string
    {
        $c = $this->findCampus($msg);
        if (!$c) return 'Kampus mana yang kamu maksud? Contoh: "website ui".';

        $baris = [];
        if (!empty($c['web']))    $baris[] = "Website: {$c['web']}";
        if (!empty($c['alamat'])) $baris[] = "Alamat: {$c['alamat']}";
        if (!empty($c['email']))  $baris[] = "Email: {$c['email']}";
        if (!empty($c['telp']))   $baris[] = "Telepon: {$c['telp']}";

        if (!$baris) return "Data kontak {$c['nama']} belum tersedia.";
        return "{$c['nama']}\n" . implode("\n", $baris);
    }

    private function prodi(string $msg, bool $detail): string
    {
        $c = $this->findCampus($msg);
        if (!$c) return 'Kampus mana yang kamu maksud? Contoh: "daftar prodi usu".';

        $sparql = <<<SPARQL
        SELECT ?fak (COUNT(DISTINCT ?prodi) AS ?n)
        WHERE {
          ?prodi ck:diselenggarakanOleh <{$c['pt']}> ; ck:namaProdi ?np .
          OPTIONAL { ?prodi ck:fakultas ?fak }
          OPTIONAL { ?prodi ck:statusProdi ?st }
          FILTER(!BOUND(?st) || ?st = "Aktif")
        }
        GROUP BY ?fak
        ORDER BY DESC(?n)
        SPARQL;

        $rows  = collect($this->fuseki->select($sparql));
        $total = $rows->sum(fn ($r) => (int) ($r['n'] ?? 0));

        if ($total === 0) return "Data program studi {$c['nama']} belum tersedia.";

        $fakultas = $rows->filter(fn ($r) => !empty($r['fak']))->values();
        $kepala   = "{$c['nama']} memiliki {$total} program studi aktif";
        $kepala  .= $fakultas->count() ? " di {$fakultas->count()} fakultas." : '.';

        if (!$detail || $fakultas->isEmpty()) return $kepala;

        $daftar = $fakultas->take(6)
            ->map(fn ($r) => "- {$r['fak']}: {$r['n']} prodi")->implode("\n");
        $sisa = $fakultas->count() > 6 ? "\n...dan " . ($fakultas->count() - 6) . ' fakultas lain.' : '';

        return "{$kepala}\n{$daftar}{$sisa}";
    }

    private function ukt(string $msg): string
    {
        $c    = $this->findCampus($msg);
        $nama = $c['nama'] ?? 'kampus ini';
        $web  = !empty($c['web']) ? " Cek di website resminya: {$c['web']}" : '';

        return "Data UKT {$nama} belum tersedia di dataset kami.{$web}";
    }

    private function profil(array $c): string
    {
        $baris = [$c['nama'] . (!empty($c['singkatan']) ? " ({$c['singkatan']})" : '')];
        $lokasi = collect([$c['kota'] ?? null, $c['prov'] ?? null])->filter()->implode(', ');
        if ($lokasi)            $baris[] = "Lokasi: {$lokasi}";
        if (!empty($c['akr']))  $baris[] = "Akreditasi: {$c['akr']}";
        if (!empty($c['web']))  $baris[] = "Website: {$c['web']}";
        $baris[] = 'Tanya lebih lanjut, misalnya "daftar prodi ' . ($c['singkatan'] ?? $c['nama']) . '".';

        return implode("\n", $baris);
    }

    private function contoh(): string
    {
        return "Kamu bisa tanya, misalnya:\n"
            . "- kampus di Medan\n"
            . "- akreditasi USU\n"
            . "- daftar prodi UGM\n"
            . "- kampus yang punya prodi informatika\n"
            . "- website UI";
    }

    /* ---------------- Pencarian kampus dari kalimat ---------------- */

    private function findCampus(string $text): ?array
    {
        $term = $this->cleanTerm($text);
        if ($term === '') return null;

        $exact = F::lit($term);
        $word  = F::lit('\b' . $this->esc($term));

        $sparql = <<<SPARQL
        SELECT ?pt ?nama ?singkatan ?akr ?lembaga ?web ?alamat ?kota ?prov ?email ?telp
               (IF(LCASE(STR(COALESCE(?singkatan, ""))) = $exact, 0, 1) AS ?rank)
        WHERE {
          ?pt a ck:Institusi ; ck:namaInstitusi ?nama .
          OPTIONAL { ?pt ck:singkatan ?singkatan }
          OPTIONAL { ?pt ck:statusAkreditasi ?akr }
          OPTIONAL { ?pt ck:lembagaAkreditasi ?lembaga }
          OPTIONAL { ?pt ck:websiteResmi ?web }
          OPTIONAL { ?pt ck:alamatLengkap ?alamat }
          OPTIONAL { ?pt ck:kota ?kota }
          OPTIONAL { ?pt ck:provinsi ?prov }
          OPTIONAL { ?pt ck:email ?email }
          OPTIONAL { ?pt ck:noTelepon ?telp }
          FILTER(LCASE(STR(COALESCE(?singkatan, ""))) = $exact || REGEX(?nama, $word, "i"))
        }
        ORDER BY ?rank ?nama
        LIMIT 1
        SPARQL;

        $rows = $this->fuseki->select($sparql);
        return $rows[0] ?? null;
    }

    // buang kata perintah, sisakan nama kampus
    private function cleanTerm(string $text, bool $stripCampusWords = true): string
    {
        $words = preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY);
        $words = array_filter($words, fn ($w) => !in_array($w, self::STOP, true));
        return trim(implode(' ', $words));
    }

    private function esc(string $s): string
    {
        return preg_replace('/[\\\\|.?*+(){}\[\]^$\-]/', '\\\\$0', $s);
    }
}