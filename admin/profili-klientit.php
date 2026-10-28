
<?php
require_once __DIR__ . '/../config/database.php';

function h($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function euro($value): string {
    return number_format((float) $value, 2, ',', '.') . ' €';
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header('Location: klientet.php');
    exit;
}

$stmt = $pdo->prepare(
    'SELECT klient_id, emri, mbiemri, telefoni, email, adresa,
            data_regjistrimit
     FROM klientet
     WHERE klient_id = ?'
);
$stmt->execute([$id]);
$klienti = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$klienti) {
    http_response_code(404);
    exit('Klienti nuk u gjet. <a href="klientet.php">Kthehu</a>');
}

$initialet = mb_strtoupper(
    mb_substr($klienti['emri'], 0, 1) .
    mb_substr($klienti['mbiemri'], 0, 1)
);

$mesazhi = '';
$gabimi = '';

// ruatja e shenimeve
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shenime = trim((string) ($_POST['shenime'] ?? ''));

    if (mb_strlen($shenime) > 5000) {
        $gabimi = 'Shënimet nuk mund të kalojnë 5000 karaktere.';
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO klient_shenime (klient_id, shenime)
             VALUES (?, ?)
             ON DUPLICATE KEY UPDATE shenime = VALUES(shenime)'
        );
        $stmt->execute([$id, $shenime]);

        header('Location: profili-klientit.php?id=' . $id . '&ruajtur=1');
        exit;
    }
}

if (isset($_GET['ruajtur'])) {
    $mesazhi = 'Shënimet u ruajtën me sukses.';
}

$stmt = $pdo->prepare(
    'SELECT shenime FROM klient_shenime WHERE klient_id = ?'
);
$stmt->execute([$id]);
$shenimet = $stmt->fetchColumn();
$shenimet = $shenimet === false ? '' : $shenimet;

// historiku i termineve
$stmt = $pdo->prepare(
    "SELECT t.termin_id, t.data, t.ora, t.statusi,
            s.emri AS sherbimi, s.cmimi,
            st.emri AS staf_emri, st.mbiemri AS staf_mbiemri
     FROM terminet t
     LEFT JOIN sherbimet s ON s.sherbim_id = t.sherbim_id
     LEFT JOIN stafi st ON st.staf_id = t.staf_id
     WHERE t.klient_id = ?
     ORDER BY t.data DESC, t.ora DESC"
);
$stmt->execute([$id]);
$terminet = $stmt->fetchAll(PDO::FETCH_ASSOC);

// statistikat
$totalTermine = count($terminet);
$perfunduar = 0;

foreach ($terminet as $termin) {
    if (mb_strtolower(trim((string) $termin['statusi'])) === 'përfunduar') {
        $perfunduar++;
    }
}

// Pagesat e lidhura me terminet e klientes
$stmt = $pdo->prepare(
    "SELECT p.pagesa_id, p.termin_id, p.shuma,
            p.menyra_pageses, p.statusi, p.data_pageses, p.shenime,
            t.data AS data_terminit,
            s.emri AS sherbimi
     FROM pagesa p
     INNER JOIN terminet t ON t.termin_id = p.termin_id
     LEFT JOIN sherbimet s ON s.sherbim_id = t.sherbim_id
     WHERE t.klient_id = ?
     ORDER BY p.data_pageses DESC, p.krijuar_me DESC"
);
$stmt->execute([$id]);
$pagesat = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalPaguar = 0;

foreach ($pagesat as $pagesa) {
    if ($pagesa['statusi'] === 'Paguar') {
        $totalPaguar += (float) $pagesa['shuma'];
    }
}
?>
<!DOCTYPE html>
<html lang="sq">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Profili i klientes | LUMIÈRE Beauty Studio</title>
<style>
* { box-sizing: border-box; }

body {
    margin: 0;
    padding: 32px 22px;
    background: #f7f4ef;
    color: #302820;
    font-family: "Segoe UI", Arial, sans-serif;
}

.container { max-width: 1180px; margin: auto; }

a { color: inherit; }

.back {
    display: inline-block;
    margin-bottom: 22px;
    color: #756454;
    text-decoration: none;
    font-size: 13px;
}

.card {
    background: #fffdf9;
    border: 1px solid #e9e0d5;
    border-radius: 10px;
    padding: 26px;
    margin-bottom: 22px;
}

.heading {
    display: flex;
    align-items: center;
    gap: 20px;
}

.avatar {
    width: 76px;
    height: 76px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    border-radius: 50%;
    background: #eee4d8;
    font: 26px Georgia, serif;
}

.eyebrow {
    color: #9b8067;
    font-size: 11px;
    letter-spacing: 2px;
    text-transform: uppercase;
}

