<?php
session_start();

require_once __DIR__ . '/../config/database.php';

/* LUMIÈRE Beauty Studio - Cilësimet */

$dbConnection = null;

foreach (['pdo', 'conn', 'mysqli', 'db', 'connection'] as $variableName) {
    if (
        isset($$variableName) &&
        ($$variableName instanceof PDO || $$variableName instanceof mysqli)
    ) {
        $dbConnection = $$variableName;
        break;
    }
}

function esc($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$settings = [
    'emri_studios' => 'LUMIÈRE Beauty Studio',
    'telefoni' => '',
    'email' => '',
    'adresa' => '',
    'orari_hapjes' => '09:00:00',
    'orari_mbylljes' => '18:00:00',
    'kohezgjatja_terminit' => 60,
    'pragu_stokut' => 5,
    'shenime' => ''
];

$message = '';
$messageType = '';

/* Leximi i cilësimeve ekzistuese */

if ($dbConnection) {
    try {
        if ($dbConnection instanceof PDO) {
            $stmt = $dbConnection->query(
                'SELECT * FROM konfigurimet_studios WHERE id = 1'
            );
            $savedSettings = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $result = $dbConnection->query(
                'SELECT * FROM konfigurimet_studios WHERE id = 1'
            );
            $savedSettings = $result ? $result->fetch_assoc() : null;
        }

        if ($savedSettings) {
            $settings = array_merge($settings, $savedSettings);
        }
    } catch (Throwable $e) {
        $message = 'Nuk u lexuan dot cilësimet. Kontrollo tabelën konfigurimet_studios.';
        $messageType = 'error';
    }
} else {
    $message = 'Nuk u gjet lidhja me databazën. Kontrollo config/database.php.';
    $messageType = 'error';
}

/* Ruajtja e formularit */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $dbConnection) {
    $settings['emri_studios'] = trim($_POST['emri_studios'] ?? '');
    $settings['telefoni'] = trim($_POST['telefoni'] ?? '');
    $settings['email'] = trim($_POST['email'] ?? '');
    $settings['adresa'] = trim($_POST['adresa'] ?? '');
    $settings['orari_hapjes'] = trim($_POST['orari_hapjes'] ?? '09:00');
    $settings['orari_mbylljes'] = trim($_POST['orari_mbylljes'] ?? '18:00');
    $settings['kohezgjatja_terminit'] = (int) ($_POST['kohezgjatja_terminit'] ?? 60);
    $settings['pragu_stokut'] = (int) ($_POST['pragu_stokut'] ?? 5);
    $settings['shenime'] = trim($_POST['shenime'] ?? '');

    if ($settings['emri_studios'] === '') {
        $message = 'Emri i studios është i detyrueshëm.';
        $messageType = 'error';
    } elseif (
        $settings['email'] !== '' &&
        !filter_var($settings['email'], FILTER_VALIDATE_EMAIL)
    ) {
        $message = 'Shkruaj një adresë të vlefshme të email-it.';
        $messageType = 'error';
    } elseif (
        $settings['kohezgjatja_terminit'] < 5 ||
        $settings['kohezgjatja_terminit'] > 240 ||
        $settings['pragu_stokut'] < 0
    ) {
        $message = 'Kontrollo kohëzgjatjen e terminit dhe pragun e stokut.';
        $messageType = 'error';
    } elseif (
        $settings['orari_hapjes'] === '' ||
        $settings['orari_mbylljes'] === ''
    ) {
        $message = 'Plotëso orarin e hapjes dhe të mbylljes.';
        $messageType = 'error';
    } else {
        try {
            $sql = "
                INSERT INTO konfigurimet_studios (
                    id,
                    emri_studios,
                    telefoni,
                    email,
                    adresa,
                    orari_hapjes,
                    orari_mbylljes,
                    kohezgjatja_terminit,
                    pragu_stokut,
                    shenime
                ) VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    emri_studios = VALUES(emri_studios),
                    telefoni = VALUES(telefoni),
                    email = VALUES(email),
                    adresa = VALUES(adresa),
                    orari_hapjes = VALUES(orari_hapjes),
                    orari_mbylljes = VALUES(orari_mbylljes),
                    kohezgjatja_terminit = VALUES(kohezgjatja_terminit),
                    pragu_stokut = VALUES(pragu_stokut),
                    shenime = VALUES(shenime)
            ";

            $params = [
                $settings['emri_studios'],
                $settings['telefoni'],
                $settings['email'],
                $settings['adresa'],
                $settings['orari_hapjes'],
                $settings['orari_mbylljes'],
                $settings['kohezgjatja_terminit'],
                $settings['pragu_stokut'],
                $settings['shenime']
            ];

            if ($dbConnection instanceof PDO) {
                $stmt = $dbConnection->prepare($sql);
                $stmt->execute($params);
            } else {
                $stmt = $dbConnection->prepare($sql);

                $stmt->bind_param(
                    'ssssssiis',
                    $params[0],
                    $params[1],
                    $params[2],
                    $params[3],
                    $params[4],
                    $params[5],
                    $params[6],
                    $params[7],
                    $params[8]
                );

                $stmt->execute();
                $stmt->close();
            }

            $message = 'Cilësimet u ruajtën me sukses!';
            $messageType = 'success';

        } catch (Throwable $e) {
            $message = 'Ndodhi një gabim gjatë ruajtjes. Kontrollo tabelën dhe lidhjen me databazën.';
            $messageType = 'error';
        }
    }
}

