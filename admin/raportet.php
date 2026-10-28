<?php
date_default_timezone_set('Europe/Belgrade');
require_once __DIR__ . '/../config/database.php';

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function euro($value): string
{
    return number_format((float)$value, 2, ',', '.') . ' €';
}

function normalize_status($value): string
{
    $value = mb_strtolower((string)$value, 'UTF-8');
    return str_replace(['ë', 'ç'], ['e', 'c'], $value);
}

function valid_date_value($value): bool
{
    if (!is_string($value) || $value === '') {
        return false;
    }

    $d = DateTime::createFromFormat('!Y-m-d', $value);

    return $d && $d->format('Y-m-d') === $value;
}

function month_label($ym): string
{
    $names = [
        1 => 'Jan', 2 => 'Shk', 3 => 'Mar',
        4 => 'Pri', 5 => 'Maj', 6 => 'Qer',
        7 => 'Kor', 8 => 'Gus', 9 => 'Sht',
        10 => 'Tet', 11 => 'Nën', 12 => 'Dhj'
    ];

    $dt = DateTime::createFromFormat('!Y-m', $ym);

    return $dt
        ? $names[(int)$dt->format('n')] . ' ' . $dt->format('Y')
        : $ym;
}

$today = new DateTime('today');
$defaultFrom = (new DateTime('first day of this month'))->format('Y-m-d');
$defaultTo = $today->format('Y-m-d');

$from = $_GET['nga'] ?? $defaultFrom;
$to = $_GET['deri'] ?? $defaultTo;

$error = '';
$message = '';

if (
    !valid_date_value($from) ||
    !valid_date_value($to) ||
    $from > $to
) {
    $from = $defaultFrom;
    $to = $defaultTo;
    $error = 'Intervali i datave nuk është valid. U rikthye periudha e këtij muaji.';
}

$periodDays = (int)((strtotime($to) - strtotime($from)) / 86400) + 1;
$chartMode = $periodDays <= 45 ? 'daily' : 'monthly';

$stats = [
    'income' => 0.0,
    'expenses' => 0.0,
    'balance' => 0.0,
    'appointments' => 0,
    'paid_count' => 0,
    'pending_payments' => 0,
    'refunded_payments' => 0,
    'expense_count' => 0,
    'completed' => 0,
    'cancelled' => 0
];

$statusRows = [];
$services = [];
$staffRows = [];
$chartRows = [];

$serviceColors = [
    '#947c64', '#b9aa98', '#d8c6b2',
    '#8d7864', '#514438', '#cbb9a5'
];

$serviceDonut = 'conic-gradient(#eee5da 0deg 360deg)';

