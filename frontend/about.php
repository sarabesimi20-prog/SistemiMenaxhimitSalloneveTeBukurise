
<?php
$currentPage = basename($_SERVER['PHP_SELF']);

require_once __DIR__ . '/includes/header.php';
?>

<link rel="stylesheet" href="assets/css/about.css">

<main class="about-page">

    <!-hyrja editoriale->
    <section class="about-hero">

        <div class="about-hero-top">
            <span class="about-overline">ARTI I BUKURISË</span>
            <span class="about-hero-index">KRIJUAR ME PASION · LUMIÈRE</span>
        </div>

        <div class="about-hero-content">

            <div class="about-hero-title">
                <span class="about-small-heading">NJË PËRKUFIZIM TJETËR I BUKURISË</span>

                <h1>
                    Më shumë se<br>
                    bukuri<span class="about-title-dot">.</span>
                    <em>Një ndjenjë.</em>
                </h1>

                <p>
                    Një hapësirë e krijuar për të festuar individualitetin,
                    frymëzuar vetëbesimin dhe për ta bërë çdo moment
                    pak më të bukur.
                </p>

                <a href="services.php" class="about-outline-link">
                    ZBULO SHËRBIMET TONA
                    <span>↗</span>
                </a>
            </div>

            <div class="about-hero-art" aria-hidden="true">

                <div class="about-art-orbit about-art-orbit-one"></div>
                <div class="about-art-orbit about-art-orbit-two"></div>

                <div class="about-art-center">
                    <span class="about-art-letter">L.</span>
                    <span class="about-art-caption">LUMIÈRE BEAUTY STUDIO</span>
                </div>

                <span class="about-art-number">01</span>
                <span class="about-art-side-text">BUKURI · VETËBESIM · TI</span>

                <svg class="about-hero-flower"
                     viewBox="0 0 180 180"
                     fill="none"
                     xmlns="http://www.w3.org/2000/svg">
                    <path d="M90 153C51 117 48 72 90 25C132 72 129 117 90 153Z"/>
                    <path d="M90 153C38 137 18 103 28 64C72 69 94 104 90 153Z"/>
                    <path d="M90 153C142 137 162 103 152 64C108 69 86 104 90 153Z"/>
                    <path d="M90 153C67 112 70 71 90 25"/>
                    <path d="M90 153C82 112 59 85 28 64"/>
                    <path d="M90 153C98 112 121 85 152 64"/>
                    <path d="M90 153V170"/>
                </svg>

            </div>

        </div>

        <div class="about-hero-bottom">
            <span>01 — HISTORIA JONË</span>
            <span>KRIJUAR ME PËRKUSHTIM</span>
            <span>ZBRIT POSHTË PËR TË ZBULUAR ↓</span>
        </div>

    </section>


    <!-- historia jon -->
    <section class="about-story">

        <div class="about-section-label">
            <span>02</span>
            <span>FILLIMI</span>
        </div>

        <div class="about-story-grid">

            <div class="about-story-heading">
                <span class="about-mini-label">NJË HISTORI PASIONI</span>

                <h2>
                    Bukuria fillon<br>
                    me <em>një vizion.</em>
                </h2>

                <div class="about-story-decoration" aria-hidden="true">
                    <span>L</span>
                    <i></i>
                    <span>01</span>
                </div>
            </div>

            <div class="about-story-copy">
                <p class="about-story-lead">
                    LUMIÈRE u krijua si diçka më shumë se një studio bukurie.
                    Është një hapësirë ku kujdesi, kreativiteti dhe
                    vetëbesimi bashkohen.
                </p>

                <p>
                    Ne besojmë se bukuria është personale. Ajo gjendet
                    në detajet më të vogla, në ndjenjën e të qenit e
                    përkujdesur dhe në vetëbesimin që vjen kur i kushton
                    pak kohë vetes.
                </p>

                <p>
                    Çdo pjesë e përvojës LUMIÈRE udhëhiqet nga një ide
                    e thjeshtë: të bëjmë që secila kliente të ndihet
                    e vlerësuar, rehat dhe e bukur në mënyrën e saj.
                </p>

                <span class="about-signature">Filozofia e LUMIÈRE</span>
            </div>

        </div>

        <div class="about-story-divider">
            <span>PASION</span>
            <span class="about-divider-star">✳</span>
            <span>DETAJE</span>
            <span class="about-divider-star">✳</span>
            <span>VETËBESIM</span>
            <span class="about-divider-star">✳</span>
            <span>BUKURI</span>
        </div>

    </section>


    <!-- filozofia jone-->
    <section class="about-philosophy">

        <div class="about-philosophy-heading">
            <div class="about-section-label">
                <span>03</span>
                <span>NË ÇFARË BESOJMË</span>
            </div>

            <h2>
                Filozofia jonë<br>
                <em>është e thjeshtë.</em>
            </h2>

            <p>
                Nuk bëhet fjalë për të ndryshuar atë që je,
                por për të vlerësuar gjithçka që të bën unike.
            </p>
        </div>

        <div class="about-values">

            <article class="about-value">
                <div class="about-value-top">
                    <span class="about-value-number">01</span>
                    <span class="about-value-symbol">✳</span>
                </div>

                <div class="about-value-art about-value-art-one" aria-hidden="true">
                    <span class="about-value-ring"></span>
                    <span class="about-value-ring"></span>
                    <span class="about-value-center">I</span>
                </div>

                <h3>Individualiteti</h3>

                <p>
                    Çdo person ka bukurinë e vet.
                    Ne e vlerësojmë individualitetin
                    dhe atë që të bën të veçantë.
                </p>

                <span class="about-value-footer">JI VETVETJA</span>
            </article>


            <article class="about-value about-value-featured">
                <div class="about-value-top">
                    <span class="about-value-number">02</span>
                    <span class="about-value-symbol">✳</span>
                </div>

                <div class="about-value-art about-value-art-two" aria-hidden="true">
                    <svg viewBox="0 0 150 150" fill="none">
                        <path d="M75 15L88 60L135 75L88 90L75 135L62 90L15 75L62 60L75 15Z"/>
                        <circle cx="75" cy="75" r="25"/>
                        <circle cx="75" cy="75" r="8"/>
                    </svg>
                </div>

                <h3>Cilësia</h3>

                <p>
                    Kujdesi profesional, vëmendja ndaj detajeve
                    dhe përkushtimi ndaj cilësisë përcjellin
                    çdo përvojë që krijojmë.
                </p>

                <span class="about-value-footer">ÇDO DETAJ KA RËNDËSI</span>
            </article>


            <article class="about-value">
                <div class="about-value-top">
                    <span class="about-value-number">03</span>
                    <span class="about-value-symbol">✳</span>
                </div>

                <div class="about-value-art about-value-art-three" aria-hidden="true">
                    <svg viewBox="0 0 150 150" fill="none">
                        <path d="M75 132C75 91 75 58 75 18"/>
                        <path d="M75 92C48 91 29 74 25 48C52 46 72 63 75 92Z"/>
                        <path d="M75 72C102 71 121 54 125 28C98 26 78 43 75 72Z"/>
                        <path d="M75 132C58 120 47 108 43 94"/>
                        <path d="M75 132C92 120 103 108 107 94"/>
                    </svg>
                </div>

                <h3>Vetëbesimi</h3>

                <p>
                    Dëshirojmë që të largohesh e freskët,
                    e vlerësuar dhe më e sigurt në
                    bukurinë tënde natyrale.
                </p>

                <span class="about-value-footer">NDIHU BUKUR</span>
            </article>

        </div>

    </section>


    <!-- pervoja -->
    <section class="about-experience">

        <div class="about-experience-left">
            <span class="about-mini-label">MË SHUMË SE NJË VIZITË</span>

            <h2>
                Momenti yt.<br>
                Hapësira jote.<br>
                <em>Përvoja jote.</em>
            </h2>

            <p>
                Që nga momenti kur hyn në studion tonë,
                dëshirojmë që çdo detaj të jetë i menduar me kujdes.
                Një atmosferë mikpritëse, përkujdesje personale
                dhe një moment i dedikuar vetëm për ty.
            </p>

            <a href="booking.php" class="about-dark-link">
                REZERVO MOMENTIN TËND
                <span>→</span>
            </a>
        </div>

        <div class="about-experience-right">

            <div class="about-experience-circle" aria-hidden="true">
                <span class="experience-circle-text">
                    KOHA JOTE · BUKURIA JOTE · MOMENTI YT ·
                </span>

                <div class="experience-circle-inner">
                    <span>Merr</span>
                    <strong>një moment</strong>
                    <span>për veten.</span>
                    <i>✳</i>
                </div>
            </div>

            <span class="about-experience-number">L / 04</span>

        </div>

    </section>


    <!-- mesazh perfundimtar-->
    <section class="about-final">

        <span class="about-final-overline">NJË KUJTESË E VOGËL NGA NE</span>

        <div class="about-final-decoration" aria-hidden="true">✳</div>

        <h2>
            Bukuria jote.<br>
            Momenti yt.<br>
            <em>Pasioni ynë.</em>
        </h2>

        <p>
            Mezi presim të të mirëpresim në botën e LUMIÈRE.
        </p>

        <div class="about-final-actions">
            <a href="services.php" class="about-final-button">
                EKSPLORO SHËRBIMET TONA <span>↗</span>
            </a>

            <a href="contact.php" class="about-final-text-link">
                NA KONTAKTO
            </a>
        </div>

        <span class="about-final-brand">
            LUMIÈRE BEAUTY STUDIO · PRIZREN
        </span>

    </section>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
