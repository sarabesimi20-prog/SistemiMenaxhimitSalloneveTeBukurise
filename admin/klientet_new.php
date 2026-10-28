
<?php

session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../repositories/ClientRepository.php';
require_once __DIR__ . '/../services/ClientService.php';
require_once __DIR__ . '/../controllers/ClientController.php';

$repository = new ClientRepository($pdo);
$service = new ClientService($repository);
$controller = new ClientController($service);

$errorMessage = '';
$successMessage = '';
$profile = null;
$formErrors = [];
$formData = [
    'emri' => '',
    'mbiemri' => '',
    'telefoni' => '',
    'email' => '',
    'adresa' => ''
];

$isAdding = isset($_GET['shto']);


 // Përpunimi i formularit.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    // Fshirja e klientit.
    if ($action === 'delete') {
        $clientId = filter_input(
            INPUT_POST,
            'klient_id',
            FILTER_VALIDATE_INT
        );

        if (!$clientId || $clientId <= 0) {
            $errorMessage = 'ID-ja e klientit nuk është e vlefshme.';
        } else {
            $result = $controller->delete($clientId);

            if ($result['success']) {
                header('Location: klientet_new.php?fshire=1');
                exit;
            }

            $formErrors = $result['errors'] ?? [
                'Fshirja e klientit nuk u realizua.'
            ];
        }
    }

    // Regjistrimi i klientit.
    elseif ($action === 'create') {
        $formData = [
            'emri' => trim((string) ($_POST['emri'] ?? '')),
            'mbiemri' => trim((string) ($_POST['mbiemri'] ?? '')),
            'telefoni' => trim((string) ($_POST['telefoni'] ?? '')),
            'email' => trim((string) ($_POST['email'] ?? '')),
            'adresa' => trim((string) ($_POST['adresa'] ?? ''))
        ];

        $result = $controller->create($formData);

        if ($result['success']) {
            header('Location: klientet_new.php?regjistruar=1');
            exit;
        }

        $formErrors = $result['errors'] ?? [];
        $isAdding = true;
    }
}

if (isset($_GET['regjistruar'])) {
    $successMessage = 'Klienti u regjistrua me sukses.';
} elseif (isset($_GET['fshire'])) {
    $successMessage = 'Klienti u fshi me sukses.';
}

// Shfaqja e profilit.
if (isset($_GET['profile'])) {
    $profileId = filter_input(INPUT_GET, 'profile', FILTER_VALIDATE_INT);

    if ($profileId) {
        $profile = $controller->show($profileId);
    }

    if (!$profile) {
        http_response_code(404);
    }
}

// Merr listën dhe statistikat.
$data = $controller->index();