try {
    /*
     * 1. FINANCAT
     * Vetëm pagesat me status "Paguar" llogariten si të ardhura.
     */
    $stmt = $pdo->prepare(
        "SELECT
            COALESCE(
                SUM(
                    CASE
                        WHEN statusi = 'Paguar' THEN shuma
                        ELSE 0
                    END
                ), 0
            ) AS income,

            COALESCE(
                SUM(
                    CASE
                        WHEN statusi = 'Paguar' THEN 1
                        ELSE 0
                    END
                ), 0
            ) AS paid_count,

            COALESCE(
                SUM(
                    CASE
                        WHEN statusi = 'Ne pritje' THEN 1
                        ELSE 0
                    END
                ), 0
            ) AS pending_count,

            COALESCE(
                SUM(
                    CASE
                        WHEN statusi = 'Rimbursuar' THEN 1
                        ELSE 0
                    END
                ), 0
            ) AS refunded_count

         FROM pagesa
         WHERE DATE(COALESCE(data_pageses, krijuar_me)) >= :from_date
           AND DATE(COALESCE(data_pageses, krijuar_me)) <= :to_date"
    );

    $stmt->execute([
        'from_date' => $from,
        'to_date' => $to
    ]);

    $paymentStats = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    $stats['income'] = (float)($paymentStats['income'] ?? 0);
    $stats['paid_count'] = (int)($paymentStats['paid_count'] ?? 0);
    $stats['pending_payments'] = (int)($paymentStats['pending_count'] ?? 0);
    $stats['refunded_payments'] = (int)($paymentStats['refunded_count'] ?? 0);

    /*
     * 2. SHPENZIMET
     */
    $stmt = $pdo->prepare(
        'SELECT
            COALESCE(SUM(shuma), 0) AS expenses,
            COUNT(*) AS expense_count
         FROM shpenzimet
         WHERE data_shpenzimit >= :from_date
           AND data_shpenzimit <= :to_date'
    );

    $stmt->execute([
        'from_date' => $from,
        'to_date' => $to
    ]);

    $expenseStats = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    $stats['expenses'] = (float)($expenseStats['expenses'] ?? 0);
    $stats['expense_count'] = (int)($expenseStats['expense_count'] ?? 0);
    $stats['balance'] = $stats['income'] - $stats['expenses'];

    /*
     * 3. STATUSI I TERMINEVE
     */
    $stmt = $pdo->prepare(
        "SELECT
            COALESCE(NULLIF(TRIM(statusi), ''), 'Pa status') AS status_name,
            COUNT(*) AS total
         FROM terminet
         WHERE data >= :from_date
           AND data <= :to_date
         GROUP BY COALESCE(NULLIF(TRIM(statusi), ''), 'Pa status')
         ORDER BY total DESC, status_name ASC"
    );

    $stmt->execute([
        'from_date' => $from,
        'to_date' => $to
    ]);

    $statusRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($statusRows as $row) {
        $stats['appointments'] += (int)$row['total'];

        $normalized = normalize_status($row['status_name']);

        if (
            str_contains($normalized, 'perfund') ||
            str_contains($normalized, 'completed') ||
            str_contains($normalized, 'done')
        ) {
            $stats['completed'] += (int)$row['total'];
        }

        if (
            str_contains($normalized, 'anul') ||
            str_contains($normalized, 'cancel')
        ) {
            $stats['cancelled'] += (int)$row['total'];
        }
    }

    /*
     * 4. SHERBIMET ME TE KERKUARA
     */
    $stmt = $pdo->prepare(
        'SELECT
            s.sherbim_id,
            s.emri AS service_name,
            COUNT(t.termin_id) AS total
         FROM terminet t
         INNER JOIN sherbimet s
            ON s.sherbim_id = t.sherbim_id
         WHERE t.data >= :from_date
           AND t.data <= :to_date
         GROUP BY s.sherbim_id, s.emri
         ORDER BY total DESC, service_name ASC
         LIMIT 6'
    );

    $stmt->execute([
        'from_date' => $from,
        'to_date' => $to
    ]);

    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /*
     * 5. PERFORMANCA E STAFIT
     */
    $stmt = $pdo->prepare(
        "SELECT
            st.staf_id,
            st.emri,
            st.mbiemri,
            COUNT(t.termin_id) AS appointments,

            COALESCE(
                SUM(
                    CASE
                        WHEN LOWER(t.statusi) LIKE '%perfund%'
                          OR LOWER(t.statusi) LIKE '%përfund%'
                          OR LOWER(t.statusi) LIKE '%completed%'
                        THEN 1
                        ELSE 0
                    END
                ), 0
            ) AS completed,

            COALESCE(
                SUM(
                    CASE
                        WHEN LOWER(t.statusi) LIKE '%anul%'
                          OR LOWER(t.statusi) LIKE '%cancel%'
                        THEN 1
                        ELSE 0
                    END
                ), 0
            ) AS cancelled

         FROM stafi st

         LEFT JOIN terminet t
            ON t.staf_id = st.staf_id
           AND t.data >= :from_date
           AND t.data <= :to_date

         GROUP BY st.staf_id, st.emri, st.mbiemri

         ORDER BY appointments DESC, st.emri ASC, st.mbiemri ASC
         LIMIT 10"
    );

    $stmt->execute([
        'from_date' => $from,
        'to_date' => $to
    ]);

    $staffRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /*
     * 6. TE ARDHURAT SIPAS PUNONJESIT
     */
    $stmt = $pdo->prepare(
        "SELECT
            t.staf_id,
            COALESCE(SUM(p.shuma), 0) AS income
         FROM pagesa p
         INNER JOIN terminet t
            ON t.termin_id = p.termin_id
         WHERE p.statusi = 'Paguar'
           AND DATE(COALESCE(p.data_pageses, p.krijuar_me)) >= :from_date
           AND DATE(COALESCE(p.data_pageses, p.krijuar_me)) <= :to_date
         GROUP BY t.staf_id"
    );

    $stmt->execute([
        'from_date' => $from,
        'to_date' => $to
    ]);

    $incomeByStaff = [];

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $incomeByStaff[(int)$row['staf_id']] = (float)$row['income'];
    }

    foreach ($staffRows as &$staff) {
        $staff['income'] =
            $incomeByStaff[(int)$staff['staf_id']] ?? 0.0;
    }

    unset($staff);

    /*
     * 7. TE DHENAT E GRAFIKUT FINANCIAR
     * Intervalet deri në 45 ditë shfaqen ditore.
     * Intervalet më të gjata shfaqen sipas muajve.
     */
    if ($chartMode === 'daily') {
        $stmt = $pdo->prepare(
            "SELECT
                DATE(COALESCE(data_pageses, krijuar_me)) AS bucket,
                COALESCE(SUM(shuma), 0) AS total
             FROM pagesa
             WHERE statusi = 'Paguar'
               AND DATE(COALESCE(data_pageses, krijuar_me)) >= :from_date
               AND DATE(COALESCE(data_pageses, krijuar_me)) <= :to_date
             GROUP BY DATE(COALESCE(data_pageses, krijuar_me))"
        );

        $stmt->execute([
            'from_date' => $from,
            'to_date' => $to
        ]);

        $incomeBuckets = [];

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $incomeBuckets[$r['bucket']] = (float)$r['total'];
        }

        $stmt = $pdo->prepare(
            'SELECT
                data_shpenzimit AS bucket,
                COALESCE(SUM(shuma), 0) AS total
             FROM shpenzimet
             WHERE data_shpenzimit >= :from_date
               AND data_shpenzimit <= :to_date
             GROUP BY data_shpenzimit'
        );

        $stmt->execute([
            'from_date' => $from,
            'to_date' => $to
        ]);

        $expenseBuckets = [];

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $expenseBuckets[$r['bucket']] = (float)$r['total'];
        }

        $cursor = new DateTime($from);
        $endDate = new DateTime($to);

        while ($cursor <= $endDate) {
            $key = $cursor->format('Y-m-d');

            $chartRows[] = [
                'key' => $key,
                'label' => $cursor->format('d.m'),
                'income' => $incomeBuckets[$key] ?? 0,
                'expenses' => $expenseBuckets[$key] ?? 0
            ];

            $cursor->modify('+1 day');
        }

    } else {
        $stmt = $pdo->prepare(
            "SELECT
                DATE_FORMAT(COALESCE(data_pageses, krijuar_me), '%Y-%m') AS bucket,
                COALESCE(SUM(shuma), 0) AS total
             FROM pagesa
             WHERE statusi = 'Paguar'
               AND DATE(COALESCE(data_pageses, krijuar_me)) >= :from_date
               AND DATE(COALESCE(data_pageses, krijuar_me)) <= :to_date
             GROUP BY DATE_FORMAT(COALESCE(data_pageses, krijuar_me), '%Y-%m')"
        );

        $stmt->execute([
            'from_date' => $from,
            'to_date' => $to
        ]);

        $incomeBuckets = [];

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $incomeBuckets[$r['bucket']] = (float)$r['total'];
        }

        $stmt = $pdo->prepare(
            "SELECT
                DATE_FORMAT(data_shpenzimit, '%Y-%m') AS bucket,
                COALESCE(SUM(shuma), 0) AS total
             FROM shpenzimet
             WHERE data_shpenzimit >= :from_date
               AND data_shpenzimit <= :to_date
             GROUP BY DATE_FORMAT(data_shpenzimit, '%Y-%m')"
        );

        $stmt->execute([
            'from_date' => $from,
            'to_date' => $to
        ]);

        $expenseBuckets = [];

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $expenseBuckets[$r['bucket']] = (float)$r['total'];
        }

        $cursor = new DateTime(date('Y-m-01', strtotime($from)));
        $endDate = new DateTime(date('Y-m-01', strtotime($to)));

        while ($cursor <= $endDate) {
            $key = $cursor->format('Y-m');

            $chartRows[] = [
                'key' => $key,
                'label' => month_label($key),
                'income' => $incomeBuckets[$key] ?? 0,
                'expenses' => $expenseBuckets[$key] ?? 0
            ];

            $cursor->modify('+1 month');
        }
    }

    /*
     * 8. EKSPORTI CSV
     */
    if (($_GET['export'] ?? '') === 'csv') {
        $exportRows = [];

        $stmt = $pdo->prepare(
            "SELECT
                DATE(COALESCE(data_pageses, krijuar_me)) AS report_date,
                CONCAT('Pagesa për termin #', termin_id) AS description,
                menyra_pageses AS category,
                shuma AS amount,
                statusi AS report_status
             FROM pagesa
             WHERE DATE(COALESCE(data_pageses, krijuar_me)) >= :from_date
               AND DATE(COALESCE(data_pageses, krijuar_me)) <= :to_date"
        );

        $stmt->execute([
            'from_date' => $from,
            'to_date' => $to
        ]);

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $exportRows[] = [
                'Pagesë',
                $r['report_date'],
                $r['description'],
                $r['category'],
                $r['amount'],
                $r['report_status']
            ];
        }

        $stmt = $pdo->prepare(
            'SELECT
                data_shpenzimit AS report_date,
                pershkrimi AS description,
                kategoria AS category,
                shuma AS amount
             FROM shpenzimet
             WHERE data_shpenzimit >= :from_date
               AND data_shpenzimit <= :to_date'
        );

        $stmt->execute([
            'from_date' => $from,
            'to_date' => $to
        ]);

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $exportRows[] = [
                'Shpenzim',
                $r['report_date'],
                $r['description'],
                $r['category'],
                $r['amount'],
                'Regjistruar'
            ];
        }

        usort(
            $exportRows,
            static fn($a, $b) => strcmp((string)$a[1], (string)$b[1])
        );

        header('Content-Type: text/csv; charset=UTF-8');
        header(
            'Content-Disposition: attachment; filename="lumiere-raporti-'
            . $from . '-deri-' . $to . '.csv"'
        );

        echo "\xEF\xBB\xBF";

        $out = fopen('php://output', 'w');

        fputcsv($out, [
            'Lloji',
            'Data',
            'Përshkrimi',
            'Kategoria / mënyra',
            'Shuma (€)',
            'Statusi'
        ]);

        foreach ($exportRows as $row) {
            fputcsv($out, $row);
        }

        fclose($out);
        exit;
    }

} catch (PDOException $e) {
    if ($error === '') {
        $error = 'Nuk mund të përgatiten të gjitha raportet. Kontrollo emrat e kolonave te pagesa, terminet, sherbimet dhe stafi.';
    }
}

