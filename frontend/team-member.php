<?php

require_once __DIR__ . '/../config/database.php';

/* foto e punojsit */
function lumiereTeamPhoto(?string $photo): string
{
    $photo = trim((string)$photo);

    if ($photo === '') {
        return '';
    }

    if (preg_match('~^https?://~i', $photo)) {
        return $photo;
    }

    $projectRoot = dirname(__DIR__);
    $frontendRoot = __DIR__;

    $cleanPath = str_replace('\\', '/', $photo);
    $cleanPath = ltrim($cleanPath, '/');
    $fileName = basename($cleanPath);

    $candidates = [
        [
            $projectRoot . '/admin/uploads/stafi/' . $fileName,
            '../admin/uploads/stafi/' . $fileName
        ],
        [
            $frontendRoot . '/' . $cleanPath,
            $cleanPath
        ],
        [
            $projectRoot . '/' . $cleanPath,
            '../' . $cleanPath
        ],
        [
            $frontendRoot . '/uploads/stafi/' . $fileName,
            'uploads/stafi/' . $fileName
        ],
        [
            $projectRoot . '/uploads/stafi/' . $fileName,
            '../uploads/stafi/' . $fileName
        ],
        [
            $frontendRoot . '/assets/uploads/stafi/' . $fileName,
            'assets/uploads/stafi/' . $fileName
        ],
        [
            $projectRoot . '/assets/uploads/stafi/' . $fileName,
            '../assets/uploads/stafi/' . $fileName
        ],
        [
            $frontendRoot . '/assets/images/staff/' . $fileName,
            'assets/images/staff/' . $fileName
        ],
        [
            $projectRoot . '/assets/images/staff/' . $fileName,
            '../assets/images/staff/' . $fileName
        ],
        [
            $projectRoot . '/uploads/' . $fileName,
            '../uploads/' . $fileName
        ]
    ];

    foreach ($candidates as [$diskPath, $urlPath]) {
        $realFile = realpath($diskPath);

        if ($realFile !== false && is_file($realFile)) {
            return implode(
                '/',
                array_map('rawurlencode', explode('/', $urlPath))
            );
        }
    }

    return '';
}

/* ID e punojsit */
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    header('Location: team.php');
    exit;
}

/* marrja e te dhenave te punojsit */
$stmt = $pdo->prepare("
    SELECT
        staf_id,
        emri,
        mbiemri,
        foto,
        pozita,
        data_punesimit,
        biografia,
        vitet_pervojes,
        specializimet,
        certifikimet,
        trajnimet,
        gjuhet,
        filozofia_punes
    FROM stafi
    WHERE staf_id = :id
      AND statusi = 'Aktive'
    LIMIT 1
");

$stmt->execute([
    ':id' => $id
]);

$person = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$person) {
    http_response_code(404);
    exit('Punonjësi nuk u gjet.');
}

/* te dhenat baze */
$photoUrl = lumiereTeamPhoto($person['foto'] ?? '');

$fullName = trim(
    ($person['emri'] ?? '') . ' ' .
    ($person['mbiemri'] ?? '')
);

$initials = mb_strtoupper(
    mb_substr($person['emri'] ?? '', 0, 1) .
    mb_substr($person['mbiemri'] ?? '', 0, 1)
);

