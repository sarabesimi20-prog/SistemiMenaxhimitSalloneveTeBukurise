
<?php
session_start();

require_once __DIR__ . '/../config/database.php';

/*
|--------------------------------------------------------------------------
| LUMIÈRE BEAUTY STUDIO - Menaxhimi i termineve
|--------------------------------------------------------------------------
| Përdor tabelat ekzistuese:
| terminet, klientet, sherbimet, stafi
|--------------------------------------------------------------------------
*/

$pageTitle = 'Menaxhimi i termineve';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['csrf_token'];
$mesazhi = '';
$llojiMesazhit = 'success';

$statuset = [
    'Ne pritje',
    'Konfirmuar',
    'Përfunduar',
    'Anuluar'
];

function h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirectToAppointments(string $message, string $type = 'success'): void
{
    $_SESSION['appointment_flash'] = [
        'message' => $message,
        'type' => $type
    ];

    header('Location: terminet.php');
    exit;
}

// Mesazhi pas ridrejtimit
if (!empty($_SESSION['appointment_flash'])) {
    $mesazhi = $_SESSION['appointment_flash']['message'];
    $llojiMesazhit = $_SESSION['appointment_flash']['type'];
    unset($_SESSION['appointment_flash']);
}

// Klientët, shërbimet dhe stafi për formularin
$klientet = $pdo->query(
    "SELECT klient_id, emri, mbiemri
     FROM klientet
     ORDER BY emri, mbiemri"
)->fetchAll();

$sherbimet = $pdo->query(
    "SELECT sherbim_id, emri, cmimi, kohezgjatja_min
     FROM sherbimet
     ORDER BY emri"
)->fetchAll();

$stafi = $pdo->query(
    "SELECT staf_id, emri, mbiemri, pozita
     FROM stafi
     ORDER BY emri, mbiemri"
)->fetchAll();