/* PERIUDHAT E SHPEJTA */
$quickPeriods = [];

$quickPeriods['Sot'] = [
    $today->format('Y-m-d'),
    $today->format('Y-m-d')
];

$quickPeriods['7 ditët'] = [
    (clone $today)->modify('-6 days')->format('Y-m-d'),
    $today->format('Y-m-d')
];

$quickPeriods['Ky muaj'] = [
    (new DateTime('first day of this month'))->format('Y-m-d'),
    $today->format('Y-m-d')
];

$quickPeriods['Ky vit'] = [
    (new DateTime('first day of January'))->format('Y-m-d'),
    $today->format('Y-m-d')
];

$maxChartValue = 0.0;

foreach ($chartRows as $row) {
    $maxChartValue = max(
        $maxChartValue,
        (float)$row['income'],
        (float)$row['expenses']
    );
}

$serviceTotal = array_sum(
    array_map(
        static fn($s) => (int)$s['total'],
        $services
    )
);

$donutParts = [];
$donutCursor = 0.0;

if ($serviceTotal > 0) {
    foreach ($services as $i => $service) {
        $degrees = (int)round(
            ((int)$service['total'] / $serviceTotal) * 360
        );

        $color = $serviceColors[$i % count($serviceColors)];
        $next = min(360, $donutCursor + $degrees);

        $donutParts[] = $color . ' ' . $donutCursor . 'deg ' . $next . 'deg';
        $donutCursor = $next;
    }

    if ($donutCursor < 360) {
        $donutParts[] = '#eee5da ' . $donutCursor . 'deg 360deg';
    }

    $serviceDonut = 'conic-gradient(' . implode(', ', $donutParts) . ')';
}

