<?php
session_start();
include('db.php');

if(!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] < 1) {
    exit("Denied: Insufficient Privileges");
}

// Catch the 'status' from the URL (sent by dashboard)
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Base SQL
$sql = "SELECT houses.*, users.full_name FROM houses JOIN users ON houses.user_id = users.id";

if($status_filter == 'Available') {
    $page_title = "Active Listings (Available)";
    // This checks for both numeric 0 and the word 'Available'
    $sql .= " WHERE (houses.status = '0' OR houses.status = 'Available')";
} elseif($status_filter == 'Rented') {
    $page_title = "Occupied Units (Rented)";
    // This checks for both numeric 1 and the word 'Rented'
    $sql .= " WHERE (houses.status = '1' OR houses.status = 'Rented')";
} else {
    $page_title = "All Property Management";
}

$sql .= " ORDER BY houses.id DESC";
$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7f6; margin: 0; display: flex; }
        .sidebar { width: 250px; background: #2c3e50; color: white; height: 100vh; padding: 20px; position: fixed; }
        .main { margin-left: 250px; padding: 40px; width: calc(100% - 250px); }
        table { width: 100%; background: white; border-collapse: collapse; margin-bottom: 30px; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        th, td { padding: 15px; border-bottom: 1px solid #eee; text-align: left; }
        th { background: #4b7b8a; color: white; }
        .txt-available { color: #27ae60; font-weight: bold; background: #eafaf1; padding: 4px 8px; border-radius: 4px; }
        .txt-rented { color: #e67e22; font-weight: bold; background: #fef5e7; padding: 4px 8px; border-radius: 4px; }
        .btn { padding: 8px 12px; border-radius: 4px; color: white; cursor:pointer; border:none; display: inline-block; text-decoration: none; font-size: 12px; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); }
        .modal-content { background: white; margin: 5% auto; padding: 20px; width: 35%; max-width: 500px; border-radius: 12px; position: relative; }
        .close-btn { position: absolute; right: 20px; top: 15px; font-size: 24px; cursor: pointer; }
        .detail-img { width: 100%; max-height: 200px; object-fit: cover; border-radius: 8px; margin-bottom: 15px; }
    </style>
</head>
<body>

<div class="sidebar">
    <h2>Master Admin</h2>
    <hr>
    <a href="admin_panel.php" style="color:white; display:block; padding:15px 0; text-decoration:none;"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="admin_manage_users.php" style="color:white; display:block; padding:15px 0; text-decoration:none;"><i class="fas fa-users"></i> Manage Users</a>
    <a href="admin_manage_houses.php" style="color:white; display:block; padding:15px 0; text-decoration:none;"><i class="fas fa-home"></i> Manage Houses</a>
    <a href="admin_manage_requests.php" style="color:white; display:block; padding:15px 0; text-decoration:none;"><i class="fas fa-envelope-open-text"></i> Property Requests</a>

</div>

<div class="main">
    <h1><i class="fas fa-list"></i> <?php echo $page_title; ?></h1>
    
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Landlord</th>
                <th>Location</th>
                <th>Price</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td>#<?php echo $row['id']; ?></td>
                <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                <td>Kebele <?php echo $row['kebele']; ?></td>
                <td><?php echo number_format($row['amount']); ?> ETB</td>
                <td>
                    <?php 
                    // Visual Status logic
                    if($row['status'] == '0' || $row['status'] == 'Available') {
                        echo '<span class="txt-available">Available</span>';
                    } else {
                        echo '<span class="txt-rented">Rented</span>';
                    }
                    ?>
                </td>
                <td>
                    <button class="btn" style="background:#3498db;" 
                        onclick="showDetails('<?php echo addslashes($row['full_name']); ?>', '<?php echo number_format($row['amount']); ?>', '<?php echo addslashes($row['house_desc']); ?>', '<?php echo $row['image']; ?>')">
                        <i class="fas fa-eye"></i> View
                    </button>
                    <form action="process_request.php" method="POST" style="display:inline;" onsubmit="return confirm('Delete this post?');">
                        <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                        <button type="submit" name="action" value="delete_house" class="btn" style="background:#e74c3c;"><i class="fas fa-trash"></i></button>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<div id="viewModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal()">&times;</span>
        <h3>Property Details</h3>
        <img id="modalImg" src="" class="detail-img">
        <p><strong>Landlord:</strong> <span id="modalLandlord"></span></p>
        <p><strong>Price:</strong> <span id="modalPrice"></span> ETB</p>
        <p><strong>Description:</strong></p>
        <div id="modalDesc" style="background:#f9f9f9; padding:15px; border-radius:5px; font-size:14px; line-height:1.5;"></div>
    </div>
</div>

<script>
function showDetails(name, price, desc, img) {
    document.getElementById('modalLandlord').innerText = name;
    document.getElementById('modalPrice').innerText = price;
    document.getElementById('modalDesc').innerText = desc;
    document.getElementById('modalImg').src = "uploads/" + img;
    document.getElementById('viewModal').style.display = "block";
}
function closeModal() { document.getElementById('viewModal').style.display = "none"; }
window.onclick = function(event) {
    if (event.target == document.getElementById('viewModal')) { closeModal(); }
}
</script>

</body>
</html>