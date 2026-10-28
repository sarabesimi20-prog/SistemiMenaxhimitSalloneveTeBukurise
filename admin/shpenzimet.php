<?php
require_once __DIR__ . '/../config/database.php';

function h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$categories = [
    'Qira',
    'Energji elektrike',
    'Uji',
    'Internet dhe telefon',
    'Produkte dhe materiale',
    'Pajisje',
    'Mirëmbajtje dhe riparime',
    'Marketing',
    'Transport',
    'Taksa dhe tarifa',
    'Paga',
    'Të tjera'
];

$paymentMethods = [
    'Para të gatshme',
    'Kartelë bankare',
    'Transfer bankar',
    'Tjetër'
];

$messageMap = [
    'shtuar' => 'Shpenzimi u regjistrua me sukses.',
    'ndryshuar' => 'Shpenzimi u përditësua me sukses.',
    'fshire' => 'Shpenzimi u fshi me sukses.'
];

$message = $messageMap[$_GET['mesazh'] ?? ''] ?? '';
$error = '';
$showForm = isset($_GET['shto']) || isset($_GET['edit']);

$form = [
    'shpenzim_id' => '',
    'pershkrimi' => '',
    'kategoria' => 'Produkte dhe materiale',
    'shuma' => '',
    'data_shpenzimit' => date('Y-m-d'),
    'menyra_pageses' => '',
    'shenime' => ''
];

$expenses = [];

$stats = [
    'total_muaj' => 0,
    'total_sot' => 0,
    'nr_muaj' => 0,
    'kategoria_kryesore' => '—'
];

