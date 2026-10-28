<?php
require_once __DIR__ . '/../config/database.php';

function h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function money($value): string
{
    return number_format((float)$value, 2, ',', '.') . ' €';
}

$messageMap = [
    'shtuar' => 'Produkti u regjistrua me sukses.',
    'ndryshuar' => 'Produkti u përditësua me sukses.',
    'levizja' => 'Lëvizja e stokut u regjistrua me sukses.',
    'statusi' => 'Statusi i produktit u ndryshua me sukses.'
];

$message = $messageMap[$_GET['mesazh'] ?? ''] ?? '';
$error = '';

$showProductForm = isset($_GET['shto']) || isset($_GET['edit']);
$showMovementForm = isset($_GET['levizje']);

$productForm = [
    'produkt_id' => '',
    'emri' => '',
    'pershkrimi' => '',
    'kategoria' => '',
    'sasia' => 0,
    'stoku_minimal' => 5,
    'cmimi_blerjes' => '0.00',
    'cmimi_shitjes' => '',
    'aktiv' => 1
];

$movementForm = [
    'produkt_id' => trim($_GET['produkt'] ?? ''),
    'lloji' => 'hyrje',
    'sasia' => '',
    'arsye' => ''
];

$products = [];
$lowStockProducts = [];
$movementHistory = [];
$movementProducts = [];
$categories = [];

$stats = [
    'produkte_aktive' => 0,
    'stok_i_ulet' => 0,
    'vlera_stokut' => 0,
    'levizje_muaj' => 0
];

/*
   VEPRIMET: PRODUKTE, STATUS DHE LEVIZJE STOKU
    */

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        /* AKTIVIZO / ÇAKTIVIZO PRODUKTIN */
        if ($action === 'ndrysho_status') {
            $id = filter_var(
                $_POST['produkt_id'] ?? '',
                FILTER_VALIDATE_INT
            );

            if (!$id || $id < 1) {
                throw new RuntimeException(
                    'ID-ja e produktit nuk është valide.'
                );
            }

            $stmt = $pdo->prepare(
                'UPDATE produktet
                 SET aktiv = IF(aktiv = 1, 0, 1)
                 WHERE produkt_id = :id'
            );

            $stmt->execute(['id' => $id]);

            header('Location: inventari.php?mesazh=statusi');
            exit;
        }

        /* regjistrimi /ndryshimi i produktit */
        if (
            $action === 'shto_produkt' ||
            $action === 'ndrysho_produkt'
        ) {
            $isEdit = $action === 'ndrysho_produkt';
            $showProductForm = true;

            $productForm = [
                'produkt_id' => trim($_POST['produkt_id'] ?? ''),
                'emri' => trim($_POST['emri'] ?? ''),
                'pershkrimi' => trim($_POST['pershkrimi'] ?? ''),
                'kategoria' => trim($_POST['kategoria'] ?? ''),
                'sasia' => trim($_POST['sasia'] ?? '0'),
                'stoku_minimal' => trim(
                    $_POST['stoku_minimal'] ?? '5'
                ),
                'cmimi_blerjes' => trim(
                    $_POST['cmimi_blerjes'] ?? '0'
                ),
                'cmimi_shitjes' => trim(
                    $_POST['cmimi_shitjes'] ?? ''
                ),
                'aktiv' => isset($_POST['aktiv'])
                    ? (int) $_POST['aktiv']
                    : 0
            ];

            $id = null;

            if ($isEdit) {
                $id = filter_var(
                    $productForm['produkt_id'],
                    FILTER_VALIDATE_INT
                );

                if (!$id || $id < 1) {
                    throw new RuntimeException(
                        'ID-ja e produktit nuk është valide.'
                    );
                }
            }

            if (
                $productForm['emri'] === '' ||
                mb_strlen($productForm['emri']) > 150
            ) {
                throw new RuntimeException(
                    'Emri është i detyrueshëm dhe nuk duhet të kalojë 150 karaktere.'
                );
            }

            if (mb_strlen($productForm['pershkrimi']) > 20000) {
                throw new RuntimeException(
                    'Përshkrimi është tepër i gjatë.'
                );
            }

            if (mb_strlen($productForm['kategoria']) > 100) {
                throw new RuntimeException(
                    'Kategoria nuk duhet të kalojë 100 karaktere.'
                );
            }

            if (
                $productForm['cmimi_blerjes'] === '' ||
                !is_numeric($productForm['cmimi_blerjes']) ||
                (float)$productForm['cmimi_blerjes'] < 0 ||
                (float)$productForm['cmimi_blerjes'] > 99999999.99
            ) {
                throw new RuntimeException(
                    'Çmimi i blerjes nuk është valid.'
                );
            }

            if (
                $productForm['cmimi_shitjes'] !== '' &&
                (
                    !is_numeric($productForm['cmimi_shitjes']) ||
                    (float)$productForm['cmimi_shitjes'] < 0 ||
                    (float)$productForm['cmimi_shitjes'] > 99999999.99
                )
            ) {
                throw new RuntimeException(
                    'Çmimi i shitjes nuk është valid.'
                );
            }

            if (
                !ctype_digit((string)$productForm['stoku_minimal']) ||
                (int)$productForm['stoku_minimal'] > 2147483647
            ) {
                throw new RuntimeException(
                    'Stoku minimal duhet të jetë numër i plotë zero ose më i madh.'
                );
            }

            $minimumStock = (int)$productForm['stoku_minimal'];

            $initialStock = 0;

            if (!$isEdit) {
                if (
                    !ctype_digit((string)$productForm['sasia']) ||
                    (int)$productForm['sasia'] > 2147483647
                ) {
                    throw new RuntimeException(
                        'Sasia fillestare duhet të jetë numër i plotë zero ose më i madh.'
                    );
                }

                $initialStock = (int)$productForm['sasia'];
            }

            $buyPrice = number_format(
                (float)$productForm['cmimi_blerjes'],
                2,
                '.',
                ''
            );

            $salePrice = $productForm['cmimi_shitjes'] !== ''
                ? number_format(
                    (float)$productForm['cmimi_shitjes'],
                    2,
                    '.',
                    ''
                )
                : null;

            $data = [
                'emri' => $productForm['emri'],
                'pershkrimi' => $productForm['pershkrimi'] !== ''
                    ? $productForm['pershkrimi'] : null,
                'kategoria' => $productForm['kategoria'] !== ''
                    ? $productForm['kategoria'] : null,
                'stoku_minimal' => $minimumStock,
                'cmimi_blerjes' => $buyPrice,
                'cmimi_shitjes' => $salePrice
            ];

            if ($isEdit) {
                $stmt = $pdo->prepare(
                    'UPDATE produktet SET
                        emri = :emri,
                        pershkrimi = :pershkrimi,
                        kategoria = :kategoria,
                        stoku_minimal = :stoku_minimal,
                        cmimi_blerjes = :cmimi_blerjes,
                        cmimi_shitjes = :cmimi_shitjes,
                        aktiv = :aktiv
                     WHERE produkt_id = :id'
                );

                $data['aktiv'] =
                    $productForm['aktiv'] === 1 ? 1 : 0;

                $data['id'] = $id;

                $stmt->execute($data);

                header('Location: inventari.php?mesazh=ndryshuar');
                exit;
            }

            /* PRODUKTI I RI DHE STOKU FILLESTAR */
            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                'INSERT INTO produktet
                    (
                        emri, pershkrimi, kategoria, sasia,
                        stoku_minimal, cmimi_blerjes,
                        cmimi_shitjes, aktiv
                    )
                 VALUES
                    (
                        :emri, :pershkrimi, :kategoria, :sasia,
                        :stoku_minimal, :cmimi_blerjes,
                        :cmimi_shitjes, :aktiv
                    )'
            );

            $stmt->execute([
                'emri' => $data['emri'],
                'pershkrimi' => $data['pershkrimi'],
                'kategoria' => $data['kategoria'],
                'sasia' => $initialStock,
                'stoku_minimal' => $minimumStock,
                'cmimi_blerjes' => $buyPrice,
                'cmimi_shitjes' => $salePrice,
                'aktiv' => $productForm['aktiv'] === 1 ? 1 : 0
            ]);

            $newProductId = (int)$pdo->lastInsertId();

            if ($initialStock > 0) {
                $stmt = $pdo->prepare(
                    'INSERT INTO levizjet_stokut
                        (produkt_id, lloji, sasia, arsye)
                     VALUES
                        (:produkt_id, :lloji, :sasia, :arsye)'
                );

                $stmt->execute([
                    'produkt_id' => $newProductId,
                    'lloji' => 'hyrje',
                    'sasia' => $initialStock,
                    'arsye' => 'Stoku fillestar gjatë regjistrimit të produktit'
                ]);
            }

            $pdo->commit();

            header('Location: inventari.php?mesazh=shtuar');
            exit;
        }

        /*hyrje daljet ose korrigjimi i stokit */
        if ($action === 'levizje_stoku') {
            $showMovementForm = true;

            $movementForm = [
                'produkt_id' => trim($_POST['produkt_id'] ?? ''),
                'lloji' => trim($_POST['lloji'] ?? 'hyrje'),
                'sasia' => trim($_POST['sasia'] ?? ''),
                'arsye' => trim($_POST['arsye'] ?? '')
            ];

            $productId = filter_var(
                $movementForm['produkt_id'],
                FILTER_VALIDATE_INT
            );

            if (!$productId || $productId < 1) {
                throw new RuntimeException(
                    'Zgjidh një produkt valid.'
                );
            }

            if (!in_array(
                $movementForm['lloji'],
                ['hyrje', 'dalje', 'korrigjim'],
                true
            )) {
                throw new RuntimeException(
                    'Lloji i lëvizjes nuk është valid.'
                );
            }

            if (
                !ctype_digit((string)$movementForm['sasia']) ||
                (int)$movementForm['sasia'] < 1 ||
                (int)$movementForm['sasia'] > 2147483647
            ) {
                throw new RuntimeException(
                    'Sasia duhet të jetë numër i plotë më i madh se zero.'
                );
            }

            if (mb_strlen($movementForm['arsye']) > 255) {
                throw new RuntimeException(
                    'Arsyeja nuk duhet të kalojë 255 karaktere.'
                );
            }

            $quantity = (int)$movementForm['sasia'];

            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                'SELECT sasia, emri
                 FROM produktet
                 WHERE produkt_id = :id
                 FOR UPDATE'
            );

            $stmt->execute(['id' => $productId]);
            $currentProduct = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$currentProduct) {
                throw new RuntimeException(
                    'Produkti nuk u gjet.'
                );
            }

            $currentStock = (int)$currentProduct['sasia'];
            $movementQuantity = $quantity;

            if ($movementForm['lloji'] === 'hyrje') {
                if ($currentStock > 2147483647 - $quantity) {
                    throw new RuntimeException(
                        'Sasia e re është tepër e madhe.'
                    );
                }

                $newStock = $currentStock + $quantity;

            } elseif ($movementForm['lloji'] === 'dalje') {
                if ($quantity > $currentStock) {
                    throw new RuntimeException(
                        'Stoku nuk mjafton për këtë dalje. Sasia aktuale është '
                        . $currentStock . '.'
                    );
                }

                $newStock = $currentStock - $quantity;

            } else {
                /* korrigjimi vendos sasin reale */
                $newStock = $quantity;
                $movementQuantity = $newStock - $currentStock;

                if ($movementQuantity === 0) {
                    throw new RuntimeException(
                        'Korrigjimi nuk ndryshon stokun aktual.'
                    );
                }
            }

            $stmt = $pdo->prepare(
                'UPDATE produktet
                 SET sasia = :sasia
                 WHERE produkt_id = :id'
            );

            $stmt->execute([
                'sasia' => $newStock,
                'id' => $productId
            ]);

            $stmt = $pdo->prepare(
                'INSERT INTO levizjet_stokut
                    (produkt_id, lloji, sasia, arsye)
                 VALUES
                    (:produkt_id, :lloji, :sasia, :arsye)'
            );

            $stmt->execute([
                'produkt_id' => $productId,
                'lloji' => $movementForm['lloji'],
                'sasia' => $movementQuantity,
                'arsye' => $movementForm['arsye'] !== ''
                    ? $movementForm['arsye'] : null
            ]);

            $pdo->commit();

            header('Location: inventari.php?mesazh=levizja');
            exit;
        }
    }

} catch (RuntimeException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $error = $e->getMessage();

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $error = 'Ndodhi një gabim në databazë. Kontrollo tabelat produktet dhe levizjet_stokut.';

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $error = 'Ndodhi një gabim. Kontrollo të dhënat dhe provo përsëri.';
}