$periodLabel = date('d.m.Y', strtotime($from))
    . ' – ' . date('d.m.Y', strtotime($to));

$exportUrl = 'raportet.php?' . http_build_query([
    'nga' => $from,
    'deri' => $to,
    'export' => 'csv'
]);
?>
<!DOCTYPE html>
<html lang="sq">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Raportet | LUMIÈRE Beauty Studio</title>

<style>
:root {
    --cream: #f7f4ef;
    --paper: #fffdf9;
    --brown: #302820;
    --soft: #8b8176;
    --gold: #947c64;
    --line: #eae2d8;
    --pale: #eee5da;
    --shadow: 0 7px 24px #30282008;
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
    background: #302820;
    color: #f8f3eb;
}

.brand {
    font: 27px Georgia, serif;
    letter-spacing: 4px;
    text-align: center;
}

.brand small {
    display: block;
    margin-top: 8px;
    font: 9px Arial, sans-serif;
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

.nav-link:hover,
.nav-link.active {
    background: #514438;
    color: #fff;
}

.nav-link.active {
    border-left: 3px solid #947c64;
    padding-left: 11px;
}

/* FAQJA E RAPORTEVE */
.main {
    min-width: 0;
    flex: 1;
    padding: 20px clamp(16px, 2vw, 30px) 28px;
}

.topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    padding-bottom: 15px;
    border-bottom: 1px solid var(--line);
}

.global-search {
    width: min(440px, 60%);
}

.global-search input {
    width: 100%;
    height: 40px;
    padding: 0 14px;
    border: 1px solid var(--line);
    border-radius: 8px;
    background: var(--paper);
    font-size: 12px;
}

.user-chip {
    display: flex;
    align-items: center;
    gap: 10px;
    color: var(--soft);
    font-size: 12px;
}

.user-avatar {
    width: 36px;
    height: 36px;
    display: grid;
    place-items: center;
    border-radius: 50%;
    background: var(--pale);
    color: var(--brown);
    font: 16px Georgia, serif;
}

.page-heading {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 20px;
    padding: 21px 0 17px;
}

.eyebrow {
    color: var(--gold);
    font-size: 10px;
    letter-spacing: 3px;
    text-transform: uppercase;
}

.page-heading h1 {
    margin: 7px 0 4px;
    font: 400 clamp(31px, 3vw, 43px)/1.1 Georgia, serif;
    letter-spacing: -.8px;
}

.page-heading p {
    margin: 0;
    color: var(--soft);
    font-size: 13px;
}

.heading-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 8px;
    flex-wrap: wrap;
}

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 39px;
    padding: 0 13px;
    border: 1px solid var(--line);
    border-radius: 7px;
    background: var(--paper);
    color: var(--brown);
    text-decoration: none;
    font-size: 11px;
    cursor: pointer;
    white-space: nowrap;
}

.btn:hover {
    background: var(--pale);
}

.btn.primary {
    background: var(--brown);
    border-color: var(--brown);
    color: #fff;
}

.btn.primary:hover {
    background: #514438;
}

.btn.export {
    background: #947c64;
    border-color: #947c64;
    color: #fff;
}

/* PERIUDHA E RAPORTIT */
.period-tools {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 13px;
    flex-wrap: wrap;
    padding: 13px 15px;
    margin-bottom: 17px;
    background: var(--paper);
    border: 1px solid var(--line);
    border-radius: 10px;
    box-shadow: var(--shadow);
}

.preset-list {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}

.preset {
    display: inline-flex;
    align-items: center;
    min-height: 32px;
    padding: 0 11px;
    border: 1px solid transparent;
    border-radius: 6px;
    background: #f5f0e9;
    color: #716252;
    text-decoration: none;
    font-size: 11px;
}

.preset:hover,
.preset.active {
    background: #302820;
    color: #fff;
}

.date-form {
    display: flex;
    align-items: center;
    gap: 7px;
    flex-wrap: wrap;
}

.date-form label {
    color: var(--soft);
    font-size: 10px;
}

.date-form input {
    height: 33px;
    padding: 0 8px;
    border: 1px solid var(--line);
    border-radius: 5px;
    background: #fff;
    font-size: 11px;
}

.notice {
    padding: 11px 14px;
    margin-bottom: 14px;
    border: 1px solid;
    border-radius: 7px;
    font-size: 12px;
}

.error {
    color: #914638;
    background: #f9ebe7;
    border-color: #efd2ca;
}

/* KPI */
.kpis {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
    margin-bottom: 15px;
}

.kpi {
    position: relative;
    min-width: 0;
    padding: 16px 17px 14px;
    border: 1px solid var(--line);
    border-radius: 10px;
    background: var(--paper);
    box-shadow: var(--shadow);
    overflow: hidden;
}

