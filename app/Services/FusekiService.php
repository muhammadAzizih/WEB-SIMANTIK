<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class FusekiService
{
    public const PREFIXES = <<<'SPARQL'
PREFIX ck:   <https://carikampus.id/ontology/>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
SPARQL;

    
     //Jalankan query SELECT ke Fuseki
    public function select(string $query): array
    {
        if (!config('services.fuseki.endpoint')) {
            throw new RuntimeException('services.fuseki.endpoint kosong: cek config/services.php dan .env');
        }

        // GET, karena endpoint Fuseki di dataset ini menolak POST (405)
        $response = Http::withHeaders(['Accept' => 'application/sparql-results+json'])
            ->timeout(20)
            ->get(config('services.fuseki.endpoint'), [
                'query' => self::PREFIXES . "\n" . $query,
            ]);

        if ($response->failed()) {
            throw new RuntimeException(
                'Fuseki error ' . $response->status()
                . ' [GET ' . config('services.fuseki.endpoint') . '] '
                . mb_substr(strip_tags($response->body()), 0, 200)
            );
        }

        return collect($response->json('results.bindings', []))
            ->map(fn ($row) => collect($row)->map(fn ($cell) => $cell['value'])->all())
            ->all();
    }

    public static function lit(string $value): string
    {
        $value = str_replace(['\\', '"', "\n", "\r", "\t"], ['\\\\', '\\"', ' ', ' ', ' '], $value);

        return '"' . $value . '"';
    }
}