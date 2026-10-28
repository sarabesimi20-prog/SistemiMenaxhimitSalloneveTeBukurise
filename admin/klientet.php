
<?php
require_once __DIR__ . '/../config/database.php';

$message = '';
$error = '';
$editClient = null;
$clients = [];
$stats = [
    'total' => 0,
    'new' => 0
];

function h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        if ($action === 'shto' || $action === 'ndrysho') {
            $emri = trim($_POST['emri'] ?? '');
            $mbiemri = trim($_POST['mbiemri'] ?? '');
            $telefoni = trim($_POST['telefoni'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $adresa = trim($_POST['adresa'] ?? '');

            if ($emri === '' || $mbiemri === '') {
                throw new RuntimeException(
                    'Emri dhe mbiemri janë të detyrueshëm.'
                );
            }

            if (mb_strlen($emri) > 100 || mb_strlen($mbiemri) > 100) {
                throw new RuntimeException(
                    'Emri dhe mbiemri nuk mund të kenë më shumë se 100 karaktere.'
                );
            }

            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('Adresa e emailit nuk është valide.');
            }

            if (mb_strlen($telefoni) > 20 || mb_strlen($email) > 150 || mb_strlen($adresa) > 255) {
                throw new RuntimeException('Njëra nga fushat i tejkalon kufijtë e lejuar.');
            }

            if ($action === 'shto') {
                $stmt = $pdo->prepare(
                    'INSERT INTO klientet
                    (emri, mbiemri, telefoni, email, adresa)
                    VALUES
                    (:emri, :mbiemri, :telefoni, :email, :adresa)'
                );

                $stmt->execute([
                    'emri' => $emri,
                    'mbiemri' => $mbiemri,
                    'telefoni' => $telefoni !== '' ? $telefoni : null,
                    'email' => $email !== '' ? $email : null,
                    'adresa' => $adresa !== '' ? $adresa : null
                ]);

                header('Location: klientet.php?mesazh=shtuar');
                exit;
            }

            $id = filter_var(
                $_POST['klient_id'] ?? '',
                FILTER_VALIDATE_INT
            );

            if (!$id || $id < 1) {
                throw new RuntimeException('Klienti i zgjedhur nuk është valid.');
            }

            $stmt = $pdo->prepare(
                'UPDATE klientet
                 SET emri = :emri,
                     mbiemri = :mbiemri,
                     telefoni = :telefoni,
                     email = :email,
                     adresa = :adresa
                 WHERE klient_id = :id'
            );

            $stmt->execute([
                'emri' => $emri,
                'mbiemri' => $mbiemri,
                'telefoni' => $telefoni !== '' ? $telefoni : null,
                'email' => $email !== '' ? $email : null,
                'adresa' => $adresa !== '' ? $adresa : null,
                'id' => $id
            ]);

            header('Location: klientet.php?mesazh=ndryshuar');
            exit;
        }

        if ($action === 'fshij') {
            $id = filter_var(
                $_POST['klient_id'] ?? '',
                FILTER_VALIDATE_INT
            );

            if (!$id || $id < 1) {
                throw new RuntimeException('ID-ja e klientit nuk është valide.');
            }

            $stmt = $pdo->prepare(
                'DELETE FROM klientet WHERE klient_id = :id'
            );
            $stmt->execute(['id' => $id]);

            header('Location: klientet.php?mesazh=fshire');
            exit;
        }
    }
} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        $error = 'Klienti nuk mund të fshihet sepse mund të ketë të dhëna të lidhura me të.';
    } else {
        $error = 'Ndodhi një gabim gjatë ruajtjes në databazë. Kontrollo lidhjen dhe tabelën klientet.';
    }
} catch (RuntimeException $e) {
    $error = $e->getMessage();
}

$messages = [
    'shtuar' => 'Klienti u regjistrua me sukses.',
    'ndryshuar' => 'Të dhënat e klientit u përditësuan.',
    'fshire' => 'Klienti u fshi me sukses.'
];

if (isset($_GET['mesazh'])) {
    $message = $messages[$_GET['mesazh']] ?? '';
}

$search = trim($_GET['kerko'] ?? '');
$sort = $_GET['rendit'] ?? 'ri';

$sortOptions = [
    'ri' => 'data_regjistrimit DESC, klient_id DESC',
    'emri' => 'emri ASC, mbiemri ASC',
    'vjetër' => 'data_regjistrimit ASC'
];