/* leximi i produktit qe ndryshohet */
if (isset($_GET['edit']) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $editId = filter_var($_GET['edit'], FILTER_VALIDATE_INT);

    if ($editId && $editId > 0) {
        try {
            $stmt = $pdo->prepare(
                'SELECT *
                 FROM produktet
                 WHERE produkt_id = :id'
            );

            $stmt->execute(['id' => $editId]);
            $existingProduct = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existingProduct) {
                $productForm = array_merge(
                    $productForm,
                    $existingProduct
                );
                $showProductForm = true;
            } else {
                $error = 'Produkti i zgjedhur nuk u gjet.';
            }

        } catch (PDOException $e) {
            $error = 'Nuk mund të lexohen të dhënat e produktit.';
        }
    }
}

/* filtra */
$search = trim($_GET['kerko'] ?? '');
$categoryFilter = trim($_GET['kategoria'] ?? '');
$stockFilter = $_GET['stoku'] ?? 'te_gjitha';
$sort = $_GET['rendit'] ?? 'emri';

$orderOptions = [
    'emri' => 'emri ASC',
    'sasia_larte' => 'sasia DESC, emri ASC',
    'sasia_ulet' => 'sasia ASC, emri ASC',
    'cmimi_blerjes' => 'cmimi_blerjes DESC, emri ASC',
    'me_te_rejat' => 'produkt_id DESC'
];

$orderBy = $orderOptions[$sort] ?? $orderOptions['emri'];

