<?php

require_once __DIR__ . '/../config/database.php';

$pageTitle = 'Gallery | LUMIÈRE Beauty Studio';

$services = [];
$groupedServices = [];
$loadError = false;

function galleryEscape(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/* klasifikimi i sherbimeve sipas emrit dhe kategorise */
function galleryCategoryKey(array $service): string
{
    $source = mb_strtolower(
        trim((string) ($service['kategoria'] ?? '') . ' ' . (string) ($service['emri'] ?? '')),
        'UTF-8'
    );

    if (
        str_contains($source, 'flok') ||
        str_contains($source, 'hair') ||
        str_contains($source, 'fenir') ||
        str_contains($source, 'ngjyros') ||
        str_contains($source, 'prerje')
    ) {
        return 'hair';
    }

    if (
        str_contains($source, 'thonj') ||
        str_contains($source, 'nail') ||
        str_contains($source, 'manik')
    ) {
        return 'nails';
    }

    if (
        str_contains($source, 'pedik') ||
        str_contains($source, 'feet') ||
        str_contains($source, 'kemb')
    ) {
        return 'pedicure';
    }

    if (
        str_contains($source, 'grim') ||
        str_contains($source, 'makeup') ||
        str_contains($source, 'make up')
    ) {
        return 'makeup';
    }

    if (
        str_contains($source, 'vetull') ||
        str_contains($source, 'brow') ||
        str_contains($source, 'lash') ||
        str_contains($source, 'qerpik')
    ) {
        return 'brows';
    }

    if (
        str_contains($source, 'fytyr') ||
        str_contains($source, 'facial') ||
        str_contains($source, 'skin') ||
        str_contains($source, 'estetik')
    ) {
        return 'skincare';
    }

    return 'other';
}


/* MARRJA E SHËRBIMEVE NGA DATABAZA */

try {

    $stmt = $pdo->query("
        SELECT
            sherbim_id,
            emri,
            pershkrimi,
            cmimi,
            kategoria,
            kohezgjatja_min
        FROM sherbimet
        ORDER BY kategoria, emri
    ");

    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($services as $service) {

        $key = galleryCategoryKey($service);

        $groupedServices[$key][] = $service;
    }

} catch (Throwable $e) {

    error_log('LUMIÈRE gallery error: ' . $e->getMessage());

    $loadError = true;
}


$categoryContent = [

    /* floke */

    'hair' => [

        'label' => 'Flokë',

        'eyebrow' => 'HAIR / THE ART OF STYLING',

        'title' => 'Flokë me formë, <em>lëvizje dhe shkëlqim.</em>',

        'intro' => 'Nga prerja dhe larja, te fenirimi, stilimi dhe ngjyrosja. Zbulo proceset dhe shërbimet e kujdesit për flokët.',

        'videos' => [

            [
                'title' => 'Fenirim & styling',

                'note' => 'Procesi i stilimit me brushë dhe tharëse.',

                'poster' => 'https://images.pexels.com/photos/14615063/pexels-photo-14615063.jpeg?auto=compress&cs=tinysrgb&w=1100',

                'src' => 'https://videos.pexels.com/video-files/4800212/4800212-hd_3840_2160_30fps.mp4',

                'page' => 'https://www.pexels.com/video/woman-getting-her-hair-blow-dried-4800212/',

                'credit' => 'Artem Podrez'
            ],

            [
                'title' => 'Hair coloring',

                'note' => 'Aplikimi profesional i ngjyrës në sallon.',

                'poster' => 'https://images.pexels.com/photos/20238659/pexels-photo-20238659.jpeg?auto=compress&cs=tinysrgb&w=1100',

                'src' => 'https://videos.pexels.com/video-files/7392662/7392662-hd_1920_1080_30fps.mp4',

                'page' => 'https://www.pexels.com/video/woman-having-hair-dyed-on-a-salon-7392662/',

                'credit' => 'Orhun Rüzgar Öz'
            ]

        ],

        'photos' => [

            [
                'src' => 'https://images.pexels.com/photos/8834077/pexels-photo-8834077.jpeg?auto=compress&cs=tinysrgb&w=1100',

                'alt' => 'Prerje flokësh në sallon',

                'credit' => 'Kampus Production',

                'page' => 'https://www.pexels.com/photo/a-client-having-her-hair-cut-by-the-hair-stylist-8834077/'
            ],

            [
                'src' => 'https://images.pexels.com/photos/8834095/pexels-photo-8834095.jpeg?auto=compress&cs=tinysrgb&w=1100',

                'alt' => 'Stiliste duke prerë flokët e klientes',

                'credit' => 'Kampus Production',

                'page' => 'https://www.pexels.com/photo/hair-stylist-cutting-the-hair-of-the-customer-8834095/'
            ]

        ]

    ],


    /* thonje */

    'nails' => [

        'label' => 'Thonj',

        'eyebrow' => 'NAILS / THE FINISHING TOUCH',

        'title' => 'Detaje të vogla, <em>ndryshim i madh.</em>',

        'intro' => 'Manikyri është kujdes, formë dhe përfundim i pastër. Shiko nga afër procesin dhe inspirimin për thonjtë.',

        'videos' => [

            [
                'title' => 'Manicure in detail',

                'note' => 'Punë e kujdesshme gjatë manikyrit.',

                'poster' => 'https://images.pexels.com/photos/6135681/pexels-photo-6135681.jpeg?auto=compress&cs=tinysrgb&w=1100',

                'src' => 'https://videos.pexels.com/video-files/7754436/7754436-hd_1080_1920_30fps.mp4',

                'page' => 'https://www.pexels.com/video/a-nail-technician-working-on-a-client-7754436/',

                'credit' => 'RDNE Stock project'
            ],

            [
                'title' => 'Nail polish application',

                'note' => 'Aplikimi i llakut me precizion.',

                'poster' => 'https://images.pexels.com/photos/34930100/pexels-photo-34930100.jpeg?auto=compress&cs=tinysrgb&w=1100',

                'src' => 'https://videos.pexels.com/video-files/4802704/4802704-hd_1920_1080_30fps.mp4',

                'page' => 'https://www.pexels.com/video/video-of-person-applying-nail-polish-4802704/',

                'credit' => 'Ketut Subiyanto'
            ]

        ],

        'photos' => [

            [
                'src' => 'https://images.pexels.com/photos/6135681/pexels-photo-6135681.jpeg?auto=compress&cs=tinysrgb&w=1100',

                'alt' => 'Manikyr profesional',

                'credit' => 'Gabriel Puyén',

                'page' => 'https://www.pexels.com/photo/professional-manicure-treatment-6135681/'
            ],

            [
                'src' => 'https://images.pexels.com/photos/13424007/pexels-photo-13424007.jpeg?auto=compress&cs=tinysrgb&w=1100',

                'alt' => 'Manikyr dhe limim i thonjve',

                'credit' => 'Soana Beatriz',

                'page' => 'https://www.pexels.com/photo/a-manicurist-filing-a-client-s-nail-13424007/'
            ]

        ]

    ],


    /* pedikyr */

    'pedicure' => [

        'label' => 'Pedikyr',

        'eyebrow' => 'PEDICURE / CARE FROM THE GROUND UP',

        'title' => 'Kujdes që fillon <em>nga detajet.</em>',

        'intro' => 'Një rutinë e dedikuar për kujdesin e këmbëve dhe thonjve, me fokus te pastërtia, rehatia dhe mirëmbajtja.',

        'videos' => [

            [
                'title' => 'Pedicure session',

                'note' => 'Kujdes profesional gjatë pedikyrit.',

                'poster' => 'https://images.pexels.com/photos/34930117/pexels-photo-34930117.jpeg?auto=compress&cs=tinysrgb&w=1100',

                'src' => 'https://videos.pexels.com/video-files/4855961/4855961-hd_1920_1080_30fps.mp4',

                'page' => 'https://www.pexels.com/video/a-person-having-a-pedicure-4855961/',

                'credit' => 'Pavel Danilyuk'
            ],

            [
                'title' => 'Foot care detail',

                'note' => 'Momente të kujdesit dhe relaksimit.',

                'poster' => 'https://images.pexels.com/photos/17056221/pexels-photo-17056221.jpeg?auto=compress&cs=tinysrgb&w=1100',

                'src' => 'https://videos.pexels.com/video-files/4855849/4855849-hd_1080_1920_30fps.mp4',

                'page' => 'https://www.pexels.com/video/a-person-having-a-pedicure-4855849/',

                'credit' => 'Pavel Danilyuk'
            ]

        ],

        'photos' => [

            [
                'src' => 'https://images.pexels.com/photos/34930117/pexels-photo-34930117.jpeg?auto=compress&cs=tinysrgb&w=1100',

                'alt' => 'Pedikyr në studio bukurie',

                'credit' => 'José Antonio Otegui Auzmendi',

                'page' => 'https://www.pexels.com/photo/professional-pedicure-at-a-salon-spa-34930117/'
            ],

            [
                'src' => 'https://images.pexels.com/photos/17056221/pexels-photo-17056221.jpeg?auto=compress&cs=tinysrgb&w=1100',

                'alt' => 'Kujdes profesional për këmbët',

                'credit' => 'Andrea Mosti',

                'page' => 'https://www.pexels.com/photo/close-up-of-a-person-getting-pedicure-done-17056221/'
            ]

        ]

    ],


    /* grim */

    'makeup' => [

        'label' => 'Grim',

        'eyebrow' => 'MAKEUP / ARTISTRY & EXPRESSION',

        'title' => 'Një pamje që <em>tregon stilin tënd.</em>',

        'intro' => 'Grimi profesional ndërthur teknikat, nuancat dhe detajet për një pamje të kuruar për raste të veçanta.',

        'videos' => [

            [
                'title' => 'Makeup application',

                'note' => 'Teknika e aplikimit të grimit në studio.',

                'poster' => 'https://images.pexels.com/photos/33580449/pexels-photo-33580449.jpeg?auto=compress&cs=tinysrgb&w=1100',

                'src' => 'https://videos.pexels.com/video-files/7290136/7290136-hd_2160_3840_25fps.mp4',

                'page' => 'https://www.pexels.com/video/video-of-a-person-applying-makeup-7290136/',

                'credit' => 'MART PRODUCTION'
            ],

            [
                'title' => 'Eye makeup details',

                'note' => 'Punë precize në zonën e syve.',

                'poster' => 'https://images.pexels.com/photos/33607399/pexels-photo-33607399.jpeg?auto=compress&cs=tinysrgb&w=1100',

                'src' => 'https://videos.pexels.com/video-files/7987960/7987960-hd_1920_1080_50fps.mp4',

                'page' => 'https://www.pexels.com/video/close-up-video-of-a-person-applying-makeup-on-a-woman-7987960/',

                'credit' => 'Antoni Shkraba'
            ]

        ],

        'photos' => [

            [
                'src' => 'https://images.pexels.com/photos/33580449/pexels-photo-33580449.jpeg?auto=compress&cs=tinysrgb&w=1100',

                'alt' => 'Makeup artist duke aplikuar grim',

                'credit' => 'Bulat843',

                'page' => 'https://www.pexels.com/photo/professional-makeup-artist-applying-makeup-on-client-33580449/'
            ],

            [
                'src' => 'https://images.pexels.com/photos/11041338/pexels-photo-11041338.jpeg?auto=compress&cs=tinysrgb&w=1100',

                'alt' => 'Grim profesional në studio',

                'credit' => 'ishola S. Oladimeji',

                'page' => 'https://www.pexels.com/photo/makeup-artist-doing-makeup-of-a-woman-11041338/'
            ]

        ]

    ],


    /* kategori te reja nese shtohen ne databaze */

    'brows' => [

        'label' => 'Vetulla & qerpikë',

        'eyebrow' => 'BROWS & LASHES / THE FRAME',

        'title' => 'Detaje që e <em>plotësojnë pamjen.</em>',

        'intro' => 'Formësimi i vetullave dhe kujdesi për qerpikët ndihmojnë në definimin e pamjes.',

        'videos' => [

            [
                'title' => 'Brow detail',
                'note' => 'Detaje të kujdesit dhe formësimit të vetullave.',
                'poster' => 'https://images.pexels.com/photos/33607399/pexels-photo-33607399.jpeg?auto=compress&cs=tinysrgb&w=1100',
                'src' => 'https://videos.pexels.com/video-files/6652123/6652123-hd_2160_4096_25fps.mp4',
                'page' => 'https://www.pexels.com/video/person-applying-makeup-on-a-woman-6652123/',
                'credit' => 'cottonbro studio'
            ],

            [
                'title' => 'Beauty close-up',
                'note' => 'Pamje ilustruese e punës me detaje.',
                'poster' => 'https://images.pexels.com/photos/6953629/pexels-photo-6953629.jpeg?auto=compress&cs=tinysrgb&w=1100',
                'src' => 'https://videos.pexels.com/video-files/7987960/7987960-hd_1920_1080_50fps.mp4',
                'page' => 'https://www.pexels.com/video/close-up-video-of-a-person-applying-makeup-on-a-woman-7987960/',
                'credit' => 'Antoni Shkraba'
            ]

        ],

        'photos' => [

            [
                'src' => 'https://images.pexels.com/photos/33607399/pexels-photo-33607399.jpeg?auto=compress&cs=tinysrgb&w=1100',
                'alt' => 'Detaj i grimit rreth syve',
                'credit' => 'Bulat843',
                'page' => 'https://www.pexels.com/photo/close-up-of-makeup-artist-applying-eyeliner-33607399/'
            ],

            [
                'src' => 'https://images.pexels.com/photos/6953629/pexels-photo-6953629.jpeg?auto=compress&cs=tinysrgb&w=1100',
                'alt' => 'Aplikim profesional i produktit të bukurisë',
                'credit' => 'George Milton',
                'page' => 'https://www.pexels.com/photo/anonymous-young-female-visagiste-applying-blush-on-cheeks-of-customer-6953629/'
            ]

        ]

    ],

    'skincare' => [

        'label' => 'Kujdes për fytyrën',

        'eyebrow' => 'SKINCARE / CARE & COMFORT',

        'title' => 'Kujdes i butë, <em>momente qetësie.</em>',

        'intro' => 'Një hapësirë për shërbimet e kujdesit të fytyrës, sipas katalogut të studios.',

        'videos' => [

            [
                'title' => 'Beauty care',
                'note' => 'Proces ilustrues i kujdesit personal.',
                'poster' => 'https://images.pexels.com/photos/6953622/pexels-photo-6953622.jpeg?auto=compress&cs=tinysrgb&w=1100',
                'src' => 'https://videos.pexels.com/video-files/7290136/7290136-hd_2160_3840_25fps.mp4',
                'page' => 'https://www.pexels.com/video/video-of-a-person-applying-makeup-7290136/',
                'credit' => 'MART PRODUCTION'
            ],

            [
                'title' => 'The care ritual',
                'note' => 'Pamje ilustruese e një rutine bukurie.',
                'poster' => 'https://images.pexels.com/photos/6953629/pexels-photo-6953629.jpeg?auto=compress&cs=tinysrgb&w=1100',
                'src' => 'https://videos.pexels.com/video-files/7987960/7987960-hd_1920_1080_50fps.mp4',
                'page' => 'https://www.pexels.com/video/close-up-video-of-a-person-applying-makeup-on-a-woman-7987960/',
                'credit' => 'Antoni Shkraba'
            ]

        ],

        'photos' => [

            [
                'src' => 'https://images.pexels.com/photos/6953622/pexels-photo-6953622.jpeg?auto=compress&cs=tinysrgb&w=1100',
                'alt' => 'Kujdes personal në studio',
                'credit' => 'George Milton',
                'page' => 'https://www.pexels.com/photo/cheerful-black-woman-applying-cosmetic-product-to-client-6953622/'
            ],

            [
                'src' => 'https://images.pexels.com/photos/6953629/pexels-photo-6953629.jpeg?auto=compress&cs=tinysrgb&w=1100',
                'alt' => 'Aplikim i produktit kozmetik',
                'credit' => 'George Milton',
                'page' => 'https://www.pexels.com/photo/anonymous-young-female-visagiste-applying-blush-on-cheeks-of-customer-6953629/'
            ]

        ]

    ],

    'other' => [

        'label' => 'Shërbime të tjera',

        'eyebrow' => 'LUMIÈRE / THE DETAILS',

        'title' => 'Çdo shërbim ka <em>momentin e vet.</em>',

        'intro' => 'Zbulo më shumë rreth shërbimeve të tjera të regjistruara në katalogun e LUMIÈRE.',

        'videos' => [

            [
                'title' => 'A closer look',
                'note' => 'Proces ilustrues i kujdesit profesional.',
                'poster' => 'https://images.pexels.com/photos/6135681/pexels-photo-6135681.jpeg?auto=compress&cs=tinysrgb&w=1100',
                'src' => 'https://videos.pexels.com/video-files/7754436/7754436-hd_1080_1920_30fps.mp4',
                'page' => 'https://www.pexels.com/video/a-nail-technician-working-on-a-client-7754436/',
                'credit' => 'RDNE Stock project'
            ],

            [
                'title' => 'The beauty process',
                'note' => 'Pamje ilustruese e një procesi në studio.',
                'poster' => 'https://images.pexels.com/photos/8834077/pexels-photo-8834077.jpeg?auto=compress&cs=tinysrgb&w=1100',
                'src' => 'https://videos.pexels.com/video-files/4800212/4800212-hd_3840_2160_30fps.mp4',
                'page' => 'https://www.pexels.com/video/woman-getting-her-hair-blow-dried-4800212/',
                'credit' => 'Artem Podrez'
            ]

        ],

        'photos' => [

            [
                'src' => 'https://images.pexels.com/photos/8834077/pexels-photo-8834077.jpeg?auto=compress&cs=tinysrgb&w=1100',
                'alt' => 'Proces pune në studio bukurie',
                'credit' => 'Kampus Production',
                'page' => 'https://www.pexels.com/photo/a-client-having-her-hair-cut-by-the-hair-stylist-8834077/'
            ],

            [
                'src' => 'https://images.pexels.com/photos/6135681/pexels-photo-6135681.jpeg?auto=compress&cs=tinysrgb&w=1100',
                'alt' => 'Detaje të kujdesit profesional',
                'credit' => 'Gabriel Puyén',
                'page' => 'https://www.pexels.com/photo/professional-manicure-treatment-6135681/'
            ]

        ]

    ]

];


require_once __DIR__ . '/includes/header.php';

?>

<style>

/* galeria lumiere */

.gallery-page {
    --ink: #302820;
    --muted: #7e7167;
    --accent: #9b795f;
    --line: #e8dfd5;
    --paper: #fbf9f5;
    --warm: #f1ebe3;

    background: var(--paper);
    color: var(--ink);
    overflow: hidden;
}

.gallery-page * {
    box-sizing: border-box;
}

.gallery-shell {
    width: min(1220px, calc(100% - 54px));
    margin: 0 auto;
}

.g-eyebrow {
    margin: 0 0 12px;
    color: var(--accent);
    font-size: 10px;
    font-weight: 700;
    letter-spacing: .18em;
    text-transform: uppercase;
}


/* HERO */

.g-hero {
    padding: clamp(52px, 8vw, 96px) 0 65px;
    background: #f3ede5;
}

.g-hero-grid {
    display: grid;
    grid-template-columns: .86fr 1.14fr;
    align-items: center;
    gap: clamp(30px, 6vw, 82px);
}

.g-hero h1 {
    margin: 0;
    color: var(--ink);
    font: 400 clamp(48px, 6.5vw, 82px)/.98 Georgia, 'Times New Roman', serif;
    letter-spacing: -.045em;
}

.g-hero h1 em,
.g-heading h2 em {
    color: var(--accent);
    font-weight: 400;
}

.g-hero-copy > p:not(.g-eyebrow) {
    max-width: 430px;
    margin: 22px 0 25px;
    color: #75695f;
    font-size: 14px;
    line-height: 1.85;
}

.g-hero-links {
    display: flex;
    align-items: center;
    gap: 18px;
    flex-wrap: wrap;
}

.g-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 14px;
    min-height: 46px;
    padding: 0 20px;

    color: #fffaf5;
    background: #3a2f27;
    border: 1px solid #3a2f27;

    text-decoration: none;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: .11em;
    transition: .2s;
}

.g-button:hover {
    color: #3a2f27;
    background: transparent;
}

.g-text-link {
    color: #685548;
    font-size: 12px;
    text-decoration: none;
    border-bottom: 1px solid #b9a693;
    padding-bottom: 4px;
}

.g-hero-visual {
    position: relative;
    min-height: 410px;
    background: #e4dbd1;
    overflow: hidden;
}

.g-hero-visual video {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.g-hero-visual:after {
    content: "";
    position: absolute;
    inset: 45% 0 0;
    background: linear-gradient(transparent, rgba(24,20,17,.48));
    pointer-events: none;
}

.g-hero-caption {
    position: absolute;
    z-index: 2;
    left: 25px;
    right: 25px;
    bottom: 22px;

    display: flex;
    justify-content: space-between;
    align-items: end;
    color: #fff;
}

.g-hero-caption strong {
    display: block;
    font: 400 30px Georgia, serif;
}

.g-hero-caption span {
    font-size: 10px;
    letter-spacing: .13em;
    text-transform: uppercase;
}

.g-stock-note {
    margin-top: 13px;
    color: #8a7d72;
    font-size: 10px;
    line-height: 1.6;
}

.g-stock-note a {
    color: inherit;
    text-underline-offset: 3px;
}


/* CATEGORY NAVIGATION */

.g-nav-wrap {
    padding: 17px 0;
    background: #fbfaf7;
    border-bottom: 1px solid var(--line);

    position: sticky;
    top: 0;
    z-index: 20;
}

.g-category-nav {
    display: flex;
    gap: 9px;
    overflow-x: auto;
    scrollbar-width: thin;
    padding-bottom: 2px;
}

.g-category-nav a {
    flex: 0 0 auto;
    padding: 10px 14px;
    color: #706358;
    border: 1px solid transparent;
    text-decoration: none;
    font-size: 11px;
    transition: .2s;
}

.g-category-nav a:hover,
.g-category-nav a.is-active {
    background: #eee6dd;
    border-color: #e0d5c9;
    color: #3b3027;
}


/* CATEGORY SECTION */

.g-section {
    padding: clamp(58px, 7vw, 88px) 0;
    border-bottom: 1px solid var(--line);
    scroll-margin-top: 80px;
}

.g-section:nth-of-type(even) {
    background: #f5f0e9;
}

.g-heading {
    display: flex;
    justify-content: space-between;
    align-items: end;
    gap: 25px;
    margin-bottom: 27px;
}

.g-heading h2 {
    max-width: 700px;
    margin: 0;
    font: 400 clamp(33px, 4.7vw, 54px)/1.07 Georgia, serif;
    letter-spacing: -.035em;
}

.g-heading-copy {
    max-width: 380px;
    margin: 0;
    color: var(--muted);
    font-size: 13px;
    line-height: 1.8;
}


/* VIDEOS */

.g-media-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 17px;
    margin-bottom: 27px;
}

.g-video-card,
.g-photo-card {
    min-width: 0;
    overflow: hidden;
    background: #fff;
    border: 1px solid #e8dfd5;
}

.g-video-frame {
    position: relative;
    aspect-ratio: 16 / 9;
    background: #ddd2c7;
}

.g-video-frame video {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
    background: #ddd2c7;
}

.g-video-label {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 14px 16px 15px;
}

.g-video-label strong {
    display: block;
    color: #403329;
    font: 400 19px Georgia, serif;
}

.g-video-label span {
    display: block;
    margin-top: 5px;
    color: #887b70;
    font-size: 11px;
}

.g-video-label a {
    color: #775e4a;
    font-size: 10px;
    font-weight: 700;
    text-decoration: none;
    white-space: nowrap;
    border-bottom: 1px solid #cbb9a7;
    padding-bottom: 4px;
}

.g-video-fallback {
    display: none;
    padding: 9px 15px;
    color: #7b6f65;
    background: #f5eee7;
    font-size: 11px;
}

.g-video-fallback a {
    color: #755841;
}


/* PHOTOS */

.g-photo-strip {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 17px;
    margin-bottom: 35px;
}

.g-photo-card {
    position: relative;
    display: block;
    aspect-ratio: 16 / 8.7;
    padding: 0;
    border: 0;
    cursor: zoom-in;
    background: #e7ded5;
    text-align: left;
}

.g-photo-card img {
    width: 100%;
    height: 100%;
    display: block;
    object-fit: cover;
    transition: transform .45s;
}

.g-photo-card:hover img {
    transform: scale(1.035);
}

.g-photo-card:after {
    content: "↗";
    position: absolute;
    right: 13px;
    bottom: 13px;

    display: grid;
    place-items: center;
    width: 35px;
    height: 35px;

    border-radius: 50%;
    background: #fff;
    color: #352a22;
    font-size: 15px;
}


/* SERVICES */

.g-service-head {
    display: flex;
    align-items: end;
    justify-content: space-between;
    gap: 20px;
    margin-bottom: 14px;
}

.g-service-head h3 {
    margin: 0;
    color: #3b3027;
    font: 400 26px Georgia, serif;
}

.g-service-head span {
    color: #8b7e72;
    font-size: 10px;
    letter-spacing: .1em;
    text-transform: uppercase;
}

.g-service-list {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 11px;
}

.g-service-card {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    align-items: center;
    gap: 14px;
    padding: 19px 20px;
    background: #fff;
    border: 1px solid #e9e1d9;
    transition: border-color .2s, transform .2s;
}

.g-service-card:hover {
    border-color: #c8b6a4;
    transform: translateY(-2px);
}

.g-service-card h4 {
    margin: 0 0 6px;
    color: #3b3027;
    font: 400 20px Georgia, serif;
}

.g-service-card p {
    margin: 0;
    color: #81756b;
    font-size: 11px;
    line-height: 1.6;
}

.g-service-meta {
    display: flex;
    align-items: center;
    gap: 13px;
    justify-content: flex-end;
}

.g-service-meta span {
    color: #81756b;
    font-size: 10px;
    white-space: nowrap;
}

.g-service-meta strong {
    color: #49392d;
    font: 400 20px Georgia, serif;
    white-space: nowrap;
}

.g-book-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-top: 9px;
    color: #80634d;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: .08em;
    text-decoration: none;
}

