<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DbpediaService
{
    private const ENDPOINT = 'https://dbpedia.org/sparql';

    //buat ambil data dari DBpedia (eksternal)
    public function getInfo(string $namaKampus): ?array
    {
        $cleanNama = trim($namaKampus);
        if (empty($cleanNama)) {
            return null;
        }

        $cacheKey = 'dbpedia:univ:' . md5(strtolower($cleanNama));

        return Cache::remember($cacheKey, 86400, function () use ($cleanNama) {
            return $this->querySparql($cleanNama);
        });
    }

    private function querySparql(string $namaKampus): ?array
    {
        $escaped = addslashes($namaKampus);

        $sparql = <<<SPARQL
PREFIX dbo: <http://dbpedia.org/ontology/>
PREFIX dbp: <http://dbpedia.org/property/>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX foaf: <http://xmlns.com/foaf/0.1/>

SELECT ?univ ?label ?motto ?rector ?students ?wikiPage WHERE {
  ?univ a dbo:University ;
        rdfs:label ?label .
  FILTER(
    LCASE(STR(?label)) = LCASE("$escaped") ||
    CONTAINS(LCASE(STR(?label)), LCASE("$escaped"))
  )
  OPTIONAL { ?univ dbo:motto ?mottoDb . }
  OPTIONAL { ?univ dbp:motto ?mottoProp . }
  BIND(COALESCE(?mottoDb, ?mottoProp) AS ?motto)

  OPTIONAL { ?univ dbo:rector ?rectorUri . }
  OPTIONAL { ?univ dbp:rector ?rectorProp . }
  BIND(COALESCE(
    IF(isURI(?rectorUri), REPLACE(STR(?rectorUri), "^.*/", ""), STR(?rectorUri)),
    STR(?rectorProp)
  ) AS ?rector)

  OPTIONAL { ?univ dbo:numberOfStudents ?students . }
  OPTIONAL { ?univ foaf:isPrimaryTopicOf ?wikiPage . }
}
LIMIT 1
SPARQL;

        try {
            $response = Http::withHeaders([
                'Accept'     => 'application/sparql-results+json',
                'User-Agent' => 'CariKampusSemanticApp/1.0 (Student Project)',
            ])->timeout(4)->get(self::ENDPOINT, [
                'query'  => $sparql,
                'format' => 'json',
            ]);

            if ($response->successful()) {
                $bindings = $response->json('results.bindings', []);
                if (!empty($bindings)) {
                    $b = $bindings[0];
                    $rectorVal = $b['rector']['value'] ?? null;
                    if ($rectorVal) {
                        $rectorVal = str_replace('_', ' ', $rectorVal);
                    }

                    return [
                        'uri'      => $b['univ']['value'] ?? null,
                        'label'    => $b['label']['value'] ?? null,
                        'motto'    => $b['motto']['value'] ?? null,
                        'rector'   => $rectorVal,
                        'students' => $b['students']['value'] ?? null,
                        'wiki'     => $b['wikiPage']['value'] ?? null,
                    ];
                }
            }
        } catch (\Throwable $e) {
            Log::warning('DBpedia SPARQL query failed for ' . $namaKampus . ': ' . $e->getMessage());
        }

        return null;
    }
}

