<?php

require_once 'includes/database.php';
require_once 'includes/config.php';

$conn = getConnection();

/*
|--------------------------------------------------------------------------
| Dashboard Statistics
|--------------------------------------------------------------------------
*/

$totalImages = 0;
$totalFavorites = 0;

$favQuery = $conn->query("
    SELECT COUNT(*) AS total_favorites
    FROM generated_images
    WHERE favorite = 1
");

if ($favQuery && $favRow = $favQuery->fetch_assoc()) {
    $totalFavorites = $favRow['total_favorites'];
}
$totalModels = 2;

$statsQuery = $conn->query("
    SELECT
        COUNT(*) AS total_images
    FROM generated_images
");

if ($statsQuery && $statsRow = $statsQuery->fetch_assoc()) {
    $totalImages = $statsRow['total_images'];
}


/*
|--------------------------------------------------------------------------
| Load Prompt Library
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
| Load Previous Images (Always Visible)
|--------------------------------------------------------------------------
*/

$previousImages = [];


/*
|--------------------------------------------------------------------------
| Load Prompt List
|--------------------------------------------------------------------------
*/

$promptList = [];

$promptQuery = $conn->query("
    SELECT
        prompt_id,
        title
    FROM prompts
    ORDER BY title
");

while($row = $promptQuery->fetch_assoc()){

    $promptList[] = $row;

}

$search = $_GET['search'] ?? "";
$modelFilter = $_GET['filter_model'] ?? "";
$promptFilter = $_GET['filter_prompt'] ?? "";
$selectedPrompt = $_GET['prompt'] ?? "";

$page = max(1, intval($_GET['page'] ?? 1));

$imagesPerPage = 24;

$offset = ($page - 1) * $imagesPerPage;

$countSql = "
    SELECT COUNT(*)
    FROM generated_images
    WHERE generation_status = 'success'
";

$countParams = [];
$countTypes = "";

if ($search !== "") {

    $countSql .= " AND (image_title LIKE ? OR model_name LIKE ?)";

    $countParams[] = "%$search%";
    $countParams[] = "%$search%";
    $countTypes .= "ss";
}

if ($modelFilter !== "") {

    $countSql .= " AND model_name = ?";

    $countParams[] = $modelFilter;
    $countTypes .= "s";
}

if ($promptFilter !== "") {

    $countSql .= " AND prompt_id = ?";

    $countParams[] = $promptFilter;
    $countTypes .= "i";
}

if(isset($_GET['favorites'])){

    $countSql .= " AND favorite = 1";

}

$countStmt = $conn->prepare($countSql);

if(!empty($countParams)){

    $countStmt->bind_param($countTypes, ...$countParams);

}

$countStmt->execute();

$totalImages = $countStmt->get_result()->fetch_row()[0];

$totalPages = max(1, ceil($totalImages / $imagesPerPage));

$sql = "
    SELECT
        image_id,
        prompt_id,
        image_title,
        image_path,
        model_name,
        generation_date,
        favorite
    FROM generated_images
    WHERE generation_status = 'success'
";


$params = [];
$types = "";


if ($search !== "") {

    $sql .= " AND (
        image_title LIKE ?
        OR model_name LIKE ?
    ) ";

    $params[] = "%$search%";
    $params[] = "%$search%";

    $types .= "ss";

}


if ($modelFilter !== "") {

    $sql .= " AND model_name = ? ";

    $params[] = $modelFilter;
    $types .= "s";

}

if ($promptFilter !== "") {

    $sql .= " AND prompt_id = ? ";

    $params[] = $promptFilter;
    $types .= "i";

}

if(isset($_GET['favorites'])) {

    $sql .= " AND favorite = 1 ";

}


$sql .= "
    ORDER BY generation_date DESC
    LIMIT ?, ?
";


$stmt = $conn->prepare($sql);

$params[] = $offset;
$params[] = $imagesPerPage;

$types .= "ii";

$stmt->bind_param(
    $types,
    ...$params
);


$stmt->execute();

$historyQuery = $stmt->get_result();


while ($history = $historyQuery->fetch_assoc()) {

    $previousImages[] = $history;

}
/*
|--------------------------------------------------------------------------
| Default Variables
|--------------------------------------------------------------------------
*/

$imageUrl = null;
$errorMessage = null;
$modelName = "";
$latestImageId = null;
$latestFavorite = false;


/*
|--------------------------------------------------------------------------
| Generate Image
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $prompt_id = intval($_POST['prompt_id']);
    $model = $_POST['model'] ?? "pollinations";

    $stmt = $conn->prepare("
        SELECT prompt_text
        FROM prompts
        WHERE prompt_id = ?
    ");

    $stmt->bind_param(
            "i",
            $prompt_id
    );
    $stmt->execute();

    $promptResult = $stmt->get_result();
    $promptRow = $promptResult->fetch_assoc();

    if ($promptRow) {

        try {

            $promptText = trim($promptRow['prompt_text']);

            /*
            |--------------------------------------------------------------------------
            | Generate Image Title
            |--------------------------------------------------------------------------
            */

            $imageTitle = trim($promptText);

            $imageTitle = preg_replace('/^(an?|the)\s+/i', '', $imageTitle);

            $imageTitle = preg_replace('/,.*$/', '', $imageTitle);

            $imageTitle = preg_replace('/\b(with|wearing|holding|during|standing|sitting|looking|in|at|on|under|beside|near)\b.*$/i', '', $imageTitle);

            $imageTitle = ucwords(trim($imageTitle));

            if (strlen($imageTitle) > 40) {

                $imageTitle = substr($imageTitle, 0, 40);

            }

            switch ($model) {

                /*
                |--------------------------------------------------------------------------
                | Pollinations AI
                |--------------------------------------------------------------------------
                */

                case "pollinations":

                    $seed = random_int(1, 999999999);

                    $pollinationsUrl =
                        "https://image.pollinations.ai/prompt/"
                        . urlencode($promptText)
                        . "?seed="
                        . $seed;

                    // Download the image
                    $imageData = @file_get_contents($pollinationsUrl);

                    if ($imageData === false) {
                        throw new Exception("Unable to download image from Pollinations.");
                    }

                    if($imageData === false){

                        throw new Exception("Failed to download image from Pollinations.");

                    }

                    // Make sure uploads folder exists
                    if(!is_dir("uploads")){

                        mkdir("uploads", 0777, true);

                    }

                    // Create unique filename
                    $filename =
                        "uploads/"
                        . time()
                        . "_"
                        . uniqid()
                        . ".png";

                    // Save image locally
                    file_put_contents($filename, $imageData);

                    // Store local path instead of Pollinations URL
                    $imageUrl = $filename;

                    $modelName = "Pollinations AI";

                    break;


                /*
                |--------------------------------------------------------------------------
                | Cloudflare FLUX
                |--------------------------------------------------------------------------
                */

                case "cloudflare":

                    $apiUrl =
                            "https://api.cloudflare.com/client/v4/accounts/"
                            . CF_ACCOUNT_ID
                            . "/ai/run/@cf/black-forest-labs/flux-1-schnell";

                    $payload = json_encode([
                            "prompt" => $promptText
                    ]);

                    $ch = curl_init();

                    curl_setopt_array($ch, [
                            CURLOPT_URL => $apiUrl,
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_POST => true,
                            CURLOPT_HTTPHEADER => [
                                    "Authorization: Bearer " . CF_API_TOKEN,
                                    "Content-Type: application/json"
                            ],
                            CURLOPT_POSTFIELDS => $payload
                    ]);

                    $response = curl_exec($ch);

                    if (curl_errno($ch)) {
                        throw new Exception(curl_error($ch));
                    }

                    curl_close($ch);

                    $cloudflare = json_decode($response, true);

                    if (!isset($cloudflare['result']['image'])) {
                        throw new Exception("Cloudflare did not return an image.");
                    }

                    $imageUrl =
                            "data:image/jpeg;base64,"
                            . $cloudflare['result']['image'];

                    $modelName = "Cloudflare FLUX";

                    break;


                default:

                    throw new Exception("Invalid AI model selected.");

            }


            /*
            |--------------------------------------------------------------------------
            | Save Successful Generation
            |--------------------------------------------------------------------------
            */

            $status = "success";

            $insertStmt = $conn->prepare("
                INSERT INTO generated_images
                (
                    prompt_id,
                    image_title,
                    model_name,
                    image_path,
                    generation_status
                )
                VALUES (?, ?, ?, ?, ?)
            ");

            $insertStmt->bind_param(
                     "issss",
                     $prompt_id,
                     $imageTitle,
                     $modelName,
                     $imageUrl,
                     $status
             );

            $insertStmt->execute();
            $latestImageId = $conn->insert_id;

            /*
            |--------------------------------------------------------------------------
            | Refresh Gallery
            |--------------------------------------------------------------------------
            */

            $previousImages = [];

            $historyQuery = $conn->query("
                SELECT
                    image_id,
                    image_title,
                    image_path,
                    model_name,
                    generation_date,
                    favorite
                FROM generated_images
                ORDER BY generation_date DESC
                LIMIT 30
            ");

            while ($history = $historyQuery->fetch_assoc()) {
                $previousImages[] = $history;
            }

            $totalImages++;

        } catch (Exception $e) {

            $errorMessage = "Image generation failed: " . $e->getMessage();

            $modelName =
                    ($model === "cloudflare")
                            ? "Cloudflare FLUX"
                            : "Pollinations AI";

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

    <title>AI Image Generator</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <style>

        body{
            background:#f4f6fb;
        }

        .page-wrapper{
            max-width:1200px;
            margin:40px auto;
        }

        .generator-card{
            border:none;
            border-radius:18px;
            box-shadow:0 8px 25px rgba(0,0,0,.08);
        }

        .stat-card{
            border:none;
            border-radius:16px;
            box-shadow:0 5px 18px rgba(0,0,0,.08);
            transition:.2s;
        }

        .stat-card:hover{
            transform:translateY(-3px);
        }

        .stat-number{
            font-size:1.5rem;
            font-weight:700;
        }

        .hero-title{
            font-weight:700;
            font-size:2rem;
        }

        .hero-subtitle{
            color:#6c757d;
        }

        .generate-btn{

            background:linear-gradient(135deg,#7c3aed,#4f46e5);
            color:white;
            border:none;

            font-weight:600;

            padding:10px 20px;

            width:auto;

            border-radius:10px;

        }

        .generate-btn:hover{
            opacity:.95;
        }

        .generated-image{

            display:block;
            margin:auto;

            max-width:700px;
            width:100%;
            max-height:550px;

            object-fit:contain;

            border-radius:15px;

            box-shadow:0 8px 25px rgba(0,0,0,.18);

        }

        .history-image{

            width:100%;
            height:220px;
            object-fit:cover;

        }

        .history-card{

            transition:.2s;

        }

        .history-card:hover{

            transform:translateY(-4px);

        }

        .loading-overlay{

            display:none;
            position:fixed;

            inset:0;

            background:rgba(255,255,255,.94);

            z-index:9999;

            justify-content:center;
            align-items:center;
            flex-direction:column;

        }

        .spinner{

            width:70px;
            height:70px;

            border:8px solid #ddd;
            border-top:8px solid #6f42c1;

            border-radius:50%;

            animation:spin 1s linear infinite;

        }

        @keyframes spin{

            from{
                transform:rotate(0deg);
            }

            to{
                transform:rotate(360deg);
            }

        }
    /* History Modal Navigation Buttons */

    #prevImage,
    #nextImage {

        transition: .2s;

    }

    #prevImage:hover,
    #nextImage:hover {

        opacity: .95 !important;
        transform: translateY(-50%) scale(1.1);

    }

    </style>

</head>

<body>


<div class="container mt-4">

    <a href="index.php" class="btn btn-outline-secondary">

        <i class="bi bi-arrow-left"></i>

        Back to Dashboard

    </a>

</div>


<div class="loading-overlay" id="loading">

    <div class="spinner"></div>

    <h4 class="mt-4">

        Generating AI Image...

    </h4>

    <p class="text-muted">

        This normally takes around 20–30 seconds.

    </p>

</div>



<div class="container page-wrapper">


    <div class="text-center mb-5">

        <h1 class="hero-title">

            🎨 AI Image Generator

        </h1>

        <p class="hero-subtitle">

            Generate images from your saved prompts using multiple AI models.

        </p>

    </div>



    <div class="row mb-4">


        <div class="col-md-4 mb-3">

            <div class="card stat-card">

                <div class="card-body text-center">

                    <div class="stat-number text-primary">

                        <?= $totalImages ?>

                    </div>

                    <small class="text-muted">

                        Images Generated

                    </small>

                </div>

            </div>

        </div>



        <div class="col-md-4 mb-3">

            <div class="card stat-card">

                <div class="card-body text-center">

                    <div class="stat-number text-warning">

                        <?= $totalFavorites ?>

                    </div>

                    <small class="text-muted">

                        Favorites

                    </small>

                </div>

            </div>

        </div>



        <div class="col-md-4 mb-3">

            <div class="card stat-card">

                <div class="card-body text-center">

                    <div class="stat-number text-success">

                        <?= $totalModels ?>

                    </div>

                    <small class="text-muted">

                        AI Models

                    </small>

                </div>

            </div>

        </div>


    </div>



    <div id="generatorCard" class="card generator-card">


        <div class="card-body p-4">


            <h3 class="mb-4">

                Generate New Image

            </h3>


            <form method="POST" onsubmit="showLoading()">


                <div class="row">


                    <div class="col-md-7">


                        <label class="form-label">

                            Select Prompt

                        </label>

                        <select
                                class="form-select"
                                name="prompt_id"
                                required>

                            <?php while ($prompt = $promptsResult->fetch_assoc()): ?>

                                <option
                                    value="<?= $prompt['prompt_id'] ?>"
                                    <?= ($selectedPrompt == $prompt['prompt_id']) ? 'selected' : '' ?>
                                >

                                    <?= htmlspecialchars($prompt['title']) ?>

                                </option>

                            <?php endwhile; ?>

                        </select>

                    </div>



                    <div class="col-md-5">


                        <label class="form-label">

                            AI Model

                        </label>

                        <select
                                class="form-select"
                                name="model">

                            <option value="pollinations">

                                Pollinations AI

                            </option>

                            <option value="cloudflare">

                                Cloudflare FLUX

                            </option>

                        </select>

                    </div>


                </div>


                <div class="text-center mt-4">

                    <button class="btn generate-btn">

                        <i class="bi bi-stars"></i>

                        Generate Image

                    </button>

                </div>

            </form>

        </div>

    </div>
</div>

</div>

<?php if ($errorMessage): ?>

    <div class="alert alert-danger mt-4 shadow-sm">

        <strong>Generation Failed</strong><br>

        <?= htmlspecialchars($errorMessage) ?>

    </div>

<?php endif; ?>



<?php if ($imageUrl): ?>

    <div id="latestImageCard" class="card generator-card mt-4">

        <div class="card-body">

            <div class="d-flex justify-content-between align-items-center mb-3">

                <h3 class="mb-0">

                    ✨ Latest Generated Image

                </h3>

                <span class="badge bg-primary fs-6">

                        <?= htmlspecialchars($modelName) ?>

                    </span>

            </div>

            <img

                    src="<?= htmlspecialchars($imageUrl) ?>"

                    class="generated-image"

                    alt="Generated Image"

                    data-bs-toggle="modal"

                    data-bs-target="#imageModal"

                    style="cursor:pointer;"

            >

            <div class="row mt-4">

                <div class="col-md-12">

                    <h4 class="mb-1">

                        <?= htmlspecialchars($imageTitle) ?>

                    </h4>

                    <p class="text-muted mb-0">

                        <?= htmlspecialchars($modelName) ?>

                    </p>

                    <small class="text-muted">

                        Generated <?= date("F j, Y g:i A") ?>

                    </small>

                </div>

            </div>

            <div class="d-flex flex-wrap gap-2">

                <a

                        href="<?= htmlspecialchars($imageUrl) ?>"

                        download="generated-image.jpg"

                        class="btn btn-success"

                >

                    <i class="bi bi-download"></i>

                    Download

                </a>

               <button
                   type="button"
                   id="latestFavorite"
                   class="btn btn-outline-warning"
                   data-id="<?= $latestImageId ?>"
               >

                   <?php if($latestFavorite): ?>

                       ⭐ Favorited

                   <?php else: ?>

                       ☆ Favorite

                   <?php endif; ?>

               </button>

                <button
                    type="button"
                    id="latestDelete"
                    class="btn btn-danger"
                    data-id="<?= $latestImageId ?>"
                >

                    <i class="bi bi-trash"></i>

                    Delete

                </button>

                <button

                        class="btn btn-primary"

                        data-bs-toggle="modal"

                        data-bs-target="#imageModal"

                >

                    <i class="bi bi-arrows-fullscreen"></i>

                    View Full Size

                </button>

            </div>

        </div>

    </div>

<?php endif; ?>



<div class="card generator-card mt-4">

    <div class="card-body">

        <div class="d-flex justify-content-between align-items-center mb-4">

            <h3 class="mb-0">

                🖼 Previous Generations

            </h3>

            <span class="badge bg-secondary">

                    <?= count($previousImages) ?> Images

                </span>

        </div>
        <form method="GET" class="row g-2 mb-4">

            <div class="col-md-4">

                <input
                        type="text"
                        name="search"
                        class="form-control"
                        placeholder="Search images..."
                        value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                >

            </div>

        <div class="col-md-3">

            <select
                name="filter_prompt"
                class="form-select"
            >

                <option value="">
                    All Prompts
                </option>

                <?php foreach($promptList as $prompt): ?>

                    <option
                        value="<?= $prompt['prompt_id'] ?>"
                        <?= ($promptFilter == $prompt['prompt_id']) ? 'selected' : '' ?>
                    >

                        <?= htmlspecialchars($prompt['title']) ?>

                    </option>

                <?php endforeach; ?>

            </select>

        </div>


            <div class="col-md-4">

                <select name="filter_model" class="form-select">

                    <option value="">
                        All AI Models
                    </option>


                    <option value="Pollinations AI"
                            <?= (($_GET['filter_model'] ?? '') == "Pollinations AI") ? "selected" : "" ?>>

                        Pollinations AI

                    </option>


                    <option value="Cloudflare FLUX"
                            <?= (($_GET['filter_model'] ?? '') == "Cloudflare FLUX") ? "selected" : "" ?>>

                        Cloudflare FLUX

                    </option>


                </select>

            </div>


            <div class="col-md-4">

                <div class="form-check mt-2">

                    <input
                            class="form-check-input"
                            type="checkbox"
                            name="favorites"
                            value="1"
                            <?= isset($_GET['favorites']) ? "checked" : "" ?>
                    >

                    <label class="form-check-label">

                        ⭐ Show Favorites Only

                    </label>

                </div>

            </div>


            <div class="col-md-2">

                <button class="btn btn-primary w-100">

                    🔎 Search

                </button>

            </div>


            <div class="col-md-2">

                <a href="generate.php" class="btn btn-secondary w-100">

                    Reset

                </a>

            </div>


        </form>

        <?php if (empty($previousImages)): ?>

            <div class="text-center text-muted py-5">

                <i class="bi bi-images fs-1"></i>

                <h5 class="mt-3">

                    No images generated yet.

                </h5>

                <p>

                    Generate your first AI image above.

                </p>

            </div>

        <?php else: ?>

            <div class="row">

                <?php foreach ($previousImages as $index => $history): ?>

                    <div class="col-lg-4 col-md-6 mb-4">

                        <div
                            id="historyCard<?= $history['image_id'] ?>"
                            class="card history-card shadow-sm h-100"
                        >

                            <?php if ($history['favorite']): ?>

                                <div
                                    class="position-absolute top-0 end-0 p-2"
                                    style="font-size:28px; z-index:10;"
                                >

                                    ❤️

                                </div>

                            <?php endif; ?>

                            <img
                                src="<?= htmlspecialchars($history['image_path']) ?>"
                                class="history-image card-img-top"

                                data-index="<?= $index ?>"

                                onclick="openHistoryModal(<?= $index ?>)"

                                style="cursor:pointer;"

                                alt="Previous generation"
                            >

                            <div class="card-body">

                                <h5 class="fw-bold mb-1">

                                    <?= htmlspecialchars(
                                        !empty($history['image_title'])
                                            ? $history['image_title']
                                            : 'Generated Image'
                                    ) ?>

                                </h5>

                                <p class="text-muted mb-1">

                                    <?= htmlspecialchars($history['model_name']) ?>

                                </p>

                                <small class="text-muted d-block mb-3">

                                    <?= date("M j, Y g:i A", strtotime($history['generation_date'])) ?>

                                </small>

                                <div class="d-grid gap-2">

                                    <a

                                            href="<?= htmlspecialchars($history['image_path']) ?>"

                                            download

                                            class="btn btn-success btn-sm"

                                    >

                                        <i class="bi bi-download"></i>

                                        Download

                                    </a>

                                    <button
                                        class="btn btn-outline-warning btn-sm favoriteCard"
                                        data-id="<?= $history['image_id'] ?>"
                                    >

                                        <?php if($history['favorite']): ?>

                                            ⭐ Favorited

                                        <?php else: ?>

                                            ☆ Favorite

                                        <?php endif; ?>

                                    </button>


                                   <button
                                       class="btn btn-outline-danger btn-sm deleteCard"
                                       data-id="<?= $history['image_id'] ?>"
                                   >

                                       <i class="bi bi-trash"></i>

                                       Delete

                                   </button>

                                </div>

                            </div>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>
        <div class="d-flex justify-content-between align-items-center mt-4">

            <?php if ($page > 1): ?>

                <a
                    class="btn btn-outline-primary"
                    href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&filter_model=<?= urlencode($modelFilter) ?>"
                >
                    ← Previous
                </a>

            <?php else: ?>

                <button class="btn btn-outline-secondary" disabled>

                    ← Previous

                </button>

            <?php endif; ?>



            <span class="fw-semibold">

                Page <?= $page ?> of <?= $totalPages ?>

            </span>



            <?php if ($page < $totalPages): ?>

                <a
                    class="btn btn-outline-primary"
                    href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&filter_model=<?= urlencode($modelFilter) ?>"
                >
                    Next →
                </a>

            <?php else: ?>

                <button class="btn btn-outline-secondary" disabled>

                    Next →

                </button>

            <?php endif; ?>

        </div>

        <?php endif; ?>

    </div>

</div>

</div>



<!-- Latest Generated Image Modal -->

<div class="modal fade" id="imageModal" tabindex="-1">

    <div class="modal-dialog modal-xl modal-dialog-centered">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modalTitle">

                    Latest Generated Image

                </h5>

                <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal">
                </button>

            </div>

            <div
                class="modal-body d-flex justify-content-center align-items-center"
                style="height:85vh; background:#111;"
            >

                <?php if ($imageUrl): ?>

                    <img
                            src="<?= htmlspecialchars($imageUrl) ?>"
                            class="img-fluid rounded"
                            style="max-height:80vh;"
                            alt="Generated Image">

                <?php endif; ?>

            </div>

        </div>

    </div>

</div>



<!-- Previous Image Preview Modal -->

<div class="modal fade" id="historyModal" tabindex="-1">

    <div class="modal-dialog modal-fullscreen modal-dialog-centered">

        <div class="modal-content bg-dark border-0">

            <div class="modal-header border-0">

                <h5 class="modalTitle text-white">

                    Previous Generation

                </h5>

                <button
                        type="button"
                        class="btn-close btn-close-white"
                        data-bs-dismiss="modal">
                </button>

            </div>

            <div class="modal-body position-relative text-center p-0">

                <!-- Previous -->

                <button
                        id="prevImage"
                        class="btn btn-light position-absolute top-50 start-0 translate-middle-y ms-3 rounded-circle opacity-50"
                        style="width:60px;height:60px;font-size:30px;z-index:1000;"
                >

                    ❮

                </button>

                <!-- Image -->

                <img
                    id="historyPreview"
                    class="rounded"
                    src=""
                    alt="History Preview"
                    style="
                        max-width:100%;
                        max-height:100%;
                        object-fit:contain;;
                        background:#111;
                    "
                >

                <div class="text-white mt-4">

                    <h3 id="modalTitle"></h3>

                    <p id="modalModel" class="mb-1"></p>

                    <small id="modalDate" class="text-light"></small>

                </div>
            <div class="d-flex justify-content-center flex-wrap gap-2 mt-4">

                <a
                    id="modalDownload"
                    href="#"
                    download
                    class="btn btn-success"
                >
                    ⬇ Download
                </a>

               <button
                   id="modalFavorite"
                   class="btn btn-warning"
               >
                   ⭐ Favorite
               </button>

                <button
                    id="modalDelete"
                    class="btn btn-danger"
                >
                    🗑 Delete
                </button>

            </div>

                <!-- Next -->

                <button
                        id="nextImage"
                        class="btn btn-light position-absolute top-50 end-0 translate-middle-y me-3 rounded-circle opacity-50"
                        style="width:60px;height:60px;font-size:30px;z-index:1000;"
                >

                    ❯

                </button>

            </div>

        </div>

    </div>

</div>



<script>

function showLoading() {

    document.getElementById("loading").style.display = "flex";

    return true;

}

const galleryImages = [

<?php foreach($previousImages as $history): ?>

{
    id: <?= $history['image_id'] ?>,
    image: "<?= htmlspecialchars($history['image_path']) ?>",
    title: "<?= htmlspecialchars($history['image_title']) ?>",
    model: "<?= htmlspecialchars($history['model_name']) ?>",
    date: "<?= date("F j, Y g:i A", strtotime($history['generation_date'])) ?>",
    favorite: <?= $history['favorite'] ? 'true' : 'false' ?>
},

<?php endforeach; ?>

];

function openHistoryModal(index){

    showHistoryImage(index);

    const modal =
        bootstrap.Modal.getOrCreateInstance(
            document.getElementById("historyModal")
        );

    modal.show();

}

let currentImage = 0;

function showHistoryImage(index){

    currentImage = index;

    const preview = document.getElementById("historyPreview");

    preview.style.opacity = "0.3";

    preview.onload = function(){

        preview.style.opacity = "1";

    };

    preview.src = galleryImages[currentImage].image;

    document.getElementById("modalTitle").textContent =
        galleryImages[currentImage].title;

    document.getElementById("modalModel").textContent =
        galleryImages[currentImage].model;

    document.getElementById("modalDate").textContent =
        galleryImages[currentImage].date;
   document.getElementById("modalDownload").href =
       galleryImages[currentImage].image;

   document.getElementById("modalDownload").download =
       galleryImages[currentImage].title
           .replace(/[^\w\s-]/g,"")
           .replace(/\s+/g,"_")
           + ".jpg";

   const favoriteButton = document.getElementById("modalFavorite");

   if(galleryImages[currentImage].favorite){

       favoriteButton.innerHTML = "❤️ Favorited";

   }else{

       favoriteButton.innerHTML = "🤍 Favorite";

   }

}

document.getElementById("prevImage").onclick = function(){

    currentImage--;

    if(currentImage < 0){

        currentImage = galleryImages.length - 1;

    }

    showHistoryImage(currentImage);

};

document.getElementById("nextImage").onclick = function(){

    currentImage++;

    if(currentImage >= galleryImages.length){

        currentImage = 0;

    }

    showHistoryImage(currentImage);

};

document.addEventListener("keydown", function(e){

    if(!document.getElementById("historyModal").classList.contains("show"))
        return;

    if(e.key === "ArrowLeft"){

        document.getElementById("prevImage").click();

    }

    if(e.key === "ArrowRight"){

        document.getElementById("nextImage").click();

    }

});
document.getElementById("modalFavorite").onclick = function(){

    fetch(
        "favorite_image.php?id=" + galleryImages[currentImage].id
    )

    .then(response => response.json())

    .then(data => {

        galleryImages[currentImage].favorite = data.favorite;

        if(data.favorite){

            this.innerHTML = "❤️ Favorited";

        }else{

            this.innerHTML = "🤍 Favorite";

        }

    });

};

document.getElementById("modalDelete").onclick = function(){

    if(!confirm("Delete this image?")){

        return;

    }

    fetch(
        "delete_image.php?id=" + galleryImages[currentImage].id
    )

    .then(response => response.json())

    .then(data => {

        if(data.success){

            // Remove the image from the gallery array
            galleryImages.splice(currentImage, 1);

            // If there are no images left, close the modal
            if(galleryImages.length === 0){

                bootstrap.Modal.getInstance(
                    document.getElementById("historyModal")
                ).hide();

                location.reload();

                return;

            }

            // If we deleted the last image, go back one
            if(currentImage >= galleryImages.length){

                currentImage = galleryImages.length - 1;

            }

            // Show the next available image
            showHistoryImage(currentImage);

        }

    });

};
document.querySelectorAll(".deleteCard").forEach(button => {

    button.addEventListener("click", function(){

        if(!confirm("Delete this image?")){

            return;

        }

        const imageId = this.dataset.id;

        fetch("delete_image.php?id=" + imageId)

        .then(response => response.json())

        .then(data => {

            if(data.success){

                this.closest(".col-lg-4").remove();

            }

        });

    });

});

document.querySelectorAll(".favoriteCard").forEach(button => {

    button.addEventListener("click", function(){

        const imageId = this.dataset.id;

        fetch("favorite_image.php?id=" + imageId)

        .then(response => response.json())

        .then(data => {

            if(data.favorite){

                this.innerHTML = "⭐ Favorited";

            }else{

                this.innerHTML = "☆ Favorite";

            }

        });

    });

});

window.addEventListener("load", function(){

    const params = new URLSearchParams(window.location.search);

    if(params.has("prompt")){

        document.getElementById("generatorCard").scrollIntoView({
            behavior: "smooth",
            block: "start"
        });

    }

});

const latestFavorite = document.getElementById("latestFavorite");

if(latestFavorite){

    latestFavorite.addEventListener("click", function(){

        fetch("favorite_image.php?id=" + this.dataset.id)

        .then(response => response.json())

        .then(data => {

            if(data.favorite){

                this.innerHTML = "⭐ Favorited";

            }else{

                this.innerHTML = "☆ Favorite";

            }

        });

    });

}

const latestDelete = document.getElementById("latestDelete");

if(latestDelete){

    latestDelete.addEventListener("click", function(){

        if(!confirm("Delete this image?")){

            return;

        }

        fetch("delete_image.php?id=" + this.dataset.id)

        .then(response => response.json())

        .then(data => {

            if(data.success){

                // Remove the latest generated image card
                document.getElementById("latestImageCard").remove();

                // Remove the same image from Previous Generations
                const historyCard = document.getElementById(
                    "historyCard" + this.dataset.id
                );

                if(historyCard){

                    historyCard.closest(".col-lg-4").remove();

                }

            }

        });

    });

}

</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>