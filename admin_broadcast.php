<?php
// admin_broadcast.php
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    ini_set('session.cookie_secure', '1');
}

session_start();
require_once __DIR__ . '/db.php';

$userId = $_SESSION['user_id'] ?? null;
$role = $_SESSION['role'] ?? null;

// Enforce Admin Authentication
if (!$userId || $role !== 'admin') {
    header('Location: decks.php');
    exit;
}

$pdo = getDBConnection();
$feedback = null;

// Auto-create broadcasts table if missing
$pdo->exec("
    CREATE TABLE IF NOT EXISTS `broadcasts` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `subject` VARCHAR(255) NOT NULL,
      `message` TEXT NOT NULL,
      `audience` VARCHAR(50) NOT NULL DEFAULT 'events',
      `show_popup` TINYINT(1) NOT NULL DEFAULT 1,
      `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
      `expires_at` DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// Handle manual removal of active popup announcements
if (isset($_GET['delete_popup'])) {
    $deleteId = (int)$_GET['delete_popup'];
    $delStmt = $pdo->prepare("DELETE FROM broadcasts WHERE id = ?");
    $delStmt->execute([$deleteId]);
    header('Location: admin_broadcast.php?deleted=1');
    exit;
}

// Audience statistics query
$statsStmt = $pdo->query("
    SELECT 
        COUNT(CASE WHEN email IS NOT NULL AND email != '' AND notify_events = 1 THEN 1 END) AS count_events,
        COUNT(CASE WHEN email IS NOT NULL AND email != '' AND notify_promotions = 1 THEN 1 END) AS count_promos,
        COUNT(CASE WHEN email IS NOT NULL AND email != '' AND (notify_events = 1 OR notify_promotions = 1) THEN 1 END) AS count_any
    FROM users
");
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC) ?: ['count_events' => 0, 'count_promos' => 0, 'count_any' => 0];

// Handle Broadcast Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $audience = $_POST['audience'] ?? 'events';
    $subject = trim($_POST['subject'] ?? '');
    $messageBody = trim($_POST['message'] ?? '');
    $isTestOnly = !empty($_POST['test_only']);
    $testTargetEmail = trim($_POST['test_email'] ?? '');
    $enablePopup = !empty($_POST['show_popup']);
    $popupDays = max(1, min(14, (int)($_POST['popup_days'] ?? 3)));

    if ($subject === '' || $messageBody === '') {
        $feedback = ['type' => 'error', 'msg' => 'Subject line and message body cannot be empty.'];
    } else {
        if ($isTestOnly) {
            $testEmail = filter_var($testTargetEmail, FILTER_VALIDATE_EMAIL) ?: filter_var($_SESSION['email'] ?? '', FILTER_VALIDATE_EMAIL);
            if (!$testEmail) {
                $feedback = ['type' => 'error', 'msg' => 'Please provide a valid recipient email address for Test Mode.'];
                $recipients = [];
            } else {
                $recipients = [['username' => $_SESSION['username'] ?? 'Admin', 'email' => $testEmail]];
            }
        } else {
            if ($audience === 'promos') {
                $q = "SELECT username, email FROM users WHERE email IS NOT NULL AND email != '' AND notify_promotions = 1";
            } elseif ($audience === 'all') {
                $q = "SELECT username, email FROM users WHERE email IS NOT NULL AND email != '' AND (notify_events = 1 OR notify_promotions = 1)";
            } else {
                $q = "SELECT username, email FROM users WHERE email IS NOT NULL AND email != '' AND notify_events = 1";
            }
            $stmt = $pdo->query($q);
            $recipients = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        if (!empty($recipients)) {
            $sentCount = 0;
            $failedCount = 0;

            $headers  = "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $headers .= "From: SoCal Pauper <updates@socalpauper.com>\r\n";
            $headers .= "Reply-To: updates@socalpauper.com\r\n";
            $headers .= "X-Mailer: PHP/" . phpversion();

            foreach ($recipients as $recipient) {
                $email = filter_var($recipient['email'], FILTER_VALIDATE_EMAIL);
                if (!$email) {
                    $failedCount++;
                    continue;
                }

                $cleanUser = htmlspecialchars($recipient['username'], ENT_QUOTES, 'UTF-8');
                $formattedContent = nl2br(htmlspecialchars($messageBody, ENT_QUOTES, 'UTF-8'));

                // MTG / SoCal Pauper Responsive HTML Template
                $htmlBody = "
                <!DOCTYPE html>
                <html>
                <head>
                  <meta charset='UTF-8'>
                  <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                </head>
                <body style='margin:0; padding:24px; background-color:#0c0e12; font-family:-apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif; color:#d4d4d8;'>
                  <table width='100%' border='0' cellspacing='0' cellpadding='0'>
                    <tr>
                      <td align='center'>
                        <table width='100%' border='0' cellspacing='0' cellpadding='0' style='max-width:600px; background-color:#161922; border:1px solid #2a3040; border-radius:14px; overflow:hidden; box-shadow:0 15px 35px rgba(0,0,0,0.6);'>
                          <tr>
                            <td style='background:linear-gradient(90deg, #b45309 0%, #d97706 100%); padding:22px 28px; text-align:left;'>
                              <h1 style='margin:0; font-size:22px; color:#ffffff; font-weight:900; letter-spacing:0.5px;'>⚡ SoCal Pauper</h1>
                            </td>
                          </tr>
                          <tr>
                            <td style='padding:32px 28px; font-size:14px; line-height:1.65; color:#e4e4e7;'>
                              <p style='margin-top:0; font-size:15px;'>Hey <strong>{$cleanUser}</strong>,</p>
                              <div style='margin:20px 0; color:#d4d4d8; font-size:14px;'>{$formattedContent}</div>
                              <p style='margin-bottom:0; color:#a1a1aa; font-size:12px;'>— The SoCal Pauper Team</p>
                            </td>
                          </tr>
                          <tr>
                            <td style='background:#0f1218; padding:18px 28px; text-align:center; font-size:11px; color:#71717a; border-top:1px solid #2a3040;'>
                              You received this message because you opted in to updates on <a href='https://socalpauper.com' style='color:#f59e0b; text-decoration:none;'>socalpauper.com</a>.<br>
                              Manage preferences anytime by logging into your account on the Deck Hub.
                            </td>
                          </tr>
                        </table>
                      </td>
                    </tr>
                  </table>
                </body>
                </html>";

                if (@mail($email, $subject, $htmlBody, $headers)) {
                    $sentCount++;
                } else {
                    $failedCount++;
                }
            }

            // Save active announcement popup to database if live blast and checkbox enabled
            if (!$isTestOnly && $enablePopup) {
                $ins = $pdo->prepare("
                    INSERT INTO broadcasts (subject, message, audience, show_popup, expires_at)
                    VALUES (?, ?, ?, 1, DATE_ADD(NOW(), INTERVAL ? DAY))
                ");
                $ins->execute([$subject, $messageBody, $audience, $popupDays]);
            }

            $feedback = [
                'type' => 'success',
                'msg' => $isTestOnly 
                    ? "Safe Mode: Test broadcast successfully sent to <strong>" . htmlspecialchars($recipients[0]['email']) . "</strong>."
                    : "Broadcast finished: <strong>{$sentCount}</strong> emails dispatched, <strong>{$failedCount}</strong> skipped/failed." . ($enablePopup ? " <strong>Homepage announcement popup set active for {$popupDays} days.</strong>" : "")
            ];
        } elseif (!$feedback) {
            $feedback = ['type' => 'error', 'msg' => 'No subscribed players found with verified emails for the selected audience.'];
        }
    }
}

// Fetch currently active popups
$activePopups = $pdo->query("SELECT * FROM broadcasts WHERE expires_at > NOW() ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SoCal Pauper — Broadcast Command Center</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Inter', sans-serif; }
    h1, h2, h3, .font-cinzel { font-family: 'Cinzel', serif; }
  </style>
</head>
<body class="bg-[#0c0e12] text-zinc-200 min-h-screen flex flex-col">

  <!-- Header Bar -->
  <header class="bg-[#12151e] border-b border-[#2a3040] px-6 py-4 flex items-center justify-between sticky top-0 z-40">
    <div class="flex items-center gap-4">
      <a href="index.php" class="text-amber-400 font-bold hover:text-white transition text-xs flex items-center gap-1.5">
        <i class="fa-solid fa-arrow-left"></i> Main Site
      </a>
      <span class="text-zinc-600">/</span>
      <a href="decks.php" class="text-sky-400 font-bold hover:text-white transition text-xs flex items-center gap-1.5">
        <i class="fa-solid fa-layer-group"></i> Deck Hub
      </a>
      <span class="text-zinc-600">/</span>
      <h1 class="text-sm font-bold text-white flex items-center gap-2">
        <span class="px-2 py-0.5 rounded bg-purple-950/80 text-purple-300 border border-purple-700/80 text-[10px] font-black uppercase tracking-wider">Admin</span>
        Broadcast Center
      </h1>
    </div>

    <div class="flex items-center gap-3 text-xs">
      <span class="text-zinc-400">Authenticated: <strong class="text-purple-400"><?= htmlspecialchars($_SESSION['username'] ?? 'BigMeatyPawz') ?></strong></span>
      <a href="api_auth.php?action=logout" class="px-2.5 py-1 bg-zinc-800 hover:bg-zinc-700 text-zinc-300 rounded border border-zinc-700 transition">Log Out</a>
    </div>
  </header>

  <main class="flex-1 max-w-4xl w-full mx-auto p-6 space-y-6">

    <!-- Audience Stat Counters -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
      <div class="bg-[#161922] border border-[#2a3040] rounded-xl p-4">
        <div class="text-[11px] uppercase tracking-wider font-bold text-zinc-400 flex items-center gap-1.5">
          <i class="fa-solid fa-trophy text-amber-400"></i> Tournament Alerts
        </div>
        <div class="text-3xl font-black text-white mt-1"><?= (int)$stats['count_events'] ?></div>
        <div class="text-[11px] text-zinc-500 mt-0.5">Opted-in tournament players</div>
      </div>

      <div class="bg-[#161922] border border-[#2a3040] rounded-xl p-4">
        <div class="text-[11px] uppercase tracking-wider font-bold text-zinc-400 flex items-center gap-1.5">
          <i class="fa-solid fa-store text-sky-400"></i> Partner & LGS Promos
        </div>
        <div class="text-3xl font-black text-white mt-1"><?= (int)$stats['count_promos'] ?></div>
        <div class="text-[11px] text-zinc-500 mt-0.5">Opted-in store promotions</div>
      </div>

      <div class="bg-[#161922] border border-[#2a3040] rounded-xl p-4">
        <div class="text-[11px] uppercase tracking-wider font-bold text-zinc-400 flex items-center gap-1.5">
          <i class="fa-solid fa-users text-emerald-400"></i> Total Subscriber Reach
        </div>
        <div class="text-3xl font-black text-emerald-400 mt-1"><?= (int)$stats['count_any'] ?></div>
        <div class="text-[11px] text-zinc-500 mt-0.5">Total unique opted-in emails</div>
      </div>
    </div>

    <!-- Active Popups Manager -->
    <?php if (!empty($activePopups)): ?>
      <div class="bg-[#161922] border border-amber-500/40 rounded-xl p-5 space-y-3 shadow-lg">
        <div class="flex items-center justify-between">
          <h2 class="text-xs font-black uppercase tracking-wider text-amber-400 flex items-center gap-2">
            <i class="fa-solid fa-bullhorn"></i> Active Main Page Popups
          </h2>
          <span class="text-[10px] text-zinc-400">Currently live on socalpauper.com</span>
        </div>
        <div class="divide-y divide-[#2a3040]">
          <?php foreach ($activePopups as $p): ?>
            <div class="py-2.5 flex items-center justify-between text-xs gap-3">
              <div class="truncate">
                <span class="font-bold text-white"><?= htmlspecialchars($p['subject']) ?></span>
                <span class="text-[10px] text-zinc-400 ml-2 font-mono">Expires: <?= date('M j, Y g:ia', strtotime($p['expires_at'])) ?></span>
              </div>
              <a href="admin_broadcast.php?delete_popup=<?= (int)$p['id'] ?>" onclick="return confirm('Immediately remove this announcement popup from the main page?')" class="shrink-0 text-rose-400 hover:text-rose-300 font-bold text-[11px] px-2.5 py-1 rounded bg-rose-950/60 border border-rose-900 transition">
                <i class="fa-solid fa-trash-can mr-1"></i> End Popup
              </a>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($feedback): ?>
      <div class="p-4 rounded-xl border text-xs <?= $feedback['type'] === 'success' ? 'bg-emerald-950/60 border-emerald-800 text-emerald-300' : 'bg-rose-950/60 border-rose-800 text-rose-300' ?>">
        <?= $feedback['msg'] ?>
      </div>
    <?php endif; ?>

    <!-- Broadcast Composition Form -->
    <form method="POST" class="bg-[#161922] border border-[#2a3040] rounded-2xl p-6 space-y-5 shadow-2xl">
      
      <!-- Target Audience Selector -->
      <div>
        <label class="block text-xs font-bold text-zinc-300 uppercase tracking-wider mb-2">1. Target Audience</label>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
          
          <label class="border border-[#2a3040] rounded-xl p-3 flex items-start gap-3 bg-[#0c0e12]/60 cursor-pointer hover:border-amber-500/50 transition">
            <input type="radio" name="audience" value="events" checked class="mt-0.5 text-amber-600 focus:ring-0">
            <div>
              <div class="text-xs font-bold text-white">Tournament Alerts</div>
              <div class="text-[11px] text-zinc-400 mt-0.5"><?= (int)$stats['count_events'] ?> players</div>
            </div>
          </label>

          <label class="border border-[#2a3040] rounded-xl p-3 flex items-start gap-3 bg-[#0c0e12]/60 cursor-pointer hover:border-amber-500/50 transition">
            <input type="radio" name="audience" value="promos" class="mt-0.5 text-amber-600 focus:ring-0">
            <div>
              <div class="text-xs font-bold text-white">Partner / LGS Promos</div>
              <div class="text-[11px] text-zinc-400 mt-0.5"><?= (int)$stats['count_promos'] ?> players</div>
            </div>
          </label>

          <label class="border border-[#2a3040] rounded-xl p-3 flex items-start gap-3 bg-[#0c0e12]/60 cursor-pointer hover:border-amber-500/50 transition">
            <input type="radio" name="audience" value="all" class="mt-0.5 text-amber-600 focus:ring-0">
            <div>
              <div class="text-xs font-bold text-white">All Subscribers</div>
              <div class="text-[11px] text-zinc-400 mt-0.5"><?= (int)$stats['count_any'] ?> players</div>
            </div>
          </label>

        </div>
      </div>

      <!-- Subject Line -->
      <div>
        <label class="block text-xs font-bold text-zinc-300 uppercase tracking-wider mb-1">2. Subject Line</label>
        <input type="text" name="subject" required placeholder="e.g. Next SoCal Pauper Monthly: Burbank Date & Details" 
               class="w-full bg-[#0c0e12] border border-[#2a3040] rounded-lg px-4 py-2.5 text-xs text-white placeholder-zinc-500 focus:outline-none focus:border-amber-500">
      </div>

      <!-- Message Body -->
      <div>
        <label class="block text-xs font-bold text-zinc-300 uppercase tracking-wider mb-1">3. Message Body</label>
        <textarea name="message" rows="9" required placeholder="Write your announcement here. Line breaks are converted automatically." 
                  class="w-full bg-[#0c0e12] border border-[#2a3040] rounded-lg p-4 text-xs text-zinc-200 placeholder-zinc-500 focus:outline-none focus:border-amber-500 font-mono leading-relaxed"></textarea>
      </div>

      <!-- Homepage Popup Duration -->
      <div class="p-4 bg-[#0c0e12]/70 rounded-xl border border-[#2a3040] flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <label class="flex items-center gap-2 cursor-pointer">
          <input type="checkbox" id="show_popup" name="show_popup" value="1" checked class="rounded bg-zinc-900 border-zinc-700 text-amber-600 focus:ring-0">
          <span class="text-xs font-bold text-amber-400">Also display as an announcement popup on the main page</span>
        </label>
        <div class="flex items-center gap-2 text-xs text-zinc-400 pl-6 sm:pl-0">
          <span>Active for:</span>
          <select name="popup_days" class="bg-[#161922] border border-[#2a3040] rounded text-xs px-2.5 py-1 text-white focus:outline-none focus:border-amber-500">
            <option value="1">1 Day</option>
            <option value="2">2 Days</option>
            <option value="3" selected>3 Days</option>
            <option value="5">5 Days</option>
            <option value="7">7 Days</option>
            <option value="14">14 Days</option>
          </select>
        </div>
      </div>

      <!-- Safe Mode / Single Test Send -->
      <div class="p-4 bg-purple-950/20 rounded-xl border border-purple-800/40 space-y-2">
        <label class="flex items-center gap-2 cursor-pointer">
          <input type="checkbox" id="test-only-checkbox" name="test_only" value="1" checked class="rounded bg-zinc-900 border-zinc-700 text-purple-600 focus:ring-0">
          <span class="text-xs font-bold text-purple-300">Safe Mode: Send a single test email preview first</span>
        </label>
        <input type="email" name="test_email" placeholder="Your test recipient email (e.g. personal@gmail.com)" 
               value="<?= htmlspecialchars($_SESSION['email'] ?? '') ?>"
               class="w-full bg-[#0c0e12] border border-purple-800/50 rounded-lg px-3 py-2 text-xs text-white placeholder-zinc-500 focus:outline-none focus:border-purple-400">
      </div>

      <!-- Actions -->
      <div class="flex items-center justify-between pt-2">
        <span class="text-[11px] text-zinc-500">Unsubscribe notice & SoCal Pauper branding are included automatically.</span>
        <button type="submit" onclick="return confirmSend()" class="px-6 py-2.5 bg-gradient-to-r from-purple-700 to-amber-600 hover:from-purple-600 hover:to-amber-500 text-white font-bold text-xs rounded-xl transition shadow-lg flex items-center gap-2">
          <i class="fa-solid fa-paper-plane"></i> Dispatch Email
        </button>
      </div>

    </form>

  </main>

  <script>
    function confirmSend() {
      const isTest = document.getElementById('test-only-checkbox').checked;
      if (isTest) return true;
      return confirm("WARNING: You are in LIVE mode. This will send an email blast to all opted-in players. Proceed?");
    }
  </script>
</body>
</html>