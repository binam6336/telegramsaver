<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
auth_boot();

if (auth_check()) {
    $next = auth_sanitize_next((string)($_GET['next'] ?? ''));
    header('Location: ' . $next);
    exit;
}

$error    = '';
$username = '';
$nextPost = auth_sanitize_next((string)($_POST['next'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    $result = auth_login($username, $password);

    if ($result['ok']) {
        header('Location: ' . $nextPost);
        exit;
    }
    $error = $result['error'];
}

$nextGet  = (string)($_GET['next'] ?? '');
$nextVal  = $nextPost !== '' ? $nextPost : $nextGet;
$nextHtml = htmlspecialchars($nextVal, ENT_QUOTES);
$errHtml  = $error !== '' ? htmlspecialchars($error, ENT_QUOTES) : '';
$userHtml = htmlspecialchars($username, ENT_QUOTES);
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ورود به پنل مدیریت</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0
        }

        html,
        body {
            height: 100%;
            font-family: 'Segoe UI', Tahoma, Arial, sans-serif;
            direction: rtl;
            background: linear-gradient(135deg, #4c8bf5 0%, #2f6bdc 100%);
        }

        body {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            min-height: 100vh
        }

        .login-box {
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, .25);
            width: 100%;
            max-width: 400px;
            padding: 36px 32px;
            animation: slideUp .4s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px)
            }

            to {
                opacity: 1;
                transform: translateY(0)
            }
        }

        .login-logo {
            text-align: center;
            margin-bottom: 24px
        }

        .login-logo-icon {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: linear-gradient(135deg, #4c8bf5, #2f6bdc);
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            margin-bottom: 14px;
            box-shadow: 0 8px 24px rgba(76, 139, 245, .4);
        }

        .login-logo h1 {
            font-size: 1.2rem;
            color: #1e293b;
            font-weight: 800
        }

        .login-logo p {
            font-size: .82rem;
            color: #94a3b8;
            margin-top: 4px
        }

        .field {
            margin-bottom: 16px
        }

        .field label {
            display: block;
            font-size: .8rem;
            font-weight: 700;
            color: #475569;
            margin-bottom: 6px
        }

        .field .input-wrap {
            position: relative
        }

        .field .input-wrap i {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: .9rem;
            pointer-events: none;
        }

        .field input {
            width: 100%;
            padding: 12px 42px 12px 14px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            font-family: inherit;
            font-size: .9rem;
            outline: none;
            transition: border-color .2s, box-shadow .2s;
            background: #fff;
            color: #1e293b;
        }

        .field input:focus {
            border-color: #4c8bf5;
            box-shadow: 0 0 0 3px rgba(76, 139, 245, .15)
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
            padding: 11px 14px;
            border-radius: 10px;
            font-size: .82rem;
            font-weight: 600;
            margin-bottom: 16px;
            border: 1px solid #fecaca;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .error i {
            flex-shrink: 0
        }

        .btn-login {
            width: 100%;
            padding: 13px;
            border: 0;
            border-radius: 10px;
            background: linear-gradient(135deg, #4c8bf5 0%, #2f6bdc 100%);
            color: #fff;
            font-family: inherit;
            font-size: .9rem;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 4px 16px rgba(76, 139, 245, .35);
            transition: opacity .2s, transform .1s;
            margin-top: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-login:hover {
            opacity: .92
        }

        .btn-login:active {
            transform: scale(.98)
        }

        .btn-login:disabled {
            opacity: .6;
            cursor: not-allowed
        }

        .footer-note {
            text-align: center;
            font-size: .72rem;
            color: #94a3b8;
            margin-top: 22px
        }

        @media (max-width:480px) {
            .login-box {
                padding: 28px 22px
            }
        }
    </style>
</head>

<body>
    <div class="login-box">
        <div class="login-logo">
            <div class="login-logo-icon"><i class="fas fa-paper-plane"></i></div>
            <h1>پنل مدیریت تلگرام</h1>
            <p>لطفاً وارد حساب خود شوید</p>
        </div>

        <?php if ($errHtml !== ''): ?>
            <div class="error">
                <i class="fas fa-exclamation-circle"></i>
                <span><?= $errHtml ?></span>
            </div>
        <?php endif; ?>

        <form method="post" autocomplete="on">
            <input type="hidden" name="next" value="<?= $nextHtml ?>">

            <div class="field">
                <label for="username">نام کاربری</label>
                <div class="input-wrap">
                    <i class="fas fa-user"></i>
                    <input type="text" id="username" name="username"
                        value="<?= $userHtml ?>"
                        autocomplete="username" required autofocus>
                </div>
            </div>

            <div class="field">
                <label for="password">رمز عبور</label>
                <div class="input-wrap">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password"
                        autocomplete="current-password" required>
                </div>
            </div>

            <button type="submit" class="btn-login" id="loginBtn">
                <i class="fas fa-sign-in-alt"></i>
                <span>ورود به پنل</span>
            </button>
        </form>

        <div class="footer-note">دسترسی فقط برای مدیران مجاز</div>
    </div>

    <script>
        (function() {
            const form = document.querySelector('form');
            const btn = document.getElementById('loginBtn');
            form.addEventListener('submit', () => {
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i><span>در حال ورود...</span>';
            });
        })();
    </script>
</body>

</html>