try {
    /* STATISTIKAT */
    $stmt = $pdo->query(
        "SELECT
            COUNT(*) AS produkte_aktive,
            COALESCE(SUM(
                CASE WHEN sasia <= stoku_minimal THEN 1 ELSE 0 END
            ), 0) AS stok_i_ulet,
            COALESCE(SUM(sasia * cmimi_blerjes), 0) AS vlera_stokut
         FROM produktet
         WHERE aktiv = 1"
    );

    $stats = array_merge(
        $stats,
        $stmt->fetch(PDO::FETCH_ASSOC) ?: []
    );

    $stmt = $pdo->query(
        "SELECT COUNT(*)
         FROM levizjet_stokut
         WHERE data_levizjes >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
           AND data_levizjes < DATE_FORMAT(
               CURDATE() + INTERVAL 1 MONTH,
               '%Y-%m-01'
           )"
    );

    $stats['levizje_muaj'] = (int)$stmt->fetchColumn();

    /* kategorit */
    $stmt = $pdo->query(
        "SELECT DISTINCT kategoria
         FROM produktet
         WHERE kategoria IS NOT NULL
           AND TRIM(kategoria) <> ''
         ORDER BY kategoria"
    );

    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

    /* produktet per kartela */
    $where = [];
    $params = [];

    if ($search !== '') {
        $where[] = '(
            emri LIKE :q1
            OR kategoria LIKE :q2
            OR pershkrimi LIKE :q3
        )';

        $term = '%' . $search . '%';

        $params['q1'] = $term;
        $params['q2'] = $term;
        $params['q3'] = $term;
    }

    if ($categoryFilter !== '') {
        $where[] = 'kategoria = :kategori';
        $params['kategori'] = $categoryFilter;
    }

    if ($stockFilter === 'ulet') {
        $where[] = 'aktiv = 1 AND sasia <= stoku_minimal';
    } elseif ($stockFilter === 'pa_stok') {
        $where[] = 'aktiv = 1 AND sasia = 0';
    } elseif ($stockFilter === 'aktive') {
        $where[] = 'aktiv = 1';
    } elseif ($stockFilter === 'joaktive') {
        $where[] = 'aktiv = 0';
    }

    $sql = 'SELECT
                produkt_id,
                emri,
                pershkrimi,
                kategoria,
                sasia,
                stoku_minimal,
                cmimi_blerjes,
                cmimi_shitjes,
                aktiv,
                krijuar_me
            FROM produktet';

    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $sql .= ' ORDER BY ' . $orderBy;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /* Produktet per te levizur */
    $stmt = $pdo->query(
        'SELECT produkt_id, emri, sasia, aktiv
         FROM produktet
         ORDER BY emri'
    );

    $movementProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /* historiku */
    $stmt = $pdo->query(
        'SELECT
            l.levizja_id,
            l.produkt_id,
            l.lloji,
            l.sasia,
            l.arsye,
            l.data_levizjes,
            p.emri AS produkt_emri
         FROM levizjet_stokut l
         INNER JOIN produktet p
             ON p.produkt_id = l.produkt_id
         ORDER BY l.data_levizjes DESC, l.levizja_id DESC
         LIMIT 12'
    );

    $movementHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /* paralajmrimet e stokut  */
    $stmt = $pdo->query(
        'SELECT produkt_id, emri, sasia, stoku_minimal, kategoria
         FROM produktet
         WHERE aktiv = 1 AND sasia <= stoku_minimal
         ORDER BY sasia ASC, emri ASC
         LIMIT 6'
    );

    $lowStockProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    if ($error === '') {
        $error = 'Nuk mund të lexohen të dhënat e inventarit. Kontrollo strukturën e tabelave.';
    }
}
?>
<!DOCTYPE html>
<html lang="sq">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Inventari | LUMIÈRE Beauty Studio</title>

<style>
:root {
    --cream: #f7f4ef;
    --paper: #fffdf9;
    --brown: #302820;
    --soft: #8b8176;
    --gold: #947c64;
    --line: #eae2d8;
    --pale: #eee5da;
    --green: #e8f0e5;
    --red: #f8e9e4;
    --amber: #f5eddf;
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

button, input, select, textarea {
    font: inherit;
}

a {
    color: inherit;
}

.layout {
    display: flex;
    min-height: 100vh;
}

/* SIDEBAR — STILI I DASHBOARD-IT */
.sidebar {
    width: 250px;
    flex-shrink: 0;
    padding: 30px 18px;
    background: #302820;
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
    border-left: 3px solid #947c64;
    padding-left: 11px;
}



.main {
    --brown: #243e32;
    --soft: #718075;
    --gold: #e77f51;
    --line: #dce5d7;
    --pale: #e9efdf;
    --paper: #fffef9;

    min-width: 0;
    flex: 1;
    padding: 27px clamp(18px, 3vw, 44px);
    background:
        radial-gradient(ellipse at 90% 5%, #f8e7ce 0, transparent 27%),
        #f3f6ec;
    color: var(--brown);
}

.topline {
    display: flex;
    justify-content: space-between;
    gap: 14px;
    padding-bottom: 17px;
    border-bottom: 1px solid var(--line);
    color: var(--soft);
    font-size: 11px;
    letter-spacing: 1.5px;
}

.hero {
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    margin: 25px 0;
    padding: 34px;
    min-height: 200px;
    border: 1px solid #d5e5ce;
    border-radius: 28px 8px 28px 8px;
    background:
        radial-gradient(circle at 90% 20%, #ffffffa6 0 7%, transparent 7.5%),
        radial-gradient(circle at 95% 90%, #ffffff75 0 13%, transparent 13.5%),
        linear-gradient(120deg, #dcebd4, #f5eedb 70%, #f8dec9);
    box-shadow: 0 12px 30px #324a3210;
}

.hero::after {
    content: "✳";
    position: absolute;
    right: 30%;
    top: 8px;
    color: #ffffffa8;
    font-size: 105px;
    transform: rotate(15deg);
    pointer-events: none;
}

.hero > div,
.hero > a,
.hero > .hero-buttons {
    position: relative;
    z-index: 1;
}

.eyebrow {
    display: inline-block;
    padding: 8px 12px;
    margin-bottom: 14px;
    border-radius: 30px;
    background: #fffef0;
    color: #b96642;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 1.5px;
    text-transform: uppercase;
}

h1 {
    margin: 0;
    font: 400 clamp(38px, 4vw, 55px)/1.1 Georgia, serif;
    letter-spacing: -1.5px;
}

.description {
    max-width: 500px;
    margin: 11px 0 0;
    color: #617365;
    font-size: 14px;
    line-height: 1.8;
}

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 42px;
    padding: 0 16px;
    border: 0;
    border-radius: 14px 5px 14px 5px;
    background: #e77f51;
    color: #fff;
    text-decoration: none;
    font-size: 12px;
    font-weight: 650;
    cursor: pointer;
    white-space: nowrap;
    box-shadow: 0 5px 0 #c9653c;
    transition: transform .2s, box-shadow .2s;
}

.btn:hover {
    background: #d86f43;
    transform: translateY(-2px);
    box-shadow: 0 7px 0 #c9653c;
}

.btn-light {
    border: 1px solid #dce5d7;
    background: #fffef9;
    color: #243e32;
    box-shadow: none;
}

.btn-light:hover {
    background: #e9efdf;
    box-shadow: none;
}

.notice {
    padding: 13px 16px;
    margin-bottom: 16px;
    border: 1px solid;
    border-radius: 7px 18px 7px 18px;
    font-size: 13px;
}

.success {
    color: #42633b;
    background: #edf3e9;
    border-color: #d8e5d1;
}

.error {
    color: #914638;
    background: #f8e9e4;
    border-color: #efd2ca;
}


.stats {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
    margin-bottom: 22px;
}

.stat-card {
    position: relative;
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
    overflow: hidden;
    padding: 18px 14px;
    border: 1px solid #e1e6d9;
    border-bottom: 4px solid #b4ceb0;
    border-radius: 7px 20px 7px 20px;
    background: #fffef9;
    box-shadow: 0 7px 18px #263b2c08;
    transition: transform .2s;
}

.stat-card:nth-child(2) {
    border-bottom-color: #f2b18d;
}

.stat-card:nth-child(3) {
    border-bottom-color: #cbb9e6;
}

.stat-card:nth-child(4) {
    border-bottom-color: #e9d17a;
}

.stat-card:hover {
    transform: translateY(-4px);
}

.stat-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 47px;
    height: 47px;
    flex-shrink: 0;
    border-radius: 15px 5px 15px 5px;
    background: #e2eddc;
    color: #41674c;
    font-size: 21px;
}

.stat-card:nth-child(2) .stat-icon {
    background: #fbe5d8;
    color: #b96642;
}

.stat-card:nth-child(3) .stat-icon {
    background: #ece5f7;
    color: #8064a9;
}

.stat-card:nth-child(4) .stat-icon {
    background: #faf0c9;
    color: #96782e;
}

.stat-label {
    color: #718075;
    font-size: 10px;
    font-weight: 700;
    line-height: 1.4;
    text-transform: uppercase;
    letter-spacing: .5px;
}

.stat-value {
    margin-top: 5px;
    color: #243e32;
    font: 400 clamp(20px, 2vw, 26px) Georgia, serif;
    overflow-wrap: anywhere;
}

/* PANELET */
.panel {
    min-width: 0;
    margin-bottom: 22px;
    padding: 23px;
    border: 1px solid #dfe6d8;
    border-radius: 8px 23px 8px 23px;
    background: #fffef9;
    box-shadow: 0 8px 22px #324a3208;
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
    color: #243e32;
    font: 400 27px Georgia, serif;
}

.panel-heading p {
    margin: 7px 0 0;
    color: #718075;
    font-size: 12px;
    line-height: 1.6;
}


.alert-list {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 10px;
}

.alert-item {
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 12px;
    border: 1px solid #f0d5ad;
    border-radius: 7px 18px 7px 18px;
    background: #fff8eb;
    transition: transform .2s;
}

.alert-item:hover {
    transform: translateY(-2px);
}

.alert-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 38px;
    height: 38px;
    flex-shrink: 0;
    border-radius: 13px 4px 13px 4px;
    background: #ffebca;
    color: #b66c30;
    font-size: 18px;
}

.alert-name {
    font-size: 12px;
    font-weight: 700;
    overflow-wrap: anywhere;
}

.alert-meta {
    margin-top: 4px;
    color: #718075;
    font-size: 11px;
}

/* FILTRAT */
.toolbar {
    display: grid;
    grid-template-columns:
        minmax(200px, 1.4fr)
        repeat(2, minmax(130px, 1fr))
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
    border: 1px solid #dce5d7;
    border-radius: 8px;
    background: #fffefb;
    color: #243e32;
    font-size: 12px;
}

.search-input:focus,
.filter-select:focus {
    outline: 3px solid #b8d4ad55;
    border-color: #87ac7e;
}

.fields {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
}

.field label {
    display: block;
    margin-bottom: 7px;
    color: #718075;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .4px;
}

.field input,
.field select,
.field textarea {
    width: 100%;
    min-height: 42px;
    padding: 10px 12px;
    border: 1px solid #dce5d7;
    border-radius: 8px;
    background: #fffefb;
    color: #243e32;
    font-size: 13px;
}

.field textarea {
    min-height: 90px;
    resize: vertical;
}

.field input:focus,
.field select:focus,
.field textarea:focus {
    outline: 3px solid #b8d4ad55;
    border-color: #87ac7e;
}

.hint {
    display: block;
    margin-top: 6px;
    color: #718075;
    font-size: 11px;
    line-height: 1.5;
}

.form-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 9px;
    margin-top: 20px;
}

/* kartelat e produkteve */
.product-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 18px;
}