$orderBy = $sortOptions[$sort] ?? $sortOptions['ri'];

try {
    if (isset($_GET['edit'])) {
        $editId = filter_var($_GET['edit'], FILTER_VALIDATE_INT);

        if ($editId) {
            $stmt = $pdo->prepare(
                'SELECT klient_id, emri, mbiemri, telefoni,
                        email, adresa, data_regjistrimit
                 FROM klientet
                 WHERE klient_id = :id'
            );

            $stmt->execute(['id' => $editId]);
            $editClient = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }
    }

    $stmt = $pdo->query(
        'SELECT COUNT(*) FROM klientet'
    );
    $stats['total'] = (int) $stmt->fetchColumn();

    $stmt = $pdo->query(
        'SELECT COUNT(*) FROM klientet
         WHERE data_regjistrimit >= DATE_FORMAT(CURDATE(), "%Y-%m-01")'
    );
    $stats['new'] = (int) $stmt->fetchColumn();

    $sql = 'SELECT klient_id, emri, mbiemri, telefoni,
                   email, adresa, data_regjistrimit
            FROM klientet';

    if ($search !== '') {
        $sql .= ' WHERE emri LIKE :q
                  OR mbiemri LIKE :q
                  OR telefoni LIKE :q
                  OR email LIKE :q';
    }

    $sql .= ' ORDER BY ' . $orderBy;

    $stmt = $pdo->prepare($sql);

    if ($search !== '') {
        $stmt->execute(['q' => '%' . $search . '%']);
    } else {
        $stmt->execute();
    }

    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = $error ?: 'Nuk mund të lexohen të dhënat e klientëve. Kontrollo databazën.';
}
?>
<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Klientët | LUMIÈRE Beauty Studio</title>

    <style>
        :root {
            --cream: #f7f4ef;
            --paper: #fffdf9;
            --brown: #302820;
            --brown-soft: #8b8176;
            --gold: #947c64;
            --line: #eae2d8;
            --pale: #eee5da;
            --white: #fff;
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

        button, input, select {
            font: inherit;
        }

        a {
            color: inherit;
        }

        .layout {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
    width: 250px;
    flex-shrink: 0;
    background: #302820;
    color: #f8f3eb;
    padding: 30px 18px;
}

.brand {
    font-family: Georgia, serif;
    font-size: 27px;
    letter-spacing: 4px;
    text-align: center;
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

        .main {
            min-width: 0;
            flex: 1;
            padding: 32px clamp(20px, 4vw, 55px);
        }

        .topline {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            padding-bottom: 18px;
            border-bottom: 1px solid var(--line);
            color: var(--brown-soft);
            font-size: 11px;
            letter-spacing: 1.5px;
        }

        .hero {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 25px;
            padding: 36px 0 28px;
        }

        .eyebrow {
            margin-bottom: 12px;
            color: var(--gold);
            font-size: 10px;
            letter-spacing: 3px;
            text-transform: uppercase;
        }

        h1 {
            margin: 0;
            font: 400 clamp(34px, 4vw, 49px)/1.1 Georgia, serif;
            letter-spacing: -1px;
        }

        .description {
            max-width: 520px;
            margin: 12px 0 0;
            color: var(--brown-soft);
            font-size: 14px;
            line-height: 1.8;
        }

        .btn {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            min-height: 42px;
            padding: 0 17px;
            border: 1px solid var(--brown);
            border-radius: 4px;
            background: var(--brown);
            color: white;
            text-decoration: none;
            font-size: 12px;
            cursor: pointer;
            transition: .2s;
            white-space: nowrap;
        }

        .btn:hover {
            background: #514438;
        }

        .btn-light {
            background: var(--paper);
            color: var(--brown);
            border-color: var(--line);
        }

        .btn-light:hover {
            background: var(--pale);
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 32px;
        }

        .stat-card {
            display: flex;
            align-items: center;
            gap: 17px;
            padding: 22px;
            background: var(--paper);
            border: 1px solid var(--line);
            border-radius: 8px;
        }

        .stat-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 48px;
            height: 48px;
            flex-shrink: 0;
            border-radius: 8px;
            background: var(--pale);
            color: var(--gold);
            font-size: 22px;
        }

        .stat-label {
            color: var(--brown-soft);
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .7px;
        }

        .stat-value {
            margin-top: 6px;
            font: 400 29px Georgia, serif;
        }

        .panel {
            margin-bottom: 25px;
            padding: 23px;
            background: var(--paper);
            border: 1px solid var(--line);
            border-radius: 8px;
        }

        .panel-heading {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 15px;
            margin-bottom: 22px;
        }

        .panel-heading h2 {
            margin: 0;
            font: 400 25px Georgia, serif;
        }

        .panel-heading p {
            margin: 7px 0 0;
            color: var(--brown-soft);
            font-size: 12px;
        }

        .fields {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 17px;
        }

        .field label {
            display: block;
            margin-bottom: 8px;
            color: var(--brown-soft);
            font-size: 11px;
            letter-spacing: .6px;
            text-transform: uppercase;
        }

        .field input {
            width: 100%;
            min-height: 43px;
            padding: 10px 12px;
            border: 1px solid var(--line);
            border-radius: 4px;
            outline: none;
            background: #fff;
            color: var(--brown);
            font-size: 13px;
        }

        .field input:focus,
        .search-input:focus,
        .sort-select:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 3px #947c641c;
        }

        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .notice {
            padding: 13px 16px;
            margin-bottom: 18px;
            border: 1px solid;
            border-radius: 5px;
            font-size: 13px;
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

        .directory-heading {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 18px;
            margin-bottom: 19px;
        }

        .directory-heading h2 {
            margin: 0;
            font: 400 29px Georgia, serif;
        }

        .directory-heading p {
            margin: 7px 0 0;
            color: var(--brown-soft);
            font-size: 12px;
        }

        .search-tools {
            display: flex;
            gap: 9px;
            margin-bottom: 18px;
        }

        .search-input,
        .sort-select {
            min-height: 42px;
            padding: 9px 12px;
            border: 1px solid var(--line);
            border-radius: 4px;
            background: var(--paper);
            color: var(--brown);
            outline: none;
            font-size: 12px;
        }

        .search-input {
            flex: 1;
            min-width: 0;
        }

        .sort-select {
            max-width: 190px;
        }

        .table-wrap {
            overflow-x: auto;
            border: 1px solid var(--line);
            border-radius: 6px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            white-space: nowrap;
        }

        thead {
            background: var(--pale);
        }

        th {
            padding: 14px 15px;
            color: #6f6255;
            font-size: 10px;
            font-weight: 600;
            letter-spacing: .8px;
            text-transform: uppercase;
        }

        td {
            padding: 15px;
            border-top: 1px solid var(--line);
            font-size: 12px;
        }

        tbody tr:hover {
            background: #faf7f2;
        }

        .client-cell {
            display: flex;
            align-items: center;
            gap: 11px;
        }

        .avatar {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            flex-shrink: 0;
            border-radius: 50%;
            background: var(--pale);
            color: var(--brown);
            font: 400 13px Georgia, serif;
        }

        .client-name {
            font-weight: 600;
            color: var(--brown);
        }

        .client-id {
            margin-top: 4px;
            color: var(--brown-soft);
            font-size: 10px;
        }

        .muted {
            color: var(--brown-soft);
        }

        .actions {
            display: flex;
            align-items: center;
            gap: 7px;
        }

        .small-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 31px;
            padding: 0 9px;
            border: 1px solid var(--line);
            border-radius: 4px;
            background: transparent;
            color: var(--brown);
            text-decoration: none;
            font-size: 11px;
            cursor: pointer;
        }

        .small-btn:hover {
            background: var(--pale);
        }

        .delete-btn {
            color: #914638;
            border-color: #efd2ca;
        }

        .delete-btn:hover {
            background: #f9ebe7;
        }

        .empty {
            padding: 45px 20px;
            text-align: center;
            color: var(--brown-soft);
        }

        .empty strong {
            display: block;
            margin-bottom: 8px;
            color: var(--brown);
            font: 400 22px Georgia, serif;
        }

        .footer {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            margin-top: 28px;
            padding-top: 17px;
            border-top: 1px solid var(--line);
            color: #8b8176;
            font: italic 12px Georgia, serif;
        }

        @media (max-width: 1050px) {
            .sidebar {
                width: 215px;
            }

            .main {
                padding: 28px 22px;
            }

            .search-tools {
                flex-wrap: wrap;
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
            }

            .stats {
                grid-template-columns: 1fr;
            }

            .fields {
                grid-template-columns: 1fr;
            }

            .search-tools {
                flex-direction: column;
            }

            .sort-select {
                max-width: none;
            }

            .directory-heading {
                align-items: flex-start;
                flex-direction: column;
            }

            .footer {
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
    <a class="nav-link active" href="klientet.php">♙ &nbsp; Klientët</a>
    <a class="nav-link" href="sherbimet.php">✧ &nbsp; Shërbimet</a>
    <a class="nav-link" href="stafi.php">♧ &nbsp; Stafi</a>

    <p class="sidebar-label">FINANCAT &amp; MENAXHIMI</p>

    <a class="nav-link" href="pagesat.php">€ &nbsp; Pagesat</a>
    <a class="nav-link" href="shpenzimet.php">↗ &nbsp; Shpenzimet</a>
    <a class="nav-link" href="inventari.php">▤ &nbsp; Inventari</a>
    <a class="nav-link" href="raportet.php">▥ &nbsp; Raportet</a>

    <p class="sidebar-label">SISTEMI</p>

    <a class="nav-link" href="settings.php">⚙ &nbsp; Cilësimet</a>
    <a class="nav-link" href="logout.php">⇥ &nbsp; Dil nga llogaria</a>
</aside>

    <main class="main">
        <div class="topline">
            <span>LUMIÈRE / MENAXHIMI I KLIENTËVE</span>
            <span><?= date('d.m.Y') ?></span>
        </div>

        <header class="hero">
            <div>
                <div class="eyebrow">Client relationship management</div>
                <h1>Klientët tanë<span style="color:#947c64">.</span></h1>
                <p class="description">
                    Një hapësirë e dedikuar për menaxhimin e klientëve,
                    të dhënave të tyre dhe marrëdhënieve me studion.
                </p>
            </div>

            <a class="btn" href="klientet.php?shto=1#formulari">
                <span style="font-size:18px">+</span> Shto klient
            </a>
        </header>

        <?php if ($message !== ''): ?>
            <div class="notice success"><?= h($message) ?></div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="notice error"><?= h($error) ?></div>
        <?php endif; ?>

        <section class="stats">
            <div class="stat-card">
                <div class="stat-icon">♙</div>
                <div>
                    <div class="stat-label">Gjithsej klientë</div>
                    <div class="stat-value"><?= $stats['total'] ?></div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">✧</div>
                <div>
                    <div class="stat-label">Të regjistruar këtë muaj</div>
                    <div class="stat-value"><?= $stats['new'] ?></div>
                </div>
            </div>
        </section>

        <?php if (isset($_GET['shto']) || $editClient): ?>
            <section class="panel" id="formulari">
                <div class="panel-heading">
                    <div>
                        <h2><?= $editClient ? 'Përditëso klientin' : 'Regjistro klient të ri' ?></h2>
                        <p>Plotëso të dhënat e klientit më poshtë.</p>
                    </div>
                    <a class="small-btn" href="klientet.php">Mbyll ✕</a>
                </div>

                <form method="POST" action="klientet.php">
                    <input type="hidden" name="action"
                           value="<?= $editClient ? 'ndrysho' : 'shto' ?>">

                    <?php if ($editClient): ?>
                        <input type="hidden" name="klient_id"
                               value="<?= h($editClient['klient_id']) ?>">
                    <?php endif; ?>

                    <div class="fields">
                        <div class="field">
                            <label for="emri">Emri *</label>
                            <input id="emri" name="emri" maxlength="100"
                                   value="<?= h($editClient['emri'] ?? '') ?>"
                                   placeholder="Emri i klientit" required>
                        </div>

                        <div class="field">
                            <label for="mbiemri">Mbiemri *</label>
                            <input id="mbiemri" name="mbiemri" maxlength="100"
                                   value="<?= h($editClient['mbiemri'] ?? '') ?>"
                                   placeholder="Mbiemri i klientit" required>
                        </div>

                        <div class="field">
                            <label for="telefoni">Telefoni</label>
                            <input id="telefoni" name="telefoni" type="tel"
                                   maxlength="20"
                                   value="<?= h($editClient['telefoni'] ?? '') ?>"
                                   placeholder="+383 4X XXX XXX">
                        </div>

                        <div class="field">
                            <label for="email">Emaili</label>
                            <input id="email" name="email" type="email"
                                   maxlength="150"
                                   value="<?= h($editClient['email'] ?? '') ?>"
                                   placeholder="email@example.com">
                        </div>

                        <div class="field" style="grid-column:1/-1">
                            <label for="adresa">Adresa</label>
                            <input id="adresa" name="adresa" maxlength="255"
                                   value="<?= h($editClient['adresa'] ?? '') ?>"
                                   placeholder="Qyteti ose adresa e klientit">
                        </div>
                    </div>

                    <div class="form-actions">
                        <button class="btn" type="submit">
                            <?= $editClient ? 'Ruaj ndryshimet' : 'Regjistro klientin' ?>
                        </button>
                        <a class="btn btn-light" href="klientet.php">Anulo</a>
                    </div>
                </form>
            </section>
        <?php endif; ?>

        <section class="panel">
            <div class="directory-heading">
                <div>
                    <h2>Client Directory</h2>
                    <p>Lista dhe të dhënat e klientëve të studios.</p>
                </div>
                <span class="muted" style="font-size:12px">
                    <?= count($clients) ?> rezultate
                </span>
            </div>

            <form class="search-tools" method="GET" action="klientet.php">
                <input class="search-input" type="search" name="kerko"
                       value="<?= h($search) ?>"
                       placeholder="Kërko emër, mbiemër, telefon ose email...">

                <select class="sort-select" name="rendit">
                    <option value="ri" <?= $sort === 'ri' ? 'selected' : '' ?>>
                        Më të rinjtë
                    </option>
                    <option value="emri" <?= $sort === 'emri' ? 'selected' : '' ?>>
                        Sipas emrit
                    </option>
                    <option value="vjetër" <?= $sort === 'vjetër' ? 'selected' : '' ?>>
                        Më të vjetrit
                    </option>
                </select>

                <button class="btn" type="submit">Kërko</button>

                <?php if ($search !== '' || $sort !== 'ri'): ?>
                    <a class="btn btn-light" href="klientet.php">Pastro</a>
                <?php endif; ?>
            </form>

            <?php if ($clients): ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                        <tr>
                            <th>Klienti</th>
                            <th>Kontakti</th>
                            <th>Adresa</th>
                            <th>Regjistruar më</th>
                            <th>Veprimet</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($clients as $client): ?>
                            <?php
                            $initials = mb_strtoupper(
                                mb_substr($client['emri'], 0, 1) .
                                mb_substr($client['mbiemri'], 0, 1)
                            );
                            ?>
                            <tr>
                                <td>
                                    <div class="client-cell">
                                        <div class="avatar"><?= h($initials) ?></div>
                                        <div>
                                            <div class="client-name">
                                                <?= h($client['emri'] . ' ' . $client['mbiemri']) ?>
                                            </div>
                                            <div class="client-id">
                                                CLIENT #<?= (int) $client['klient_id'] ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <?php if (!empty($client['telefoni'])): ?>
                                        <div><?= h($client['telefoni']) ?></div>
                                    <?php endif; ?>

                                    <?php if (!empty($client['email'])): ?>
                                        <div class="muted" style="margin-top:5px">
                                            <?= h($client['email']) ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (empty($client['telefoni']) && empty($client['email'])): ?>
                                        <span class="muted">Pa kontakt</span>
                                    <?php endif; ?>
                                </td>

                                <td><?= h($client['adresa'] ?: '—') ?></td>

                                <td>
                                    <?= !empty($client['data_regjistrimit'])
                                        ? h(date('d.m.Y', strtotime($client['data_regjistrimit'])))
                                        : '—' ?>
                                </td>

                                <td>
                                    <div class="actions">
                                        <a class="small-btn"
                                           href="klientet.php?edit=<?= (int) $client['klient_id'] ?>#formulari">
                                            Ndrysho
                                        </a>
                                        <a class="small-btn"
  
                                            href="profili-klientit.php?id=<?= (int) $client['klient_id'] ?>">
                                            Shiko profilin
                                        </a>

                                        <form method="POST" action="klientet.php"
                                              onsubmit="return confirm('A dëshiron vërtet ta fshish këtë klient?');">
                                            <input type="hidden" name="action" value="fshij">
                                            <input type="hidden" name="klient_id"
                                                   value="<?= (int) $client['klient_id'] ?>">
                                            <button class="small-btn delete-btn" type="submit">
                                                Fshij
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty">
                    <strong>Nuk u gjet asnjë klient.</strong>
                    <?= $search !== ''
                        ? 'Provo një term tjetër kërkimi.'
                        : 'Kliko “Shto klient” për të regjistruar klientin e parë.' ?>
                </div>
            <?php endif; ?>
        </section>

        <footer class="footer">
            <span>LUMIÈRE Beauty Studio</span>
            <span>Beauty is in every detail.</span>
        </footer>
    </main>
</div>
</body>
</html>
