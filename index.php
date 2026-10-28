<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="LUMIÈRE Beauty Studio - Your glow, your moment.">
    <title>LUMIÈRE | Beauty Studio</title>

    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <?php include 'includes/header.php'; ?>

    <main>
        <section class="hero">
            <div class="hero-content">
                <p class="eyebrow">NATURAL · MODERN · CONFIDENT</p>

                <h1>
                    Beauty that feels
                    <br>
                    like you.
                </h1>

                <p class="hero-description">
                    Premium beauty services for your natural glow.
                    <br>
                    Look good, feel even better.
                </p>

                <a href="#booking" class="booking-button hero-button">
                    BOOK AN APPOINTMENT <span>↗</span>
                </a>

                <p class="hero-note">YOUR BEAUTY. YOUR MOMENT.</p>
            </div>

            <div class="hero-image">
                <img
                    src="https://images.unsplash.com/photo-1524504388940-b1c1722653e1?auto=format&fit=crop&w=1000&q=85"
                    alt="Natural beauty portrait"
                >
            </div>
        </section>

        <section class="services" id="services">
            <div class="section-heading">
                <p class="eyebrow">OUR SERVICES</p>
                <h2>A little moment, just for you.</h2>
            </div>

            <div class="service-grid">
                <article class="service-card">
                    <h3>Hair</h3>
                    <p>Cuts, colour and styling</p>
                </article>

                <article class="service-card">
                    <h3>Nails</h3>
                    <p>Manicure, gel and nail art</p>
                </article>

                <article class="service-card">
                    <h3>Skincare</h3>
                    <p>Facials and skin treatments</p>
                </article>

                <article class="service-card">
                    <h3>Lashes</h3>
                    <p>Lash lift and extensions</p>
                </article>

                <article class="service-card">
                    <h3>Makeup</h3>
                    <p>Natural and glam looks</p>
                </article>
            </div>
        </section>

        <section class="about" id="about">
            <p class="eyebrow">WELCOME TO LUMIÈRE</p>
            <h2>A beauty space made for you.</h2>
            <p>
                A modern and relaxing space where beauty,
                confidence and self-care come together.
            </p>
        </section>

        <section class="booking" id="booking">
            <p class="eyebrow">YOUR MOMENT STARTS HERE</p>
            <h2>Ready for your glow?</h2>
            <p>Choose your favourite service and book your appointment.</p>
            <a href="#contact" class="booking-button">
                GET IN TOUCH <span>↗</span>
            </a>
        </section>

        <section class="contact" id="contact">
            <p class="eyebrow">WE WOULD LOVE TO HEAR FROM YOU</p>
            <h2>Let's get in touch.</h2>
            <p>Contact LUMIÈRE Beauty Studio for more information.</p>
        </section>
    </main>

    <?php include 'includes/footer.php'; ?>

</body>
</html>