.g-empty {
    padding: 23px;
    background: #fff;
    border: 1px solid var(--line);
    color: #75695f;
    font-size: 13px;
    line-height: 1.7;
}


/* FINAL CTA */

.g-finale {
    padding: 72px 20px 78px;
    color: #fffaf5;
    background: #392e26;
    text-align: center;
}

.g-finale .g-eyebrow {
    color: #d3bba4;
}

.g-finale h2 {
    margin: 0 auto 15px;
    font: 400 clamp(34px, 5vw, 54px)/1.1 Georgia, serif;
}

.g-finale h2 em {
    color: #d7bda5;
    font-weight: 400;
}

.g-finale > p:not(.g-eyebrow) {
    max-width: 470px;
    margin: 0 auto 23px;
    color: #d6c8bc;
    font-size: 13px;
    line-height: 1.8;
}

.g-finale .g-button {
    color: #392e26;
    background: #f2e8de;
    border-color: #f2e8de;
}

.g-finale .g-button:hover {
    color: #fff;
    background: transparent;
}


/* LIGHTBOX */

.g-lightbox {
    position: fixed;
    inset: 0;
    z-index: 9999;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 25px;
    background: rgba(18,15,12,.88);
    backdrop-filter: blur(6px);
}

.g-lightbox.is-open {
    display: flex;
}

