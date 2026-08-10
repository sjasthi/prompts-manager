<?php

require_once __DIR__ . '/config.php';

function analyzeImageWithOpenRouter(string $imagePath, string $mimeType): array
{
    /*
    |--------------------------------------------------------------------------
    | Check OpenRouter Configuration
    |--------------------------------------------------------------------------
    */

    if (!defined('OPENROUTER_API_KEY') || OPENROUTER_API_KEY === '') {
        return [
            'success' => false,
            'error' => 'OpenRouter is not configured.'
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Read Uploaded Image
    |--------------------------------------------------------------------------
    */

    $imageContents = file_get_contents($imagePath);

    if ($imageContents === false) {
        return [
            'success' => false,
            'error' => 'The uploaded image could not be read.'
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Build OpenRouter Request
    |--------------------------------------------------------------------------
    */

    $payload = [
        'model' => 'google/gemma-4-26b-a4b-it:free',

        'messages' => [
            [
                'role' => 'user',

                'content' => [
                    [
                        'type' => 'text',

                        'text' =>
                            'Turn this image into one detailed AI image-generation prompt. '
                            . 'Include the subject, setting, composition, camera angle, '
                            . 'lighting, colors, mood, visual style, and important details. '
                            . 'Return only the prompt, without an introduction or explanation.'
                    ],

                    [
                        'type' => 'image_url',

                        'image_url' => [
                            'url' =>
                                'data:'
                                . $mimeType
                                . ';base64,'
                                . base64_encode($imageContents)
                        ]
                    ]
                ]
            ]
        ],

        'max_tokens' => 250
    ];

    /*
    |--------------------------------------------------------------------------
    | Send Request to OpenRouter
    |--------------------------------------------------------------------------
    */

    $ch = curl_init(
        'https://openrouter.ai/api/v1/chat/completions'
    );

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,

        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . OPENROUTER_API_KEY,
            'Content-Type: application/json',
            'X-Title: Image Prompt Manager'
        ],

        CURLOPT_POSTFIELDS => json_encode($payload),

        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 60
    ]);

    $response = curl_exec($ch);

    /*
    |--------------------------------------------------------------------------
    | Handle Connection Error / Timeout
    |--------------------------------------------------------------------------
    */

    if ($response === false) {

        $error = curl_error($ch);

        curl_close($ch);

        return [
            'success' => false,
            'error' => 'OpenRouter connection error: ' . $error
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Get HTTP Status
    |--------------------------------------------------------------------------
    */

    $status = curl_getinfo(
        $ch,
        CURLINFO_HTTP_CODE
    );

    curl_close($ch);

    /*
    |--------------------------------------------------------------------------
    | Decode Response
    |--------------------------------------------------------------------------
    */

    $result = json_decode(
        $response,
        true
    );

    if (!is_array($result)) {
        return [
            'success' => false,
            'error' => 'OpenRouter returned an invalid response.'
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Successful Response
    |--------------------------------------------------------------------------
    */

    if ($status >= 200 && $status < 300) {

        $content =
            $result['choices'][0]['message']['content']
            ?? '';

        if (is_string($content) && trim($content) !== '') {

            return [
                'success' => true,
                'prompt' => trim($content),
                'model' => 'google/gemma-4-26b-a4b-it:free'
            ];
        }

        return [
            'success' => false,
            'error' =>
                'OpenRouter returned a successful response, but no prompt was generated.'
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Rate Limit Error
    |--------------------------------------------------------------------------
    */

    if ($status === 429) {
        return [
            'success' => false,
            'error' =>
                'The AI service is temporarily rate limited. Please wait a moment and try again.'
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | OpenRouter API Error
    |--------------------------------------------------------------------------
    */

    $message =
        $result['error']['message']
        ?? 'OpenRouter could not analyze the image.';

    return [
        'success' => false,
        'error' =>
            'OpenRouter error (HTTP '
            . $status
            . '): '
            . $message
    ];
}