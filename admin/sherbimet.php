
<?php
require_once __DIR__ . '/../config/database.php';

$message = '';
$error = '';
$editService = null;

function h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function serviceIcon(string $name): string {
    $name = mb_strtolower($name, 'UTF-8');

    if (str_contains($name, 'flok')) return '✂';
    if (str_contains($name, 'manikyr') || str_contains($name, 'thonj')) return '◇';
    if (str_contains($name, 'pedikyr')) return '◈';
    if (str_contains($name, 'make') || str_contains($name, 'grim')) return '✧';
    if (str_contains($name, 'masazh')) return '❋';
    if (str_contains($name, 'fytyr') || str_contains($name, 'facial')) return '❀';
    if (str_contains($name, 'depilim')) return '✦';

    return '✧';
}

function serviceCategory(string $name): string {
    $name = mb_strtolower($name, 'UTF-8');

    if (str_contains($name, 'flok')) return 'STILIMI I FLOKËVE';
    if (str_contains($name, 'manikyr') || str_contains($name, 'thonj')) return 'KUJDESI PËR DUART';
    if (str_contains($name, 'pedikyr')) return 'KUJDESI PËR KËMBËT';
    if (str_contains($name, 'make') || str_contains($name, 'grim')) return 'MAKE-UP';
    if (str_contains($name, 'masazh')) return 'RELAX & WELLNESS';
    if (str_contains($name, 'fytyr') || str_contains($name, 'facial')) return 'KUJDESI PËR FYTYRËN';
    if (str_contains($name, 'depilim')) return 'KUJDESI PËR LËKURËN';

    return 'BEAUTY SERVICE';
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        if ($action === 'shto' || $action === 'ndrysho') {
            $emri = trim($_POST['emri'] ?? '');
            $cmimi = $_POST['cmimi'] ?? '';
            $kohezgjatja = $_POST['kohezgjatja_min'] ?? '';

            if ($emri === '' || mb_strlen($emri, 'UTF-8') > 150) {
                throw new RuntimeException('Shkruaje emrin e shërbimit (maksimumi 150 karaktere).');
            }

            if (!is_numeric($cmimi) || (float)$cmimi < 0) {
                throw new RuntimeException('Vendose një çmim të vlefshëm.');
            }

            $kohezgjatjaInt = filter_var($kohezgjatja, FILTER_VALIDATE_INT);

            if ($kohezgjatjaInt === false || $kohezgjatjaInt < 1) {
                throw new RuntimeException('Kohëzgjatja duhet të jetë së paku 1 minutë.');
            }

            if ($action === 'shto') {
                $stmt = $pdo->prepare(
                    'INSERT INTO sherbimet (emri, cmimi, kohezgjatja_min)
                     VALUES (:emri, :cmimi, :kohezgjatja)'
                );

                $stmt->execute([
                    'emri' => $emri,
                    'cmimi' => $cmimi,
                    'kohezgjatja' => $kohezgjatjaInt
                ]);

                header('Location: sherbimet.php?mesazh=shtuar');
                exit;
            }

            $id = filter_var($_POST['sherbim_id'] ?? '', FILTER_VALIDATE_INT);

            if (!$id) {
                throw new RuntimeException('Shërbimi i zgjedhur nuk është valid.');
            }

            $stmt = $pdo->prepare(
                'UPDATE sherbimet
                 SET emri = :emri,
                     cmimi = :cmimi,
                     kohezgjatja_min = :kohezgjatja
                 WHERE sherbim_id = :id'
            );

            $stmt->execute([
                'emri' => $emri,
                'cmimi' => $cmimi,
                'kohezgjatja' => $kohezgjatjaInt,
                'id' => $id
            ]);

            header('Location: sherbimet.php?mesazh=ndryshuar');
            exit;
        }

        if ($action === 'fshij') {
            $id = filter_var($_POST['sherbim_id'] ?? '', FILTER_VALIDATE_INT);

            if (!$id) {
                throw new RuntimeException('ID-ja e shërbimit nuk është valide.');
            }

            $stmt = $pdo->prepare(
                'DELETE FROM sherbimet WHERE sherbim_id = :id'
            );
            $stmt->execute(['id' => $id]);

            header('Location: sherbimet.php?mesazh=fshire');
            exit;
        }
    }
} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        $error = 'Ky shërbim mund të jetë i lidhur me termine. Kontrollo lidhjet në databazë para fshirjes.';
    } else {
        $error = 'Ndodhi një gabim në databazë. Kontrollo tabelën sherbimet dhe kolonat e saj.';
    }
} catch (RuntimeException $e) {
    $error = $e->getMessage();
}

