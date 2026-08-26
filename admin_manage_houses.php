<?php
session_start();
include('db.php');

if(!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] < 1) {
    header("Location: admin_login.php");
    exit();
}

$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

$sql = "SELECT houses.*, users.full_name FROM houses JOIN users ON houses.user_id = users.id WHERE NOT (houses.status = 'Pending' OR houses.is_approved = 0 OR houses.is_approved IS NULL)";

if($status_filter == 'Available') {
    $page_title = "Active Listings";
    $sql .= " AND (houses.status = '0' OR houses.status = 'Available')";
} elseif($status_filter == 'Rented') {
    $page_title = "Occupied Units";
    $sql .= " AND (houses.status = '1' OR houses.status = 'Rented')";
} else {
    $page_title = "All Properties";
}

$sql .= " ORDER BY houses.id DESC";
$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - AdamaRent Admin</title>
    <?php include(__DIR__ . '/header.php'); ?>
    <style>
        .content{margin-left:260px;padding:40px;width:calc(100% - 260px)}
        .content h1{font-size:22px;font-weight:800;color:#0f172a;margin-bottom:24px;display:flex;align-items:center;gap:10px}
        .content h1 i{color:#0d9488}
        .txt-available{color:#059669;font-weight:600;background:rgba(16,185,129,.1);padding:4px 10px;border-radius:6px;font-size:12px}
        .txt-rented{color:#dc2626;font-weight:600;background:rgba(239,68,68,.1);padding:4px 10px;border-radius:6px;font-size:12px}
        .txt-pending{color:#d97706;font-weight:600;background:rgba(245,158,11,.1);padding:4px 10px;border-radius:6px;font-size:12px}
        .txt-unknown{color:#64748b;font-weight:600;background:#f1f5f9;padding:4px 10px;border-radius:6px;font-size:12px}
        .modal{display:none;position:fixed;z-index:1000;left:0;top:0;width:100%;height:100%;background:rgba(0,0,0,.5);backdrop-filter:blur(4px)}
        .modal-content{background:#fff;margin:8% auto;padding:0;width:90%;max-width:500px;border-radius:16px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.2)}
        .modal-header{padding:20px 24px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between}
        .modal-header h3{font-size:16px;font-weight:700;color:#0f172a}
        .close-btn{background:none;border:none;font-size:20px;cursor:pointer;color:#94a3b8;padding:4px}
        .modal-body{padding:24px}
        .detail-img{width:100%;height:220px;object-fit:cover;border-radius:10px;margin-bottom:16px}
        .modal-body p{margin:8px 0;font-size:14px;color:#374151}
        .modal-body strong{color:#0f172a}
    </style>
</head>
<body>
<div class="content">
    <h1><i class="fas fa-building"></i> <?php echo $page_title; ?></h1>
    
    <div class="data-card">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Landlord</th>
                    <th>Category</th>
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
                    <td><strong><?php echo htmlspecialchars($row['full_name']); ?></strong></td>
                    <td><?php echo htmlspecialchars($row['category'] ?? 'N/A'); ?></td>
                    <td>Kebele <?php echo htmlspecialchars($row['kebele']); ?></td>
                    <td><strong><?php echo number_format($row['amount']); ?> ETB</strong></td>
                    <td>
                        <?php 
                        $status_val = $row['status'] ?? '';
                        if($status_val === '0' || strcasecmp($status_val, 'Available') === 0)
                            echo '<span class="txt-available">Available</span>';
                        elseif($status_val === '1' || strcasecmp($status_val, 'Rented') === 0)
                            echo '<span class="txt-rented">Rented</span>';
                        elseif(strcasecmp($status_val, 'Pending') === 0 || $row['is_approved'] == 0)
                            echo '<span class="txt-pending">Pending</span>';
                        else
                            echo '<span class="txt-unknown">Unknown</span>';
                        ?>
                    </td>
                    <td>
                        <button class="btn btn-blue" 
                            onclick="showDetails('<?php echo addslashes($row['full_name']); ?>', '<?php echo number_format($row['amount']); ?>', '<?php echo addslashes($row['description'] ?? ''); ?>', '<?php echo $row['image']; ?>')">
                            <i class="fas fa-eye"></i> View
                        </button>
                        <form action="process_request.php" method="POST" style="display:inline;" onsubmit="return confirm('Delete this listing permanently?')">
                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                            <button type="submit" name="action" value="delete_house" class="btn btn-red"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="viewModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Property Details</h3>
            <button class="close-btn" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body">
            <img id="modalImg" src="" class="detail-img">
            <p><strong>Landlord:</strong> <span id="modalLandlord"></span></p>
            <p><strong>Price:</strong> <span id="modalPrice"></span> ETB/month</p>
            <p><strong>Description:</strong></p>
            <div id="modalDesc" style="background:#f8fafc;padding:14px;border-radius:8px;font-size:14px;line-height:1.6;color:#475569"></div>
        </div>
    </div>
</div>

<script>
function showDetails(name, price, desc, img) {
    document.getElementById('modalLandlord').innerText = name;
    document.getElementById('modalPrice').innerText = price;
    document.getElementById('modalDesc').innerText = desc || 'No description provided.';
    document.getElementById('modalImg').src = "uploads/" + img;
    document.getElementById('viewModal').style.display = "block";
}
function closeModal() { document.getElementById('viewModal').style.display = "none"; }
window.onclick = function(e) { if(e.target == document.getElementById('viewModal')) closeModal(); }
</script>
</body>
</html>