$orariHapjes = substr((string) $settings['orari_hapjes'], 0, 5);
$orariMbylljes = substr((string) $settings['orari_mbylljes'], 0, 5);
?>

<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Cilësimet | LUMIÈRE Beauty Studio</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
    --cream: #f7f4ef;
    --white: #fffdf9;
    --brown: #302820;
    --brown-light: #514438;
    --beige: #eee5da;
    --gold: #947c64;
    --text: #302820;
    --muted: #8b8176;
    --border: #eae2d8;
}

        body {
            font-family: "Segoe UI", Arial, sans-serif;
            background: var(--cream);
            color: var(--text);
        }

        .layout {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 245px;
            flex-shrink: 0;
            background: var(--brown);
            color: white;
            padding: 30px 17px;
        }

        .brand {
            text-align: center;
            padding: 0 5px 28px;
            border-bottom: 1px solid rgba(255,255,255,.15);
            margin-bottom: 28px;
        }

        .brand h2 {
            font-family: Georgia, serif;
            letter-spacing: 2px;
            font-size: 23px;
            font-weight: normal;
            color: #f3dfbd;
        }

        .brand p {
            font-size: 10px;
            letter-spacing: 3px;
            margin-top: 7px;
            color: #dfcdb8;
        }

        .menu-title {
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: 1.5px;
            color: #cbb8a7;
            margin: 25px 10px 10px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 11px;
            text-decoration: none;
            color: #f3eae1;
            padding: 12px 13px;
            border-radius: 9px;
            margin-bottom: 5px;
            font-size: 13px;
            transition: .2s;
        }

        .nav-link:hover,
        .nav-link.active {
            background: #6a4c3e;
            color: #f4d7a5;
        }

        .nav-icon {
            width: 22px;
            text-align: center;
            font-size: 16px;
        }

        .main {
            flex: 1;
            min-width: 0;
            padding: 30px 36px 45px;
        }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 30px;
        }

        .eyebrow {
            color: var(--gold);
            letter-spacing: 2px;
            text-transform: uppercase;
            font-size: 10px;
            font-weight: 700;
            margin-bottom: 7px;
        }

        h1 {
            font-family: Georgia, serif;
            color: var(--brown);
            font-size: 32px;
            font-weight: normal;
        }

        .subtitle {
            color: var(--muted);
            margin-top: 8px;
            font-size: 13px;
            line-height: 1.6;
        }

        .top-badge {
            background: #efe4d5;
            border: 1px solid #e2d1ba;
            padding: 10px 15px;
            border-radius: 30px;
            color: var(--brown);
            font-size: 12px;
            white-space: nowrap;
        }

        .intro {
            background: var(--brown);
            color: #fff8ef;
            padding: 25px 28px;
            border-radius: 16px;
            margin-bottom: 24px;
            position: relative;
            overflow: hidden;
        }

        .intro::after {
            content: "✧";
            position: absolute;
            right: 25px;
            top: -20px;
            font-size: 115px;
            color: rgba(237,211,169,.13);
        }

        .intro h2 {
            color: #f0d7ad;
            font-family: Georgia, serif;
            font-weight: normal;
            font-size: 22px;
            margin-bottom: 8px;
        }

        .intro p {
            color: #e9dcd0;
            line-height: 1.7;
            max-width: 580px;
            font-size: 13px;
        }

        .message {
            padding: 14px 17px;
            border-radius: 10px;
            margin-bottom: 22px;
            font-size: 13px;
            line-height: 1.5;
        }

        .message.success {
            background: #e7f2e8;
            border: 1px solid #c8dfca;
            color: #276238;
        }

        .message.error {
            background: #f9e9e5;
            border: 1px solid #e9c9c0;
            color: #843b2c;
        }

        .settings-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.4fr) minmax(260px, .8fr);
            gap: 22px;
            align-items: start;
        }

        .panel {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 22px;
            box-shadow: 0 5px 20px rgba(75,52,43,.035);
        }

        .panel-heading {
            display: flex;
            align-items: center;
            gap: 13px;
            border-bottom: 1px solid var(--border);
            padding-bottom: 18px;
            margin-bottom: 22px;
        }

        .heading-icon {
            width: 43px;
            height: 43px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f1e6d8;
            color: var(--brown);
            border-radius: 12px;
            font-size: 20px;
        }

        .panel-heading h3 {
            color: var(--brown);
            font-family: Georgia, serif;
            font-size: 18px;
            font-weight: normal;
        }

        .panel-heading p {
            margin-top: 5px;
            font-size: 11px;
            color: var(--muted);
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group.full {
            grid-column: 1 / -1;
        }

        label {
            font-size: 12px;
            color: var(--brown);
            font-weight: 600;
        }

        input,
        textarea,
        select {
            width: 100%;
            font: inherit;
            font-size: 13px;
            padding: 12px 13px;
            border: 1px solid #e2d7ca;
            border-radius: 9px;
            background: #fffefa;
            color: var(--text);
            outline: none;
            transition: .2s;
        }

        input:focus,
        textarea:focus,
        select:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(187,150,96,.12);
        }

        textarea {
            resize: vertical;
            min-height: 100px;
            line-height: 1.6;
        }

        .hint {
            font-size: 11px;
            color: var(--muted);
            line-height: 1.5;
        }

        .actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid var(--border);
        }

        .save-button {
            border: none;
            background: var(--brown);
            color: white;
            padding: 13px 24px;
            border-radius: 9px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: .2s;
        }

        .save-button:hover {
            background: var(--brown-light);
            transform: translateY(-1px);
        }

        .info-card {
            background: #f1e7da;
            border: 1px solid #e5d6c4;
            border-radius: 13px;
            padding: 21px;
            margin-bottom: 16px;
        }

        .info-card h3 {
            font-family: Georgia, serif;
            font-weight: normal;
            color: var(--brown);
            font-size: 18px;
            margin-bottom: 10px;
        }

        .info-card p {
            font-size: 12px;
            line-height: 1.8;
            color: #756257;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 13px 0;
            border-bottom: 1px solid var(--border);
            font-size: 12px;
        }

        .summary-item:last-child {
            border-bottom: none;
        }

        .summary-item span {
            color: var(--muted);
        }

        .summary-item strong {
            color: var(--brown);
            text-align: right;
            overflow-wrap: anywhere;
        }

        .footer {
            text-align: center;
            color: #a09184;
            font-size: 11px;
            margin-top: 24px;
        }

        @media (max-width: 1000px) {
            .settings-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 700px) {
            .layout {
                display: block;
            }

            .sidebar {
                width: 100%;
            }

            .main {
                padding: 22px 15px;
            }

            .topbar {
                align-items: flex-start;
                flex-direction: column;
            }

            h1 {
                font-size: 27px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-group.full {
                grid-column: auto;
            }

            .panel {
                padding: 18px;
            }
        }
    </style>
</head>

<body>

<div class="layout">

    <aside class="sidebar">

        <div class="brand">
            <h2>LUMIÈRE</h2>
            <p>BEAUTY STUDIO</p>
        </div>

        <div class="menu-title">Menu kryesore</div>

        <a href="index.php" class="nav-link">
            <span class="nav-icon">⌂</span>
            Përmbledhja
        </a>

        <a href="terminet.php" class="nav-link">
            <span class="nav-icon">▦</span>
            Terminet
        </a>

        <a href="klientet.php" class="nav-link">
            <span class="nav-icon">♙</span>
            Klientët
        </a>

        <a href="sherbimet.php" class="nav-link">
            <span class="nav-icon">✧</span>
            Shërbimet
        </a>

        <a href="stafi.php" class="nav-link">
            <span class="nav-icon">♧</span>
            Stafi
        </a>

        <div class="menu-title">Financat & menaxhimi</div>

        <a href="pagesat.php" class="nav-link">
            <span class="nav-icon">€</span>
            Pagesat
        </a>

        <a href="shpenzimet.php" class="nav-link">
            <span class="nav-icon">↗</span>
            Shpenzimet
        </a>

        <a href="inventari.php" class="nav-link">
            <span class="nav-icon">▤</span>
            Inventari
        </a>

        <a href="raportet.php" class="nav-link">
            <span class="nav-icon">▥</span>
            Raportet
        </a>

        <div class="menu-title">Sistemi</div>

        <a href="cilesimet.php" class="nav-link active">
            <span class="nav-icon">⚙</span>
            Cilësimet
        </a>

        <a href="logout.php" class="nav-link">
            <span class="nav-icon">⇥</span>
            Dil
        </a>

    </aside>

    <main class="main">

        <header class="topbar">
            <div>
                <div class="eyebrow">LUMIÈRE · Administrimi</div>
                <h1>Cilësimet e studios</h1>
                <p class="subtitle">
                    Menaxho të dhënat, orarin dhe preferencat e studios
                    në një vend të vetëm.
                </p>
            </div>

            <div class="top-badge">⚙ Paneli i konfigurimit</div>
        </header>

        <section class="intro">
            <h2>Hapësira jote, rregullat e tua.</h2>
            <p>
                Përditëso informacionin e studios, cakto orarin e punës
                dhe përcakto preferencat për terminet dhe inventarin.
                Ndryshimet ruhen në databazë.
            </p>
        </section>

        <?php if ($message !== ''): ?>
            <div class="message <?= esc($messageType) ?>">
                <?= esc($message) ?>
            </div>
        <?php endif; ?>

        <div class="settings-grid">

            <div>

                <form method="POST" action="cilesimet.php">

                    <section class="panel">

                        <div class="panel-heading">
                            <div class="heading-icon">✧</div>
                            <div>
                                <h3>Informacionet e studios</h3>
                                <p>Të dhënat kryesore të biznesit</p>
                            </div>
                        </div>

                        <div class="form-grid">

                            <div class="form-group full">
                                <label for="emri_studios">Emri i studios *</label>
                                <input
                                    type="text"
                                    id="emri_studios"
                                    name="emri_studios"
                                    value="<?= esc($settings['emri_studios']) ?>"
                                    maxlength="150"
                                    required
                                >
                            </div>

                            <div class="form-group">
                                <label for="telefoni">Numri i telefonit</label>
                                <input
                                    type="text"
                                    id="telefoni"
                                    name="telefoni"
                                    value="<?= esc($settings['telefoni']) ?>"
                                    maxlength="30"
                                    placeholder="+383 ..."
                                >
                            </div>

                            <div class="form-group">
                                <label for="email">Email-i</label>
                                <input
                                    type="email"
                                    id="email"
                                    name="email"
                                    value="<?= esc($settings['email']) ?>"
                                    maxlength="150"
                                    placeholder="studio@example.com"
                                >
                            </div>

                            <div class="form-group full">
                                <label for="adresa">Adresa e studios</label>
                                <input
                                    type="text"
                                    id="adresa"
                                    name="adresa"
                                    value="<?= esc($settings['adresa']) ?>"
                                    maxlength="255"
                                    placeholder="Shkruaj adresën e studios"
                                >
                            </div>

                        </div>

                    </section>

                    <section class="panel">

                        <div class="panel-heading">
                            <div class="heading-icon">◷</div>
                            <div>
                                <h3>Orari dhe terminet</h3>
                                <p>Konfigurimi i punës së përditshme</p>
                            </div>
                        </div>

                        <div class="form-grid">

                            <div class="form-group">
                                <label for="orari_hapjes">Ora e hapjes</label>
                                <input
                                    type="time"
                                    id="orari_hapjes"
                                    name="orari_hapjes"
                                    value="<?= esc($orariHapjes) ?>"
                                    required
                                >
                            </div>

                            <div class="form-group">
                                <label for="orari_mbylljes">Ora e mbylljes</label>
                                <input
                                    type="time"
                                    id="orari_mbylljes"
                                    name="orari_mbylljes"
                                    value="<?= esc($orariMbylljes) ?>"
                                    required
                                >
                            </div>

                            <div class="form-group full">
                                <label for="kohezgjatja_terminit">
                                    Kohëzgjatja standarde e terminit
                                </label>

                                <select
                                    id="kohezgjatja_terminit"
                                    name="kohezgjatja_terminit"
                                >
                                    <?php foreach ([15, 30, 45, 60, 90, 120, 150, 180, 240] as $minutes): ?>
                                        <option
                                            value="<?= $minutes ?>"
                                            <?= (int) $settings['kohezgjatja_terminit'] === $minutes ? 'selected' : '' ?>
                                        >
                                            <?= $minutes ?> minuta
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <span class="hint">
                                    Kohëzgjatja standarde e konfiguruar për një termin.
                                </span>
                            </div>

                        </div>

                    </section>

                    <section class="panel">

                        <div class="panel-heading">
                            <div class="heading-icon">▤</div>
                            <div>
                                <h3>Preferencat e inventarit</h3>
                                <p>Kontrolli i stokut të produkteve</p>
                            </div>
                        </div>

                        <div class="form-grid">

                            <div class="form-group full">
                                <label for="pragu_stokut">Pragu minimal i stokut</label>

                                <input
                                    type="number"
                                    id="pragu_stokut"
                                    name="pragu_stokut"
                                    value="<?= esc($settings['pragu_stokut']) ?>"
                                    min="0"
                                    max="1000000"
                                    required
                                >

                                <span class="hint">
                                    Përcakton pragun minimal të stokut që dëshiron të përdorësh.
                                </span>
                            </div>

                            <div class="form-group full">
                                <label for="shenime">Shënime shtesë</label>

                                <textarea
                                    id="shenime"
                                    name="shenime"
                                    maxlength="5000"
                                    placeholder="Shkruaj këtu shënime për studion..."
                                ><?= esc($settings['shenime']) ?></textarea>
                            </div>

                        </div>

                        <div class="actions">
                            <button type="submit" class="save-button">
                                ✓ &nbsp; Ruaji ndryshimet
                            </button>
                        </div>

                    </section>

                </form>

            </div>

            <div>

                <section class="info-card">
                    <h3>Mirë se erdhe te Cilësimet</h3>
                    <p>
                        Kjo faqe shërben për ruajtjen e informacioneve
                        kryesore të LUMIÈRE Beauty Studio. Plotëso fushat
                        dhe shtyp butonin për t'i ruajtur ndryshimet.
                    </p>
                </section>

                <section class="panel">

                    <div class="panel-heading">
                        <div class="heading-icon">▣</div>
                        <div>
                            <h3>Përmbledhje</h3>
                            <p>Informacionet aktuale</p>
                        </div>
                    </div>

                    <div class="summary-item">
                        <span>Studioja</span>
                        <strong><?= esc($settings['emri_studios']) ?></strong>
                    </div>

                    <div class="summary-item">
                        <span>Telefoni</span>
                        <strong>
                            <?= $settings['telefoni'] !== ''
                                ? esc($settings['telefoni'])
                                : 'Nuk është vendosur' ?>
                        </strong>
                    </div>

                    <div class="summary-item">
                        <span>Email-i</span>
                        <strong>
                            <?= $settings['email'] !== ''
                                ? esc($settings['email'])
                                : 'Nuk është vendosur' ?>
                        </strong>
                    </div>

                    <div class="summary-item">
                        <span>Orari</span>
                        <strong>
                            <?= esc($orariHapjes) ?> – <?= esc($orariMbylljes) ?>
                        </strong>
                    </div>

                    <div class="summary-item">
                        <span>Kohëzgjatja</span>
                        <strong>
                            <?= (int) $settings['kohezgjatja_terminit'] ?> min
                        </strong>
                    </div>

                    <div class="summary-item">
                        <span>Pragu i stokut</span>
                        <strong>
                            <?= (int) $settings['pragu_stokut'] ?> njësi
                        </strong>
                    </div>

                </section>

                <section class="info-card">
                    <h3>Informacion i përditësuar</h3>
                    <p>
                        Kontrollo rregullisht numrin e telefonit,
                        email-in, orarin e punës dhe pragun e inventarit.
                        Konfigurimet ruhen në tabelën
                        <strong>konfigurimet_studios</strong>.
                    </p>
                </section>

            </div>

        </div>

        <footer class="footer">
            © <?= date('Y') ?> LUMIÈRE Beauty Studio · Paneli i administrimit
        </footer>

    </main>

</div>

</body>
</html>