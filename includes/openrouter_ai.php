<?php

require_once __DIR__ . '/config.php';

function analyzeImageWithOpenRouter(string $imagePath, string $mimeType): array
{
    if (!defined('OPENROUTER_API_KEY') || OPENROUTER_API_KEY === '') {
        return ['success' => false, 'error' => 'OpenRouter is not configured.'];
    }

    $imageContents = file_get_contents($imagePath);
    if ($imageContents === false) {
        return ['success' => false, 'error' => 'The uploaded image could not be read.'];
    }

    $payload = [
        'model' => 'openrouter/free',
        'messages' => [[
            'role' => 'user',
            'content' => [
                [
                    'type' => 'text',
                    'text' => "Turn this image into one detailed AI image-generation prompt. Include the subject, setting, composition, camera angle, lighting, colors, mood, visual style, and important details. Return only the prompt, without an introduction or explanation."
                ],
                [
                    'type' => 'image_url',
                    'image_url' => [
                        'url' => 'data:' . $mimeType . ';base64,' . base64_encode($imageContents)
                    ]
                ]
            ]
        ]],
        'max_tokens' => 512
    ];

    $ch = curl_init('https://openrouter.ai/api/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . OPENROUTER_API_KEY,
            'Content-Type: application/json',
            'X-Title: Image Prompt Manager'
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_TIMEOUT => 90
    ]);

    $response = curl_exec($ch);
    if ($response === false) {
        $error = curl_error($ch);
        curl_close($ch);
        return ['success' => false, 'error' => 'OpenRouter connection error: ' . $error];
    }

    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $result = json_decode($response, true);

    if ($status >= 200 && $status < 300) {
        $content = $result['choices'][0]['message']['content'] ?? '';
        if (is_string($content) && trim($content) !== '') {
            return [
                'success' => true,
                'prompt' => trim($content),
                'model' => $result['model'] ?? 'openrouter/free'
            ];
        }
    }

    $message = $result['error']['message'] ?? 'OpenRouter could not analyze the image.';
    return ['success' => false, 'error' => $message];
}