$monthlyChart = [];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        /* FSHIRJA E SHPENZIMIT */
        if ($action === 'fshij') {
            $id = filter_var(
                $_POST['shpenzim_id'] ?? '',
                FILTER_VALIDATE_INT
            );

            if (!$id || $id < 1) {
                throw new RuntimeException(
                    'ID-ja e shpenzimit nuk është valide.'
                );
            }

            $stmt = $pdo->prepare(
                'DELETE FROM shpenzimet
                 WHERE shpenzim_id = :id'
            );

            $stmt->execute(['id' => $id]);

            header('Location: shpenzimet.php?mesazh=fshire');
            exit;
        }

        /* regjistrimi dhe ndryshimi  */
        if ($action === 'shto' || $action === 'ndrysho') {
            $showForm = true;

            $form = [
                'shpenzim_id' => trim($_POST['shpenzim_id'] ?? ''),
                'pershkrimi' => trim($_POST['pershkrimi'] ?? ''),
                'kategoria' => trim($_POST['kategoria'] ?? ''),
                'shuma' => trim($_POST['shuma'] ?? ''),
                'data_shpenzimit' => trim(
                    $_POST['data_shpenzimit'] ?? ''
                ),
                'menyra_pageses' => trim(
                    $_POST['menyra_pageses'] ?? ''
                ),
                'shenime' => trim($_POST['shenime'] ?? '')
            ];

            $id = null;

            if ($action === 'ndrysho') {
                $id = filter_var(
                    $form['shpenzim_id'],
                    FILTER_VALIDATE_INT
                );

                if (!$id || $id < 1) {
                    throw new RuntimeException(
                        'ID-ja e shpenzimit nuk është valide.'
                    );
                }
            }

            if (
                $form['pershkrimi'] === '' ||
                mb_strlen($form['pershkrimi']) > 255
            ) {
                throw new RuntimeException(
                    'Përshkrimi është i detyrueshëm dhe nuk duhet të kalojë 255 karaktere.'
                );
            }

            if (
                $form['kategoria'] === '' ||
                mb_strlen($form['kategoria']) > 100
            ) {
                throw new RuntimeException(
                    'Kategoria është e detyrueshme dhe nuk duhet të kalojë 100 karaktere.'
                );
            }

            if (
                $form['shuma'] === '' ||
                !is_numeric($form['shuma']) ||
                (float) $form['shuma'] <= 0
            ) {
                throw new RuntimeException(
                    'Shuma duhet të jetë numër më i madh se zero.'
                );
            }

            if ((float) $form['shuma'] > 99999999.99) {
                throw new RuntimeException(
                    'Shuma e shpenzimit është tepër e madhe.'
                );
            }

            $form['shuma'] = number_format(
                (float) $form['shuma'],
                2,
                '.',
                ''
            );

            $date = DateTime::createFromFormat(
                '!Y-m-d',
                $form['data_shpenzimit']
            );

            if (
                !$date ||
                $date->format('Y-m-d') !== $form['data_shpenzimit']
            ) {
                throw new RuntimeException(
                    'Zgjidh një datë valide për shpenzimin.'
                );
            }

            if (
                $form['menyra_pageses'] !== '' &&
                !in_array(
                    $form['menyra_pageses'],
                    $paymentMethods,
                    true
                )
            ) {
                throw new RuntimeException(
                    'Zgjidh një mënyrë valide të pagesës.'
                );
            }

            if (mb_strlen($form['shenime']) > 5000) {
                throw new RuntimeException(
                    'Shënimet janë tepër të gjata.'
                );
            }

            $data = [
                'pershkrimi' => $form['pershkrimi'],
                'kategoria' => $form['kategoria'],
                'shuma' => $form['shuma'],
                'data_shpenzimit' => $form['data_shpenzimit'],
                'menyra_pageses' =>
                    $form['menyra_pageses'] !== ''
                    ? $form['menyra_pageses']
                    : null,
                'shenime' =>
                    $form['shenime'] !== ''
                    ? $form['shenime']
                    : null
            ];

            if ($action === 'shto') {
                $stmt = $pdo->prepare(
                    'INSERT INTO shpenzimet
                    (
                        pershkrimi,
                        kategoria,
                        shuma,
                        data_shpenzimit,
                        menyra_pageses,
                        shenime
                    )
                    VALUES
                    (
                        :pershkrimi,
                        :kategoria,
                        :shuma,
                        :data_shpenzimit,
                        :menyra_pageses,
                        :shenime
                    )'
                );

                $stmt->execute($data);

                header('Location: shpenzimet.php?mesazh=shtuar');
                exit;
            }

            $data['id'] = $id;

            $stmt = $pdo->prepare(
                'UPDATE shpenzimet SET
                    pershkrimi = :pershkrimi,
                    kategoria = :kategoria,
                    shuma = :shuma,
                    data_shpenzimit = :data_shpenzimit,
                    menyra_pageses = :menyra_pageses,
                    shenime = :shenime
                 WHERE shpenzim_id = :id'
            );

            $stmt->execute($data);

            header('Location: shpenzimet.php?mesazh=ndryshuar');
            exit;
        }
    }

    /* marrja e te dhenave per ndryshim */
    if (isset($_GET['edit'])) {
        $editId = filter_var(
            $_GET['edit'],
            FILTER_VALIDATE_INT
        );

        if ($editId && $editId > 0) {
            $stmt = $pdo->prepare(
                'SELECT *
                 FROM shpenzimet
                 WHERE shpenzim_id = :id'
            );

            $stmt->execute(['id' => $editId]);

            $record = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($record) {
                $form = array_merge($form, $record);
                $showForm = true;
            } else {
                $error = 'Shpenzimi i zgjedhur nuk u gjet.';
            }
        }
    }
} catch (RuntimeException $e) {
    $error = $e->getMessage();
} catch (PDOException $e) {
    $error = 'Ndodhi një gabim gjatë ruajtjes në databazë. Kontrollo tabelën shpenzimet.';
}

/* kerkimi dhe filtrat*/
$search = trim($_GET['kerko'] ?? '');
$categoryFilter = trim($_GET['kategoria'] ?? '');
$dateFrom = trim($_GET['nga'] ?? '');
$dateTo = trim($_GET['deri'] ?? '');
$sort = $_GET['rendit'] ?? 'data_ri';

$orderOptions = [
    'data_ri' => 'data_shpenzimit DESC, shpenzim_id DESC',
    'data_vjeter' => 'data_shpenzimit ASC, shpenzim_id ASC',
    'shuma_larte' => 'shuma DESC, data_shpenzimit DESC',
    'shuma_ulet' => 'shuma ASC, data_shpenzimit DESC',
    'pershkrimi' => 'pershkrimi ASC'
];

$orderBy = $orderOptions[$sort] ?? $orderOptions['data_ri'];

