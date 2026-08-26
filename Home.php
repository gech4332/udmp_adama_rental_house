<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Adama Property Hub</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

<style>

*{
    box-sizing:border-box;
}


html,body{

    width:100%;
    height:100%;
    margin:0;
    padding:0;
    overflow:hidden;

}


body{

    font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;

}



/* Dropdown */

.dropdown{

    position:relative;
    display:inline-block;

}


.dropdown-content{

    display:none;
    position:absolute;
    right:0;
    background:white;
    min-width:160px;
    box-shadow:0 8px 16px rgba(0,0,0,0.2);
    z-index:100;
    border-radius:8px;

}


.dropdown-content a{

    color:#333 !important;
    padding:12px 16px;
    text-decoration:none;
    display:block;
    font-size:14px;

}


.dropdown-content a:hover{

    background:#f1f1f1;

}


.dropdown:hover .dropdown-content{

    display:block;

}



/* HERO */

.hero{

    position:relative;
    height:65vh;
    width:100%;
    background:#000;
    color:white;
    text-align:center;
    overflow:hidden;

}



.hero img.bg-image{

    width:100%;
    height:100%;
    object-fit:cover;
    opacity:0.6;
    position:absolute;
    top:0;
    left:0;

}



/* NAV */

.top-nav{

    position:relative;
    z-index:10;
    padding:15px 35px;
    display:flex;
    justify-content:flex-end;

}


.top-nav a{

    color:white;
    text-decoration:none;
    font-size:24px;
    text-shadow:1px 1px 4px black;

}


.top-nav a:hover{

    color:#ffc107;

}



/* HERO CONTENT */

.hero-content{

    position:relative;
    z-index:2;
    padding:20px;
    margin:auto;

}


.hero-content h1{

    font-size:clamp(25px,4vw,50px);
    margin:10px;

}


.hero-content p{

    font-size:clamp(14px,2vw,20px);
    margin:10px auto;

}



/* BUTTON */

.btn{

    background:#ffc107;
    padding:10px 25px;
    color:black;
    text-decoration:none;
    border-radius:5px;
    font-weight:bold;
    display:inline-block;

}


.btn:hover{

    background:#e0a800;

}



/* CATEGORY */

.categories{

    height:30vh;
    display:flex;
    justify-content:center;
    align-items:center;
    gap:15px;
    background:#f9f9f9;
    padding:10px;
    overflow:hidden;

}



.cat-card{

    background:white;
    padding:15px;
    width:230px;
    height:auto;
    border-radius:10px;
    box-shadow:0 4px 6px rgba(0,0,0,0.1);
    text-align:center;

}


.cat-card:hover{

    transform:translateY(-5px);

}



.cat-card i{

    font-size:35px;
    color:#4b7b8a;
    margin-bottom:5px;

}


.cat-card h3{

    margin:5px;

}


.cat-card p{

    font-size:13px;

}



/* FOOTER */

footer{

    height:5vh;
    background:#4b7b8a;
    color:white;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:14px;

}



/* MOBILE */

@media(max-width:600px){


.hero{

    height:65vh;

}


.top-nav{

    padding:10px 20px;

}


.hero-content h1{

    font-size:24px;

}


.hero-content p{

    font-size:13px;

}


.categories{

    height:30vh;
    flex-direction:column;

}


/* Keep screen fitting */

.cat-card{

    width:90%;
    padding:8px;

}


/* Hide extra cards if screen is too small */

.cat-card:nth-child(n+2){

    display:none;

}


footer{

    height:5vh;
    font-size:12px;

}


}



</style>

</head>


<body>


<?php

$hero_title = "A Unified Digital Marketplace for Adama Rent House";

$hero_desc  = "Find Simple Homes, Luxury Real Estate, and Commercial Spaces in the heart of Adama.";

$btn_text   = "Search Properties";

?>



<header class="hero">


<img src="adama1.webp" alt="Adama City" class="bg-image">



<nav class="top-nav">


<div class="dropdown">


<a href="#" title="Login">

<i class="fa-solid fa-user-circle"></i>

</a>



<div class="dropdown-content">


<a href="admin_login.php">

<i class="fa-solid fa-user-shield"></i>

Admin Login

</a>


<a href="login.php">

<i class="fa-solid fa-user"></i>

Guest/Landlord

</a>


</div>


</div>


</nav>





<div class="hero-content">


<h1>

<?php echo $hero_title; ?>

</h1>


<p>

<?php echo $hero_desc; ?>

</p>


<br>


<a href="index.php" class="btn">

<?php echo $btn_text; ?>

</a>


</div>


</header>





<section class="categories">



<div class="cat-card">


<i class="fas fa-bed"></i>


<h3>

Simple Homes

</h3>


<p>

Rooms & Traditional Houses for Students and Workers.

</p>


</div>





<div class="cat-card">


<i class="fas fa-building"></i>


<h3>

Real Estate

</h3>


<p>

Modern Apartments and Luxury Villas in Adama.

</p>


</div>





<div class="cat-card">


<i class="fas fa-store"></i>


<h3>

Commercial

</h3>


<p>

Offices, Shops, and Warehouses for Businesses.

</p>


</div>



</section>





<footer>


<p>

&copy; <?php echo date("Y"); ?> By Getahun Million. All rights reserved.

</p>
</footer>
</body>
</html>