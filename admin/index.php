
<?php
session_start();

require_once __DIR__ . '/../config/database.php';

$pageTitle = 'Dashboard';

// 1. Numri i termineve per sot
$stmt = $pdo->prepare(
    "SELECT COUNT(*)
     FROM terminet
     WHERE data = CURDATE()"
);
$stmt->execute();
$terminetSot = (int) $stmt->fetchColumn();

// 2. Numri i klienteve
$stmt = $pdo->query("SELECT COUNT(*) FROM klientet");
$numriKlienteve = (int) $stmt->fetchColumn();

// 3. Numri i sherbimeve
$stmt = $pdo->query("SELECT COUNT(*) FROM sherbimet");
$numriSherbimeve = (int) $stmt->fetchColumn();

// 4. Te ardhurat nga pagesat e kryera sot
$stmt = $pdo->prepare(
    "SELECT COALESCE(SUM(shuma), 0)
     FROM pagesa
     WHERE statusi = 'Paguar'
       AND data_pageses >= CURDATE()
       AND data_pageses < CURDATE() + INTERVAL 1 DAY"
);
$stmt->execute();
$teArdhuratSot = (float) $stmt->fetchColumn();

// 5. Terminet e ardhshme
$stmt = $pdo->prepare(
    "SELECT
        t.termin_id,
        t.data,
        t.ora,
        t.statusi,
        k.emri AS klienti_emri,
        k.mbiemri AS klienti_mbiemri,
        s.emri AS sherbimi_emri,
        st.emri AS staf_emri,
        st.mbiemri AS staf_mbiemri
     FROM terminet t
     INNER JOIN klientet k ON t.klient_id = k.klient_id
     INNER JOIN sherbimet s ON t.sherbim_id = s.sherbim_id
     INNER JOIN stafi st ON t.staf_id = st.staf_id
     WHERE t.data >= CURDATE()
     ORDER BY t.data ASC, t.ora ASC
     LIMIT 5"
);
$stmt->execute();
$terminetArdhshme = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Statistikat e dashboard-it
$stats = [
    [
        'label' => 'Terminet sot',
        'value' => $terminetSot,
        'note' => 'Terminet e regjistruara per sot'
    ],
    [
        'label' => 'Klientet',
        'value' => $numriKlienteve,
        'note' => 'Klientet e regjistruar'
    ],
    [
        'label' => 'Sherbimet',
        'value' => $numriSherbimeve,
        'note' => 'Sherbimet e regjistruara'
    ],
    [
        'label' => 'Te ardhurat sot',
        'value' => number_format($teArdhuratSot, 2, ',', '.') . ' €',
        'note' => 'Pagesat e kryera sot'
    ],
];

function h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($pageTitle) ?> | LUMIÈRE</title>

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #f7f4ef;
            color: #302820;
            font-family: Arial, sans-serif;
        }

        .dashboard {
            display: flex;
            min-height: 100vh;
        }

        /* SIDEBAR ORIGJINALE */
        .sidebar {
            width: 250px;
            flex: 0 0 250px;
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
            transition: background .2s ease;
        }

        .nav-link.active,
        .nav-link:hover {
            background: #514438;
            color: #fff;
        }

        .main-content {
            min-width: 0;
            flex: 1;
            padding: 35px;
        }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 35px;
        }

        .eyebrow {
            margin: 0 0 10px;
            color: #947c64;
            font-size: 10px;
            letter-spacing: 2px;
        }

        h1, h2, p {
            margin-top: 0;
        }

        h1 {
            margin-bottom: 8px;
            font-family: Georgia, serif;
            font-size: clamp(27px, 3vw, 36px);
            font-weight: normal;
        }

        .muted {
            color: #8b8176;
            font-size: 13px;
        }

        .date-label {
            padding: 12px 16px;
            border: 1px solid #e5ddd2;
            border-radius: 9px;
            background: #fffdf9;
            color: #67594b;
            font-size: 12px;
            white-space: nowrap;
        }

        .section-title {
            margin: 0 0 18px;
            font-family: Georgia, serif;
            font-size: 21px;
            font-weight: normal;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 17px;
            margin-bottom: 35px;
        }

        .stat-card {
            padding: 23px;
            border: 1px solid #eae2d8;
            border-radius: 13px;
            background: #fffdf9;
        }

        .stat-label {
            margin-bottom: 20px;
            color: #85786b;
            font-size: 12px;
        }

        .stat-value {
            margin-bottom: 8px;
            font-family: Georgia, serif;
            font-size: 30px;
        }

        .stat-note {
            color: #94887c;
            font-size: 11px;
        }

        .content-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.5fr) minmax(240px, 1fr);
            gap: 20px;
        }

        .panel {
            min-width: 0;
            padding: 25px;
            border: 1px solid #eae2d8;
            border-radius: 13px;
            background: #fffdf9;
        }

        .panel-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 20px;
        }

        .panel-heading h2 {
            margin: 0;
            font-family: Georgia, serif;
            font-size: 20px;
            font-weight: normal;
        }

        .panel-heading span {
            color: #94887c;
            font-size: 11px;
        }

        .empty-state {
            padding: 28px 10px;
            color: #8b8176;
            text-align: center;
            font-size: 13px;
            line-height: 1.8;
        }

        .table-wrap {
            overflow-x: auto;
            width: 100%;
        }

        .appointments-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        .appointments-table th {
            padding: 12px 8px;
            color: #947c64;
            text-align: left;
            font-weight: normal;
            white-space: nowrap;
        }

        .appointments-table td {
            padding: 14px 8px;
            border-top: 1px solid #eee5da;
            vertical-align: top;
        }

        .appointment-date {
            white-space: nowrap;
            font-weight: 600;
        }

        .appointment-time {
            display: inline-block;
            margin-top: 5px;
            color: #94887c;
        }

        .status {
            display: inline-block;
            padding: 6px 9px;
            border-radius: 20px;
            background: #f1e7d8;
            color: #72583d;
            white-space: nowrap;
            font-size: 11px;
        }

        .quick-link {
            display: block;
            margin-top: 10px;
            padding: 15px;
            border: 1px solid #eee5da;
            border-radius: 9px;
            color: #45392e;
            text-decoration: none;
            font-size: 13px;
        }

        .quick-link:hover {
            background: #f7f0e7;
        }

        .quick-link span {
            float: right;
            color: #947c64;
        }

        .footer-note {
            margin-top: 30px;
            color: #9b9084;
            font-size: 11px;
        }

        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .content-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 700px) {
            .dashboard {
                display: block;
            }

            .sidebar {
                width: 100%;
                padding: 20px;
            }

            .sidebar-label {
                margin-top: 24px;
            }

            .main-content {
                padding: 22px 16px;
            }

            .topbar {
                align-items: flex-start;
                flex-direction: column;
            }
        }

        @media (max-width: 420px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .panel {
                padding: 17px;
            }
        }
    </style>