/* sherbimet e realizuara */
$servicesStmt = $pdo->prepare("
    SELECT
        sh.sherbim_id,
        sh.emri,
        COUNT(t.termin_id) AS numri
    FROM terminet t
    INNER JOIN sherbimet sh
        ON sh.sherbim_id = t.sherbim_id
    WHERE t.staf_id = :id
      AND LOWER(TRIM(t.statusi)) = 'përfunduar'
    GROUP BY sh.sherbim_id, sh.emri
    ORDER BY numri DESC
");

$servicesStmt->execute([
    ':id' => $id
]);

$performedServices = $servicesStmt->fetchAll(PDO::FETCH_ASSOC);

/* vlersimet */
$statsStmt = $pdo->prepare("
    SELECT
        COUNT(*) AS total_vleresime,
        ROUND(AVG(v.nota), 1) AS mesatarja
    FROM vleresimet v
    INNER JOIN terminet t
        ON t.termin_id = v.termin_id
    WHERE t.staf_id = :id
      AND v.statusi = 'publikuar'
");

$statsStmt->execute([
    ':id' => $id
]);

$rating = $statsStmt->fetch(PDO::FETCH_ASSOC);

/* info profesionale */
$profileSections = [
    'Biografia' => $person['biografia'] ?? '',
    'Specializimet' => $person['specializimet'] ?? '',
    'Certifikimet' => $person['certifikimet'] ?? '',
    'Trajnimet' => $person['trajnimet'] ?? '',
    'Gjuhët' => $person['gjuhet'] ?? '',
    'Filozofia e punës' => $person['filozofia_punes'] ?? ''
];

$hasProfileDetails = false;

foreach ($profileSections as $content) {
    if (trim((string)$content) !== '') {
        $hasProfileDetails = true;
        break;
    }
}

$pageTitle = $fullName . ' | LUMIÈRE Beauty Studio';

require_once __DIR__ . '/includes/header.php';

?>

<link rel="stylesheet" href="assets/css/team.css?v=5">

<main class="team-page">

    <!-- profili kryesor -->

    <section class="member-profile">

        <a href="team.php" class="member-back">
            ← KTHEHU TE EKIPI
        </a>

        <div class="member-profile-grid">

            <div class="member-profile-photo">

                <?php if ($photoUrl !== ''): ?>

                    <img
                        src="<?= htmlspecialchars($photoUrl, ENT_QUOTES, 'UTF-8') ?>"
                        alt="<?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?>"
                        class="member-profile-image"
                        loading="eager"
                    >

                <?php else: ?>

                    <div class="team-photo-placeholder">
                        <span>
                            <?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?>
                        </span>
                        <small>LUMIÈRE</small>
                    </div>

                <?php endif; ?>

            </div>

            <div class="member-profile-info">

                <span class="team-eyebrow">
                    <span></span>
                    PROFILI I PROFESIONISTIT
                </span>

                <h1>
                    <?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?>
                </h1>

                <p class="member-position">
                    <?= htmlspecialchars($person['pozita'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                </p>

                <?php if (!empty($person['data_punesimit'])): ?>

                    <p class="member-joined">
                        Pjesë e ekipit që nga:

                        <?= htmlspecialchars(
                            date('d.m.Y', strtotime($person['data_punesimit'])),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </p>

                <?php endif; ?>

                <div class="member-statistics">

                    <div>
                        <strong>
                            <?= (int)($rating['total_vleresime'] ?? 0) ?>
                        </strong>
                        <span>Vlerësime</span>
                    </div>

                    <div>
                        <strong>
                            <?= ($rating['mesatarja'] ?? null) !== null
                                ? '★ ' . htmlspecialchars(
                                    (string)$rating['mesatarja'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                )
                                : '—'
                            ?>
                        </strong>
                        <span>Mesatarja</span>
                    </div>

                    <?php if (
                        $person['vitet_pervojes'] !== null &&
                        $person['vitet_pervojes'] !== ''
                    ): ?>

                        <div>
                            <strong>
                                <?= (int)$person['vitet_pervojes'] ?>
                            </strong>

                            <span>
                                <?= (int)$person['vitet_pervojes'] === 1
                                    ? 'Vit përvojë'
                                    : 'Vite përvojë'
                                ?>
                            </span>
                        </div>

                    <?php endif; ?>

                </div>

                <a href="booking.php" class="team-hero-button">
                    REZERVO TERMIN
                    <span>↗</span>
                </a>

            </div>

        </div>

    </section>

    <!-- info profesionale -->

    <?php if ($hasProfileDetails): ?>

        <section class="member-services">

            <span class="team-eyebrow centered">
                <span></span>
                NJOHUNI ME PROFESIONISTEN
                <span></span>
            </span>

            <h2>Më shumë rreth profesionistes</h2>

            <div class="member-service-list">

                <?php foreach ($profileSections as $heading => $content): ?>

                    <?php if (trim((string)$content) !== ''): ?>

                        <div class="member-service-item">

                            <span>

                                <strong>
                                    <?= htmlspecialchars(
                                        $heading,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </strong>

                                <br>

                                <span style="white-space:pre-line; line-height:1.8;">

                                    <?= htmlspecialchars(
                                        trim((string)$content),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </span>

                            </span>

                        </div>

                    <?php endif; ?>

                <?php endforeach; ?>

            </div>

        </section>

    <?php endif; ?>

    <!-- sherbimet e realizuara -->

    <section class="member-services">

        <span class="team-eyebrow centered">
            <span></span>
            PËRVOJA PROFESIONALE
            <span></span>
        </span>

        <h2>Shërbimet e realizuara</h2>

        <?php if (empty($performedServices)): ?>

            <p class="member-no-services">
                Nuk ka ende shërbime të përfunduara për këtë punonjës.
            </p>

        <?php else: ?>

            <div class="member-service-list">

                <?php foreach ($performedServices as $service): ?>

                    <div class="member-service-item">

                        <span>
                            <?= htmlspecialchars(
                                $service['emri'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </span>

                        <strong>
                            <?= (int)$service['numri'] ?> termine
                        </strong>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </section>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>