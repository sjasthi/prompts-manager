<?php
require_once '../includes/database.php';
$conn = getConnection();
$result = $conn->query("SELECT * FROM prompts WHERE status = 'active' ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Prompt Library</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <h1>📚 Prompt Library</h1>
    <table class="table table-bordered mt-3">
        <thead class="table-dark">
            <tr>
                <th>Title</th><th>Category</th><th>Tags</th><th>Version</th><th>Status</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['title']) ?></td>
                <td><?= htmlspecialchars($row['category']) ?></td>
                <td><?= htmlspecialchars($row['tags']) ?></td>
                <td>v<?= $row['version_number'] ?></td>
                <td><?= $row['status'] ?></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    <a href="../index.php" class="btn btn-secondary">← Back</a>
</div>
</body>
</html>