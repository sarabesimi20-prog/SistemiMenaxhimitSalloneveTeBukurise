
<?php

session_start();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require_once __DIR__ . '/../config/database.php';

/*pergaditisim lidhjen me databaz*/

if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'DATABASE_CONNECTION_ERROR',
        'message' => 'Lidhja me databazën nuk u gjet.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

/* funskioni per pergjigjet JOSN  */

function apiResponse(int $status, array $data): void
{
    http_response_code($status);

    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    exit;
}

/* kontrolli i administratorit */

if (
    empty($_SESSION['admin_id']) ||
    ($_SESSION['admin_role'] ?? '') !== 'admin'
) {
    apiResponse(401, [
        'success' => false,
        'error' => 'UNAUTHORIZED',
        'message' => 'Duhet të kyçesh si administrator.'
    ]);
}

/* Krijimi i token-it CSRF */

if (empty($_SESSION['api_csrf'])) {
    $_SESSION['api_csrf'] = bin2hex(random_bytes(32));
}

/* kontrolli i token per veprimet qe krijojn te dhenat */

function checkCsrf(): void
{
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

    if (
        !is_string($token) ||
        empty($_SESSION['api_csrf']) ||
        !hash_equals($_SESSION['api_csrf'], $token)
    ) {
        apiResponse(403, [
            'success' => false,
            'error' => 'INVALID_CSRF_TOKEN',
            'message' => 'Token-i i sigurisë nuk është valid.'
        ]);
    }
}

/* leximi i te dhenav JSON */

function readJson(): array
{
    $raw = file_get_contents('php://input');

    try {
        $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $e) {
        apiResponse(400, [
            'success' => false,
            'error' => 'INVALID_JSON',
            'message' => 'Të dhënat JSON nuk janë në format të saktë.'
        ]);
    }

    if (!is_array($data)) {
        apiResponse(400, [
            'success' => false,
            'error' => 'INVALID_INPUT',
            'message' => 'Dërgo të dhënat në format JSON.'
        ]);
    }

    return $data;
}

/* validimi i te dhenave te klientit  */

function validateClient(array $data, array $existing = []): array
{
    $allowed = ['emri', 'mbiemri', 'telefoni', 'email', 'adresa'];

    foreach ($data as $field => $value) {
        if (!in_array($field, $allowed, true)) {
            apiResponse(400, [
                'success' => false,
                'error' => 'INVALID_FIELD',
                'message' => 'Fusha ' . $field . ' nuk lejohet.'
            ]);
        }
    }

    $merged = array_merge($existing, $data);

    $emri = trim((string) ($merged['emri'] ?? ''));
    $mbiemri = trim((string) ($merged['mbiemri'] ?? ''));

    $telefoni = trim((string) ($merged['telefoni'] ?? ''));
    $email = trim((string) ($merged['email'] ?? ''));
    $adresa = trim((string) ($merged['adresa'] ?? ''));

    if ($emri === '' || $mbiemri === '') {
        apiResponse(422, [
            'success' => false,
            'error' => 'REQUIRED_FIELDS',
            'message' => 'Emri dhe mbiemri janë të detyrueshëm.'
        ]);
    }

    if (
        mb_strlen($emri) > 100 ||
        mb_strlen($mbiemri) > 100 ||
        mb_strlen($telefoni) > 20 ||
        mb_strlen($email) > 150 ||
        mb_strlen($adresa) > 255
    ) {
        apiResponse(422, [
            'success' => false,
            'error' => 'FIELD_TOO_LONG',
            'message' => 'Njëra nga fushat i tejkalon karakteret e lejuara.'
        ]);
    }

    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        apiResponse(422, [
            'success' => false,
            'error' => 'INVALID_EMAIL',
            'message' => 'Adresa e email-it nuk është valide.'
        ]);
    }

    return [
        'emri' => $emri,
        'mbiemri' => $mbiemri,
        'telefoni' => $telefoni !== '' ? $telefoni : null,
        'email' => $email !== '' ? $email : null,
        'adresa' => $adresa !== '' ? $adresa : null
    ];
}

/* kontrolli i ID  */

function getClientId(): int
{
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

    if (!$id || $id < 1) {
        apiResponse(400, [
            'success' => false,
            'error' => 'INVALID_CLIENT_ID',
            'message' => 'ID-ja e klientit nuk është valide.'
        ]);
    }

    return $id;
}

/* percaktimi i metodes HTTP */

$method = $_SERVER['REQUEST_METHOD'];

