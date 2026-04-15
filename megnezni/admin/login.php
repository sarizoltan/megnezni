<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Ha már be van jelentkezve
if (isset($_SESSION['admin_id'])) {
    header('Location: ' . ADMIN_URL . '/index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email && $password) {
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE email=?");
        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id']   = $admin['id'];
            $_SESSION['admin_name'] = $admin['name'];
            $_SESSION['admin_role'] = $admin['role'];
            header('Location: ' . ADMIN_URL . '/index.php');
            exit;
        } else {
            $error = 'Hibás email cím vagy jelszó!';
        }
    } else {
        $error = 'Kérjük töltsd ki az összes mezőt!';
    }
}
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bejelentkezés – Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }

        :root {
            --gold:    #c8a96e;
            --gold-2:  #d4b887;
            --dark:    #0f0f0f;
            --dark-2:  #1a1a1a;
            --dark-3:  #242424;
            --border:  #2a2a2a;
            --text:    #e0e0e0;
            --muted:   #888;
            --red:     #ef4444;
            --radius:  12px;
        }

        body {
            background: var(--dark);
            color: var(--text);
            font-family: 'Segoe UI', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        /* Háttér animáció */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background:
                radial-gradient(ellipse at 20% 50%, rgba(200,169,110,.06) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 20%, rgba(200,169,110,.04) 0%, transparent 50%);
            pointer-events: none;
        }

        .login-wrap {
            width: 100%;
            max-width: 420px;
            position: relative;
            z-index: 1;
        }

        /* Logo */
        .login-logo {
            text-align: center;
            margin-bottom: 32px;
        }
        .login-logo .icon {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, var(--gold), var(--gold-2));
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: var(--dark);
            margin-bottom: 16px;
            box-shadow: 0 8px 32px rgba(200,169,110,.3);
        }
        .login-logo h1 {
            font-size: 24px;
            font-weight: 800;
            color: #fff;
            letter-spacing: 1px;
        }
        .login-logo p {
            font-size: 13px;
            color: var(--muted);
            margin-top: 4px;
        }

        /* Kártya */
        .login-card {
            background: var(--dark-2);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 36px;
            box-shadow: 0 24px 80px rgba(0,0,0,.5);
        }

        /* Hibaüzenet */
        .alert-error {
            background: rgba(239,68,68,.1);
            border: 1px solid rgba(239,68,68,.3);
            border-radius: 8px;
            padding: 12px 16px;
            color: #fca5a5;
            font-size: 13px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Form */
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .5px;
            margin-bottom: 8px;
        }
        .input-wrap {
            position: relative;
        }
        .input-wrap i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--muted);
            font-size: 14px;
        }
        .input-wrap input {
            width: 100%;
            background: var(--dark-3);
            border: 2px solid var(--border);
            border-radius: 10px;
            padding: 12px 14px 12px 40px;
            color: var(--text);
            font-size: 15px;
            transition: border-color .2s, box-shadow .2s;
            outline: none;
        }
        .input-wrap input:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(200,169,110,.15);
        }
        .input-wrap .toggle-pass {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--muted);
            cursor: pointer;
            font-size: 14px;
            padding: 0;
            transition: color .2s;
        }
        .input-wrap .toggle-pass:hover { color: var(--gold); }

        /* Gomb */
        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, var(--gold), var(--gold-2));
            border: none;
            border-radius: 10px;
            color: var(--dark);
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: opacity .2s, transform .2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 8px;
        }
        .btn-login:hover {
            opacity: .9;
            transform: translateY(-1px);
        }
        .btn-login:active { transform: translateY(0); }

        /* Footer */
        .login-footer {
            text-align: center;
            margin-top: 24px;
            font-size: 12px;
            color: var(--muted);
        }
    </style>
</head>
<body>

<div class="login-wrap">
    <div class="login-logo">
        <div class="icon"><i class="fas fa-calendar-check"></i></div>
        <h1>Foglalas.hu</h1>
        <p>Admin felület</p>
    </div>

    <div class="login-card">
        <?php if ($error): ?>
        <div class="alert-error">
            <i class="fas fa-exclamation-circle"></i> <?= e($error) ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="form-group">
                <label>Email cím</label>
                <div class="input-wrap">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email"
                           value="<?= e($_POST['email'] ?? '') ?>"
                           placeholder="admin@foglalas.hu"
                           autofocus required>
                </div>
            </div>
            <div class="form-group">
                <label>Jelszó</label>
                <div class="input-wrap">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" id="passInput"
                           placeholder="••••••••" required>
                    <button type="button" class="toggle-pass"
                            onclick="togglePass()">
                        <i class="fas fa-eye" id="passIcon"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt"></i> Bejelentkezés
            </button>
        </form>
    </div>

    <div class="login-footer">
        &copy; <?= date('Y') ?> Foglalas.hu – Minden jog fenntartva
    </div>
</div>

<script>
function togglePass() {
    const input = document.getElementById('passInput');
    const icon  = document.getElementById('passIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fas fa-eye';
    }
}
</script>
</body>
</html>