h1 {
    margin: 7px 0;
    font: 34px Georgia, serif;
}

h2 {
    margin: 0 0 8px;
    font: 25px Georgia, serif;
}

.subtitle {
    color: #8b7c6c;
    font-size: 13px;
}

.id { color: #8b7c6c; font-size: 12px; }

.grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 16px;
    margin-bottom: 22px;
}

.stat {
    padding: 22px;
    background: #fffdf9;
    border: 1px solid #e9e0d5;
    border-radius: 9px;
}

.stat-label {
    color: #8b7c6c;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.stat-value {
    margin-top: 12px;
    font: 30px Georgia, serif;
}

.details {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 15px;
    margin-top: 24px;
}

.detail {
    padding: 17px;
    border: 1px solid #eee5da;
    border-radius: 7px;
    min-width: 0;
}

.label {
    margin-bottom: 9px;
    color: #8b7c6c;
    font-size: 10px;
    letter-spacing: 1px;
    text-transform: uppercase;
}

.value {
    font-size: 14px;
    overflow-wrap: anywhere;
}

.section-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 18px;
}

.count {
    color: #8b7c6c;
    font-size: 12px;
}

.table-wrap { overflow-x: auto; }

table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
    min-width: 650px;
}

th {
    padding: 14px 12px;
    background: #eee5da;
    text-align: left;
    font-size: 10px;
    font-weight: 600;
    letter-spacing: .7px;
    text-transform: uppercase;
}

td {
    padding: 14px 12px;
    border-bottom: 1px solid #eee5da;
}

tr:last-child td { border-bottom: none; }

.empty {
    padding: 24px;
    text-align: center;
    color: #8b7c6c;
    background: #faf7f2;
    border-radius: 6px;
    font-size: 13px;
}

.status {
    display: inline-block;
    padding: 5px 9px;
    background: #eee5da;
    border-radius: 20px;
    font-size: 11px;
}

textarea {
    width: 100%;
    min-height: 135px;
    resize: vertical;
    padding: 14px;
    border: 1px solid #e3d8ca;
    border-radius: 6px;
    background: #fffdf9;
    font: inherit;
    font-size: 13px;
    color: #302820;
}

.btn {
    display: inline-block;
    padding: 12px 18px;
    border: none;
    border-radius: 5px;
    background: #302820;
    color: white;
    text-decoration: none;
    font-size: 12px;
    cursor: pointer;
}

.notice {
    padding: 13px 16px;
    margin-bottom: 18px;
    background: #edf4e9;
    border: 1px solid #d7e6cf;
    color: #45603c;
    border-radius: 5px;
    font-size: 13px;
}

.error {
    padding: 13px 16px;
    margin-bottom: 18px;
    background: #fff0ee;
    border: 1px solid #f1d1ca;
    color: #9a3930;
    border-radius: 5px;
    font-size: 13px;
}

.footer {
    padding: 12px 0;
    color: #8b7c6c;
    font: italic 13px Georgia, serif;
}