.g-lightbox-inner {
    position: relative;
    width: min(1000px, 100%);
    max-height: 90vh;
    background: #f8f4ef;
    padding: 12px;
}

.g-lightbox-inner img {
    display: block;
    max-width: 100%;
    max-height: 84vh;
    margin: auto;
    object-fit: contain;
}

.g-lightbox-close {
    position: absolute;
    top: -15px;
    right: -15px;
    width: 38px;
    height: 38px;
    border: 0;
    border-radius: 50%;
    background: #fff;
    color: #33271f;
    font-size: 22px;
    cursor: pointer;
}

body.g-lightbox-open {
    overflow: hidden;
}


/* RESPONSIVE */

@media(max-width:800px) {

    .g-hero-grid {
        grid-template-columns: 1fr 1fr;
        gap: 24px;
    }

    .g-hero-visual {
        min-height: 365px;
    }

    .g-heading {
        display: block;
    }

    .g-heading-copy {
        margin-top: 13px;
    }

    .g-service-list {
        grid-template-columns: 1fr;
    }

}

@media(max-width:620px) {

    .gallery-shell {
        width: calc(100% - 34px);
    }

    .g-hero {
        padding: 42px 0 48px;
    }

    .g-hero-grid {
        grid-template-columns: 1fr;
    }

    .g-hero-visual {
        min-height: 340px;
    }

    .g-heading h2 {
        font-size: 36px;
    }

    .g-media-grid {
        grid-template-columns: 1fr;
        gap: 12px;
    }

    .g-photo-strip {
        gap: 10px;
    }

    .g-photo-card {
        aspect-ratio: 4 / 3.5;
    }

    .g-service-card {
        padding: 16px;
        grid-template-columns: minmax(0,1fr) auto;
        gap: 8px;
    }

    .g-service-card h4 {
        font-size: 18px;
    }

    .g-service-meta {
        gap: 8px;
        flex-direction: column;
        align-items: flex-end;
    }

    .g-service-meta strong {
        font-size: 19px;
    }

    .g-nav-wrap {
        top: 0;
    }

    .g-category-nav a {
        padding: 9px 11px;
    }

    .g-video-label {
        align-items: flex-start;
    }

    .g-video-label a {
        font-size: 9px;
    }

}

