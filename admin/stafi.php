<?php
require_once __DIR__ . '/../config/database.php';

function h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$message = '';
$error = '';
$staff = [];
$showForm = isset($_GET['shto']);

$form = [
    'staf_id' => '',
    'emri' => '',
    'mbiemri' => '',
    'telefoni' => '',
    'email' => '',
    'foto' => '',
    'pozita' => '',
    'data_punesimit' => '',
    'statusi' => 'Aktive'
];

$messageMap = [
    'shtuar' => 'Punonjësi u regjistrua me sukses.',
    'ndryshuar' => 'Të dhënat u përditësuan me sukses.',
    'statusi' => 'Statusi u ndryshua me sukses.'
];

$message = $messageMap[$_GET['mesazh'] ?? ''] ?? '';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        /* AKTIVIZO / ÇAKTIVIZO PUNONJËSIN */
        if ($action === 'ndrysho_status') {
            $id = filter_var(
                $_POST['staf_id'] ?? '',
                FILTER_VALIDATE_INT
            );

            if (!$id || $id < 1) {
                throw new RuntimeException(
                    'ID-ja e punonjësit nuk është valide.'
                );
            }

            $stmt = $pdo->prepare(
                "UPDATE stafi
                 SET statusi = IF(
                     statusi = 'Aktive',
                     'Joaktive',
                     'Aktive'
                 )
                 WHERE staf_id = :id"
            );

            $stmt->execute(['id' => $id]);

            header('Location: stafi.php?mesazh=statusi');
            exit;
        }

        /* REGJISTRIMI DHE NDRYSHIMI I PUNONJËSIT */
        if ($action === 'shto' || $action === 'ndrysho') {
            $showForm = true;

            $isEdit = $action === 'ndrysho';

            $form = [
                'staf_id' => trim($_POST['staf_id'] ?? ''),
                'emri' => trim($_POST['emri'] ?? ''),
                'mbiemri' => trim($_POST['mbiemri'] ?? ''),
                'telefoni' => trim($_POST['telefoni'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'foto' => '',
                'pozita' => trim($_POST['pozita'] ?? ''),
                'data_punesimit' => trim(
                    $_POST['data_punesimit'] ?? ''
                ),
                'statusi' => $_POST['statusi'] ?? 'Aktive'
            ];

            $id = null;
            $fotoAktuale = '';

            if ($isEdit) {
                $id = filter_var(
                    $form['staf_id'],
                    FILTER_VALIDATE_INT
                );

                if (!$id || $id < 1) {
                    throw new RuntimeException(
                        'ID-ja e punonjësit nuk është valide.'
                    );
                }

                $stmt = $pdo->prepare(
                    'SELECT foto FROM stafi WHERE staf_id = :id'
                );

                $stmt->execute(['id' => $id]);

                $existing = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$existing) {
                    throw new RuntimeException(
                        'Punonjësi nuk u gjet.'
                    );
                }

                $fotoAktuale = (string)($existing['foto'] ?? '');
                $form['foto'] = $fotoAktuale;
            }

            if (
                $form['emri'] === '' ||
                $form['mbiemri'] === ''
            ) {
                throw new RuntimeException(
                    'Emri dhe mbiemri janë të detyrueshëm.'
                );
            }

            if (
                mb_strlen($form['emri']) > 100 ||
                mb_strlen($form['mbiemri']) > 100
            ) {
                throw new RuntimeException(
                    'Emri ose mbiemri është shumë i gjatë.'
                );
            }

            if (
                mb_strlen($form['telefoni']) > 20 ||
                mb_strlen($form['email']) > 150 ||
                mb_strlen($form['pozita']) > 100
            ) {
                throw new RuntimeException(
                    'Njëra nga fushat i tejkalon kufijtë e lejuar.'
                );
            }

            if (
                $form['email'] !== '' &&
                !filter_var(
                    $form['email'],
                    FILTER_VALIDATE_EMAIL
                )
            ) {
                throw new RuntimeException(
                    'Adresa e emailit nuk është valide.'
                );
            }

            if (!in_array(
                $form['statusi'],
                ['Aktive', 'Joaktive'],
                true
            )) {
                $form['statusi'] = 'Aktive';
            }

            if ($form['data_punesimit'] !== '') {
                $date = DateTime::createFromFormat(
                    '!Y-m-d',
                    $form['data_punesimit']
                );

                if (
                    !$date ||
                    $date->format('Y-m-d') !==
                    $form['data_punesimit']
                ) {
                    throw new RuntimeException(
                        'Data e punësimit nuk është valide.'
                    );
                }
            }

            /* NGARKIMI I FOTOGRAFISË */
            $fotoPath = $fotoAktuale;

            if (
                isset($_FILES['foto']) &&
                $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE
            ) {
                if ($_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
                    throw new RuntimeException(
                        'Fotografia nuk u ngarkua.'
                    );
                }

                if (
                    (int)$_FILES['foto']['size'] >
                    4 * 1024 * 1024
                ) {
                    throw new RuntimeException(
                        'Fotografia nuk duhet të jetë mbi 4 MB.'
                    );
                }

                $imageInfo = @getimagesize(
                    $_FILES['foto']['tmp_name']
                );

                $mime = $imageInfo['mime'] ?? '';

                $allowed = [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/webp' => 'webp',
                    'image/gif' => 'gif'
                ];

                if (!$imageInfo || !isset($allowed[$mime])) {
                    throw new RuntimeException(
                        'Lejohen vetëm JPG, PNG, WEBP ose GIF.'
                    );
                }

                $uploadDir = __DIR__ . '/uploads/stafi';

                if (
                    !is_dir($uploadDir) &&
                    !mkdir($uploadDir, 0755, true) &&
                    !is_dir($uploadDir)
                ) {
                    throw new RuntimeException(
                        'Folderi për fotografitë nuk u krijua.'
                    );
                }

                $fileName = 'staf_' .
                    bin2hex(random_bytes(8)) .
                    '.' . $allowed[$mime];

                if (!move_uploaded_file(
                    $_FILES['foto']['tmp_name'],
                    $uploadDir . '/' . $fileName
                )) {
                    throw new RuntimeException(
                        'Fotografia nuk mund të ruhet.'
                    );
                }

                $fotoPath = 'uploads/stafi/' . $fileName;
                $form['foto'] = $fotoPath;
            }

            $data = [
                'emri' => $form['emri'],
                'mbiemri' => $form['mbiemri'],
                'telefoni' => $form['telefoni'] !== ''
                    ? $form['telefoni'] : null,
                'email' => $form['email'] !== ''
                    ? $form['email'] : null,
                'foto' => $fotoPath !== ''
                    ? $fotoPath : null,
                'pozita' => $form['pozita'] !== ''
                    ? $form['pozita'] : null,
                'data_punesimit' =>
                    $form['data_punesimit'] !== ''
                    ? $form['data_punesimit'] : null,
                'statusi' => $form['statusi']
            ];

            if ($action === 'shto') {
                $stmt = $pdo->prepare(
                    'INSERT INTO stafi
                    (
                        emri,
                        mbiemri,
                        telefoni,
                        email,
                        foto,
                        pozita,
                        data_punesimit,
                        statusi
                    )
                    VALUES
                    (
                        :emri,
                        :mbiemri,
                        :telefoni,
                        :email,
                        :foto,
                        :pozita,
                        :data_punesimit,
                        :statusi
                    )'
                );

                $stmt->execute($data);

                header('Location: stafi.php?mesazh=shtuar');
                exit;
            }

            $data['id'] = $id;

            $stmt = $pdo->prepare(
                'UPDATE stafi SET
                    emri = :emri,
                    mbiemri = :mbiemri,
                    telefoni = :telefoni,
                    email = :email,
                    foto = :foto,
                    pozita = :pozita,
                    data_punesimit = :data_punesimit,
                    statusi = :statusi
                 WHERE staf_id = :id'
            );

            $stmt->execute($data);

            header('Location: stafi.php?mesazh=ndryshuar');
            exit;
        }
    }

    /* MARRJA E TË DHËNAVE PËR NDRYSHIM */
    if (isset($_GET['edit'])) {
        $id = filter_var(
            $_GET['edit'],
            FILTER_VALIDATE_INT
        );

        if ($id && $id > 0) {
            $stmt = $pdo->prepare(
                'SELECT * FROM stafi WHERE staf_id = :id'
            );

            $stmt->execute(['id' => $id]);

            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                $form = array_merge($form, $existing);
                $showForm = true;
            }
        }
    }
} catch (RuntimeException $e) {
    $error = $e->getMessage();
} catch (PDOException $e) {
    $error = 'Gabim në databazë. Kontrollo tabelën stafi.';
} catch (Throwable $e) {
    $error = 'Ndodhi një gabim. Kontrollo të dhënat dhe provo përsëri.';
}

