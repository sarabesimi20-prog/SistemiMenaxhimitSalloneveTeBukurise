<?php
// Meny e përbashkët e LUMIÈRE Beauty Studio për të gjitha faqet e adminit.
$currentPage = basename($_SERVER['PHP_SELF'] ?? 'index.php');
$sidebarLinks = [
    ['file' => 'index.php',       'label' => 'Përmbledhja', 'icon' => '◈', 'group' => 'MENU KRYESORE'],
    ['file' => 'terminet.php',    'label' => 'Terminet',    'icon' => '▦', 'group' => 'MENU KRYESORE'],
    ['file' => 'klientet.php',    'label' => 'Klientët',   'icon' => '♙', 'group' => 'MENU KRYESORE'],
    ['file' => 'sherbimet.php',   'label' => 'Shërbimet',   'icon' => '✧', 'group' => 'MENU KRYESORE'],
    ['file' => 'stafi.php',       'label' => 'Stafi',       'icon' => '♧', 'group' => 'MENU KRYESORE'],
    ['file' => 'pagesat.php',     'label' => 'Pagesat',     'icon' => '€', 'group' => 'FINANCAT & MENAXHIMI'],
    ['file' => 'shpenzimet.php',  'label' => 'Shpenzimet',  'icon' => '↗', 'group' => 'FINANCAT & MENAXHIMI'],
    ['file' => 'inventari.php',   'label' => 'Inventari',   'icon' => '▤', 'group' => 'FINANCAT & MENAXHIMI'],
    ['file' => 'raportet.php',    'label' => 'Raportet',    'icon' => '▥', 'group' => 'FINANCAT & MENAXHIMI'],
    ['file' => 'settings.php',    'label' => 'Cilësimet',   'icon' => '⚙', 'group' => 'SISTEMI'],
    ['file' => 'login.php',       'label' => 'Kyçu',        'icon' => '⇥', 'group' => 'SISTEMI'],
];
$activePage = $currentPage;
if ($currentPage === 'profili-klientit.php') $activePage = 'klientet.php';
if ($currentPage === 'profili-stafit.php') $activePage = 'stafi.php';
?>
<aside class="sidebar">
    <div class="brand">
        LUMIÈRE
        <small>BEAUTY STUDIO</small>
    </div>

    <?php $lastGroup = null; foreach ($sidebarLinks as $link): ?>
        <?php if ($link['group'] !== $lastGroup): ?>
            <p class="sidebar-label"><?= htmlspecialchars($link['group'], ENT_QUOTES, 'UTF-8') ?></p>
            <?php $lastGroup = $link['group']; ?>
        <?php endif; ?>
        <a
            class="nav-link<?= $activePage === $link['file'] ? ' active' : '' ?>"
            href="<?= htmlspecialchars($link['file'], ENT_QUOTES, 'UTF-8') ?>"
            <?= $activePage === $link['file'] ? 'aria-current="page"' : '' ?>
        ><?= htmlspecialchars($link['icon'], ENT_QUOTES, 'UTF-8') ?>&nbsp;&nbsp; <?= htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8') ?></a>
    <?php endforeach; ?>
</aside>