
<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$pageTitle = $pageTitle ?? 'LUMIÈRE Beauty Studio';
?>
<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="description" content="LUMIÈRE Beauty Studio - The Art of Beauty.">

    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Italiana&family=Playfair+Display:ital,wght@0,400;0,500;1,400&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="assets/css/website.css">
    <link rel="stylesheet" href="assets/css/pages.css?v=2">
</head>

<body>

<div class="announcement-bar">
    A BEAUTY EXPERIENCE, MADE FOR YOU
    <span>✧</span>
    WELCOME TO LUMIÈRE
</div>

<header class="site-header">

    <a href="index.php" class="brand">
        <span class="brand-name">LUMIÈRE</span>
        <span class="brand-subtitle">BEAUTY STUDIO</span>
    </a>

    <nav class="main-navigation" id="main-navigation">
        <a href="index.php"
           class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">
            Ballina
        </a>

        <a href="services.php"
           class="<?= $currentPage === 'services.php' ? 'active' : '' ?>">
            Shërbimet
        </a>

        <a href="team.php"
           class="<?= $currentPage === 'team.php' ? 'active' : '' ?>">
            Ekipi
        </a>

        <a href="gallery.php"
           class="<?= $currentPage === 'gallery.php' ? 'active' : '' ?>">
            Galeria
        </a>

        <a href="about.php"
           class="<?= $currentPage === 'about.php' ? 'active' : '' ?>">
            Rreth nesh
        </a>

        <a href="contact.php"
           class="<?= $currentPage === 'contact.php' ? 'active' : '' ?>">
            Kontakti
        </a>
    </nav>
<a href="my-bookings.php">Rezervimet e mia</a>
    <a href="booking.php" class="header-booking">
        REZERVO TERMIN <span>↗</span>
    </a>

    <button class="menu-toggle" id="menu-toggle"
            aria-label="Hap menynë"
            aria-expanded="false">
        <span></span>
        <span></span>
    </button>

</header>