/* KËRKIMI DHE FILTRIMI */
$search = trim($_GET['kerko'] ?? '');
$statusFilter = $_GET['status'] ?? 'te_gjithe';
$sort = $_GET['rendit'] ?? 'ri';

$stats = [
    'total' => 0,
    'aktive' => 0,
    'joaktive' => 0,
    'te_ri' => 0
];

try {
    $stmt = $pdo->query(
        "SELECT
            COUNT(*) AS total,
            SUM(statusi = 'Aktive') AS aktive,
            SUM(statusi = 'Joaktive') AS joaktive,
            SUM(
                data_punesimit >=
                DATE_FORMAT(CURDATE(), '%Y-%m-01')
                AND data_punesimit <
                DATE_FORMAT(
                    CURDATE() + INTERVAL 1 MONTH,
                    '%Y-%m-01'
                )
            ) AS te_ri
         FROM stafi"
    );

    $stats = $stmt->fetch(PDO::FETCH_ASSOC) ?: $stats;

    $orderOptions = [
        'ri' => 'staf_id DESC',
        'emri' => 'emri ASC, mbiemri ASC',
        'pozita' => 'pozita ASC, emri ASC',
        'punesimi' => 'data_punesimit DESC, staf_id DESC'
    ];

    $orderBy = $orderOptions[$sort] ?? $orderOptions['ri'];

    $sql = 'SELECT
                staf_id, emri, mbiemri, telefoni,
                email, foto, pozita, data_punesimit, statusi
            FROM stafi';

    $where = [];
    $params = [];

    if ($search !== '') {
        $where[] = '(
            emri LIKE :q1
            OR mbiemri LIKE :q2
            OR telefoni LIKE :q3
            OR email LIKE :q4
            OR pozita LIKE :q5
        )';

        $q = '%' . $search . '%';

        $params = [
            'q1' => $q,
            'q2' => $q,
            'q3' => $q,
            'q4' => $q,
            'q5' => $q
        ];
    }

    if ($statusFilter === 'aktive') {
        $where[] = "statusi = 'Aktive'";
    } elseif ($statusFilter === 'joaktive') {
        $where[] = "statusi = 'Joaktive'";
    }

    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $sql .= ' ORDER BY ' . $orderBy;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $staff = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
    if ($error === '') {
        $error = 'Nuk mund të lexohen të dhënat nga tabela stafi.';
    }

    $staff = [];
}
?>
<!DOCTYPE html>
<html lang="sq">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Stafi | LUMIÈRE Beauty Studio</title>

