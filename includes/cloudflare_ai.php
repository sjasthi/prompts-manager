<?php

require_once 'config.php';

function analyzeImageWithCloudflare($imagePath)
{
    if (
        empty(CF_ACCOUNT_ID) ||
        empty(CF_API_TOKEN) ||
        CF_ACCOUNT_ID === 'YOUR_ACCOUNT_ID' ||
        CF_API_TOKEN === 'YOUR_CLOUDFLARE_TOKEN'
    ) {
        return "Cloudflare AI is not configured.";
    }

    if (!file_exists($imagePath)) {
        return "Uploaded image could not be found.";
    }

    $imageContents = file_get_contents($imagePath);

    if ($imageContents === false) {
        return "Unable to read the uploaded image.";
    }

    // Cloudflare expects an array of image byte values, not Base64.
    $unpackedBytes = unpack('C*', $imageContents);

    if ($unpackedBytes === false) {
        return "Unable to prepare the image for analysis.";
    }

    $imageBytes = array_values($unpackedBytes);

    $url = "https://api.cloudflare.com/client/v4/accounts/" .
        CF_ACCOUNT_ID .
        "/ai/run/@cf/llava-hf/llava-1.5-7b-hf";

    $payload = [
        "image" => $imageBytes,
      "prompt" => "Generate a professional AI image generation prompt for this image. Do not describe the image. Return only one detailed prompt suitable for Stable Diffusion, DALL·E, Midjourney, or Flux. Include subject, style, lighting, colors, camera angle, composition, quality, and artistic details. Do not use phrases like 'the image shows' or 'this image features'.",
        "max_tokens" => 512
    ];

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer " . CF_API_TOKEN,
            "Content-Type: application/json"
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 60
    ]);

    $response = curl_exec($ch);

    if ($response === false) {
        return "Cloudflare connection error: " . curl_error($ch);
    }

    $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $result = json_decode($response, true);

    if (!is_array($result)) {
        return "Cloudflare returned an invalid response.";
    }

    if (
        $httpStatus >= 200 &&
        $httpStatus < 300 &&
        isset($result['success']) &&
        $result['success'] === true
    ) {
        if (!empty($result['result']['description'])) {
            return trim($result['result']['description']);
        }

        if (!empty($result['result']['response'])) {
            return trim($result['result']['response']);
        }
    }

    if (!empty($result['errors'][0]['message'])) {
        return "Cloudflare error: " . $result['errors'][0]['message'];
    }

    return "Unable to analyze image.";
}