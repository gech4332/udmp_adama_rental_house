<?php
include('includes/db.php');
include('includes/session_config.php');
session_start();
include('includes/security.php');

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

if(!isset($_POST['delete_btn'])){
    header("Location: manage_houses.php");
    exit();
}
csrf_validate();

$id = (int)$_POST['id'];
$current_user = (int)$_SESSION['user_id'];

$status = ['type' => 'error', 'message' => 'Something went wrong. Please try again.', 'title' => 'Error', 'redirect' => 'manage_houses.php'];

$stmt = mysqli_prepare($conn, "SELECT image, video_file FROM houses WHERE id = ? AND user_id = ?");
mysqli_stmt_bind_param($stmt, "ii", $id, $current_user);
mysqli_stmt_execute($stmt);
$query = mysqli_stmt_get_result($stmt);

if($query && mysqli_num_rows($query) > 0){
    $data = mysqli_fetch_assoc($query);

    if(!empty($data['image']) && file_exists("uploads/" . $data['image'])){
        unlink("uploads/" . $data['image']);
    }
    if(!empty($data['video_file']) && file_exists("uploads/" . $data['video_file'])){
        @unlink("uploads/" . $data['video_file']);
    }
        $stmt2 = mysqli_prepare($conn, "SELECT filename FROM house_images WHERE house_id = ?");
        mysqli_stmt_bind_param($stmt2, "i", $id);
        mysqli_stmt_execute($stmt2);
        $imgs = mysqli_stmt_get_result($stmt2);
        if($imgs && mysqli_num_rows($imgs) > 0){
            while($im = mysqli_fetch_assoc($imgs)){
                if(!empty($im['filename']) && file_exists("uploads/" . $im['filename'])){
                    @unlink("uploads/" . $im['filename']);
                }
            }
        }
        $del_stmt = mysqli_prepare($conn, "DELETE FROM requests WHERE house_id = ?");
        mysqli_stmt_bind_param($del_stmt, "i", $id);
        mysqli_stmt_execute($del_stmt);

        $del_stmt = mysqli_prepare($conn, "DELETE FROM rental_requests WHERE house_id = ?");
        mysqli_stmt_bind_param($del_stmt, "i", $id);
        mysqli_stmt_execute($del_stmt);

        $del_stmt = mysqli_prepare($conn, "DELETE FROM house_images WHERE house_id = ?");
        mysqli_stmt_bind_param($del_stmt, "i", $id);
        mysqli_stmt_execute($del_stmt);

        $del_stmt = mysqli_prepare($conn, "DELETE FROM house_amenities WHERE house_id = ?");
        mysqli_stmt_bind_param($del_stmt, "i", $id);
        mysqli_stmt_execute($del_stmt);

        $del_stmt = mysqli_prepare($conn, "DELETE FROM houses WHERE id = ?");
        mysqli_stmt_bind_param($del_stmt, "i", $id);
        mysqli_stmt_execute($del_stmt);
        $status = ['type' => 'success', 'message' => 'Post removed successfully.', 'title' => 'Listing deleted', 'redirect' => 'manage_houses.php'];
} else {
    $status = ['type' => 'error', 'message' => 'Unauthorized! You can only delete your own posts.', 'title' => 'Restricted', 'redirect' => 'manage_houses.php'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AdamaRent</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body style="margin:0;background:#f8fafc;font-family:'Inter',sans-serif">
    <?php include(__DIR__ . '/includes/popup.php'); ?>
    <script>
        window.addEventListener('DOMContentLoaded', function(){
            showToast(<?php echo json_encode($status['message']); ?>, <?php echo json_encode($status['type']); ?>, <?php echo json_encode($status['title']); ?>);
            setTimeout(function(){ window.location = <?php echo json_encode($status['redirect']); ?>; }, 2000);
        });
    </script>
</body>
</html>