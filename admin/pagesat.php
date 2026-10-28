
<?php
session_start();

require_once __DIR__ . '/../config/database.php';

$pageTitle = 'Pagesat';
$monedha = '€';

if (!isset($_SESSION['csrf_pagesat'])) {
    $_SESSION['csrf_pagesat'] = bin2hex(random_bytes(32));
}

function e($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function euro($value): string
{
    return number_format((float) $value, 2, ',', '.') . ' €';
}

function dataShqip($value): string
{
    if (!$value) {
        return '—';
    }

    try {
        return (new DateTime($value))->format('d.m.Y');
    } catch (Throwable $ex) {
        return (string) $value;
    }
}

$mesazhi = '';
$llojiMesazhit = 'success';

/*

| veprimet e formularve
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';

    if (
        !is_string($token) ||
        !hash_equals($_SESSION['csrf_pagesat'], $token)
    ) {
        $mesazhi = 'Sesioni i formularit ka skaduar. Rifresko faqen dhe provo përsëri.';
        $llojiMesazhit = 'error';
    } else {
        $veprimi = $_POST['veprimi'] ?? '';

        try {
            if ($veprimi === 'regjistro') {
                $terminId = filter_input(
                    INPUT_POST,
                    'termin_id',
                    FILTER_VALIDATE_INT
                );

                $shuma = filter_input(
                    INPUT_POST,
                    'shuma',
                    FILTER_VALIDATE_FLOAT
                );

                $menyra = trim((string) ($_POST['menyra_pageses'] ?? ''));
                $statusi = trim((string) ($_POST['statusi'] ?? ''));
                $shenime = trim((string) ($_POST['shenime'] ?? ''));

                $menyratLejuara = [
                    'Cash',
                    'Kartelë',
                    'Transfer bankar'
                ];

                $statusetLejuara = [
                    'Paguar',
                    'Ne pritje',
                    'Anuluar'
                ];

                if (!$terminId || $shuma === false || $shuma <= 0) {
                    throw new RuntimeException(
                        'Zgjidh një termin dhe shkruaj një shumë të vlefshme.'
                    );
                }

                if (!in_array($menyra, $menyratLejuara, true)) {
                    throw new RuntimeException(
                        'Mënyra e pagesës nuk është e vlefshme.'
                    );
                }

                if (!in_array($statusi, $statusetLejuara, true)) {
                    throw new RuntimeException(
                        'Statusi i pagesës nuk është i vlefshëm.'
                    );
                }

                if (mb_strlen($shenime) > 1000) {
                    throw new RuntimeException(
                        'Shënimet nuk mund të kenë më shumë se 1000 karaktere.'
                    );
                }

                $pdo->beginTransaction();

                $stmt = $pdo->prepare(
                    "SELECT t.termin_id, s.cmimi
                     FROM terminet t
                     INNER JOIN sherbimet s
                        ON s.sherbim_id = t.sherbim_id
                     WHERE t.termin_id = ?
                     LIMIT 1
                     FOR UPDATE"
                );
                $stmt->execute([$terminId]);
                $termini = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$termini) {
                    throw new RuntimeException(
                        'Termini i zgjedhur nuk u gjet.'
                    );
                }

                // Mos lejo dy regjistrime për të njëjtin termin.
                $kontrollo = $pdo->prepare(
                    "SELECT pagesa_id
                     FROM pagesa
                     WHERE termin_id = ?
                     LIMIT 1"
                );
                $kontrollo->execute([$terminId]);

                if ($kontrollo->fetchColumn()) {
                    throw new RuntimeException(
                        'Ky termin ka tashmë një regjistrim pagese. Përdore tabelën për ta ndryshuar statusin.'
                    );
                }

                $dataPageses = $statusi === 'Paguar'
                    ? date('Y-m-d H:i:s')
                    : null;

                $stmt = $pdo->prepare(
                    "INSERT INTO pagesa
                        (termin_id, shuma, menyra_pageses, statusi,
                         data_pageses, shenime)
                     VALUES (?, ?, ?, ?, ?, ?)"
                );

                $stmt->execute([
                    $terminId,
                    round((float) $shuma, 2),
                    $menyra,
                    $statusi,
                    $dataPageses,
                    $shenime !== '' ? $shenime : null
                ]);

                $pdo->commit();

                $_SESSION['csrf_pagesat'] = bin2hex(random_bytes(32));

                header('Location: pagesat.php?success=regjistruar');
                exit;
            }

            if ($veprimi === 'ndrysho_status') {
                $pagesaId = filter_input(
                    INPUT_POST,
                    'pagesa_id',
                    FILTER_VALIDATE_INT
                );

                $statusiRi = trim(
                    (string) ($_POST['statusi_ri'] ?? '')
                );

                $statusetLejuara = [
                    'Paguar',
                    'Ne pritje',
                    'Anuluar'
                ];

                if (!$pagesaId) {
                    throw new RuntimeException(
                        'Identifikuesi i pagesës nuk është i vlefshëm.'
                    );
                }

                if (!in_array($statusiRi, $statusetLejuara, true)) {
                    throw new RuntimeException(
                        'Statusi i zgjedhur nuk është i vlefshëm.'
                    );
                }

                $dataPageses = $statusiRi === 'Paguar'
                    ? date('Y-m-d H:i:s')
                    : null;

                $stmt = $pdo->prepare(
                    "UPDATE pagesa
                     SET statusi = ?, data_pageses = ?
                     WHERE pagesa_id = ?"
                );

                $stmt->execute([
                    $statusiRi,
                    $dataPageses,
                    $pagesaId
                ]);

                $_SESSION['csrf_pagesat'] = bin2hex(random_bytes(32));

                header('Location: pagesat.php?success=statusi');
                exit;
            }
        } catch (Throwable $ex) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $mesazhi = $ex instanceof RuntimeException
                ? $ex->getMessage()
                : 'Ndodhi një gabim gjatë ruajtjes. Kontrollo strukturën e databazës.';

            $llojiMesazhit = 'error';
        }
    }
}

if (isset($_GET['success'])) {
    if ($_GET['success'] === 'regjistruar') {
        $mesazhi = 'Pagesa u regjistrua me sukses.';
    } elseif ($_GET['success'] === 'statusi') {
        $mesazhi = 'Statusi i pagesës u përditësua me sukses.';
    }
}

/*
| filtrat
*/

$kerkimi = trim((string) ($_GET['q'] ?? ''));
$filtriStatusit = trim((string) ($_GET['statusi'] ?? ''));
$filtriMetodes = trim((string) ($_GET['metoda'] ?? ''));

$statusetLejuara = ['Paguar', 'Ne pritje', 'Anuluar'];
$metodatLejuara = ['Cash', 'Kartelë', 'Transfer bankar'];

if (!in_array($filtriStatusit, $statusetLejuara, true)) {
    $filtriStatusit = '';
}

if (!in_array($filtriMetodes, $metodatLejuara, true)) {
    $filtriMetodes = '';
}

/*
|statistikat financiare
*/

$stmt = $pdo->query(
    "SELECT
        COALESCE(SUM(CASE
            WHEN statusi = 'Paguar' THEN shuma ELSE 0
        END), 0) AS gjithsej_paguar,

        COALESCE(SUM(CASE
            WHEN statusi = 'Paguar'
             AND data_pageses >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
             AND data_pageses < DATE_FORMAT(
                 CURDATE() + INTERVAL 1 MONTH, '%Y-%m-01'
             )
            THEN shuma ELSE 0
        END), 0) AS paguar_muajin,

        COALESCE(SUM(CASE
            WHEN statusi = 'Ne pritje' THEN shuma ELSE 0
        END), 0) AS ne_pritje,

        COUNT(CASE
            WHEN statusi = 'Paguar' THEN 1
        END) AS numri_paguar

     FROM pagesa"
);

$statistikat = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

$gjithsejPaguar = (float) ($statistikat['gjithsej_paguar'] ?? 0);
$paguarMuajin = (float) ($statistikat['paguar_muajin'] ?? 0);
$nePritje = (float) ($statistikat['ne_pritje'] ?? 0);
$numriPaguar = (int) ($statistikat['numri_paguar'] ?? 0);

/*
| Grafiku per 6 muajt e fundit
*/

$muajt = [];
$muajtEmrat = [
    'Jan' => 'Jan',
    'Feb' => 'Shk',
    'Mar' => 'Mar',
    'Apr' => 'Pri',
    'May' => 'Maj',
    'Jun' => 'Qer',
    'Jul' => 'Kor',
    'Aug' => 'Gus',
    'Sep' => 'Sht',
    'Oct' => 'Tet',
    'Nov' => 'Nën',
    'Dec' => 'Dhj'
];

$fillimi = new DateTimeImmutable('first day of this month');

for ($i = 5; $i >= 0; $i--) {
    $data = $fillimi->modify("-{$i} months");
    $kyc = $data->format('Y-m');

    $muajt[$kyc] = [
        'emri' => $muajtEmrat[$data->format('M')],
        'shuma' => 0
    ];
}

$stmt = $pdo->query(
    "SELECT
        DATE_FORMAT(data_pageses, '%Y-%m') AS muaji,
        COALESCE(SUM(shuma), 0) AS shuma
     FROM pagesa
     WHERE statusi = 'Paguar'
       AND data_pageses >= DATE_FORMAT(
           DATE_SUB(CURDATE(), INTERVAL 5 MONTH), '%Y-%m-01'
       )
       AND data_pageses < DATE_FORMAT(
           CURDATE() + INTERVAL 1 MONTH, '%Y-%m-01'
       )
     GROUP BY DATE_FORMAT(data_pageses, '%Y-%m')"
);

foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $rreshti) {
    if (isset($muajt[$rreshti['muaji']])) {
        $muajt[$rreshti['muaji']]['shuma'] =
            (float) $rreshti['shuma'];
    }
}

