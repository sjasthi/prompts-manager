<?php
require_once 'includes/database.php';
$conn = getConnection();

$result = $conn->query("SELECT COUNT(*) as total FROM prompts");
$row = $result->fetch_assoc();
$promptCount = $row['total'];
$conn->close();
$pageTitle = "Image Prompt Manager";

$sections = [
    [
        "href"        => "pages/prompts.php",
        "icon"        => "📚",
        "title"       => "Prompt Library",
        "desc"        => "Browse, search, and manage all your saved image prompts. Filter by tag, category, or model.",
        "status"      => "In Progress",
        "badge_class" => "bg-warning text-dark",
    ],
    [
        "href"        => "#",
        "icon"        => "✏️",
        "title"       => "Create Prompt",
        "desc"        => "Write a new image prompt with a title, tags, category, and version history built in.",
        "status"      => "In Progress",
        "badge_class" => "bg-warning text-dark",
    ],
    [
        "href"        => "generate.php",
        "icon"        => "🎨",
        "title"       => "Generate Image",
        "desc"        => "Select a prompt and an AI model to generate images instantly.",
        "status"      => "Active",
        "badge_class" => "bg-success",
    ],
    [
        "href"        => "#",
        "icon"        => "⚖️",
        "title"       => "Compare Models",
        "desc"        => "Run the same prompt across multiple AI models side by side and score their outputs.",
        "status"      => "Coming Soon",
        "badge_class" => "bg-secondary",
    ],
    [
        "href"        => "#",
        "icon"        => "🔍",
        "title"       => "Image-to-Prompt",
        "desc"        => "Upload an image and let the system reverse-engineer a descriptive prompt you can save and reuse.",
        "status"      => "Coming Soon",
        "badge_class" => "bg-secondary",
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
</head>
<body class="bg-light">

<!-- Under Development Banner -->
<div class="alert alert-warning alert-dismissible fade show text-center rounded-0 mb-0 py-2" role="alert">
    🚧 <strong>Under Development</strong> — This application is actively being built. Features may be incomplete or change without notice.
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>

<div class="container mt-5">

    <!-- Header -->
    <div class="text-center mb-5">
        <h1 class="fw-bold">🖼️ Image Prompt Manager</h1>
        <p class="lead text-muted">
            Create, organize, version, and evaluate AI image prompts — all in one place.
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
                            <span class="fs-2"><?php echo $section['icon']; ?></span>
                            <span class="badge <?php echo $section['badge_class']; ?>">
                                <?php echo $section['status']; ?>
                            </span>
                        </div>
                        <h5 class="card-title fw-semibold text-dark"><?php echo $section['title']; ?></h5>
                        <p class="card-text text-muted small"><?php echo $section['desc']; ?></p>
                    </div>
                    <div class="card-footer bg-transparent border-0">
                        <small class="text-primary">Open &rarr;</small>
                    </div>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Footer note -->
<p class="text-center text-muted small mt-5">
    ICS499 Capstone Project &mdash; Image Prompt Manager &mdash; Phase 3
</p>
<p class="text-center text-muted">
    📦 <?php echo $promptCount; ?> prompts currently in the library
</p>

</div>

<style>
    .hover-card {
        transition: transform 0.15s ease, box-shadow 0.15s ease;
        cursor: pointer;
    }
    .hover-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.12) !important;
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
