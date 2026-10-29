<?php
// admin_broadcast.php
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
if (!empty($_SERVER['HTTPS']) &&$_SERVER['HTTPS'] !== 'off') {
    ini_set('session.cookie_secure', '1');
}

session_start();
require_once __DIR__ . '/db.php';

$userId =$_SESSION['user_id'] ?? null;
$role =$_SESSION['role'] ?? null;

// Enforce Admin Authentication
if (!$userId \vert{}\vert{}$role !== 'admin') {
    header('Location: decks.php');
    exit;
}

$pdo = getDBConnection();$feedback = null;

// Ensure broadcasts table exists
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

// Handle deleting past broadcast popup
if (isset($_GET['delete_popup'])) {
    $deleteId = (int)$_GET['delete_popup'];
    $delStmt =$pdo->prepare("DELETE FROM broadcasts WHERE id = ?");
    $delStmt->execute([$deleteId]);
    header('Location: admin_broadcast.php?deleted=1');
    exit;
}

// Audience statistics query
$statsStmt =$pdo->query("
    SELECT 
        COUNT(CASE WHEN email IS NOT NULL AND email != '' AND notify_events = 1 THEN 1 END) AS count_events,
        COUNT(CASE WHEN email IS NOT NULL AND email != '' AND notify_promotions = 1 THEN 1 END) AS count_promos,
        COUNT(CASE WHEN email IS NOT NULL AND email != '' AND (notify_events = 1 OR notify_promotions = 1) THEN 1 END) AS count_any
    FROM users
");
$stats =$statsStmt->fetch(PDO::FETCH_ASSOC) ?: ['count_events' => 0, 'count_promos' => 0, 'count_any' => 0];

// Handle Email Dispatch
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $audience =$_POST['audience'] ?? 'events';
    $subject = trim($_POST['subject'] ?? '');
    $messageBody = trim($_POST['message'] ?? '');
    $isTestOnly = !empty($_POST['test_only']);
    $testTargetEmail = trim($_POST['test_email'] ?? '');
    $enablePopup = !empty($_POST['show_popup']);
    $popupDays = max(1, min(14, (int)($_POST['popup_days'] ?? 3)));

    if ($subject === '' || $messageBody === '') {$feedback = ['type' => 'error', 'msg' => 'Subject and message cannot be empty.'];
    } else {
        if ($isTestOnly) {
            $recipients = [['username' => 'Admin Tester', 'email' =>$testTargetEmail ?: 'admin@socalpauper.com']];
        } else {
            if ($audience === 'promos') {$q = "SELECT username, email FROM users WHERE email IS NOT NULL AND email != '' AND notify_promotions = 1";
            } elseif ($audience === 'all') {$q = "SELECT username, email FROM users WHERE email IS NOT NULL AND email != '' AND (notify_events = 1 OR notify_promotions = 1)";
            } else {
                $q = "SELECT username, email FROM users WHERE email IS NOT NULL AND email != '' AND notify_events = 1";
            }
            $stmt = $pdo->query($q);
            $recipients =$stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        if (empty($recipients)) {$feedback = ['type' => 'error', 'msg' => 'No subscribed users found with valid email addresses for this audience.'];
        } else {
            $sentCount = 0;
            $failedCount = 0;

            $headers  = "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $headers .= "From: SoCal Pauper <updates@socalpauper.com>\r\n";
            $headers .= "Reply-To: updates@socalpauper.com\r\n";
            $headers .= "X-Mailer: PHP/" . phpversion();

            foreach ($recipients as$recipient) {
                $email = filter_var($recipient['email'], FILTER_VALIDATE_EMAIL);
                if (!$email) {$failedCount++;
                    continue;
                }

                $cleanUser = htmlspecialchars($recipient['username'], ENT_QUOTES, 'UTF-8');
                $formattedContent = nl2br(htmlspecialchars($messageBody, ENT_QUOTES, 'UTF-8'));

                $htmlBody = "
                <!DOCTYPE html>
                <html>
                <head><meta charset='UTF-8'></head>
                <body style='margin:0; padding:20px; background-color:#090d16; font-family:-apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif; color:#cbd5e1;'>
                  <table width='100%' border='0' cellspacing='0' cellpadding='0'>
                    <tr>
                      <td align='center'>
                        <table width='600' border='0' cellspacing='0' cellpadding='0' style='background-color:#0f172a; border:1px solid #1e293b; border-radius:12px; overflow:hidden;'>
                          <tr>
                            <td style='background:#0284c7; padding:20px; text-align:left;'>
                              <h1 style='margin:0; font-size:20px; color:#ffffff; font-weight:800; letter-spacing:0.5px;'>⚡ SoCal Pauper</h1>
                            </td>
                          </tr>
                          <tr>
                            <td style='padding:30px; font-size:14px; line-height:1.6; color:#e2e8f0;'>
                              <p style='margin-top:0;'>Hey <strong>{$cleanUser}</strong>,</p>
                              <div style='margin:20px 0; color:#cbd5e1;'>{$formattedContent}</div>
                              <p style='margin-bottom:0; color:#94a3b8; font-size:12px;'>— The SoCal Pauper Team</p>
                            </td>
                          </tr>
                          <tr>
                            <td style='background:#0b1120; padding:16px 30px; text-align:center; font-size:11px; color:#64748b; border-top:1px solid #1e293b;'>
                              You received this message because you opted in to updates on <a href='https://socalpauper.com' style='color:#38bdf8; text-decoration:none;'>socalpauper.com</a>.<br>
                              Manage your preferences anytime by logging into your account on the Deck Hub.
                            </td>
                          </tr>
                        </table>
                      </td>
                    </tr>
                  </table>
                </body>
                </html>";

                if (@mail($email, $subject,$htmlBody, $headers)) {$sentCount++;
                } else {
                    $failedCount++;
                }
            }

            // Save to broadcasts database if live blast and popup requested
            if (!$isTestOnly &&$enablePopup) {
                $ins =$pdo->prepare("
                    INSERT INTO broadcasts (subject, message, audience, show_popup, expires_at)
                    VALUES (?, ?, ?, 1, DATE_ADD(NOW(), INTERVAL ? DAY))
                ");
                $ins->execute([$subject,$messageBody, $audience,$popupDays]);
            }

            $feedback = [
                'type' => 'success',
                'msg' => $isTestOnly 
                    ? "Test email dispatched successfully to <strong>" . htmlspecialchars($recipients[0]['email']) . "</strong>."
                    : "Broadcast finished: <strong>{$sentCount}</strong> sent, <strong>{$failedCount}</strong> skipped/failed." . ($enablePopup ? " <strong>Active homepage popup set for {$popupDays} days.</strong>" : "")
            ];
        }
    }
}

// Fetch active popups
$activePopups =$pdo->query("SELECT * FROM broadcasts WHERE expires_at > NOW() ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SoCal Pauper — Broadcast Center</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-[#090d16] text-slate-100 min-h-screen flex flex-col font-sans">

  <!-- Admin Bar -->
  <header class="bg-slate-900 border-b border-slate-800 px-6 py-4 flex items-center justify-between sticky top-0 z-40">
    <div class="flex items-center gap-4">
      <a href="decks.php" class="text-sky-400 font-bold hover:text-white transition text-xs flex items-center gap-1.5">
        <i class="fa-solid fa-arrow-left"></i> Hub
      </a>
      <span class="text-slate-600">/</span>
      <h1 class="text-sm font-bold text-white flex items-center gap-2">
        <span class="px-2 py-0.5 rounded bg-purple-950 text-purple-400 border border-purple-800 text-[10px] font-black uppercase">Admin Only</span>
        Email Dispatch Center
      </h1>
    </div>

    <div class="flex items-center gap-3 text-xs">
      <span class="text-slate-400">Admin: <strong class="text-sky-400"><?= htmlspecialchars($_SESSION['username'] ?? 'BigMeatyPawz') ?></strong></span>
      <a href="api_auth.php?action=logout" class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded border border-slate-700 transition">Log Out</a>
    </div>
  </header>

  <main class="flex-1 max-w-4xl w-full mx-auto p-6 space-y-6">

    <!-- Audience Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
      <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
        <div class="text-xs uppercase tracking-wider font-bold text-slate-400 flex items-center gap-1.5">
          <i class="fa-solid fa-trophy text-amber-400"></i> Tournament Alerts
        </div>
        <div class="text-2xl font-black text-white mt-1"><?= (int)$stats['count_events'] ?></div>
        <div class="text-[11px] text-slate-500">Opted in to event schedules</div>
      </div>

      <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
        <div class="text-xs uppercase tracking-wider font-bold text-slate-400 flex items-center gap-1.5">
          <i class="fa-solid fa-store text-sky-400"></i> Partner / LGS Promos
        </div>
        <div class="text-2xl font-black text-white mt-1"><?= (int)$stats['count_promos'] ?></div>
        <div class="text-[11px] text-slate-500">Opted in to store promotions</div>
      </div>

      <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
        <div class="text-xs uppercase tracking-wider font-bold text-slate-400 flex items-center gap-1.5">
          <i class="fa-solid fa-users text-emerald-400"></i> Combined Reach
        </div>
        <div class="text-2xl font-black text-emerald-400 mt-1"><?= (int)$stats['count_any'] ?></div>
        <div class="text-[11px] text-slate-500">Unique subscribed emails</div>
      </div>
    </div>

    <!-- Active Popups Manager -->
    <?php if (!empty($activePopups)): ?>
      <div class="bg-slate-900/90 border border-sky-800/60 rounded-2xl p-5 space-y-3">
        <div class="flex items-center justify-between">
          <h2 class="text-xs font-black uppercase tracking-wider text-sky-400 flex items-center gap-2">
            <i class="fa-solid fa-bullhorn"></i> Currently Active Main Page Popup(s)
          </h2>
          <span class="text-[10px] text-slate-400">Visible to homepage visitors</span>
        </div>
        <div class="divide-y divide-slate-800">
          <?php foreach ($activePopups as$p): ?>
            <div class="py-2.5 flex items-center justify-between text-xs">
              <div>
                <span class="font-bold text-white"><?= htmlspecialchars($p['subject']) ?></span>
                <span class="text-[10px] text-slate-400 ml-2 font-mono">Expires: <?= date('M j, Y g:ia', strtotime($p['expires_at'])) ?></span>
              </div>
              <a href="admin_broadcast.php?delete_popup=<?= (int)$p['id'] ?>" onclick="return confirm('Remove this popup from the main page immediately?')" class="text-rose-400 hover:text-rose-300 font-bold text-[11px] px-2 py-1 rounded bg-rose-950/60 border border-rose-900 transition">
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

    <!-- Form -->
    <form method="POST" class="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-5 shadow-xl">
      <div>
        <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Target Email Audience</label>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
          <label class="border border-slate-800 rounded-xl p-3 flex items-start gap-3 bg-slate-950/60 cursor-pointer hover:border-slate-700 transition">
            <input type="radio" name="audience" value="events" checked class="mt-0.5 text-sky-600 focus:ring-0">
            <div>
              <div class="text-xs font-bold text-white">Tournament Announcements</div>
              <div class="text-[11px] text-slate-400">Target: <?= (int)$stats['count_events'] ?> players</div>
            </div>
          </label>

          <label class="border border-slate-800 rounded-xl p-3 flex items-start gap-3 bg-slate-950/60 cursor-pointer hover:border-slate-700 transition">
            <input type="radio" name="audience" value="promos" class="mt-0.5 text-sky-600 focus:ring-0">
            <div>
              <div class="text-xs font-bold text-white">LGS & Store Promotions</div>
              <div class="text-[11px] text-slate-400">Target: <?= (int)$stats['count_promos'] ?> players</div>
            </div>
          </label>

          <label class="border border-slate-800 rounded-xl p-3 flex items-start gap-3 bg-slate-950/60 cursor-pointer hover:border-slate-700 transition">
            <input type="radio" name="audience" value="all" class="mt-0.5 text-sky-600 focus:ring-0">
            <div>
              <div class="text-xs font-bold text-white">All Subscribed Users</div>
              <div class="text-[11px] text-slate-400">Target: <?= (int)$stats['count_any'] ?> players</div>
            </div>
          </label>
        </div>
      </div>

      <div>
        <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1">Subject / Headline</label>
        <input type="text" name="subject" required placeholder="e.g. Next SoCal Pauper Monthly: Burbank Date & Details" 
               class="w-full bg-slate-950 border border-slate-700 rounded-lg px-4 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-sky-500">
      </div>

      <div>
        <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1">Message Body</label>
        <textarea name="message" rows="8" required placeholder="Write your announcement here. Line breaks are converted automatically." 
                  class="w-full bg-slate-950 border border-slate-700 rounded-lg p-4 text-xs text-slate-200 placeholder-slate-500 focus:outline-none focus:border-sky-500 font-mono"></textarea>
      </div>

      <!-- Homepage Popup Toggle -->
      <div class="p-4 bg-slate-950 rounded-xl border border-slate-800/80 space-y-3">
        <div class="flex items-center justify-between">
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" id="show_popup" name="show_popup" value="1" checked class="rounded bg-slate-900 border-slate-700 text-sky-600 focus:ring-0">
            <span class="text-xs font-bold text-sky-300">Also show as an announcement popup on the main page</span>
          </label>
          <div class="flex items-center gap-2">
            <span class="text-[11px] text-slate-400">Active for:</span>
            <select name="popup_days" class="bg-slate-900 border border-slate-700 rounded text-xs px-2 py-1 text-white focus:outline-none">
              <option value="1">1 Day</option>
              <option value="2">2 Days</option>
              <option value="3" selected>3 Days</option>
              <option value="5">5 Days</option>
              <option value="7">7 Days</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Safe Mode / Test Mode -->
      <div class="p-4 bg-slate-950 rounded-xl border border-slate-800/80 space-y-2">
        <label class="flex items-center gap-2 cursor-pointer">
          <input type="checkbox" id="test-only-checkbox" name="test_only" value="1" checked class="rounded bg-slate-900 border-slate-700 text-purple-600 focus:ring-0">
          <span class="text-xs font-bold text-purple-300">Safe Mode: Send a single test email first</span>
        </label>
        <input type="email" name="test_email" placeholder="Your test recipient email" 
               value="<?= htmlspecialchars($_SESSION['email'] ?? '') ?>"
               class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-purple-500">
      </div>

      <div class="flex items-center justify-between pt-2">
        <span class="text-[11px] text-slate-500">Unsubscribe notice & branding are appended automatically.</span>
        <button type="submit" onclick="return confirmSend()" class="px-6 py-2.5 bg-purple-700 hover:bg-purple-600 text-white font-bold text-xs rounded-lg transition shadow-lg flex items-center gap-2">
          <i class="fa-solid fa-paper-plane"></i> Dispatch Blast
        </button>
      </div>
    </form>

  </main>

  <script>
    function confirmSend() {
      const isTest = document.getElementById('test-only-checkbox').checked;
      if (isTest) return true;
      return confirm("WARNING: You are about to send a LIVE email broadcast to subscribers. Proceed?");
    }
  </script>
</body>
</html>