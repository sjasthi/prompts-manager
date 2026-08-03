<?php
require_once '../includes/database.php';
require_once '../includes/config.php';

$conn = getConnection();
$editPrompt = null;
$message = '';

// Handle RESTORE
if (isset($_GET['restore']) && isset($_GET['prompt_id'])) {
    $promptId  = (int) $_GET['prompt_id'];
    $versionId = (int) $_GET['restore'];

    $vResult = $conn->query("SELECT * FROM prompt_versions WHERE version_id = $versionId AND prompt_id = $promptId");
    $version = $vResult->fetch_assoc();

    if ($version) {
        $restoredText = $conn->real_escape_string($version['version_text']);

        $conn->query("
            UPDATE prompts
            SET prompt_text = '$restoredText',
                version_number = version_number + 1,
                updated_at = NOW()
            WHERE prompt_id = $promptId
        ");

        $conn->query("
            INSERT INTO prompt_versions (prompt_id, version_number, version_text)
            VALUES ($promptId, (SELECT version_number FROM prompts WHERE prompt_id = $promptId), '$restoredText')
        ");

        header("Location: create_prompt.php?edit=$promptId&msg=restored");
        exit;
    }
}

// Handle VERSION DELETE
if (isset($_GET['delete_version'])) {
    $versionId = (int) $_GET['delete_version'];
    $promptId  = (int) $_GET['prompt_id'];
    $conn->query("DELETE FROM prompt_versions WHERE version_id = $versionId");
    header("Location: create_prompt.php?edit=$promptId&msg=version_deleted");
    exit;
}

// Handle CREATE or UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = $conn->real_escape_string(trim($_POST['title']));
    $prompt_text = $conn->real_escape_string(trim($_POST['prompt_text']));
    $category    = $conn->real_escape_string(trim($_POST['category']));
    $tags        = $conn->real_escape_string(trim($_POST['tags']));
    $id          = isset($_POST['prompt_id']) ? (int) $_POST['prompt_id'] : 0;

    if ($id > 0) {
        $conn->query("
            UPDATE prompts
            SET title='$title', prompt_text='$prompt_text', category='$category', tags='$tags',
                version_number = version_number + 1,
                updated_at = NOW()
            WHERE prompt_id = $id
        ");

        $conn->query("
            INSERT INTO prompt_versions (prompt_id, version_number, version_text)
            VALUES ($id, (SELECT version_number FROM prompts WHERE prompt_id = $id), '$prompt_text')
        ");

        header("Location: create_prompt.php?edit=$id&msg=updated");
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

        header("Location: create_prompt.php?msg=created");
    }
    exit;
}

// Load prompt for editing
if (isset($_GET['edit'])) {
    $id = (int) $_GET['edit'];
    $result = $conn->query("SELECT * FROM prompts WHERE prompt_id = $id");
    $editPrompt = $result->fetch_assoc();
}

// Load version history if editing
$versions = [];
if ($editPrompt) {
    $vResult = $conn->query("
        SELECT * FROM prompt_versions
        WHERE prompt_id = {$editPrompt['prompt_id']}
        ORDER BY version_number DESC
    ");
    while ($v = $vResult->fetch_assoc()) {
        $versions[] = $v;
    }
}

// Flash messages
if (isset($_GET['msg'])) {
    $msgs = [
        'created'         => ['success', 'Prompt created successfully.'],
        'updated'         => ['success', 'Prompt updated successfully.'],
        'restored'        => ['info',    'Previous version restored successfully.'],
        'version_deleted' => ['warning', 'Version deleted.'],
    ];
    if (isset($msgs[$_GET['msg']])) {
        [$type, $text] = $msgs[$_GET['msg']];
        $message = "<div class='alert alert-$type alert-dismissible fade show' role='alert'>
            $text
            <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
        </div>";
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create Prompt – Image Prompt Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* Red border on invalid required fields after attempted submit */
        .was-validated .form-control:invalid {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
        }

        /* Character counter color states */
        .char-count { font-size: 0.8rem; }
        .char-count.warning { color: #fd7e14; }
        .char-count.danger  { color: #dc3545; }

        /* Version history text truncation */
        .version-text {
            max-width: 400px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            cursor: pointer;
        }
        .version-text.expanded {
            white-space: normal;
            overflow: visible;
        }
    </style>
</head>
<body>

<?php include '../includes/navbar.php'; ?>

<div class="container py-5">

    <div class="page-card">

    <a href="../index.php" class="text-decoration-none text-muted small">&larr; Back to Home</a>

    <h1 class="page-title mt-3 mb-2">
        <?php echo $editPrompt ? 'Edit Prompt' : 'Create Prompt'; ?>
    </h1>

    <p class="page-subtitle">
        <?php echo $editPrompt
            ? 'Update the prompt details below while keeping the previous versions available.'
            : 'Write a reusable image prompt with a title, category, tags, and version history.';
        ?>
    </p>

    <?php echo $message; ?>

    <!-- CREATE / EDIT FORM -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <h5 class="card-title mb-3"><?php echo $editPrompt ? 'Edit Prompt' : 'New Prompt'; ?></h5>
            <form method="POST" action="create_prompt.php" id="promptForm" novalidate>
                <?php if ($editPrompt): ?>
                    <input type="hidden" name="prompt_id" value="<?php echo $editPrompt['prompt_id']; ?>">
                <?php endif; ?>

                <!-- Title -->
                <div class="mb-3">
                    <label for="title" class="form-label fw-semibold">
                        Title <span class="text-danger">*</span>
                    </label>
                    <input type="text" class="form-control" id="title" name="title"
                           placeholder="e.g. Sunset Over Mountains" required
                           value="<?php echo $editPrompt ? htmlspecialchars($editPrompt['title']) : ''; ?>">
                    <div class="invalid-feedback">Title is required.</div>
                </div>

                <!-- Prompt Text with character counter -->
                <div class="mb-3">
                    <label for="prompt_text" class="form-label fw-semibold">
                        Prompt Text <span class="text-danger">*</span>
                    </label>
                    <textarea class="form-control" id="prompt_text" name="prompt_text"
                              rows="4" required maxlength="1000"
                              placeholder="Describe the image you want to generate..."><?php echo $editPrompt ? htmlspecialchars($editPrompt['prompt_text']) : ''; ?></textarea>
                    <div class="d-flex justify-content-between mt-1">
                        <div class="invalid-feedback d-block" id="prompt_text_error" style="display:none!important;"></div>
                        <small class="char-count text-muted ms-auto" id="charCount">0 / 1000</small>
                    </div>
                </div>

                <!-- Category and Tags -->
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="category" class="form-label fw-semibold">Category</label>
                        <input type="text" class="form-control" id="category" name="category"
                               placeholder="e.g. Landscape, Portrait, Architecture"
                               value="<?php echo $editPrompt ? htmlspecialchars($editPrompt['category']) : ''; ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="tags" class="form-label fw-semibold">Tags</label>
                        <input type="text" class="form-control" id="tags" name="tags"
                               placeholder="e.g. sunset,nature,mountains"
                               value="<?php echo $editPrompt ? htmlspecialchars($editPrompt['tags']) : ''; ?>">
                        <div class="form-text">Comma-separated</div>
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <?php echo $editPrompt ? 'Update Prompt' : 'Save Prompt'; ?>
                    </button>
                    <?php if ($editPrompt): ?>
                        <a href="create_prompt.php" class="btn btn-outline-secondary">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- VERSION HISTORY (only shown when editing) -->
    <?php if ($editPrompt && count($versions) > 0): ?>
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <h5 class="card-title mb-1">🕓 Version History</h5>
            <p class="text-muted small mb-3">Click on prompt text to expand. Restore any previous version or delete unwanted ones.</p>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width:120px;">Version</th>
                            <th>Prompt Text</th>
                            <th style="width:160px;">Saved On</th>
                            <th style="width:160px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($versions as $index => $v): ?>
                        <tr <?php echo $index === 0 ? 'class="table-success"' : ''; ?>>
                            <td>
                                <span class="badge bg-info text-dark">v<?php echo $v['version_number']; ?></span>
                                <?php echo $index === 0 ? '<span class="badge bg-success ms-1">Current</span>' : ''; ?>
                            </td>
                            <td>
                                <span class="version-text text-muted small"
                                      onclick="this.classList.toggle('expanded')"
                                      title="Click to expand">
                                    <?php echo htmlspecialchars($v['version_text']); ?>
                                </span>
                            </td>
                            <td class="text-muted small">
                                <?php echo isset($v['created_at']) ? date('M j, Y g:i A', strtotime($v['created_at'])) : '—'; ?>
                            </td>
                            <td>
                                <?php if ($index !== 0): ?>
                                    <a href="create_prompt.php?restore=<?php echo $v['version_id']; ?>&prompt_id=<?php echo $editPrompt['prompt_id']; ?>"
                                       class="btn btn-sm btn-outline-warning"
                                       onclick="return confirm('Restore this version? The current text will be saved as a new version.')">
                                        Restore
                                    </a>
                                    <a href="create_prompt.php?delete_version=<?php echo $v['version_id']; ?>&prompt_id=<?php echo $editPrompt['prompt_id']; ?>"
                                       class="btn btn-sm btn-outline-danger ms-1"
                                       onclick="return confirm('Delete this version?')">
                                        Delete
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted small">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <a href="prompt_library.php" class="btn btn-outline-secondary">📚 View Prompt Library</a>

 </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Character counter for prompt text
    const textarea = document.getElementById('prompt_text');
    const charCount = document.getElementById('charCount');

    function updateCharCount() {
        const len = textarea.value.length;
        const max = 1000;
        charCount.textContent = len + ' / ' + max;
        charCount.className = 'char-count ms-auto';
        if (len > 900) {
            charCount.classList.add('danger');
        } else if (len > 750) {
            charCount.classList.add('warning');
        } else {
            charCount.classList.add('text-muted');
        }
    }

    textarea.addEventListener('input', updateCharCount);
    updateCharCount(); // run on page load for edit mode

    // Required field validation on submit
    const form = document.getElementById('promptForm');
    form.addEventListener('submit', function (e) {
        if (!form.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
        }
        form.classList.add('was-validated');
    });
</script>
</body>
</html>
