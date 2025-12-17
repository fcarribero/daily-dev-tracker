<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class DailySummaryGenerator
{
    /** @var Client */
    protected $http;

    public function __construct($http = null)
    {
        $this->http = $http ?: new Client(array(
            'timeout' => (float) config('services.openai.timeout', 20),
        ));
    }

    /**
     * Genera un resumen estilo "daily" usando OpenAI. Si falla, devuelve null.
     *
     * @param array $entries Lista de strings (entradas) para el día.
     * @param string $date Fecha en formato YYYY-MM-DD.
     * @return string|null Texto generado o null si no se pudo generar con IA.
     */
    public function generate(array $entries, string $date): ?string
    {
        $enabled = (bool) config('services.openai.enabled', false);
        $apiKey = (string) config('services.openai.api_key');
        if (!$enabled || empty($apiKey)) {
            return null; // IA deshabilitada o sin API key
        }

        $baseUrl = rtrim((string) config('services.openai.base_url', 'https://api.openai.com/v1'), '/');
        $model = (string) config('services.openai.model', 'gpt-4o-mini');

        // Limitar tamaño de entrada (simple recorte si hay demasiadas líneas)
        $entries = array_values(array_filter(array_map('trim', $entries), function ($s) { return $s !== ''; }));
        if (count($entries) > 50) {
            $entries = array_slice($entries, 0, 50);
            $entries[] = '...';
        }

        $system = 'Eres un asistente que prepara un breve resumen de daily standup en español, tono profesional y conciso.';
        $instructions = "Fecha: {$date}.\nCon las siguientes entradas de trabajo, redacta un breve resumen listo para decir en el daily.\n- Sé concreto.\n- Agrupa tareas similares.\n- Usa viñetas cortas (•) o un párrafo breve.\n- No inventes información.";

        $content = $instructions . "\n\nEntradas:\n" . implode("\n", array_map(function ($i, $t) { return ($i + 1) . ") " . $t; }, array_keys($entries), $entries));

        try {
            $response = $this->http->post($baseUrl . '/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $model,
                    'temperature' => 0.3,
                    'messages' => [
                        ['role' => 'system', 'content' => $system],
                        ['role' => 'user', 'content' => $content],
                    ],
                ],
            ]);

            $data = json_decode((string) $response->getBody(), true);
            $text = $data['choices'][0]['message']['content'] ?? null;
            if (!is_string($text) || trim($text) === '') {
                return null;
            }
            return trim($text);
        } catch (\Throwable $e) {
            Log::warning('OpenAI summary failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Genera un resumen con dos secciones: Hoy (lo que haré) y Ayer (lo que hice).
     * Si falla la IA, devuelve null para permitir fallback en el controlador.
     *
     * @param array $todayEntries Entradas para HOY (plan de hoy).
     * @param array $yesterdayEntries Entradas para AYER (lo realizado).
     * @param string $todayDate Fecha de hoy (YYYY-MM-DD).
     * @param string $yesterdayDate Fecha de hoy (YYYY-MM-DD).
     * @return string|null
     */
    public function generateHoyAyer(array $todayEntries, array $yesterdayEntries, string $todayDate, string $yesterdayDate): ?string
    {
        $enabled = (bool) config('services.openai.enabled', false);
        $apiKey = (string) config('services.openai.api_key');
        if (!$enabled || empty($apiKey)) {
            return null;
        }

        $baseUrl = rtrim((string) config('services.openai.base_url', 'https://api.openai.com/v1'), '/');
        $model = (string) config('services.openai.model', 'gpt-4o-mini');

        // Normalizar y limitar
        $todayEntries = array_values(array_filter(array_map('trim', $todayEntries), function ($s) { return $s !== ''; }));
        $yesterdayEntries = array_values(array_filter(array_map('trim', $yesterdayEntries), function ($s) { return $s !== ''; }));
        $limitList = function (array $list) {
            if (count($list) > 50) {
                $list = array_slice($list, 0, 50);
                $list[] = '...';
            }
            return $list;
        };
        $todayEntries = $limitList($todayEntries);
        $yesterdayEntries = $limitList($yesterdayEntries);

        $system = 'Eres un asistente que redacta un breve update de daily standup en español, con dos secciones: Ayer (lo realizado) y Hoy (lo planificado). Tono profesional, conciso y sin inventar.';
        $instructions = "Con las listas provistas, redacta:
1) Ayer ({$yesterdayDate}): breve resumen de lo realizado (a partir de la lista AYER).
2) Hoy ({$todayDate}): breve plan para el día (a partir de la lista HOY).
- Sé concreto.
- Agrupa tareas similares.
- Usa viñetas cortas (•) o dos pequeños párrafos.
- No inventes información ni agregues estimaciones.";

        $formatList = function (array $items) {
            if (empty($items)) {
                return "(sin entradas)";
            }
            $lines = array();
            foreach ($items as $i => $t) {
                $lines[] = ($i + 1) . ") " . $t;
            }
            return implode("\n", $lines);
        };

        $content = $instructions
            . "\n\nLista AYER:\n" . $formatList($yesterdayEntries)
            . "\n\nLista HOY:\n" . $formatList($todayEntries);

        try {
            $response = $this->http->post($baseUrl . '/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $model,
                    'temperature' => 0.3,
                    'messages' => [
                        ['role' => 'system', 'content' => $system],
                        ['role' => 'user', 'content' => $content],
                    ],
                ],
            ]);

            $data = json_decode((string) $response->getBody(), true);
            $text = $data['choices'][0]['message']['content'] ?? null;
            if (!is_string($text) || trim($text) === '') {
                return null;
            }
            return trim($text);
        } catch (\Throwable $e) {
            Log::warning('OpenAI summary hoy/ayer failed: ' . $e->getMessage());
            return null;
        }
    }
}