// Përpunimi i formularëve
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tokenPost = $_POST['csrf_token'] ?? '';

    if (!is_string($tokenPost) || !hash_equals($csrfToken, $tokenPost)) {
        redirectToAppointments(
            'Sesioni i formularit ka skaduar. Provo përsëri.',
            'error'
        );
    }

    $action = $_POST['action'] ?? '';

    try {
        // Regjistrimi i një termini të ri
        if ($action === 'create') {
            $klientId = filter_var(
                $_POST['klient_id'] ?? null,
                FILTER_VALIDATE_INT
            );

            $stafId = filter_var(
                $_POST['staf_id'] ?? null,
                FILTER_VALIDATE_INT
            );

            $sherbimId = filter_var(
                $_POST['sherbim_id'] ?? null,
                FILTER_VALIDATE_INT
            );

            $data = trim($_POST['data'] ?? '');
            $ora = trim($_POST['ora'] ?? '');

            $dateObject = DateTimeImmutable::createFromFormat('!Y-m-d', $data);
            $dateErrors = DateTimeImmutable::getLastErrors();

            $validDate = $dateObject !== false
                && ($dateErrors === false
                    || ($dateErrors['warning_count'] === 0
                        && $dateErrors['error_count'] === 0))
                && $dateObject->format('Y-m-d') === $data;

            if (
                !$klientId || !$stafId || !$sherbimId ||
                !$validDate || $data < date('Y-m-d') ||
                !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $ora)
            ) {
                redirectToAppointments(
                    'Plotëso të gjitha fushat me të dhëna të vlefshme.',
                    'error'
                );
            }

            // Kontrollo nëse klienti, punonjësja dhe shërbimi ekzistojnë
            $check = $pdo->prepare(
                "SELECT
                    (SELECT COUNT(*) FROM klientet WHERE klient_id = :kid) AS klient_ok,
                    (SELECT COUNT(*) FROM stafi WHERE staf_id = :sid) AS staf_ok,
                    (SELECT COUNT(*) FROM sherbimet WHERE sherbim_id = :shid) AS sherbim_ok"
            );

            $check->execute([
                'kid' => $klientId,
                'sid' => $stafId,
                'shid' => $sherbimId
            ]);

            $validRecords = $check->fetch();

            if (
                !$validRecords ||
                (int) $validRecords['klient_ok'] !== 1 ||
                (int) $validRecords['staf_ok'] !== 1 ||
                (int) $validRecords['sherbim_ok'] !== 1
            ) {
                redirectToAppointments(
                    'Klienti, punonjësja ose shërbimi nuk është valid.',
                    'error'
                );
            }

            // Kohëzgjatja e shërbimit
            $durationQuery = $pdo->prepare(
                "SELECT kohezgjatja_min
                 FROM sherbimet
                 WHERE sherbim_id = :id"
            );
            $durationQuery->execute(['id' => $sherbimId]);

            $duration = (int) $durationQuery->fetchColumn();

            if ($duration <= 0) {
                $duration = 30;
            }

            if ($duration > 720) {
                redirectToAppointments(
                    'Kohëzgjatja e shërbimit nuk është e vlefshme.',
                    'error'
                );
            }

            // Kontrollo përplasje të termineve të së njëjtës punonjëse
            $conflict = $pdo->prepare(
                "SELECT COUNT(*)
                 FROM terminet t
                 INNER JOIN sherbimet s
                    ON s.sherbim_id = t.sherbim_id
                 WHERE t.staf_id = :staf_id
                   AND t.data = :data
                   AND LOWER(TRIM(t.statusi)) NOT IN
                       ('anuluar', 'anuluar nga klienti', 'cancelled', 'canceled')
                   AND t.ora < :ora_fundit
                   AND ADDTIME(
                       t.ora,
                       SEC_TO_TIME(
                           GREATEST(COALESCE(s.kohezgjatja_min, 30), 1) * 60
                       )
                   ) > :ora_fillimit"
            );

            $oraFundit = (new DateTimeImmutable($data . ' ' . $ora))
                ->modify('+' . $duration . ' minutes')
                ->format('H:i:s');

            // Ora e përfundimit duhet të jetë më vonë se ora e fillimit.
            // Përfshihen edhe terminet që kalojnë mesnatën.
            $oraFillimitDate = new DateTimeImmutable($data . ' ' . $ora);
            $oraFunditDate = $oraFillimitDate->modify('+' . $duration . ' minutes');

            if ($oraFunditDate->format('Y-m-d') !== $data) {
                redirectToAppointments(
                    'Termini duhet të përfundojë brenda së njëjtës ditë.',
                    'error'
                );
            }

            $conflict->execute([
                'staf_id' => $stafId,
                'data' => $data,
                'ora_fundit' => $oraFundit,
                'ora_fillimit' => $oraFillimitDate->format('H:i:s')
            ]);

            if ((int) $conflict->fetchColumn() > 0) {
                redirectToAppointments(
                    'Kjo punonjëse ka një termin tjetër që përplaset me orarin e zgjedhur.',
                    'error'
                );
            }

            // Ruaje terminin
            $insert = $pdo->prepare(
                "INSERT INTO terminet
                    (klient_id, staf_id, sherbim_id, data, ora, statusi)
                 VALUES
                    (:klient_id, :staf_id, :sherbim_id, :data, :ora, 'Ne pritje')"
            );

            $insert->execute([
                'klient_id' => $klientId,
                'staf_id' => $stafId,
                'sherbim_id' => $sherbimId,
                'data' => $data,
                'ora' => $ora . ':00'
            ]);

            redirectToAppointments(
                'Termini u regjistrua me sukses.'
            );
        }

        // Ndryshimi i statusit
        if ($action === 'update_status') {
            $terminId = filter_var(
                $_POST['termin_id'] ?? null,
                FILTER_VALIDATE_INT
            );

            $statusiRi = $_POST['statusi'] ?? '';

            if (!$terminId || !in_array($statusiRi, $statuset, true)) {
                redirectToAppointments(
                    'Të dhënat për ndryshimin e statusit nuk janë të vlefshme.',
                    'error'
                );
            }

            $update = $pdo->prepare(
                "UPDATE terminet
                 SET statusi = :statusi
                 WHERE termin_id = :id"
            );

            $update->execute([
                'statusi' => $statusiRi,
                'id' => $terminId
            ]);

            redirectToAppointments(
                'Statusi i terminit u përditësua.'
            );
        }

        // Anulimi i terminit
        if ($action === 'cancel') {
            $terminId = filter_var(
                $_POST['termin_id'] ?? null,
                FILTER_VALIDATE_INT
            );

            if (!$terminId) {
                redirectToAppointments(
                    'ID e terminit nuk është e vlefshme.',
                    'error'
                );
            }

            $cancel = $pdo->prepare(
                "UPDATE terminet
                 SET statusi = 'Anuluar'
                 WHERE termin_id = :id
                   AND LOWER(TRIM(statusi)) NOT IN
                       ('anuluar', 'anuluar nga klienti', 'cancelled', 'canceled')"
            );

            $cancel->execute(['id' => $terminId]);

            if ($cancel->rowCount() > 0) {
                redirectToAppointments('Termini u anulua me sukses.');
            }

            redirectToAppointments(
                'Termini nuk u gjet ose është anuluar më parë.',
                'error'
            );
        }

        redirectToAppointments(
            'Veprimi i kërkuar nuk njihet.',
            'error'
        );

    } catch (PDOException $e) {
        error_log('LUMIERE appointment error: ' . $e->getMessage());

        redirectToAppointments(
            'Ndodhi një gabim gjatë ruajtjes. Kontrollo të dhënat dhe provo përsëri.',
            'error'
        );
    }
}

