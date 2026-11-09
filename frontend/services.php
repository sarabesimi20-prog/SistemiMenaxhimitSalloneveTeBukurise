
<?php
require_once __DIR__ . '/../config/database.php';

$pageTitle = 'Shërbimet | LUMIÈRE Beauty Studio';

$services = [];
$dbError = false;

function serviceColumn(array $columns, array $candidates): ?string
{
    foreach ($candidates as $candidate) {
        if (in_array($candidate, $columns, true)) {
            return $candidate;
        }
    }

    return null;
}

function serviceValue(array $row, ?string $column, string $default = ''): string
{
    if (!$column || !isset($row[$column])) {
        return $default;
    }

    return trim((string) $row[$column]);
}

try {
    $columnInfo = $pdo->query("SHOW COLUMNS FROM sherbimet")->fetchAll(PDO::FETCH_ASSOC);
    $columns = array_column($columnInfo, 'Field');

    $idColumn = serviceColumn($columns, ['id', 'id_sherbimi', 'sherbimi_id']);
    $nameColumn = serviceColumn($columns, ['emri', 'emri_sherbimit', 'sherbimi', 'name']);
    $descriptionColumn = serviceColumn($columns, ['pershkrimi', 'description', 'detajet']);
    $priceColumn = serviceColumn($columns, ['cmimi', 'price', 'qmimi']);
    $durationColumn = serviceColumn($columns, ['kohezgjatja', 'kohezgjatja_minuta', 'duration', 'minuta']);
    $categoryColumn = serviceColumn($columns, ['kategoria', 'kategori', 'category']);
    $statusColumn = serviceColumn($columns, ['statusi', 'status', 'aktiv']);

    if (!$nameColumn) {
        throw new RuntimeException('Kolona e emrit të shërbimit nuk u gjet.');
    }

    $query = "SELECT * FROM sherbimet";

    if ($statusColumn) {
        $query .= " WHERE `$statusColumn` = 'aktiv'";
    }

    $query .= " ORDER BY " . ($idColumn ? "`$idColumn` DESC" : "`$nameColumn` ASC");

    $rows = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $row) {
        $name = serviceValue($row, $nameColumn, 'Shërbim bukurie');

        $description = serviceValue(
            $row,
            $descriptionColumn,
            'Një përvojë e krijuar për kujdesin dhe bukurinë tënde.'
        );

        $price = serviceValue($row, $priceColumn);
        $duration = serviceValue($row, $durationColumn);
        $category = serviceValue($row, $categoryColumn);

        if ($category === '') {
            $lowerName = mb_strtolower($name);

            if (preg_match('/flok|hair|ngjyros|prerje|stilim/u', $lowerName)) {
                $category = 'Flokët';
            } elseif (preg_match('/thonj|manik|pedik|nail/u', $lowerName)) {
                $category = 'Thonjtë';
            } elseif (preg_match('/vetull|qerpik|lash|brow/u', $lowerName)) {
                $category = 'Qerpikët & Vetullat';
            } elseif (preg_match('/fytyr|facial|pastrim|skin/u', $lowerName)) {
                $category = 'Kujdesi për fytyrën';
            } else {
                $category = 'Trajtime të tjera';
            }
        }

        $services[] = [
            'id' => serviceValue($row, $idColumn),
            'name' => $name,
            'description' => $description,
            'price' => $price,
            'duration' => $duration,
            'category' => $category
        ];
    }
} catch (Throwable $e) {
    $dbError = true;
}

require_once __DIR__ . '/includes/header.php';
?>

<link rel="stylesheet" href="assets/css/services.css">

