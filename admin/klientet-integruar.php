
<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../repositories/ClientRepository.php';
require_once __DIR__ . '/../services/ClientService.php';
require_once __DIR__ . '/../controllers/ClientController.php';

$controller = new ClientController(new ClientService(new ClientRepository($pdo)));
if (empty($_SESSION['client_csrf'])) {
    $_SESSION['client_csrf'] = bin2hex(random_bytes(32));
}
$message = '';
$error = '';
$editClient = null;
$profile = null;
$clients = [];
$stats = ['total' => 0, 'new' => 0];
$search = trim((string) ($_GET['kerko'] ?? ''));
$sort = (string) ($_GET['rendit'] ?? 'ri');
$allowedSorts = ['ri', 'emri', 'vjetër'];
if (!in_array($sort, $allowedSorts, true)) $sort = 'ri';
$formValues = [];

function h($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!hash_equals($_SESSION['client_csrf'], (string) ($_POST['csrf_token'] ?? ''))) {
            throw new RuntimeException('Sesioni i formularit nuk është valid. Rifresko faqen dhe provo përsëri.');
        }
        $action = (string) ($_POST['action'] ?? '');
        if ($action === 'shto' || $action === 'ndrysho') {
            $formValues = [
                'emri' => trim((string) ($_POST['emri'] ?? '')),
                'mbiemri' => trim((string) ($_POST['mbiemri'] ?? '')),
                'telefoni' => trim((string) ($_POST['telefoni'] ?? '')),
                'email' => trim((string) ($_POST['email'] ?? '')),
                'adresa' => trim((string) ($_POST['adresa'] ?? '')),
            ];
            if ($action === 'shto') {
                $result = $controller->create($formValues);
            } else {
                $id = filter_var($_POST['klient_id'] ?? null, FILTER_VALIDATE_INT);
                if (!$id || $id < 1) throw new RuntimeException('ID-ja e klientit nuk është valide.');
                $result = $controller->update($id, $formValues);
                $editClient = array_merge($formValues, ['klient_id' => $id]);
            }
            if (!($result['success'] ?? false)) {
                throw new RuntimeException(implode(' ', $result['errors'] ?? ['Veprimi nuk u realizua.']));
            }
            header('Location: klientet.php?mesazh=' . ($action === 'shto' ? 'shtuar' : 'ndryshuar'));
            exit;
        } elseif ($action === 'fshij') {
            $id = filter_var($_POST['klient_id'] ?? null, FILTER_VALIDATE_INT);
            if (!$id || $id < 1) throw new RuntimeException('ID-ja e klientit nuk është valide.');
            $result = $controller->delete($id);
            if (!($result['success'] ?? false)) {
                throw new RuntimeException(implode(' ', $result['errors'] ?? ['Fshirja nuk u realizua.']));
            }
            header('Location: klientet.php?mesazh=fshire');
            exit;
        }
    }
    if (isset($_GET['edit']) && !$editClient) {
        $id = filter_var($_GET['edit'], FILTER_VALIDATE_INT);
        if ($id && $id > 0) $editClient = $controller->show($id);
        if (!$editClient) $error = 'Klienti për ndryshim nuk u gjet.';
    }
    if (isset($_GET['profile'])) {
        $id = filter_var($_GET['profile'], FILTER_VALIDATE_INT);
        if ($id && $id > 0) $profile = $controller->show($id);
        if (!$profile) $error = 'Profili i klientit nuk u gjet.';
    }
    $data = $controller->index();
    $clients = $data['clients'];
    $statistics = $data['statistics'];
    $stats = [
        'total' => (int) ($statistics['total_clients'] ?? 0),
        'new' => (int) ($statistics['new_this_month'] ?? 0),
    ];
    if ($sort === 'emri') {
        usort($clients, fn($a, $b) => strcasecmp(($a['emri'] ?? '') . ' ' . ($a['mbiemri'] ?? ''), ($b['emri'] ?? '') . ' ' . ($b['mbiemri'] ?? '')));
    } elseif ($sort === 'vjetër') {
        $clients = array_reverse($clients);
    }
} catch (Throwable $e) {
    error_log('Clients admin error: ' . $e->getMessage());
    $error = $e instanceof RuntimeException ? $e->getMessage() : 'Ndodhi një gabim. Kontrollo databazën ose konfigurimin.';
}
$messages = [
    'shtuar' => 'Klienti u regjistrua me sukses.',
    'ndryshuar' => 'Të dhënat e klientit u përditësuan.',
    'fshire' => 'Klienti u fshi me sukses.'
];