<style>
:root {
    --cream: #f7f4ef;
    --paper: #fffdf9;
    --brown: #302820;
    --brown-soft: #8b8176;
    --gold: #947c64;
    --line: #eae2d8;
    --pale: #eee5da;
    --green: #dfe9db;
    --red: #f8e9e4;
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

/* SIDEBAR */
.sidebar {
    width: 250px;
    flex-shrink: 0;
    padding: 30px 18px;
    background: var(--brown);
    color: #f8f3eb;
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

.nav-link.active {
    border-left: 3px solid var(--gold);
    padding-left: 11px;
}

/* FAQJA */
.main {
    min-width: 0;
    flex: 1;
    padding: 27px clamp(18px, 3vw, 44px);
}

.topline {
    display: flex;
    justify-content: space-between;
    gap: 14px;
    padding-bottom: 17px;
    border-bottom: 1px solid var(--line);
    color: var(--brown-soft);
    font-size: 11px;
    letter-spacing: 1.5px;
}

.hero {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 20px;
    padding: 32px 0 24px;
}

.eyebrow {
    margin-bottom: 10px;
    color: var(--gold);
    font-size: 10px;
    letter-spacing: 3px;
    text-transform: uppercase;
}

h1 {
    margin: 0;
    font: 400 clamp(34px, 3.6vw, 48px)/1.1 Georgia, serif;
}

.description {
    max-width: 580px;
    margin: 11px 0 0;
    color: var(--brown-soft);
    font-size: 14px;
    line-height: 1.7;
}

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 42px;
    padding: 0 16px;
    border: 1px solid var(--brown);
    border-radius: 5px;
    background: var(--brown);
    color: white;
    text-decoration: none;
    font-size: 12px;
    cursor: pointer;
    white-space: nowrap;
}

.btn:hover {
    background: #514438;
}

.btn-light {
    background: var(--paper);
    border-color: var(--line);
    color: var(--brown);
}

.btn-light:hover {
    background: var(--pale);
}

/* STATISTIKAT */
.stats {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 13px;
    margin-bottom: 23px;
}

.stat-card {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
    padding: 17px;
    background: var(--paper);
    border: 1px solid var(--line);
    border-radius: 10px;
    box-shadow: 0 5px 15px #30282008;
}

.stat-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 45px;
    height: 45px;
    flex-shrink: 0;
    border-radius: 9px;
    background: var(--pale);
    color: var(--gold);
    font-size: 21px;
}

