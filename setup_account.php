<?php
// setup_account.php
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
if (!empty($_SERVER['HTTPS']) &&$_SERVER['HTTPS'] !== 'off') {
    ini_set('session.cookie_secure', '1');
}

session_start();
require_once __DIR__ . '/db.php';

$userId =$_SESSION['user_id'] ?? null;
if (!$userId) {
    header('Location: index.php');
    exit;
}

$pdo = getDBConnection();$error = null;

// Verify if user actually needs setup
$check =$pdo->prepare("SELECT email, force_setup FROM users WHERE id = ?");
$check->execute([$userId]);
$user =$check->fetch(PDO::FETCH_ASSOC);

if (!$user || ($user['force_setup'] == 0 && !empty($user['email']))) {
    unset($_SESSION['pending_setup']);
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $newPass =$_POST['new_password'] ?? '';
    $confirmPass =$_POST['confirm_password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {$error = "Please enter a valid email address.";
    } elseif (strlen($newPass) < 8) {$error = "Your new password must be at least 8 characters.";
    } elseif ($newPass !== $confirmPass) {$error = "Passwords do not match.";
    } else {
        // Prevent duplicate emails
        $dupStmt =$pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $dupStmt->execute([$email,$userId]);
        if ($dupStmt->fetch()) {$error = "That email address is already bound to another account.";
        } else {
            $hash = password_hash($newPass, PASSWORD_DEFAULT);
            $upd =$pdo->prepare("UPDATE users SET email = ?, password_hash = ?, force_setup = 0 WHERE id = ?");
            $upd->execute([$email, $hash,$userId]);

            $_SESSION['email'] =$email;
            unset($_SESSION['pending_setup']);

            // Append to audit trail
            $logDir = __DIR__ . '/logs';
            if (is_dir($logDir)) {$audit = [
                    'timestamp' => date('Y-m-d H:i:s'),
                    'user_id'   => $userId,
                    'username'  => $_SESSION['username'],
                    'role'      => $_SESSION['role'],
                    'action'    => 'ACCOUNT_ACTIVATED',
                    'email'     => $email,
                    'ip'        => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
                ];
                @file_put_contents($logDir . '/audit.log', json_encode($audit) . PHP_EOL, FILE_APPEND | LOCK_EX);
            }

            header('Location: index.php?activated=1');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Complete Moderator Setup | SoCal Pauper</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#0c0e12] text-zinc-200 min-h-screen flex items-center justify-center p-4">

  <div class="max-w-md w-full bg-[#161922] border border-[#2a3040] rounded-2xl p-6 sm:p-8 shadow-2xl space-y-5">
    <div>
      <span class="text-[10px] font-black uppercase tracking-wider text-amber-500 bg-amber-950/60 border border-amber-800 px-2 py-0.5 rounded">First-Time Setup</span>
      <h2 class="text-xl font-bold text-white mt-2">Welcome, <?= htmlspecialchars($_SESSION['username']) ?>!</h2>
      <p class="text-xs text-zinc-400 mt-1">Please register your personal email address and choose your private permanent password to activate your organizer account.</p>
    </div>

    <?php if ($error): ?>
      <div class="p-3 rounded-lg bg-rose-950/60 border border-rose-800 text-rose-300 text-xs">
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form method="POST" class="space-y-4 text-xs">
      <div>
        <label class="block text-zinc-300 font-semibold mb-1">Your Email Address</label>
        <input type="email" name="email" required placeholder="organizer@store.com"
               class="w-full bg-[#0c0e12] border border-[#2a3040] rounded-lg px-3 py-2 text-zinc-100 focus:outline-none focus:border-amber-500">
      </div>

      <div>
        <label class="block text-zinc-300 font-semibold mb-1">Set New Private Password</label>
        <input type="password" name="new_password" required minlength="8" placeholder="••••••••••••"
               class="w-full bg-[#0c0e12] border border-[#2a3040] rounded-lg px-3 py-2 text-zinc-100 focus:outline-none focus:border-amber-500">
      </div>

      <div>
        <label class="block text-zinc-300 font-semibold mb-1">Confirm New Password</label>
        <input type="password" name="confirm_password" required minlength="8" placeholder="••••••••••••"
               class="w-full bg-[#0c0e12] border border-[#2a3040] rounded-lg px-3 py-2 text-zinc-100 focus:outline-none focus:border-amber-500">
      </div>

      <button type="submit" class="w-full py-2.5 bg-amber-600 hover:bg-amber-500 text-white font-bold rounded-lg transition shadow-md mt-2">
        Activate Account & Continue
      </button>
    </form>
  </div>

</body>
</html>