$messages = [
    'shtuar' => 'Shërbimi u shtua me sukses.',
    'ndryshuar' => 'Ndryshimet u ruajtën me sukses.',
    'fshire' => 'Shërbimi u fshi me sukses.'
];

if (isset($_GET['mesazh'])) {
    $message = $messages[$_GET['mesazh']] ?? '';
}

$search = trim($_GET['kerko'] ?? '');

try {
    if (isset($_GET['edit'])) {
        $editId = filter_var($_GET['edit'], FILTER_VALIDATE_INT);

        if ($editId) {
            $stmt = $pdo->prepare(
                'SELECT sherbim_id, emri, cmimi, kohezgjatja_min
                 FROM sherbimet
                 WHERE sherbim_id = :id'
            );
            $stmt->execute(['id' => $editId]);
            $editService = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }
    }

    if ($search !== '') {
        $stmt = $pdo->prepare(
            'SELECT sherbim_id, emri, cmimi, kohezgjatja_min
             FROM sherbimet
             WHERE emri LIKE :kerko
             ORDER BY emri ASC'
        );
        $stmt->execute(['kerko' => '%' . $search . '%']);
        $sherbimet = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $stmt = $pdo->query(
            'SELECT sherbim_id, emri, cmimi, kohezgjatja_min
             FROM sherbimet
             ORDER BY emri ASC'
        );
        $sherbimet = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $stats = $pdo->query(
        'SELECT COUNT(*) AS total,
                COALESCE(AVG(cmimi), 0) AS mesatarja,
                COALESCE(MIN(cmimi), 0) AS minimumi
         FROM sherbimet'
    )->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $sherbimet = [];
    $stats = ['total' => 0, 'mesatarja' => 0, 'minimumi' => 0];

    if ($error === '') {
        $error = 'Nuk mund të lexohen shërbimet. Kontrollo lidhjen me databazën dhe emrat e kolonave.';
    }
}

$total = (int)($stats['total'] ?? 0);
$averagePrice = (float)($stats['mesatarja'] ?? 0);
$minPrice = (float)($stats['minimumi'] ?? 0);
$displayCount = count($sherbimet);
?>
<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shërbimet | LUMIÈRE Beauty Studio</title>

    <style>
       
:root {
    --cream: #f7f4ef;
    --paper: #fffdf9;
    --brown: #302820;
    --brown-dark: #302820;
    --brown-soft: #8b8176;
    --gold: #947c64;
    --line: #eae2d8;
    --muted: #8b8176;
    --pale: #eee5da;
}


        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: var(--cream);
            color: var(--brown);
            font-family: "Segoe UI", Arial, sans-serif;
        }

        a {
            color: inherit;
        }

        .layout {
            display: flex;
            min-height: 100vh;
        }
/* SIDEBAR - NJËJTË SI PËRMBLEDHJA */
.sidebar {
    width: 250px;
    flex-shrink: 0;
    background: #302820;
    color: #f8f3eb;
    padding: 30px 18px;
}

.brand {
    padding: 0;
    margin: 0;
    border: 0;
    text-align: center;
    font-family: Georgia, serif;
    font-size: 27px;
    font-weight: 400;
    letter-spacing: 4px;
}

.brand small {
    display: block;
    margin-top: 8px;
    font-family: Arial, sans-serif;
    font-size: 9px;
    letter-spacing: 3px;
}

.sidebar-label {
    margin: 40px 12px 12px;
    color: #b9a995;
    font-size: 10px;
    letter-spacing: 2px;
    text-transform: uppercase;
}

.nav-link {
    display: block;
    margin: 5px 0;
    padding: 13px 14px;
    border-radius: 8px;
    color: #eee5da;
    text-decoration: none;
    font-size: 13px;
}