<main class="services-page">

    <!-- HERO -->
    <section class="services-hero">
        <div class="hero-decoration hero-decoration-one"></div>
        <div class="hero-decoration hero-decoration-two"></div>

        <div class="services-hero-inner">

            <div class="hero-copy">
                <span class="eyebrow">
                    <span class="eyebrow-line"></span>
                    LUMIÈRE BEAUTY STUDIO
                </span>

                <h1>
                    Bukuria jote,<br>
                    <em>arti ynë.</em>
                </h1>

                <p>
                    Çdo detaj ka rëndësi. Zbulo trajtimet tona të krijuara
                    për të nxjerrë në pah bukurinë dhe individualitetin tënd.
                </p>

                <a href="#katalogu" class="hero-link">
                    ZBULO SHËRBIMET
                    <span>↓</span>
                </a>
            </div>

            <!-- lule dekorative-->
            <div class="hero-art flower-art" aria-hidden="true">

                <div class="flower-circle"></div>

                <svg viewBox="0 0 400 440" class="flower-illustration">

                    <g fill="none" stroke="currentColor" stroke-width="1.3">

                        <path d="M200 215
                            C155 185 125 135 165 95
                            C195 65 215 110 200 215Z"/>

                        <path d="M200 215
                            C215 160 250 105 290 130
                            C325 155 270 195 200 215Z"/>

                        <path d="M200 215
                            C260 190 320 200 310 245
                            C300 285 245 260 200 215Z"/>

                        <path d="M200 215
                            C245 270 265 330 220 345
                            C180 355 185 280 200 215Z"/>

                        <path d="M200 215
                            C160 275 115 300 95 260
                            C75 220 140 205 200 215Z"/>

                        <path d="M200 215
                            C145 195 90 160 115 125
                            C140 90 180 155 200 215Z"/>

                        <circle cx="200" cy="215" r="17"/>
                        <circle cx="200" cy="215" r="5"/>

                    </g>

                    <g fill="none" stroke="currentColor" stroke-width="1">
                        <path d="M200 232 C195 285 205 345 180 410"/>
                        <path d="M190 325 C155 300 130 315 135 345 C160 350 180 340 190 325Z"/>
                        <path d="M195 365 C225 335 260 340 265 365 C240 385 215 380 195 365Z"/>
                    </g>

                    <g fill="currentColor" opacity=".55">
                        <circle cx="90" cy="100" r="2"/>
                        <circle cx="310" cy="90" r="2"/>
                        <circle cx="320" cy="320" r="2"/>
                        <circle cx="75" cy="310" r="2"/>
                    </g>

                </svg>

                <span class="flower-caption">BLOOM IN YOUR OWN WAY</span>
            </div>

        </div>

        <div class="hero-bottom">
            <span>01 — 05</span>
            <span>BUKURI • KUJDES • PËRVOJË</span>
        </div>
    </section>

    <!--intro-->
    <section class="services-intro">
        <div class="intro-label">PËRVOJA LUMIÈRE</div>

        <div class="intro-content">
            <h2>
                Një moment për veten.<br>
                <em>Një ndjesi që mbetet.</em>
            </h2>

            <p>
                Nga kujdesi i përditshëm deri te transformimet speciale,
                çdo shërbim realizohet me vëmendje ndaj detajeve dhe
                përkushtim ndaj përvojës sate.
            </p>
        </div>
    </section>

    <!-- kategorite dhe sherbimet -->
    <section class="category-section" id="katalogu">

        <div class="section-heading">
            <div>
                <span class="eyebrow">KATALOGU YNË</span>
                <h2>Gjej <em>momentin tënd.</em></h2>
            </div>

            <p>
                Zgjidh kategorinë dhe zbulo trajtimet
                që kemi përgatitur për ty.
            </p>
        </div>

        <div class="category-navigation" role="group" aria-label="Kategoritë e shërbimeve">

            <button class="category-button active" data-filter="all">
                Të gjitha <span>01</span>
            </button>

            <button class="category-button" data-filter="Flokët">
                Flokët <span>02</span>
            </button>

            <button class="category-button" data-filter="Thonjtë">
                Thonjtë <span>03</span>
            </button>

            <button class="category-button" data-filter="Qerpikët & Vetullat">
                Qerpikët & Vetullat <span>04</span>
            </button>

            <button class="category-button" data-filter="Kujdesi për fytyrën">
                Fytyra <span>05</span>
            </button>

            <button class="category-button" data-filter="Trajtime të tjera">
                Të tjera <span>06</span>
            </button>

        </div>

        <div class="services-catalog">

            <?php if ($dbError): ?>

                <div class="services-empty">
                    <span>01 / DATABASE</span>
                    <h3>Nuk mundëm t'i ngarkojmë shërbimet.</h3>
                    <p>Kontrollo tabelën sherbimet dhe lidhjen me databazën.</p>
                </div>

            <?php elseif (empty($services)): ?>

                <div class="services-empty">
                    <span>01 / LUMIÈRE</span>
                    <h3>Shërbimet po përgatiten.</h3>
                    <p>Së shpejti do të gjeni këtu të gjitha trajtimet tona.</p>
                </div>

            <?php else: ?>

                <?php foreach ($services as $index => $service): ?>

                    <article
                        class="service-item"
                        data-category="<?= htmlspecialchars($service['category'], ENT_QUOTES, 'UTF-8') ?>"
                    >

                        <div class="service-index">
                            <?= str_pad((string)($index + 1), 2, '0', STR_PAD_LEFT) ?>
                        </div>

                        <div class="service-main">

                            <span class="service-category">
                                <?= htmlspecialchars($service['category'], ENT_QUOTES, 'UTF-8') ?>
                            </span>

                            <h3>
                                <?= htmlspecialchars($service['name'], ENT_QUOTES, 'UTF-8') ?>
                            </h3>

                            <p>
                                <?= htmlspecialchars($service['description'], ENT_QUOTES, 'UTF-8') ?>
                            </p>

                        </div>

                        <div class="service-details">

                            <?php if ($service['duration'] !== ''): ?>
                                <span class="service-duration">
                                    <span class="detail-label">KOHËZGJATJA</span>
                                    <?= htmlspecialchars($service['duration'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            <?php endif; ?>

                            <?php if ($service['price'] !== ''): ?>
                                <span class="service-price">
                                    <span class="detail-label">ÇMIMI</span>
                                    <?= htmlspecialchars($service['price'], ENT_QUOTES, 'UTF-8') ?>
                                    <small>€</small>
                                </span>
                            <?php endif; ?>

                        </div>

                        <a
                            href="booking.php"
                            class="service-book"
                            aria-label="Rezervo <?= htmlspecialchars($service['name'], ENT_QUOTES, 'UTF-8') ?>"
                        >
                            <span>↗</span>
                        </a>

                    </article>

                <?php endforeach; ?>

            <?php endif; ?>

        </div>

        <div class="catalog-footer">
            <span>GJITHMONË ME KUJDES PËR TY</span>
            <span><?= count($services) ?> SHËRBIME</span>
        </div>

    </section>

    <!-- pervoja jote-->
    <section class="beauty-journey">

        <div class="journey-heading">
            <span class="eyebrow">SI FUNKSIONON</span>
            <h2>Përvoja jote,<br><em>hap pas hapi.</em></h2>
        </div>

        <div class="journey-steps">

            <div class="journey-step">
                <span class="step-number">01</span>
                <div class="step-symbol">✳</div>
                <h3>Zgjidh shërbimin</h3>
                <p>Gjej trajtimin që i përshtatet dëshirave dhe nevojave të tua.</p>
            </div>

            <div class="journey-step">
                <span class="step-number">02</span>
                <div class="step-symbol">◇</div>
                <h3>Zgjidh terminin</h3>
                <p>Përcakto ditën dhe kohën që të përshtatet më së miri.</p>
            </div>

            <div class="journey-step">
                <span class="step-number">03</span>
                <div class="step-symbol">✧</div>
                <h3>Shijo përvojën</h3>
                <p>Ne kujdesemi për detajet. Ti shijo momentin tënd.</p>
            </div>

        </div>

        <a href="booking.php" class="journey-cta">
            REZERVO TERMININ TËND <span>↗</span>
        </a>

    </section>

    <!-- perfundimi-->
    <section class="services-final">

        <div class="final-decoration">L</div>

        <span class="eyebrow">LUMIÈRE BEAUTY STUDIO</span>

        <h2>
            Ti meriton<br>
            <em>të shkëlqesh.</em>
        </h2>

        <p>
            Bukuria nuk është vetëm ajo që shihet.
            Është edhe mënyra si ndihesh.
        </p>

        <a href="booking.php">
            REZERVO PËRVOJËN TËNDE <span>↗</span>
        </a>

    </section>

</main>

<script src="assets/js/services.js"></script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
