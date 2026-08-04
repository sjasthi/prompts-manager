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

// Pagination settings
$allowedPageSizes = [25, 50, 100, 200];

$promptsPerPage = isset($_GET['per_page'])
    ? (int) $_GET['per_page']
    : 25;

if (!in_array($promptsPerPage, $allowedPageSizes)) {
    $promptsPerPage = 25;
}

$page = isset($_GET['page'])
    ? max(1, (int) $_GET['page'])
    : 1;

// Search
$search = isset($_GET['search'])
    ? trim($_GET['search'])
    : '';

$whereSql = "status = 'active'";

if ($search !== '') {
    $safeSearch = $conn->real_escape_string($search);

    $whereSql .= " AND (
        title LIKE '%$safeSearch%'
        OR prompt_text LIKE '%$safeSearch%'
        OR category LIKE '%$safeSearch%'
        OR tags LIKE '%$safeSearch%'
    )";
}

// Sorting
$allowedSortColumns = ['title', 'category', 'version_number', 'created_at'];
$sortColumn = isset($_GET['sort']) && in_array($_GET['sort'], $allowedSortColumns)
    ? $_GET['sort']
    : 'created_at';
$sortDir = isset($_GET['dir']) && $_GET['dir'] === 'asc' ? 'ASC' : 'DESC';
$nextDir = $sortDir === 'ASC' ? 'desc' : 'asc';

// Count all active prompts
$countResult = $conn->query("
    SELECT COUNT(*) AS total
    FROM prompts
    WHERE $whereSql
");

$countRow = $countResult->fetch_assoc();
$totalPrompts = (int) $countRow['total'];

$totalPages = max(
    1,
    (int) ceil($totalPrompts / $promptsPerPage)
);

if ($page > $totalPages) {
    $page = $totalPages;
}

$offset = ($page - 1) * $promptsPerPage;

// Load only the prompts for the selected page
$stmt = $conn->prepare("
    SELECT *
    FROM prompts
    WHERE $whereSql
    ORDER BY $sortColumn $sortDir
    LIMIT ? OFFSET ?
");

$stmt->bind_param("ii", $promptsPerPage, $offset);
$stmt->execute();

$prompts = $stmt->get_result();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Prompt Library – Image Prompt Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        #promptsTable {
            min-width: 1050px;
        }

        #promptsTable th,
        #promptsTable td {
            padding: 14px;
            vertical-align: middle;
        }

        #promptsTable th:first-child,
        #promptsTable td:first-child {
            min-width: 230px;
        }

        #promptsTable th:nth-child(3),
        #promptsTable td:nth-child(3) {
            min-width: 190px;
        }

        #promptsTable th:last-child,
        #promptsTable td:last-child {
            min-width: 150px;
            white-space: nowrap;
        }

        #promptsTable tbody tr:hover {
            background-color: #f8f9fa;
        }

        #promptsTable th a {
            text-decoration: none;
            color: inherit;
        }

        #promptsTable th a:hover {
            color: #0d6efd;
        }
    </style>