$maxGrafikut = max(
    1,
    ...array_column($muajt, 'shuma')
);

/*
termine qe mund te regjistrohen si pagesa
*/

$stmt = $pdo->query(
    "SELECT
        t.termin_id,
        t.data,
        t.ora,
        k.emri AS klienti_emri,
        k.mbiemri AS klienti_mbiemri,
        s.emri AS sherbimi_emri,
        s.cmimi
     FROM terminet t
     INNER JOIN klientet k ON k.klient_id = t.klient_id
     INNER JOIN sherbimet s ON s.sherbim_id = t.sherbim_id
     WHERE NOT EXISTS (
        SELECT 1
        FROM pagesa p
        WHERE p.termin_id = t.termin_id
     )
       AND t.statusi <> 'Anuluar'
     ORDER BY t.data DESC, t.ora DESC
     LIMIT 300"
);

$terminetPaPagese = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
historia e pagesave
*/

$sql = "
    SELECT
        p.pagesa_id,
        p.termin_id,
        p.shuma,
        p.menyra_pageses,
        p.statusi,
        p.data_pageses,
        p.shenime,
        t.data AS data_terminit,
        t.ora,
        k.emri AS klienti_emri,
        k.mbiemri AS klienti_mbiemri,
        s.emri AS sherbimi_emri
    FROM pagesa p
    INNER JOIN terminet t ON t.termin_id = p.termin_id
    INNER JOIN klientet k ON k.klient_id = t.klient_id
    INNER JOIN sherbimet s ON s.sherbim_id = t.sherbim_id
    WHERE 1 = 1
