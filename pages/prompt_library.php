<?php
require_once '../includes/database.php';
require_once '../includes/config.php';
$conn = getConnection();
$message = '';

// Handle DELETE
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $conn->query("DELETE FROM prompts WHERE prompt_id = $id");
    header("Location: prompt_library.php?msg=deleted");
    exit;
}

// Flash messages
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'deleted') {
        $message = "<div class='alert alert-warning alert-dismissible fade show' role='alert'>
            Prompt deleted.
            <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
        </div>";
    }
}

// Fetch all prompts
$prompts = $conn->query("SELECT * FROM prompts WHERE status = 'active' ORDER BY created_at DESC");
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Prompt Library – Image Prompt Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
</head>
<body class="bg-light">

<div class="container mt-4 mb-5">
    <a href="../index.php" class="text-decoration-none text-muted small">&larr; Back to Home</a>
    <h2 class="fw-bold mt-2 mb-1">📚 Prompt Library</h2>
    <p class="text-muted mb-4">Browse, search, and manage all your saved image prompts.</p>

    <?php echo $message; ?>

    <div class="d-flex justify-content-end mb-3">
        <a href="create_prompt.php" class="btn btn-primary">+ Create New Prompt</a>
    </div>

    <?php if ($prompts && $prompts->num_rows > 0): ?>
    <div class="table-responsive">
        <table id="promptsTable" class="table table-hover align-middle bg-white shadow-sm rounded">
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
                        <a href="create_prompt.php?edit=<?php echo $row['prompt_id']; ?>"
                           class="btn btn-sm btn-outline-primary me-1">Edit</a>
                        <a href="prompt_library.php?delete=<?php echo $row['prompt_id']; ?>"
                           class="btn btn-sm btn-outline-danger"
                           onclick="return confirm('Delete this prompt?')">Delete</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
        <p class="text-muted">No prompts yet. <a href="create_prompt.php">Create your first one</a>.</p>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
    $(document).ready(function() {
        $('#promptsTable').DataTable({
            columnDefs: [
                { orderable: false, targets: 5 } // Disable sorting on Actions column
            ],
            language: {
                search: "Search prompts:",
                lengthMenu: "Show _MENU_ prompts per page",
                info: "Showing _START_ to _END_ of _TOTAL_ prompts",
                emptyTable: "No prompts found"
            }
        });
    });
</script>
</body>
</html>
