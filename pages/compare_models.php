<?php

require_once '../includes/database.php';
require_once '../includes/config.php';

$conn = getConnection();

$errorMessage = '';
$promptTitle = '';
$promptText = '';
$successMessage = '';

if (isset($_GET['saved'])) {
    $successMessage = "Evaluation saved successfully. Select another prompt to compare.";
}

$pollinationsImage = '';
$cloudflareImage = '';

$pollinationsTime = null;
$cloudflareTime = null;

if (isset($_POST['save_feedback'])) {

    $promptId = (int)$_POST['prompt_id'];
    $selectedModel = $_POST['preferred_model'] ?? '';
    $notes = trim($_POST['notes']);

    if ($selectedModel != '') {

        $stmt = $conn->prepare("
            INSERT INTO comparison_feedback
            (prompt_id, selected_model, notes)
            VALUES (?, ?, ?)
        ");

        $stmt->bind_param(
            "iss",
            $promptId,
            $selectedModel,
            $notes
        );

        $stmt->execute();

        header("Location: compare_models.php?saved=true");
                exit;
    }
}

/*
|--------------------------------------------------------------------------
| Load active prompts
|--------------------------------------------------------------------------
*/

$promptsResult = $conn->query("
    SELECT prompt_id, title, prompt_text
    FROM prompts
    WHERE status = 'active'
    ORDER BY title
");

/*
|--------------------------------------------------------------------------
| Compare both models
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['compare_models'])
) {

    $promptId = isset($_POST['prompt_id'])
        ? (int) $_POST['prompt_id']
        : 0;

    if ($promptId <= 0) {
        $errorMessage = 'Please select a prompt.';
    } else {

        $stmt = $conn->prepare("
            SELECT title, prompt_text
            FROM prompts
            WHERE prompt_id = ?
              AND status = 'active'
        ");

        $stmt->bind_param('i', $promptId);
        $stmt->execute();

        $promptResult = $stmt->get_result();
        $prompt = $promptResult->fetch_assoc();

        if (!$prompt) {
            $errorMessage = 'The selected prompt could not be found.';
        } else {

            $promptTitle = $prompt['title'];
            $promptText = trim($prompt['prompt_text']);

            /*
            |--------------------------------------------------------------------------
            | Model 1: Pollinations AI
            |--------------------------------------------------------------------------
            */

            try {
                $pollinationsStart = microtime(true);
                $seed = random_int(1, 999999999);

                $pollinationsImage =
                    'https://image.pollinations.ai/prompt/' .
                    urlencode($promptText) .
                    '?seed=' . $seed .
                    '&width=768&height=768&nologo=true';

                $pollinationsTime = microtime(true) - $pollinationsStart;

                $status = 'success';
                $modelName = 'Pollinations AI';

                $insert = $conn->prepare("
                    INSERT INTO generated_images
                    (prompt_id, model_name, image_path, generation_status)
                    VALUES (?, ?, ?, ?)
                ");

                $insert->bind_param(
                    'isss',
                    $promptId,
                    $modelName,
                    $pollinationsImage,
                    $status
                );

                $insert->execute();

            } catch (Throwable $e) {
                $pollinationsImage = '';
            }

            /*
            |--------------------------------------------------------------------------
            | Model 2: Cloudflare FLUX
            |--------------------------------------------------------------------------
            */

            try {
                $cloudflareStart = microtime(true);

                $apiUrl =
                    'https://api.cloudflare.com/client/v4/accounts/' .
                    CF_ACCOUNT_ID .
                    '/ai/run/@cf/black-forest-labs/flux-1-schnell';

                $payload = json_encode([
                    'prompt' => $promptText
                ]);

                $ch = curl_init();

                curl_setopt_array($ch, [
                    CURLOPT_URL => $apiUrl,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_HTTPHEADER => [
                        'Authorization: Bearer ' . CF_API_TOKEN,
                        'Content-Type: application/json'
                    ],
                    CURLOPT_POSTFIELDS => $payload,
                    CURLOPT_TIMEOUT => 90
                ]);

                $response = curl_exec($ch);

                if ($response === false) {
                    throw new Exception(curl_error($ch));
                }

                $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $cloudflare = json_decode($response, true);

                if (
                    $httpStatus < 200 ||
                    $httpStatus >= 300 ||
                    empty($cloudflare['result']['image'])
                ) {
                    $message =
                        $cloudflare['errors'][0]['message']
                        ?? 'Cloudflare did not return an image.';

                    throw new Exception($message);
                }

                $cloudflareImage =
                    'data:image/jpeg;base64,' .
                    $cloudflare['result']['image'];

                $cloudflareTime = microtime(true) - $cloudflareStart;

                $status = 'success';
                $modelName = 'Cloudflare FLUX';

                $insert = $conn->prepare("
                    INSERT INTO generated_images
                    (prompt_id, model_name, image_path, generation_status)
                    VALUES (?, ?, ?, ?)
                ");

                $insert->bind_param(
                    'isss',
                    $promptId,
                    $modelName,
                    $cloudflareImage,
                    $status
                );

                $insert->execute();

            } catch (Throwable $e) {
                $cloudflareImage = '';

                if ($errorMessage === '') {
                    $errorMessage =
                        'Cloudflare generation failed: ' .
                        $e->getMessage();
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Save comparison history
            |--------------------------------------------------------------------------
            */

            if ($pollinationsImage !== '' || $cloudflareImage !== '') {

                $modelsUsed = 'Pollinations AI, Cloudflare FLUX';

                $outputReferences = json_encode([
                    'pollinations' => $pollinationsImage,
                    'cloudflare' => 'stored_in_generated_images'
                ]);

                $comparisonStmt = $conn->prepare("
                    INSERT INTO model_comparisons
                    (prompt_id, models_used, output_references)
                    VALUES (?, ?, ?)
                ");

                $comparisonStmt->bind_param(
                    'iss',
                    $promptId,
                    $modelsUsed,
                    $outputReferences
                );

                $comparisonStmt->execute();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1">

    <title>Compare AI Models</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<link rel="stylesheet" href="../assets/css/style.css">
    <style>

        .page-wrapper {
            max-width: 1200px;
            margin: 40px auto;
        }

        .main-card,
        .model-card {
            border: none;
            border-radius: 18px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, .08);
        }

        .model-image {
            width: 100%;
            height: 460px;
            object-fit: contain;
            background: #f8f9fa;
            border-radius: 12px;
        }

        .compare-button {
            background: linear-gradient(135deg, #7c3aed, #4f46e5);
            color: white;
            border: none;
            font-weight: 600;
            border-radius: 10px;
            padding: 11px 24px;
        }

        .compare-button:hover {
            color: white;
            opacity: .95;
        }

        .loading-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(255, 255, 255, .94);
            z-index: 9999;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }

        .spinner {
            width: 70px;
            height: 70px;
            border: 8px solid #ddd;
            border-top: 8px solid #6f42c1;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }
    </style>
</head>

<body>
<?php include '../includes/navbar.php'; ?>
<div class="loading-overlay" id="loading">
    <div class="spinner"></div>
    <h4 class="mt-4">Generating both images...</h4>
    <p class="text-muted">
        Cloudflare may take several seconds.
    </p>
</div>

<div class="container page-wrapper">

    <a href="../index.php"
    class="btn btn-outline-secondary mb-4">
    ← Dashboard
    </a>

    <a href="comparison_history.php"
    class="btn btn-outline-primary mb-4">
    📚 Comparison History
    </a>

    <div class="text-center mb-5">
        <h1 class="fw-bold">⚖️ Compare AI Models</h1>
        <p class="text-muted">
            Run the same saved prompt through Pollinations AI and
            Cloudflare FLUX, then compare the outputs side by side.
        </p>
    </div>

    <?php if ($errorMessage !== ''): ?>
        <div class="alert alert-warning">
            <?php echo htmlspecialchars($errorMessage); ?>
        </div>
    <?php endif; ?>

    <?php if ($successMessage !== ''): ?>

    <div class="alert alert-success">
        <?php echo htmlspecialchars($successMessage); ?>
    </div>

    <div class="mb-4">
        <a href="compare_models.php"
           class="btn btn-outline-primary">
            Compare Another Prompt
        </a>
    </div>

    <?php endif; ?>

    <div class="card main-card mb-4">
        <div class="card-body p-4">

            <form method="POST"
                  onsubmit="showLoading()">

                <label class="form-label fw-semibold">
                    Select Prompt
                </label>

                <select name="prompt_id"
                        class="form-select"
                        required>

                    <option value="">
                        Choose a saved prompt
                    </option>

                    <?php while ($prompt = $promptsResult->fetch_assoc()): ?>
                        <option
                            value="<?php echo $prompt['prompt_id']; ?>"
                            <?php
                            echo (
                                isset($promptId) &&
                                $promptId === (int) $prompt['prompt_id']
                            ) ? 'selected' : '';
                            ?>
                        >
                            <?php
                            echo htmlspecialchars($prompt['title']);
                            ?>
                        </option>
                    <?php endwhile; ?>

                </select>

                <div class="text-center mt-4">
                   <button type="submit"
                           name="compare_models"
                           class="btn compare-button">
                       Compare Both Models
                   </button>
                </div>

            </form>
        </div>
    </div>

    <?php if ($pollinationsImage !== '' || $cloudflareImage !== ''): ?>

        <div class="card main-card mb-4">
            <div class="card-body">
                <h5 class="fw-bold">
                    Prompt: <?php echo htmlspecialchars($promptTitle); ?>
                </h5>

                <p class="text-muted mb-0">
                    <?php echo htmlspecialchars($promptText); ?>
                </p>
            </div>
        </div>

        <div class="row g-4">

            <div class="col-lg-6">
                <div class="card model-card h-100">
                    <div class="card-body">

                        <div class="d-flex justify-content-between
                                    align-items-center mb-3">
                            <h4 class="mb-0">Pollinations AI</h4>
                            <span class="badge bg-primary">
                                Model 1
                            </span>
                        </div>

                        <?php if ($pollinationsImage !== ''): ?>

                            <img
                                src="<?php
                                echo htmlspecialchars(
                                    $pollinationsImage
                                );
                                ?>"
                                class="model-image"
                                alt="Pollinations output">

                            <p class="text-muted small mt-3">
                                Request prepared in
                                <?php
                                echo number_format(
                                    $pollinationsTime,
                                    3
                                );
                                ?> seconds
                            </p>

                         <a href="<?php echo htmlspecialchars($pollinationsImage); ?>"
                            target="_blank"
                            class="btn btn-sm btn-outline-secondary float-end mt-2"
                            title="Download"
                            onclick="downloadImage(event, this.href)">
                             <i class="bi bi-download"></i>
                         </a>

                        <?php else: ?>
                            <div class="alert alert-danger">
                                Pollinations could not generate an image.
                            </div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card model-card h-100">
                    <div class="card-body">

                        <div class="d-flex justify-content-between
                                    align-items-center mb-3">
                            <h4 class="mb-0">Cloudflare FLUX</h4>
                            <span class="badge bg-success">
                                Model 2
                            </span>
                        </div>

                        <?php if ($cloudflareImage !== ''): ?>

                            <img
                                src="<?php
                                echo htmlspecialchars(
                                    $cloudflareImage
                                );
                                ?>"
                                class="model-image"
                                alt="Cloudflare output">

                            <p class="text-muted small mt-3">
                                Generated in
                                <?php
                                echo number_format(
                                    $cloudflareTime,
                                    2
                                );
                                ?> seconds
                            </p>

                            <a
                                href="<?php echo htmlspecialchars($cloudflareImage); ?>"
                                download="cloudflare-output.jpg"
                                class="btn btn-sm btn-outline-secondary float-end mt-2"
                                title="Download Image">

                                <i class="bi bi-download"></i>
                            </a>

                        <?php else: ?>
                            <div class="alert alert-danger">
                                Cloudflare could not generate an image.
                            </div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>

        </div>
    <form method="POST">

        <div class="card main-card mt-4">
            <div class="card-body p-4">

                <h4 class="fw-bold">
                    Comparison Results
                </h4>

                <p class="text-muted">
                    Select the model that produced the best image.
                </p>
             <input
            type="hidden"
            name="prompt_id"
             value="<?php echo $promptId; ?>">

                <!-- Winner Selection -->
                <div class="form-check mb-2">
                 <input
                 class="form-check-input"
                 type="radio"
                 name="preferred_model"
                 value="Pollinations AI"
                 id="pollinationsWinner">
                    <label class="form-check-label"
                           for="pollinationsWinner">
                        Pollinations AI is better
                    </label>
                </div>

                <div class="form-check mb-4">
                   <input
                   class="form-check-input"
                   type="radio"
                   name="preferred_model"
                   value="Cloudflare FLUX"
                   id="cloudflareWinner">

                    <label class="form-check-label"
                           for="cloudflareWinner">
                        Cloudflare FLUX is better
                    </label>
                </div>

                <!-- Notes -->
                <div class="mb-4">
                    <label class="form-label fw-semibold">
                        Additional Notes
                    </label>

                    <textarea
                        class="form-control"
                        name="notes"
                        rows="3"
                        placeholder="Write your observations..."></textarea>
                </div>

                <!-- Save -->
                <div class="text-end">
                    <button
                        type="submit"
                        name="save_feedback"
                        class="btn btn-primary">
                        Save Evaluation
                    </button>
                </div>

                <p class="small text-muted mt-3 mb-0">
                    Save your evaluation for future reference.
                </p>

            </div>
        </div>
 </form>
    <?php endif; ?>

</div>

<script>
    function showLoading() {
        document.getElementById('loading').style.display = 'flex';
        return true;
    }
</script>
<script>
async function downloadImage(e, url) {

    e.preventDefault();

    try {

        const response = await fetch(url);
        const blob = await response.blob();

        const objectUrl = URL.createObjectURL(blob);

        const a = document.createElement("a");
        a.href = objectUrl;
        a.download = "pollinations-output.png";
        document.body.appendChild(a);
        a.click();
        a.remove();

        URL.revokeObjectURL(objectUrl);

    } catch {

        window.open(url);

    }

}
</script>
<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>

</body>
</html>
<?php
$conn->close();
?>