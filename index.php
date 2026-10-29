<?php
session_start();
require_once __DIR__ . '/db.php';

// Check for any active broadcast to display as a popup
$activeBroadcast = null;
try {
    $pdo = getDBConnection();
    
    // Check if an explicit preview was requested by admin via ?preview_popup=ID
    if (!empty($_GET['preview_popup'])) {
        $pId = (int)$_GET['preview_popup'];
        $bStmt = $pdo->prepare("SELECT id, subject, message, created_at FROM broadcasts WHERE id = ?");
        $bStmt->execute([$pId]);
        $activeBroadcast = $bStmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Default: grab newest active broadcast that has not expired
    if (!$activeBroadcast) {
        $bStmt = $pdo->query("SELECT id, subject, message, created_at FROM broadcasts WHERE expires_at > NOW() AND show_popup = 1 ORDER BY created_at DESC LIMIT 1");
        $activeBroadcast = $bStmt->fetch(PDO::FETCH_ASSOC);
    }
} catch (Throwable $e) {
    $activeBroadcast = null;
}
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>SoCal Pauper | Southern California MTG Community</title>
  <meta name="description" content="Tournaments, weekly meetups, partner store directory, card price explorer, and community hub for Magic: The Gathering Pauper players across Southern California." />

  <script type="text/javascript">(function(i,m,p,a,c,t){c.ire_o=p;c[p]=c[p]||function(){(c[p].a=c[p].a||[]).push(arguments)};t=a.createElement(m);var z=a.getElementsByTagName(m)[0];t.async=1;t.src=i;z.parentNode.insertBefore(t,z)})('https://utt.impactcdn.com/P-A8026393-9cd5-4761-9fbc-12cf165790931.js','script','impactStat',document,window);impactStat('transformLinks');impactStat('trackImpression');</script>

  <!-- Favicon / Tab Logo -->
  <link rel="icon" type="image/webp" href="/logo.webp">

  <!-- Discord & Social Open Graph Embed -->
  <meta property="og:type" content="website">
  <meta property="og:title" content="SoCal Pauper | Southern California MTG Community">
  <meta property="og:description" content="Tournaments, weekly store meetups, life counter, card price explorer, and SoCal Pauper Discord community hub.">
  <meta property="og:image" content="https://socalpauper.com/logo.webp">
  <meta property="og:image:type" content="image/webp">
  <meta property="og:url" content="https://socalpauper.com">
  <meta name="theme-color" content="#d97706">
  <meta name="twitter:card" content="summary_large_image">

  <!-- Tailwind CSS CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            mtg: {
              dark: '#0c0e12',
              card: '#161922',
              border: '#2a3040',
              accent: '#d97706'
            }
          }
        }
      }
    }
  </script>

  <!-- Google Fonts: Cinzel & Inter -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  
  <style>
    h1, h2, h3, .font-cinzel { font-family: 'Cinzel', serif; }
    body { font-family: 'Inter', sans-serif; }
    .card-frame {
      background: linear-gradient(180deg, rgba(28, 33, 46, 0.95) 0%, rgba(18, 21, 30, 0.98) 100%);
      border: 1px solid #2a3040;
      box-shadow: 0 10px 30px -10px rgba(0,0,0,0.7), inset 0 1px 0 rgba(255,255,255,0.05);
    }
  </style>
  <script data-goatcounter="https://socalpauper.goatcounter.com/count" async src="//gc.zgo.at/count.js"></script>
</head>
<body class="bg-mtg-dark text-zinc-200 min-h-screen flex flex-col selection:bg-amber-600 selection:text-white">

  <?php include __DIR__ . '/partials/header.php'; ?>
  <?php include __DIR__ . '/partials/hero.php'; ?>

  <!-- Main Content Column -->
  <main class="max-w-6xl mx-auto px-4 py-12 flex-1 w-full space-y-12">
    <?php include __DIR__ . '/partials/card-explorer.php'; ?>
    <?php include __DIR__ . '/partials/community-links.php'; ?>
    <?php include __DIR__ . '/partials/format-primer.php'; ?>
    <?php include __DIR__ . '/partials/promoter-banner.php'; ?>
    <?php include __DIR__ . '/partials/calendar.php'; ?>
    <?php include __DIR__ . '/partials/stores.php'; ?>
  </main>

  <?php include __DIR__ . '/partials/console-promo.php'; ?>
  <?php include __DIR__ . '/partials/tipjar.php'; ?>
  <?php include __DIR__ . '/partials/footer.php'; ?>
  <?php include __DIR__ . '/partials/modals.php'; ?>

  <!-- Broadcast Announcement Modal -->
  <?php if ($activeBroadcast): ?>
    <div id="broadcast-popup" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
      <div class="card-frame max-w-lg w-full p-6 space-y-4 rounded-2xl shadow-2xl relative border border-amber-500/40">
        
        <div class="flex items-start justify-between">
          <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-lg bg-amber-500/20 text-amber-400 flex items-center justify-center text-base font-bold border border-amber-500/30">
              <i class="fa-solid fa-bullhorn"></i>
            </div>
            <div>
              <span class="text-[10px] uppercase font-black text-amber-400 tracking-wider">Announcement</span>
              <h3 class="text-base font-bold text-white"><?= htmlspecialchars($activeBroadcast['subject']) ?></h3>
            </div>
          </div>
          <button onclick="dismissBroadcast(<?= (int)$activeBroadcast['id'] ?>)" class="text-zinc-400 hover:text-white text-lg">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>

        <div class="text-xs text-zinc-300 leading-relaxed bg-[#0c0e12]/80 p-4 rounded-xl border border-mtg-border max-h-60 overflow-y-auto whitespace-pre-wrap">
          <?= htmlspecialchars($activeBroadcast['message']) ?>
        </div>

        <div class="flex items-center justify-between pt-2">
          <span class="text-[10px] text-zinc-500">Posted on <?= date('M j, Y', strtotime($activeBroadcast['created_at'])) ?></span>
          <button onclick="dismissBroadcast(<?= (int)$activeBroadcast['id'] ?>)" class="px-5 py-2 bg-amber-600 hover:bg-amber-500 text-white font-bold text-xs rounded-lg transition shadow-md">
            Got it
          </button>
        </div>

      </div>
    </div>

    <script>
      (function() {
        const broadcastId = <?= (int)$activeBroadcast['id'] ?>;
        const isPreview = window.location.search.includes('preview_popup');
        const dismissed = localStorage.getItem('socal_dismissed_broadcast_' + broadcastId);

        // Render popup if not dismissed, or if admin explicitly requests ?preview_popup
        if (!dismissed || isPreview) {
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

  <!-- Modular Client-Side JavaScript -->
  <script src="/js/config.js?v=99"></script>
  <script src="/js/calendar.js?v=99"></script>
  <script src="/js/stores.js?v=99"></script>
  <script src="/js/cards.js?v=99"></script>
  <script src="/js/admin.js?v=99"></script>

</body>
</html>