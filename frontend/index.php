
<?php
$pageTitle = 'LUMIÈRE Beauty Studio | The Art of Beauty';
require_once __DIR__ . '/includes/header.php';
?>

<style>
.lm-home {
    --ivory:#F8F4EE;
    --cream:#EEE5DA;
    --beige:#DCC9B8;
    --espresso:#49352C;
    --rose:#B58C7F;
    --gold:#C4A16B;
    --muted:#82736A;
    overflow:hidden;
    background:var(--ivory);
    color:var(--espresso);
    font-family:'DM Sans',sans-serif;
}

.lm-home * { box-sizing:border-box; }
.lm-home img { display:block;width:100%;height:100%;object-fit:cover; }
.lm-home a { color:inherit;text-decoration:none; }

.lm-label {
    color:var(--rose);
    font-size:9px;
    letter-spacing:3px;
    font-weight:600;
}

.lm-title {
    font-family:'Italiana','Playfair Display',serif;
    font-weight:400;
}

/* HERO — ruajmë konceptin që të pëlqeu */

.lm-hero {
    min-height:730px;
    height:min(800px,88vh);
    position:relative;
    display:flex;
    align-items:center;
    overflow:hidden;
    background:#766253;
    color:white;
}

.lm-hero-image {
    position:absolute;
    inset:0;
    width:100%;
    height:100%;
    object-fit:cover;
    filter:saturate(1.12);
}

.lm-hero-overlay {
    position:absolute;
    inset:0;
    background:
        linear-gradient(90deg,rgba(38,27,22,.76),rgba(38,27,22,.4) 50%,rgba(38,27,22,.04)),
        linear-gradient(0deg,rgba(38,27,22,.35),transparent 50%);
}

.lm-hero-content {
    position:relative;
    z-index:2;
    max-width:850px;
    padding-left:10%;
}

.lm-hero-kicker {
    color:#E8D0BB;
    font-size:9px;
    letter-spacing:4px;
    margin-bottom:30px;
}

.lm-hero h1 {
    font-family:'Italiana',serif;
    font-size:clamp(75px,9vw,125px);
    line-height:.97;
    font-weight:400;
}

.lm-hero h1 em {
    color:#E4BFB0;
    font-family:'Playfair Display',serif;
}

.lm-hero-description {
    max-width:410px;
    margin-top:28px;
    color:#F0E7DF;
    font-size:13px;
    line-height:2;
}

.lm-hero-button {
    display:inline-flex;
    gap:30px;
    align-items:center;
    margin-top:35px;
    padding:18px 23px;
    border:1px solid rgba(255,255,255,.75);
    color:white!important;
    font-size:9px;
    letter-spacing:2px;
    transition:.3s;
}

.lm-hero-button:hover {
    background:var(--ivory);
    color:var(--espresso)!important;
}

.lm-hero-top,
.lm-hero-bottom,
.lm-hero-side {
    position:absolute;
    z-index:2;
    color:#F0E3D7;
    font-size:8px;
    letter-spacing:2px;
}

.lm-hero-top { top:35px;left:6%;font-size:9px; }
.lm-hero-side { right:4%;top:45%;writing-mode:vertical-rl; }

.lm-hero-bottom {
    bottom:26px;
    left:6%;
    right:6%;
    display:flex;
    justify-content:space-between;
}

/* INTRODUCTION — tipografi editoriale */

.lm-editorial {
    padding:140px 10% 125px;
    display:grid;
    grid-template-columns:.65fr 1.35fr;
    gap:65px;
    position:relative;
}

.lm-editorial-label {
    display:flex;
    align-items:center;
    gap:13px;
    padding-top:12px;
}

.lm-editorial-line {
    width:34px;
    height:1px;
    background:var(--gold);
}

.lm-editorial-content {
    max-width:850px;
}

.lm-editorial-content h2 {
    font-family:'Italiana',serif;
    font-size:clamp(52px,6.5vw,86px);
    font-weight:400;
    line-height:1.12;
}

.lm-editorial-content h2 em {
    color:var(--rose);
}

.lm-editorial-bottom {
    display:flex;
    align-items:flex-end;
    justify-content:space-between;
    gap:30px;
    margin-top:35px;
}

.lm-editorial-bottom p {
    max-width:420px;
    color:var(--muted);
    font-size:12px;
    line-height:2.1;
}

