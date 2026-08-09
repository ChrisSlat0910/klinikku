<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiService
{
    private string $apiKey;

    /** @var array<string> */
    private array $modelChain = [
        'gemini-3.6-flash',
        'gemini-2.5-flash',
        'gemini-2.5-flash-lite',
        'gemma-4-31b-it',
        'gemma-4-12b-it',
    ];

    private string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models';

    public function __construct()
    {
        $this->apiKey = (string) config('services.gemini.api_key', '');
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function generate(string $task, array $context = []): array
    {
        if (empty($this->apiKey)) {
            return $this->formatResponse($task, 'AI service not configured. Please set GEMINI_API_KEY.', 'none');
        }

        $prompt = $this->buildPrompt($task, $context);
        $lastError = '';

        foreach ($this->modelChain as $model) {
            try {
                $draft = $this->callGemini($model, $prompt);

                Log::info("AI generation successful with model: {$model}");

                return $this->formatResponse($task, $draft, $model);
            } catch (\Exception $e) {
                $lastError = $e->getMessage();
                Log::warning("Model {$model} failed: {$lastError}. Trying next fallback...");

                continue;
            }
        }

        Log::error("All AI models failed. Last error: {$lastError}");

        throw new \RuntimeException('AI_UNAVAILABLE');
    }

    private function callGemini(string $model, string $prompt): string
    {
        $response = Http::timeout(30)
            ->post("{$this->baseUrl}/{$model}:generateContent?key={$this->apiKey}", [
                'contents' => [
                    ['parts' => [['text' => $prompt]]],
                ],
                'generationConfig' => [
                    'maxOutputTokens' => 500,
                    'temperature' => 0.3,
                ],
            ]);

        if ($response->status() === 429) {
            throw new \RuntimeException("Rate limit hit on {$model}");
        }

        if ($response->status() === 404) {
            throw new \RuntimeException("Model {$model} not available on free tier");
        }

        if (! $response->successful()) {
            throw new \RuntimeException("API error {$response->status()} on {$model}");
        }

        $text = $response->json('candidates.0.content.parts.0.text', '');

        if (empty($text)) {
            throw new \RuntimeException("Empty response from {$model}");
        }

        return (string) $text;
    }

    /**
     * @return array<string, mixed>
     */
    private function formatResponse(string $task, string $draft, string $provider): array
    {
        return [
            'task' => $task,
            'draft' => $draft,
            'model' => $provider,
            'requires_verification' => true,
            'warning' => 'AI output requires staff verification before use.',
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function buildPrompt(string $task, array $context): string
    {
        return match ($task) {
            'reminder-draft' => sprintf(
                'Buatkan pesan pengingat janji temu dalam Bahasa Indonesia yang ramah dan profesional untuk pasien bernama %s dengan dokter %s pada tanggal %s. Maksimal 3 kalimat.',
                $context['patient_name'] ?? 'Pasien',
                $context['doctor_name'] ?? 'Dokter',
                $context['scheduled_at'] ?? 'besok'
            ),
            'prefill' => sprintf(
                'Berdasarkan riwayat medis berikut: %s. Buatkan ringkasan singkat kondisi pasien dalam Bahasa Indonesia untuk membantu dokter mengisi form. Maksimal 100 kata. HARUS diverifikasi dokter sebelum disimpan.',
                json_encode($context['history'] ?? [])
            ),
            'daily-report' => sprintf(
                'Buatkan ringkasan operasional harian klinik dalam Bahasa Indonesia berdasarkan data berikut: %s pasien dikunjungi, %s antrian selesai, pendapatan Rp %s. Maksimal 5 kalimat.',
                $context['total_patients'] ?? '0',
                $context['completed_queues'] ?? '0',
                number_format((float) ($context['revenue'] ?? 0), 0, ',', '.')
            ),
            default => 'Tugas tidak dikenali.',
        };
    }
}