.nav-link.active,
.nav-link:hover {
    background: #514438;
    color: #fff;
}

        /* faqja*/
        .main {
            min-width: 0;
            flex: 1;
            padding: 34px clamp(20px, 4vw, 58px) 28px;
        }

        .topline {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 18px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--line);
            color: var(--muted);
            font-size: 11px;
            letter-spacing: 1.6px;
            text-transform: uppercase;
        }

        .topline-right {
            letter-spacing: .5px;
            text-transform: none;
        }

        .hero {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 28px;
            padding: 39px 0 31px;
        }

        .hero-label {
            margin-bottom: 13px;
            color: var(--gold);
            font-size: 10px;
            letter-spacing: 3px;
            text-transform: uppercase;
        }

        h1 {
            margin: 0;
            font: 400 clamp(34px, 4vw, 51px)/1.1 Georgia, serif;
            letter-spacing: -1.4px;
        }

        .hero-description {
            max-width: 450px;
            margin: 13px 0 0;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.8;
        }

        .primary-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            min-height: 45px;
            padding: 0 20px;
            border: 1px solid var(--brown);
            border-radius: 3px;
            background: var(--brown);
            color: white;
            font-size: 12px;
            text-decoration: none;
            cursor: pointer;
            transition: .2s;
            white-space: nowrap;
        }

        .primary-btn:hover {
            background: #6a4b38;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            margin: 4px 0 38px;
            background: var(--paper);
            border: 1px solid var(--line);
        }

        .stat {
            padding: 20px 25px;
            border-right: 1px solid var(--line);
        }

        .stat:last-child {
            border-right: 0;
        }

        .stat-label {
            color: var(--muted);
            font-size: 11px;
            letter-spacing: .8px;
            text-transform: uppercase;
        }

        .stat-value {
            margin-top: 10px;
            font: 400 29px Georgia, serif;
            color: var(--brown);
        }

        .stat-note {
            margin-top: 5px;
            color: #a49587;
            font-size: 11px;
        }

        /* formulari */
        .form-panel {
            margin-bottom: 28px;
            padding: 25px;
            border: 1px solid #d9c7b4;
            background: #f0e6da;
        }

        .form-heading {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 15px;
            margin-bottom: 20px;
        }

        .form-heading h2 {
            margin: 0;
            font: 400 25px Georgia, serif;
        }

        .form-heading p {
            margin: 7px 0 0;
            color: var(--brown-soft);
            font-size: 12px;
        }

        .close-link {
            color: var(--brown-soft);
            font-size: 12px;
            text-decoration: none;
        }

        .fields {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr auto;
            align-items: end;
            gap: 14px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #6c5443;
            font-size: 11px;
            letter-spacing: .4px;
        }

        input {
            width: 100%;
            min-width: 0;
            min-height: 43px;
            padding: 11px 12px;
            border: 1px solid #ddcbb8;
            border-radius: 2px;
            outline: none;
            background: #fffdf9;
            color: var(--brown);
            font: inherit;
            font-size: 13px;
        }

        input:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 2px #b0926c20;
        }

        /* lista horizontale */
        .catalog-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 18px;
            margin-bottom: 18px;
        }

        .section-label {
            color: var(--gold);
            font-size: 10px;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .catalog-head h2 {
            margin: 7px 0 0;
            font: 400 29px Georgia, serif;
        }

        .catalog-count {
            color: var(--muted);
            font-size: 12px;
            white-space: nowrap;
        }

        .search-form {
            display: flex;
            gap: 7px;
            width: min(100%, 340px);
        }

        .search-form input {
            min-height: 39px;
            background: transparent;
            border-color: #ddcfc0;
        }

        .search-btn {
            padding: 0 14px;
            border: 1px solid #d8c7b5;
            border-radius: 2px;
            background: #eee4d8;
            color: var(--brown);
            cursor: pointer;
            font-size: 12px;
        }

        .list-heading {
            display: grid;
            grid-template-columns: 1fr 145px 105px 150px;
            gap: 15px;
            padding: 12px 19px;
            background: #eae0d4;
            color: #8b7462;
            font-size: 10px;
            letter-spacing: 1.3px;
            text-transform: uppercase;
        }

        .service-row {
            display: grid;
            grid-template-columns: 1fr 145px 105px 150px;
            align-items: center;
            gap: 15px;
            min-height: 91px;
            padding: 15px 19px;
            border: 1px solid var(--line);
            border-top: 0;
            background: var(--paper);
            transition: background .2s;
        }

        .service-row:hover {
            background: #fbf5ed;
        }

        .service-main {
            display: flex;
            align-items: center;
            gap: 16px;
            min-width: 0;
        }

        .service-symbol {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 47px;
            height: 47px;
            flex-shrink: 0;
            border: 1px solid #e1d0bd;
            border-radius: 50%;
            color: #947657;
            font: 400 23px Georgia, serif;
            background: #f8f0e6;
        }

        .service-number {
            margin-bottom: 5px;
            color: #ad967e;
            font-size: 9px;
            letter-spacing: 1.5px;
        }

        .service-name {
            overflow-wrap: anywhere;
            font: 400 19px Georgia, serif;
            color: var(--brown);
        }

        .service-category {
            margin-top: 5px;
            color: #a08d7c;
            font-size: 9px;
            letter-spacing: 1px;
        }

        .row-price {
            font: 400 21px Georgia, serif;
            white-space: nowrap;
        }

        .row-duration {
            color: #7e6a5b;
            font-size: 12px;
            white-space: nowrap;
        }

        .row-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 7px;
        }

        .action-link,
        .delete-btn {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            min-height: 33px;
            padding: 0 10px;
            border: 1px solid #e4d6c8;
            border-radius: 2px;
            background: transparent;
            color: #6c5443;
            font-size: 11px;
            text-decoration: none;
            cursor: pointer;
        }

        .action-link:hover {
            background: #eee3d7;
        }

        .delete-btn {
            color: #98594c;
            border-color: #ead6cf;
        }

        .delete-btn:hover {
            background: #f7e9e4;
        }

        .empty {
            padding: 45px 20px;
            border: 1px solid var(--line);
            border-top: 0;
            background: var(--paper);
            text-align: center;
            color: var(--muted);
            font: 400 18px Georgia, serif;
        }

        .empty small {
            display: block;
            margin-top: 9px;
            font: 12px "Segoe UI", Arial, sans-serif;
        }

        .notice {
            padding: 13px 16px;
            margin-bottom: 18px;
            font-size: 13px;
            border: 1px solid;
        }

        .success {
            color: #42633b;
            background: #edf3e9;
            border-color: #d8e5d1;
        }

        .error {
            color: #914638;
            background: #f9ebe7;
            border-color: #efd2ca;
        }

        .bottom-note {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            margin-top: 27px;
            padding-top: 16px;
            border-top: 1px solid var(--line);
            color: #a39487;
            font: italic 12px Georgia, serif;
        }

        @media (max-width: 1050px) {
            .sidebar {
                width: 215px;
            }

            .list-heading,
            .service-row {
                grid-template-columns: minmax(190px, 1fr) 90px 85px 135px;
                gap: 10px;
            }

            .service-row {
                padding: 13px;
            }

            .service-main {
                gap: 10px;
            }

            .service-symbol {
                width: 40px;
                height: 40px;
            }

            .action-link,
            .delete-btn {
                padding: 0 7px;
            }
        }

        @media (max-width: 760px) {
            .layout {
                display: block;
            }

            .sidebar {
                width: 100%;
                padding: 20px;
            }

            .nav {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 3px;
            }

            .main {
                padding: 22px 15px;
            }

            .hero {
                align-items: flex-start;
                flex-direction: column;
                padding: 27px 0;
            }

            .stats {
                grid-template-columns: 1fr;
            }

            .stat {
                border-right: 0;
                border-bottom: 1px solid var(--line);
            }

            .stat:last-child {
                border-bottom: 0;
            }

            .fields {
                grid-template-columns: 1fr;
            }

            .catalog-head {
                align-items: flex-start;
                flex-direction: column;
            }

            .search-form {
                width: 100%;
            }

            .list-heading {
                display: none;
            }

            .service-row {
                grid-template-columns: minmax(0, 1fr) auto;
                gap: 13px;
                padding: 17px 13px;
                border-top: 1px solid var(--line);
            }

            .service-main {
                grid-column: 1 / -1;
            }

            .row-price {
                align-self: center;
            }

            .row-duration {
                align-self: center;
                text-align: right;
            }

            .row-actions {
                grid-column: 1 / -1;
                justify-content: flex-start;
                padding-left: 53px;
            }

            .bottom-note {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
<div class="layout">

    <aside class="sidebar">
    <div class="brand">
        LUMIÈRE
        <small>BEAUTY STUDIO</small>
    </div>

    <p class="sidebar-label">MENU KRYESORE</p>

    <a class="nav-link" href="index.php">◈ &nbsp; Përmbledhja</a>
    <a class="nav-link" href="terminet.php">▦ &nbsp; Terminet</a>
    <a class="nav-link" href="klientet.php">♙ &nbsp; Klientët</a>
    <a class="nav-link active" href="sherbimet.php">✧ &nbsp; Shërbimet</a>
    <a class="nav-link" href="stafi.php">♧ &nbsp; Stafi</a>

    <p class="sidebar-label">FINANCAT &amp; MENAXHIMI</p>

    <a class="nav-link" href="pagesat.php">€ &nbsp; Pagesat</a>
    <a class="nav-link" href="shpenzimet.php">↗ &nbsp; Shpenzimet</a>
    <a class="nav-link" href="inventari.php">▤ &nbsp; Inventari</a>
    <a class="nav-link" href="raportet.php">▥ &nbsp; Raportet</a>

    <p class="sidebar-label">SISTEMI</p>

    <a class="nav-link" href="settings.php">⚙ &nbsp; Cilësimet</a>
    <a class="nav-link" href="login.php">⇥ &nbsp; Kyçu</a>
</aside>

    <main class="main">
        <div class="topline">
            <span>LUMIÈRE &nbsp; / &nbsp; KATALOGU</span>
            <span class="topline-right"><?= date('d.m.Y') ?></span>
        </div>

        <header class="hero">
            <div>
                <div class="hero-label">The art of beauty</div>
                <h1>Shërbimet tona<span style="color:#b0926c">.</span></h1>
                <p class="hero-description">
                    Çdo detaj ka rëndësi. Menaxho katalogun e shërbimeve,
                    çmimet dhe kohëzgjatjen në një hapësirë të dedikuar.
                </p>
            </div>

            <a class="primary-btn" href="sherbimet.php?shto=1#formulari">
                <span style="font-size:18px">+</span> Shto shërbim
            </a>
        </header>

        <?php if ($message !== ''): ?>
            <div class="notice success"><?= h($message) ?></div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="notice error"><?= h($error) ?></div>
        <?php endif; ?>

        <section class="stats">
            <div class="stat">
                <div class="stat-label">Shërbime në katalog</div>
                <div class="stat-value"><?= $total ?></div>
                <div class="stat-note">Numri i përgjithshëm i shërbimeve</div>
            </div>

            <div class="stat">
                <div class="stat-label">Çmimi mesatar</div>
                <div class="stat-value"><?= number_format($averagePrice, 2) ?> €</div>
                <div class="stat-note">Mesatarja e çmimeve të regjistruara</div>
            </div>

            <div class="stat">
                <div class="stat-label">Çmimi më i ulët</div>
                <div class="stat-value"><?= number_format($minPrice, 2) ?> €</div>
                <div class="stat-note">Çmimi minimal në katalog</div>
            </div>
        </section>

        <?php if (isset($_GET['shto']) || $editService): ?>
            <section class="form-panel" id="formulari">
                <div class="form-heading">
                    <div>
                        <h2><?= $editService ? 'Përditëso shërbimin' : 'Shërbim i ri' ?></h2>
                        <p>
                            <?= $editService
                                ? 'Ndrysho të dhënat dhe ruaji ndryshimet.'
                                : 'Shto një shërbim të ri në katalogun e studios.' ?>
                        </p>
                    </div>
                    <a class="close-link" href="sherbimet.php">Mbyll ✕</a>
                </div>

                <form method="POST" action="sherbimet.php">
                    <input
                        type="hidden"
                        name="action"
                        value="<?= $editService ? 'ndrysho' : 'shto' ?>"
                    >

                    <?php if ($editService): ?>
                        <input
                            type="hidden"
                            name="sherbim_id"
                            value="<?= h($editService['sherbim_id']) ?>"
                        >
                    <?php endif; ?>

                    <div class="fields">
                        <div>
                            <label for="emri">EMRI I SHËRBIMIT</label>
                            <input
                                id="emri"
                                name="emri"
                                type="text"
                                maxlength="150"
                                placeholder="P.sh. Manikyr"
                                value="<?= h($editService['emri'] ?? '') ?>"
                                required
                            >
                        </div>

                        <div>
                            <label for="cmimi">ÇMIMI (€)</label>
                            <input
                                id="cmimi"
                                name="cmimi"
                                type="number"
                                min="0"
                                step="0.01"
                                placeholder="15.00"
                                value="<?= h($editService['cmimi'] ?? '') ?>"
                                required
                            >
                        </div>

                        <div>
                            <label for="kohezgjatja_min">KOHËZGJATJA (MIN)</label>
                            <input
                                id="kohezgjatja_min"
                                name="kohezgjatja_min"
                                type="number"
                                min="1"
                                step="1"
                                placeholder="45"
                                value="<?= h($editService['kohezgjatja_min'] ?? '') ?>"
                                required
                            >
                        </div>

                        <div>
                            <button class="primary-btn" type="submit">
                                <?= $editService ? 'Ruaj ndryshimet' : 'Ruaj shërbimin' ?>
                            </button>
                        </div>
                    </div>
                </form>
            </section>
        <?php endif; ?>

        <section class="catalog">
            <div class="catalog-head">
                <div>
                    <div class="section-label">Koleksioni ynë</div>
                    <h2>Katalogu i shërbimeve</h2>
                </div>

                <div class="catalog-count">
                    <?= $displayCount ?> rezultate
                </div>
            </div>

            <form class="search-form" method="GET" action="sherbimet.php" style="margin-bottom:18px">
                <input
                    type="search"
                    name="kerko"
                    placeholder="Kërko sipas emrit..."
                    value="<?= h($search) ?>"
                >
                <button class="search-btn" type="submit">Kërko</button>

                <?php if ($search !== ''): ?>
                    <a class="action-link" href="sherbimet.php">Pastro</a>
                <?php endif; ?>
            </form>

            <div class="list-heading">
                <span>Shërbimi</span>
                <span>Çmimi</span>
                <span>Kohëzgjatja</span>
                <span style="text-align:right">Veprimet</span>
            </div>

            <?php if (!empty($sherbimet)): ?>
                <?php foreach ($sherbimet as $index => $s): ?>
                    <article class="service-row">
                        <div class="service-main">
                            <div class="service-symbol">
                                <?= h(serviceIcon($s['emri'])) ?>
                            </div>

                            <div>
                                <div class="service-number">
                                    SERVICE № <?= str_pad((string)($index + 1), 2, '0', STR_PAD_LEFT) ?>
                                </div>

                                <div class="service-name">
                                    <?= h($s['emri']) ?>
                                </div>

                                <div class="service-category">
                                    <?= h(serviceCategory($s['emri'])) ?>
                                </div>
                            </div>
                        </div>

                        <div class="row-price">
                            <?= number_format((float)$s['cmimi'], 2) ?> €
                        </div>

                        <div class="row-duration">
                            ◷ &nbsp; <?= (int)$s['kohezgjatja_min'] ?> min
                        </div>

                        <div class="row-actions">
                            <a
                                class="action-link"
                                href="sherbimet.php?edit=<?= (int)$s['sherbim_id'] ?>#formulari"
                            >Ndrysho</a>

                            <form
                                method="POST"
                                action="sherbimet.php"
                                onsubmit="return confirm('A dëshiron vërtet ta fshish këtë shërbim?');"
                            >
                                <input type="hidden" name="action" value="fshij">
                                <input
                                    type="hidden"
                                    name="sherbim_id"
                                    value="<?= (int)$s['sherbim_id'] ?>"
                                >
                                <button class="delete-btn" type="submit">Fshij</button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty">
                    Nuk u gjet asnjë shërbim.
                    <small>
                        <?= $search !== ''
                            ? 'Provo një fjalë tjetër në kërkim.'
                            : 'Kliko “Shto shërbim” për ta krijuar shërbimin e parë.' ?>
                    </small>
                </div>
            <?php endif; ?>
        </section>

        <footer class="bottom-note">
            <span>LUMIÈRE Beauty Studio</span>
            <span>Beauty is in every detail.</span>
        </footer>
    </main>
</div>
</body>
</html>