</head>
<body>
<?php include '../includes/navbar.php'; ?>
<div class="container py-5">
    <div class="page-card">
        <a href="../index.php" class="text-decoration-none text-muted small">
            &larr; Back to Home
        </a>

        <h1 class="page-title mt-3 mb-2">
            Prompt Library
        </h1>

        <p class="page-subtitle">
            Browse, search, and manage all your saved image prompts.
        </p>

        <?php echo $message; ?>

        <form method="GET" action="prompt_library.php" class="card card-body shadow-sm border-0 mb-3">
            <input type="hidden" name="per_page" value="<?php echo $promptsPerPage; ?>">
            <input type="hidden" name="sort" value="<?php echo $sortColumn; ?>">
            <input type="hidden" name="dir" value="<?php echo strtolower($sortDir); ?>">

            <div class="input-group">
                <input
                    type="search"
                    name="search"
                    class="form-control"
                    placeholder="Search by title, prompt text, category, or tags"
                    value="<?php echo htmlspecialchars($search); ?>"
                >
                <button type="submit" class="btn btn-primary">Search</button>
                <?php if ($search !== ''): ?>
                    <a href="prompt_library.php?per_page=<?php echo $promptsPerPage; ?>" class="btn btn-outline-secondary">Clear</a>
                <?php endif; ?>
            </div>
        </form>

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">

            <div class="text-muted">
                Showing
                <strong><?php echo $totalPrompts > 0 ? $offset + 1 : 0; ?> – <?php echo min($offset + $promptsPerPage, $totalPrompts); ?></strong>
                of
                <strong><?php echo $totalPrompts; ?></strong>
                prompts
            </div>

            <div class="d-flex align-items-center gap-2">
                <form method="GET" action="prompt_library.php" class="d-flex align-items-center gap-2">
                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                    <input type="hidden" name="sort" value="<?php echo $sortColumn; ?>">
                    <input type="hidden" name="dir" value="<?php echo strtolower($sortDir); ?>">

                    <label for="per_page" class="text-muted text-nowrap">Show:</label>
                    <select name="per_page" id="per_page" class="form-select" onchange="this.form.submit()">
                        <?php foreach ($allowedPageSizes as $pageSize): ?>
                            <option value="<?php echo $pageSize; ?>" <?php echo $promptsPerPage === $pageSize ? 'selected' : ''; ?>>
                                <?php echo $pageSize; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>

                <a href="create_prompt.php" class="btn btn-primary text-nowrap">+ Create New Prompt</a>
            </div>
        </div>

        <?php if ($prompts && $prompts->num_rows > 0): ?>
        <div class="table-responsive">
            <table id="promptsTable" class="table table-hover align-middle bg-white shadow-sm rounded">
                <thead class="table-light">
                    <tr>
                        <th>
                            <a href="?sort=title&dir=<?php echo $sortColumn === 'title' ? $nextDir : 'asc'; ?>&search=<?php echo urlencode($search); ?>&per_page=<?php echo $promptsPerPage; ?>">
                                Title <?php echo $sortColumn === 'title' ? ($sortDir === 'ASC' ? '↑' : '↓') : '↕'; ?>
                            </a>
                        </th>
                        <th>
                            <a href="?sort=category&dir=<?php echo $sortColumn === 'category' ? $nextDir : 'asc'; ?>&search=<?php echo urlencode($search); ?>&per_page=<?php echo $promptsPerPage; ?>">
                                Category <?php echo $sortColumn === 'category' ? ($sortDir === 'ASC' ? '↑' : '↓') : '↕'; ?>
                            </a>
                        </th>
                        <th>Tags</th>
                        <th>
                            <a href="?sort=version_number&dir=<?php echo $sortColumn === 'version_number' ? $nextDir : 'asc'; ?>&search=<?php echo urlencode($search); ?>&per_page=<?php echo $promptsPerPage; ?>">
                                Version <?php echo $sortColumn === 'version_number' ? ($sortDir === 'ASC' ? '↑' : '↓') : '↕'; ?>
                            </a>
                        </th>
                        <th>
                            <a href="?sort=created_at&dir=<?php echo $sortColumn === 'created_at' ? $nextDir : 'asc'; ?>&search=<?php echo urlencode($search); ?>&per_page=<?php echo $promptsPerPage; ?>">
                                Created <?php echo $sortColumn === 'created_at' ? ($sortDir === 'ASC' ? '↑' : '↓') : '↕'; ?>
                            </a>
                        </th>
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
                            <a href="create_prompt.php?edit=<?php echo $row['prompt_id']; ?>" class="btn btn-sm btn-outline-primary me-1">Edit</a>
                            <a href="../generate.php?prompt=<?php echo $row['prompt_id']; ?>" class="btn btn-sm btn-success me-1">Generate</a>
                            <a href="prompt_library.php?delete=<?php echo $row['prompt_id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this prompt?')">Delete</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <nav class="d-flex justify-content-between align-items-center mt-4">
                <?php if ($page > 1): ?>
                    <a href="prompt_library.php?page=<?php echo $page - 1; ?>&per_page=<?php echo $promptsPerPage; ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo $sortColumn; ?>&dir=<?php echo strtolower($sortDir); ?>" class="btn btn-outline-primary">← Previous</a>
                <?php else: ?>
                    <button class="btn btn-outline-secondary" disabled>← Previous</button>
                <?php endif; ?>

                <span class="text-muted">Page <strong><?php echo $page; ?></strong> of <strong><?php echo $totalPages; ?></strong></span>

                <?php if ($page < $totalPages): ?>
                    <a href="prompt_library.php?page=<?php echo $page + 1; ?>&per_page=<?php echo $promptsPerPage; ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo $sortColumn; ?>&dir=<?php echo strtolower($sortDir); ?>" class="btn btn-outline-primary">Next →</a>
                <?php else: ?>
                    <button class="btn btn-outline-secondary" disabled>Next →</button>
                <?php endif; ?>
            </nav>
        <?php endif; ?>

        <?php else: ?>
            <p class="text-muted">No prompts yet. <a href="create_prompt.php">Create your first one</a>.</p>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