// Filtrat e listës
$kerkimi = trim($_GET['q'] ?? '');
$filtriStatusit = trim($_GET['statusi'] ?? '');
$filtriData = trim($_GET['data'] ?? '');

$sql = "
    SELECT
        t.termin_id,
        t.klient_id,
        t.staf_id,
        t.sherbim_id,
        t.data,
        t.ora,
        t.statusi,
        k.emri AS klienti_emri,
        k.mbiemri AS klienti_mbiemri,
        k.telefoni AS klienti_telefoni,
        s.emri AS sherbimi_emri,
        s.cmimi AS sherbimi_cmimi,
        s.kohezgjatja_min,
        st.emri AS staf_emri,
        st.mbiemri AS staf_mbiemri
    FROM terminet t
    INNER JOIN klientet k ON t.klient_id = k.klient_id
    INNER JOIN sherbimet s ON t.sherbim_id = s.sherbim_id
    INNER JOIN stafi st ON t.staf_id = st.staf_id
    WHERE 1 = 1
";

$params = [];

if ($kerkimi !== '') {
    $sql .= "
        AND (
            CONCAT(k.emri, ' ', k.mbiemri) LIKE :q1
            OR CONCAT(st.emri, ' ', st.mbiemri) LIKE :q2
            OR s.emri LIKE :q3
            OR k.telefoni LIKE :q4
        )
    ";

    $termKerkimi = '%' . $kerkimi . '%';

    $params['q1'] = $termKerkimi;
    $params['q2'] = $termKerkimi;
    $params['q3'] = $termKerkimi;
    $params['q4'] = $termKerkimi;
}

if ($filtriStatusit !== '' && in_array($filtriStatusit, $statuset, true)) {
    $sql .= " AND t.statusi = :statusi";
    $params['statusi'] = $filtriStatusit;
}

if ($filtriData !== '') {
    $dateFilter = DateTimeImmutable::createFromFormat('!Y-m-d', $filtriData);
    $dateErrors = DateTimeImmutable::getLastErrors();

    $validFilterDate = $dateFilter !== false
        && ($dateErrors === false
            || ($dateErrors['warning_count'] === 0
                && $dateErrors['error_count'] === 0))
        && $dateFilter->format('Y-m-d') === $filtriData;

    if ($validFilterDate) {
        $sql .= " AND t.data = :data";
        $params['data'] = $filtriData;
    }
}

$sql .= " ORDER BY t.data DESC, t.ora DESC LIMIT 200";

$listQuery = $pdo->prepare($sql);
$listQuery->execute($params);
$terminet = $listQuery->fetchAll();

// Statistika për faqen
$totalTermineve = (int) $pdo->query(
    "SELECT COUNT(*) FROM terminet"
)->fetchColumn();

$totalNePritje = (int) $pdo->query(
    "SELECT COUNT(*) FROM terminet
     WHERE LOWER(TRIM(statusi)) = 'ne pritje'"
)->fetchColumn();

$totalKonfirmuar = (int) $pdo->query(
    "SELECT COUNT(*) FROM terminet
     WHERE LOWER(TRIM(statusi)) = 'konfirmuar'"
)->fetchColumn();

