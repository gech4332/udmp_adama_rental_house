<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Inter',system-ui,sans-serif;background:#f8fafc;color:#1e293b;margin:0;display:flex}

        .main-content,.main{margin-left:260px;padding:32px;width:100%;min-height:100vh}

        .data-card{background:#fff;border-radius:14px;box-shadow:0 1px 3px rgba(0,0,0,.04);overflow:hidden;border:1px solid #f1f5f9}
        table{width:100%;border-collapse:collapse}
        th{background:#f8fafc;padding:14px 16px;text-align:left;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:#64748b;border-bottom:1px solid #f1f5f9}
        td{padding:14px 16px;border-bottom:1px solid #f1f5f9;font-size:14px;color:#374151}

        .btn{padding:8px 14px;border-radius:8px;border:none;font-size:12px;font-weight:600;cursor:pointer;transition:all .2s;text-decoration:none;display:inline-flex;align-items:center;gap:5px;font-family:inherit}
        .btn-blue{background:linear-gradient(135deg,#0d9488,#14b8a6);color:#fff}
        .btn-red{background:rgba(239,68,68,.1);color:#dc2626}
        .btn-outline{background:transparent;border:1.5px solid #e5e7eb;color:#374151}
        .btn:hover{transform:translateY(-1px);box-shadow:0 4px 12px rgba(0,0,0,.08)}

        .badge{padding:4px 10px;border-radius:6px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.3px}
        .badge-green{background:rgba(16,185,129,.1);color:#059669}
        .badge-orange{background:rgba(245,158,11,.1);color:#d97706}
        .badge-red{background:rgba(239,68,68,.1);color:#dc2626}

        @media(max-width:768px){
            .main-content,.main{margin-left:0;padding:16px}
        }
    </style>
</head>
<body>
    <?php include(__DIR__ . '/sidebar.php'); ?>

<script>
(function(){
    function findMain(doc){
        return doc.querySelector('.main-content') || doc.querySelector('.main') || doc.querySelector('.content') || doc.querySelector('main');
    }
    document.addEventListener('click', function(e){
        var a = e.target.closest('.nav-link');
        if(!a) return;
        var href = a.getAttribute('href');
        if(!href) return;
        if(a.dataset && a.dataset.noAjax) return;
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
                    history.pushState({ajax:true}, '', href);
                } else {
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
