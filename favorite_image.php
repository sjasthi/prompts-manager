<?php

require_once 'includes/database.php';

$conn = getConnection();

if(isset($_GET['id'])){

    $id = intval($_GET['id']);

    $stmt = $conn->prepare("
        UPDATE generated_images
        SET favorite = NOT favorite
        WHERE image_id = ?
    ");

    $stmt->bind_param("i", $id);

    $stmt->execute();

    // Get the new favorite status
    $stmt = $conn->prepare("
        SELECT favorite
        FROM generated_images
        WHERE image_id = ?
    ");

    $stmt->bind_param("i", $id);

    $stmt->execute();

    $result = $stmt->get_result();

    $row = $result->fetch_assoc();

   echo json_encode([
       "favorite" => (bool)$row['favorite']
   ]);

}

$conn->close();