try {

    /* GET -shfaq klientet */

    if ($method === 'GET') {

        $id = $_GET['id'] ?? null;

        if ($id !== null) {

            $clientId = getClientId();

            $stmt = $pdo->prepare(
                'SELECT * FROM klientet WHERE klient_id = :id'
            );

            $stmt->execute(['id' => $clientId]);

            $client = $stmt->fetch();

            if (!$client) {
                apiResponse(404, [
                    'success' => false,
                    'error' => 'CLIENT_NOT_FOUND',
                    'message' => 'Klienti nuk ekziston.'
                ]);
            }

            apiResponse(200, [
                'success' => true,
                'data' => $client,
                'csrf_token' => $_SESSION['api_csrf']
            ]);
        }

        $stmt = $pdo->query(
            'SELECT * FROM klientet
             ORDER BY data_regjistrimit DESC, klient_id DESC'
        );

        apiResponse(200, [
            'success' => true,
            'total' => $stmt->rowCount(),
            'data' => $stmt->fetchAll(),
            'csrf_token' => $_SESSION['api_csrf']
        ]);
    }

    /* Kontrolli CSRF per ndryshime */

    if (in_array($method, ['POST', 'PATCH', 'DELETE'], true)) {
        checkCsrf();
    }

    /* POST - Regjistron klient te ri */

    if ($method === 'POST') {

        $data = readJson();
        $client = validateClient($data);

        $stmt = $pdo->prepare(
            'INSERT INTO klientet
             (emri, mbiemri, telefoni, email, adresa)
             VALUES
             (:emri, :mbiemri, :telefoni, :email, :adresa)'
        );

        $stmt->execute($client);

        $newId = (int) $pdo->lastInsertId();

        apiResponse(201, [
            'success' => true,
            'message' => 'Klienti u regjistrua me sukses.',
            'klient_id' => $newId
        ]);
    }

    /* PATCH - perditson klientin  */

    if ($method === 'PATCH') {

        $clientId = getClientId();
        $data = readJson();

        $stmt = $pdo->prepare(
            'SELECT * FROM klientet WHERE klient_id = :id'
        );

        $stmt->execute(['id' => $clientId]);

        $existing = $stmt->fetch();

        if (!$existing) {
            apiResponse(404, [
                'success' => false,
                'error' => 'CLIENT_NOT_FOUND',
                'message' => 'Klienti nuk ekziston.'
            ]);
        }

        if (empty($data)) {
            apiResponse(400, [
                'success' => false,
                'error' => 'EMPTY_UPDATE',
                'message' => 'Nuk janë dërguar të dhëna për përditësim.'
            ]);
        }

        $client = validateClient($data, $existing);

        $client['id'] = $clientId;

        $stmt = $pdo->prepare(
            'UPDATE klientet SET
                emri = :emri,
                mbiemri = :mbiemri,
                telefoni = :telefoni,
                email = :email,
                adresa = :adresa
             WHERE klient_id = :id'
        );

        $stmt->execute($client);

        apiResponse(200, [
            'success' => true,
            'message' => 'Të dhënat e klientit u përditësuan.'
        ]);
    }

    /* delete- Fshin klientin */

    if ($method === 'DELETE') {

        $clientId = getClientId();

        $stmt = $pdo->prepare(
            'DELETE FROM klientet WHERE klient_id = :id'
        );

        $stmt->execute(['id' => $clientId]);

        if ($stmt->rowCount() === 0) {
            apiResponse(404, [
                'success' => false,
                'error' => 'CLIENT_NOT_FOUND',
                'message' => 'Klienti nuk ekziston.'
            ]);
        }

        apiResponse(200, [
            'success' => true,
            'message' => 'Klienti u fshi me sukses.'
        ]);
    }

    /* Metoda HTTP e palejuar */

    header('Allow: GET, POST, PATCH, DELETE');

    apiResponse(405, [
        'success' => false,
        'error' => 'METHOD_NOT_ALLOWED',
        'message' => 'Metoda HTTP nuk lejohet.'
    ]);

} catch (PDOException $e) {

    error_log('Clients API database error: ' . $e->getMessage());

    if ($e->getCode() === '23000') {
        apiResponse(409, [
            'success' => false,
            'error' => 'CLIENT_HAS_DEPENDENCIES',
            'message' => 'Klienti ka të dhëna të lidhura dhe nuk mund të fshihet.'
        ]);
    }

    apiResponse(500, [
        'success' => false,
        'error' => 'DATABASE_ERROR',
        'message' => 'Ndodhi një gabim gjatë komunikimit me databazën.'
    ]);

} catch (Throwable $e) {

    error_log('Clients API error: ' . $e->getMessage());

    apiResponse(500, [
        'success' => false,
        'error' => 'SERVER_ERROR',
        'message' => 'Ndodhi një gabim i brendshëm në server.'
    ]);
}