try {
    /* statistikat kryesore */
    $stmt = $pdo->query(
        "SELECT
            COALESCE(
                SUM(
                    CASE
                        WHEN data_shpenzimit >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
                         AND data_shpenzimit < DATE_FORMAT(
                            CURDATE() + INTERVAL 1 MONTH,
                            '%Y-%m-01'
                         )
                        THEN shuma ELSE 0
                    END
                ), 0
            ) AS total_muaj,

            COALESCE(
                SUM(
                    CASE
                        WHEN data_shpenzimit = CURDATE()
                        THEN shuma ELSE 0
                    END
                ), 0
            ) AS total_sot,

            COALESCE(
                SUM(
                    CASE
                        WHEN data_shpenzimit >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
                         AND data_shpenzimit < DATE_FORMAT(
                            CURDATE() + INTERVAL 1 MONTH,
                            '%Y-%m-01'
                         )
                        THEN 1 ELSE 0
                    END
                ), 0
            ) AS nr_muaj

         FROM shpenzimet"
    );

    $stats = array_merge(
        $stats,
        $stmt->fetch(PDO::FETCH_ASSOC) ?: []
    );

    /* kategoria me shume shpenzime ket muaj */
    $stmt = $pdo->query(
        "SELECT kategoria, SUM(shuma) AS total
         FROM shpenzimet
         WHERE data_shpenzimit >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
           AND data_shpenzimit < DATE_FORMAT(
                CURDATE() + INTERVAL 1 MONTH,
                '%Y-%m-01'
           )
         GROUP BY kategoria
         ORDER BY total DESC
         LIMIT 1"
    );

    $topCategory = $stmt->fetch(PDO::FETCH_ASSOC);

    $stats['kategoria_kryesore'] =
        $topCategory['kategoria'] ?? '—';

    /* te dhenat per grafikun mujor  */
    $stmt = $pdo->query(
        "SELECT
            DATE_FORMAT(data_shpenzimit, '%Y-%m') AS muaji,
            SUM(shuma) AS total
         FROM shpenzimet
         WHERE data_shpenzimit >= DATE_SUB(
            DATE_FORMAT(CURDATE(), '%Y-%m-01'),
            INTERVAL 5 MONTH
         )
           AND data_shpenzimit < DATE_FORMAT(
            CURDATE() + INTERVAL 1 MONTH,
            '%Y-%m-01'
         )
         GROUP BY DATE_FORMAT(data_shpenzimit, '%Y-%m')
         ORDER BY muaji ASC"
    );

    $chartRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $chartTotals = [];

    foreach ($chartRows as $chartRow) {
        $chartTotals[$chartRow['muaji']] =
            (float) $chartRow['total'];
    }

    $albanianMonths = [
        1 => 'Jan',
        2 => 'Shk',
        3 => 'Mar',
        4 => 'Pri',
        5 => 'Maj',
        6 => 'Qer',
        7 => 'Kor',
        8 => 'Gus',
        9 => 'Sht',
        10 => 'Tet',
        11 => 'Nën',
        12 => 'Dhj'
    ];

    for ($i = 5; $i >= 0; $i--) {
        $monthDate = new DateTime('first day of this month');
        $monthDate->modify('-' . $i . ' months');

        $key = $monthDate->format('Y-m');

        $monthlyChart[] = [
            'key' => $key,
            'label' =>
                $albanianMonths[(int) $monthDate->format('n')]
                . ' ' . $monthDate->format('y'),
            'total' => $chartTotals[$key] ?? 0
        ];
    }

    /* ndertimi i filtrave */
    $where = [];
    $params = [];

    if ($search !== '') {
        $where[] = '(
            pershkrimi LIKE :q1
            OR kategoria LIKE :q2
            OR shenime LIKE :q3
            OR menyra_pageses LIKE :q4
        )';

        $query = '%' . $search . '%';

        $params['q1'] = $query;
        $params['q2'] = $query;
        $params['q3'] = $query;
        $params['q4'] = $query;
    }

    if ($categoryFilter !== '') {
        $where[] = 'kategoria = :kategori';
        $params['kategori'] = $categoryFilter;
    }

    if ($dateFrom !== '') {
        $fromDate = DateTime::createFromFormat(
            '!Y-m-d',
            $dateFrom
        );

        if (
            $fromDate &&
            $fromDate->format('Y-m-d') === $dateFrom
        ) {
            $where[] = 'data_shpenzimit >= :nga';
            $params['nga'] = $dateFrom;
        } else {
            $dateFrom = '';
        }
    }

    if ($dateTo !== '') {
        $toDate = DateTime::createFromFormat(
            '!Y-m-d',
            $dateTo
        );

        if (
            $toDate &&
            $toDate->format('Y-m-d') === $dateTo
        ) {
            $where[] = 'data_shpenzimit <= :deri';
            $params['deri'] = $dateTo;
        } else {
            $dateTo = '';
        }
    }

    $sql = 'SELECT
                shpenzim_id,
                pershkrimi,
                kategoria,
                shuma,
                data_shpenzimit,
                menyra_pageses,
                shenime,
                krijuar_me
            FROM shpenzimet';

    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $sql .= ' ORDER BY ' . $orderBy;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    if ($error === '') {
        $error = 'Nuk mund të lexohen të dhënat e shpenzimeve. Kontrollo databazën.';
    }

    $expenses = [];
}

