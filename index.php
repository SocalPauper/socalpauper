<?php
// index.php
session_start();
require_once __DIR__ . '/db.php';

// Check for any active broadcast to display as a popup
$activeBroadcast = null;
try {
    $pdo = getDBConnection();
    $bStmt = $pdo->query("SELECT id, subject, message, created_at FROM broadcasts WHERE expires_at > NOW() AND show_popup = 1 ORDER BY created_at DESC LIMIT 1");
    $activeBroadcast = $bStmt->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $activeBroadcast = null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SoCal Pauper — Southern California Magic: The Gathering</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .site-bg { background-color: #090d16; }
  </style>
</head>
<body class="site-bg text-slate-100 min-h-screen flex flex-col font-sans">

  <!-- Top Navigation -->
  <header class="bg-slate-900 border-b border-slate-800 px-6 py-4 flex items-center justify-between sticky top-0 z-40">
    <a href="index.php" class="text-sky-400 font-black tracking-wider text-lg flex items-center gap-2 hover:text-white transition">
      <span>⚡ SoCal Pauper</span>
    </a>

    <nav class="flex items-center gap-4 text-xs font-bold">
      <a href="decks.php" class="text-slate-300 hover:text-sky-400 transition flex items-center gap-1.5">
        <i class="fa-solid fa-layer-group text-sky-400"></i> Deck Hub
      </a>
      <a href="builder.php" class="text-slate-300 hover:text-sky-400 transition flex items-center gap-1.5">
        <i class="fa-solid fa-wand-magic-sparkles text-emerald-400"></i> Deck Architect
      </a>
      <?php if (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
        <a href="admin_broadcast.php" class="text-purple-300 hover:text-purple-200 transition flex items-center gap-1 bg-purple-950/60 border border-purple-800 px-2 py-1 rounded">
          <i class="fa-solid fa-bullhorn text-purple-400"></i> Broadcast
        </a>
      <?php endif; ?>
    </nav>
  </header>

  <!-- Hero Section -->
  <main class="flex-1 max-w-6xl w-full mx-auto p-6 space-y-8">
    
    <div class="text-center py-12 space-y-4">
      <span class="text-xs font-black uppercase tracking-widest text-sky-400 bg-sky-950/60 border border-sky-800 px-3 py-1 rounded-full">Southern California MTG Pauper Community</span>
      <h1 class="text-4xl md:text-5xl font-black text-white tracking-tight">Competitive Magic.<br><span class="text-sky-400">Commons Only.</span></h1>
      <p class="text-sm md:text-base text-slate-400 max-w-2xl mx-auto leading-relaxed">
        Join our local tournament circuits across Los Angeles, Orange County, San Diego, and the Inland Empire. Build, test, and share tournament brews.
      </p>
      <div class="flex items-center justify-center gap-3 pt-2">
        <a href="decks.php" class="px-5 py-2.5 bg-sky-600 hover:bg-sky-500 font-bold text-xs rounded-xl shadow-lg transition flex items-center gap-2 text-white">
          <i class="fa-solid fa-layer-group"></i> Explore Deck Hub
        </a>
        <a href="builder.php" class="px-5 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs rounded-xl border border-slate-700 transition flex items-center gap-2">
          <i class="fa-solid fa-plus text-sky-400"></i> Build New Deck
        </a>
      </div>
    </div>

    <!-- Features Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      
      <!-- Feature Card: Deck Architect -->
      <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 hover:border-sky-500/50 transition group shadow-xl flex flex-col justify-between">
        <div>
          <div class="w-12 h-12 rounded-xl bg-sky-500/10 border border-sky-500/30 flex items-center justify-center text-sky-400 mb-4 group-hover:scale-110 transition-transform">
            <i class="fa-solid fa-wand-magic-sparkles text-xl"></i>
          </div>
          <h2 class="text-lg font-black text-white mb-2">Deck Architect & Odds Engine</h2>
          <p class="text-xs text-slate-400 leading-relaxed mb-6">
            Construct brews with Scryfall integration, alternate card art selection, hypergeometric keepable hand odds, and real-time Pauper format legality checks.
          </p>
        </div>
        <div class="flex items-center gap-3">
          <a href="builder.php" class="px-4 py-2 bg-sky-600 hover:bg-sky-500 text-white text-xs font-bold rounded-lg transition shadow-md flex items-center gap-2">
            Launch Architect <i class="fa-solid fa-arrow-right text-[10px]"></i>
          </a>
        </div>
      </div>

      <!-- Feature Card: Deck Hub -->
      <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 hover:border-sky-500/50 transition group shadow-xl flex flex-col justify-between">
        <div>
          <div class="w-12 h-12 rounded-xl bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-emerald-400 mb-4 group-hover:scale-110 transition-transform">
            <i class="fa-solid fa-folder-tree text-xl"></i>
          </div>
          <h2 class="text-lg font-black text-white mb-2">Deck Command Hub</h2>
          <p class="text-xs text-slate-400 leading-relaxed mb-6">
            Organize personal deck folders, browse community brews, toggle public/private deck access, and duplicate tournament lists in one click.
          </p>
        </div>
        <div class="flex items-center gap-3">
          <a href="decks.php" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 text-xs font-bold rounded-lg transition flex items-center gap-2">
            Browse All Decks <i class="fa-solid fa-arrow-right text-[10px]"></i>
          </a>
        </div>
      </div>

    </div>

  </main>

  <!-- Broadcast Announcement Modal (Shown if active and not dismissed) -->
  <?php if ($activeBroadcast): ?>
    <div id="broadcast-popup" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
      <div class="bg-slate-900 border border-sky-600 rounded-2xl max-w-lg w-full p-6 space-y-4 shadow-2xl relative">
        
        <div class="flex items-start justify-between">
          <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-sky-500/20 text-sky-400 flex items-center justify-center text-sm font-bold">
              <i class="fa-solid fa-bullhorn"></i>
            </div>
            <div>
              <span class="text-[10px] uppercase font-black text-sky-400 tracking-wider">Announcement</span>
              <h2 class="text-base font-bold text-white"><?= htmlspecialchars($activeBroadcast['subject']) ?></h2>
            </div>
          </div>
          <button onclick="dismissBroadcast(<?= (int)$activeBroadcast['id'] ?>)" class="text-slate-400 hover:text-white text-lg">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>

        <div class="text-xs text-slate-300 leading-relaxed bg-slate-950 p-4 rounded-xl border border-slate-800 max-h-60 overflow-y-auto whitespace-pre-wrap">
          <?= htmlspecialchars($activeBroadcast['message']) ?>
        </div>

        <div class="flex items-center justify-between pt-2">
          <span class="text-[10px] text-slate-500">Posted on <?= date('M j, Y', strtotime($activeBroadcast['created_at'])) ?></span>
          <button onclick="dismissBroadcast(<?= (int)$activeBroadcast['id'] ?>)" class="px-5 py-2 bg-sky-600 hover:bg-sky-500 text-white font-bold text-xs rounded-lg transition shadow-md">
            Got it
          </button>
        </div>

      </div>
    </div>

    <script>
      (function() {
        const broadcastId = <?= (int)$activeBroadcast['id'] ?>;
        const dismissed = localStorage.getItem('socal_dismissed_broadcast_' + broadcastId);
        if (!dismissed) {
          const popup = document.getElementById('broadcast-popup');
          if (popup) popup.classList.remove('hidden');
        }
      })();

      function dismissBroadcast(id) {
        localStorage.setItem('socal_dismissed_broadcast_' + id, 'true');
        const popup = document.getElementById('broadcast-popup');
        if (popup) popup.classList.add('hidden');
      }
    </script>
  <?php endif; ?>

  <footer class="bg-slate-950 border-t border-slate-900 py-6 text-center text-xs text-slate-500">
    SoCal Pauper © <?= date('Y') ?> • Not affiliated with Wizards of the Coast
  </footer>

</body>
</html>