.kpi::after {
    content: "";
    position: absolute;
    right: -15px;
    top: -20px;
    width: 75px;
    height: 75px;
    border-radius: 50%;
    background: var(--pale);
    opacity: .55;
}

.kpi-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    position: relative;
    z-index: 1;
}

.kpi-icon {
    width: 37px;
    height: 37px;
    display: grid;
    place-items: center;
    border-radius: 9px;
    background: var(--pale);
    color: var(--gold);
    font-size: 18px;
}

.kpi-label {
    font-size: 11px;
    color: var(--soft);
}

.kpi-value {
    margin-top: 10px;
    font: 400 clamp(21px, 2.2vw, 29px) Georgia, serif;
    overflow-wrap: anywhere;
    position: relative;
    z-index: 1;
}

.kpi-foot {
    margin-top: 6px;
    color: var(--soft);
    font-size: 10px;
    position: relative;
    z-index: 1;
}

.kpi-foot strong {
    color: var(--gold);
    font-weight: 600;
}

/* PANELET DHE GRAFIKU */
.panel {
    min-width: 0;
    margin-bottom: 15px;
    padding: 17px 18px;
    border: 1px solid var(--line);
    border-radius: 10px;
    background: var(--paper);
    box-shadow: var(--shadow);
}

.panel-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 14px;
}

.panel-head h2 {
    margin: 0;
    font: 400 21px Georgia, serif;
}

.panel-head p {
    margin: 5px 0 0;
    color: var(--soft);
    font-size: 11px;
    line-height: 1.5;
}

.legend {
    display: flex;
    align-items: center;
    gap: 13px;
    flex-wrap: wrap;
    color: var(--soft);
    font-size: 10px;
}

.legend span {
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
}

.dot.income {
    background: #947c64;
}

.dot.expense {
    background: #d8c6b2;
}

.financial-panel {
    padding-bottom: 13px;
}

.chart-scroll {
    overflow-x: auto;
    padding: 6px 1px 0;
}

.chart-grid {
    display: flex;
    align-items: stretch;
    gap: 7px;
    min-width: max-content;
    height: 190px;
    border-bottom: 1px solid var(--line);
    padding: 0 5px;
}

.chart-col {
    width: 32px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: flex-end;
    gap: 6px;
}

.bars {
    height: 142px;
    width: 100%;
    display: flex;
    align-items: flex-end;
    justify-content: center;
    gap: 3px;
    border-bottom: 1px solid #eee5da;
}

.bar {
    width: 11px;
    min-height: 0;
    border-radius: 4px 4px 0 0;
    transition: height .3s;
}

.bar.revenue {
    background: linear-gradient(180deg, #b7a18a, #80664e);
}

.bar.spending {
    background: linear-gradient(180deg, #e3d2bf, #c2ad96);
}

.chart-label {
    padding-bottom: 8px;
    color: var(--soft);
    font-size: 9px;
    white-space: nowrap;
}

.chart-amount {
    color: var(--soft);
    font-size: 8px;
    white-space: nowrap;
}

/* ANALITIKA */
.analytics-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.15fr) minmax(0, 1fr) minmax(0, .95fr);
    gap: 13px;
    margin-bottom: 15px;
}

.analytics-grid .panel {
    margin: 0;
    padding: 16px;
}

.donut-layout {
    display: flex;
    align-items: center;
    gap: 14px;
}

.donut {
    width: 128px;
    height: 128px;
    flex-shrink: 0;
    border-radius: 50%;
    display: grid;
    place-items: center;
    position: relative;
}

.donut::before {
    content: "";
    position: absolute;
    inset: 19px;
    border-radius: 50%;
    background: var(--paper);
}

.donut-center {
    position: relative;
    text-align: center;
}

.donut-center strong {
    display: block;
    font: 400 25px Georgia, serif;
}

.donut-center span {
    font-size: 10px;
    color: var(--soft);
}

.legend-list {
    flex: 1;
    min-width: 0;
    display: grid;
    gap: 9px;
}

.legend-row {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 10px;
    min-width: 0;
}