$chartMax = 0;

foreach ($monthlyChart as $month) {
    $chartMax = max($chartMax, (float) $month['total']);
}
?>
<!DOCTYPE html>
<html lang="sq">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Shpenzimet | LUMIÈRE Beauty Studio</title>

<style>
:root {
    --cream: #f7f4ef;
    --paper: #fffdf9;
    --brown: #302820;
    --brown-soft: #8b8176;
    --gold: #947c64;
    --line: #eae2d8;
    --pale: #eee5da;
    --green: #edf3e9;
    --red: #f9ebe7;
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

button,
input,
select,
textarea {
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
    transition: background .2s;
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
    max-width: 620px;
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
    color: #fff;
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

.notice {
    padding: 13px 16px;
    margin-bottom: 16px;
    border: 1px solid;
    border-radius: 6px;
    font-size: 13px;
}

.success {
    color: #42633b;
    background: var(--green);
    border-color: #d8e5d1;
}

.error {
    color: #914638;
    background: var(--red);
    border-color: #efd2ca;
}

/* statistika */
.stats {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 13px;
    margin-bottom: 22px;
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
    letter-spacing: .4px;
}

.stat-value {
    margin-top: 5px;
    font: 400 clamp(20px, 2vw, 27px) Georgia, serif;
    overflow-wrap: anywhere;
}

/* grafiku dhe permbledhja */
.content-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.35fr) minmax(280px, .85fr);
    gap: 17px;
    margin-bottom: 22px;
}

.panel {
    min-width: 0;
    margin-bottom: 22px;
    padding: 22px;
    background: var(--paper);
    border: 1px solid var(--line);
    border-radius: 10px;
    box-shadow: 0 5px 16px #30282007;
}

.content-grid .panel {
    margin-bottom: 0;
}

.panel-heading {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 15px;
    margin-bottom: 18px;
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

.chart {
    display: flex;
    align-items: stretch;
    gap: 12px;
    height: 190px;
    padding-top: 14px;
    border-bottom: 1px solid var(--line);
}

.chart-column {
    display: flex;
    flex: 1;
    min-width: 0;
    flex-direction: column;
    align-items: center;
    justify-content: flex-end;
    gap: 8px;
}

.chart-value {
    font-size: 10px;
    color: var(--brown-soft);
    white-space: nowrap;
}

.bar-track {
    display: flex;
    align-items: flex-end;
    justify-content: center;
    width: min(100%, 36px);
    height: 128px;
    border-radius: 7px 7px 2px 2px;
    background: #f3eee7;
    overflow: hidden;
}

.bar-fill {
    width: 100%;
    min-height: 0;
    border-radius: 7px 7px 0 0;
    background: linear-gradient(180deg, #bda68e, #80674f);
}

.chart-label {
    padding-bottom: 9px;
    color: var(--brown-soft);
    font-size: 10px;
    white-space: nowrap;
}

.chart-note {
    margin-top: 12px;
    color: var(--brown-soft);
    font-size: 11px;
}

.summary-row {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 0;
    border-bottom: 1px solid var(--line);
}

.summary-row:last-child {
    border-bottom: 0;
    padding-bottom: 0;
}

.summary-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 42px;
    height: 42px;
    flex-shrink: 0;
    border-radius: 9px;
    background: var(--pale);
    color: var(--gold);
    font-size: 18px;
}

.summary-text {
    min-width: 0;
    flex: 1;
}

.summary-title {
    font-size: 12px;
    font-weight: 600;
}

.summary-sub {
    margin-top: 4px;
    color: var(--brown-soft);
    font-size: 11px;
}

.summary-value {
    font-size: 12px;
    font-weight: 600;
    text-align: right;
    overflow-wrap: anywhere;
}

/* FORMULARI */
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
    letter-spacing: .4px;
}

.field input,
.field select,
.field textarea {
    width: 100%;
    min-height: 42px;
    padding: 10px 12px;
    border: 1px solid var(--line);
    border-radius: 5px;
    background: #fff;
    color: var(--brown);
    font-size: 13px;
}

