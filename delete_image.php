<?php

require_once 'includes/database.php';

header('Content-Type: application/json');

$conn = getConnection();

$response = [
    'success' => false
];

if (isset($_GET['id'])) {

    $id = intval($_GET['id']);

    /*
    |--------------------------------------------------------------------------
    | Get the image path before deleting the database row
    |--------------------------------------------------------------------------
    */

    $selectStmt = $conn->prepare("
        SELECT image_path
        FROM generated_images
        WHERE image_id = ?
    ");

    $selectStmt->bind_param("i", $id);
    $selectStmt->execute();

    $result = $selectStmt->get_result();
    $image = $result->fetch_assoc();

    if ($image) {

        $imagePath = $image['image_path'];

        /*
        |--------------------------------------------------------------------------
        | Delete the database record
        |--------------------------------------------------------------------------
        */

        $deleteStmt = $conn->prepare("
            DELETE FROM generated_images
            WHERE image_id = ?
        ");

        $deleteStmt->bind_param("i", $id);

        if ($deleteStmt->execute()) {

            /*
            |--------------------------------------------------------------------------
            | Delete the physical file only when it is stored locally
            |--------------------------------------------------------------------------
            */

            if (
                !empty($imagePath) &&
                str_starts_with($imagePath, 'uploads/')
            ) {

                $fullPath = __DIR__ . '/' . $imagePath;

                if (is_file($fullPath)) {
                    unlink($fullPath);
                }
            }

            $response['success'] = true;
        }
    }
}

$conn->close();

echo json_encode($response);