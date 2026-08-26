<?php 
session_start();
include('db.php'); 

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post a Property - AdamaRent</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Inter',system-ui,sans-serif;background:#f8fafc;color:#1e293b;min-height:100vh}

        .navbar{background:#0f172a;padding:14px 32px;display:flex;justify-content:space-between;align-items:center}
        .nav-brand{display:flex;align-items:center;gap:10px;text-decoration:none}
        .nav-brand-icon{width:36px;height:36px;background:linear-gradient(135deg,#0d9488,#14b8a6);border-radius:9px;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:900;font-size:14px}
        .nav-brand-text{color:#fff;font-size:18px;font-weight:800}
        .nav-brand-text span{color:#2dd4bf}
        .nav-right{display:flex;align-items:center;gap:6px}
        .nav-right a{color:rgba(255,255,255,.8);text-decoration:none;font-size:13px;font-weight:500;padding:8px 14px;border-radius:8px;transition:all .2s}
        .nav-right a:hover{color:#fff;background:rgba(255,255,255,.1)}

        .form-page{max-width:680px;margin:40px auto;padding:0 20px}
        .form-header{margin-bottom:28px}
        .form-header h1{font-size:26px;font-weight:800;color:#0f172a;letter-spacing:-.5px}
        .form-header p{color:#64748b;font-size:14px;margin-top:4px}
        .form-header .user-tag{display:inline-flex;align-items:center;gap:6px;background:#f1f5f9;color:#475569;padding:6px 14px;border-radius:8px;font-size:13px;font-weight:500;margin-top:12px}
        .form-header .user-tag i{color:#0d9488}

        .form-card{background:#fff;border-radius:16px;border:1px solid #f1f5f9;padding:32px;box-shadow:0 1px 3px rgba(0,0,0,.04)}
        .form-section{margin-bottom:28px;padding-bottom:28px;border-bottom:1px solid #f1f5f9}
        .form-section:last-of-type{border-bottom:none;margin-bottom:0;padding-bottom:0}
        .form-section-title{display:flex;align-items:center;gap:10px;font-size:15px;font-weight:700;color:#0f172a;margin-bottom:16px}
        .form-section-title i{color:#0d9488;font-size:16px}
        .form-row{display:grid;grid-template-columns:1fr 1fr;gap:14px}
        .form-group{margin-bottom:16px}
        .form-group:last-child{margin-bottom:0}
        .form-group label{display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px}
        .form-group label .req{color:#dc2626}
        .form-group input,.form-group select,.form-group textarea{width:100%;padding:12px 14px;border:1.5px solid #e5e7eb;border-radius:10px;font-size:14px;font-family:inherit;transition:all .2s;background:#fff}
        .form-group input:focus,.form-group select:focus,.form-group textarea:focus{outline:none;border-color:#0d9488;box-shadow:0 0 0 3px rgba(13,148,136,.08)}
        .form-group textarea{resize:vertical;min-height:90px}
        .form-group .hint{font-size:12px;color:#94a3b8;margin-top:4px}
        .form-group .map-auto{background:rgba(13,148,136,.06);color:#0d9488;padding:8px 12px;border-radius:8px;font-size:12px;font-weight:500;display:none;margin-top:8px}
        .form-group .map-auto i{margin-right:4px}

        .file-upload{border:2px dashed #e5e7eb;border-radius:12px;padding:28px;text-align:center;cursor:pointer;transition:all .3s;background:#fafbfc}
        .file-upload:hover{border-color:#0d9488;background:rgba(13,148,136,.03)}
        .file-upload i{font-size:28px;color:#94a3b8;margin-bottom:8px;display:block}
        .file-upload p{font-size:14px;color:#64748b;font-weight:500}
        .file-upload span{font-size:12px;color:#94a3b8}
        .file-upload input{display:none}
        .file-name{font-size:13px;color:#0d9488;font-weight:600;margin-top:8px;display:none}

        .btn-submit{width:100%;padding:14px;background:linear-gradient(135deg,#0d9488,#14b8a6);color:#fff;border:none;border-radius:12px;font-size:15px;font-weight:700;font-family:inherit;cursor:pointer;transition:all .3s;margin-top:8px}
        .btn-submit:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(13,148,136,.4)}

        .error-msg{background:#fef2f2;border:1px solid #fecaca;color:#dc2626;padding:12px 16px;border-radius:10px;font-size:13px;font-weight:500;margin-bottom:20px;display:flex;align-items:center;gap:10px}

        @media(max-width:640px){
            .form-row{grid-template-columns:1fr}
            .form-page{padding:0 12px;margin:20px auto}
            .form-card{padding:20px}
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="Home.php" class="nav-brand">
            <div class="nav-brand-icon">AR</div>
            <div class="nav-brand-text">Adama<span>Rent</span></div>
        </a>
        <div class="nav-right">
            <a href="manage_houses.php"><i class="fas fa-th-large"></i> Dashboard</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </nav>

    <div class="form-page">
        <div class="form-header">
            <h1>Post a New Property</h1>
            <p>Fill in the details below to list your property for potential tenants.</p>
            <div class="user-tag"><i class="fas fa-user-check"></i> Logged in as <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Landlord'); ?></div>
        </div>

        <div class="form-card">
            <?php if(isset($error)): ?>
                <div class="error-msg"><i class="fas fa-circle-exclamation"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <form action="" method="POST" enctype="multipart/form-data">
                <!-- Location -->
                <div class="form-section">
                    <div class="form-section-title"><i class="fas fa-location-dot"></i> Location Details</div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Kebele <span class="req">*</span></label>
                            <input type="text" name="kebele" placeholder="e.g. 03 or 12" required>
                        </div>
                        <div class="form-group">
                            <label>House Number</label>
                            <input type="text" name="house_num" placeholder="e.g. 45">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Street Name <span class="req">*</span></label>
                        <input type="text" name="street" placeholder="e.g. Bole Road" required>
                    </div>
                    <div class="form-group">
                        <input type="url" id="map_link" name="map_link" placeholder="Auto-generated Google Maps link" readonly style="background:#f8fafc">
                        <div class="map-auto" id="auto-gen-note"><i class="fas fa-check-circle"></i> Auto-generated from location fields</div>
                    </div>
                </div>

                <!-- Property Info -->
                <div class="form-section">
                    <div class="form-section-title"><i class="fas fa-home"></i> Property Information</div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Category <span class="req">*</span></label>
                            <select name="category" required>
                                <option value="">Select type...</option>
                                <optgroup label="Residential">
                                    <option value="Single Home">Single Home</option>
                                    <option value="Apartment">Apartment</option>
                                    <option value="Villa">Villa</option>
                                </optgroup>
                                <optgroup label="Commercial">
                                    <option value="Office">Office</option>
                                    <option value="Shop">Shop</option>
                                    <option value="Warehouse">Warehouse</option>
                                </optgroup>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Monthly Price (ETB) <span class="req">*</span></label>
                            <input type="number" name="amount" placeholder="e.g. 8000" required min="1">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Contact Phone <span class="req">*</span></label>
                        <input type="text" name="phone" placeholder="e.g. 0911234567" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="desc" placeholder="Describe your property - water, electricity, furnished status, etc."></textarea>
                    </div>
                </div>

                <!-- Media -->
                <div class="form-section">
                    <div class="form-section-title"><i class="fas fa-camera"></i> Photos & Media</div>
                    <div class="form-group">
                        <label>Property Photo <span class="req">*</span></label>
                        <div class="file-upload" onclick="this.querySelector('input').click()">
                            <i class="fas fa-cloud-arrow-up"></i>
                            <p>Click to upload a photo</p>
                            <span>JPG, PNG or WebP (max 5MB)</span>
                            <div class="file-name" id="img-name"></div>
                            <input type="file" name="house_image" accept="image/*" required onchange="document.getElementById('img-name').textContent=this.files[0].name; document.getElementById('img-name').style.display='block'">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Video Tour (Optional)</label>
                        <div class="file-upload" onclick="this.querySelector('input').click()">
                            <i class="fas fa-video"></i>
                            <p>Click to upload a video</p>
                            <span>MP4 or MOV (optional)</span>
                            <div class="file-name" id="vid-name"></div>
                            <input type="file" name="house_video" accept="video/mp4,video/x-m4v,video/*" onchange="document.getElementById('vid-name').textContent=this.files[0].name; document.getElementById('vid-name').style.display='block'">
                        </div>
                    </div>
                </div>

                <button type="submit" name="submit" class="btn-submit"><i class="fas fa-paper-plane"></i> Submit for Approval</button>
            </form>
        </div>
    </div>

    <script>
    const kebeleInput = document.querySelector('input[name="kebele"]');
    const streetInput = document.querySelector('input[name="street"]');
    const houseNumInput = document.querySelector('input[name="house_num"]');
    const mapLinkInput = document.getElementById('map_link');
    const autoGenNote = document.getElementById('auto-gen-note');

    function updateMapLink() {
        const kebele = kebeleInput.value.trim();
        const street = streetInput.value.trim();
        const houseNum = houseNumInput.value.trim();
        if (kebele || street) {
            let parts = [];
            if (kebele) parts.push('Kebele ' + kebele);
            if (street) parts.push(street);
            if (houseNum) parts.push('House ' + houseNum);
            parts.push('Adama, Ethiopia');
            mapLinkInput.value = 'https://www.google.com/maps/search/' + encodeURIComponent(parts.join(', '));
            autoGenNote.style.display = 'block';
        } else {
            mapLinkInput.value = '';
            autoGenNote.style.display = 'none';
        }
    }
    kebeleInput.addEventListener('input', updateMapLink);
    streetInput.addEventListener('input', updateMapLink);
    houseNumInput.addEventListener('input', updateMapLink);
    </script>

    <?php
    if(isset($_POST['submit'])){
        $upload_dir = __DIR__ . '/uploads';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $kebele   = mysqli_real_escape_string($conn, $_POST['kebele']);
        $street   = mysqli_real_escape_string($conn, $_POST['street']);
        $h_num    = mysqli_real_escape_string($conn, $_POST['house_num']);
        $category = mysqli_real_escape_string($conn, $_POST['category']);
        $amount   = (int)$_POST['amount'];
        $phone    = mysqli_real_escape_string($conn, $_POST['phone']);
        $map      = mysqli_real_escape_string($conn, $_POST['map_link']);
        $desc     = mysqli_real_escape_string($conn, $_POST['desc']);
        $user_id  = $_SESSION['user_id'];

        $videoName = "";
        if(!empty($_FILES['house_video']['name'])){
            $videoName = time() . "_v_" . basename($_FILES['house_video']['name']);
            @move_uploaded_file($_FILES['house_video']['tmp_name'], $upload_dir . "/" . $videoName);
        }

        $imgName = time() . "_" . basename($_FILES['house_image']['name']);
        $target = $upload_dir . "/" . $imgName;

        if($_FILES['house_image']['error'] !== UPLOAD_ERR_OK){
            $err_code = $_FILES['house_image']['error'];
            $err_msg = match($err_code){
                UPLOAD_ERR_INI_SIZE   => 'File exceeds server upload limit.',
                UPLOAD_ERR_FORM_SIZE  => 'File exceeds form upload limit.',
                UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
                UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder on server.',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
                UPLOAD_ERR_EXTENSION  => 'Upload blocked by server extension.',
                default               => 'Unknown upload error (code: ' . $err_code . ').'
            };
            echo "<script>alert('Upload failed: " . addslashes($err_msg) . "');</script>";
        } elseif(!is_writable($upload_dir)){
            echo "<script>alert('Upload failed: The uploads folder is not writable. Check permissions.');</script>";
        } elseif(move_uploaded_file($_FILES['house_image']['tmp_name'], $target)){
            $sql = "INSERT INTO houses (kebele, street, house_number, category, amount, phone, map_link, image, description, user_id, video_file, status, is_approved, created_at) 
                    VALUES ('$kebele', '$street', '$h_num', '$category', '$amount', '$phone', '$map', '$imgName', '$desc', $user_id, '$videoName', 'Pending', 0, NOW())";
            
            if(mysqli_query($conn, $sql)){
                $house_id = mysqli_insert_id($conn);
                $req_sql = "INSERT INTO requests (user_id, house_id, status, created_at) VALUES ($user_id, $house_id, 0, NOW())";
                mysqli_query($conn, $req_sql);
                echo "<script>alert('Property submitted for approval! It will appear once reviewed by an admin.'); window.location='manage_houses.php';</script>";
            } else {
                echo "<script>alert('Database error. Please try again.');</script>";
            }
        } else {
            echo "<script>alert('Upload failed: move_uploaded_file returned false. Check server error log.');</script>";
        }
    }
    ?>
</body>
</html>
