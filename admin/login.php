<?php
session_start();

require_once __DIR__ . '/../config/database.php';

if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    exit('Lidhja me databazën nuk u gjet. Kontrollo config/database.php.');
}

if (!empty($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

if (empty($_SESSION['login_csrf'])) {
    $_SESSION['login_csrf'] = bin2hex(random_bytes(32));
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = (string) ($_POST['csrf_token'] ?? '');
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (!hash_equals($_SESSION['login_csrf'], $csrf)) {
        $error = 'Seanca skadoi. Rifresko faqen dhe provo përsëri.';
    } elseif ($email === '' || $password === '') {
        $error = 'Plotëso email-in dhe fjalëkalimin.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email-i ose fjalëkalimi nuk është i saktë.';
    } else {
        try {
            $stmt = $pdo->prepare(
                'SELECT id, emri, email, password_hash, roli, aktiv
                 FROM administratoret
                 WHERE email = :email
                 LIMIT 1'
            );

            $stmt->execute(['email' => $email]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if (
                $admin &&
                (int) $admin['aktiv'] === 1 &&
                password_verify($password, $admin['password_hash'])
            ) {
                session_regenerate_id(true);

                $_SESSION['admin_id'] = (int) $admin['id'];
                $_SESSION['admin_emri'] = $admin['emri'];
                $_SESSION['admin_email'] = $admin['email'];
                $_SESSION['admin_role'] = $admin['roli'];

                unset($_SESSION['login_csrf']);

                header('Location: index.php');
                exit;
            }

            $error = 'Email-i ose fjalëkalimi nuk është i saktë.';

        } catch (PDOException $e) {
            $error = 'Gabimi i databazës: ' . $e->getMessage();
        }
    }
}

function login_h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Kyçu | LUMIÈRE Beauty Studio</title>

    <style>
        :root {
            --cream: #f7f4ef;
            --paper: #fffdf9;
            --brown: #302820;
            --soft: #8b8176;
            --gold: #947c64;
            --line: #eae2d8;
            --pale: #eee5da;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
            background: var(--cream);
            color: var(--brown);
            font-family: "Segoe UI", Arial, sans-serif;
        }

        .login-shell {
            width: min(950px, 100%);
            display: grid;
            grid-template-columns: 1fr 1fr;
            background: var(--paper);
            border: 1px solid var(--line);
            box-shadow: 0 20px 60px #30282012;
            border-radius: 14px;
            overflow: hidden;
        }

        .brand-panel {
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 48px 42px;
            background: var(--brown);
            color: #f8f3eb;
            min-height: 540px;
        }

        .brand-panel::after {
            content: "✧";
            position: absolute;
            right: 14px;
            bottom: 32px;
            color: #eee5da18;
            font-size: 220px;
            pointer-events: none;
        }

        .brand,
        .brand-copy,
        .brand-foot {
            position: relative;
            z-index: 1;
        }

        .brand {
            font: 400 31px Georgia, serif;
            letter-spacing: 4px;
        }

        .brand small {
            display: block;
            margin-top: 9px;
            color: #b9a995;
            font: 10px "Segoe UI", Arial, sans-serif;
            letter-spacing: 4px;
        }

        .brand-copy .eyebrow {
            margin-bottom: 16px;
            color: #b9a995;
            font-size: 10px;
            letter-spacing: 2px;
        }

        .brand-copy h1 {
            margin: 0;
            font: 400 clamp(34px, 4vw, 48px)/1.13 Georgia, serif;
        }

        .brand-copy p {
            max-width: 340px;
            margin-top: 18px;
            color: #d6cabe;
            font-size: 13px;
            line-height: 1.9;
        }

        .brand-foot {
            color: #b9a995;
            font: italic 12px Georgia, serif;
        }

        .form-panel {
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 48px 42px;
        }

        .form-eyebrow {
            color: var(--gold);
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 2px;
        }

        h2 {
            margin: 12px 0 8px;
            font: 400 32px Georgia, serif;
        }

        .subtitle {
            margin: 0 0 28px;
            color: var(--soft);
            font-size: 13px;
            line-height: 1.7;
        }

        .message {
            margin-bottom: 17px;
            padding: 12px 14px;
            border: 1px solid #efd2ca;
            border-radius: 6px;
            background: #f8e9e4;
            color: #914638;
            font-size: 12px;
            line-height: 1.6;
        }

        .field {
            margin-bottom: 18px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: var(--brown);
            font-size: 12px;
            font-weight: 600;
        }

        input {
            width: 100%;
            min-height: 45px;
            padding: 12px 13px;
            border: 1px solid var(--line);
            border-radius: 5px;
            background: #fff;
            color: var(--brown);
            font: inherit;
            font-size: 13px;
        }

        input:focus {
            outline: 2px solid #947c6430;
            border-color: var(--gold);
        }

        .submit-btn {
            width: 100%;
            min-height: 46px;
            margin-top: 6px;
            border: 1px solid var(--brown);
            border-radius: 5px;
            background: var(--brown);
            color: white;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: background .2s;
        }

        .submit-btn:hover {
            background: #514438;
        }

        .form-note {
            margin-top: 22px;
            padding-top: 17px;
            border-top: 1px solid var(--line);
            color: var(--soft);
            text-align: center;
            font-size: 11px;
            line-height: 1.7;
        }

        @media (max-width: 700px) {
            body {
                padding: 12px;
            }

            .login-shell {
                grid-template-columns: 1fr;
            }

            .brand-panel {
                min-height: 250px;
                padding: 28px 24px;
                gap: 35px;
            }

            .brand-copy h1 {
                font-size: 34px;
            }

            .brand-copy p {
                margin-top: 10px;
            }

            .brand-foot {
                display: none;
            }

            .brand-panel::after {
                font-size: 130px;
                bottom: 0;
            }

            .form-panel {
                padding: 32px 24px;
            }
        }
    </style>
</head>

<body>

<div class="login-shell">

    <section class="brand-panel">

        <div class="brand">
            LUMIÈRE
            <small>BEAUTY STUDIO</small>
        </div>

        <div class="brand-copy">
            <div class="eyebrow">MIRË SE VJEN</div>

            <h1>Bukuria qëndron në çdo detaj.</h1>

            <p>
                Hyr në panelin e administrimit për të menaxhuar
                terminet, klientët, stafin dhe aktivitetin e studios.
            </p>
        </div>

        <div class="brand-foot">
            Beauty is in every detail.
        </div>

    </section>

    <section class="form-panel">

        <div class="form-eyebrow">
            PANELI I ADMINISTRIMIT
        </div>

        <h2>Kyçu</h2>

        <p class="subtitle">
            Shkruaj të dhënat e llogarisë së administratorit për të vazhduar.
        </p>

        <?php if ($error !== ''): ?>
            <div class="message">
                <?= login_h($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php" autocomplete="on">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= login_h($_SESSION['login_csrf']) ?>"
            >

            <div class="field">
                <label for="email">Email-i</label>

                <input
                    id="email"
                    name="email"
                    type="email"
                    value="<?= login_h($_POST['email'] ?? '') ?>"
                    placeholder="admin@lumiere.com"
                    autocomplete="username"
                    required
                    autofocus
                >
            </div>

            <div class="field">
                <label for="password">Fjalëkalimi</label>

                <input
                    id="password"
                    name="password"
                    type="password"
                    placeholder="Shkruaj fjalëkalimin"
                    autocomplete="current-password"
                    required
                >
            </div>

            <button class="submit-btn" type="submit">
                Kyçu në sistem &nbsp; →
            </button>

        </form>

        <div class="form-note">
            LUMIÈRE Beauty Studio<br>
            Qasje vetëm për personelin e autorizuar.
        </div>

    </section>

</div>

</body>
</html>