$totalAnuluar = (int) $pdo->query(
    "SELECT COUNT(*) FROM terminet
     WHERE LOWER(TRIM(statusi)) = 'anuluar'"
)->fetchColumn();

?>
<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= h($pageTitle) ?> | LUMIÈRE</title>

    <style>
        :root {
            --espresso: #302820;
            --espresso-light: #514438;
            --cream: #f7f4ef;
            --white: #fffdf9;
            --border: #eae2d8;
            --muted: #8b8176;
            --accent: #947c64;
            --green-bg: #e6f2e7;
            --green: #32633d;
            --red-bg: #f8e7e2;
            --red: #a33e32;
            --gold-bg: #f1e7d8;
            --gold: #72583d;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: var(--cream);
            color: var(--espresso);
            font-family: Arial, sans-serif;
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
            padding: 30px 18px;
            background: var(--espresso);
            color: #f8f3eb;
        }

        .brand {
            margin-bottom: 38px;
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

        .nav-label {
            margin: 30px 12px 12px;
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

        .nav-link:hover,
        .nav-link.active {
            background: var(--espresso-light);
            color: white;
        }

        .main {
            min-width: 0;
            flex: 1;
            padding: 34px;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 28px;
        }

        .eyebrow {
            margin-bottom: 10px;
            color: var(--accent);
            font-size: 10px;
            letter-spacing: 2px;
        }

        h1, h2, h3, p {
            margin-top: 0;
        }

        h1 {
            margin-bottom: 8px;
            font-family: Georgia, serif;
            font-size: clamp(28px, 3vw, 38px);
            font-weight: normal;
        }

        .subtitle {
            color: var(--muted);
            font-size: 13px;
            line-height: 1.7;
        }

        .date-chip {
            padding: 13px 17px;
            border: 1px solid var(--border);
            border-radius: 10px;
            background: var(--white);
            white-space: nowrap;
            font-size: 12px;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 25px;
        }

        .stat-card,
        .panel {
            border: 1px solid var(--border);
            border-radius: 13px;
            background: var(--white);
        }

        .stat-card {
            padding: 21px;
        }

        .stat-label {
            margin-bottom: 17px;
            color: var(--muted);
            font-size: 12px;
        }

        .stat-number {
            margin-bottom: 8px;
            font-family: Georgia, serif;
            font-size: 30px;
        }

        .stat-note {
            margin-bottom: 0;
            color: var(--muted);
            font-size: 11px;
        }

        .panel {
            margin-bottom: 24px;
            padding: 25px;
        }

        .panel-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 22px;
        }

        .panel-heading h2 {
            margin-bottom: 0;
            font-family: Georgia, serif;
            font-size: 22px;
            font-weight: normal;
        }

        .panel-heading p {
            margin: 7px 0 0;
            color: var(--muted);
            font-size: 12px;
        }

        .message {
            margin-bottom: 22px;
            padding: 14px 16px;
            border: 1px solid transparent;
            border-radius: 9px;
            font-size: 13px;
            line-height: 1.6;
        }

        .message.success {
            border-color: #cce4ce;
            background: var(--green-bg);
            color: var(--green);
        }

        .message.error {
            border-color: #efc9c2;
            background: var(--red-bg);
            color: var(--red);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
        }

        .field {
            min-width: 0;
        }

        .field label {
            display: block;
            margin-bottom: 8px;
            color: #65584b;
            font-size: 12px;
            font-weight: 600;
        }

        .field input,
        .field select {
            width: 100%;
            min-height: 45px;
            padding: 11px 12px;
            border: 1px solid #e3d9cc;
            border-radius: 8px;
            outline: none;
            background: #fff;
            color: var(--espresso);
            font: inherit;
            font-size: 13px;
        }

        .field input:focus,
        .field select:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(148, 124, 100, .12);
        }

        .field-hint {
            display: block;
            margin-top: 7px;
            color: var(--muted);
            font-size: 11px;
            line-height: 1.5;
        }

        .form-actions {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 22px;
        }

        .btn {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            min-height: 42px;
            padding: 11px 17px;
            border: 1px solid transparent;
            border-radius: 8px;
            cursor: pointer;
            font: inherit;
            font-size: 12px;
            text-decoration: none;
            transition: background .15s ease;
        }

        .btn-primary {
            background: var(--espresso);
            color: white;
        }

        .btn-primary:hover {
            background: var(--espresso-light);
        }

        .btn-secondary {
            border-color: var(--border);
            background: white;
            color: var(--espresso);
        }

        .btn-secondary:hover {
            background: var(--cream);
        }

        .btn-danger {
            border-color: #efc9c2;
            background: var(--red-bg);
            color: var(--red);
        }

        .toolbar {
            display: grid;
            grid-template-columns: minmax(180px, 1.5fr) minmax(150px, .8fr) minmax(150px, .8fr) auto auto;
            gap: 10px;
            align-items: end;
            margin-bottom: 22px;
        }

        .toolbar .field label {
            font-size: 11px;
        }

        .toolbar .btn {
            white-space: nowrap;
        }

        .table-wrap {
            width: 100%;
            overflow-x: auto;
        }

        .appointments-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            text-align: left;
        }

        .appointments-table th {
            padding: 14px 11px;
            border-bottom: 1px solid var(--border);
            color: var(--accent);
            font-size: 11px;
            font-weight: 600;
            white-space: nowrap;
        }

        .appointments-table td {
            padding: 15px 11px;
            border-bottom: 1px solid #f0e9e0;
            vertical-align: middle;
        }

        .appointments-table tbody tr:hover {
            background: #fbf7f1;
        }

        .client-name {
            font-weight: 600;
        }

        .secondary-text {
            display: block;
            margin-top: 5px;
            color: var(--muted);
            font-size: 11px;
        }

        .status-badge {
            display: inline-block;
            padding: 7px 10px;
            border-radius: 20px;
            background: var(--gold-bg);
            color: var(--gold);
            font-size: 10px;
            white-space: nowrap;
        }

        .status-konfirmuar {
            background: #e4eee8;
            color: #3f6951;
        }

        .status-perfunduar {
            background: #e8e7f4;
            color: #55528c;
        }

        .status-anuluar {
            background: var(--red-bg);
            color: var(--red);
        }

        .row-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .row-actions form {
            margin: 0;
        }

        .row-actions select {
            max-width: 135px;
            min-height: 34px;
            padding: 6px;
            border: 1px solid var(--border);
            border-radius: 6px;
            background: white;
            font-size: 11px;
        }

        .small-btn {
            padding: 8px 10px;
            min-height: 32px;
            font-size: 11px;
        }

        .empty-state {
            padding: 38px 15px;
            color: var(--muted);
            text-align: center;
            font-size: 13px;
            line-height: 1.8;
        }

        .empty-state strong {
            display: block;
            margin-bottom: 8px;
            color: var(--espresso);
            font-family: Georgia, serif;
            font-size: 20px;
            font-weight: normal;
        }

        .results-info {
            margin-bottom: 14px;
            color: var(--muted);
            font-size: 12px;
        }

        .footer {
            margin-top: 28px;
            color: #9b9084;
            font-size: 11px;
        }

        @media (max-width: 1250px) {
            .stats {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .toolbar {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 800px) {
            .layout {
                display: block;
            }

            .sidebar {
                width: 100%;
                padding: 20px;
            }

            .nav-label {
                margin-top: 22px;
            }

            .main {
                padding: 22px 16px;
            }

            .form-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .topbar {
                align-items: flex-start;
                flex-direction: column;
            }
        }

        @media (max-width: 500px) {
            .stats,
            .form-grid,
            .toolbar {
                grid-template-columns: 1fr;
            }

            .panel {
                padding: 17px;
            }

            .form-actions {
                align-items: stretch;
                flex-direction: column;
            }

            .form-actions .btn {
                width: 100%;
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

        <p class="nav-label">MENU KRYESORE</p>

        <a class="nav-link" href="index.php">◈ &nbsp; Përmbledhja</a>
        <a class="nav-link active" href="terminet.php">▦ &nbsp; Terminet</a>
        <a class="nav-link" href="klientet.php">♙ &nbsp; Klientët</a>
        <a class="nav-link" href="sherbimet.php">✧ &nbsp; Shërbimet</a>
        <a class="nav-link" href="stafi.php">♧ &nbsp; Stafi</a>

        <p class="nav-label">FINANCAT & MENAXHIMI</p>

        <a class="nav-link" href="pagesat.php">€ &nbsp; Pagesat</a>
        <a class="nav-link" href="shpenzimet.php">↗ &nbsp; Shpenzimet</a>
        <a class="nav-link" href="inventari.php">▤ &nbsp; Inventari</a>
        <a class="nav-link" href="raportet.php">▥ &nbsp; Raportet</a>

        <p class="nav-label">SISTEMI</p>

        <a class="nav-link" href="settings.php">⚙ &nbsp; Cilësimet</a>
        <a class="nav-link" href="login.php">⇥ &nbsp; Kyçu</a>
    </aside>

    <main class="main">

        <header class="topbar">
            <div>
                <p class="eyebrow">LUMIÈRE / MENAXHIMI</p>
                <h1>Menaxhimi i termineve</h1>
                <p class="subtitle">
                    Organizo rezervimet, cakto punonjëset dhe mbaj çdo termin nën kontroll.
                </p>
            </div>

            <div class="date-chip">
                <?= date('d.m.Y') ?>
            </div>
        </header>

        <?php if ($mesazhi !== ''): ?>
            <div class="message <?= h($llojiMesazhit) ?>" role="status">
                <?= h($mesazhi) ?>
            </div>
        <?php endif; ?>

        <section class="stats">
            <article class="stat-card">
                <p class="stat-label">Gjithsej termine</p>
                <p class="stat-number"><?= $totalTermineve ?></p>
                <p class="stat-note">Të gjitha rezervimet e regjistruara</p>
            </article>

            <article class="stat-card">
                <p class="stat-label">Në pritje</p>
                <p class="stat-number"><?= $totalNePritje ?></p>
                <p class="stat-note">Presin konfirmimin</p>
            </article>

            <article class="stat-card">
                <p class="stat-label">Të konfirmuara</p>
                <p class="stat-number"><?= $totalKonfirmuar ?></p>
                <p class="stat-note">Rezervime të konfirmuara</p>
            </article>

            <article class="stat-card">
                <p class="stat-label">Të anuluara</p>
                <p class="stat-number"><?= $totalAnuluar ?></p>
                <p class="stat-note">Rezervime të anuluara</p>
            </article>
        </section>

        <section class="panel" id="rezervo">
            <div class="panel-heading">
                <div>
                    <h2>Regjistro termin të ri</h2>
                    <p>Plotëso të dhënat për të krijuar një rezervim të ri.</p>
                </div>
            </div>

            <?php if (empty($klientet) || empty($sherbimet) || empty($stafi)): ?>

                <div class="empty-state">
                    <strong>Të dhënat bazë mungojnë</strong>
                    Për të regjistruar termin, duhet të kesh të paktën një klient,
                    një shërbim dhe një punonjëse në databazë.
                </div>

            <?php else: ?>

                <form method="POST" action="terminet.php" id="appointmentForm">
                    <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                    <input type="hidden" name="action" value="create">

                    <div class="form-grid">

                        <div class="field">
                            <label for="klient_id">Klientja / Klienti *</label>
                            <select name="klient_id" id="klient_id" required>
                                <option value="">Zgjidh klientin</option>
                                <?php foreach ($klientet as $klient): ?>
                                    <option value="<?= (int) $klient['klient_id'] ?>">
                                        <?= h($klient['emri'] . ' ' . $klient['mbiemri']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="field">
                            <label for="sherbim_id">Shërbimi *</label>
                            <select name="sherbim_id" id="sherbim_id" required>
                                <option value="">Zgjidh shërbimin</option>
                                <?php foreach ($sherbimet as $sherbim): ?>
                                    <option
                                        value="<?= (int) $sherbim['sherbim_id'] ?>"
                                        data-price="<?= h($sherbim['cmimi']) ?>"
                                        data-duration="<?= h($sherbim['kohezgjatja_min'] ?? 30) ?>"
                                    >
                                        <?= h($sherbim['emri']) ?>
                                        — <?= number_format((float) $sherbim['cmimi'], 2, ',', '.') ?> €
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="field-hint" id="serviceInfo">
                                Çmimi dhe kohëzgjatja e shërbimit.
                            </small>
                        </div>

                        <div class="field">
                            <label for="staf_id">Punonjësja / Punonjësi *</label>
                            <select name="staf_id" id="staf_id" required>
                                <option value="">Zgjidh punonjësen</option>
                                <?php foreach ($stafi as $punonjes): ?>
                                    <option value="<?= (int) $punonjes['staf_id'] ?>">
                                        <?= h($punonjes['emri'] . ' ' . $punonjes['mbiemri']) ?>
                                        <?php if (!empty($punonjes['pozita'])): ?>
                                            — <?= h($punonjes['pozita']) ?>
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="field">
                            <label for="data">Data e terminit *</label>
                            <input
                                type="date"
                                name="data"
                                id="data"
                                min="<?= date('Y-m-d') ?>"
                                value="<?= date('Y-m-d') ?>"
                                required
                            >
                        </div>

                        <div class="field">
                            <label for="ora">Ora e fillimit *</label>
                            <input
                                type="time"
                                name="ora"
                                id="ora"
                                step="300"
                                required
                            >
                            <small class="field-hint">
                                Zgjidh orën e fillimit të rezervimit.
                            </small>
                        </div>

                        <div class="field">
                            <label>Statusi fillestar</label>
                            <input type="text" value="Në pritje" disabled>
                            <small class="field-hint">
                                Terminet e reja krijohen në pritje të konfirmimit.
                            </small>
                        </div>

                    </div>

                    <div class="form-actions">
                        <button class="btn btn-primary" type="submit">
                            + Regjistro termin
                        </button>

                        <button class="btn btn-secondary" type="reset">
                            Pastro formularin
                        </button>
                    </div>
                </form>

            <?php endif; ?>
        </section>

        <section class="panel" id="lista">
            <div class="panel-heading">
                <div>
                    <h2>Lista e rezervimeve</h2>
                    <p>Kërko, filtro dhe menaxho terminet ekzistuese.</p>
                </div>
            </div>

            <form method="GET" action="terminet.php" class="toolbar">

                <div class="field">
                    <label for="q">Kërko</label>
                    <input
                        type="search"
                        name="q"
                        id="q"
                        placeholder="Klienti, telefoni, shërbimi..."
                        value="<?= h($kerkimi) ?>"
                    >
                </div>

                <div class="field">
                    <label for="filter_status">Statusi</label>
                    <select name="statusi" id="filter_status">
                        <option value="">Të gjitha statuset</option>
                        <?php foreach ($statuset as $status): ?>
                            <option
                                value="<?= h($status) ?>"
                                <?= $filtriStatusit === $status ? 'selected' : '' ?>
                            >
                                <?= h($status) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label for="filter_date">Data</label>
                    <input
                        type="date"
                        name="data"
                        id="filter_date"
                        value="<?= h($filtriData) ?>"
                    >
                </div>

                <button class="btn btn-primary" type="submit">
                    Kërko
                </button>

                <a class="btn btn-secondary" href="terminet.php">
                    Pastro filtrat
                </a>

            </form>

            <p class="results-info">
                U gjetën <?= count($terminet) ?> rezervime
                (maksimumi 200 rezultate).
            </p>

            <?php if (empty($terminet)): ?>

                <div class="empty-state">
                    <strong>Nuk u gjet asnjë termin</strong>
                    Ndrysho filtrat ose regjistro një termin të ri.
                </div>

            <?php else: ?>

                <div class="table-wrap">
                    <table class="appointments-table">
                        <thead>
                            <tr>
                                <th>Data / Ora</th>
                                <th>Klienti</th>
                                <th>Shërbimi</th>
                                <th>Punonjësja</th>
                                <th>Çmimi</th>
                                <th>Statusi</th>
                                <th>Veprimet</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($terminet as $item): ?>
                                <?php
                                $statusClass = 'status-badge';

                                if ($item['statusi'] === 'Konfirmuar') {
                                    $statusClass .= ' status-konfirmuar';
                                } elseif ($item['statusi'] === 'Përfunduar') {
                                    $statusClass .= ' status-perfunduar';
                                } elseif ($item['statusi'] === 'Anuluar') {
                                    $statusClass .= ' status-anuluar';
                                }
                                ?>

                                <tr>
                                    <td>
                                        <strong>
                                            <?= h(date('d.m.Y', strtotime($item['data']))) ?>
                                        </strong>
                                        <span class="secondary-text">
                                            <?= h(substr((string) $item['ora'], 0, 5)) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <span class="client-name">
                                            <?= h($item['klienti_emri'] . ' ' . $item['klienti_mbiemri']) ?>
                                        </span>

                                        <?php if (!empty($item['klienti_telefoni'])): ?>
                                            <span class="secondary-text">
                                                <?= h($item['klienti_telefoni']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?= h($item['sherbimi_emri']) ?>
                                        <span class="secondary-text">
                                            <?= (int) ($item['kohezgjatja_min'] ?? 30) ?> minuta
                                        </span>
                                    </td>

                                    <td>
                                        <?= h($item['staf_emri'] . ' ' . $item['staf_mbiemri']) ?>
                                    </td>

                                    <td>
                                        <?= number_format((float) $item['sherbimi_cmimi'], 2, ',', '.') ?> €
                                    </td>

                                    <td>
                                        <span class="<?= h($statusClass) ?>">
                                            <?= h($item['statusi']) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <div class="row-actions">

                                            <form method="POST" action="terminet.php">
                                                <input
                                                    type="hidden"
                                                    name="csrf_token"
                                                    value="<?= h($csrfToken) ?>"
                                                >
                                                <input
                                                    type="hidden"
                                                    name="action"
                                                    value="update_status"
                                                >
                                                <input
                                                    type="hidden"
                                                    name="termin_id"
                                                    value="<?= (int) $item['termin_id'] ?>"
                                                >

                                                <select
                                                    name="statusi"
                                                    aria-label="Ndrysho statusin"
                                                    onchange="if(confirm('A dëshiron ta ndryshosh statusin e këtij termini?')) { this.form.submit(); } else { this.value = this.dataset.previous; }"
                                                    onfocus="this.dataset.previous=this.value"
                                                >
                                                    <?php foreach ($statuset as $status): ?>
                                                        <option
                                                            value="<?= h($status) ?>"
                                                            <?= $item['statusi'] === $status ? 'selected' : '' ?>
                                                        >
                                                            <?= h($status) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </form>

                                            <?php if ($item['statusi'] !== 'Anuluar' && $item['statusi'] !== 'Përfunduar'): ?>
                                                <form
                                                    method="POST"
                                                    action="terminet.php"
                                                    onsubmit="return confirm('A je e sigurt që dëshiron ta anulosh këtë termin?');"
                                                >
                                                    <input
                                                        type="hidden"
                                                        name="csrf_token"
                                                        value="<?= h($csrfToken) ?>"
                                                    >
                                                    <input
                                                        type="hidden"
                                                        name="action"
                                                        value="cancel"
                                                    >
                                                    <input
                                                        type="hidden"
                                                        name="termin_id"
                                                        value="<?= (int) $item['termin_id'] ?>"
                                                    >

                                                    <button
                                                        class="btn btn-danger small-btn"
                                                        type="submit"
                                                    >
                                                        Anulo
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php endif; ?>
        </section>

        <p class="footer">
            LUMIÈRE BEAUTY STUDIO · MENAXHIMI I REZERVIMEVE
        </p>

    </main>
</div>

<script>
    // Shfaq çmimin dhe kohëzgjatjen e shërbimit të zgjedhur.
    const serviceSelect = document.getElementById('sherbim_id');
    const serviceInfo = document.getElementById('serviceInfo');

    function updateServiceInfo() {
        if (!serviceSelect || !serviceInfo) {
            return;
        }

        const option = serviceSelect.options[serviceSelect.selectedIndex];

        if (!option || !option.value) {
            serviceInfo.textContent = 'Çmimi dhe kohëzgjatja e shërbimit.';
            return;
        }

        const price = option.dataset.price;
        const duration = option.dataset.duration;

        serviceInfo.textContent =
            'Çmimi: ' + Number(price).toFixed(2) + ' € · Kohëzgjatja: ' +
            (Number(duration) > 0 ? duration : 30) + ' minuta';
    }

    if (serviceSelect) {
        serviceSelect.addEventListener('change', updateServiceInfo);
    }

    // Konfirmim para pastrimit të formularit.
    const appointmentForm = document.getElementById('appointmentForm');

    if (appointmentForm) {
        appointmentForm.addEventListener('reset', function () {
            setTimeout(updateServiceInfo, 0);
        });
    }
</script>

</body>
</html>