.stat-label {
    color: var(--brown-soft);
    font-size: 10px;
    line-height: 1.4;
    text-transform: uppercase;
}

.stat-value {
    margin-top: 4px;
    font: 400 28px Georgia, serif;
}

.notice {
    padding: 13px 16px;
    margin-bottom: 16px;
    border: 1px solid;
    border-radius: 6px;
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

/* FORMULARI */
.panel {
    margin-bottom: 22px;
    padding: 22px;
    background: var(--paper);
    border: 1px solid var(--line);
    border-radius: 10px;
}

.panel-heading {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 15px;
    margin-bottom: 19px;
}

.panel-heading h2 {
    margin: 0;
    font: 400 25px Georgia, serif;
}

.panel-heading p {
    margin: 7px 0 0;
    color: var(--brown-soft);
    font-size: 12px;
    line-height: 1.6;
}

.fields {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
}

.field label {
    display: block;
    margin-bottom: 7px;
    color: var(--brown-soft);
    font-size: 11px;
    text-transform: uppercase;
}

.field input,
.field select {
    width: 100%;
    min-height: 42px;
    padding: 10px 12px;
    border: 1px solid var(--line);
    border-radius: 5px;
    background: white;
    color: var(--brown);
    font-size: 13px;
}

.field input[type="file"] {
    padding: 8px;
}

.field input:focus,
.field select:focus,
.search-input:focus,
.filter-select:focus {
    outline: 2px solid #947c6430;
    border-color: var(--gold);
}

.form-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 9px;
    margin-top: 20px;
}

.current-photo {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-top: 10px;
    color: var(--brown-soft);
    font-size: 12px;
}

.current-photo img {
    width: 58px;
    height: 58px;
    border-radius: 8px;
    object-fit: cover;
    border: 1px solid var(--line);
}

/* KËRKIMI */
.toolbar {
    display: flex;
    gap: 10px;
    align-items: center;
    margin-bottom: 18px;
}

.search-input,
.filter-select {
    min-width: 0;
    min-height: 42px;
    padding: 9px 12px;
    border: 1px solid var(--line);
    border-radius: 5px;
    background: var(--paper);
    color: var(--brown);
    font-size: 12px;
}

.search-input {
    flex: 1;
}

.filter-select {
    max-width: 205px;
}

/* KARTELAT E STAFIT */
.staff-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 17px;
}

.staff-card {
    min-width: 0;
    overflow: hidden;
    background: var(--paper);
    border: 1px solid #e4d8cb;
    border-radius: 11px;
    box-shadow: 0 5px 16px #3028200a;
    transition: transform .18s, box-shadow .18s;
}

