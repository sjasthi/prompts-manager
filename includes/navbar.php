<?php

$currentPage = basename($_SERVER['PHP_SELF']);
$scriptDirectory = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$basePath = basename($scriptDirectory) === 'pages'
    ? dirname($scriptDirectory)
    : $scriptDirectory;
$basePath = rtrim(str_replace('\\', '/', $basePath), '/');

?>
<nav class="navbar navbar-expand-lg navbar-dark custom-navbar shadow">

    <div class="container">

        <a class="navbar-brand d-flex align-items-center"
           href="<?= htmlspecialchars($basePath) ?>/index.php">

            <div class="logo-box me-3">

                IPM

            </div>

            <div>

                <div class="brand-title">

                    Image Prompt Manager

                </div>

                <small class="brand-subtitle">

                    AI Prompt Workspace

                </small>

            </div>

        </a>

        <button
                class="navbar-toggler"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#navbarNav">

            <span class="navbar-toggler-icon"></span>

        </button>

        <div class="collapse navbar-collapse"
             id="navbarNav">

            <ul class="navbar-nav ms-auto">

                <li class="nav-item">
                    <a
                        class="nav-link <?= $currentPage == 'index.php' ? 'active' : '' ?>"
                        href="<?= htmlspecialchars($basePath) ?>/index.php"
                    >
                        <i class="bi bi-house-door me-1"></i>
                        Home
                    </a>
                </li>

                <li class="nav-item">
                    <a
                        class="nav-link <?= $currentPage == 'prompt_library.php' ? 'active' : '' ?>"
                        href="<?= htmlspecialchars($basePath) ?>/pages/prompt_library.php"
                    >
                        <i class="bi bi-collection me-1"></i>
                        Prompt Library
                    </a>
                </li>

                <li class="nav-item">
                    <a
                        class="nav-link <?= $currentPage == 'create_prompt.php' ? 'active' : '' ?>"
                        href="<?= htmlspecialchars($basePath) ?>/pages/create_prompt.php"
                    >
                        <i class="bi bi-pencil-square me-1"></i>
                        Create Prompt
                    </a>
                </li>

                <li class="nav-item">
                    <a
                        class="nav-link <?= $currentPage == 'generate.php' ? 'active' : '' ?>"
                        href="<?= htmlspecialchars($basePath) ?>/generate.php"
                    >
                        <i class="bi bi-image me-1"></i>
                        Generate
                    </a>
                </li>

                <li class="nav-item">
                    <a
                        class="nav-link <?= in_array($currentPage, [
                            'compare_models.php',
                            'comparison_history.php'
                        ]) ? 'active' : '' ?>"
                        href="<?= htmlspecialchars($basePath) ?>/pages/compare_models.php"
                    >
                        <i class="bi bi-columns-gap me-1"></i>
                        Compare
                    </a>
                </li>

                <li class="nav-item">
                    <a
                        class="nav-link <?= $currentPage == 'image_to_prompt.php' ? 'active' : '' ?>"
                        href="<?= htmlspecialchars($basePath) ?>/pages/image_to_prompt.php"
                    >
                        <i class="bi bi-camera me-1"></i>
                        Image→Prompt
                    </a>
                </li>

            </ul>

        </div>

    </div>

</nav>
