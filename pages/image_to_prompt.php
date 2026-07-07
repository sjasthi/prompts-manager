<?php
require_once '../includes/database.php';
require_once '../includes/config.php';

$conn = getConnection();

$message = "";
$error = "";
$generatedPrompt = "";
$imagePreview = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['save_prompt'])) {
        $title = $conn->real_escape_string(trim($_POST['title']));
        $prompt_text = $conn->real_escape_string(trim($_POST['prompt_text']));
        $category = $conn->real_escape_string(trim($_POST['category']));
        $tags = $conn->real_escape_string(trim($_POST['tags']));

        if ($title == "" || $prompt_text == "") {
            $error = "Title and prompt text are required.";
        } else {
            $conn->query("
                INSERT INTO prompts (title, prompt_text, category, tags)
                VALUES ('$title', '$prompt_text', '$category', '$tags')
            ");

            $newId = $conn->insert_id;

            $conn->query("
                INSERT INTO prompt_versions (prompt_id, version_number, version_text)
                VALUES ($newId, 1, '$prompt_text')
            ");

            header("Location: prompt_library.php");
            exit;
        }
    }

    if (isset($_POST['analyze_image'])) {
        if (!isset($_FILES['image']) || $_FILES['image']['error'] != 0) {
            $error = "Please upload an image.";
        } else {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
            $fileType = mime_content_type($_FILES['image']['tmp_name']);

            if (!in_array($fileType, $allowedTypes)) {
                $error = "Only JPG, PNG, and WEBP images are allowed.";
            } else {
                $uploadDir = "../uploads/";

                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $fileName = time() . "_" . basename($_FILES['image']['name']);
                $targetPath = $uploadDir . $fileName;

                move_uploaded_file($_FILES['image']['tmp_name'], $targetPath);

                $imagePreview = $targetPath;

                $generatedPrompt = "A detailed high-quality image based on the uploaded picture. Include the main subject, background, lighting, colors, mood, camera angle, and visual style. Make the image clear, realistic, and professional with strong details and balanced composition.";
            }
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Image to Prompt</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container mt-4 mb-5">

    <a href="../index.php" class="text-decoration-none text-muted small">&larr; Back to Home</a>

    <h2 class="fw-bold mt-2">Image to Prompt</h2>
    <p class="text-muted">Upload an image and create an editable prompt from it.</p>

    <?php if ($error != ""): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if ($message != ""): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <h5 class="card-title">Upload Image</h5>

            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Choose Image</label>
                    <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png,.webp" required>
                    <div class="form-text">Supported formats: JPG, PNG, WEBP</div>
                </div>

                <button type="submit" name="analyze_image" class="btn btn-primary">
                    Generate Prompt
                </button>

                <a href="../index.php" class="btn btn-secondary">Back</a>
            </form>
        </div>
    </div>

    <?php if ($generatedPrompt != ""): ?>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">

                <h5 class="card-title">Uploaded Image Preview</h5>

                <img src="<?php echo htmlspecialchars($imagePreview); ?>"
                     class="img-fluid rounded border mb-4"
                     style="max-height: 300px;">

                <h5 class="card-title">Generated Prompt</h5>
                <p class="text-muted small">You can edit this prompt before saving it to the library.</p>

                <form method="POST">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Title</label>
                        <input type="text"
                               name="title"
                               class="form-control"
                               value="Image Generated Prompt"
                               required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Prompt Text</label>
                        <textarea name="prompt_text"
                                  class="form-control"
                                  rows="6"
                                  required><?php echo htmlspecialchars($generatedPrompt); ?></textarea>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Category</label>
                            <input type="text"
                                   name="category"
                                   class="form-control"
                                   value="Image to Prompt">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tags</label>
                            <input type="text"
                                   name="tags"
                                   class="form-control"
                                   value="image,upload,generated">
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" name="save_prompt" class="btn btn-success">
                            Save to Prompt Library
                        </button>
                    </div>

                </form>

            </div>
        </div>

    <?php endif; ?>

    <a href="prompt_library.php" class="btn btn-outline-secondary">View Prompt Library</a>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>