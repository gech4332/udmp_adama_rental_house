<?php
// Shared Header Logic
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root {
            --sidebar-bg: #0f172a;
            --main-bg: #f8fafc;
            --primary: #3b82f6;
            --danger: #ef4444;
            --success: #10b981;
            --warning: #f59e0b;
            --white: #ffffff;
            --text-dark: #1e293b;
            --text-light: #94a3b8;
        }

        body { font-family: 'Inter', sans-serif; background: var(--main-bg); color: var(--text-dark); margin: 0; display: flex; }
        
        /* Consistent Sidebar */
        .sidebar { width: 260px; background: var(--sidebar-bg); color: white; height: 100vh; position: fixed; padding: 25px; box-sizing: border-box; }
        .sidebar .brand { font-size: 20px; font-weight: 800; color: var(--primary); margin-bottom: 40px; display: block; text-decoration: none; }
        .nav-link { display: flex; align-items: center; padding: 12px 15px; color: var(--text-light); text-decoration: none; border-radius: 10px; margin-bottom: 8px; transition: 0.2s; }
        .nav-link i { margin-right: 12px; width: 20px; text-align: center; }
        .nav-link:hover, .nav-link.active { background: #1e293b; color: white; }
        .nav-link.active { background: var(--primary); }

        /* Consistent Content Area */
        .main-content, .main { margin-left: 260px; padding: 40px; width: 100%; }
        
        /* Professional Tables */
        .data-card { background: var(--white); border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f1f5f9; padding: 15px; text-align: left; font-size: 13px; text-transform: uppercase; color: var(--text-light); }
        td { padding: 15px; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
        
        /* Modern Buttons */
        .btn { padding: 8px 14px; border-radius: 8px; border: none; font-size: 13px; font-weight: 600; cursor: pointer; transition: 0.2s; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; }
        .btn-blue { background: var(--primary); color: white; }
        .btn-red { background: var(--danger); color: white; }
        .btn-outline { background: transparent; border: 1px solid #e2e8f0; color: var(--text-dark); }
        .btn:hover { opacity: 0.85; transform: translateY(-1px); }

        /* Badges */
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .badge-green { background: #dcfce7; color: #166534; }
        .badge-orange { background: #fef3c7; color: #92400e; }

        /* Sidebar footer */
        .sidebar { position: fixed; }
        .sidebar-footer { position: absolute; bottom: 20px; left: 20px; right: 20px; }
        .logout-btn { display:block; text-align:center; background:#fff; color:#ef4444; padding:10px 12px; border-radius:8px; font-weight:700; text-decoration:none; }
    </style>
</head>
<body>
    <button onclick="history.back()" style="position:fixed; top:16px; right:16px; z-index:9999; background:#ffffff; border:1px solid #e2e8f0; padding:8px 12px; border-radius:8px; cursor:pointer; font-weight:600;"><i class="fas fa-arrow-left"></i> Back</button>
    <?php include(__DIR__ . '/sidebar.php'); ?>

<script>
// Intercept sidebar nav-link clicks and load content via AJAX into the page container
(function(){
    function findMain(doc){
        return doc.querySelector('.main-content') || doc.querySelector('.main') || doc.querySelector('.content') || doc.querySelector('main');
    }

    document.addEventListener('click', function(e){
        var a = e.target.closest('.nav-link');
        if(!a) return;
        var href = a.getAttribute('href');
        if(!href) return;
        if(a.dataset && a.dataset.noAjax) return; // allow no-ajax links (logout)
        // Only handle internal links (no protocol)
        if(href.indexOf('http') === 0 || href.indexOf('//') === 0) return;

        e.preventDefault();
        fetch(href, {credentials: 'same-origin'})
            .then(function(res){ return res.text(); })
            .then(function(html){
                var parser = new DOMParser();
                var doc = parser.parseFromString(html, 'text/html');
                var newMain = findMain(doc);
                var curMain = findMain(document);
                if(newMain && curMain){
                    curMain.innerHTML = newMain.innerHTML;
                    // update URL
                    history.pushState({ajax:true}, '', href);
                } else {
                    // fallback to full navigation
                    window.location = href;
                }
            }).catch(function(){ window.location = href; });
    });

    window.addEventListener('popstate', function(){
        var href = location.pathname + location.search;
        fetch(href, {credentials: 'same-origin'})
            .then(function(res){ return res.text(); })
            .then(function(html){
                var parser = new DOMParser();
                var doc = parser.parseFromString(html, 'text/html');
                var newMain = findMain(doc);
                var curMain = findMain(document);
                if(newMain && curMain) curMain.innerHTML = newMain.innerHTML;
            });
    });
})();
</script>