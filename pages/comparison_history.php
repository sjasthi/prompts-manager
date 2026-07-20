<?php

require_once '../includes/database.php';

$conn = getConnection();


$query = "
SELECT
    mc.comparison_id,
    mc.prompt_id,
    mc.models_used,
    mc.output_references,
    mc.created_at,

    p.title,
    p.prompt_text,

    cf.selected_model,
    cf.notes

FROM model_comparisons mc

JOIN prompts p
    ON mc.prompt_id = p.prompt_id

LEFT JOIN comparison_feedback cf
    ON mc.prompt_id = cf.prompt_id

ORDER BY mc.created_at DESC
";


$result = $conn->query($query);

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Comparison History</title>


<link
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
rel="stylesheet">


<style>

body {
    background:#f4f6fb;
}


.page-wrapper {
    max-width:1200px;
    margin:40px auto;
}


.card {
    border:none;
    border-radius:18px;
    box-shadow:0 8px 25px rgba(0,0,0,.08);
}


</style>


</head>


<body>


<div class="container page-wrapper">


<a href="../index.php"
class="btn btn-outline-secondary mb-4">

← Back Dashboard

</a>


<h1 class="fw-bold mb-4">

📚 Comparison History

</h1>


<?php if ($result->num_rows == 0): ?>


<div class="alert alert-info">

No comparisons have been saved yet.

</div>


<?php endif; ?>



<?php while($row = $result->fetch_assoc()): ?>


<div class="card mb-4">


<div class="card-body">


<h3>

<?php echo htmlspecialchars($row['title']); ?>

</h3>


<p class="text-muted">

<?php echo htmlspecialchars($row['prompt_text']); ?>

</p>


<hr>


<div class="row">


<div class="col-md-6">


<h5>
Models Compared
</h5>


<p>
<?php echo htmlspecialchars($row['models_used']); ?>
</p>


</div>



<div class="col-md-6">


<h5>
Winner
</h5>


<?php if($row['selected_model']): ?>

<span class="badge bg-success">

<?php echo htmlspecialchars($row['selected_model']); ?>

</span>


<?php else: ?>


<span class="badge bg-secondary">

No evaluation yet

</span>


<?php endif; ?>


</div>


</div>



<hr>


<h5>
Notes
</h5>


<p>

<?php

echo $row['notes']
? htmlspecialchars($row['notes'])
: "No notes provided.";

?>

</p>



<small class="text-muted">

Compared:
<?php echo $row['created_at']; ?>

</small>



</div>


</div>


<?php endwhile; ?>


</div>


</body>

</html>