</head>

<body>
<div class="dashboard">

    <aside class="sidebar">
        <div class="brand">
            LUMIÈRE
            <small>BEAUTY STUDIO</small>
        </div>

        <p class="sidebar-label">MENU KRYESORE</p>

        <a class="nav-link active" href="index.php">◈ &nbsp; Përmbledhja</a>
        <a class="nav-link" href="terminet.php">▦ &nbsp; Terminet</a>
        <a class="nav-link" href="klientet.php">♙ &nbsp; Klientët</a>
        <a class="nav-link" href="sherbimet.php">✧ &nbsp; Shërbimet</a>
        <a class="nav-link" href="stafi.php">♧ &nbsp; Stafi</a>

        <p class="sidebar-label">FINANCAT & MENAXHIMI</p>

        <a class="nav-link" href="pagesat.php">€ &nbsp; Pagesat</a>
        <a class="nav-link" href="shpenzimet.php">↗ &nbsp; Shpenzimet</a>
        <a class="nav-link" href="inventari.php">▤ &nbsp; Inventari</a>
        <a class="nav-link" href="raportet.php">▥ &nbsp; Raportet</a>

        <p class="sidebar-label">SISTEMI</p>

        <a class="nav-link" href="settings.php">⚙ &nbsp; Cilësimet</a>
        <a class="nav-link" href="login.php">⇥ &nbsp; Kyçu</a>
    </aside>

    <main class="main-content">

        <header class="topbar">
            <div>
                <p class="eyebrow">LUMIÈRE / ADMINISTRATION</p>
                <h1>Mirë se erdhe në dashboard.</h1>
                <p class="muted">Menaxho sallonin tënd nga një vend i vetëm.</p>
            </div>

            <div class="date-label">
                <?= date('d.m.Y') ?>
            </div>
        </header>

        <section>
            <h2 class="section-title">Pasqyra e sallonit</h2>

            <div class="stats-grid">
                <?php foreach ($stats as $stat): ?>
                    <article class="stat-card">
                        <p class="stat-label"><?= h($stat['label']) ?></p>
                        <p class="stat-value"><?= h($stat['value']) ?></p>
                        <p class="stat-note"><?= h($stat['note']) ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <div class="content-grid">

            <section class="panel">
                <div class="panel-heading">
                    <h2>Terminet e ardhshme</h2>
                    <span>5 rezervimet e radhës</span>
                </div>

                <?php if (empty($terminetArdhshme)): ?>
                    <div class="empty-state">
                        Nuk ka termine të ardhshme të regjistruara.
                    </div>
                <?php else: ?>
                    <div class="table-wrap">
                        <table class="appointments-table">
                            <thead>
                                <tr>
                                    <th>Data / Ora</th>
                                    <th>Klientja</th>
                                    <th>Shërbimi</th>
                                    <th>Punonjësja</th>
                                    <th>Statusi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($terminetArdhshme as $termin): ?>
                                    <tr>
                                        <td class="appointment-date">
                                            <?= h(date('d.m.Y', strtotime($termin['data']))) ?>
                                            <br>
                                            <span class="appointment-time">
                                                <?= h(substr((string) $termin['ora'], 0, 5)) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?= h($termin['klienti_emri'] . ' ' . $termin['klienti_mbiemri']) ?>
                                        </td>
                                        <td><?= h($termin['sherbimi_emri']) ?></td>
                                        <td>
                                            <?= h($termin['staf_emri'] . ' ' . $termin['staf_mbiemri']) ?>
                                        </td>
                                        <td>
                                            <span class="status"><?= h($termin['statusi']) ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>

            <section class="panel">
                <div class="panel-heading">
                    <h2>Veprime të shpejta</h2>
                    <span>Shkurtore</span>
                </div>

                <a class="quick-link" href="terminet.php">
                    Menaxho terminet <span>↗</span>
                </a>

                <a class="quick-link" href="klientet.php">
                    Shiko klientët <span>↗</span>
                </a>

                <a class="quick-link" href="sherbimet.php">
                    Menaxho shërbimet <span>↗</span>
                </a>

                <a class="quick-link" href="pagesat.php">
                    Kontrollo pagesat <span>↗</span>
                </a>
            </section>

        </div>

        <p class="footer-note">
            LUMIÈRE BEAUTY STUDIO · PANELI I MENAXHIMIT
        </p>

    </main>
</div>
</body>
</html>