.lm-link {
    display:inline-flex;
    align-items:center;
    gap:17px;
    padding-bottom:8px;
    border-bottom:1px solid var(--gold);
    font-size:9px;
    letter-spacing:1.6px;
    white-space:nowrap;
}

/* SERVICES — një imazh dhe listë, jo kartela */

.lm-services {
    padding:100px 8%;
    background:var(--cream);
}

.lm-services-header {
    display:flex;
    justify-content:space-between;
    align-items:end;
    gap:30px;
    margin-bottom:55px;
}

.lm-services-header h2 {
    margin-top:17px;
    font-family:'Italiana',serif;
    font-size:clamp(52px,6vw,78px);
    font-weight:400;
    line-height:1.08;
}

.lm-services-header h2 em { color:var(--rose); }

.lm-services-header > p {
    max-width:280px;
    color:var(--muted);
    font-size:12px;
    line-height:2;
}

.lm-services-layout {
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:65px;
    align-items:center;
}

.lm-services-image {
    height:650px;
    position:relative;
    overflow:hidden;
}

.lm-services-image img {
    transition:transform .8s;
}

.lm-services-image:hover img { transform:scale(1.04); }

.lm-services-image-caption {
    position:absolute;
    bottom:25px;
    left:25px;
    color:white;
    font-size:9px;
    letter-spacing:3px;
    text-shadow:0 2px 10px #49352C;
}

.lm-service-list-header {
    margin-bottom:18px;
    color:var(--rose);
    font-size:9px;
    letter-spacing:3px;
}

.lm-service-row {
    display:grid;
    grid-template-columns:42px 1fr 25px;
    gap:15px;
    align-items:center;
    padding:27px 0;
    border-top:1px solid #D6C6B9;
    transition:padding .3s;
}

.lm-service-row:last-of-type {
    border-bottom:1px solid #D6C6B9;
}

.lm-service-row:hover { padding-left:8px; }

.lm-service-number {
    color:var(--gold);
    font-size:10px;
}

.lm-service-row h3 {
    font-family:'Italiana',serif;
    font-weight:400;
    font-size:26px;
}

.lm-service-row p {
    margin-top:6px;
    color:var(--muted);
    font-size:10px;
    line-height:1.7;
}

.lm-service-arrow { color:var(--rose);font-size:20px; }

/* SPACE — fotografi panoramike */

.lm-space {
    position:relative;
    height:650px;
    background:#6F594C;
}

.lm-space img {
    filter:saturate(1.1);
}

.lm-space-overlay {
    position:absolute;
    inset:0;
    background:linear-gradient(90deg,rgba(35,25,20,.65),rgba(35,25,20,.05));
}

.lm-space-content {
    position:absolute;
    top:50%;
    left:10%;
    transform:translateY(-50%);
    max-width:550px;
    color:white;
}