@media (max-width: 760px) {
    body { padding: 18px 12px; }
    .card { padding: 19px; }
    .grid { grid-template-columns: 1fr; }
    .details { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    h1 { font-size: 27px; }
}

@media (max-width: 480px) {
    .details { grid-template-columns: 1fr; }
    .heading { align-items: flex-start; }
    .avatar { width: 58px; height: 58px; font-size: 21px; }
}
</style>
</head>
<body>
<div class="container">

<a class="back" href="klientet.php">← Kthehu te lista e klienteve</a>

<?php if ($mesazhi): ?>
    <div class="notice"><?= h($mesazhi) ?></div>
<?php endif; ?>

<?php if ($gabimi): ?>
    <div class="error"><?= h($gabimi) ?></div>
<?php endif; ?>

<section class="card">
    <div class="heading">
        <div class="avatar"><?= h($initialet) ?></div>
        <div>
            <div class="eyebrow">LUMIÈRE Beauty Studio · Client Profile</div>
            <h1><?= h($klienti['emri'] . ' ' . $klienti['mbiemri']) ?></h1>
            <div class="id">Numri i klientes: #<?= (int) $klienti['klient_id'] ?></div>
        </div>
    </div>

    <div class="details">
        <div class="detail">
            <div class="label">Emri</div>
            <div class="value"><?= h($klienti['emri']) ?></div>
        </div>
        <div class="detail">
            <div class="label">Mbiemri</div>
            <div class="value"><?= h($klienti['mbiemri']) ?></div>
        </div>
        <div class="detail">
            <div class="label">Telefoni</div>
            <div class="value"><?= h($klienti['telefoni'] ?: 'Nuk është shënuar') ?></div>
        </div>
        <div class="detail">
            <div class="label">Email</div>
            <div class="value"><?= h($klienti['email'] ?: 'Nuk është shënuar') ?></div>
        </div>
        <div class="detail">
            <div class="label">Adresa</div>
            <div class="value"><?= h($klienti['adresa'] ?: 'Nuk është shënuar') ?></div>
        </div>
        <div class="detail">
            <div class="label">Data e regjistrimit</div>
            <div class="value">
                <?= !empty($klienti['data_regjistrimit'])
                    ? h(date('d.m.Y', strtotime($klienti['data_regjistrimit'])))
                    : 'Nuk është shënuar' ?>
            </div>
        </div>
    </div>

    <div style="margin-top:22px">
        <a class="btn"
           href="klientet.php?edit=<?= (int) $id ?>#formulari">
            Ndrysho të dhënat
        </a>
    </div>
</section>

<div class="grid">
    <div class="stat">
        <div class="stat-label">Gjithsej termine</div>
        <div class="stat-value"><?= $totalTermine ?></div>
    </div>
    <div class="stat">
        <div class="stat-label">Termine të përfunduara</div>
        <div class="stat-value"><?= $perfunduar ?></div>
    </div>
    <div class="stat">
        <div class="stat-label">Totali i paguar</div>
        <div class="stat-value"><?= h(euro($totalPaguar)) ?></div>
    </div>
</div>

<section class="card">
    <div class="section-head">
        <div>
            <h2>Historiku i termineve</h2>
            <div class="subtitle">Terminet e regjistruara për këtë kliente.</div>
        </div>
        <span class="count"><?= $totalTermine ?> termine</span>
    </div>

    <?php if (!$terminet): ?>
        <div class="empty">Kjo kliente nuk ka ende termine të regjistruara.</div>
    <?php else: ?>
        <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>Data</th>
                <th>Ora</th>
                <th>Shërbimi</th>
                <th>Punonjësja</th>
                <th>Çmimi</th>
                <th>Statusi</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($terminet as $t): ?>
            <tr>
                <td><?= h(date('d.m.Y', strtotime($t['data']))) ?></td>
                <td><?= h(substr((string) $t['ora'], 0, 5)) ?></td>
                <td><?= h($t['sherbimi'] ?? 'Nuk dihet') ?></td>
                <td>
                    <?= h(trim(($t['staf_emri'] ?? '') . ' ' .
                        ($t['staf_mbiemri'] ?? '')) ?: 'Nuk është caktuar') ?>
                </td>
                <td><?= $t['cmimi'] !== null ? h(euro($t['cmimi'])) : '—' ?></td>
                <td><span class="status"><?= h($t['statusi'] ?: 'Ne pritje') ?></span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    <?php endif; ?>
</section>

<section class="card">
    <div class="section-head">
        <div>
            <h2>Historiku i pagesave</h2>
            <div class="subtitle">Pagesat e lidhura me terminet e kësaj klienteje.</div>
        </div>
        <span class="count"><?= count($pagesat) ?> pagesa</span>
    </div>

    <?php if (!$pagesat): ?>
        <div class="empty">
            Nuk ka ende pagesa të regjistruara për këtë kliente.
        </div>
    <?php else: ?>
        <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>Data e pagesës</th>
                <th>Shërbimi</th>
                <th>Shuma</th>
                <th>Mënyra</th>
                <th>Statusi</th>
                <th>Shënime</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($pagesat as $p): ?>
            <tr>
                <td>
                    <?= !empty($p['data_pageses'])
                        ? h(date('d.m.Y', strtotime($p['data_pageses'])))
                        : 'Pa datë' ?>
                </td>
                <td><?= h($p['sherbimi'] ?? 'Nuk dihet') ?></td>
                <td><?= h(euro($p['shuma'])) ?></td>
                <td><?= h($p['menyra_pageses']) ?></td>
                <td><span class="status"><?= h($p['statusi']) ?></span></td>
                <td><?= h($p['shenime'] ?: '—') ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    <?php endif; ?>
</section>

<section class="card">
    <div class="section-head">
        <div>
            <h2>Shënime për klienten</h2>
            <div class="subtitle">
                Ruaj preferencat dhe kërkesat e veçanta për vizitat e ardhshme.
            </div>
        </div>
    </div>

    <form method="post"
          action="profili-klientit.php?id=<?= (int) $id ?>"
          accept-charset="UTF-8">
        <textarea name="shenime" maxlength="5000"
                  placeholder="Shkruaj shënimet për këtë kliente..."><?= h($shenimet) ?></textarea>
        <div style="margin-top:15px">
            <button class="btn" type="submit">Ruaj shënimet</button>
        </div>
    </form>
</section>

<div class="footer">LUMIÈRE Beauty Studio · Beauty is in every detail.</div>

</div>
</body>
</html>