.product-card {
    position: relative;
    min-width: 0;
    overflow: hidden;
    border: 1px solid #dce5d7;
    border-radius: 8px 22px 8px 22px;
    background: #fffef9;
    box-shadow: 0 7px 20px #263b2c09;
    transition: transform .22s, box-shadow .22s;
}

.product-card::before {
    content: "";
    display: block;
    height: 7px;
    background: #b7d5ac;
}

.product-card:nth-child(4n + 2)::before {
    background: #f0b18d;
}

.product-card:nth-child(4n + 3)::before {
    background: #c8b7e3;
}

.product-card:nth-child(4n + 4)::before {
    background: #e9d477;
}

.product-card:hover {
    transform: translateY(-5px) rotate(-.25deg);
    box-shadow: 0 15px 28px #263b2c13;
}

.product-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 10px;
    padding: 18px 16px 12px;
}

.product-symbol {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 55px;
    height: 55px;
    flex-shrink: 0;
    border-radius: 17px 5px 17px 5px;
    background: #e3eedc;
    color: #3f6548;
    font: 700 25px Georgia, serif;
}

.product-card:nth-child(4n + 2) .product-symbol {
    background: #fbe7da;
    color: #b96844;
}

.product-card:nth-child(4n + 3) .product-symbol {
    background: #eee7f8;
    color: #8065a7;
}

.product-card:nth-child(4n + 4) .product-symbol {
    background: #fbf1ca;
    color: #957a30;
}

.product-title {
    min-width: 0;
    flex: 1;
}

.product-title h3 {
    margin: 0;
    color: #263f32;
    font-size: 16px;
    line-height: 1.45;
    overflow-wrap: anywhere;
}

.product-title p {
    margin: 5px 0 0;
    color: #819083;
    font-size: 11px;
    line-height: 1.5;
}

.status {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 8px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 700;
    white-space: nowrap;
}

.status.good {
    background: #e3f1df;
    color: #396443;
}

.status.low {
    background: #fff0d7;
    color: #996322;
}

.status.zero {
    background: #fde5de;
    color: #a94938;
}

.status.inactive {
    background: #eeeae4;
    color: #756d63;
}

.product-description {
    min-height: 34px;
    padding: 0 16px;
    color: #748177;
    font-size: 11px;
    line-height: 1.7;
    overflow-wrap: anywhere;
}

.product-metrics {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px;
    padding: 12px 16px;
}

.metric {
    min-width: 0;
    padding: 10px;
    border: 1px solid #e4e9dc;
    border-radius: 5px 13px 5px 13px;
    background: #f5f7ef;
}

.metric:nth-child(2) {
    border-color: #f2e3d6;
    background: #fff5ec;
}

.metric-label {
    color: #758276;
    font-size: 10px;
}

.metric-value {
    margin-top: 5px;
    color: #263f32;
    font: 400 20px Georgia, serif;
    overflow-wrap: anywhere;
}

.stock-line {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    padding: 0 16px 12px;
    color: #768377;
    font-size: 10px;
}

.stock-track {
    height: 8px;
    margin: 0 16px 14px;
    overflow: hidden;
    border-radius: 20px;
    background: #e7ecdf;
}