.lm-space-content .lm-label { color:#E6C6AA; }

.lm-space-content h2 {
    margin-top:22px;
    font-family:'Italiana',serif;
    font-size:clamp(58px,7vw,85px);
    font-weight:400;
    line-height:1.05;
}

.lm-space-content h2 em { color:#E3BDAA; }

.lm-space-content p {
    max-width:390px;
    margin-top:23px;
    color:#F1E6DD;
    font-size:12px;
    line-height:2;
}

.lm-space-content a {
    display:inline-block;
    margin-top:28px;
    padding-bottom:8px;
    border-bottom:1px solid white;
    font-size:9px;
    letter-spacing:2px;
}

/* TEAM — portret dhe tekst editorial */

.lm-team {
    padding:130px 10%;
    display:grid;
    grid-template-columns:1fr .95fr;
    align-items:center;
    gap:9%;
}

.lm-team-photo {
    height:560px;
    position:relative;
}

.lm-team-photo img { object-position:center 35%; }

.lm-team-note {
    position:absolute;
    bottom:-25px;
    right:-25px;
    background:var(--cream);
    padding:25px 30px;
    color:var(--rose);
    font-family:'Playfair Display',serif;
    font-size:19px;
    font-style:italic;
}

.lm-team-content h2 {
    margin-top:22px;
    font-family:'Italiana',serif;
    font-weight:400;
    font-size:clamp(52px,5.5vw,75px);
    line-height:1.1;
}

.lm-team-content h2 em { color:var(--rose); }

.lm-team-content > p {
    max-width:420px;
    margin-top:25px;
    color:var(--muted);
    font-size:12px;
    line-height:2.1;
}

/* GALLERY — kompozim asimetrik */

.lm-gallery {
    padding:105px 8%;
    background:var(--cream);
}

.lm-gallery-header {
    display:flex;
    justify-content:space-between;
    align-items:end;
    gap:25px;
    margin-bottom:45px;
}

.lm-gallery-header h2 {
    margin-top:18px;
    font-family:'Italiana',serif;
    font-size:clamp(50px,6vw,77px);
    font-weight:400;
}

.lm-gallery-header h2 em { color:var(--rose); }

.lm-gallery-grid {
    display:grid;
    grid-template-columns:1.05fr .8fr 1.1fr;
    grid-template-rows:260px 260px;
    gap:15px;
}

.lm-gallery-item {
    position:relative;
    overflow:hidden;
    background:var(--beige);
}

.lm-gallery-item img {
    transition:transform .8s;
}

.lm-gallery-item:hover img { transform:scale(1.055); }

.lm-gallery-item:nth-child(1) { grid-row:1/3; }
.lm-gallery-item:nth-child(2) { grid-column:2;grid-row:1; }
.lm-gallery-item:nth-child(3) { grid-column:2;grid-row:2; }
.lm-gallery-item:nth-child(4) { grid-column:3;grid-row:1/3; }

.lm-gallery-caption {
    position:absolute;
    left:17px;
    bottom:16px;
    color:white;
    font-size:8px;
    letter-spacing:2px;
    text-shadow:0 2px 8px rgba(0,0,0,.4);
}

/* BOOKING — finale */

.lm-finale {
    padding:140px 8%;
    background:var(--espresso);
    color:var(--ivory);
    text-align:center;
}

.lm-finale .lm-label { color:var(--gold); }

.lm-finale h2 {
    margin-top:26px;
    font-family:'Italiana',serif;
    font-size:clamp(58px,7.5vw,100px);
    line-height:1.05;
    font-weight:400;
}

.lm-finale h2 em { color:#DAB7A6; }

.lm-finale > p:not(.lm-label) {
    max-width:420px;
    margin:25px auto;
    color:#D9CDC4;
    font-size:12px;
    line-height:2;
}

.lm-finale-button {
    display:inline-block;
    margin-top:17px;
    padding:19px 30px;
    border:1px solid #DAB7A6;
    color:white!important;
    font-size:9px;
    letter-spacing:2px;
    transition:.3s;
}

.lm-finale-button:hover {
    background:var(--ivory);
    color:var(--espresso)!important;
}

/* RESPONSIVE */

@media(max-width:800px) {
    .lm-hero { min-height:680px;height:85vh; }
    .lm-hero-content { padding-left:7%; }
    .lm-hero h1 { font-size:clamp(65px,12vw,90px); }
    .lm-hero-side { display:none; }

    .lm-editorial {
        grid-template-columns:1fr;
        gap:30px;
        padding:90px 7%;
    }

    .lm-editorial-bottom {
        align-items:flex-start;
        flex-direction:column;
    }

    .lm-services { padding:85px 7%; }
    .lm-services-header { align-items:flex-start;flex-direction:column; }

    .lm-services-layout {
        grid-template-columns:1fr;
        gap:35px;
    }

    .lm-services-image { height:480px; }

    .lm-space { height:550px; }

    .lm-space-content { left:7%;right:7%; }

    .lm-team {
        grid-template-columns:1fr;
        gap:65px;
        padding:90px 7%;
    }

    .lm-team-photo { height:460px;width:90%; }

    .lm-gallery { padding:85px 7%; }

    .lm-gallery-grid {
        grid-template-columns:1fr 1fr;
        grid-template-rows:250px 170px 190px;
    }

    .lm-gallery-item:nth-child(1) { grid-column:1/3;grid-row:1; }
    .lm-gallery-item:nth-child(2) { grid-column:1;grid-row:2; }
    .lm-gallery-item:nth-child(3) { grid-column:2;grid-row:2; }
    .lm-gallery-item:nth-child(4) { grid-column:1/3;grid-row:3; }
}

@media(max-width:480px) {
    .lm-hero { min-height:650px; }
    .lm-hero h1 { font-size:64px; }
    .lm-hero-content { max-width:95%; }
    .lm-hero-bottom { font-size:6px;letter-spacing:1px; }

    .lm-editorial-content h2 { font-size:51px; }
    .lm-space { height:480px; }
    .lm-space-content h2 { font-size:58px; }
    .lm-team-photo { height:370px; }
    .lm-team-note { right:-12px; }
    .lm-gallery-grid {
        grid-template-rows:220px 145px 145px;
        gap:9px;
    }
    .lm-finale { padding:95px 7%; }
}
</style>


<main class="lm-home">

    <!-- HERO -->
    <section class="lm-hero">

        <img
            class="lm-hero-image"
            src="https://images.unsplash.com/photo-1560066984-138dadb4c035?auto=format&fit=crop&w=2200&q=90"
            alt="Luxury beauty studio"
        >

        <div class="lm-hero-overlay"></div>

        <div class="lm-hero-top">
            LUMIÈRE · BEAUTY STUDIO
        </div>

        <div class="lm-hero-content">

            <p class="lm-hero-kicker">
                YOUR BEAUTY DESTINATION
            </p>

            <h1>
                The art of<br>
                <em>feeling beautiful.</em>
            </h1>

            <p class="lm-hero-description">
                Një hapësirë ku eleganca takohet me
                kujdesin dhe çdo moment shndërrohet
                në një përvojë të veçantë për ty.
            </p>

            <a href="booking.php" class="lm-hero-button">
                <span>REZERVO PËRVOJËN TËNDE</span>
                <span>↗</span>
            </a>

        </div>

        <div class="lm-hero-side">
            BEAUTY · CARE · ELEGANCE
        </div>

        <div class="lm-hero-bottom">
            <span>WELCOME TO LUMIÈRE</span>
            <span>01 — A WORLD OF BEAUTY</span>
        </div>

    </section>


    <!-- EDITORIAL INTRO -->
    <section class="lm-editorial">

        <div class="lm-editorial-label">
            <span class="lm-gold-line"></span>
            <span class="lm-label">THE PHILOSOPHY OF LUMIÈRE</span>
        </div>

        <div class="lm-editorial-content">

            <h2>
                Beauty is not just
                <em>what you see.</em>
                It is how you feel.
            </h2>

            <div class="lm-editorial-bottom">

                <p>
                    LUMIÈRE është krijuar si një destinacion
                    ku kujdesi personal, eleganca dhe
                    përkushtimi ndaj detajeve krijojnë
                    një përvojë të veçantë.
                </p>

                <a href="about.php" class="lm-link">
                    HISTORIA E LUMIÈRE <span>↗</span>
                </a>

            </div>

        </div>

    </section>


    <!-- SIGNATURE SERVICES -->
    <section class="lm-services">

        <div class="lm-services-header">

            <div>
                <p class="lm-label">A CURATED BEAUTY EXPERIENCE</p>

                <h2>
                    Find your<br>
                    <em>own ritual.</em>
                </h2>
            </div>

            <p>
                Nga kujdesi për lëkurën deri te detajet
                e bukurisë, zbulo përvojën që i përshtatet
                stilit dhe dëshirave të tua.
            </p>

        </div>


        <div class="lm-services-layout">

            <div class="lm-services-image">

                <img
                    src="https://images.unsplash.com/photo-1570172619644-dfd03ed5d881?auto=format&fit=crop&w=1300&q=90"
                    alt="Professional beauty treatment"
                    loading="lazy"
                >

                <div class="lm-services-image-caption">
                    THE ART OF SELF CARE
                </div>

            </div>


            <div class="lm-service-list">

                <p class="lm-service-list-header">
                    EXPLORE OUR SERVICES
                </p>

                <a href="services.php" class="lm-service-row">

                    <span class="lm-service-number">01</span>

                    <div>
                        <h3>Skin care</h3>
                        <p>Kujdes dhe trajtime për fytyrën.</p>
                    </div>

                    <span class="lm-service-arrow">↗</span>

                </a>


                <a href="services.php" class="lm-service-row">

                    <span class="lm-service-number">02</span>

                    <div>
                        <h3>Nail artistry</h3>
                        <p>Manikyr, pedikyr dhe kujdes profesional.</p>
                    </div>

                    <span class="lm-service-arrow">↗</span>

                </a>


                <a href="services.php" class="lm-service-row">

                    <span class="lm-service-number">03</span>

                    <div>
                        <h3>Make-up</h3>
                        <p>Stil dhe bukuri për momentet e veçanta.</p>
                    </div>

                    <span class="lm-service-arrow">↗</span>

                </a>


                <a href="services.php" class="lm-service-row">

                    <span class="lm-service-number">04</span>

                    <div>
                        <h3>Beauty treatments</h3>
                        <p>Momente të dedikuara për kujdesin personal.</p>
                    </div>

                    <span class="lm-service-arrow">↗</span>

                </a>


                <a href="services.php" class="lm-link">
                    SHIKO TË GJITHA SHËRBIMET <span>↗</span>
                </a>

            </div>

        </div>

    </section>


    <!-- PANORAMIC STUDIO -->
    <section class="lm-space">

        <img
            src="https://images.unsplash.com/photo-1600948836101-f9ffda59d250?auto=format&fit=crop&w=2200&q=90"
            alt="Elegant beauty studio interior"
            loading="lazy"
        >

        <div class="lm-space-overlay"></div>

        <div class="lm-space-content">

            <p class="lm-label">THE LUMIÈRE SPACE</p>

            <h2>
                A place to pause.
                <em>A space to glow.</em>
            </h2>

            <p>
                Një ambient i menduar për qetësi,
                rehati dhe përkujdesje. Sepse çdo
                moment që i kushton vetes ka rëndësi.
            </p>

            <a href="about.php">
                ZBULO LUMIÈRE ↗
            </a>

        </div>

    </section>


    <!-- TEAM -->
    <section class="lm-team">

        <div class="lm-team-photo">

            <img
                src="https://images.unsplash.com/photo-1551836022-d5d88e9218df?auto=format&fit=crop&w=1200&q=85"
                alt="Beauty professionals"
                loading="lazy"
            >

            <div class="lm-team-note">
                The art is in the details.
            </div>

        </div>


        <div class="lm-team-content">

            <p class="lm-label">
                THE PEOPLE BEHIND THE EXPERIENCE
            </p>

            <h2>
                Beauty is an art.
                <em>Meet the artists.</em>
            </h2>

            <p>
                Prapa çdo përvoje qëndron një ekip
                i përkushtuar ndaj bukurisë, kujdesit
                dhe vëmendjes ndaj çdo detaji.
            </p>

            <a href="team.php" class="lm-link">
                NJIHU ME EKIPIN <span>↗</span>
            </a>

        </div>

    </section>


    <!-- GALLERY -->
    <section class="lm-gallery">

        <div class="lm-gallery-header">

            <div>
                <p class="lm-label">A VISUAL JOURNAL</p>

                <h2>
                    Moments of
                    <em>beauty.</em>
                </h2>
            </div>

            <a href="gallery.php" class="lm-link">
                EKSPLORO GALERINË <span>↗</span>
            </a>

        </div>


        <div class="lm-gallery-grid">

            <div class="lm-gallery-item">
                <img
                    src="https://images.unsplash.com/photo-1600334129128-685c5582fd35?auto=format&fit=crop&w=1000&q=85"
                    alt="Beauty atmosphere"
                    loading="lazy"
                >
                <span class="lm-gallery-caption">THE EXPERIENCE</span>
            </div>

            <div class="lm-gallery-item">
                <img
                    src="https://images.unsplash.com/photo-1604654894610-df63bc536371?auto=format&fit=crop&w=850&q=85"
                    alt="Nail artistry"
                    loading="lazy"
                >
                <span class="lm-gallery-caption">THE DETAILS</span>
            </div>

            <div class="lm-gallery-item">
                <img
                    src="https://images.unsplash.com/photo-1512496015851-a90fb38ba796?auto=format&fit=crop&w=850&q=85"
                    alt="Beauty inspiration"
                    loading="lazy"
                >
                <span class="lm-gallery-caption">THE ARTISTRY</span>
            </div>

            <div class="lm-gallery-item">
                <img
                    src="https://images.unsplash.com/photo-1522335789203-aabd1fc54bc9?auto=format&fit=crop&w=1000&q=85"
                    alt="Beauty editorial"
                    loading="lazy"
                >
                <span class="lm-gallery-caption">THE BEAUTY</span>
            </div>

        </div>

    </section>


    <!-- FINAL BOOKING -->
    <section class="lm-finale">

        <p class="lm-label">YOUR MOMENT STARTS HERE</p>

        <h2>
            Make time for<br>
            <em>yourself.</em>
        </h2>

        <p>
            Një moment për veten. Një përvojë e veçantë.
            Zbulo kujdesin dhe elegancën e LUMIÈRE.
        </p>

        <a href="booking.php" class="lm-finale-button">
            REZERVO TERMININ TËND ↗
        </a>

    </section>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
