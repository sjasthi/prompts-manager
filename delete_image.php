<?php

require_once 'includes/database.php';

$conn = getConnection();

if(isset($_GET['id'])){

    $id = intval($_GET['id']);

    $stmt = $conn->prepare("
        DELETE FROM generated_images
        WHERE image_id = ?
    ");

    $stmt->bind_param("i", $id);

    $success = $stmt->execute();

    echo json_encode([
        "success" => $success
    ]);

}

$conn->close();