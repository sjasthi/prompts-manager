<?php
require_once '../includes/database.php';

$conn = getConnection();

$message = null;
$suggestedPrompt = null;
$saved = false;

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['make_suggestion'])) {
    $imageDescription = trim($_POST['image_description']);

    if ($imageDescription === '') {
        $message = ['danger', 'Please enter an image description first.'];
    } else {
        $suggestedPrompt = $imageDescription . ', highly detailed, realistic lighting, sharp focus, professional composition';
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['save_prompt'])) {
    $title = trim($_POST['title']);
    $promptText = trim($_POST['prompt_text']);
    $category = trim($_POST['category']);
    $tags = trim($_POST['tags']);

    if ($title === '' || $promptText === '') {
        $message = ['danger', 'Title and prompt text are required.'];
        $suggestedPrompt = $promptText;
    } else {
        $stmt = $conn->prepare("
            INSERT INTO prompts (title, prompt_text, category, tags, favorite_status)
            VALUES (?, ?, ?, ?, 0)
        ");

        $stmt->bind_param("ssss", $title, $promptText, $category, $tags);
        $stmt->execute();

        $saved = true;
        $message = ['success', 'Prompt saved successfully. You can now view it in the Prompt Library.'];
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Image-to-Prompt</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background-color: #f8f9fa;
        }

        .page-wrapper {
            max-width: 900px;
            margin: 50px auto;
        }

        .main-card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,.08);
        }

        .suggestion-box {
            background: #f1f7ff;
            border: 1px solid #b6d4fe;
            border-radius: 12px;
            padding: 18px;
            font-size: 16px;
            line-height: 1.6;
        }

        .save-btn {
            background: #198754;
            color: white;
            font-weight: 600;
        }

        .copy-btn {
            font-weight: 600;
        }
    </style>
</head>

<body>

<div class="container mt-3">
    <a href="../index.php" class="btn btn-outline-secondary">
        ← Back to Dashboard
    </a>
</div>

<div class="container page-wrapper">

    <div class="card main-card mb-4">
        <div class="card-body p-4">

            <h1 class="text-center mb-2">🔍 Image-to-Prompt</h1>

            <p class="text-center text-muted mb-4">
                Display reverse-engineered prompt suggestions and save them into the Prompt Library.
            </p>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message[0]; ?>">
                    <?php echo htmlspecialchars($message[1]); ?>
                </div>
            <?php endif; ?>

            <?php if (!$saved): ?>

                <form method="POST">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Image Analysis Result
                        </label>

                        <textarea
                            name="image_description"
                            class="form-control"
                            rows="4"
                            placeholder="Example: A cat sitting near a window during sunset with warm lighting"
                            required><?php echo htmlspecialchars($_POST['image_description'] ?? ''); ?></textarea>

                        <div class="form-text">
                            The uploaded image will automatically generate a prompt suggestion after analysis.
                        </div>
                    </div>

                    <button type="submit" name="make_suggestion" class="btn btn-primary">
                        Generate Prompt Suggestion
                    </button>

                </form>

            <?php endif; ?>

        </div>
    </div>

    <?php if ($suggestedPrompt && !$saved): ?>

        <div class="card main-card">
            <div class="card-body p-4">

                <h4 class="mb-3">Generated Prompt Suggestion</h4>

                <div class="suggestion-box mb-4" id="promptText">
                    <?php echo htmlspecialchars($suggestedPrompt); ?>
                </div>

                <form method="POST">

                    <input
                        type="hidden"
                        name="prompt_text"
                        value="<?php echo htmlspecialchars($suggestedPrompt); ?>"
                    >

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Prompt Title</label>
                        <input
                            type="text"
                            name="title"
                            class="form-control"
                            placeholder="Example: Sunset Cat Portrait"
                            required
                        >
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Category</label>
                        <input
                            type="text"
                            name="category"
                            class="form-control"
                            value="Image-to-Prompt"
                        >
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tags</label>
                        <input
                            type="text"
                            name="tags"
                            class="form-control"
                            value="image-to-prompt"
                        >
                    </div>

                    <button type="submit" name="save_prompt" class="btn save-btn">
                        Save as New Prompt
                    </button>

                    <button type="button" class="btn btn-outline-primary copy-btn" onclick="copyPrompt()">
                        Copy Prompt
                    </button>

                    <a href="prompt_library.php" class="btn btn-outline-secondary">
                        View Library
                    </a>

                </form>

            </div>
        </div>

    <?php endif; ?>

    <?php if ($saved): ?>

        <div class="card main-card">
            <div class="card-body p-4 text-center">

                <h3 class="mb-3">✅ Saved</h3>

                <p class="text-muted">
                    Your reverse-engineered prompt was saved as a new prompt.
                </p>

                <a href="prompt_library.php" class="btn btn-primary">
                    View Prompt Library
                </a>

                <a href="image_to_prompt.php" class="btn btn-outline-secondary">
                    Create Another
                </a>

            </div>
        </div>

    <?php endif; ?>

</div>

<script>
    function copyPrompt() {
        const text = document.getElementById("promptText").innerText;

        navigator.clipboard.writeText(text).then(function () {
            alert("Prompt copied!");
        });
    }
</script>

</body>
</html>