.staff-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 22px #30282013;
}

.photo-wrap {
    position: relative;
    height: 195px;
    margin: 10px 10px 0;
    overflow: hidden;
    border: 1px solid var(--line);
    border-radius: 9px;
    background: linear-gradient(135deg, #e7d8c8, #f8f0e7);
}

.staff-photo {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.photo-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
    color: #71563f;
    font: 400 44px Georgia, serif;
}

.status-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    padding: 6px 10px;
    border: 1px solid #ffffffaa;
    border-radius: 6px;
    background: var(--green);
    color: #31502d;
    font-size: 11px;
}

.status-badge.inactive {
    background: #f2e3df;
    color: #8b493d;
}

.staff-info {
    padding: 14px 14px 0;
}

.staff-name {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
}

.staff-role {
    margin-top: 5px;
    color: var(--gold);
    font-size: 12px;
}

.staff-contact {
    display: grid;
    gap: 7px;
    margin-top: 13px;
    color: #6f6255;
    font-size: 11px;
    overflow-wrap: anywhere;
}

.staff-contact div {
    display: flex;
    gap: 7px;
}

.staff-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    padding: 12px 14px 14px;
}

.tag {
    display: inline-block;
    padding: 5px 8px;
    border-radius: 5px;
    background: var(--pale);
    color: #5e4d3d;
    font-size: 10px;
}

.card-actions {
    display: grid;
    grid-template-columns: 1fr 1fr;
    border-top: 1px solid var(--line);
}

.card-actions form {
    margin: 0;
}

.card-action {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    min-height: 41px;
    padding: 8px 5px;
    border: 0;
    background: transparent;
    color: var(--brown);
    text-decoration: none;
    font-size: 11px;
    cursor: pointer;
}

.card-action:first-child {
    border-right: 1px solid var(--line);
}

.card-action:hover {
    background: var(--pale);
}

.empty {
    grid-column: 1 / -1;
    padding: 42px 20px;
    border: 1px dashed #d9c9b9;
    border-radius: 10px;
    text-align: center;
    color: var(--brown-soft);
}

.empty strong {
    display: block;
    margin-bottom: 8px;
    color: var(--brown);
    font: 400 23px Georgia, serif;
}

.footer {
    display: flex;
    justify-content: space-between;
    gap: 15px;
    margin-top: 26px;
    padding-top: 16px;
    border-top: 1px solid var(--line);
    color: var(--brown-soft);
    font: italic 12px Georgia, serif;
}