.stock-fill {
    height: 100%;
    border-radius: 20px;
    background: linear-gradient(90deg, #a4c994, #507e59);
}

.stock-fill.low {
    background: linear-gradient(90deg, #f7d17d, #e5a04d);
}

.stock-fill.zero {
    background: linear-gradient(90deg, #f3a18c, #d85e4a);
}

.product-actions {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    border-top: 1px solid #e3e8dd;
    background: #fbfcf6;
}

.product-actions form {
    min-width: 0;
    margin: 0;
}

.product-action {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    min-height: 43px;
    padding: 8px 4px;
    border: 0;
    border-right: 1px solid #e3e8dd;
    background: transparent;
    color: #42604a;
    text-align: center;
    text-decoration: none;
    font-size: 11px;
    font-weight: 650;
    cursor: pointer;
    transition: background .2s;
}

.product-action:last-child {
    border-right: 0;
}

.product-action:hover {
    background: #eaf1e4;
}

.product-action.muted {
    color: #b65f4d;
}

/* HISTORIKU */
.table-wrap {
    width: 100%;
    overflow-x: auto;
    border: 1px solid #dfe6d8;
    border-radius: 8px 18px 8px 18px;
}

table {
    width: 100%;
    border-collapse: collapse;
    text-align: left;
    white-space: nowrap;
}

thead {
    background: #e8efdf;
}

th {
    padding: 15px;
    color: #46604a;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: .7px;
    text-transform: uppercase;
}

td {
    padding: 13px 14px;
    border-top: 1px solid #e7ebdf;
    font-size: 12px;
}

tbody tr:hover {
    background: #f1f6eb;
}

.movement-type {
    display: inline-block;
    padding: 5px 8px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 700;
}

.movement-type.hyrje {
    background: #e3f1df;
    color: #396443;
}

.movement-type.dalje {
    background: #fde5de;
    color: #a94938;
}

.movement-type.korrigjim {
    background: #fff0d7;
    color: #996322;
}


.empty {
    grid-column: 1 / -1;
    padding: 42px 20px;
    border: 1px dashed #cbdcc5;
    border-radius: 8px 22px 8px 22px;
    background: #f8faf3;
    text-align: center;
    color: #718075;
}

.empty strong {
    display: block;
    margin-bottom: 8px;
    color: #294333;
    font: 400 23px Georgia, serif;
}

/*footeri */
.footer {
    display: flex;
    justify-content: space-between;
    gap: 15px;
    margin-top: 26px;
    padding-top: 16px;
    border-top: 1px solid #dce5d7;
    color: #718075;
    font: italic 12px Georgia, serif;
}

/* responsive faqja*/
@media (max-width: 1350px) {
    .stats {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .product-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .alert-list {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .toolbar {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .hero::after {
        right: 18%;
        font-size: 75px;
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
        padding: 24px 20px;
        border-radius: 18px 5px 18px 5px;
    }

    .hero::after {
        right: 12px;
        top: 8px;
        font-size: 55px;
    }

    .stats,
    .product-grid,
    .alert-list,
    .fields,
    .toolbar {
        grid-template-columns: 1fr;
    }

    .panel {
        padding: 17px;
    }

    .product-card:hover,
    .alert-item:hover,
    .stat-card:hover {
        transform: none;
    }

    .footer {
        flex-direction: column;
    }
}

@media (max-width: 390px) {
    .stats {
        grid-template-columns: 1fr;
    }

    .product-top {
        flex-direction: column;
    }
}
/* ngjyra e perbashket nuancat*/

.main {
    --brown: #302820;
    --soft: #8b8176;
    --gold: #947c64;
    --line: #eae2d8;
    --pale: #eee5da;
    --paper: #fffdf9;

    background: #f7f4ef;
    color: #302820;
}

/* Headeri */
.main .hero {
    border: 1px solid #eae2d8;
    background: linear-gradient(135deg, #fffdf9, #f3eee7);
    box-shadow: 0 8px 24px #30282008;
}

.main .hero::after {
    color: #eee5da;
}

.main .eyebrow {
    background: #eee5da;
    color: #947c64;
}

/* Butonat */
.main .btn:not(.btn-light) {
    background: #302820;
    color: #fff;
    box-shadow: none;
}

.main .btn:not(.btn-light):hover {
    background: #514438;
    box-shadow: none;
}

.main .btn-light {
    background: #fffdf9;
    border: 1px solid #eae2d8;
    color: #302820;
}

/* Kartelat statistikore */
.main .stat-card {
    background: #fffdf9;
    border-color: #eae2d8;
    border-bottom-color: #947c64;
}

.main .stat-card:nth-child(n) .stat-icon {
    background: #eee5da;
    color: #947c64;
}

.main .stat-label {
    color: #8b8176;
}

.main .stat-value {
    color: #302820;
}

/* Paralajmerimet e stokut */
.main .alert-item {
    border-color: #eae2d8;
    background: #fffdf9;
}

.main .alert-icon {
    background: #eee5da;
    color: #947c64;
}

/* Kartelat e produkteve */
.main .product-card {
    border-color: #eae2d8;
    background: #fffdf9;
    box-shadow: 0 6px 18px #30282008;
}

.main .product-card::before {
    background: #947c64;
}

.main .product-card:nth-child(n)::before {
    background: #947c64;
}

.main .product-symbol {
    background: #eee5da;
    color: #947c64;
}

.main .product-card:nth-child(n) .product-symbol {
    background: #eee5da;
    color: #947c64;
}

.main .product-title h3 {
    color: #302820;
}

.main .product-title p,
.main .product-description {
    color: #8b8176;
}

/* Fushat informative */
.main .metric {
    background: #f7f4ef;
    border-color: #eae2d8;
}

.main .metric:nth-child(n) {
    background: #f7f4ef;
    border-color: #eae2d8;
}

.main .metric-label {
    color: #8b8176;
}

.main .metric-value {
    color: #302820;
}

/* Shiriti i stokut */
.main .stock-track {
    background: #eee5da;
}

.main .stock-fill {
    background: #947c64;
}

/* Butonat e kartelave */
.main .product-actions {
    background: #fffdf9;
    border-color: #eae2d8;
}

.main .product-action {
    color: #302820;
    border-color: #eae2d8;
}

.main .product-action:hover {
    background: #eee5da;
}

.main .product-action.muted {
    color: #8b8176;
}

/* Tabela e historikut */
.main .table-wrap {
    border-color: #eae2d8;
}

.main thead {
    background: #eee5da;
}

.main th {
    color: #6f6255;
}

.main td {
    border-color: #eae2d8;
}

.main tbody tr:hover {
    background: #faf7f2;
}

/* Formularët dhe filtrat */
.main .field input,
.main .field select,
.main .field textarea,
.main .search-input,
.main .filter-select {
    border-color: #eae2d8;
    background: #fffdf9;
    color: #302820;
}

.main .field input:focus,
.main .field select:focus,
.main .field textarea:focus,
.main .search-input:focus,
.main .filter-select:focus {
    border-color: #947c64;
    outline: 3px solid #947c6420;
}

/* Panelet dhe përmbledhjet */
.main .panel {
    border-color: #eae2d8;
    background: #fffdf9;
}

.main .panel-heading h2 {
    color: #302820;
}

.main .panel-heading p {
    color: #8b8176;
}

.main .empty {
    border-color: #d8cbbb;
    background: #fffdf9;
}

.main .empty strong {
    color: #302820;
}

.main .footer {
    border-color: #eae2d8;
    color: #8b8176;
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
    <a class="nav-link active" href="inventari.php">▤ &nbsp; Inventari</a>
    <a class="nav-link" href="raportet.php">▥ &nbsp; Raportet</a>

    <p class="sidebar-label">SISTEMI</p>
    <a class="nav-link" href="cilësimet.php">⚙ &nbsp; Cilësimet</a>
    <a class="nav-link" href="logout.php">⇥ &nbsp; Kyçu</a>
</aside>

<main class="main">

    <div class="topline">
        <span>LUMIÈRE / BEAUTY STOCKROOM</span>
        <span><?= date('d.m.Y') ?></span>
    </div>

    <header class="hero">
        <div>
            <div class="eyebrow">PRODUKTET · STOKU · LËVIZJET</div>
            <h1>Beauty Stockroom<span style="color:#e77f51">.</span></h1>
            <p class="description">
                Gjithçka që i duhet studios në një vend. Mbaj nën kontroll
                produktet, sasitë, çmimet dhe lëvizjet e stokut.
            </p>
        </div>

        <div class="hero-buttons" style="display:flex;gap:10px;flex-wrap:wrap">
            <a class="btn btn-light" href="inventari.php?levizje=1#levizja">
                ↕ Lëvizje stoku
            </a>
            <a class="btn" href="inventari.php?shto=1#produkti">
                + Shto produkt
            </a>
        </div>
    </header>

    <?php if ($message !== ''): ?>
        <div class="notice success"><?= h($message) ?></div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="notice error"><?= h($error) ?></div>
    <?php endif; ?>

    <section class="stats">
        <div class="stat-card">
            <div class="stat-icon">▤</div>
            <div>
                <div class="stat-label">Produkte aktive</div>
                <div class="stat-value">
                    <?= (int)($stats['produkte_aktive'] ?? 0) ?>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">⚠</div>
            <div>
                <div class="stat-label">Stok i ulët</div>
                <div class="stat-value">
                    <?= (int)($stats['stok_i_ulet'] ?? 0) ?>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">€</div>
            <div>
                <div class="stat-label">Vlera e stokut</div>
                <div class="stat-value">
                    <?= money($stats['vlera_stokut'] ?? 0) ?>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">↕</div>
            <div>
                <div class="stat-label">Lëvizje këtë muaj</div>
                <div class="stat-value">
                    <?= (int)($stats['levizje_muaj'] ?? 0) ?>
                </div>
            </div>
        </div>
    </section>

    <?php if ($lowStockProducts): ?>
    <section class="panel">
        <div class="panel-heading">
            <div>
                <h2>Koha për furnizim!</h2>
                <p>
                    Këto produkte kanë arritur ose kanë kaluar nivelin
                    minimal të stokut.
                </p>
            </div>
            <span class="status low">
                <?= count($lowStockProducts) ?> produkte
            </span>
        </div>

        <div class="alert-list">
            <?php foreach ($lowStockProducts as $lowProduct): ?>
                <div class="alert-item">
                    <div class="alert-icon">
                        <?= (int)$lowProduct['sasia'] === 0 ? '!' : '↘' ?>
                    </div>
                    <div style="min-width:0">
                        <div class="alert-name">
                            <?= h($lowProduct['emri']) ?>
                        </div>
                        <div class="alert-meta">
                            Në stok: <?= (int)$lowProduct['sasia'] ?>
                            · Minimumi: <?= (int)$lowProduct['stoku_minimal'] ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <?php if ($showProductForm): ?>
    <section class="panel" id="produkti">
        <div class="panel-heading">
            <div>
                <h2>
                    <?= !empty($productForm['produkt_id'])
                        ? 'Përditëso produktin'
                        : 'Produkti i ri' ?>
                </h2>
                <p>
                    Plotëso të dhënat e produktit. Për produktet ekzistuese,
                    stoku ndryshohet përmes lëvizjeve.
                </p>
            </div>
            <a class="btn btn-light" href="inventari.php">Mbyll ✕</a>
        </div>

        <form method="POST" action="inventari.php">
            <input
                type="hidden"
                name="action"
                value="<?= !empty($productForm['produkt_id'])
                    ? 'ndrysho_produkt'
                    : 'shto_produkt' ?>"
            >
            <input
                type="hidden"
                name="produkt_id"
                value="<?= h($productForm['produkt_id'] ?? '') ?>"
            >

            <div class="fields">
                <div class="field">
                    <label for="emri">Emri i produktit *</label>
                    <input
                        id="emri"
                        name="emri"
                        maxlength="150"
                        value="<?= h($productForm['emri'] ?? '') ?>"
                        placeholder="P.sh. Shampon profesional"
                        required
                    >
                </div>

                <div class="field">
                    <label for="kategoria">Kategoria</label>
                    <input
                        id="kategoria"
                        name="kategoria"
                        maxlength="100"
                        value="<?= h($productForm['kategoria'] ?? '') ?>"
                        placeholder="P.sh. Produkte për flokë"
                    >
                </div>

                <div class="field">
                    <label for="cmimi_blerjes">Çmimi i blerjes (€) *</label>
                    <input
                        id="cmimi_blerjes"
                        name="cmimi_blerjes"
                        type="number"
                        min="0"
                        max="99999999.99"
                        step="0.01"
                        value="<?= h($productForm['cmimi_blerjes'] ?? '0.00') ?>"
                        required
                    >
                </div>

                <div class="field">
                    <label for="cmimi_shitjes">Çmimi i shitjes (€)</label>
                    <input
                        id="cmimi_shitjes"
                        name="cmimi_shitjes"
                        type="number"
                        min="0"
                        max="99999999.99"
                        step="0.01"
                        value="<?= h($productForm['cmimi_shitjes'] ?? '') ?>"
                        placeholder="Opsionale"
                    >
                </div>

                <div class="field">
                    <label for="stoku_minimal">Stoku minimal *</label>
                    <input
                        id="stoku_minimal"
                        name="stoku_minimal"
                        type="number"
                        min="0"
                        max="2147483647"
                        step="1"
                        value="<?= h($productForm['stoku_minimal'] ?? 5) ?>"
                        required
                    >
                    <small class="hint">
                        Shfaqet paralajmërim kur sasia arrin këtë nivel ose më poshtë.
                    </small>
                </div>

                <?php if (empty($productForm['produkt_id'])): ?>
                    <div class="field">
                        <label for="sasia_fillestare">Sasia fillestare *</label>
                        <input
                            id="sasia_fillestare"
                            name="sasia"
                            type="number"
                            min="0"
                            max="2147483647"
                            step="1"
                            value="<?= h($productForm['sasia'] ?? 0) ?>"
                            required
                        >
                        <small class="hint">
                            Sasia fillestare ruhet edhe në historikun e stokut.
                        </small>
                    </div>
                <?php else: ?>
                    <div class="field">
                        <label>Stoku aktual</label>
                        <input
                            value="<?= (int)($productForm['sasia'] ?? 0) ?> njësi"
                            disabled
                        >
                        <small class="hint">
                            Përdor “Lëvizje stoku” për të ndryshuar sasinë.
                        </small>
                    </div>
                <?php endif; ?>

                <div class="field" style="grid-column:1/-1">
                    <label for="pershkrimi">Përshkrimi</label>
                    <textarea
                        id="pershkrimi"
                        name="pershkrimi"
                        placeholder="Përshkrimi, madhësia ose detaje të tjera..."
                    ><?= h($productForm['pershkrimi'] ?? '') ?></textarea>
                </div>

                <div class="field">
                    <label for="aktiv">Statusi</label>
                    <select id="aktiv" name="aktiv">
                        <option
                            value="1"
                            <?= (int)($productForm['aktiv'] ?? 1) === 1
                                ? 'selected' : '' ?>
                        >Aktiv</option>
                        <option
                            value="0"
                            <?= (int)($productForm['aktiv'] ?? 1) === 0
                                ? 'selected' : '' ?>
                        >Joaktiv</option>
                    </select>
                </div>
            </div>

            <div class="form-actions">
                <button class="btn" type="submit">
                    <?= !empty($productForm['produkt_id'])
                        ? 'Ruaj ndryshimet'
                        : 'Regjistro produktin' ?>
                </button>
                <a class="btn btn-light" href="inventari.php">Anulo</a>
            </div>
        </form>
    </section>
    <?php endif; ?>

    <?php if ($showMovementForm): ?>
    <section class="panel" id="levizja">
        <div class="panel-heading">
            <div>
                <h2>Lëvizje e re e stokut</h2>
                <p>
                    Hyrje shton njësi, dalje i zbret, ndërsa korrigjimi
                    vendos sasinë reale që ke numëruar.
                </p>
            </div>
            <a class="btn btn-light" href="inventari.php">Mbyll ✕</a>
        </div>

        <form method="POST" action="inventari.php#levizja" id="movementForm">
            <input type="hidden" name="action" value="levizje_stoku">

            <div class="fields">
                <div class="field">
                    <label for="produkt_id">Produkti *</label>
                    <select id="produkt_id" name="produkt_id" required>
                        <option value="">Zgjidh produktin</option>
                        <?php foreach ($movementProducts as $mp): ?>
                            <option
                                value="<?= (int)$mp['produkt_id'] ?>"
                                <?= (string)($movementForm['produkt_id'] ?? '') === (string)$mp['produkt_id']
                                    ? 'selected' : '' ?>
                            >
                                <?= h($mp['emri']) ?>
                                <?= (int)$mp['aktiv'] === 0 ? ' (joaktiv)' : '' ?>
                                — stok: <?= (int)$mp['sasia'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label for="lloji">Lloji i lëvizjes *</label>
                    <select id="lloji" name="lloji" required>
                        <option
                            value="hyrje"
                            <?= ($movementForm['lloji'] ?? '') === 'hyrje'
                                ? 'selected' : '' ?>
                        >Hyrje — shto në stok</option>
                        <option
                            value="dalje"
                            <?= ($movementForm['lloji'] ?? '') === 'dalje'
                                ? 'selected' : '' ?>
                        >Dalje — zbrit nga stoku</option>
                        <option
                            value="korrigjim"
                            <?= ($movementForm['lloji'] ?? '') === 'korrigjim'
                                ? 'selected' : '' ?>
                        >Korrigjim — vendos stokun real</option>
                    </select>
                </div>

                <div class="field">
                    <label for="sasia_levizjes" id="quantityLabel">Sasia *</label>
                    <input
                        id="sasia_levizjes"
                        name="sasia"
                        type="number"
                        min="1"
                        max="2147483647"
                        step="1"
                        value="<?= h($movementForm['sasia'] ?? '') ?>"
                        required
                    >
                    <small class="hint" id="quantityHint">
                        Fut numrin e njësive që po hyjnë në stok.
                    </small>
                </div>

                <div class="field">
                    <label for="arsye">Arsyeja / shënimi</label>
                    <input
                        id="arsye"
                        name="arsye"
                        maxlength="255"
                        value="<?= h($movementForm['arsye'] ?? '') ?>"
                        placeholder="P.sh. Furnizim i ri ose përdorim"
                    >
                </div>
            </div>

            <div class="form-actions">
                <button class="btn" type="submit">Ruaj lëvizjen</button>
                <a class="btn btn-light" href="inventari.php">Anulo</a>
            </div>
        </form>
    </section>
    <?php endif; ?>

    <section class="panel">
        <div class="panel-heading">
            <div>
                <h2>Rafti i produkteve</h2>
                <p>
                    Kartela kreative me sasinë, çmimet dhe gjendjen
                    aktuale të secilit produkt.
                </p>
            </div>
            <span style="color:#718075;font-size:12px">
                <?= count($products) ?> produkte
            </span>
        </div>

        <form class="toolbar" method="GET" action="inventari.php">
            <input
                class="search-input"
                type="search"
                name="kerko"
                value="<?= h($search) ?>"
                placeholder="Kërko emër, kategori ose përshkrim..."
            >

            <select class="filter-select" name="kategoria">
                <option value="">Të gjitha kategoritë</option>
                <?php foreach ($categories as $cat): ?>
                    <option
                        value="<?= h($cat) ?>"
                        <?= $categoryFilter === $cat ? 'selected' : '' ?>
                    ><?= h($cat) ?></option>
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

            <select class="filter-select" name="stoku">
                <option value="te_gjitha" <?= $stockFilter === 'te_gjitha' ? 'selected' : '' ?>>Të gjitha produktet</option>
                <option value="aktive" <?= $stockFilter === 'aktive' ? 'selected' : '' ?>>Vetëm aktivët</option>
                <option value="ulet" <?= $stockFilter === 'ulet' ? 'selected' : '' ?>>Stok i ulët</option>
                <option value="pa_stok" <?= $stockFilter === 'pa_stok' ? 'selected' : '' ?>>Pa stok</option>
                <option value="joaktive" <?= $stockFilter === 'joaktive' ? 'selected' : '' ?>>Joaktivët</option>
            </select>

            <select class="filter-select" name="rendit">
                <option value="emri" <?= $sort === 'emri' ? 'selected' : '' ?>>Emri A–Z</option>
                <option value="sasia_larte" <?= $sort === 'sasia_larte' ? 'selected' : '' ?>>Stoku më i lartë</option>
                <option value="sasia_ulet" <?= $sort === 'sasia_ulet' ? 'selected' : '' ?>>Stoku më i ulët</option>
                <option value="cmimi_blerjes" <?= $sort === 'cmimi_blerjes' ? 'selected' : '' ?>>Çmimi i blerjes</option>
                <option value="me_te_rejat" <?= $sort === 'me_te_rejat' ? 'selected' : '' ?>>Më të rejat</option>
            </select>

            <button class="btn" type="submit">Filtro</button>

            <?php if (
                $search !== '' ||
                $categoryFilter !== '' ||
                $stockFilter !== 'te_gjitha' ||
                $sort !== 'emri'
            ): ?>
                <a class="btn btn-light" href="inventari.php">Pastro</a>
            <?php endif; ?>
        </form>

        <div class="product-grid">
            <?php if ($products): ?>
                <?php foreach ($products as $product): ?>
                    <?php
                    $stock = (int)$product['sasia'];
                    $minimum = (int)$product['stoku_minimal'];
                    $active = (int)$product['aktiv'] === 1;
                    $isZero = $stock === 0;
                    $isLow = $stock <= $minimum;

                    $statusClass = !$active
                        ? 'inactive'
                        : ($isZero ? 'zero' : ($isLow ? 'low' : 'good'));

                    $statusText = !$active
                        ? 'Joaktiv'
                        : ($isZero ? 'Pa stok' : ($isLow ? 'Stok i ulët' : 'Në stok'));

                    $barPercent = $minimum > 0
                        ? min(100, ($stock / max($minimum * 2, 1)) * 100)
                        : ($stock > 0 ? 100 : 0);

                    $symbol = mb_strtoupper(
                        mb_substr((string)$product['emri'], 0, 1, 'UTF-8'),
                        'UTF-8'
                    );
                    ?>

                    <article class="product-card">
                        <div class="product-top">
                            <div class="product-symbol"><?= h($symbol) ?></div>

                            <div class="product-title">
                                <h3><?= h($product['emri']) ?></h3>
                                <p>
                                    <?= h($product['kategoria'] ?: 'Pa kategori') ?>
                                    · ID #<?= (int)$product['produkt_id'] ?>
                                </p>
                            </div>

                            <span class="status <?= h($statusClass) ?>">
                                <?= h($statusText) ?>
                            </span>
                        </div>

                        <div class="product-description">
                            <?= h(
                                $product['pershkrimi']
                                ?: 'Ende nuk është shtuar përshkrimi i produktit.'
                            ) ?>
                        </div>

                        <div class="product-metrics">
                            <div class="metric">
                                <div class="metric-label">Sasia në stok</div>
                                <div class="metric-value"><?= $stock ?></div>
                            </div>

                            <div class="metric">
                                <div class="metric-label">Stoku minimal</div>
                                <div class="metric-value"><?= $minimum ?></div>
                            </div>

                            <div class="metric">
                                <div class="metric-label">Çmimi i blerjes</div>
                                <div class="metric-value" style="font-size:15px">
                                    <?= money($product['cmimi_blerjes']) ?>
                                </div>
                            </div>

                            <div class="metric">
                                <div class="metric-label">Çmimi i shitjes</div>
                                <div class="metric-value" style="font-size:15px">
                                    <?= $product['cmimi_shitjes'] !== null
                                        ? money($product['cmimi_shitjes'])
                                        : '—' ?>
                                </div>
                            </div>
                        </div>

                        <div class="stock-line">
                            <span>Niveli i stokut</span>
                            <span><?= $stock ?> njësi</span>
                        </div>

                        <div class="stock-track">
                            <div
                                class="stock-fill <?= $isZero ? 'zero' : ($isLow ? 'low' : '') ?>"
                                style="width:<?= $barPercent ?>%"
                            ></div>
                        </div>

                        <div class="product-actions">
                            <a
                                class="product-action"
                                href="inventari.php?edit=<?= (int)$product['produkt_id'] ?>#produkti"
                            >✎ Ndrysho</a>

                            <a
                                class="product-action"
                                href="inventari.php?levizje=1&amp;produkt=<?= (int)$product['produkt_id'] ?>#levizja"
                            >↕ Stoku</a>

                            <form
                                method="POST"
                                action="inventari.php"
                                onsubmit="return confirm('A dëshiron ta <?= $active ? 'çaktivizosh' : 'aktivizosh' ?> këtë produkt?');"
                            >
                                <input type="hidden" name="action" value="ndrysho_status">
                                <input
                                    type="hidden"
                                    name="produkt_id"
                                    value="<?= (int)$product['produkt_id'] ?>"
                                >
                                <button
                                    class="product-action <?= $active ? 'muted' : '' ?>"
                                    type="submit"
                                ><?= $active ? '○ Çaktivizo' : '✓ Aktivizo' ?></button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>

            <?php else: ?>
                <div class="empty">
                    <strong>Nuk u gjet asnjë produkt.</strong>
                    <?= $search !== '' ||
                        $categoryFilter !== '' ||
                        $stockFilter !== 'te_gjitha'
                        ? 'Ndrysho kërkimin ose pastro filtrat.'
                        : 'Shto produktin e parë për ta mbushur inventarin.' ?>

                    <div style="margin-top:16px">
                        <a class="btn" href="inventari.php?shto=1#produkti">
                            + Shto produkt
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="panel">
        <div class="panel-heading">
            <div>
                <h2>Historiku i stokut</h2>
                <p>
                    Lëvizjet e fundit të regjistruara në databazë.
                </p>
            </div>

            <a class="btn btn-light" href="inventari.php?levizje=1#levizja">
                + Regjistro lëvizje
            </a>
        </div>

        <?php if ($movementHistory): ?>
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>Produkti</th>
                        <th>Lloji</th>
                        <th>Ndryshimi i stokut</th>
                        <th>Arsyeja</th>
                        <th>Data</th>
                    </tr>
                    </thead>

                    <tbody>
                    <?php foreach ($movementHistory as $movement): ?>
                        <?php
                        $type = $movement['lloji'];
                        $qty = (int)$movement['sasia'];

                        $movementLabel = $type === 'hyrje'
                            ? 'Hyrje'
                            : ($type === 'dalje' ? 'Dalje' : 'Korrigjim');

                        if ($type === 'dalje') {
                            $qtyLabel = '−' . abs($qty);
                        } elseif ($type === 'korrigjim') {
                            $qtyLabel = ($qty > 0 ? '+' : '') . $qty;
                        } else {
                            $qtyLabel = '+' . abs($qty);
                        }
                        ?>

                        <tr>
                            <td>
                                <strong><?= h($movement['produkt_emri']) ?></strong>
                                <div style="margin-top:3px;color:#718075;font-size:10px">
                                    ID #<?= (int)$movement['produkt_id'] ?>
                                </div>
                            </td>

                            <td>
                                <span class="movement-type <?= h($type) ?>">
                                    <?= h($movementLabel) ?>
                                </span>
                            </td>

                            <td style="font-weight:700">
                                <?= h($qtyLabel) ?> njësi
                            </td>

                            <td><?= h($movement['arsye'] ?: '—') ?></td>

                            <td>
                                <?= h(date(
                                    'd.m.Y H:i',
                                    strtotime($movement['data_levizjes'])
                                )) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty">
                <strong>Historiku është ende bosh.</strong>
                Kur regjistron stok fillestar ose lëvizjen e parë,
                historiku shfaqet këtu.
            </div>
        <?php endif; ?>
    </section>

    <footer class="footer">
        <span>LUMIÈRE Beauty Studio</span>
        <span>Beauty is in every detail.</span>
    </footer>

</main>
</div>

<script>
(function () {
    const typeSelect = document.getElementById('lloji');
    const qtyLabel = document.getElementById('quantityLabel');
    const qtyHint = document.getElementById('quantityHint');

    function updateMovementHelper() {
        if (!typeSelect || !qtyLabel || !qtyHint) {
            return;
        }

        if (typeSelect.value === 'korrigjim') {
            qtyLabel.textContent = 'Stoku real pas numërimit *';
            qtyHint.textContent =
                'Shkruaj sasinë reale që ke numëruar. Sistemi ruan diferencën si korrigjim.';
        } else if (typeSelect.value === 'dalje') {
            qtyLabel.textContent = 'Sasia që del *';
            qtyHint.textContent =
                'Fut numrin e njësive që po dalin. Stoku nuk lejohet të bëhet negativ.';
        } else {
            qtyLabel.textContent = 'Sasia që hyn *';
            qtyHint.textContent =
                'Fut numrin e njësive që po shtohen në stok.';
        }
    }

    if (typeSelect) {
        typeSelect.addEventListener('change', updateMovementHelper);
        updateMovementHelper();
    }
})();
</script>

</body>
</html>