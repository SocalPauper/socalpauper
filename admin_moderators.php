<?php
// admin_moderators.php
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    ini_set('session.cookie_secure', '1');
}

session_start();
require_once __DIR__ . '/db.php';

$userId = $_SESSION['user_id'] ?? null;
$role = $_SESSION['role'] ?? null;

// Only master admins can access this page
if (!$userId || $role !== 'admin') {
    header('Location: index.php');
    exit;
}

$pdo = getDBConnection();
$feedback = null;
$newCredentials = null;

// Revoke an account
if (isset($_GET['revoke_id'])) {
    $revokeId = (int)$_GET['revoke_id'];
    if ($revokeId !== (int)$userId) {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
        $stmt->execute([$revokeId]);
        header('Location: admin_moderators.php?msg=revoked');
        exit;
    }
}

// Provision account
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'provision') {
    $username = trim($_POST['username'] ?? '');
    $assignRole = in_array($_POST['role'] ?? '', ['moderator', 'admin']) ? $_POST['role'] : 'moderator';

    $cleanUsername = preg_replace('/[^a-zA-Z0-9_\-]/', '', $username);

    if (empty($cleanUsername) || strlen($cleanUsername) < 3) {
        $feedback = ['type' => 'error', 'msg' => 'Username must be at least 3 characters and contain only letters, numbers, hyphens, or underscores.'];
    } else {
        $check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $check->execute([$cleanUsername]);
        if ($check->fetch()) {
            $feedback = ['type' => 'error', 'msg' => "The username '{$cleanUsername}' is already taken."];
        } else {
            $tempPass = 'SoCal-' . bin2hex(random_bytes(3)) . '!' . rand(10, 99);
            $hash = password_hash($tempPass, PASSWORD_DEFAULT);

            $ins = $pdo->prepare("
                INSERT INTO users (username, password_hash, role, force_setup, email)
                VALUES (?, ?, ?, 1, NULL)
            ");
            $ins->execute([$cleanUsername, $hash, $assignRole]);

            $newCredentials = [
                'username'  => $cleanUsername,
                'temp_pass' => $tempPass,
                'role'      => $assignRole
            ];

            $feedback = ['type' => 'success', 'msg' => "Account provisioned for <strong>{$cleanUsername}</strong>!"];
        }
    }
}

$staff = $pdo->query("
    SELECT id, username, email, role, force_setup, created_at
    FROM users
    WHERE role IN ('admin', 'moderator')
    ORDER BY role ASC, created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Staff & Moderator Provisioning — SoCal Pauper</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-[#0c0e12] text-zinc-200 min-h-screen flex flex-col">

  <header class="bg-[#12151e] border-b border-[#2a3040] px-6 py-4 flex items-center justify-between sticky top-0 z-40">
    <div class="flex items-center gap-3">
      <a href="index.php" class="text-amber-400 font-bold hover:text-white transition text-xs flex items-center gap-1.5">
        <i class="fa-solid fa-arrow-left"></i> Main Site
      </a>
      <span class="text-zinc-600">/</span>
      <a href="admin_broadcast.php" class="text-purple-400 font-bold hover:text-white transition text-xs flex items-center gap-1.5">
        <i class="fa-solid fa-bullhorn"></i> Broadcasts
      </a>
      <span class="text-zinc-600">/</span>
      <h1 class="text-sm font-bold text-white flex items-center gap-2">
        <span class="px-2 py-0.5 rounded bg-emerald-950/80 text-emerald-300 border border-emerald-700/80 text-[10px] font-black uppercase tracking-wider">Access Control</span>
        Staff & Moderator Provisioning
      </h1>
    </div>

    <div class="flex items-center gap-3 text-xs">
      <span class="text-zinc-400">Master Admin: <strong class="text-purple-400"><?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></strong></span>
      <a href="api_auth.php?action=logout" class="px-2.5 py-1 bg-zinc-800 hover:bg-zinc-700 text-zinc-300 rounded border border-zinc-700 transition">Log Out</a>
    </div>
  </header>

  <main class="flex-1 max-w-4xl w-full mx-auto p-6 space-y-6">

    <?php if ($feedback): ?>
      <div class="p-4 rounded-xl border text-xs <?= $feedback['type'] === 'success' ? 'bg-emerald-950/60 border-emerald-800 text-emerald-300' : 'bg-rose-950/60 border-rose-800 text-rose-300' ?>">
        <?= $feedback['msg'] ?>
      </div>
    <?php endif; ?>

    <?php if ($newCredentials): ?>
      <?php 
        $discordSlip = "Hey! Here are your temporary organizer credentials for SoCal Pauper:\n" .
                       "• Site: https://socalpauper.com\n" .
                       "• Username: " . $newCredentials['username'] . "\n" .
                       "• Temporary Password: " . $newCredentials['temp_pass'] . "\n\n" .
                       "When you first log in, you will be prompted to enter your personal email address and choose your own permanent private password.";
      ?>
      <div class="p-5 rounded-2xl bg-gradient-to-r from-amber-950/70 via-[#181d28] to-emerald-950/70 border border-amber-500/60 shadow-2xl space-y-3">
        <div class="flex items-center justify-between">
          <span class="text-xs font-black uppercase tracking-wider text-amber-300 flex items-center gap-1.5">
            <i class="fa-solid fa-key"></i> New Account Provision Slip
          </span>
          <button type="button" onclick="copyDiscordSlip()" class="px-3 py-1 rounded bg-amber-500 hover:bg-amber-400 text-zinc-950 font-bold text-xs transition flex items-center gap-1.5 shadow">
            <i class="fa-solid fa-copy"></i> Copy Discord Message
          </button>
        </div>

        <p class="text-xs text-zinc-300">
          Copy and send these credentials to the store organizer. They will be locked into entering their personal email and setting their permanent password upon logging in.
        </p>

        <textarea id="discord-slip-text" readonly rows="5" class="w-full bg-[#0c0e12] border border-amber-500/40 rounded-xl p-3 text-xs font-mono text-zinc-200 focus:outline-none"><?= htmlspecialchars($discordSlip) ?></textarea>
      </div>
    <?php endif; ?>

    <div class="bg-[#161922] border border-[#2a3040] rounded-2xl p-6 shadow-xl space-y-4">
      <div>
        <h2 class="text-sm font-bold text-white uppercase tracking-wider flex items-center gap-2">
          <i class="fa-solid fa-user-plus text-amber-500"></i> Provision a New Partner / Organizer Account
        </h2>
        <p class="text-xs text-zinc-400 mt-1">
          Moderators can create and edit tournaments and store directory listings, but cannot delete records or broadcast emails.
        </p>
      </div>

      <form method="POST" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
        <input type="hidden" name="action" value="provision">

        <div class="sm:col-span-6">
          <label class="block text-[11px] font-bold text-zinc-300 uppercase tracking-wider mb-1">Account Handle / Store Identifier</label>
          <input type="text" name="username" required placeholder="e.g. GeekyTeas_Staff or DoubleD20" 
                 class="w-full bg-[#0c0e12] border border-[#2a3040] rounded-lg px-3.5 py-2 text-xs text-white placeholder-zinc-600 focus:outline-none focus:border-amber-500">
        </div>

        <div class="sm:col-span-3">
          <label class="block text-[11px] font-bold text-zinc-300 uppercase tracking-wider mb-1">Access Role</label>
          <select name="role" class="w-full bg-[#0c0e12] border border-[#2a3040] rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-amber-500">
            <option value="moderator" selected>Moderator (Edit Only)</option>
            <option value="admin">Admin (Full Access)</option>
          </select>
        </div>

        <div class="sm:col-span-3">
          <button type="submit" class="w-full py-2 bg-gradient-to-r from-amber-600 to-amber-500 hover:from-amber-500 hover:to-amber-400 text-zinc-950 font-bold text-xs rounded-lg transition shadow flex items-center justify-center gap-1.5">
            <i class="fa-solid fa-paper-plane"></i> Generate Account
          </button>
        </div>
      </form>
    </div>

    <div class="bg-[#161922] border border-[#2a3040] rounded-2xl p-6 shadow-xl space-y-4">
      <div class="flex items-center justify-between">
        <h2 class="text-sm font-bold text-white uppercase tracking-wider flex items-center gap-2">
          <i class="fa-solid fa-users-gear text-sky-400"></i> Active Staff & Partner Directory
        </h2>
        <span class="text-xs text-zinc-500 font-mono"><?= count($staff) ?> Staff Members</span>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead>
            <tr class="border-b border-[#2a3040] text-zinc-400 uppercase text-[10px] tracking-wider">
              <th class="pb-2.5">User</th>
              <th class="pb-2.5">Role</th>
              <th class="pb-2.5">Bound Email</th>
              <th class="pb-2.5">Status</th>
              <th class="pb-2.5 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[#2a3040]/60">
            <?php foreach ($staff as $s): ?>
              <tr>
                <td class="py-3 font-semibold text-white flex items-center gap-2">
                  <span class="w-6 h-6 rounded-full bg-zinc-800 border border-zinc-700 flex items-center justify-center text-[10px] text-zinc-300 font-bold">
                    <?= strtoupper(substr($s['username'], 0, 1)) ?>
                  </span>
                  <?= htmlspecialchars($s['username']) ?>
                </td>
                <td class="py-3">
                  <?php if ($s['role'] === 'admin'): ?>
                    <span class="px-2 py-0.5 rounded bg-purple-950/80 text-purple-300 border border-purple-800 text-[10px] font-bold uppercase">Admin</span>
                  <?php else: ?>
                    <span class="px-2 py-0.5 rounded bg-sky-950/80 text-sky-300 border border-sky-800 text-[10px] font-bold uppercase">Moderator</span>
                  <?php endif; ?>
                </td>
                <td class="py-3 font-mono text-zinc-300">
                  <?= !empty($s['email']) ? htmlspecialchars($s['email']) : '<span class="text-zinc-600 italic">None yet</span>' ?>
                </td>
                <td class="py-3">
                  <?php if ($s['force_setup'] == 1 || empty($s['email'])): ?>
                    <span class="inline-flex items-center gap-1 text-[11px] font-bold text-amber-400 bg-amber-950/50 px-2 py-0.5 rounded border border-amber-800/60">
                      <i class="fa-solid fa-clock"></i> Pending Setup
                    </span>
                  <?php else: ?>
                    <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-400 bg-emerald-950/50 px-2 py-0.5 rounded border border-emerald-800/60">
                      <i class="fa-solid fa-check"></i> Claimed & Active
                    </span>
                  <?php endif; ?>
                </td>
                <td class="py-3 text-right">
                  <?php if ($s['id'] !== (int)$userId && $s['role'] !== 'admin'): ?>
                    <a href="admin_moderators.php?revoke_id=<?= (int)$s['id'] ?>" 
                       onclick="return confirm('Revoke all access for <?= htmlspecialchars($s['username']) ?>? This action is immediate.')" 
                       class="text-rose-400 hover:text-rose-300 font-semibold px-2.5 py-1 rounded bg-rose-950/50 border border-rose-900 transition text-[11px]">
                      Revoke Access
                    </a>
                  <?php else: ?>
                    <span class="text-zinc-600 text-[11px] italic">Primary Admin</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

  </main>

  <script>
    function copyDiscordSlip() {
      const copyText = document.getElementById("discord-slip-text");
      copyText.select();
      copyText.setSelectionRange(0, 99999);
      navigator.clipboard.writeText(copyText.value).then(() => {
        alert("Provision slip copied! Send it to the store organizer.");
      });
    }
  </script>
</body>
</html>