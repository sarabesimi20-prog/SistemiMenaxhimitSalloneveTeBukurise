
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['contact_csrf'])) {
    $_SESSION['contact_csrf'] = bin2hex(random_bytes(32));
}

$currentPage = basename($_SERVER['PHP_SELF']);
$status = $_GET['status'] ?? '';

require_once __DIR__ . '/includes/header.php';
?>

<link rel="stylesheet" href="assets/css/contact.css">

<main class="contact-page">

    <!-- HYRJA -->
    <section class="contact-intro">

        <div class="contact-art" aria-hidden="true">
            <div class="contact-art-circle"></div>

            <svg class="contact-line-art"
                 viewBox="0 0 300 520"
                 fill="none"
                 xmlns="http://www.w3.org/2000/svg">
                <path d="M28 480C66 416 72 356 54 303C35 248 50 191 93 149C126 117 150 78 151 35" />
                <path d="M55 305C94 279 125 269 155 273C189 278 212 260 224 230" />
                <path d="M91 149C116 166 142 174 168 168C195 162 211 142 219 116" />
                <path d="M151 35C177 65 184 94 173 123C166 141 154 151 139 160" />
                <path d="M155 273C137 239 138 211 158 187C174 168 199 158 224 160" />
                <path d="M54 303C29 282 18 255 23 224C28 195 46 173 75 157" />
                <path d="M224 230C240 207 257 194 278 188" />
                <path d="M93 149C72 127 62 102 65 76" />
                <path d="M168 168C187 183 198 201 201 222" />
                <path d="M28 480C12 450 9 420 17 389" />
            </svg>

            <span class="contact-art-caption">L.</span>
        </div>

        <div class="contact-intro-content">

            <div class="contact-eyebrow">
                <span>NA KONTAKTO</span>
                <i></i>
            </div>

            <h1>
                Kontakti
                <span>LUMIÈRE</span>
            </h1>

            <p class="contact-intro-text">
                Jemi gjithmonë këtu për të të ndihmuar,
                për t'iu përgjigjur pyetjeve të tua dhe
                për të të shoqëruar në rrugëtimin tënd të bukurisë.
            </p>

            <div class="contact-details">

                <div class="contact-detail">
                    <span class="contact-detail-icon">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"/>
                            <circle cx="12" cy="10" r="2.5"/>
                        </svg>
                    </span>
                    <div>
                        <strong>Lokacioni ynë</strong>
                        <span>Prizren, Kosovë</span>
                    </div>
                </div>

                <div class="contact-detail">
                    <span class="contact-detail-icon">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M7 3H4a1 1 0 0 0-1 1c0 9.4 7.6 17 17 17a1 1 0 0 0 1-1v-3l-5-2-2 3a15 15 0 0 1-7-7l3-2-2-5Z"/>
                        </svg>
                    </span>
                    <div>
                        <strong>Telefoni</strong>
                        <span>+383 44 123 456</span>
                    </div>
                </div>

                <div class="contact-detail">
                    <span class="contact-detail-icon">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <rect x="3" y="5" width="18" height="14" rx="2"/>
                            <path d="m4 7 8 6 8-6"/>
                        </svg>
                    </span>
                    <div>
                        <strong>Email</strong>
                        <span>info@lumierebeauty.com</span>
                    </div>
                </div>

                <div class="contact-detail">
                    <span class="contact-detail-icon">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <circle cx="12" cy="12" r="9"/>
                            <path d="M12 7v5l3 2"/>
                        </svg>
                    </span>
                    <div>
                        <strong>Orari i punës</strong>
                        <span>Hënë – Shtunë: 09:00 – 18:00</span>
                    </div>
                </div>

            </div>

            <a class="contact-map-link"
               href="https://www.google.com/maps/search/?api=1&query=Prizren%2C+Kosovo"
               target="_blank"
               rel="noopener noreferrer">

                <span class="contact-map-symbol">↗</span>

                <span>
                    <strong>Na gjeni në Prizren</strong>
                    <small>Shiko lokacionin në Google Maps</small>
                </span>

                <span class="contact-map-arrow">→</span>
            </a>

        </div>
    </section>


    <!-- formulari i kontaktit -->
    <section class="contact-form-section">

        <div class="contact-form-card">

            <div class="contact-form-decoration" aria-hidden="true">
                <svg viewBox="0 0 70 55" fill="none">
                    <path d="M35 48C18 40 7 27 7 14C21 12 32 23 35 48Z"/>
                    <path d="M35 48C52 40 63 27 63 14C49 12 38 23 35 48Z"/>
                    <path d="M35 48C27 29 29 12 35 4C41 12 43 29 35 48Z"/>
                </svg>
            </div>

            <div class="contact-form-heading">
                <span class="contact-eyebrow contact-eyebrow-center">
                    NA DËRGO NJË MESAZH
                </span>

                <h2>Si mund të të ndihmojmë?</h2>

                <p>
                    Plotëso formularin më poshtë dhe ekipi ynë
                    do të të kontaktojë sa më shpejt që të jetë e mundur.
                </p>
            </div>

            <?php if ($status === 'success'): ?>
                <div class="contact-alert contact-alert-success">
                    Mesazhi yt u dërgua me sukses. Faleminderit!
                </div>
            <?php elseif ($status === 'error'): ?>
                <div class="contact-alert contact-alert-error">
                    Ndodhi një gabim. Të lutemi provo përsëri.
                </div>
            <?php elseif ($status === 'invalid'): ?>
                <div class="contact-alert contact-alert-error">
                    Kontrollo të dhënat dhe provo përsëri.
                </div>
            <?php endif; ?>

            <form action="contact-submit.php" method="POST" class="contact-form">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars($_SESSION['contact_csrf'], ENT_QUOTES, 'UTF-8') ?>"
                >

                <!-- FUSHA E FSHEHTË PËR MBROJTJE NGA SPAM -->
                <div class="contact-honeypot" aria-hidden="true">
                    <label for="website">Website</label>
                    <input
                        type="text"
                        id="website"
                        name="website"
                        tabindex="-1"
                        autocomplete="off"
                    >
                </div>

                <div class="contact-form-grid">

                    <div class="contact-field">
                        <label for="contact-name">Emri dhe mbiemri <span>*</span></label>
                        <input
                            type="text"
                            id="contact-name"
                            name="name"
                            placeholder="Shkruaj emrin dhe mbiemrin"
                            maxlength="150"
                            autocomplete="name"
                            required
                        >
                    </div>

                    <div class="contact-field">
                        <label for="contact-email">Adresa elektronike <span>*</span></label>
                        <input
                            type="email"
                            id="contact-email"
                            name="email"
                            placeholder="email@shembull.com"
                            maxlength="150"
                            autocomplete="email"
                            required
                        >
                    </div>

                    <div class="contact-field">
                        <label for="contact-phone">Numri i telefonit</label>
                        <input
                            type="tel"
                            id="contact-phone"
                            name="phone"
                            placeholder="+383 ..."
                            maxlength="30"
                            autocomplete="tel"
                        >
                    </div>

                    <div class="contact-field">
                        <label for="contact-subject">Tema e mesazhit <span>*</span></label>
                        <select id="contact-subject" name="subject" required>
                            <option value="" disabled selected>Zgjidh temën</option>
                            <option value="General inquiry">Pyetje të përgjithshme</option>
                            <option value="Services information">Informata për shërbimet</option>
                            <option value="Appointment inquiry">Pyetje për termin</option>
                            <option value="Prices and offers">Çmimet dhe ofertat</option>
                            <option value="Other">Tjetër</option>
                        </select>
                    </div>

                    <div class="contact-field contact-field-full">
                        <label for="contact-message">Mesazhi yt <span>*</span></label>
                        <textarea
                            id="contact-message"
                            name="message"
                            rows="5"
                            maxlength="2000"
                            placeholder="Shkruaj mesazhin tënd këtu..."
                            required
                        ></textarea>

                        <div class="contact-character-count">
                            <span id="contact-char-count">0</span> / 2000
                        </div>
                    </div>

                </div>

                <button type="submit" class="contact-submit">
                    <span>Dërgo mesazhin</span>
                    <span class="contact-submit-arrow">→</span>
                </button>

                <p class="contact-form-note">
                    Me kënaqësi presim të dëgjojmë nga ti.
                </p>

            </form>
        </div>

        <div class="contact-side-note">
            <span class="contact-side-number">01</span>

            <p>
                Një bisedë e vogël mund të jetë fillimi
                i diçkaje të bukur.
            </p>

            <span class="contact-side-line"></span>

            <span class="contact-side-brand">LUMIÈRE BEAUTY STUDIO</span>
        </div>

    </section>

</main>

<script src="assets/js/contact.js"></script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
