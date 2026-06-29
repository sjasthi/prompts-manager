<?php
require_once '../includes/database.php';
require_once '../includes/config.php';

$conn = getConnection();
$editPrompt = null;
$message = '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
// Handle DELETE
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $conn->query("DELETE FROM prompts WHERE prompt_id = $id");
    header("Location: prompts.php?msg=deleted");
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
        // UPDATE existing prompt
        $conn->query("
            UPDATE prompts
            SET title='$title', prompt_text='$prompt_text', category='$category', tags='$tags',
                version_number = version_number + 1,
                updated_at = NOW()
            WHERE prompt_id = $id
        ");

        // Save old version to prompt_versions
        $versionResult = $conn->query("SELECT version_number, prompt_text FROM prompts WHERE prompt_id = $id");
        // (version already incremented above; log the new text)
        $conn->query("
            INSERT INTO prompt_versions (prompt_id, version_number, version_text)
            VALUES ($id, (SELECT version_number FROM prompts WHERE prompt_id = $id), '$prompt_text')
        ");

        header("Location: prompts.php?msg=updated");
    } else {
        // INSERT new prompt
        $conn->query("
            INSERT INTO prompts (title, prompt_text, category, tags)
            VALUES ('$title', '$prompt_text', '$category', '$tags')
        ");
        $newId = $conn->insert_id;

        // Save initial version
        $conn->query("
            INSERT INTO prompt_versions (prompt_id, version_number, version_text)
            VALUES ($newId, 1, '$prompt_text')
        ");

        header("Location: prompts.php?msg=created");
    }
    exit;
}

// Load prompt for editing
if (isset($_GET['edit'])) {
    $id = (int) $_GET['edit'];
    $result = $conn->query("SELECT * FROM prompts WHERE prompt_id = $id");
    $editPrompt = $result->fetch_assoc();
}

// Flash messages
if (isset($_GET['msg'])) {
    $msgs = [
        'created' => ['success', 'Prompt created successfully.'],
        'updated' => ['success', 'Prompt updated successfully.'],
        'deleted' => ['warning', 'Prompt deleted.'],
    ];
    if (isset($msgs[$_GET['msg']])) {
        [$type, $text] = $msgs[$_GET['msg']];
        $message = "<div class='alert alert-$type alert-dismissible fade show' role='alert'>
            $text
            <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
        </div>";
    }
}
$searchSql = "";

if ($search != "") {
    $safeSearch = $conn->real_escape_string($search);
    $searchSql = " AND (title LIKE '%$safeSearch%'
                   OR prompt_text LIKE '%$safeSearch%'
                   OR category LIKE '%$safeSearch%'
                   OR tags LIKE '%$safeSearch%')";
}
// Fetch all prompts
$prompts = $conn->query("SELECT * FROM prompts WHERE status = 'active' $searchSql ORDER BY created_at DESC");
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Prompts – Image Prompt Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-4 mb-5">

    <!-- Back link -->
    <a href="../index.php" class="text-decoration-none text-muted small">&larr; Back to Home</a>

    <h2 class="fw-bold mt-2 mb-1">
        <?php echo $editPrompt ? '✏️ Edit Prompt' : '📚 Prompt Library'; ?>
    </h2>
    <p class="text-muted mb-4">
        <?php echo $editPrompt ? 'Update the prompt details below.' : 'Create, edit, and manage your saved prompts.'; ?>
    </p>

    <?php echo $message; ?>

    <!-- CREATE / EDIT FORM -->
    <div class="card shadow-sm border-0 mb-5">
        <div class="card-body">
            <h5 class="card-title mb-3"><?php echo $editPrompt ? 'Edit Prompt' : 'New Prompt'; ?></h5>
            <form method="POST" action="prompts.php">
                <?php if ($editPrompt): ?>
                    <input type="hidden" name="prompt_id" value="<?php echo $editPrompt['prompt_id']; ?>">
                <?php endif; ?>

                <div class="mb-3">
                    <label for="title" class="form-label fw-semibold">Title <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="title" name="title" required
                           placeholder="e.g. Sunset Over Mountains"
                           value="<?php echo $editPrompt ? htmlspecialchars($editPrompt['title']) : ''; ?>">
                </div>

                <div class="mb-3">
                    <label for="prompt_text" class="form-label fw-semibold">Prompt Text <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="prompt_text" name="prompt_text" rows="4" required
                              placeholder="Describe the image you want to generate..."><?php echo $editPrompt ? htmlspecialchars($editPrompt['prompt_text']) : ''; ?></textarea>
                </div>

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
                        <a href="prompts.php" class="btn btn-outline-secondary">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- PROMPT LIST -->
    <?php if (!$editPrompt): ?>
    <h5 class="fw-semibold mb-3">Saved Prompts</h5>
  <form method="GET" action="prompts.php" class="mb-3">
      <div class="input-group">
          <input type="text" name="search" class="form-control"
                 placeholder="Search by title, category, tags, or prompt text"
                 value="<?php echo htmlspecialchars($search); ?>">
          <button class="btn btn-primary" type="submit">Search</button>
          <a href="prompts.php" class="btn btn-outline-secondary">Clear</a>
      </div>
  </form>

    <?php if ($prompts && $prompts->num_rows > 0): ?>
    <div class="table-responsive">
        <table class="table table-hover align-middle bg-white shadow-sm rounded">
            <thead class="table-light">
                <tr>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Tags</th>
                    <th>Version</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $prompts->fetch_assoc()): ?>
                <tr>
                    <td class="fw-semibold"><?php echo htmlspecialchars($row['title']); ?></td>
                    <td><?php echo htmlspecialchars($row['category'] ?? '—'); ?></td>
                    <td>
                        <?php
                        if (!empty($row['tags'])) {
                            foreach (explode(',', $row['tags']) as $tag) {
                                echo '<span class="badge bg-secondary me-1">' . htmlspecialchars(trim($tag)) . '</span>';
                            }
                        } else {
                            echo '—';
                        }
                        ?>
                    </td>
                    <td><span class="badge bg-info text-dark">v<?php echo $row['version_number']; ?></span></td>
                    <td class="text-muted small"><?php echo date('M j, Y', strtotime($row['created_at'])); ?></td>
                    <td>
                        <a href="prompts.php?edit=<?php echo $row['prompt_id']; ?>"
                           class="btn btn-sm btn-outline-primary me-1">Edit</a>
                        <a href="prompts.php?delete=<?php echo $row['prompt_id']; ?>"
                           class="btn btn-sm btn-outline-danger"
                           onclick="return confirm('Delete this prompt?')">Delete</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
        <p class="text-muted">No prompts yet. Create your first one above.</p>
    <?php endif; ?>
    <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
