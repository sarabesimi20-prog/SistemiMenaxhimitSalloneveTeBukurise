
<?php

require_once __DIR__ . '/../config/database.php';

$pageTitle = 'Ekipi ynë | LUMIÈRE Beauty Studio';

$search = trim($_GET['kerko'] ?? '');
$team = [];
$dbError = false;

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
        ],
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

try {
    $sql = "
        SELECT
            staf_id,
            emri,
            mbiemri,
            foto,
            pozita,
            data_punesimit
        FROM stafi
        WHERE statusi = 'Aktive'
    ";

    $params = [];

    if ($search !== '') {
        $sql .= "
            AND (
                emri LIKE :emri
                OR mbiemri LIKE :mbiemri
                OR pozita LIKE :pozita
            )
        ";

        $term = '%' . $search . '%';

        $params = [
            ':emri' => $term,
            ':mbiemri' => $term,
            ':pozita' => $term
        ];
    }

    $sql .= " ORDER BY emri ASC, mbiemri ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $team = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $dbError = true;
}

require_once __DIR__ . '/includes/header.php';

?>

<link rel="stylesheet" href="assets/css/team.css">

<main class="team-page">

    <section class="team-hero">

        <div class="team-hero-inner">

            <div class="team-hero-content">

                <span class="team-eyebrow">
                    <span></span>
                    LUMIÈRE BEAUTY STUDIO
                </span>

                <h1>Ekipi <em>ynë.</em></h1>

                <p>
                    Profesionalizëm, pasion dhe kujdes në çdo detaj.
                    Njihuni me njerëzit që e bëjnë përvojën tuaj në
                    LUMIÈRE kaq të veçantë.
                </p>

                <a href="#ekipi" class="team-hero-button">
                    NJIHUNI ME EKIPIN <span>↓</span>
                </a>

            </div>

            <div class="team-hero-art" aria-hidden="true">

                <div class="team-flower-circle"></div>

                <svg viewBox="0 0 400 440" class="team-flower-svg">
                    <g fill="none" stroke="currentColor" stroke-width="1.3">
                        <path d="M200 215 C155 185 125 135 165 95 C195 65 215 110 200 215Z"/>
                        <path d="M200 215 C215 160 250 105 290 130 C325 155 270 195 200 215Z"/>
                        <path d="M200 215 C260 190 320 200 310 245 C300 285 245 260 200 215Z"/>
                        <path d="M200 215 C245 270 265 330 220 345 C180 355 185 280 200 215Z"/>
                        <path d="M200 215 C160 275 115 300 95 260 C75 220 140 205 200 215Z"/>
                        <path d="M200 215 C145 195 90 160 115 125 C140 90 180 155 200 215Z"/>
                        <circle cx="200" cy="215" r="17"/>
                        <circle cx="200" cy="215" r="5"/>
                        <path d="M200 232 C195 285 205 345 180 410"/>
                        <path d="M190 325 C155 300 130 315 135 345 C160 350 180 340 190 325Z"/>
                        <path d="M195 365 C225 335 260 340 265 365 C240 385 215 380 195 365Z"/>
                    </g>
                </svg>

                <span>BEAUTY BLOOMS HERE</span>

            </div>

        </div>

        <div class="team-hero-bottom">
            <span>01 — LUMIÈRE</span>
            <span>PEOPLE • PASSION • BEAUTY</span>
        </div>

    </section>

    <section class="team-values">

        <div class="team-value">
            <span>◇</span>
            <h3>EKSPERTIZË</h3>
            <p>Profesionistë me përvojë dhe trajnim të vazhdueshëm.</p>
        </div>

        <div class="team-value">
            <span>♡</span>
            <h3>PËRKUSHTIM</h3>
            <p>Kujdes i personalizuar për çdo kliente.</p>
        </div>

        <div class="team-value">
            <span>✧</span>
            <h3>CILËSI</h3>
            <p>Standarde të larta dhe rezultate të qëndrueshme.</p>
        </div>

    </section>

    <section class="team-members" id="ekipi">

        <div class="team-heading">

            <span class="team-eyebrow centered">
                <span></span>
                EKIPI LUMIÈRE
                <span></span>
            </span>

            <h2>
                Njihuni me <em>profesionistët</em> tanë
            </h2>

            <div class="team-heading-flower">✿</div>

            <p>
                Çdo anëtar i ekipit tonë sjell talent, kreativitet
                dhe dashuri për bukurinë.
            </p>

        </div>

        <form method="GET" action="team.php" class="team-search">

            <div class="team-search-input">
                <span>⌕</span>

                <input
                    type="text"
                    name="kerko"
                    placeholder="Kërko punonjësin..."
                    value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"
                >
            </div>

            <button type="submit">KËRKO</button>

            <?php if ($search !== ''): ?>
                <a href="team.php">Pastro</a>
            <?php endif; ?>

        </form>

        <?php if ($dbError): ?>

            <div class="team-empty">
                <h3>Ndodhi një gabim gjatë ngarkimit të ekipit.</h3>
                <p>Kontrollo lidhjen me databazën.</p>
            </div>

        <?php elseif (empty($team)): ?>

            <div class="team-empty">
                <h3>
                    <?= $search !== ''
                        ? 'Nuk u gjet asnjë punonjës.'
                        : 'Nuk ka punonjës aktivë për momentin.' ?>
                </h3>
                <p>Provo një kërkim tjetër.</p>
            </div>

        <?php else: ?>

            <div class="team-grid">

                <?php foreach ($team as $index => $person): ?>

                    <?php
                    $photoUrl = lumiereTeamPhoto($person['foto'] ?? '');

                    $fullName = trim(
                        ($person['emri'] ?? '') . ' ' .
                        ($person['mbiemri'] ?? '')
                    );

                    $initials = mb_strtoupper(
                        mb_substr($person['emri'] ?? '', 0, 1) .
                        mb_substr($person['mbiemri'] ?? '', 0, 1)
                    );
                    ?>

                    <a
                        href="team-member.php?id=<?= (int)$person['staf_id'] ?>"
                        class="team-card"
                    >

                        <div class="team-photo-wrap">

                            <?php if ($photoUrl !== ''): ?>

                                <img
                                    src="<?= htmlspecialchars($photoUrl, ENT_QUOTES, 'UTF-8') ?>"
                                    alt="<?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?>"
                                    class="team-photo"
                                    loading="lazy"
                                >

                            <?php else: ?>

                                <div class="team-photo-placeholder">
                                    <span><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?></span>
                                    <small>LUMIÈRE</small>
                                </div>

                            <?php endif; ?>

                            <span class="team-photo-number">
                                <?= str_pad((string)($index + 1), 2, '0', STR_PAD_LEFT) ?>
                            </span>

                            <span class="team-photo-overlay">
                                SHIKO PROFILIN ↗
                            </span>

                        </div>

                        <div class="team-card-content">

                            <span class="team-position">
                                <?= htmlspecialchars($person['pozita'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                            </span>

                            <h3>
                                <?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?>
                            </h3>

                            <div class="team-card-bottom">
                                <span>Njihu më shumë me mua</span>
                                <span class="team-arrow">→</span>
                            </div>

                        </div>

                    </a>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

        <div class="team-count">
            <span>EKIPI YNË, PËRKUSHTIMI YNË</span>
            <span><?= count($team) ?> ANËTARË AKTIVË</span>
        </div>

    </section>

    <section class="team-final">

        <div class="team-final-flower">✿</div>

        <h2>
            Një ekip, një qëllim –<br>
            <em>bukuria jote.</em>
        </h2>

        <div class="team-final-content">
            <p>
                Jemi këtu për t'ju ofruar një përvojë të veçantë,
                me kujdes profesional dhe atmosferë të ngrohtë.
            </p>

            <a href="booking.php">
                REZERVO TERMININ TËND <span>↗</span>
            </a>
        </div>

    </section>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