.field textarea {
    min-height: 90px;
    resize: vertical;
}

.field input:focus,
.field select:focus,
.field textarea:focus,
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

/* filtrat*/
.toolbar {
    display: grid;
    grid-template-columns:
        minmax(220px, 1.5fr)
        repeat(2, minmax(135px, 1fr))
        repeat(2, minmax(135px, .9fr))
        auto auto;
    gap: 9px;
    align-items: center;
    margin-bottom: 17px;
}

.search-input,
.filter-select {
    width: 100%;
    min-width: 0;
    min-height: 42px;
    padding: 9px 11px;
    border: 1px solid var(--line);
    border-radius: 5px;
    background: var(--paper);
    color: var(--brown);
    font-size: 12px;
}

/* tabela */
.table-wrap {
    width: 100%;
    overflow-x: auto;
    border: 1px solid var(--line);
    border-radius: 7px;
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
    padding: 13px 14px;
    color: #6f6255;
    font-size: 10px;
    font-weight: 600;
    letter-spacing: .7px;
    text-transform: uppercase;
}

td {
    padding: 13px 14px;
    border-top: 1px solid var(--line);
    font-size: 12px;
}

tbody tr:hover {
    background: #faf7f2;
}

.expense-title {
    font-weight: 600;
    color: var(--brown);
}

.expense-note {
    max-width: 220px;
    margin-top: 4px;
    color: var(--brown-soft);
    font-size: 10px;
    white-space: normal;
    line-height: 1.5;
}

.category-pill {
    display: inline-block;
    padding: 5px 8px;
    border-radius: 5px;
    background: var(--pale);
    color: #5e4d3d;
    font-size: 10px;
}

.amount {
    font-weight: 600;
    font-variant-numeric: tabular-nums;
}

.action-group {
    display: flex;
    gap: 6px;
    align-items: center;
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
    background: var(--red);
}

.delete-form {
    display: inline;
    margin: 0;
}

