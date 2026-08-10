<?php

require_once '../includes/database.php';
require_once '../includes/config.php';
require_once '../includes/openrouter_ai.php';

$conn = getConnection();

$message = "";
$error = "";
$generatedPrompt = "";
$imagePreview = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /*
    |--------------------------------------------------------------------------
    | Save Generated Prompt
    |--------------------------------------------------------------------------
    */

    if (isset($_POST['save_prompt'])) {

        $title = $conn->real_escape_string(
            trim($_POST['title'])
        );

        $prompt_text = $conn->real_escape_string(
            trim($_POST['prompt_text'])
        );

        $category = $conn->real_escape_string(
            trim($_POST['category'])
        );

        $tags = $conn->real_escape_string(
            trim($_POST['tags'])
        );

        if ($title == "" || $prompt_text == "") {

            $error = "Title and prompt text are required.";

        } else {

            $conn->query("
                INSERT INTO prompts
                (
                    title,
                    prompt_text,
                    category,
                    tags
                )
                VALUES
                (
                    '$title',
                    '$prompt_text',
                    '$category',
                    '$tags'
                )
            ");

            $newId = $conn->insert_id;

            $conn->query("
                INSERT INTO prompt_versions
                (
                    prompt_id,
                    version_number,
                    version_text
                )
                VALUES
                (
                    $newId,
                    1,
                    '$prompt_text'
                )
            ");

            header("Location: prompt_library.php");
            exit;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Analyze Uploaded Image
    |--------------------------------------------------------------------------
    */

    if (isset($_POST['analyze_image'])) {

        if (
            !isset($_FILES['image']) ||
            $_FILES['image']['error'] != 0
        ) {

            $error = "Please upload an image.";

        } else {

            $allowedTypes = [
                'image/jpeg',
                'image/png',
                'image/webp'
            ];

            $fileType = mime_content_type(
                $_FILES['image']['tmp_name']
            );

            if (!in_array($fileType, $allowedTypes)) {

                $error =
                    "Only JPG, PNG, and WEBP images are allowed.";

            } else {

                /*
                |--------------------------------------------------------------------------
                | Upload Image
                |--------------------------------------------------------------------------
                */

                $uploadDir = "../uploads/";

                if (!is_dir($uploadDir)) {

                    mkdir(
                        $uploadDir,
                        0777,
                        true
                    );
                }

                $fileName =
                    time()
                    . "_"
                    . basename($_FILES['image']['name']);

                $targetPath =
                    $uploadDir
                    . $fileName;

                if (
                    !move_uploaded_file(
                        $_FILES['image']['tmp_name'],
                        $targetPath
                    )
                ) {

                    $error =
                        "The image could not be uploaded.";

                } else {

                    $imagePreview = $targetPath;

                    /*
                    |--------------------------------------------------------------------------
                    | Send Image to OpenRouter
                    |--------------------------------------------------------------------------
                    */

                    $analysis =
                        analyzeImageWithOpenRouter(
                            $targetPath,
                            $fileType
                        );

                    if (
                        isset($analysis['success']) &&
                        $analysis['success'] === true
                    ) {

                        $generatedPrompt =
                            $analysis['prompt'];

                    } else {

                        $error =
                            $analysis['error']
                            ?? "Could not generate prompt from image.";
                    }
                }
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

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>
        Image to Prompt – Image Prompt Manager
    </title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

</head>

<body>

<?php include '../includes/navbar.php'; ?>


<div class="container py-5">

    <div class="page-card">

        <a
            href="../index.php"
            class="text-decoration-none text-muted small"
        >
            &larr; Back to Home
        </a>


        <h1 class="page-title mt-3 mb-2">

            Image to Prompt

        </h1>


        <p class="page-subtitle">

            Upload an image and let AI reverse-engineer
            a descriptive prompt from it.

        </p>


        <!-- Error Message -->

        <?php if ($error != ""): ?>

            <div class="alert alert-danger">

                <?php echo htmlspecialchars($error); ?>

            </div>

        <?php endif; ?>


        <!-- Success Message -->

        <?php if ($message != ""): ?>

            <div class="alert alert-success">

                <?php echo htmlspecialchars($message); ?>

            </div>

        <?php endif; ?>


        <!-- =============================================================
             Upload Image
             ============================================================= -->

        <div class="card shadow-sm border-0 mb-4">

            <div class="card-body">

                <h5 class="card-title">

                    Upload Image

                </h5>


                <p class="text-muted small">

                    The AI will analyze your image and generate
                    a detailed prompt you can edit and save.

                </p>


                <form
                    method="POST"
                    enctype="multipart/form-data"
                    id="imagePromptForm"
                >
                <input type="hidden" name="analyze_image" value="1">

                    <div class="mb-3">

                        <label class="form-label fw-semibold">

                            Choose Image

                        </label>


                        <input
                            type="file"
                            name="image"
                            class="form-control"
                            accept=".jpg,.jpeg,.png,.webp"
                            required
                        >


                        <div class="form-text">

                            Supported formats:
                            JPG, PNG, WEBP

                        </div>

                    </div>


                    <button
                        type="submit"
                        id="generatePromptBtn"
                        class="btn btn-primary"
                    >

                        <i class="bi bi-stars me-1"></i>

                        Generate Prompt

                    </button>


                    <a
                        href="../index.php"
                        class="btn btn-secondary ms-2"
                    >

                        Back

                    </a>


                    <!-- =================================================
                         Loading Indicator
                         ================================================= -->

                    <div
                        id="loadingIndicator"
                        class="text-center mt-4 d-none"
                    >

                        <div
                            class="spinner-border text-primary"
                            role="status"
                            style="width:3rem;height:3rem;"
                        >

                            <span class="visually-hidden">

                                Loading...

                            </span>

                        </div>


                        <h5 class="mt-3 mb-1">

                            Analyzing your image...

                        </h5>


                        <p class="text-muted mb-0">

                            This may take a few moments.

                        </p>

                    </div>

                </form>

            </div>

        </div>


        <!-- =============================================================
             Generated Prompt Result
             ============================================================= -->

        <?php if ($generatedPrompt != ""): ?>

            <div class="card shadow-sm border-0 mb-4">

                <div class="card-body">


                    <h5 class="card-title">

                        Uploaded Image Preview

                    </h5>


                    <img
                        src="<?php echo htmlspecialchars($imagePreview); ?>"
                        class="img-fluid rounded border mb-4"
                        style="max-height:300px;"
                        alt="Uploaded image preview"
                    >


                    <h5 class="card-title">

                        AI Generated Prompt

                    </h5>


                    <p class="text-muted small">

                        Edit the prompt below before saving
                        it to the library.

                    </p>


                    <form method="POST">


                        <!-- Title -->

                        <div class="mb-3">

                            <label class="form-label fw-semibold">

                                Title

                            </label>


                            <input
                                type="text"
                                name="title"
                                class="form-control"
                                value="Image Generated Prompt"
                                required
                            >

                        </div>


                        <!-- Prompt Text -->

                        <div class="mb-3">

                            <div
                                class="d-flex justify-content-between align-items-center mb-1"
                            >

                                <label
                                    class="form-label fw-semibold mb-0"
                                >

                                    Prompt Text

                                </label>


                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-secondary"
                                    id="copyBtn"
                                    onclick="copyPrompt()"
                                >

                                    <i class="bi bi-copy"></i>

                                    Copy

                                </button>

                            </div>


                            <textarea
                                name="prompt_text"
                                class="form-control"
                                id="promptTextarea"
                                rows="6"
                                required
                            ><?php echo htmlspecialchars($generatedPrompt); ?></textarea>

                        </div>


                        <!-- Category and Tags -->

                        <div class="row g-3">

                            <div class="col-md-6">

                                <label class="form-label fw-semibold">

                                    Category

                                </label>


                                <input
                                    type="text"
                                    name="category"
                                    class="form-control"
                                    value="Image to Prompt"
                                >

                            </div>


                            <div class="col-md-6">

                                <label class="form-label fw-semibold">

                                    Tags

                                </label>


                                <input
                                    type="text"
                                    name="tags"
                                    class="form-control"
                                    value="image,upload,generated"
                                >

                            </div>

                        </div>


                        <!-- Save -->

                        <div class="mt-4">

                            <button
                                type="submit"
                                name="save_prompt"
                                class="btn btn-success"
                            >

                                <i class="bi bi-save me-1"></i>

                                Save to Prompt Library

                            </button>

                        </div>

                    </form>

                </div>

            </div>

        <?php endif; ?>


        <a
            href="prompt_library.php"
            class="btn btn-outline-secondary"
        >

            <i class="bi bi-collection me-1"></i>

            View Prompt Library

        </a>

    </div>

</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>


<script>

    /*
    |--------------------------------------------------------------------------
    | Image Analysis Loading Indicator
    |--------------------------------------------------------------------------
    */

    const imagePromptForm =
        document.getElementById("imagePromptForm");

    if (imagePromptForm) {

        imagePromptForm.addEventListener(
            "submit",
            function () {

                const loadingIndicator =
                    document.getElementById(
                        "loadingIndicator"
                    );

                const generateButton =
                    document.getElementById(
                        "generatePromptBtn"
                    );

                loadingIndicator
                    .classList
                    .remove("d-none");



                generateButton.innerHTML =
                    '<span class="spinner-border spinner-border-sm me-2" role="status"></span>'
                    + 'Analyzing...';
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Copy Generated Prompt
    |--------------------------------------------------------------------------
    */

    function copyPrompt() {

        const textarea =
            document.getElementById(
                "promptTextarea"
            );

        navigator.clipboard
            .writeText(textarea.value)
            .then(function () {

                const btn =
                    document.getElementById(
                        "copyBtn"
                    );

                btn.innerHTML =
                    '<i class="bi bi-check-lg"></i> Copied!';

                btn.classList.replace(
                    'btn-outline-secondary',
                    'btn-success'
                );

                setTimeout(
                    function () {

                        btn.innerHTML =
                            '<i class="bi bi-copy"></i> Copy';

                        btn.classList.replace(
                            'btn-success',
                            'btn-outline-secondary'
                        );

                    },
                    2000
                );
            });
    }

</script>


</body>

</html>