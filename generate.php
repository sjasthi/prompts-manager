<?php
require_once 'includes/database.php';

$conn = getConnection();

/* Load prompts */
$promptsResult = $conn->query("
    SELECT prompt_id, title, prompt_text
    FROM prompts
    WHERE status = 'active'
    ORDER BY title
");

$imageUrl = null;
$errorMessage = null;
$previousImages = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $prompt_id = intval($_POST['prompt_id']);

    $stmt = $conn->prepare("
        SELECT prompt_text
        FROM prompts
        WHERE prompt_id = ?
    ");

    $stmt->bind_param("i", $prompt_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if ($row) {

        try {

            $promptText = $row['prompt_text'];

            $encodedPrompt = urlencode($promptText);

            // Force a fresh image every request
            $seed = random_int(1, 999999999);

            $imageUrl =
                    "https://image.pollinations.ai/prompt/"
                    . $encodedPrompt
                    . "?seed="
                    . $seed;

            // Save generation metadata
            $modelName = "Pollinations AI";
            $status = "success";

            $insertStmt = $conn->prepare("
                INSERT INTO generated_images
                (prompt_id, model_name, image_path, generation_status)
                VALUES (?, ?, ?, ?)
            ");

            $insertStmt->bind_param(
                    "isss",
                    $prompt_id,
                    $modelName,
                    $imageUrl,
                    $status
            );

            $insertStmt->execute();

            // Load previous generations for this prompt
            $historyStmt = $conn->prepare("
                SELECT image_path, generation_date
                FROM generated_images
                WHERE prompt_id = ?
                ORDER BY generation_date DESC
            ");

            $historyStmt->bind_param("i", $prompt_id);
            $historyStmt->execute();

            $historyResult = $historyStmt->get_result();

            while ($historyRow = $historyResult->fetch_assoc()) {
                $previousImages[] = $historyRow;
            }

        } catch (Exception $e) {

            $errorMessage = "Image generation failed. Please try again later.";

            // Save failed attempt
            $modelName = "Pollinations AI";
            $status = "failure";

            $insertStmt = $conn->prepare("
                INSERT INTO generated_images
                (prompt_id, model_name, image_path, generation_status)
                VALUES (?, ?, '', ?)
            ");

            $insertStmt->bind_param(
                    "iss",
                    $prompt_id,
                    $modelName,
                    $status
            );

            $insertStmt->execute();
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Image</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>

        body {
            background-color: #f8f9fa;
        }

        .page-wrapper {
            max-width: 900px;
            margin: 50px auto;
        }

        .generator-card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,.08);
        }

        .generate-btn {
            background: linear-gradient(135deg,#7c3aed,#4f46e5);
            border: none;
            color: white;
            font-weight: 600;
            padding: 12px;
            width: 100%;
        }

        .generate-btn:hover {
            opacity: .95;
        }

        .generated-image {
            width: 100%;
            border-radius: 12px;
            margin-top: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,.15);
        }

        .history-image {
            width: 100%;
            height: 220px;
            object-fit: cover;
        }

        .loading-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(255,255,255,.92);
            z-index: 9999;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }

        .spinner {
            width: 70px;
            height: 70px;
            border: 8px solid #dee2e6;
            border-top: 8px solid #6f42c1;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        .loading-text {
            margin-top: 20px;
            color: #212529;
            font-size: 18px;
            font-weight: 600;
            text-align: center;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

    </style>

</head>

<body>

<div class="container mt-3">
    <a href="index.php" class="btn btn-outline-secondary">
        ← Back to Dashboard
    </a>
</div>

<div class="loading-overlay" id="loading">

    <div class="spinner"></div>

    <div class="loading-text">
        Generating AI image...
        <br>
        <small class="text-muted">
            This usually takes about 20–30 seconds.
        </small>
    </div>

</div>

<div class="container page-wrapper">

    <div class="card generator-card">

        <div class="card-body p-4">

            <h1 class="text-center mb-2">
                🎨 Generate Image
            </h1>

            <p class="text-center text-muted mb-4">
                Generate AI images from saved prompts.
            </p>

            <form method="POST" onsubmit="return showLoading()">

                <div class="mb-3">

                    <label class="form-label">
                        Select Prompt
                    </label>

                    <select class="form-select" name="prompt_id" required>

                        <?php while ($prompt = $promptsResult->fetch_assoc()): ?>

                            <option value="<?= $prompt['prompt_id'] ?>">
                                <?= htmlspecialchars($prompt['title']) ?>
                            </option>

                        <?php endwhile; ?>

                    </select>

                </div>

                <div class="mb-3">

                    <label class="form-label">
                        AI Model
                    </label>

                    <select class="form-select" disabled>
                        <option>
                            Pollinations AI (Active)
                        </option>
                    </select>

                    <div class="form-text">
                        Stable Diffusion support is planned for a future release.
                    </div>

                </div>

                <button class="btn generate-btn">
                    Generate Image
                </button>

            </form>

        </div>

    </div>

    <?php if ($errorMessage): ?>

        <div class="alert alert-danger mt-4">
            <?= htmlspecialchars($errorMessage) ?>
        </div>

    <?php endif; ?>

    <?php if ($imageUrl): ?>

        <div class="card generator-card mt-4">

            <div class="card-body">

                <h4 class="mb-3">
                    Generated Image
                </h4>

                <img
                        src="<?= htmlspecialchars($imageUrl) ?>"
                        class="generated-image"
                        alt="Generated AI Image"
                >

                <hr class="my-4">

                <h5 class="mb-3">
                    Previous Generations
                </h5>

                <div class="row">

                    <?php foreach ($previousImages as $history): ?>

                        <div class="col-md-4 mb-3">

                            <div class="card shadow-sm">

                                <img
                                        src="<?= htmlspecialchars($history['image_path']) ?>"
                                        class="card-img-top history-image"
                                        alt="Generated Image"
                                >

                                <div class="card-body">

                                    <small class="text-muted">
                                        <?= $history['generation_date'] ?>
                                    </small>

                                </div>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

            </div>

        </div>

    <?php endif; ?>

</div>

<script>

    function showLoading() {
        document.getElementById('loading').style.display = 'flex';
        return true;
    }

</script>

</body>
</html>