@media(prefers-reduced-motion:reduce) {

    .gallery-page * {
        scroll-behavior: auto !important;
        transition-duration: .01ms !important;
    }

}

</style>


<main class="gallery-page">

    <!-- HERO -->

    <section class="g-hero">

        <div class="gallery-shell g-hero-grid">

            <div class="g-hero-copy">

                <p class="g-eyebrow">
                    LUMIÈRE / THE SERVICE EDIT
                </p>

                <h1>
                    See the craft.<br>
                    Feel the <em>detail.</em>
                </h1>

                <p>
                    Hyr në botën e secilit shërbim të LUMIÈRE.
                    Shiko proceset në video, zbulo detajet në fotografi
                    dhe gjej shërbimin që të përshtatet.
                </p>

                <div class="g-hero-links">

                    <a href="#service-stories" class="g-button">
                        ZBULO SHËRBIMET <span>↓</span>
                    </a>

                    <a href="services.php" class="g-text-link">
                        Shiko katalogun e plotë
                    </a>

                </div>

                <p class="g-stock-note">
                    Materialet vizuale janë fotografi dhe video ilustruese
                    nga Pexels për projektin universitar, jo punime reale
                    të LUMIÈRE.
                    <a href="https://www.pexels.com/license/" target="_blank" rel="noopener">
                        Licenca
                    </a>.
                </p>

            </div>


            <div class="g-hero-visual">

                <video
                    autoplay
                    muted
                    loop
                    playsinline
                    preload="metadata"
                    poster="https://images.pexels.com/photos/14615063/pexels-photo-14615063.jpeg?auto=compress&cs=tinysrgb&w=1400"
                    aria-label="Video ilustruese e stilimit të flokëve"
                >

                    <source
                        src="https://videos.pexels.com/video-files/4800212/4800212-hd_3840_2160_30fps.mp4"
                        type="video/mp4"
                    >

                </video>

                <div class="g-hero-caption">

                    <div>
                        <strong>The beauty process</strong>
                        <span>SEE EVERY DETAIL</span>
                    </div>

                    <span>VISUAL JOURNAL / 01</span>

                </div>

            </div>

        </div>

    </section>


    <!-- CATEGORY NAVIGATION -->

    <div class="g-nav-wrap" id="service-stories">

        <nav class="gallery-shell g-category-nav" aria-label="Kategoritë e shërbimeve">

            <?php foreach ($groupedServices as $key => $categoryServices): ?>

                <?php $config = $categoryContent[$key] ?? $categoryContent['other']; ?>

                <a href="#category-<?= galleryEscape($key) ?>">

                    <?= galleryEscape($config['label']) ?>

                    <span>(<?= count($categoryServices) ?>)</span>

                </a>

            <?php endforeach; ?>

        </nav>

    </div>


    <!-- DYNAMIC SERVICE CATEGORIES -->

    <?php if ($loadError): ?>

        <section class="g-section">

            <div class="gallery-shell">

                <div class="g-empty">
                    Nuk mund të lexohen shërbimet nga databaza.
                    Kontrollo lidhjen dhe provo përsëri.
                </div>

            </div>

        </section>

    <?php elseif (empty($services)): ?>

        <section class="g-section">

            <div class="gallery-shell">

                <div class="g-empty">
                    Për momentin nuk ka shërbime të regjistruara në katalogun e LUMIÈRE.
                </div>

            </div>

        </section>

    <?php else: ?>


        <?php foreach ($groupedServices as $key => $categoryServices): ?>

            <?php $content = $categoryContent[$key] ?? $categoryContent['other']; ?>

            <section
                class="g-section"
                id="category-<?= galleryEscape($key) ?>"
            >

                <div class="gallery-shell">


                    <!-- CATEGORY INTRO -->

                    <div class="g-heading">

                        <div>

                            <p class="g-eyebrow">
                                <?= galleryEscape($content['eyebrow']) ?>
                            </p>

                            <h2><?= $content['title'] ?></h2>

                        </div>

                        <p class="g-heading-copy">
                            <?= galleryEscape($content['intro']) ?>
                        </p>

                    </div>


                    <!-- VIDEOS -->

                    <div class="g-media-grid">

                        <?php foreach ($content['videos'] as $video): ?>

                            <article class="g-video-card">

                                <div class="g-video-frame">

                                    <video
                                        controls
                                        playsinline
                                        preload="none"
                                        poster="<?= galleryEscape($video['poster']) ?>"
                                        data-fallback-link="<?= galleryEscape($video['page']) ?>"
                                    >

                                        <source
                                            src="<?= galleryEscape($video['src']) ?>"
                                            type="video/mp4"
                                        >

                                        Shfletuesi yt nuk e mbështet videon.

                                    </video>

                                </div>


                                <div class="g-video-label">

                                    <div>

                                        <strong>
                                            <?= galleryEscape($video['title']) ?>
                                        </strong>

                                        <span>
                                            <?= galleryEscape($video['note']) ?>
                                        </span>

                                    </div>

                                    <a
                                        href="<?= galleryEscape($video['page']) ?>"
                                        target="_blank"
                                        rel="noopener"
                                    >
                                        BURIMI ↗
                                    </a>

                                </div>


                                <div class="g-video-fallback">

                                    Nëse videoja nuk hapet këtu,

                                    <a
                                        href="<?= galleryEscape($video['page']) ?>"
                                        target="_blank"
                                        rel="noopener"
                                    >
                                        shikoje në Pexels
                                    </a>.

                                </div>

                            </article>

                        <?php endforeach; ?>

                    </div>


                    <!-- PHOTOS -->

                    <div class="g-photo-strip">

                        <?php foreach ($content['photos'] as $photo): ?>

                            <button
                                type="button"
                                class="g-photo-card"
                                data-lightbox-src="<?= galleryEscape($photo['src']) ?>"
                                data-lightbox-alt="<?= galleryEscape($photo['alt']) ?>"
                                aria-label="Hap fotografinë: <?= galleryEscape($photo['alt']) ?>"
                            >

                                <img
                                    src="<?= galleryEscape($photo['src']) ?>"
                                    alt="<?= galleryEscape($photo['alt']) ?> — fotografi ilustruese"
                                    loading="lazy"
                                >

                            </button>

                        <?php endforeach; ?>

                    </div>


                    <!-- SERVICES FROM DATABASE -->

                    <div class="g-service-head">

                        <h3>Shërbimet në këtë kategori</h3>

                        <span>
                            <?= count($categoryServices) ?>
                            <?= count($categoryServices) === 1 ? 'shërbim' : 'shërbime' ?>
                        </span>

                    </div>


                    <div class="g-service-list">

                        <?php foreach ($categoryServices as $service): ?>

                            <article class="g-service-card">

                                <div>

                                    <h4>
                                        <?= galleryEscape($service['emri']) ?>
                                    </h4>

                                    <p>
                                        <?= galleryEscape(
                                            $service['pershkrimi']
                                            ?: 'Përvojë bukurie nga katalogu LUMIÈRE.'
                                        ) ?>
                                    </p>

                                    <a
                                        class="g-book-link"
                                        href="booking.php?sherbim_id=<?= (int) $service['sherbim_id'] ?>"
                                    >
                                        REZERVO KËTË SHËRBIM <span>↗</span>
                                    </a>

                                </div>


                                <div class="g-service-meta">

                                    <?php if ($service['kohezgjatja_min'] !== null): ?>

                                        <span>
                                            <?= (int) $service['kohezgjatja_min'] ?> min
                                        </span>

                                    <?php endif; ?>

                                    <strong>
                                        <?= number_format(
                                            (float) $service['cmimi'],
                                            2,
                                            ',',
                                            '.'
                                        ) ?> €
                                    </strong>

                                </div>

                            </article>

                        <?php endforeach; ?>

                    </div>

                </div>

            </section>

        <?php endforeach; ?>

    <?php endif; ?>


    <!-- FINAL CTA -->

    <section class="g-finale">

        <p class="g-eyebrow">
            YOUR NEXT BEAUTY MOMENT
        </p>

        <h2>
            Gjeje shërbimin që<br>
            <em>është për ty.</em>
        </h2>

        <p>
            Eksploro katalogun dhe planifiko vizitën tënde në LUMIÈRE.
        </p>

        <a href="booking.php" class="g-button">
            REZERVO TERMININ <span>↗</span>
        </a>

    </section>