";

$params = [];

if ($kerkimi !== '') {
    $sql .= " AND (
        k.emri LIKE ?
        OR k.mbiemri LIKE ?
        OR s.emri LIKE ?
        OR CAST(p.pagesa_id AS CHAR) LIKE ?
        OR CAST(p.termin_id AS CHAR) LIKE ?
    )";

    $like = '%' . $kerkimi . '%';
    array_push($params, $like, $like, $like, $like, $like);
}

if ($filtriStatusit !== '') {
    $sql .= " AND p.statusi = ?";
    $params[] = $filtriStatusit;
}

if ($filtriMetodes !== '') {
    $sql .= " AND p.menyra_pageses = ?";
    $params[] = $filtriMetodes;
}

$sql .= " ORDER BY p.pagesa_id DESC LIMIT 250";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$pagesat = $stmt->fetchAll(PDO::FETCH_ASSOC);

$csrfToken = $_SESSION['csrf_pagesat'];
?>
<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagesat | LUMIÈRE Beauty Studio</title>

    <style>
        :root {
            --bg: #f7f3ee;
            --surface: #fffdfa;
            --surface-soft: #f4eee6;
            --sidebar: #30271f;
            --sidebar-soft: #514235;
            --text: #30251d;
            --muted: #907e6f;
            --border: #e9dfd3;
            --accent: #9a7656;
            --accent-dark: #74543a;
            --success: #56755c;
            --success-bg: #e8f0e6;
            --warning: #9b713d;
            --warning-bg: #f4e9d8;
            --danger: #a35e55;
            --danger-bg: #f5e6e2;
            --shadow: 0 8px 28px rgba(66, 47, 32, .045);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: var(--text);
            background: var(--bg);
            font-family: "Segoe UI", Arial, sans-serif;
            font-size: 14px;
        }

        button, input, select, textarea {
            font: inherit;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .layout {
            min-height: 100vh;
        }

        .sidebar {
            position: fixed;
            inset: 0 auto 0 0;
            width: 243px;
            padding: 25px 17px;
            background: var(--sidebar);
            color: #fffaf3;
            overflow-y: auto;
            z-index: 20;
        }

        .brand {
            text-align: center;
            padding: 0 0 32px;
        }

        .brand-name {
            font-family: Georgia, serif;
            font-size: 27px;
            letter-spacing: 4px;
        }

        .brand-sub {
            margin-top: 6px;
            font-size: 9px;
            letter-spacing: 3px;
        }

        .nav-label {
            margin: 24px 12px 10px;
            color: #c5ab8e;
            font-size: 10px;
            letter-spacing: 2px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 13px 12px;
            margin-bottom: 4px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            transition: background .2s;
        }

        .nav-link:hover,
        .nav-link.active {
            background: var(--sidebar-soft);
        }

        .nav-icon {
            width: 16px;
            text-align: center;
            color: #e4c9a8;
        }

        .main {
            margin-left: 243px;
            padding: 31px 34px 24px;
            min-width: 0;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 26px;
        }

        .eyebrow {
            color: var(--accent);
            font-size: 10px;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        h1, h2, h3, p {
            margin-top: 0;
        }

        h1, h2, h3 {
            font-family: Georgia, "Times New Roman", serif;
            font-weight: 400;
        }

        h1 {
            margin-bottom: 7px;
            font-size: clamp(27px, 3vw, 37px);
            letter-spacing: -.6px;
        }

        .subtitle {
            color: var(--muted);
            margin-bottom: 0;
            font-size: 13px;
        }

        .today {
            white-space: nowrap;
            background: var(--surface);
            border: 1px solid var(--border);
            padding: 12px 15px;
            border-radius: 9px;
            color: var(--muted);
            font-size: 12px;
        }

        .section-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin: 28px 0 15px;
        }

        .section-heading h2 {
            margin-bottom: 0;
            font-size: 22px;
        }

        .muted {
            color: var(--muted);
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
        }

        .stat-card {
            min-width: 0;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 13px;
            padding: 20px 21px;
            box-shadow: var(--shadow);
        }

        .stat-top {
            display: flex;
            justify-content: space-between;
            gap: 8px;
            align-items: center;
        }

        .stat-label {
            color: var(--muted);
            font-size: 12px;
        }

        .stat-icon {
            display: grid;
            place-items: center;
            width: 34px;
            height: 34px;
            border-radius: 10px;
            background: var(--surface-soft);
            color: var(--accent-dark);
            font-size: 17px;
        }

        .stat-value {
            margin: 17px 0 7px;
            font-family: Georgia, serif;
            font-size: clamp(22px, 2vw, 29px);
            overflow-wrap: anywhere;
        }

        .stat-note {
            color: var(--muted);
            font-size: 11px;
        }

        .upper-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.35fr) minmax(300px, .9fr);
            gap: 18px;
            margin-top: 20px;
        }

        .panel {
            min-width: 0;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 13px;
            padding: 23px;
            box-shadow: var(--shadow);
        }

        .panel-heading {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 15px;
            margin-bottom: 20px;
        }

        .panel-heading h2 {
            margin-bottom: 5px;
            font-size: 21px;
        }

        .panel-heading p {
            margin-bottom: 0;
            color: var(--muted);
            font-size: 12px;
        }

        .chart-total {
            font-family: Georgia, serif;
            font-size: 18px;
            white-space: nowrap;
        }

        .chart {
            height: 190px;
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 12px;
            align-items: end;
            padding-top: 15px;
        }

        .chart-item {
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-end;
            gap: 9px;
            min-width: 0;
        }

        .chart-number {
            font-size: 10px;
            color: var(--muted);
            white-space: nowrap;
        }

        .bar-track {
            height: 125px;
            width: 100%;
            max-width: 40px;
            display: flex;
            align-items: flex-end;
            background: #f2ece4;
            border-radius: 7px 7px 3px 3px;
            overflow: hidden;
        }

        .bar {
            width: 100%;
            min-height: 3px;
            background: linear-gradient(180deg, #c9ad8c, #8d6b4e);
            border-radius: 7px 7px 2px 2px;
            transition: height .3s;
        }

        .chart-label {
            color: var(--muted);
            font-size: 11px;
        }

        .chart-caption {
            margin-top: 17px;
            color: var(--muted);
            font-size: 11px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: 7px;
            min-width: 0;
        }

        .field.full {
            grid-column: 1 / -1;
        }

        .field label {
            font-size: 12px;
            font-weight: 600;
            color: #685748;
        }

        .field input,
        .field select,
        .field textarea,
        .filters input,
        .filters select {
            width: 100%;
            border: 1px solid var(--border);
            background: #fffdfa;
            color: var(--text);
            border-radius: 8px;
            padding: 11px 12px;
            outline: none;
            min-width: 0;
        }

        .field textarea {
            min-height: 70px;
            resize: vertical;
        }

        .field input:focus,
        .field select:focus,
        .field textarea:focus,
        .filters input:focus,
        .filters select:focus {
            border-color: #b99b7a;
            box-shadow: 0 0 0 3px rgba(185, 155, 122, .13);
        }

        .btn {
            border: 1px solid transparent;
            border-radius: 8px;
            padding: 11px 15px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            font-weight: 600;
            transition: .2s;
        }

        .btn-primary {
            background: var(--sidebar);
            color: #fffaf3;
        }

        .btn-primary:hover {
            background: #514235;
        }

        .btn-light {
            background: var(--surface-soft);
            color: var(--text);
            border-color: var(--border);
        }

        .btn-light:hover {
            background: #eee3d6;
        }

        .form-actions {
            margin-top: 17px;
            display: flex;
            gap: 9px;
            flex-wrap: wrap;
        }

        .notice {
            padding: 13px 15px;
            margin-bottom: 18px;
            border-radius: 9px;
            font-size: 13px;
            border: 1px solid;
        }

        .notice.success {
            color: #42634a;
            background: #eaf2e8;
            border-color: #d2e2cf;
        }

        .notice.error {
            color: #8e4c43;
            background: #f8e9e5;
            border-color: #efd2cb;
        }

        .table-panel {
            margin-top: 20px;
        }

        .filters {
            display: grid;
            grid-template-columns: minmax(180px, 1fr) 180px 180px auto auto;
            gap: 9px;
            margin-bottom: 19px;
        }

        .table-wrap {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            white-space: nowrap;
        }

        th {
            color: var(--muted);
            font-size: 11px;
            font-weight: 500;
            padding: 13px 10px;
            border-bottom: 1px solid var(--border);
        }

        td {
            padding: 14px 10px;
            border-bottom: 1px solid #eee6dd;
            font-size: 12px;
            vertical-align: middle;
        }

        tbody tr:last-child td {
            border-bottom: 0;
        }

        tbody tr:hover {
            background: #fcf8f3;
        }

        .id {
            color: var(--accent-dark);
            font-weight: 700;
        }

        .client-name {
            font-weight: 600;
        }

        .subtext {
            display: block;
            color: var(--muted);
            font-size: 10px;
            margin-top: 5px;
        }

        .status {
            display: inline-block;
            padding: 6px 9px;
            border-radius: 30px;
            font-size: 10px;
            font-weight: 600;
        }

        .status.paguar {
            color: var(--success);
            background: var(--success-bg);
        }

        .status.ne-pritje {
            color: var(--warning);
            background: var(--warning-bg);
        }

        .status.anuluar {
            color: var(--danger);
            background: var(--danger-bg);
        }

        .inline-status {
            display: flex;
            gap: 6px;
            align-items: center;
        }

        .inline-status select {
            border: 1px solid var(--border);
            background: white;
            border-radius: 6px;
            padding: 7px;
            font-size: 11px;
        }

        .inline-status button {
            padding: 7px 9px;
            border: 0;
            border-radius: 6px;
            background: var(--surface-soft);
            color: var(--text);
            cursor: pointer;
        }

        .empty {
            text-align: center;
            padding: 35px 15px;
            color: var(--muted);
            white-space: normal;
        }

        .empty strong {
            display: block;
            margin-bottom: 7px;
            color: var(--text);
            font-family: Georgia, serif;
            font-size: 18px;
            font-weight: 400;
        }

        .footer {
            padding: 25px 0 8px;
            color: var(--muted);
            font-size: 10px;
            letter-spacing: .4px;
        }

        .mobile-menu {
            display: none;
        }

        @media (max-width: 1250px) {
            .stats {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .upper-grid {
                grid-template-columns: 1fr;
            }

            .filters {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 760px) {
            .sidebar {
                position: static;
                width: 100%;
                padding: 15px;
            }

            .brand {
                padding-bottom: 12px;
            }

            .brand-name {
                font-size: 22px;
            }

            .nav-label {
                margin-top: 14px;
            }

            .nav-link {
                display: inline-flex;
                padding: 10px;
                margin: 2px;
            }

            .main {
                margin-left: 0;
                padding: 22px 14px;
            }

            .topbar {
                align-items: flex-start;
            }

            .today {
                display: none;
            }

            .stats {
                gap: 10px;
            }

            .stat-card {
                padding: 15px;
            }

            .panel {
                padding: 16px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .field.full {
                grid-column: auto;
            }

            .filters {
                grid-template-columns: 1fr;
            }

            .chart {
                gap: 7px;
            }

            .chart-number {
                font-size: 9px;
            }

            .section-heading h2 {
                font-size: 20px;
            }
        }
    </style>
</head>

<body>
<div class="layout">

    <aside class="sidebar">
        <div class="brand">
            <div class="brand-name">LUMIÈRE</div>
            <div class="brand-sub">BEAUTY STUDIO</div>
        </div>

        <div class="nav-label">MENU KRYESORE</div>
        <a class="nav-link" href="index.php">
            <span class="nav-icon">✧</span> Përmbledhja
        </a>
        <a class="nav-link" href="terminet.php">
            <span class="nav-icon">▦</span> Terminet
        </a>
        <a class="nav-link" href="klientet.php">
            <span class="nav-icon">♙</span> Klientët
        </a>
        <a class="nav-link" href="sherbimet.php">
            <span class="nav-icon">✧</span> Shërbimet
        </a>
        <a class="nav-link" href="stafi.php">
            <span class="nav-icon">♙</span> Stafi
        </a>

        <div class="nav-label">FINANCAT &amp; MENAXHIMI</div>
        <a class="nav-link active" href="pagesat.php">
            <span class="nav-icon">€</span> Pagesat
        </a>
        <a class="nav-link" href="shpenzimet.php">
            <span class="nav-icon">↗</span> Shpenzimet
        </a>
        <a class="nav-link" href="inventari.php">
            <span class="nav-icon">▤</span> Inventari
        </a>
        <a class="nav-link" href="raportet.php">
            <span class="nav-icon">▥</span> Raportet
        </a>

        <div class="nav-label">SISTEMI</div>
        <a class="nav-link" href="cilësimet.php">
            <span class="nav-icon">⚙</span> Cilësimet
        </a>
        <a class="nav-link" href="logout.php">
            <span class="nav-icon">⇥</span> Kyçu
        </a>
    </aside>

    <main class="main">

        <header class="topbar">
            <div>
                <div class="eyebrow">LUMIÈRE / FINANCAT</div>
                <h1>Menaxhimi i pagesave.</h1>
                <p class="subtitle">
                    Kontrollo të ardhurat, transaksionet dhe pagesat e sallonit.
                </p>
            </div>

            <div class="today">
                <?= date('d.m.Y') ?>
            </div>
        </header>

        <?php if ($mesazhi !== ''): ?>
            <div class="notice <?= e($llojiMesazhit) ?>">
                <?= e($mesazhi) ?>
            </div>
        <?php endif; ?>

        <div class="section-heading">
            <h2>Përmbledhja financiare</h2>
            <span class="muted">Të dhëna nga databaza</span>
        </div>

        <section class="stats">
            <article class="stat-card">
                <div class="stat-top">
                    <span class="stat-label">Të ardhurat totale</span>
                    <span class="stat-icon">€</span>
                </div>
                <div class="stat-value"><?= euro($gjithsejPaguar) ?></div>
                <div class="stat-note">Nga të gjitha pagesat e kryera</div>
            </article>

            <article class="stat-card">
                <div class="stat-top">
                    <span class="stat-label">Të ardhurat këtë muaj</span>
                    <span class="stat-icon">↗</span>
                </div>
                <div class="stat-value"><?= euro($paguarMuajin) ?></div>
                <div class="stat-note">Pagesat e kryera gjatë muajit aktual</div>
            </article>

            <article class="stat-card">
                <div class="stat-top">
                    <span class="stat-label">Pagesat në pritje</span>
                    <span class="stat-icon">◷</span>
                </div>
                <div class="stat-value"><?= euro($nePritje) ?></div>
                <div class="stat-note">Shuma e pagesave të papërfunduara</div>
            </article>

            <article class="stat-card">
                <div class="stat-top">
                    <span class="stat-label">Pagesat e kryera</span>
                    <span class="stat-icon">✓</span>
                </div>
                <div class="stat-value"><?= $numriPaguar ?></div>
                <div class="stat-note">Numri i transaksioneve me status Paguar</div>
            </article>
        </section>

        <section class="upper-grid">

            <article class="panel">
                <div class="panel-heading">
                    <div>
                        <h2>Analitika e të ardhurave</h2>
                        <p>Pagesat e kryera gjatë gjashtë muajve të fundit</p>
                    </div>
                    <div class="chart-total"><?= euro($paguarMuajin) ?></div>
                </div>

                <div class="chart">
                    <?php foreach ($muajt as $muaji): ?>
                        <?php
                        $lartesia = ((float) $muaji['shuma'] / $maxGrafikut) * 100;
                        ?>
                        <div class="chart-item">
                            <span class="chart-number">
                                <?= number_format((float) $muaji['shuma'], 0, ',', '.') ?>
                            </span>

                            <div class="bar-track"
                                 title="<?= e($muaji['emri']) ?>: <?= e(euro($muaji['shuma'])) ?>">
                                <div class="bar"
                                     style="height: <?= max(3, $lartesia) ?>%">
                                </div>
                            </div>

                            <span class="chart-label"><?= e($muaji['emri']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="chart-caption">
                    Grafiku llogarit vetëm pagesat me statusin “Paguar”.
                </div>
            </article>

            <article class="panel">
                <div class="panel-heading">
                    <div>
                        <h2>Regjistro pagesë</h2>
                        <p>Plotëso të dhënat e transaksionit</p>
                    </div>
                </div>

                <?php if (count($terminetPaPagese) === 0): ?>
                    <div class="empty">
                        <strong>Nuk ka termine të disponueshme</strong>
                        Të gjitha terminet ekzistuese kanë një regjistrim pagese,
                        ose nuk ka termine aktive.
                    </div>
                <?php else: ?>
                    <form method="POST" action="pagesat.php">
                        <input type="hidden" name="csrf_token"
                               value="<?= e($csrfToken) ?>">
                        <input type="hidden" name="veprimi" value="regjistro">

                        <div class="form-grid">
                            <div class="field full">
                                <label for="termin_id">Termini / Klienti</label>
                                <select name="termin_id" id="termin_id" required>
                                    <option value="">Zgjidh termin...</option>

                                    <?php foreach ($terminetPaPagese as $termin): ?>
                                        <option
                                            value="<?= (int) $termin['termin_id'] ?>"
                                            data-cmimi="<?= e($termin['cmimi']) ?>"
                                        >
                                            #<?= (int) $termin['termin_id'] ?>
                                            · <?= e($termin['klienti_emri'] . ' ' . $termin['klienti_mbiemri']) ?>
                                            · <?= e($termin['sherbimi_emri']) ?>
                                            · <?= e(dataShqip($termin['data'])) ?>
                                            · <?= e(euro($termin['cmimi'])) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="field">
                                <label for="shuma">Shuma (€)</label>
                                <input type="number" name="shuma" id="shuma"
                                       min="0.01" step="0.01"
                                       placeholder="0.00" required>
                            </div>

                            <div class="field">
                                <label for="menyra_pageses">Mënyra e pagesës</label>
                                <select name="menyra_pageses"
                                        id="menyra_pageses" required>
                                    <option value="Cash">Cash</option>
                                    <option value="Kartelë">Kartelë</option>
                                    <option value="Transfer bankar">
                                        Transfer bankar
                                    </option>
                                </select>
                            </div>

                            <div class="field full">
                                <label for="statusi">Statusi</label>
                                <select name="statusi" id="statusi" required>
                                    <option value="Paguar">Paguar</option>
                                    <option value="Ne pritje">Në pritje</option>
                                    <option value="Anuluar">Anuluar</option>
                                </select>
                            </div>

                            <div class="field full">
                                <label for="shenime">Shënime (opsionale)</label>
                                <textarea name="shenime" id="shenime"
                                          maxlength="1000"
                                          placeholder="Shënime për transaksionin..."></textarea>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                + Regjistro pagesën
                            </button>
                            <button type="reset" class="btn btn-light">
                                Pastro formularin
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </article>

        </section>

        <section class="panel table-panel">
            <div class="panel-heading">
                <div>
                    <h2>Historia e transaksioneve</h2>
                    <p>
                        Kërko, filtro dhe menaxho pagesat e regjistruara.
                        Shfaqen deri në 250 rezultate.
                    </p>
                </div>
                <span class="muted"><?= count($pagesat) ?> rezultate</span>
            </div>

            <form method="GET" action="pagesat.php" class="filters">
                <input type="search" name="q"
                       placeholder="Kërko klientin, shërbimin ose ID..."
                       value="<?= e($kerkimi) ?>">

                <select name="statusi">
                    <option value="">Të gjitha statuset</option>
                    <?php foreach ($statusetLejuara as $status): ?>
                        <option value="<?= e($status) ?>"
                            <?= $filtriStatusit === $status ? 'selected' : '' ?>>
                            <?= e($status === 'Ne pritje' ? 'Në pritje' : $status) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="metoda">
                    <option value="">Të gjitha mënyrat</option>
                    <?php foreach ($metodatLejuara as $metoda): ?>
                        <option value="<?= e($metoda) ?>"
                            <?= $filtriMetodes === $metoda ? 'selected' : '' ?>>
                            <?= e($metoda) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="submit" class="btn btn-primary">
                    Filtro
                </button>

                <a href="pagesat.php" class="btn btn-light">
                    Pastro
                </a>
            </form>

            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>TRANSAKSIONI</th>
                        <th>KLIENTI</th>
                        <th>SHËRBIMI</th>
                        <th>SHUMA</th>
                        <th>MËNYRA</th>
                        <th>DATA E PAGESËS</th>
                        <th>STATUSI</th>
                        <th>VEPRIMI</th>
                    </tr>
                    </thead>

                    <tbody>
                    <?php if (count($pagesat) === 0): ?>
                        <tr>
                            <td colspan="8" class="empty">
                                <strong>Nuk u gjet asnjë transaksion</strong>
                                Provo të ndryshosh filtrat ose regjistro një pagesë të re.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($pagesat as $pagesa): ?>
                            <?php
                            $statusKlasa = match ($pagesa['statusi']) {
                                'Paguar' => 'paguar',
                                'Ne pritje' => 'ne-pritje',
                                'Anuluar' => 'anuluar',
                                default => 'ne-pritje'
                            };
                            ?>
                            <tr>
                                <td>
                                    <span class="id">
                                        #<?= (int) $pagesa['pagesa_id'] ?>
                                    </span>
                                    <span class="subtext">
                                        Termin #<?= (int) $pagesa['termin_id'] ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="client-name">
                                        <?= e($pagesa['klienti_emri'] . ' ' . $pagesa['klienti_mbiemri']) ?>
                                    </span>
                                    <span class="subtext">
                                        <?= e(dataShqip($pagesa['data_terminit'])) ?>
                                        · <?= e(substr((string) $pagesa['ora'], 0, 5)) ?>
                                    </span>
                                </td>

                                <td><?= e($pagesa['sherbimi_emri']) ?></td>

                                <td>
                                    <strong><?= e(euro($pagesa['shuma'])) ?></strong>
                                </td>

                                <td><?= e($pagesa['menyra_pageses']) ?></td>

                                <td>
                                    <?= e(dataShqip($pagesa['data_pageses'])) ?>
                                </td>

                                <td>
                                    <span class="status <?= e($statusKlasa) ?>">
                                        <?= e($pagesa['statusi'] === 'Ne pritje'
                                            ? 'Në pritje'
                                            : $pagesa['statusi']) ?>
                                    </span>
                                </td>

                                <td>
                                    <form method="POST" action="pagesat.php"
                                          class="inline-status">
                                        <input type="hidden" name="csrf_token"
                                               value="<?= e($csrfToken) ?>">
                                        <input type="hidden" name="veprimi"
                                               value="ndrysho_status">
                                        <input type="hidden" name="pagesa_id"
                                               value="<?= (int) $pagesa['pagesa_id'] ?>">

                                        <select name="statusi_ri"
                                                aria-label="Statusi i ri">
                                            <?php foreach ($statusetLejuara as $status): ?>
                                                <option value="<?= e($status) ?>"
                                                    <?= $pagesa['statusi'] === $status
                                                        ? 'selected'
                                                        : '' ?>>
                                                    <?= e($status === 'Ne pritje'
                                                        ? 'Në pritje'
                                                        : $status) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>

                                        <button type="submit"
                                                title="Ruaj statusin"
                                                aria-label="Ruaj statusin">
                                            ✓
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <footer class="footer">
            LUMIÈRE BEAUTY STUDIO · PANELI I MENAXHIMIT · FINANCAT
        </footer>

    </main>
</div>

<script>
    // Ploteso automatikisht qmimin sipas sherbimit
    const terminiSelect = document.getElementById('termin_id');
    const shumaInput = document.getElementById('shuma');

    if (terminiSelect && shumaInput) {
        terminiSelect.addEventListener('change', function () {
            const option = this.options[this.selectedIndex];

            if (option && option.dataset.cmimi) {
                shumaInput.value = Number(option.dataset.cmimi).toFixed(2);
            }
        });
    }
</script>
</body>
</html>

