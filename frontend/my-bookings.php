
<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($pdo) || !$pdo instanceof PDO) {
    die('Gabim në lidhjen me databazën.');
}

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if (empty($_SESSION['my_bookings_csrf'])) {
    $_SESSION['my_bookings_csrf'] = bin2hex(random_bytes(32));
}

// funksion per sigurine e tekstit html .
function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// Normalizimi i statuseve.
function normalizeBookingStatus(string $status): string
{
    $status = trim($status);
    $status = strtr($status, ['Ë' => 'ë']);

    if (function_exists('mb_strtolower')) {
        return mb_strtolower($status, 'UTF-8');
    }

    return strtolower($status);
}

$phone = trim((string)($_POST['telefoni'] ?? $_GET['telefoni'] ?? ''));
$errors = [];
$message = '';
$bookings = [];

// perpunimi i formularit.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = (string)($_POST['csrf_token'] ?? '');

    if (!hash_equals($_SESSION['my_bookings_csrf'], $csrf)) {
        $errors[] = 'Sesioni ka skaduar. Rifresko faqen dhe provo përsëri.';
    } else {
        $action = (string)($_POST['action'] ?? '');

        if ($action === 'cancel') {
            $appointmentId = filter_var(
                $_POST['termin_id'] ?? null,
                FILTER_VALIDATE_INT
            );

            if (!$appointmentId || $phone === '') {
                $errors[] = 'Të dhënat për anulim nuk janë të vlefshme.';
            } else {
                try {
                    // Kontrollojmë nëse rezervimi i përket klientit.
                    $check = $pdo->prepare("
                        SELECT t.termin_id, t.data, t.ora, t.statusi
                        FROM terminet t
                        INNER JOIN klientet k ON k.klient_id = t.klient_id
                        WHERE t.termin_id = :termin_id
                          AND k.telefoni = :phone
                        LIMIT 1
                    ");

                    $check->execute([
                        'termin_id' => $appointmentId,
                        'phone' => $phone
                    ]);

                    $appointment = $check->fetch(PDO::FETCH_ASSOC);

                    if (!$appointment) {
                        $errors[] = 'Rezervimi nuk u gjet për këtë numër telefoni.';
                    } else {
                        $currentStatus = normalizeBookingStatus(
                            (string)$appointment['statusi']
                        );

                        $cancelledStatuses = [
                            'anuluar',
                            'anuluar nga klienti',
                            'cancelled',
                            'canceled'
                        ];

                        $completedStatuses = [
                            'përfunduar',
                            'perfunduar',
                            'completed',
                            'finished'
                        ];

                        if (in_array($currentStatus, $cancelledStatuses, true)) {
                            $errors[] = 'Ky rezervim është anuluar më parë.';
                        } elseif (in_array($currentStatus, $completedStatuses, true)) {
                            $errors[] = 'Nuk mund të anulohet një termin që është përfunduar.';
                        } elseif (
                            strtotime($appointment['data'] . ' ' . $appointment['ora']) <= time()
                        ) {
                            $errors[] = 'Nuk mund të anulohet një termin që ka kaluar.';
                        } else {
                            // Anulimi i rezervimit.
                            $cancel = $pdo->prepare("
                                UPDATE terminet
                                SET statusi = 'Anuluar'
                                WHERE termin_id = :termin_id
                                  AND LOWER(TRIM(statusi)) NOT IN (
                                      'anuluar',
                                      'anuluar nga klienti',
                                      'cancelled',
                                      'canceled',
                                      'perfunduar',
                                      'completed',
                                      'finished'
                                  )
                            ");

                            $cancel->execute([
                                'termin_id' => $appointmentId
                            ]);

                            if ($cancel->rowCount() > 0) {
                                $message = 'Rezervimi u anulua me sukses.';
                            } else {
                                $errors[] = 'Rezervimi nuk mund të anulohet. Rifresko faqen dhe kontrollo statusin.';
                            }
                        }
                    }
                } catch (PDOException $e) {
                    $errors[] = 'Ndodhi një gabim gjatë anulimit të rezervimit.';
                }
            }
        }
    }

    
    $_SESSION['my_bookings_csrf'] = bin2hex(random_bytes(32));
}

// Ngarkimi i rezervimeve sipas numrit të telefonit.
if ($phone !== '' && !$errors) {
    try {
        $stmt = $pdo->prepare("
            SELECT
                t.termin_id,
                t.data,
                t.ora,
                t.statusi,
                s.emri AS sherbimi,
                s.cmimi,
                s.kohezgjatja_min,
                st.emri AS staf_emri,
                st.mbiemri AS staf_mbiemri
            FROM terminet t
            INNER JOIN klientet k ON k.klient_id = t.klient_id
            INNER JOIN sherbimet s ON s.sherbim_id = t.sherbim_id
            INNER JOIN stafi st ON st.staf_id = t.staf_id
            WHERE k.telefoni = :phone
            ORDER BY t.data DESC, t.ora DESC
        ");

        $stmt->execute(['phone' => $phone]);
        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        $errors[] = 'Nuk u arritën të ngarkohen rezervimet.';
    }
}
?>

<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Rezervimet e mia | LUMIÈRE Beauty Studio</title>

    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/my-bookings.css">
</head>

<body>

<?php include __DIR__ . '/includes/header.php'; ?>

<main class="my-bookings-page">

    <section class="my-bookings-hero">
        <span class="my-bookings-kicker">LUMIÈRE BEAUTY STUDIO</span>

        <h1>Rezervimet <em>e mia</em></h1>

        <p>
            Shiko rezervimet e tua, kontrollo statusin dhe menaxho vizitën
            tënde në LUMIÈRE.
        </p>
    </section>

    <section class="my-bookings-container">

        <div class="lookup-card">

            <div class="lookup-heading">
                <span class="lookup-number">01</span>

                <div>
                    <h2>Gjej rezervimet e tua</h2>
                    <p>Shëno numrin e telefonit që ke përdorur gjatë rezervimit.</p>
                </div>
            </div>

            <form method="POST" class="lookup-form">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= h($_SESSION['my_bookings_csrf']) ?>"
                >

                <input type="hidden" name="action" value="search">

                <div class="lookup-input">
                    <label for="telefoni">Numri i telefonit</label>

                    <input
                        type="tel"
                        id="telefoni"
                        name="telefoni"
                        placeholder="+383 4X XXX XXX"
                        value="<?= h($phone) ?>"
                        required
                    >
                </div>

                <button type="submit" class="lookup-button">
                    Kërko rezervimet <span>→</span>
                </button>

            </form>
        </div>

        <?php if ($message): ?>
            <div class="my-bookings-message success">
                <span>✓</span>
                <?= h($message) ?>
            </div>
        <?php endif; ?>

        <?php if ($errors): ?>
            <div class="my-bookings-message error">
                <strong>Vëmendje</strong>

                <?php foreach ($errors as $error): ?>
                    <p><?= h($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($phone !== '' && !$errors): ?>

            <div class="results-heading">
                <div>
                    <span class="lookup-number">02</span>
                    <h2>Rezervimet e gjetura</h2>
                </div>

                <span class="results-count">
                    <?= count($bookings) ?> rezervim(e)
                </span>
            </div>

            <?php if (!$bookings): ?>

                <div class="empty-bookings">
                    <span class="empty-icon">✧</span>

                    <h3>Nuk u gjet asnjë rezervim</h3>

                    <p>
                        Kontrollo numrin e telefonit ose krijo një rezervim të ri.
                    </p>

                    <a href="booking.php">Rezervo një termin</a>
                </div>

            <?php else: ?>

                <div class="booking-cards">

                    <?php foreach ($bookings as $booking): ?>

                        <?php
                            $status = trim((string)$booking['statusi']);
                            $statusLower = normalizeBookingStatus($status);

                            $isCancelled = in_array($statusLower, [
                                'anuluar',
                                'anuluar nga klienti',
                                'cancelled',
                                'canceled'
                            ], true);

                            $isCompleted = in_array($statusLower, [
                                'përfunduar',
                                'perfunduar',
                                'completed',
                                'finished'
                            ], true);

                            $isPast = strtotime(
                                $booking['data'] . ' ' . $booking['ora']
                            ) <= time();

                            if ($isCancelled) {
                                $statusClass = 'status-cancelled';
                            } elseif ($statusLower === 'konfirmuar') {
                                $statusClass = 'status-confirmed';
                            } elseif ($isCompleted) {
                                $statusClass = 'status-completed';
                            } else {
                                $statusClass = 'status-pending';
                            }

                            $canCancel = !$isCancelled && !$isCompleted && !$isPast;
                        ?>

                        <article class="customer-booking-card">

                            <div class="booking-card-top">

                                <span class="booking-reference">
                                    REZERVIM #<?= (int)$booking['termin_id'] ?>
                                </span>

                                <span class="booking-status <?= h($statusClass) ?>">
                                    <?= h($status) ?>
                                </span>

                            </div>

                            <h3><?= h($booking['sherbimi']) ?></h3>

                            <div class="booking-info-grid">

                                <div>
                                    <small>DATA</small>
                                    <strong>
                                        <?= date('d.m.Y', strtotime($booking['data'])) ?>
                                    </strong>
                                </div>

                                <div>
                                    <small>ORA</small>
                                    <strong>
                                        <?= h(substr($booking['ora'], 0, 5)) ?>
                                    </strong>
                                </div>

                                <div>
                                    <small>PUNONJËSJA</small>
                                    <strong>
                                        <?= h($booking['staf_emri'] . ' ' . $booking['staf_mbiemri']) ?>
                                    </strong>
                                </div>

                                <div>
                                    <small>KOHËZGJATJA</small>
                                    <strong>
                                        <?= (int)$booking['kohezgjatja_min'] ?> min
                                    </strong>
                                </div>

                                <div>
                                    <small>ÇMIMI</small>
                                    <strong>
                                        <?= number_format((float)$booking['cmimi'], 2) ?> €
                                    </strong>
                                </div>

                            </div>

                            <?php if ($canCancel): ?>

                                <form
                                    method="POST"
                                    class="cancel-booking-form"
                                    onsubmit="return confirm('A je e sigurt që dëshiron ta anulosh këtë rezervim?');"
                                >

                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= h($_SESSION['my_bookings_csrf']) ?>"
                                    >

                                    <input type="hidden" name="action" value="cancel">

                                    <input
                                        type="hidden"
                                        name="telefoni"
                                        value="<?= h($phone) ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="termin_id"
                                        value="<?= (int)$booking['termin_id'] ?>"
                                    >

                                    <button type="submit" class="cancel-booking-button">
                                        Anulo rezervimin
                                    </button>

                                </form>

                            <?php elseif ($isCancelled): ?>

                                <div class="booking-disabled-note">
                                    Ky rezervim është anuluar.
                                </div>

                            <?php elseif ($isCompleted): ?>

                                <div class="booking-disabled-note">
                                    Ky termin është përfunduar.
                                </div>

                            <?php else: ?>

                                <div class="booking-disabled-note">
                                    Ky termin ka kaluar.
                                </div>

                            <?php endif; ?>

                        </article>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        <?php endif; ?>

    </section>

</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

</body>
</html>