</main>


<!-- IMAGE LIGHTBOX -->

<div class="g-lightbox" id="galleryLightbox" aria-hidden="true">

    <div
        class="g-lightbox-inner"
        role="dialog"
        aria-modal="true"
        aria-label="Fotografia e zgjedhur"
    >

        <button
            type="button"
            class="g-lightbox-close"
            id="galleryLightboxClose"
            aria-label="Mbyll fotografinë"
        >
            ×
        </button>

        <img
            src=""
            alt=""
            id="galleryLightboxImage"
        >

    </div>

</div>


<script>
(() => {

    const lightbox = document.getElementById('galleryLightbox');

    const lightboxImage = document.getElementById('galleryLightboxImage');

    const closeButton = document.getElementById('galleryLightboxClose');

    let previousFocus = null;


    /* Hapja e fotografive */

    document.querySelectorAll('[data-lightbox-src]').forEach(button => {

        button.addEventListener('click', () => {

            previousFocus = document.activeElement;

            lightboxImage.src = button.dataset.lightboxSrc;

            lightboxImage.alt =
                button.dataset.lightboxAlt || 'Fotografi ilustruese';

            lightbox.classList.add('is-open');

            lightbox.setAttribute('aria-hidden', 'false');

            document.body.classList.add('g-lightbox-open');

            closeButton.focus();

        });

    });


    /* Mbyllja e fotografive */

    function closeLightbox() {

        lightbox.classList.remove('is-open');

        lightbox.setAttribute('aria-hidden', 'true');

        lightboxImage.src = '';

        document.body.classList.remove('g-lightbox-open');

        if (
            previousFocus &&
            typeof previousFocus.focus === 'function'
        ) {
            previousFocus.focus();
        }

    }


    closeButton.addEventListener('click', closeLightbox);


    lightbox.addEventListener('click', event => {

        if (event.target === lightbox) {
            closeLightbox();
        }

    });


    document.addEventListener('keydown', event => {

        if (
            event.key === 'Escape' &&
            lightbox.classList.contains('is-open')
        ) {
            closeLightbox();
        }

    });


    /* fallback nese vid nuk mund te ngarkohet */

    document.querySelectorAll('.g-video-card video').forEach(video => {

        video.addEventListener('error', () => {

            const fallback = video
                .closest('.g-video-card')
                ?.querySelector('.g-video-fallback');

            if (fallback) {
                fallback.style.display = 'block';
            }

        });

    });


    /* ndryshimi automatik*/

    const sections = [
        ...document.querySelectorAll('.g-section[id]')
    ];

    const navLinks = [
        ...document.querySelectorAll('.g-category-nav a')
    ];

    if ('IntersectionObserver' in window && sections.length) {

        const observer = new IntersectionObserver(entries => {

            entries.forEach(entry => {

                if (!entry.isIntersecting) return;

                navLinks.forEach(link => {

                    link.classList.toggle(
                        'is-active',
                        link.getAttribute('href') === '#' + entry.target.id
                    );

                });

            });

        }, {
            rootMargin: '-25% 0px -60% 0px'
        });

        sections.forEach(section => observer.observe(section));

    }

})();
</script>


<?php require_once __DIR__ . '/includes/footer.php'; ?>