.legend-row .name {
    flex: 1;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.legend-row .val {
    color: var(--soft);
    white-space: nowrap;
}

.status-list {
    display: grid;
    gap: 12px;
}

.status-row {
    display: grid;
    grid-template-columns: minmax(78px, 1fr) minmax(50px, 1.3fr) 25px;
    gap: 8px;
    align-items: center;
    font-size: 10px;
}

.status-name {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.progress {
    height: 7px;
    border-radius: 99px;
    overflow: hidden;
    background: #f0ebe4;
}

.progress-fill {
    height: 100%;
    border-radius: 99px;
    background: #947c64;
}

.status-count {
    text-align: right;
    color: var(--soft);
}

.ranking-list {
    display: grid;
    gap: 0;
}

.ranking-row {
    display: grid;
    grid-template-columns: 23px minmax(0, 1fr) auto;
    align-items: center;
    gap: 8px;
    padding: 9px 0;
    border-bottom: 1px solid #f0eae2;
}

.ranking-row:last-child {
    border-bottom: 0;
}

.rank {
    width: 22px;
    height: 22px;
    display: grid;
    place-items: center;
    border-radius: 50%;
    background: #eee5da;
    color: #6d5846;
    font-size: 10px;
}

.ranking-name {
    font-size: 11px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.ranking-meta {
    color: var(--soft);
    font-size: 10px;
    white-space: nowrap;
}

.muted-empty {
    padding: 12px 0;
    color: var(--soft);
    font-size: 11px;
    line-height: 1.6;
}

/* TABELAT */
.table-wrap {
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
    padding: 12px 13px;
    color: #6f6255;
    font-size: 10px;
    letter-spacing: .5px;
    text-transform: uppercase;
    font-weight: 600;
}

td {
    padding: 12px 13px;
    border-top: 1px solid var(--line);
    font-size: 11px;
}

tbody tr:hover {
    background: #faf7f2;
}

.staff-name {
    font-weight: 600;
}

.small-muted {
    margin-top: 3px;
    color: var(--soft);
    font-size: 10px;
}

.status-tag {
    display: inline-block;
    padding: 4px 7px;
    border-radius: 5px;
    background: var(--pale);
    font-size: 10px;
}

.footer {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    margin-top: 20px;
    padding-top: 14px;
    border-top: 1px solid var(--line);
    color: var(--soft);
    font: italic 12px Georgia, serif;
}

.empty-state {
    padding: 20px;
    text-align: center;
    color: var(--soft);
    font-size: 11px;
    line-height: 1.6;
}

@media (max-width: 1350px) {
    .analytics-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .analytics-grid .ranking-panel {
        grid-column: 1 / -1;
    }

    .kpis {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 900px) {
    .sidebar {
        width: 215px;
    }

    .page-heading {
        align-items: flex-start;
        flex-direction: column;
    }

    .heading-actions {
        justify-content: flex-start;
    }

    .analytics-grid {
        grid-template-columns: 1fr;
    }

    .analytics-grid .ranking-panel {
        grid-column: auto;
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
        padding: 18px 13px;
    }

    .topbar {
        align-items: flex-start;
    }

    .global-search {
        width: 70%;
    }

    .period-tools {
        align-items: flex-start;
    }

    .date-form {
        width: 100%;
    }

    .date-form input {
        flex: 1;
        min-width: 105px;
    }

    .kpis {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .panel {
        padding: 14px;
    }

    .donut-layout {
        flex-wrap: wrap;
    }

    .legend-list {
        min-width: 140px;
    }

    .footer {
        flex-direction: column;
    }
}

@media print {
    .sidebar,
    .topbar,
    .period-tools,
    .heading-actions,
    .no-print {
        display: none !important;
    }

    .layout {
        display: block;
    }

    .main {
        padding: 0;
        background: #fff;
    }

    .page-heading {
        padding: 0 0 14px;
    }

    .panel,
    .kpi {
        box-shadow: none;
        break-inside: avoid;
    }

    .analytics-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .kpis {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }

    body {
        background: #fff;
    }

    .footer {
        margin-top: 12px;
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
    <a class="nav-link" href="shpenzimet.php">↗ &nbsp; Shpenzimet</a>
    <a class="nav-link" href="inventari.php">▤ &nbsp; Inventari</a>
    <a class="nav-link active" href="raportet.php">▥ &nbsp; Raportet</a>

    <p class="sidebar-label">SISTEMI</p>
    <a class="nav-link" href="cilësimet.php">⚙ &nbsp; Cilësimet</a>
    <a class="nav-link" href="logout.php">⇥ &nbsp; Kyçu</a>
</aside>

<main class="main">

    <div class="topbar">
        <div class="global-search">
            <input
                id="reportQuickSearch"
                type="search"
                placeholder="Kërko shërbim, staf, status..."
                aria-label="Kërkim i shpejtë në këtë raport"
            >
        </div>

        <div class="user-chip">
            <span>Raportet e studios</span>
            <span class="user-avatar">L</span>
        </div>
    </div>

    <header class="page-heading">
        <div>
            <div class="eyebrow">RAPORTET · ANALIZË E DETAJUAR</div>
            <h1>Analitika e studios.</h1>
            <p>Të gjitha të dhënat e rëndësishme të biznesit në një pamje të vetme.</p>
        </div>

        <div class="heading-actions">
            <button type="button" class="btn" onclick="window.print()">
                ▣ &nbsp; Printo raportin
            </button>

            <a class="btn primary export" href="<?= h($exportUrl) ?>">
                ↓ &nbsp; Eksporto CSV
            </a>
        </div>
    </header>

    <section class="period-tools no-print">
        <div class="preset-list">
            <?php foreach ($quickPeriods as $label => [$presetFrom, $presetTo]): ?>
                <a
                    class="preset <?= $from === $presetFrom && $to === $presetTo ? 'active' : '' ?>"
                    href="raportet.php?<?= h(http_build_query([
                        'nga' => $presetFrom,
                        'deri' => $presetTo
                    ])) ?>"
                >
                    <?= h($label) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <form class="date-form" method="GET" action="raportet.php">
            <label for="nga">Nga</label>
            <input id="nga" name="nga" type="date" value="<?= h($from) ?>" required>

            <label for="deri">Deri</label>
            <input id="deri" name="deri" type="date" value="<?= h($to) ?>" required>

            <button class="btn primary" type="submit">Përditëso</button>
        </form>
    </section>

    <?php if ($error !== ''): ?>
        <div class="notice error"><?= h($error) ?></div>
    <?php endif; ?>

    <section class="kpis">
        <article class="kpi">
            <div class="kpi-top">
                <span class="kpi-icon">€</span>
                <span class="kpi-label">Të ardhurat</span>
            </div>
            <div class="kpi-value"><?= euro($stats['income']) ?></div>
            <div class="kpi-foot">Nga pagesat me status <strong>Paguar</strong></div>
        </article>

        <article class="kpi">
            <div class="kpi-top">
                <span class="kpi-icon">▤</span>
                <span class="kpi-label">Shpenzimet</span>
            </div>
            <div class="kpi-value"><?= euro($stats['expenses']) ?></div>
            <div class="kpi-foot">Nga regjistri i shpenzimeve</div>
        </article>

        <article class="kpi">
            <div class="kpi-top">
                <span class="kpi-icon">↗</span>
                <span class="kpi-label">Bilanci neto</span>
            </div>
            <div class="kpi-value"><?= euro($stats['balance']) ?></div>
            <div class="kpi-foot">Të ardhurat minus shpenzimet</div>
        </article>

        <article class="kpi">
            <div class="kpi-top">
                <span class="kpi-icon">▦</span>
                <span class="kpi-label">Terminet</span>
            </div>
            <div class="kpi-value"><?= (int)$stats['appointments'] ?></div>
            <div class="kpi-foot">
                <?= (int)$stats['completed'] ?> të përfunduara ·
                <?= (int)$stats['cancelled'] ?> të anuluara
            </div>
        </article>
    </section>

    <section class="panel financial-panel">
        <div class="panel-head">
            <div>
                <h2>Ecuria financiare</h2>
                <p>Krahasimi i të ardhurave dhe shpenzimeve për periudhën: <?= h($periodLabel) ?>.</p>
            </div>

            <div class="legend">
                <span><i class="dot income"></i>Të ardhurat</span>
                <span><i class="dot expense"></i>Shpenzimet</span>
            </div>
        </div>

        <?php if (
            array_sum(array_column($chartRows, 'income')) == 0 &&
            array_sum(array_column($chartRows, 'expenses')) == 0
        ): ?>
            <div class="empty-state">
                Nuk ka të dhëna financiare për këtë periudhë.
                Grafiku do të plotësohet kur të regjistrohen pagesa të paguara ose shpenzime.
            </div>
        <?php else: ?>
            <div class="chart-scroll">
                <div class="chart-grid">
                    <?php foreach ($chartRows as $row): ?>
                        <?php
                        $incomeHeight = $maxChartValue > 0
                            ? max(0, ($row['income'] / $maxChartValue) * 100)
                            : 0;

                        $expenseHeight = $maxChartValue > 0
                            ? max(0, ($row['expenses'] / $maxChartValue) * 100)
                            : 0;
                        ?>

                        <div
                            class="chart-col"
                            title="<?= h($row['label']) ?> · Të ardhura: <?= h(euro($row['income'])) ?> · Shpenzime: <?= h(euro($row['expenses'])) ?>"
                        >
                            <div class="chart-amount">
                                <?= $row['income'] > 0
                                    ? h(number_format($row['income'], 0, ',', '.'))
                                    : ' ' ?>
                            </div>

                            <div class="bars">
                                <div
                                    class="bar revenue"
                                    style="height:<?= $incomeHeight ?>%"
                                    title="Të ardhurat: <?= h(euro($row['income'])) ?>"
                                ></div>

                                <div
                                    class="bar spending"
                                    style="height:<?= $expenseHeight ?>%"
                                    title="Shpenzimet: <?= h(euro($row['expenses'])) ?>"
                                ></div>
                            </div>

                            <div class="chart-label"><?= h($row['label']) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </section>

    <section class="analytics-grid">

        <div class="panel">
            <div class="panel-head">
                <div>
                    <h2>Shërbimet sipas kategorisë</h2>
                    <p>Shpërndarja e termineve në periudhë.</p>
                </div>
            </div>

            <?php if ($serviceTotal > 0): ?>
                <div class="donut-layout">
                    <div
                        class="donut"
                        style="background:<?= h($serviceDonut) ?>"
                    >
                        <div class="donut-center">
                            <strong><?= (int)$serviceTotal ?></strong>
                            <span>Termine</span>
                        </div>
                    </div>

                    <div class="legend-list">
                        <?php foreach ($services as $i => $service): ?>
                            <div class="legend-row">
                                <i
                                    class="dot"
                                    style="background:<?= h($serviceColors[$i % count($serviceColors)]) ?>"
                                ></i>

                                <span class="name"><?= h($service['service_name']) ?></span>

                                <span class="val">
                                    <?= round(((int)$service['total'] / $serviceTotal) * 100) ?>%
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="muted-empty">
                    Nuk ka termine të lidhura me shërbimet në këtë periudhë.
                </div>
            <?php endif; ?>
        </div>

        <div class="panel">
            <div class="panel-head">
                <div>
                    <h2>Statusi i termineve</h2>
                    <p>Numri i rezervimeve sipas statusit real.</p>
                </div>
            </div>

            <?php if ($statusRows): ?>
                <div class="status-list">
                    <?php foreach ($statusRows as $i => $status): ?>
                        <?php
                        $percent = $stats['appointments'] > 0
                            ? ((int)$status['total'] / $stats['appointments']) * 100
                            : 0;
                        ?>

                        <div class="status-row">
                            <span
                                class="status-name"
                                title="<?= h($status['status_name']) ?>"
                            >
                                <?= h($status['status_name']) ?>
                            </span>

                            <div class="progress">
                                <div
                                    class="progress-fill"
                                    style="width:<?= $percent ?>%;opacity:<?= max(.45, 1 - ($i * .08)) ?>"
                                ></div>
                            </div>

                            <span class="status-count"><?= (int)$status['total'] ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="muted-empty">
                    Ende nuk ka termine për këtë interval.
                </div>
            <?php endif; ?>
        </div>

        <div class="panel ranking-panel">
            <div class="panel-head">
                <div>
                    <h2>Shërbimet më të kërkuara</h2>
                    <p>Top 6 shërbimet në periudhën e zgjedhur.</p>
                </div>
            </div>

            <?php if ($services): ?>
                <div class="ranking-list">
                    <?php foreach ($services as $i => $service): ?>
                        <div class="ranking-row">
                            <span class="rank"><?= $i + 1 ?></span>

                            <span
                                class="ranking-name"
                                title="<?= h($service['service_name']) ?>"
                            >
                                <?= h($service['service_name']) ?>
                            </span>

                            <span class="ranking-meta">
                                <?= (int)$service['total'] ?> termine
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="muted-empty">
                    Renditja do të shfaqet kur të regjistrohen termine.
                </div>
            <?php endif; ?>
        </div>

    </section>

    <section class="panel">
        <div class="panel-head">
            <div>
                <h2>Performanca e stafit</h2>
                <p>Terminet dhe të ardhurat e paguara që lidhen me secilin punonjës.</p>
            </div>

            <span class="status-tag"><?= count($staffRows) ?> punonjës</span>
        </div>

        <?php if ($staffRows): ?>
            <div class="table-wrap">
                <table id="staffReportTable">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>Punonjësi</th>
                        <th>Termine gjithsej</th>
                        <th>Të përfunduara</th>
                        <th>Të anuluara</th>
                        <th>Të ardhurat</th>
                        <th>Përqindja e përfundimit</th>
                    </tr>
                    </thead>

                    <tbody>
                    <?php foreach ($staffRows as $i => $staff): ?>
                        <?php
                        $completePercent = (int)$staff['appointments'] > 0
                            ? round(
                                ((int)$staff['completed'] /
                                (int)$staff['appointments']) * 100
                            )
                            : 0;
                        ?>

                        <tr>
                            <td><?= $i + 1 ?></td>

                            <td>
                                <div class="staff-name">
                                    <?= h(trim($staff['emri'] . ' ' . $staff['mbiemri'])) ?>
                                </div>
                                <div class="small-muted">
                                    ID #<?= (int)$staff['staf_id'] ?>
                                </div>
                            </td>

                            <td><?= (int)$staff['appointments'] ?></td>
                            <td><?= (int)$staff['completed'] ?></td>
                            <td><?= (int)$staff['cancelled'] ?></td>
                            <td><?= euro($staff['income'] ?? 0) ?></td>
                            <td><?= $completePercent ?>%</td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                Nuk u gjet staf për t'u analizuar.
            </div>
        <?php endif; ?>
    </section>

    <section class="panel">
        <div class="panel-head">
            <div>
                <h2>Përmbledhja e pagesave</h2>
                <p>Gjendja e pagesave gjatë periudhës së zgjedhur.</p>
            </div>
        </div>

        <div class="status-list">
            <?php
            $paymentTotal = $stats['paid_count']
                + $stats['pending_payments']
                + $stats['refunded_payments'];

            $paidPercent = $paymentTotal > 0
                ? ($stats['paid_count'] / $paymentTotal) * 100
                : 0;

            $pendingPercent = $paymentTotal > 0
                ? ($stats['pending_payments'] / $paymentTotal) * 100
                : 0;

            $refundedPercent = $paymentTotal > 0
                ? ($stats['refunded_payments'] / $paymentTotal) * 100
                : 0;
            ?>

            <div class="status-row">
                <span class="status-name">Pagesa të kryera</span>
                <div class="progress">
                    <div class="progress-fill" style="width:<?= $paidPercent ?>%"></div>
                </div>
                <span class="status-count"><?= (int)$stats['paid_count'] ?></span>
            </div>

            <div class="status-row">
                <span class="status-name">Në pritje</span>
                <div class="progress">
                    <div
                        class="progress-fill"
                        style="width:<?= $pendingPercent ?>%;background:#b9aa98"
                    ></div>
                </div>
                <span class="status-count"><?= (int)$stats['pending_payments'] ?></span>
            </div>

            <div class="status-row">
                <span class="status-name">Të rimbursuara</span>
                <div class="progress">
                    <div
                        class="progress-fill"
                        style="width:<?= $refundedPercent ?>%;background:#c9b6a2"
                    ></div>
                </div>
                <span class="status-count"><?= (int)$stats['refunded_payments'] ?></span>
            </div>
        </div>

        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:16px">
            <span class="status-tag">
                Regjistrime shpenzimesh: <?= (int)$stats['expense_count'] ?>
            </span>
            <span class="status-tag">
                Intervali: <?= h($periodLabel) ?>
            </span>
        </div>
    </section>

    <footer class="footer">
        <span>LUMIÈRE Beauty Studio</span>
        <span>Beauty is in every detail.</span>
    </footer>
</main>
</div>

<script>
(function () {
    const search = document.getElementById('reportQuickSearch');

    if (!search) {
        return;
    }

    search.addEventListener('input', function () {
        const q = this.value.trim().toLocaleLowerCase('sq');

        document.querySelectorAll(
            '.ranking-row, #staffReportTable tbody tr, .status-row, .legend-row'
        ).forEach(function (row) {
            row.style.display =
                !q || row.textContent.toLocaleLowerCase('sq').includes(q)
                    ? ''
                    : 'none';
        });
    });
})();
</script>

</body>
</html>