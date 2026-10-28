
<?php

$statistics = $data['statistics'] ?? [];
$clients = $data['clients'] ?? [];
$search = $data['search'] ?? '';

function clientEscape($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>

<section class="client-intelligence">

    <header class="client-heading">
        <div>
            <span class="client-eyebrow">LUMIÈRE / CLIENT INTELLIGENCE</span>
            <h1>Njih klientët e tu<span>.</span></h1>
            <p>Një pamje inteligjente e marrëdhënieve me klientët e studios.</p>
        </div>

        <a class="client-primary-button" href="klientet_new.php?shto=1">
            + Regjistro klient
        </a>
    </header>

    <div class="client-statistics">

        <article class="client-stat-card">
            <span class="client-stat-icon">♙</span>
            <div>
                <p>Gjithsej klientë</p>
                <strong><?= (int) ($statistics['total_clients'] ?? 0) ?></strong>
            </div>
        </article>

        <article class="client-stat-card">
            <span class="client-stat-icon">✧</span>
            <div>
                <p>Klientë të rinj këtë muaj</p>
                <strong><?= (int) ($statistics['new_this_month'] ?? 0) ?></strong>
            </div>
        </article>

    </div>

    <div class="client-directory">

        <div class="client-directory-heading">
            <div>
                <span class="client-eyebrow">CUSTOMER DATABASE</span>
                <h2>Client Directory</h2>
                <p>Kërko klientët dhe analizo aktivitetin e tyre.</p>
            </div>

            <span class="client-result-count">
                <?= count($clients) ?> rezultate
            </span>
        </div>

        <form method="GET" action="klientet_new.php" class="client-search">
            <input
                type="search"
                name="kerko"
                value="<?= clientEscape($search) ?>"
                placeholder="Kërko sipas emrit, telefonit ose emailit..."
            >

            <button type="submit">Kërko</button>

            <a href="klientet_new.php">Pastro</a>
        </form>

        <div class="client-table-wrapper">

            <table class="client-table">

                <thead>
                    <tr>
                        <th>Klienti</th>
                        <th>Kontaktet</th>
                        <th>Vizita të përfunduara</th>
                        <th>Pagesat totale</th>
                        <th>Segmenti</th>
                        <th>Veprimet</th>
                    </tr>
                </thead>

                <tbody>

                <?php foreach ($clients as $client): ?>

                    <tr>

                        <td>
                            <strong>
                                <?= clientEscape(
                                    ($client['emri'] ?? '') . ' ' .
                                    ($client['mbiemri'] ?? '')
                                ) ?>
                            </strong>

                            <small>
                                CLIENT #<?= (int) ($client['klient_id'] ?? 0) ?>
                            </small>
                        </td>

                        <td>
                            <?= clientEscape($client['telefoni'] ?: 'Pa telefon') ?>

                            <small>
                                <?= clientEscape($client['email'] ?: 'Pa email') ?>
                            </small>
                        </td>

                        <td>
                            <?= (int) ($client['completed_visits'] ?? 0) ?>
                        </td>

                        <td>
                            <?= number_format(
                                (float) ($client['total_paid'] ?? 0),
                                2
                            ) ?> €
                        </td>

                        <td>
                            <span class="client-segment">
                                <?= clientEscape($client['segment'] ?? 'Klient i ri') ?>
                            </span>
                        </td>

                        <td>
                            <div class="client-actions">

                                <a
                                    class="client-action"
                                    href="klientet_new.php?profile=<?= (int) ($client['klient_id'] ?? 0) ?>"
                                >
                                    Shiko profilin
                                </a>

                                <form
                                    method="POST"
                                    action="klientet_new.php"
                                    onsubmit="return confirm('A je e sigurt që dëshiron ta fshish këtë klient?');"
                                >
                                    <input
                                        type="hidden"
                                        name="action"
                                        value="delete"
                                    >

                                    <input
                                        type="hidden"
                                        name="klient_id"
                                        value="<?= (int) ($client['klient_id'] ?? 0) ?>"
                                    >

                                    <button
                                        type="submit"
                                        class="client-delete-button"
                                    >
                                        Fshij
                                    </button>
                                </form>

                            </div>
                        </td>

                    </tr>

                <?php endforeach; ?>

                <?php if (empty($clients)): ?>
                    <tr>
                        <td colspan="6" class="client-empty">
                            Nuk u gjetën klientë. Provo një kërkim tjetër.
                        </td>
                    </tr>
                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</section>
