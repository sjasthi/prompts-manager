<?php
require_once 'includes/database.php';
$conn = getConnection();

// Total prompts
$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM prompts
    WHERE status = 'active'
");

$promptCount = (int) $result->fetch_assoc()['total'];


// Total successfully generated images
$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM generated_images
    WHERE generation_status = 'success'
");

$imageCount = (int) $result->fetch_assoc()['total'];


// Total favorite images
$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM generated_images
    WHERE favorite = 1
      AND generation_status = 'success'
");

$favoriteCount = (int) $result->fetch_assoc()['total'];


// Number of AI models that have generated images
$result = $conn->query("
    SELECT COUNT(DISTINCT model_name) AS total
    FROM generated_images
    WHERE generation_status = 'success'
");

$modelCount = (int) $result->fetch_assoc()['total'];

$conn->close();
$pageTitle = "Image Prompt Manager";

$sections = [
    [
        "href"        => "pages/prompt_library.php",
        "icon"        => "bi-collection",
        "title"       => "Prompt Library",
        "desc"        => "Browse, search, and manage all your saved image prompts. Filter by tag, category, or model.",
        "status"      => "Active",
        "badge_class" => "bg-success",
    ],
    [
        "href"        => "pages/create_prompt.php",
        "icon"        => "bi-pencil-square",
        "title"       => "Create Prompt",
        "desc"        => "Write a new image prompt with a title, tags, category, and version history built in.",
        "status"      => "Active",
        "badge_class" => "bg-success",
    ],
    [
        "href"        => "generate.php",
        "icon"        => "bi-image",
        "title"       => "Generate Image",
        "desc"        => "Select a prompt and an AI model to generate images instantly.",
        "status"      => "Active",
        "badge_class" => "bg-success",
    ],
    [
        "href"        => "pages/compare_models.php",
        "icon"        => "bi-columns-gap",
        "title"       => "Compare Models",
        "desc"        => "Run the same prompt across multiple AI models side by side and score their outputs.",
        "status"      => "Active",
        "badge_class" => "bg-success",
    ],
    [
        "href"        => "pages/image_to_prompt.php",
        "icon"        => "bi-camera",
        "title"       => "Image-to-Prompt",
        "desc"        => "Upload an image and let the system reverse-engineer a descriptive prompt you can save and reuse.",
        "status"      => "Active",
        "badge_class" => "bg-success",
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title><?php echo $pageTitle; ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

    <link rel="stylesheet" href="assets/css/style.css">

</head>
<body>
    <?php include 'includes/navbar.php'; ?>

<!-- Under Development Banner -->
<div class="alert alert-warning alert-dismissible fade show text-center rounded-0 mb-0 py-2" role="alert">
    🚧 <strong>Under Development</strong> — This application is actively being built. Features may be incomplete or change without notice.
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>

<div class="container py-5">

    <!-- Header -->
    <div class="hero-card text-center mb-5">

        <div class="hero-logo" aria-hidden="true">

            <div class="hero-logo-symbol">
                <i class="bi bi-stars"></i>
            </div>

            <span>IPM</span>

        </div>

        <h1 class="hero-title">
            Image Prompt Manager
        </h1>

        <p class="hero-tagline">
            Your AI image prompt workspace
        </p>

        <p class="hero-subtitle">

            Create reusable prompts, generate original images,
            compare AI models, and organize your favorite results.

        </p>

    <div class="mt-4">

        <a
            href="generate.php"
            class="btn btn-primary btn-lg quick-btn me-3"
        >
            <i class="bi bi-stars me-2"></i>
            Generate Images
        </a>

        <a
            href="pages/prompt_library.php"
            class="btn btn-outline-primary btn-lg quick-btn"
        >
            <i class="bi bi-collection me-2"></i>
            Prompt Library
        </a>

    </div>
</div>

<div class="row g-4 mb-5">

    <!-- Active Prompts -->
    <div class="col-sm-6 col-lg-3">

        <a
            href="pages/prompt_library.php"
            class="stats-link"
        >
            <div class="stats-card">

                <div class="stats-icon">
                    <i class="bi bi-collection"></i>
                </div>

                <div class="stats-number">
                    <?php echo number_format($promptCount); ?>
                </div>

                <div class="stats-label">
                    Active Prompts
                </div>

                <div class="stats-action">
                    View Library
                    <i class="bi bi-arrow-right"></i>
                </div>

            </div>
        </a>

    </div>


    <!-- Generated Images -->
    <div class="col-sm-6 col-lg-3">

        <a
            href="generate.php#previousGenerations"
            class="stats-link"
        >
            <div class="stats-card">

                <div class="stats-icon">
                    <i class="bi bi-image"></i>
                </div>

                <div class="stats-number">
                    <?php echo number_format($imageCount); ?>
                </div>

                <div class="stats-label">
                    Images Generated
                </div>

                <div class="stats-action">
                    View Gallery
                    <i class="bi bi-arrow-right"></i>
                </div>

            </div>
        </a>

    </div>


    <!-- Favorite Images -->
    <div class="col-sm-6 col-lg-3">

        <a
            href="generate.php?favorites=1#previousGenerations"
            class="stats-link"
        >
            <div class="stats-card">

                <div class="stats-icon">
                    <i class="bi bi-star-fill"></i>
                </div>

                <div class="stats-number">
                    <?php echo number_format($favoriteCount); ?>
                </div>

                <div class="stats-label">
                    Favorite Images
                </div>

                <div class="stats-action">
                    View Favorites
                    <i class="bi bi-arrow-right"></i>
                </div>

            </div>
        </a>

    </div>


    <!-- AI Models -->
    <div class="col-sm-6 col-lg-3">

        <a
            href="pages/compare_models.php"
            class="stats-link"
        >
            <div class="stats-card">

                <div class="stats-icon">
                    <i class="bi bi-cpu"></i>
                </div>

                <div class="stats-number">
                    <?php echo number_format($modelCount); ?>
                </div>

                <div class="stats-label">
                    AI Models Used
                </div>

                <div class="stats-action">
                    Compare Models
                    <i class="bi bi-arrow-right"></i>
                </div>

            </div>
        </a>

    </div>

</div>

<div class="text-center">

    <h2 class="section-title">

        Explore Features

    </h2>

    <p class="section-sub">

        Everything you need to build, organize,
        and evaluate AI image prompts.

    </p>

</div>
    <!-- Feature Cards -->
    <div class="row g-4">
        <?php foreach ($sections as $section): ?>
        <div class="col-md-6 col-lg-4">
            <a href="<?php echo $section['href']; ?>" class="text-decoration-none">
                <div class="card h-100 shadow-sm border-0 hover-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="feature-icon">
                                <i class="bi <?php echo $section['icon']; ?>"></i>
                            </span>
                            <span class="badge <?php echo $section['badge_class']; ?>">
                                <?php echo $section['status']; ?>
                            </span>
                        </div>
                        <h5 class="card-title fw-semibold text-dark"><?php echo $section['title']; ?></h5>
                        <p class="card-text text-muted small"><?php echo $section['desc']; ?></p>
                    </div>
                    <div class="card-footer bg-transparent border-0">
                      <small class="text-primary fw-semibold">
                          Launch →
                      </small>
                    </div>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Footer note -->
<p class="text-center text-muted small mt-5">
    ICS499 Capstone Project &mdash; Image Prompt Manager &mdash; Group 2 2026
</p>
<p class="text-center text-muted">
    📦 <?php echo $promptCount; ?> prompts currently in the library
</p>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
