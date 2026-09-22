<?php
include('includes/db.php');
include('includes/session_config.php');
session_start();
include('includes/security.php');

// Tenant "rent request" action — requires login.
$isAjax = (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest') || isset($_GET['ajax']);
$target_house = (int)($_POST['house'] ?? $_GET['house'] ?? 0);

if(!isset($_SESSION['user_id'])){
    $redir = "login.php?redirect=" . urlencode('rent_request.php?house=' . $target_house);
    if($isAjax){
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['type' => 'login_needed', 'message' => 'You need to sign in to request this property.', 'title' => 'Sign in required', 'redirect' => $redir]);
        exit();
    }
    header("Location: " . $redir);
    exit();
}

// If returned from login via GET, redirect directly to the listing page to finalize request
if($_SERVER['REQUEST_METHOD'] === 'GET' && $target_house > 0){
    header("Location: house_detail.php?id=" . $target_house . "#rent");
    exit();
}

$status = null; // ['type','message','title','redirect']
$house_id = (int)($_POST['house'] ?? 0);

if($house_id > 0){ csrf_validate(); }

if($house_id <= 0){
    $status = ['type' => 'error', 'message' => 'No property was specified.', 'title' => 'Invalid request', 'redirect' => 'index.php'];
} else {
    // Verify house exists, is approved and available
    $stmt = mysqli_prepare($conn, "SELECT * FROM houses WHERE id=? AND status='Available' AND is_approved=1");
    mysqli_stmt_bind_param($stmt, "i", $house_id);
    mysqli_stmt_execute($stmt);
    $hq = mysqli_stmt_get_result($stmt);
    $house = $hq ? mysqli_fetch_assoc($hq) : null;

    if(!$house){
        $status = ['type' => 'error', 'message' => 'This property is not currently available for rent.', 'title' => 'Not available', 'redirect' => 'index.php'];
    } else {
        $uid = (int)$_SESSION['user_id'];

        if($house['user_id'] == $uid){
            $status = ['type' => 'error', 'message' => 'You cannot rent your own property.', 'title' => 'Your own listing', 'redirect' => 'index.php'];
        } else {
            // Prevent duplicate pending rental requests for the same user + house
            $dup_stmt = mysqli_prepare($conn, "SELECT id FROM rental_requests WHERE user_id=? AND house_id=? AND status='pending'");
            mysqli_stmt_bind_param($dup_stmt, "ii", $uid, $house_id);
            mysqli_stmt_execute($dup_stmt);
            $dup = mysqli_stmt_get_result($dup_stmt);
            if($dup && ($dupRow = mysqli_fetch_assoc($dup))){
                $status = ['type' => 'info', 'message' => 'You already requested this property. The owner has been notified.', 'title' => 'Already requested', 'redirect' => 'index.php', 'request_id' => (int)$dupRow['id']];
            } else {
                $ins_stmt = mysqli_prepare($conn, "INSERT INTO rental_requests (user_id, house_id, status, created_at) VALUES (?, ?, 'pending', NOW())");
                mysqli_stmt_bind_param($ins_stmt, "ii", $uid, $house_id);
                if(mysqli_stmt_execute($ins_stmt)){
                    $newReqId = (int)mysqli_insert_id($conn);
                    // Notify the property owner
                    $requester = $_SESSION['user_name'] ?? 'A user';
                    $notifMsg = $requester . ' requested to rent your property in Kebele ' . $house['kebele'] . '.';
                    $owner_id = (int)$house['user_id'];
                    $notif_stmt = mysqli_prepare($conn, "INSERT INTO notifications (user_id, type, title, message, link) VALUES (?, 'rent_request', 'New rental request', ?, 'manage_houses.php')");
                    mysqli_stmt_bind_param($notif_stmt, "is", $owner_id, $notifMsg);
                    mysqli_stmt_execute($notif_stmt);
                    $status = ['type' => 'success', 'message' => 'Your rental request was sent to the property owner. They will contact you soon.', 'title' => 'Request sent', 'redirect' => 'index.php', 'request_id' => $newReqId];
                } else {
                    $status = ['type' => 'error', 'message' => 'Something went wrong. Please try again.', 'title' => 'Error', 'redirect' => 'index.php'];
                }
            }
        }
    }
}
?>
<?php if($isAjax): ?>
    <?php header('Content-Type: application/json; charset=utf-8'); echo json_encode($status); ?>
<?php else: ?>
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
<?php endif; ?>