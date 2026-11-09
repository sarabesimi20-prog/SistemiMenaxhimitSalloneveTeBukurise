
<?php

session_start();

require_once __DIR__ . '/../config/database.php';

if (!isset($pdo) || !$pdo instanceof PDO) {
    die('Gabim në lidhjen me databazën.');
}

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if (empty($_SESSION['booking_csrf'])) {
    $_SESSION['booking_csrf'] = bin2hex(random_bytes(32));
}

$errors = [];
$success = null;

$form = [
    'sherbim_id' => $_GET['sherbim_id'] ?? '',
    'staf_id' => '',
    'data' => '',
    'ora' => '',
    'emri' => '',
    'mbiemri' => '',
    'telefoni' => '',
    'email' => ''
];

try {
    $servicesStmt = $pdo->query("
        SELECT sherbim_id, emri, cmimi, kohezgjatja_min
        FROM sherbimet
        ORDER BY emri ASC
    ");
    $services = $servicesStmt->fetchAll(PDO::FETCH_ASSOC);

    $staffStmt = $pdo->query("
        SELECT staf_id, emri, mbiemri, pozita
        FROM stafi
        WHERE statusi = 'Aktive'
        ORDER BY emri ASC
    ");
    $staff = $staffStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die('Nuk u arritën të ngarkohen shërbimet dhe stafi.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($form as $key => $value) {
        $form[$key] = trim((string)($_POST[$key] ?? ''));
    }

    $csrf = (string)($_POST['csrf_token'] ?? '');

    if (!hash_equals($_SESSION['booking_csrf'], $csrf)) {
        $errors[] = 'Sesioni ka skaduar. Rifresko faqen dhe provo përsëri.';
    }

    $serviceId = filter_var($form['sherbim_id'], FILTER_VALIDATE_INT);
    $staffId = filter_var($form['staf_id'], FILTER_VALIDATE_INT);

    if (!$serviceId) {
        $errors[] = 'Zgjedhe shërbimin që dëshiron.';
    }

    if (!$staffId) {
        $errors[] = 'Zgjedhe punonjësen.';
    }

    if ($form['emri'] === '' || $form['mbiemri'] === '') {
        $errors[] = 'Emri dhe mbiemri janë të detyrueshëm.';
    }

    if ($form['telefoni'] === '') {
        $errors[] = 'Shëno numrin e telefonit.';
    }

    if ($form['email'] !== '' && !filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Adresa e emailit nuk është valide.';
    }

    if (!preg_match('/^[0-9+\s()\-]{7,20}$/', $form['telefoni'])) {
        $errors[] = 'Numri i telefonit nuk është në format të vlefshëm.';
    }

    $dateObject = DateTime::createFromFormat('Y-m-d', $form['data']);
    if (!$dateObject || $dateObject->format('Y-m-d') !== $form['data']) {
        $errors[] = 'Zgjedhe një datë valide.';
    } elseif ($form['data'] < date('Y-m-d')) {
        $errors[] = 'Nuk mund të rezervosh në një datë që ka kaluar.';
    }

    if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $form['ora'])) {
        $errors[] = 'Zgjedhe një orë valide.';
    }

    if (!$errors) {
        try {
            $serviceStmt = $pdo->prepare("
                SELECT sherbim_id, emri, cmimi, kohezgjatja_min
                FROM sherbimet
                WHERE sherbim_id = :id
                LIMIT 1
            ");
            $serviceStmt->execute(['id' => $serviceId]);
            $selectedService = $serviceStmt->fetch(PDO::FETCH_ASSOC);

            $staffCheck = $pdo->prepare("
                SELECT staf_id, emri, mbiemri
                FROM stafi
                WHERE staf_id = :id AND statusi = 'Aktive'
                LIMIT 1
            ");
            $staffCheck->execute(['id' => $staffId]);
            $selectedStaff = $staffCheck->fetch(PDO::FETCH_ASSOC);

            if (!$selectedService) {
                $errors[] = 'Shërbimi i zgjedhur nuk ekziston.';
            }

            if (!$selectedStaff) {
                $errors[] = 'Punonjësja e zgjedhur nuk është e disponueshme.';
            }

            if (!$errors) {
                $duration = max((int)$selectedService['kohezgjatja_min'], 1);
                $startDateTime = new DateTime($form['data'] . ' ' . $form['ora']);
                $endDateTime = clone $startDateTime;
                $endDateTime->modify('+' . $duration . ' minutes');

                $openingTime = new DateTime($form['data'] . ' 09:00');
                $closingTime = new DateTime($form['data'] . ' 17:00');

                if ($startDateTime < $openingTime || $endDateTime > $closingTime) {
                    $errors[] = 'Rezervimet pranohen nga ora 09:00 deri në 17:00. Ki parasysh edhe kohëzgjatjen e shërbimit.';
                }

                if ($form['data'] === date('Y-m-d') && $startDateTime <= new DateTime()) {
                    $errors[] = 'Ora e zgjedhur ka kaluar. Zgjedhe një orë tjetër.';
                }

                if (!$errors) {
                    $overlapStmt = $pdo->prepare("
                        SELECT COUNT(*)
                        FROM terminet t
                        INNER JOIN sherbimet s ON s.sherbim_id = t.sherbim_id
                        WHERE t.staf_id = :staff_id
                          AND t.data = :date
                          AND LOWER(TRIM(COALESCE(t.statusi, ''))) NOT IN
                              ('anuluar', 'anuluar nga klienti', 'cancelled', 'canceled')
                          AND t.ora < :end_time
                          AND ADDTIME(
                              t.ora,
                              SEC_TO_TIME(GREATEST(COALESCE(s.kohezgjatja_min, 30), 1) * 60)
                          ) > :start_time
                    ");

                    $overlapStmt->execute([
                        'staff_id' => $staffId,
                        'date' => $form['data'],
                        'end_time' => $endDateTime->format('H:i:s'),
                        'start_time' => $startDateTime->format('H:i:s')
                    ]);

                    if ((int)$overlapStmt->fetchColumn() > 0) {
                        $errors[] = 'Kjo punonjëse ka një termin tjetër në atë orar. Zgjedhe një orë tjetër.';
                    }
                }
            }

            if (!$errors) {
                $pdo->beginTransaction();

                $email = strtolower($form['email']);
                $phone = $form['telefoni'];

                $clientByEmail = null;
                $clientByPhone = null;

                if ($email !== '') {
                    $emailStmt = $pdo->prepare("
                        SELECT klient_id
                        FROM klientet
                        WHERE LOWER(email) = :email
                        LIMIT 1
                    ");
                    $emailStmt->execute(['email' => $email]);
                    $clientByEmail = $emailStmt->fetchColumn() ?: null;
                }

                $phoneStmt = $pdo->prepare("
                    SELECT klient_id
                    FROM klientet
                    WHERE telefoni = :phone
                    LIMIT 1
                ");
                $phoneStmt->execute(['phone' => $phone]);
                $clientByPhone = $phoneStmt->fetchColumn() ?: null;

                if ($clientByEmail && $clientByPhone && (int)$clientByEmail !== (int)$clientByPhone) {
                    throw new RuntimeException('Emaili dhe numri i telefonit janë të lidhur me klientë të ndryshëm.');
                }

                $clientId = $clientByEmail ?: $clientByPhone;

                if ($clientId) {
                    $updateClient = $pdo->prepare("
                        UPDATE klientet
                        SET emri = :emri,
                            mbiemri = :mbiemri,
                            telefoni = :telefoni,
                            email = :email
                        WHERE klient_id = :id
                    ");
                    $updateClient->execute([
                        'emri' => $form['emri'],
                        'mbiemri' => $form['mbiemri'],
                        'telefoni' => $phone,
                        'email' => $email !== '' ? $email : null,
                        'id' => $clientId
                    ]);
                } else {
                    $insertClient = $pdo->prepare("
                        INSERT INTO klientet (emri, mbiemri, telefoni, email)
                        VALUES (:emri, :mbiemri, :telefoni, :email)
                    ");
                    $insertClient->execute([
                        'emri' => $form['emri'],
                        'mbiemri' => $form['mbiemri'],
                        'telefoni' => $phone,
                        'email' => $email !== '' ? $email : null
                    ]);

                    $clientId = (int)$pdo->lastInsertId();
                }

                $insertAppointment = $pdo->prepare("
                    INSERT INTO terminet
                        (klient_id, staf_id, sherbim_id, data, ora, statusi)
                    VALUES
                        (:klient_id, :staf_id, :sherbim_id, :data, :ora, 'Ne pritje')
                ");

                $insertAppointment->execute([
                    'klient_id' => $clientId,
                    'staf_id' => $staffId,
                    'sherbim_id' => $serviceId,
                    'data' => $form['data'],
                    'ora' => $form['ora']
                ]);

                $appointmentId = (int)$pdo->lastInsertId();

                $pdo->commit();

                $success = [
                    'id' => $appointmentId,
                    'service' => $selectedService['emri'],
                    'staff' => $selectedStaff['emri'] . ' ' . $selectedStaff['mbiemri'],
                    'date' => $form['data'],
                    'time' => $form['ora'],
                    'price' => $selectedService['cmimi'],
                    'duration' => $duration
                ];

                $_SESSION['booking_csrf'] = bin2hex(random_bytes(32));

                $form = [
                    'sherbim_id' => '',
                    'staf_id' => '',
                    'data' => '',
                    'ora' => '',
                    'emri' => '',
                    'mbiemri' => '',
                    'telefoni' => '',
                    'email' => ''
                ];
            }
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            if ($e instanceof RuntimeException) {
                $errors[] = $e->getMessage();
            } else {
                $errors[] = 'Rezervimi nuk u realizua. Ju lutem provoni përsëri.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rezervo Termin | LUMIÈRE Beauty Studio</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/booking.css">
</head>
<body>

<?php include __DIR__ . '/includes/header.php'; ?>

<main class="booking-page">
    <section class="booking-layout">

        <aside class="booking-intro">
            <div class="intro-top">
                <span class="intro-eyebrow">LUMIÈRE BEAUTY STUDIO</span>
                <span class="intro-line"></span>
            </div>

            <div class="intro-content">
                <span class="intro-small">Momenti yt, kujdesi ynë</span>
                <h1>Rezervo<br>momentin tënd<br><em>të bukurisë.</em></h1>
                <p>
                    Një përvojë e veçantë kujdesi fillon me një rezervim të thjeshtë.
                    Zgjedhe shërbimin dhe kohën që të përshtatet.
                </p>

                <div class="intro-details">
                    <div>
                        <span class="detail-label">ORARI I PUNËS</span>
                        <strong>09:00 – 17:00</strong>
                    </div>
                    <div>
                        <span class="detail-label">REZERVIMET</span>
                        <strong>Çdo ditë pune</strong>
                    </div>
                </div>
            </div>

            <div class="intro-bottom">
                <span>BEAUTY · CARE · CONFIDENCE</span>
                <span>01 / LUMIÈRE</span>
            </div>
        </aside>

        <section class="booking-form-area">
            <div class="form-heading">
                <div>
                    <span class="section-kicker">TA PLANIFIKOJMË VIZITËN</span>
                    <h2>Rezervo termin</h2>
                    <p>Plotëso të dhënat më poshtë për të kërkuar rezervimin.</p>
                </div>
                <div class="form-number">01</div>
            </div>

            <?php if ($errors): ?>
                <div class="booking-alert error-alert">
                    <strong>Kontrollo të dhënat e formularit</strong>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="booking-alert success-alert">
                    <div class="success-icon">✓</div>
                    <div>
                        <h3>Rezervimi u regjistrua!</h3>
                        <p>Numri i rezervimit: <strong>#<?= (int)$success['id'] ?></strong></p>
                        <div class="success-details">
                            <span><?= htmlspecialchars($success['service']) ?></span>
                            <span><?= htmlspecialchars($success['staff']) ?></span>
                            <span><?= date('d.m.Y', strtotime($success['date'])) ?> · <?= htmlspecialchars(substr($success['time'], 0, 5)) ?></span>
                            <span><?= number_format((float)$success['price'], 2) ?> € · <?= (int)$success['duration'] ?> min</span>
                        </div>
                        <p class="success-note">Statusi fillestar i rezervimit është “Në pritje”.</p>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="booking-form" id="bookingForm">
                <input type="hidden" name="csrf_token"
                       value="<?= htmlspecialchars($_SESSION['booking_csrf']) ?>">

                <div class="form-section-title">
                    <span>01</span>
                    <h3>Detajet e rezervimit</h3>
                </div>

                <div class="form-grid">
                    <div class="field field-full">
                        <label for="sherbim_id">Shërbimi <span>*</span></label>
                        <select name="sherbim_id" id="sherbim_id" required>
                            <option value="">Zgjedh shërbimin</option>
                            <?php foreach ($services as $service): ?>
                                <option
                                    value="<?= (int)$service['sherbim_id'] ?>"
                                    data-price="<?= htmlspecialchars((string)$service['cmimi']) ?>"
                                    data-duration="<?= (int)$service['kohezgjatja_min'] ?>"
                                    <?= (string)$form['sherbim_id'] === (string)$service['sherbim_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($service['emri']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="field">
                        <label for="staf_id">Punonjësja <span>*</span></label>
                        <select name="staf_id" id="staf_id" required>
                            <option value="">Zgjedh punonjësen</option>
                            <?php foreach ($staff as $person): ?>
                                <option value="<?= (int)$person['staf_id'] ?>"
                                    <?= (string)$form['staf_id'] === (string)$person['staf_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($person['emri'] . ' ' . $person['mbiemri']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="field">
                        <label for="data">Data <span>*</span></label>
                        <input type="date" name="data" id="data"
                               min="<?= date('Y-m-d') ?>"
                               value="<?= htmlspecialchars($form['data']) ?>" required>
                    </div>

                    <div class="field">
                        <label for="ora">Ora <span>*</span></label>
                        <input type="time" name="ora" id="ora"
                               min="09:00" max="17:00" step="900"
                               value="<?= htmlspecialchars($form['ora']) ?>" required>
                        <small>Orari i rezervimeve: 09:00–17:00</small>
                    </div>
                </div>

                <div class="form-section-title customer-title">
                    <span>02</span>
                    <h3>Të dhënat e klientit</h3>
                </div>

                <div class="form-grid">
                    <div class="field">
                        <label for="emri">Emri <span>*</span></label>
                        <input type="text" name="emri" id="emri"
                               placeholder="Emri yt"
                               value="<?= htmlspecialchars($form['emri']) ?>" required>
                    </div>

                    <div class="field">
                        <label for="mbiemri">Mbiemri <span>*</span></label>
                        <input type="text" name="mbiemri" id="mbiemri"
                               placeholder="Mbiemri yt"
                               value="<?= htmlspecialchars($form['mbiemri']) ?>" required>
                    </div>

                    <div class="field">
                        <label for="telefoni">Numri i telefonit <span>*</span></label>
                        <input type="tel" name="telefoni" id="telefoni"
                               placeholder="+383 4X XXX XXX"
                               value="<?= htmlspecialchars($form['telefoni']) ?>" required>
                    </div>

                    <div class="field">
                        <label for="email">Email</label>
                        <input type="email" name="email" id="email"
                               placeholder="email@example.com"
                               value="<?= htmlspecialchars($form['email']) ?>">
                    </div>
                </div>

                <div class="booking-summary">
                    <div class="summary-heading">
                        <span>PËRMBLEDHJA</span>
                        <span class="summary-status">REZERVIM ONLINE</span>
                    </div>
                    <div class="summary-content">
                        <div>
                            <small>Shërbimi</small>
                            <strong id="summaryService">Nuk është zgjedhur</strong>
                        </div>
                        <div>
                            <small>Kohëzgjatja</small>
                            <strong id="summaryDuration">—</strong>
                        </div>
                        <div>
                            <small>Çmimi</small>
                            <strong id="summaryPrice">—</strong>
                        </div>
                    </div>
                </div>

                <button type="submit" class="booking-submit">
                    <span>Dërgo kërkesën për rezervim</span>
                    <span class="submit-arrow">→</span>
                </button>

                <p class="form-footnote">
                    Rezervimi juaj regjistrohet me statusin “Në pritje”.
                </p>
            </form>
        </section>
    </section>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const serviceSelect = document.getElementById('sherbim_id');
    const summaryService = document.getElementById('summaryService');
    const summaryDuration = document.getElementById('summaryDuration');
    const summaryPrice = document.getElementById('summaryPrice');

    function updateSummary() {
        const option = serviceSelect.options[serviceSelect.selectedIndex];

        if (!option || !option.value) {
            summaryService.textContent = 'Nuk është zgjedhur';
            summaryDuration.textContent = '—';
            summaryPrice.textContent = '—';
            return;
        }

        const price = Number(option.dataset.price || 0);
        const duration = Number(option.dataset.duration || 0);

        summaryService.textContent = option.textContent.trim();
        summaryDuration.textContent = duration + ' min';
        summaryPrice.textContent = price.toFixed(2) + ' €';
    }

    serviceSelect.addEventListener('change', updateSummary);
    updateSummary();
});
</script>

</body>
</html>