@media (max-width: 1250px) {
    .stats,
    .staff-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
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

    .sidebar-label {
        margin-top: 22px;
    }

    .nav-link {
        display: inline-block;
        margin: 3px 2px;
    }

    .main {
        padding: 22px 14px;
    }

    .hero {
        align-items: flex-start;
        flex-direction: column;
    }

    .stats {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .staff-grid,
    .fields {
        grid-template-columns: 1fr;
    }

    .toolbar {
        align-items: stretch;
        flex-direction: column;
    }

    .filter-select {
        max-width: none;
        width: 100%;
    }

    .photo-wrap {
        height: 230px;
    }

    .footer {
        flex-direction: column;
    }
}

@media (max-width: 390px) {
    .stats {
        grid-template-columns: 1fr;
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

    <a class="nav-link" href="index.php">✧ &nbsp; Përmbledhja</a>
    <a class="nav-link" href="terminet.php">▦ &nbsp; Terminet</a>
    <a class="nav-link" href="klientet.php">♙ &nbsp; Klientët</a>
    <a class="nav-link" href="sherbimet.php">✧ &nbsp; Shërbimet</a>
    <a class="nav-link active" href="stafi.php">♙ &nbsp; Stafi</a>

    <p class="sidebar-label">FINANCAT &amp; MENAXHIMI</p>

    <a class="nav-link" href="pagesat.php">€ &nbsp; Pagesat</a>
    <a class="nav-link" href="shpenzimet.php">↗ &nbsp; Shpenzimet</a>
    <a class="nav-link" href="inventari.php">▤ &nbsp; Inventari</a>
    <a class="nav-link" href="raportet.php">▥ &nbsp; Raportet</a>

    <p class="sidebar-label">SISTEMI</p>

    <a class="nav-link" href="cilësimet.php">⚙ &nbsp; Cilësimet</a>
    <a class="nav-link" href="logout.php">⇥ &nbsp; Kyçu</a>
</aside>

<main class="main">

    <div class="topline">
        <span>LUMIÈRE / MENAXHIMI I STAFIT</span>
        <span><?= date('d.m.Y') ?></span>
    </div>

    <header class="hero">
        <div>
            <div class="eyebrow">EKIPI · SPECIALIZIMET · ORGANIZIMI</div>
            <h1>Ekipi ynë<span style="color:#947c64">.</span></h1>
            <p class="description">
                Menaxho punonjësit, fotografitë, kontaktet dhe pozitën
                e secilit anëtar të LUMIÈRE Beauty Studio.
            </p>
        </div>

        <a class="btn" href="stafi.php?shto=1#formulari">
            <span style="font-size:18px">+</span> Shto punonjës
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
                <div class="stat-label">Gjithsej staf</div>
                <div class="stat-value"><?= (int)($stats['total'] ?? 0) ?></div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">✓</div>
            <div>
                <div class="stat-label">Punonjës aktivë</div>
                <div class="stat-value"><?= (int)($stats['aktive'] ?? 0) ?></div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">◷</div>
            <div>
                <div class="stat-label">Joaktivë</div>
                <div class="stat-value"><?= (int)($stats['joaktive'] ?? 0) ?></div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">✧</div>
            <div>
                <div class="stat-label">Të punësuar këtë muaj</div>
                <div class="stat-value"><?= (int)($stats['te_ri'] ?? 0) ?></div>
            </div>
        </div>

    </section>

    <?php if ($showForm): ?>
    <section class="panel" id="formulari">

        <div class="panel-heading">
            <div>
                <h2>
                    <?= !empty($form['staf_id'])
                        ? 'Përditëso punonjësin'
                        : 'Regjistro punonjës të ri' ?>
                </h2>
                <p>Emri dhe mbiemri janë të detyrueshëm.</p>
            </div>

            <a class="btn btn-light" href="stafi.php">Mbyll ✕</a>
        </div>

        <form method="POST" action="stafi.php" enctype="multipart/form-data">

            <input
                type="hidden"
                name="action"
                value="<?= !empty($form['staf_id']) ? 'ndrysho' : 'shto' ?>"
            >

            <input
                type="hidden"
                name="staf_id"
                value="<?= h($form['staf_id'] ?? '') ?>"
            >

            <div class="fields">

                <div class="field">
                    <label for="emri">Emri *</label>
                    <input
                        id="emri"
                        name="emri"
                        maxlength="100"
                        value="<?= h($form['emri'] ?? '') ?>"
                        placeholder="Emri i punonjësit"
                        required
                    >
                </div>

                <div class="field">
                    <label for="mbiemri">Mbiemri *</label>
                    <input
                        id="mbiemri"
                        name="mbiemri"
                        maxlength="100"
                        value="<?= h($form['mbiemri'] ?? '') ?>"
                        placeholder="Mbiemri i punonjësit"
                        required
                    >
                </div>

                <div class="field">
                    <label for="telefoni">Telefoni</label>
                    <input
                        id="telefoni"
                        name="telefoni"
                        type="tel"
                        maxlength="20"
                        value="<?= h($form['telefoni'] ?? '') ?>"
                        placeholder="+383 4X XXX XXX"
                    >
                </div>

                <div class="field">
                    <label for="email">Emaili</label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        maxlength="150"
                        value="<?= h($form['email'] ?? '') ?>"
                        placeholder="email@example.com"
                    >
                </div>

                <div class="field">
                    <label for="pozita">Pozita / specializimi</label>
                    <input
                        id="pozita"
                        name="pozita"
                        maxlength="100"
                        value="<?= h($form['pozita'] ?? '') ?>"
                        placeholder="P.sh. Estetiste, Parukiere"
                    >
                </div>

                <div class="field">
                    <label for="data_punesimit">Data e punësimit</label>
                    <input
                        id="data_punesimit"
                        name="data_punesimit"
                        type="date"
                        value="<?= h($form['data_punesimit'] ?? '') ?>"
                    >
                </div>

                <div class="field">
                    <label for="statusi">Statusi</label>
                    <select id="statusi" name="statusi">
                        <option
                            value="Aktive"
                            <?= ($form['statusi'] ?? 'Aktive') === 'Aktive'
                                ? 'selected' : '' ?>
                        >Aktive</option>

                        <option
                            value="Joaktive"
                            <?= ($form['statusi'] ?? '') === 'Joaktive'
                                ? 'selected' : '' ?>
                        >Joaktive</option>
                    </select>
                </div>

                <div class="field">
                    <label for="foto">Fotografia e punonjësit</label>
                    <input
                        id="foto"
                        name="foto"
                        type="file"
                        accept="image/jpeg,image/png,image/webp,image/gif"
                    >

                    <small style="display:block;margin-top:6px;color:#8b8176;font-size:11px">
                        JPG, PNG, WEBP ose GIF · maksimumi 4 MB
                    </small>

                    <?php if (!empty($form['foto'])): ?>
                        <div class="current-photo">
                            <img
                                src="<?= h($form['foto']) ?>"
                                alt="Fotografia aktuale"
                            >
                            <span>Fotografia aktuale e punonjësit.</span>
                        </div>
                    <?php endif; ?>
                </div>

            </div>

            <div class="form-actions">
                <button class="btn" type="submit">
                    <?= !empty($form['staf_id'])
                        ? 'Ruaj ndryshimet'
                        : 'Regjistro punonjësin' ?>
                </button>

                <a class="btn btn-light" href="stafi.php">Anulo</a>
            </div>

        </form>
    </section>
    <?php endif; ?>

    <section class="panel">

        <div class="panel-heading">
            <div>
                <h2>Anëtarët e ekipit</h2>
                <p>Kartelat e stafit me fotografi, kontakt, pozitë dhe status.</p>
            </div>

            <span style="color:var(--brown-soft);font-size:12px">
                <?= count($staff) ?> rezultate
            </span>
        </div>

        <form class="toolbar" method="GET" action="stafi.php">

            <input
                class="search-input"
                type="search"
                name="kerko"
                value="<?= h($search) ?>"
                placeholder="Kërko emër, telefon, email ose pozitë..."
            >

            <select class="filter-select" name="status">
                <option
                    value="te_gjithe"
                    <?= $statusFilter === 'te_gjithe' ? 'selected' : '' ?>
                >Të gjithë statuset</option>

                <option
                    value="aktive"
                    <?= $statusFilter === 'aktive' ? 'selected' : '' ?>
                >Vetëm aktivët</option>

                <option
                    value="joaktive"
                    <?= $statusFilter === 'joaktive' ? 'selected' : '' ?>
                >Vetëm joaktivët</option>
            </select>

            <select class="filter-select" name="rendit">
                <option value="ri" <?= $sort === 'ri' ? 'selected' : '' ?>>
                    Më të rinjtë
                </option>

                <option value="emri" <?= $sort === 'emri' ? 'selected' : '' ?>>
                    Sipas emrit
                </option>

                <option value="pozita" <?= $sort === 'pozita' ? 'selected' : '' ?>>
                    Sipas pozitës
                </option>

                <option value="punesimi" <?= $sort === 'punesimi' ? 'selected' : '' ?>>
                    Sipas datës së punësimit
                </option>
            </select>

            <button class="btn" type="submit">Filtro</button>

            <?php if (
                $search !== '' ||
                $statusFilter !== 'te_gjithe' ||
                $sort !== 'ri'
            ): ?>
                <a class="btn btn-light" href="stafi.php">Pastro</a>
            <?php endif; ?>

        </form>

        <div class="staff-grid">

            <?php if ($staff): ?>

                <?php foreach ($staff as $person): ?>

                    <?php
                    $initials = strtoupper(
                        substr((string)($person['emri'] ?? ''), 0, 1) .
                        substr((string)($person['mbiemri'] ?? ''), 0, 1)
                    );

                    $photo = trim((string)($person['foto'] ?? ''));

                    $active = ($person['statusi'] ?? '') === 'Aktive';
                    ?>

                    <article class="staff-card">

                        <div class="photo-wrap">

                            <?php if ($photo !== ''): ?>

                                <img
                                    class="staff-photo"
                                    src="<?= h($photo) ?>"
                                    alt="Fotografia e <?= h($person['emri'] . ' ' . $person['mbiemri']) ?>"
                                    onerror="this.style.display='none';this.nextElementSibling.style.display='flex';"
                                >

                                <div class="photo-placeholder" style="display:none">
                                    <?= h($initials ?: 'LS') ?>
                                </div>

                            <?php else: ?>

                                <div class="photo-placeholder">
                                    <?= h($initials ?: 'LS') ?>
                                </div>

                            <?php endif; ?>

                            <span class="status-badge <?= $active ? '' : 'inactive' ?>">
                                <?= h($person['statusi'] ?? 'Pa status') ?>
                            </span>

                        </div>

                        <div class="staff-info">

                            <h3 class="staff-name">
                                <?= h($person['emri'] . ' ' . $person['mbiemri']) ?>
                            </h3>

                            <div class="staff-role">
                                <?= h($person['pozita'] ?: 'Pozita nuk është caktuar') ?>
                            </div>

                            <div class="staff-contact">

                                <div>
                                    <span>☎</span>
                                    <span>
                                        <?= h($person['telefoni'] ?: 'Pa numër telefoni') ?>
                                    </span>
                                </div>

                                <div>
                                    <span>✉</span>
                                    <span>
                                        <?= h($person['email'] ?: 'Pa email') ?>
                                    </span>
                                </div>

                                <div>
                                    <span>◷</span>
                                    <span>
                                        <?php
                                        if (!empty($person['data_punesimit'])) {
                                            echo 'Punësuar më ' .
                                                h(date(
                                                    'd.m.Y',
                                                    strtotime($person['data_punesimit'])
                                                ));
                                        } else {
                                            echo 'Data e punësimit nuk është caktuar';
                                        }
                                        ?>
                                    </span>
                                </div>

                            </div>
                        </div>

                        <div class="staff-tags">
                            <span class="tag">
                                <?= h($person['pozita'] ?: 'Anëtar i ekipit') ?>
                            </span>
                        </div>

                        <div class="card-actions">

                            <a
                                class="card-action"
                                href="stafi.php?edit=<?= (int)$person['staf_id'] ?>#formulari"
                            >
                                ✎ &nbsp; Ndrysho
                            </a>

                            <form
                                method="POST"
                                action="stafi.php"
                                onsubmit="return confirm('A dëshiron ta <?= $active ? 'çaktivizosh' : 'aktivizosh' ?> këtë punonjës?');"
                            >
                                <input
                                    type="hidden"
                                    name="action"
                                    value="ndrysho_status"
                                >

                                <input
                                    type="hidden"
                                    name="staf_id"
                                    value="<?= (int)$person['staf_id'] ?>"
                                >

                                <button class="card-action" type="submit">
                                    <?= $active ? '○ Çaktivizo' : '✓ Aktivizo' ?>
                                </button>
                            </form>

                        </div>

                    </article>

                <?php endforeach; ?>

            <?php else: ?>

                <div class="empty">

                    <strong>Nuk u gjet asnjë punonjës.</strong>

                    <?= $search !== '' || $statusFilter !== 'te_gjithe'
                        ? 'Ndrysho kërkimin ose filtrat.'
                        : 'Regjistro punonjësin e parë për ta shfaqur këtu.' ?>

                    <div style="margin-top:16px">
                        <a class="btn" href="stafi.php?shto=1#formulari">
                            + Shto punonjës
                        </a>
                    </div>

                </div>

            <?php endif; ?>

        </div>
    </section>

    <footer class="footer">
        <span>LUMIÈRE Beauty Studio</span>
        <span>Beauty is in every detail.</span>
    </footer>

</main>
</div>
</body>
</html>