function escapeClientPage(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$pageTitle = 'Menaxhimi i klientëve | LUMIÈRE Beauty Studio';

?>
<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= escapeClientPage($pageTitle) ?></title>

    <link rel="stylesheet" href="../assets/css/clients.css">

    <style>
        body {
            margin: 0;
            padding: 24px;
            background: #f8f4f0;
            color: #45352f;
            font-family: Arial, sans-serif;
        }

        .client-test-notice {
            max-width: 1100px;
            margin: 0 auto 20px;
            padding: 12px 16px;
            border: 1px solid #e7d7cc;
            border-radius: 10px;
            background: #fff;
            font-size: 13px;
        }

        .client-profile,
        .client-form-card {
            max-width: 700px;
            margin: 30px auto;
            padding: 28px;
            border: 1px solid #eadfd5;
            border-radius: 14px;
            background: #fff;
            box-shadow: 0 8px 30px rgba(60, 40, 30, .06);
        }

        .client-profile h1,
        .client-form-card h1 {
            margin-top: 0;
        }

        .client-profile p {
            line-height: 1.8;
        }

        .client-profile a,
        .client-back-link {
            color: #865c55;
        }

        .client-form {
            display: grid;
            gap: 18px;
        }

        .client-form label {
            display: block;
            margin-bottom: 7px;
            font-size: 14px;
            font-weight: 600;
        }

        .client-form input,
        .client-form textarea {
            box-sizing: border-box;
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #e4d8cd;
            border-radius: 8px;
            background: #fffdfa;
            color: #45352f;
            font: inherit;
        }

        .client-form input:focus,
        .client-form textarea:focus {
            outline: 2px solid #c9a895;
            outline-offset: 1px;
        }

        .client-form textarea {
            min-height: 90px;
            resize: vertical;
        }

        .client-form button {
            padding: 13px 18px;
            border: 0;
            border-radius: 8px;
            background: #30251f;
            color: white;
            font: inherit;
            cursor: pointer;
        }

        .client-form button:hover {
            background: #594238;
        }

        .client-message {
            max-width: 1100px;
            margin: 16px auto;
            padding: 14px 18px;
            border-radius: 8px;
            background: #fff;
        }

        .client-success {
            border: 1px solid #b7d9bd;
            color: #245c31;
        }

        .client-error {
            border: 1px solid #e4b9b9;
            color: #922f2f;
        }

        .client-form-actions {
            display: flex;
            align-items: center;
            gap: 18px;
            flex-wrap: wrap;
        }

        .client-back-link {
            text-decoration: none;
        }
    </style>
</head>
<body>

    <div class="client-test-notice">
        <strong>LUMIÈRE Beauty Studio</strong> — Menaxhimi i klientëve
    </div>

    <?php if ($successMessage !== ''): ?>
        <div class="client-message client-success" role="status">
            <?= escapeClientPage($successMessage) ?>
        </div>
    <?php endif; ?>

    <?php if ($isAdding): ?>

        <section class="client-form-card">
            <h1>Regjistro klient të ri</h1>
            <p>Plotëso të dhënat e klientit për ta ruajtur në databazë.</p>

            <?php if ($formErrors): ?>
                <div class="client-message client-error" role="alert">
                    <strong>Kontrollo të dhënat:</strong>
                    <ul>
                        <?php foreach ($formErrors as $error): ?>
                            <li><?= escapeClientPage($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="klientet_new.php?shto=1" class="client-form">
                <input type="hidden" name="action" value="create">

                <div>
                    <label for="emri">Emri *</label>
                    <input
                        type="text"
                        id="emri"
                        name="emri"
                        maxlength="100"
                        value="<?= escapeClientPage($formData['emri']) ?>"
                        autocomplete="given-name"
                        required
                    >
                </div>

                <div>
                    <label for="mbiemri">Mbiemri *</label>
                    <input
                        type="text"
                        id="mbiemri"
                        name="mbiemri"
                        maxlength="100"
                        value="<?= escapeClientPage($formData['mbiemri']) ?>"
                        autocomplete="family-name"
                        required
                    >
                </div>

                <div>
                    <label for="telefoni">Numri i telefonit</label>
                    <input
                        type="tel"
                        id="telefoni"
                        name="telefoni"
                        maxlength="30"
                        value="<?= escapeClientPage($formData['telefoni']) ?>"
                        autocomplete="tel"
                    >
                </div>

                <div>
                    <label for="email">Email</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        maxlength="150"
                        value="<?= escapeClientPage($formData['email']) ?>"
                        autocomplete="email"
                    >
                </div>

                <div>
                    <label for="adresa">Adresa</label>
                    <textarea
                        id="adresa"
                        name="adresa"
                        maxlength="255"
                        autocomplete="street-address"
                    ><?= escapeClientPage($formData['adresa']) ?></textarea>
                </div>

                <div class="client-form-actions">
                    <button type="submit">Ruaj klientin</button>
                    <a class="client-back-link" href="klientet_new.php">
                        Anulo dhe kthehu
                    </a>
                </div>
            </form>
        </section>

    <?php elseif (isset($_GET['profile'])): ?>

        <?php if ($profile): ?>
            <section class="client-profile">
                <h1>
                    <?= escapeClientPage(
                        ($profile['emri'] ?? '') . ' ' .
                        ($profile['mbiemri'] ?? '')
                    ) ?>
                </h1>

                <p><strong>ID:</strong> <?= (int) $profile['klient_id'] ?></p>
                <p><strong>Telefoni:</strong>
                    <?= escapeClientPage($profile['telefoni'] ?: 'Nuk është dhënë') ?>
                </p>
                <p><strong>Email:</strong>
                    <?= escapeClientPage($profile['email'] ?: 'Nuk është dhënë') ?>
                </p>
                <p><strong>Adresa:</strong>
                    <?= escapeClientPage($profile['adresa'] ?: 'Nuk është dhënë') ?>
                </p>
                <p><strong>Data e regjistrimit:</strong>
                    <?= escapeClientPage($profile['data_regjistrimit'] ?? 'Nuk është dhënë') ?>
                </p>

                <a href="klientet_new.php">← Kthehu te lista e klientëve</a>
            </section>
        <?php else: ?>
            <section class="client-profile">
                <h1>Klienti nuk u gjet</h1>
                <a href="klientet_new.php">← Kthehu te lista</a>
            </section>
        <?php endif; ?>

    <?php else: ?>

        <?php require __DIR__ . '/../views/clients/index.php'; ?>

    <?php endif; ?>

</body>
</html>

