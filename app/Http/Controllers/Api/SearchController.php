<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FusekiService;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __construct(private FusekiService $fuseki) {}

    // GET /api/search?q=medan
    public function search(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        if ($q === '') {
            return response()->json(['data' => [], 'total' => 0]);
        }

        $p = FusekiService::lit($this->wordPattern($q, false));

        $sparql = <<<SPARQL
        SELECT DISTINCT ?kodePT ?nama ?singkatan ?kota ?provinsi
        WHERE {
          ?pt a ck:Institusi ;
              ck:kodePT ?kodePT ;
              ck:namaInstitusi ?nama .
          OPTIONAL { ?pt ck:singkatan ?singkatan }
          OPTIONAL { ?pt ck:kota ?kota }
          OPTIONAL { ?pt ck:provinsi ?provinsi }
          FILTER(
            REGEX(?nama, $p, "i") ||
            REGEX(STR(COALESCE(?singkatan, "")), $p, "i") ||
            REGEX(STR(COALESCE(?kota, "")), $p, "i") ||
            REGEX(STR(COALESCE(?provinsi, "")), $p, "i")
          )
        }
        ORDER BY ?nama
        LIMIT 20
        SPARQL;

        $data = collect($this->fuseki->select($sparql))
            ->map(fn ($row) => [
                'kodePT'    => $row['kodePT'] ?? null,
                'nama'      => $row['nama'] ?? null,
                'singkatan' => $row['singkatan'] ?? null,
                'kota'      => $row['kota'] ?? null,
                'provinsi'  => $row['provinsi'] ?? null,
            ])
            ->values();

        return response()->json(['data' => $data, 'total' => $data->count()]);
    }

    // kata pengguna pola regex 
    private function wordPattern(string $q, bool $wholeWord = true): string
    {
        $q = mb_substr(trim(preg_replace('/\s+/', ' ', $q)), 0, 60);
        
        $escaped = preg_replace('/[\\\\|.?*+(){}\[\]^$\-]/', '\\\\$0', $q);

        return '\b' . $escaped . ($wholeWord ? '\b' : '');
    }
}