.empty {
    padding: 42px 20px;
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

@media (max-width: 1350px) {
    .stats {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .toolbar {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .content-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 1050px) {
    .sidebar {
        width: 250px;
    }

    .main {
        padding: 25px 20px;
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

    .fields {
        grid-template-columns: 1fr;
    }

    .toolbar {
        grid-template-columns: 1fr;
    }

    .footer {
        flex-direction: column;
    }

    .panel {
        padding: 16px;
    }
}

@media (max-width: 390px) {
    .stats {
        grid-template-columns: 1fr;
    }

    .stat-card {
        padding: 14px;
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
    <a class="nav-link" href="stafi.php">♙ &nbsp; Stafi</a>

    <p class="sidebar-label">FINANCAT &amp; MENAXHIMI</p>

    <a class="nav-link" href="pagesat.php">€ &nbsp; Pagesat</a>
    <a class="nav-link active" href="shpenzimet.php">↗ &nbsp; Shpenzimet</a>
    <a class="nav-link" href="inventari.php">▤ &nbsp; Inventari</a>
    <a class="nav-link" href="raportet.php">▥ &nbsp; Raportet</a>

    <p class="sidebar-label">SISTEMI</p>

    <a class="nav-link" href="cilësimet.php">⚙ &nbsp; Cilësimet</a>
    <a class="nav-link" href="logout.php">⇥ &nbsp; Kyçu</a>

</aside>

<main class="main">

    <div class="topline">
        <span>LUMIÈRE / MENAXHIMI FINANCIAR</span>
        <span><?= date('d.m.Y') ?></span>
    </div>

    <header class="hero">
        <div>
            <div class="eyebrow">KONTROLLI · ANALIZA · PLANIFIKIMI</div>

            <h1>
                Shpenzimet<span style="color:#947c64">.</span>
            </h1>

            <p class="description">
                Regjistro dhe analizo kostot e studios në një hapësirë
                të organizuar, nga shpenzimet e përditshme deri te
                përmbledhja mujore.
            </p>
        </div>

        <a class="btn" href="shpenzimet.php?shto=1#formulari">
            <span style="font-size:18px">+</span>
            Regjistro shpenzim
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
            <div class="stat-icon">€</div>
            <div>
                <div class="stat-label">Shpenzime këtë muaj</div>
                <div class="stat-value">
                    € <?= number_format(
                        (float)($stats['total_muaj'] ?? 0),
                        2, ',', '.'
                    ) ?>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">◷</div>
            <div>
                <div class="stat-label">Shpenzime sot</div>
                <div class="stat-value">
                    € <?= number_format(
                        (float)($stats['total_sot'] ?? 0),
                        2, ',', '.'
                    ) ?>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">▤</div>
            <div>
                <div class="stat-label">Regjistrime këtë muaj</div>
                <div class="stat-value">
                    <?= (int)($stats['nr_muaj'] ?? 0) ?>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">✧</div>
            <div>
                <div class="stat-label">Kategoria kryesore këtë muaj</div>
                <div class="stat-value" style="font-size:18px">
                    <?= h($stats['kategoria_kryesore'] ?? '—') ?>
                </div>
            </div>
        </div>

    </section>

    <section class="content-grid">

        <div class="panel">

            <div class="panel-heading">
                <div>
                    <h2>Trendi i shpenzimeve</h2>
                    <p>Krahasimi i gjashtë muajve të fundit.</p>
                </div>
            </div>

            <?php if ($chartMax > 0): ?>

                <div class="chart">

                    <?php foreach ($monthlyChart as $month): ?>

                        <?php
                        $height = $chartMax > 0 &&
                            (float)$month['total'] > 0
                            ? max(
                                7,
                                ((float)$month['total'] / $chartMax) * 100
                            )
                            : 0;
                        ?>

                        <div class="chart-column">

                            <div class="chart-value">
                                <?= number_format(
                                    (float)$month['total'],
                                    0, ',', '.'
                                ) ?> €
                            </div>

                            <div class="bar-track">
                                <div
                                    class="bar-fill"
                                    style="height:<?= $height ?>%"
                                ></div>
                            </div>

                            <div class="chart-label">
                                <?= h($month['label']) ?>
                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

                <div class="chart-note">
                    Vlerat paraqesin shumën e shpenzimeve të regjistruara
                    për secilin muaj.
                </div>

            <?php else: ?>

                <div class="empty" style="padding:25px 10px">
                    <strong>Grafiku do të shfaqet këtu.</strong>
                    Regjistro shpenzime që të krijohet krahasimi mujor.
                </div>

            <?php endif; ?>

        </div>

        <div class="panel">

            <div class="panel-heading">
                <div>
                    <h2>Përmbledhje</h2>
                    <p>Informacion i shpejtë për menaxhimin e kostove.</p>
                </div>
            </div>

            <div class="summary-row">
                <div class="summary-icon">▦</div>

                <div class="summary-text">
                    <div class="summary-title">Kategoria kryesore</div>
                    <div class="summary-sub">
                        Sipas shumës së këtij muaji
                    </div>
                </div>

                <div class="summary-value">
                    <?= h($stats['kategoria_kryesore'] ?? '—') ?>
                </div>
            </div>

            <div class="summary-row">
                <div class="summary-icon">€</div>

                <div class="summary-text">
                    <div class="summary-title">Totali mujor</div>
                    <div class="summary-sub">
                        Nga data 1 e muajit deri sot
                    </div>
                </div>

                <div class="summary-value">
                    € <?= number_format(
                        (float)($stats['total_muaj'] ?? 0),
                        2, ',', '.'
                    ) ?>
                </div>
            </div>

            <div class="summary-row">
                <div class="summary-icon">✓</div>

                <div class="summary-text">
                    <div class="summary-title">Regjistrime mujore</div>
                    <div class="summary-sub">
                        Numri i shpenzimeve këtë muaj
                    </div>
                </div>

                <div class="summary-value">
                    <?= (int)($stats['nr_muaj'] ?? 0) ?>
                </div>
            </div>

        </div>

    </section>

    <?php if ($showForm): ?>

    <section class="panel" id="formulari">

        <div class="panel-heading">
            <div>
                <h2>
                    <?= !empty($form['shpenzim_id'])
                        ? 'Përditëso shpenzimin'
                        : 'Regjistro shpenzim të ri' ?>
                </h2>

                <p>
                    Plotëso të dhënat e shpenzimit.
                    Fushat me * janë të detyrueshme.
                </p>
            </div>

            <a class="btn btn-light" href="shpenzimet.php">
                Mbyll ✕
            </a>
        </div>

        <form method="POST" action="shpenzimet.php" id="expenseForm">

            <input
                type="hidden"
                name="action"
                value="<?= !empty($form['shpenzim_id'])
                    ? 'ndrysho' : 'shto' ?>"
            >

            <input
                type="hidden"
                name="shpenzim_id"
                value="<?= h($form['shpenzim_id'] ?? '') ?>"
            >

            <div class="fields">

                <div class="field">
                    <label for="pershkrimi">Përshkrimi *</label>

                    <input
                        id="pershkrimi"
                        name="pershkrimi"
                        maxlength="255"
                        value="<?= h($form['pershkrimi'] ?? '') ?>"
                        placeholder="P.sh. Pagesa e energjisë elektrike"
                        required
                    >
                </div>

                <div class="field">
                    <label for="kategoria">Kategoria *</label>

                    <select id="kategoria" name="kategoria" required>
                        <option value="">Zgjidh kategorinë</option>

                        <?php foreach ($categories as $category): ?>
                            <option
                                value="<?= h($category) ?>"
                                <?= ($form['kategoria'] ?? '') === $category
                                    ? 'selected' : '' ?>
                            >
                                <?= h($category) ?>
                            </option>
                        <?php endforeach; ?>

                        <?php if (
                            !empty($form['kategoria']) &&
                            !in_array($form['kategoria'], $categories, true)
                        ): ?>
                            <option
                                value="<?= h($form['kategoria']) ?>"
                                selected
                            >
                                <?= h($form['kategoria']) ?>
                            </option>
                        <?php endif; ?>

                    </select>
                </div>

                <div class="field">
                    <label for="shuma">Shuma (€) *</label>

                    <input
                        id="shuma"
                        name="shuma"
                        type="number"
                        min="0.01"
                        max="99999999.99"
                        step="0.01"
                        value="<?= h($form['shuma'] ?? '') ?>"
                        placeholder="0.00"
                        required
                    >
                </div>

                <div class="field">
                    <label for="data_shpenzimit">Data e shpenzimit *</label>

                    <input
                        id="data_shpenzimit"
                        name="data_shpenzimit"
                        type="date"
                        value="<?= h(
                            $form['data_shpenzimit'] ?? date('Y-m-d')
                        ) ?>"
                        required
                    >
                </div>

                <div class="field">
                    <label for="menyra_pageses">Mënyra e pagesës</label>

                    <select id="menyra_pageses" name="menyra_pageses">
                        <option value="">Zgjidh mënyrën (opsionale)</option>

                        <?php foreach ($paymentMethods as $method): ?>
                            <option
                                value="<?= h($method) ?>"
                                <?= ($form['menyra_pageses'] ?? '') === $method
                                    ? 'selected' : '' ?>
                            >
                                <?= h($method) ?>
                            </option>
                        <?php endforeach; ?>

                    </select>
                </div>

                <div class="field">
                    <label for="shenime">Shënime</label>

                    <textarea
                        id="shenime"
                        name="shenime"
                        maxlength="5000"
                        placeholder="Detaje shtesë, numri i faturës ose shënime të tjera..."
                    ><?= h($form['shenime'] ?? '') ?></textarea>
                </div>

            </div>

            <div class="form-actions">

                <button class="btn" type="submit">
                    <?= !empty($form['shpenzim_id'])
                        ? 'Ruaj ndryshimet'
                        : 'Regjistro shpenzimin' ?>
                </button>

                <a class="btn btn-light" href="shpenzimet.php">
                    Anulo
                </a>

            </div>

        </form>
    </section>

    <?php endif; ?>

    <section class="panel">

        <div class="panel-heading">
            <div>
                <h2>Regjistri i shpenzimeve</h2>
                <p>
                    Kërko dhe filtro shpenzimet sipas përshkrimit,
                    kategorisë ose datës.
                </p>
            </div>

            <span style="color:var(--brown-soft);font-size:12px">
                <?= count($expenses) ?> rezultate
            </span>
        </div>

        <form class="toolbar" method="GET" action="shpenzimet.php">

            <input
                class="search-input"
                type="search"
                name="kerko"
                value="<?= h($search) ?>"
                placeholder="Kërko përshkrim, kategori..."
            >

            <select class="filter-select" name="kategoria">
                <option value="">Të gjitha kategoritë</option>

                <?php foreach ($categories as $category): ?>
                    <option
                        value="<?= h($category) ?>"
                        <?= $categoryFilter === $category ? 'selected' : '' ?>
                    >
                        <?= h($category) ?>
                    </option>
                <?php endforeach; ?>

                <?php if (
                    $categoryFilter !== '' &&
                    !in_array($categoryFilter, $categories, true)
                ): ?>
                    <option value="<?= h($categoryFilter) ?>" selected>
                        <?= h($categoryFilter) ?>
                    </option>
                <?php endif; ?>

            </select>

            <input
                class="filter-select"
                type="date"
                name="nga"
                value="<?= h($dateFrom) ?>"
                aria-label="Nga data"
                title="Nga data"
            >

            <select class="filter-select" name="rendit">
                <option value="data_ri"
                    <?= $sort === 'data_ri' ? 'selected' : '' ?>>
                    Data: më të rejat
                </option>

                <option value="data_vjeter"
                    <?= $sort === 'data_vjeter' ? 'selected' : '' ?>>
                    Data: më të vjetrat
                </option>

                <option value="shuma_larte"
                    <?= $sort === 'shuma_larte' ? 'selected' : '' ?>>
                    Shuma: më e larta
                </option>

                <option value="shuma_ulet"
                    <?= $sort === 'shuma_ulet' ? 'selected' : '' ?>>
                    Shuma: më e ulëta
                </option>

                <option value="pershkrimi"
                    <?= $sort === 'pershkrimi' ? 'selected' : '' ?>>
                    Përshkrimi A–Z
                </option>
            </select>

            <input
                class="filter-select"
                type="date"
                name="deri"
                value="<?= h($dateTo) ?>"
                aria-label="Deri më"
                title="Deri më"
            >

            <button class="btn" type="submit">Filtro</button>

            <?php if (
                $search !== '' ||
                $categoryFilter !== '' ||
                $dateFrom !== '' ||
                $dateTo !== '' ||
                $sort !== 'data_ri'
            ): ?>
                <a class="btn btn-light" href="shpenzimet.php">
                    Pastro
                </a>
            <?php endif; ?>

        </form>

        <?php if ($expenses): ?>

            <div class="table-wrap">

                <table>
                    <thead>
                    <tr>
                        <th>Përshkrimi</th>
                        <th>Kategoria</th>
                        <th>Data</th>
                        <th>Mënyra e pagesës</th>
                        <th>Shuma</th>
                        <th>Veprimet</th>
                    </tr>
                    </thead>

                    <tbody>

                    <?php foreach ($expenses as $expense): ?>

                        <tr>
                            <td>
                                <div class="expense-title">
                                    <?= h($expense['pershkrimi']) ?>
                                </div>

                                <?php if (!empty($expense['shenime'])): ?>
                                    <div class="expense-note">
                                        <?= h($expense['shenime']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <td>
                                <span class="category-pill">
                                    <?= h($expense['kategoria']) ?>
                                </span>
                            </td>

                            <td>
                                <?= h(date(
                                    'd.m.Y',
                                    strtotime($expense['data_shpenzimit'])
                                )) ?>
                            </td>

                            <td>
                                <?= h($expense['menyra_pageses'] ?: '—') ?>
                            </td>

                            <td class="amount">
                                € <?= number_format(
                                    (float)$expense['shuma'],
                                    2,
                                    ',',
                                    '.'
                                ) ?>
                            </td>

                            <td>
                                <div class="action-group">

                                    <a
                                        class="small-btn"
                                        href="shpenzimet.php?edit=<?= (int)$expense['shpenzim_id'] ?>#formulari"
                                    >
                                        Ndrysho
                                    </a>

                                    <form
                                        class="delete-form"
                                        method="POST"
                                        action="shpenzimet.php"
                                        onsubmit="return confirm('A dëshiron vërtet ta fshish këtë shpenzim?');"
                                    >
                                        <input
                                            type="hidden"
                                            name="action"
                                            value="fshij"
                                        >

                                        <input
                                            type="hidden"
                                            name="shpenzim_id"
                                            value="<?= (int)$expense['shpenzim_id'] ?>"
                                        >

                                        <button
                                            class="small-btn delete-btn"
                                            type="submit"
                                        >
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
                <strong>Nuk u gjet asnjë shpenzim.</strong>

                <?= $search !== '' ||
                    $categoryFilter !== '' ||
                    $dateFrom !== '' ||
                    $dateTo !== ''
                    ? 'Ndrysho kriteret e kërkimit ose pastro filtrat.'
                    : 'Kliko “Regjistro shpenzim” për ta shtuar shpenzimin e parë.' ?>

                <div style="margin-top:16px">
                    <a
                        class="btn"
                        href="shpenzimet.php?shto=1#formulari"
                    >
                        + Regjistro shpenzim
                    </a>
                </div>
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