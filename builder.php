<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SoCal Pauper — Deck Architect</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="css/builder.css?v=13">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="builder-root">

  <!-- Top Action Bar -->
  <header class="builder-header">
    <div class="flex items-center gap-4">
      <a href="decks.php" class="text-sky-400 font-bold hover:text-white transition text-sm flex items-center gap-1.5">
        <i class="fa-solid fa-arrow-left"></i> Hub
      </a>
      <input type="text" id="deck-title-input" value="Untitled Pauper Brew" 
             class="bg-slate-900 border border-slate-700 text-white font-bold px-3 py-1.5 rounded-lg text-lg focus:outline-none focus:border-sky-500 w-64 md:w-80">
      
      <!-- Pauper Legality Status Badge -->
      <span id="pauper-badge" class="text-xs uppercase px-2.5 py-1 bg-rose-950 text-rose-400 font-bold border border-rose-800 rounded transition cursor-default">
        Not Legal
      </span>

      <!-- Read Only Badge -->
      <span id="read-only-badge" class="hidden text-xs uppercase px-2.5 py-1 bg-amber-950 text-amber-300 font-bold border border-amber-800 rounded">
        <i class="fa-solid fa-eye mr-1"></i> Read Only View
      </span>
    </div>

    <!-- Board Toggle, Modals & Account Auth -->
    <div class="flex items-center gap-2.5">
      <div class="flex bg-slate-900 border border-slate-700 rounded-lg p-0.5">
        <button id="target-main-btn" type="button" class="px-3 py-1 text-xs font-bold rounded-md bg-sky-600 text-white transition">Mainboard</button>
        <button id="target-side-btn" type="button" class="px-3 py-1 text-xs font-bold rounded-md text-slate-400 hover:text-white transition">Sideboard</button>
      </div>

      <button id="import-deck-btn" type="button" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold rounded-lg border border-slate-700 transition flex items-center gap-1.5">
        <i class="fa-solid fa-file-import"></i> Import
      </button>

      <button id="sample-hand-btn" type="button" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold rounded-lg border border-slate-700 transition flex items-center gap-1.5">
        <i class="fa-solid fa-hand"></i> Sample Hand
      </button>

      <!-- Visibility Toggle Button (Only One) -->
      <button id="visibility-toggle-btn" type="button" onclick="Builder.toggleVisibility()" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold rounded-lg border border-slate-700 transition flex items-center gap-1.5">
        <i class="fa-solid fa-lock text-amber-400"></i> <span>Private</span>
      </button>

      <!-- Save Button (Only One) -->
      <button id="save-deck-btn" type="button" class="px-4 py-1.5 bg-sky-600 hover:bg-sky-500 text-white text-xs font-bold rounded-lg shadow-md transition flex items-center gap-1.5">
        <i class="fa-solid fa-floppy-disk"></i> Save
      </button>

      <!-- Clone action for read-only decks -->
      <button id="clone-deck-btn" type="button" class="hidden px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-lg shadow-md transition flex items-center gap-1.5">
        <i class="fa-solid fa-copy"></i> Clone to My Decks
      </button>

      <!-- User Decks Drawer Trigger -->
      <button id="my-decks-btn" type="button" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold rounded-lg border border-slate-700 transition hidden">
        <i class="fa-solid fa-folder"></i> My Decks
      </button>

      <!-- Admin Panel Button -->
      <button id="admin-panel-btn" type="button" class="px-3 py-1.5 bg-purple-900/60 hover:bg-purple-800 border border-purple-700 text-purple-300 text-xs font-bold rounded-lg transition hidden">
        <i class="fa-solid fa-shield-halved"></i> Admin
      </button>

      <div id="nav-auth-slot" class="ml-2"></div>
    </div>
  </header>

  <!-- Workspace Grid -->
  <div class="builder-main-grid">

    <!-- Left Analytics Column -->
    <aside class="stats-drawer">
      
      <!-- Card Counts -->
      <div class="grid grid-cols-2 gap-3">
        <div class="stat-card">
          <div class="stat-label">Mainboard</div>
          <div id="stat-main-count" class="text-2xl font-black text-sky-400">0</div>
          <div class="text-[10px] text-slate-400">Target: 60+</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Sideboard</div>
          <div id="stat-side-count" class="text-2xl font-black text-emerald-400">0</div>
          <div class="text-[10px] text-slate-400">Max: 15</div>
        </div>
      </div>

      <!-- Real-time Legality Diagnostic Box -->
      <div id="legality-reasons"></div>

      <!-- Keepable Hand Probabilities (Hypergeometric) -->
      <div class="stat-card">
        <div class="stat-label flex justify-between">
          <span>Opening Hand Odds (7 Cards)</span>
          <span class="text-sky-400 font-mono text-[10px]">P(Keep)</span>
        </div>
        <div class="space-y-2 mt-2 text-xs">
          <div>
            <div class="flex justify-between text-slate-300 font-semibold mb-0.5">
              <span>Standard (2-4 Lands):</span>
              <span id="prob-standard-lands" class="text-sky-400 font-bold font-mono">0.0%</span>
            </div>
            <div class="w-full bg-slate-800 h-1.5 rounded-full overflow-hidden">
              <div id="bar-standard-lands" class="bg-sky-500 h-full" style="width: 0%"></div>
            </div>
          </div>
          <div>
            <div class="flex justify-between text-slate-300 font-semibold mb-0.5">
              <span>Aggro/Burn (1-3 Lands):</span>
              <span id="prob-aggro-lands" class="text-amber-400 font-bold font-mono">0.0%</span>
            </div>
            <div class="w-full bg-slate-800 h-1.5 rounded-full overflow-hidden">
              <div id="bar-aggro-lands" class="bg-amber-500 h-full" style="width: 0%"></div>
            </div>
          </div>
          <div>
            <div class="flex justify-between text-slate-300 font-semibold mb-0.5">
              <span>≥1 Castable 1-Drop:</span>
              <span id="prob-one-drop" class="text-emerald-400 font-bold font-mono">0.0%</span>
            </div>
            <div class="w-full bg-slate-800 h-1.5 rounded-full overflow-hidden">
              <div id="bar-one-drop" class="bg-emerald-500 h-full" style="width: 0%"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Estimated Deck Cost -->
      <div class="stat-card">
        <div class="stat-label flex justify-between">
          <span>Estimated Deck Cost</span>
          <span class="text-sky-400 font-mono text-xs">(Scryfall / TCG)</span>
        </div>
        <div class="flex items-baseline gap-2">
          <div id="stat-price-usd" class="text-2xl font-black text-emerald-400">$0.00</div>
          <div id="stat-price-eur" class="text-sm font-semibold text-slate-400">€0.00</div>
        </div>
      </div>

      <!-- Mana Curve Histogram -->
      <div class="stat-card">
        <div class="stat-label">Mana Curve (Non-land)</div>
        <div class="curve-chart">
          <div class="curve-col"><div id="curve-bar-0" class="curve-bar"></div><span class="curve-label">0</span></div>
          <div class="curve-col"><div id="curve-bar-1" class="curve-bar"></div><span class="curve-label">1</span></div>
          <div class="curve-col"><div id="curve-bar-2" class="curve-bar"></div><span class="curve-label">2</span></div>
          <div class="curve-col"><div id="curve-bar-3" class="curve-bar"></div><span class="curve-label">3</span></div>
          <div class="curve-col"><div id="curve-bar-4" class="curve-bar"></div><span class="curve-label">4</span></div>
          <div class="curve-col"><div id="curve-bar-5" class="curve-bar"></div><span class="curve-label">5</span></div>
          <div class="curve-col"><div id="curve-bar-6" class="curve-bar"></div><span class="curve-label">6+</span></div>
        </div>
      </div>

      <!-- Colored Mana Pips Distribution -->
      <div class="stat-card">
        <div class="stat-label">Colored Pip Distribution</div>
        <div class="pip-bar-container" id="pip-progress-bar">
          <div class="pip-segment-w" style="width: 0%"></div>
          <div class="pip-segment-u" style="width: 0%"></div>
          <div class="pip-segment-b" style="width: 0%"></div>
          <div class="pip-segment-r" style="width: 0%"></div>
          <div class="pip-segment-g" style="width: 0%"></div>
          <div class="pip-segment-c" style="width: 0%"></div>
        </div>
        <div class="grid grid-cols-3 gap-2 mt-3" id="pip-legend">
          <div class="pip-legend-item"><span class="pip-dot bg-yellow-300"></span> W: <span id="pip-val-w">0</span></div>
          <div class="pip-legend-item"><span class="pip-dot bg-sky-400"></span> U: <span id="pip-val-u">0</span></div>
          <div class="pip-legend-item"><span class="pip-dot bg-purple-500"></span> B: <span id="pip-val-b">0</span></div>
          <div class="pip-legend-item"><span class="pip-dot bg-red-400"></span> R: <span id="pip-val-r">0</span></div>
          <div class="pip-legend-item"><span class="pip-dot bg-green-400"></span> G: <span id="pip-val-g">0</span></div>
          <div class="pip-legend-item"><span class="pip-dot bg-slate-400"></span> C: <span id="pip-val-c">0</span></div>
        </div>
      </div>

      <!-- Type Breakdowns -->
      <div class="stat-card">
        <div class="stat-label">Mainboard Composition</div>
        <div class="text-xs space-y-1 text-slate-300 font-medium" id="main-type-counts-list"></div>
      </div>

      <div class="stat-card">
        <div class="stat-label">Sideboard Composition</div>
        <div class="text-xs space-y-1 text-slate-300 font-medium" id="side-type-counts-list"></div>
      </div>

    </aside>

    <!-- Visual Deck Surface -->
    <main class="workspace-content">
      
      <!-- Search Input Container -->
      <div id="search-section" class="search-bar-wrap">
        <div class="relative">
          <input type="text" id="card-search-input" placeholder="Search card name (e.g. Counterspell, Kuldotha Rebirth, Lightning Bolt)..." 
                 class="w-full bg-slate-900 border border-slate-700 text-white placeholder-slate-500 px-4 py-2.5 rounded-lg text-sm focus:outline-none focus:border-sky-500" autocomplete="off">
          <div id="search-spinner" class="absolute right-3 top-3 text-sky-400 text-xs" style="display: none;">
            <i class="fa-solid fa-circle-notch fa-spin"></i>
          </div>
        </div>
        <div id="search-suggestions" class="suggestions-dropdown"></div>
      </div>

      <!-- Mainboard Visual Stacks -->
      <div class="space-y-2">
        <div class="flex items-center justify-between border-b border-slate-800 pb-2">
          <h2 class="text-sm font-extrabold tracking-wider uppercase text-slate-300">Mainboard</h2>
          <span id="main-group-count" class="text-xs text-sky-400 font-mono font-bold">0 Cards</span>
        </div>
        <div id="main-stacks" class="stacks-lane"></div>
      </div>

      <!-- Sideboard Visual Stacks -->
      <div class="space-y-2 mt-8">
        <div class="flex items-center justify-between border-b border-slate-800 pb-2">
          <h2 class="text-sm font-extrabold tracking-wider uppercase text-slate-300">Sideboard</h2>
          <span id="side-group-count" class="text-xs text-emerald-400 font-mono font-bold">0 Cards</span>
        </div>
        <div id="side-stacks" class="stacks-lane"></div>
      </div>

    </main>

  </div>

  <!-- Universal Import Modal -->
  <div id="import-modal" class="deck-modal-backdrop" style="display: none;">
    <div class="deck-modal-window !max-w-2xl">
      <div class="p-4 border-b border-slate-800 flex justify-between items-center">
        <div>
          <h3 class="font-bold text-white text-base">Import Deck List</h3>
          <span class="text-xs text-slate-400">Supports exports from Moxfield, MTGGoldfish, MTGO, MTG Arena & Archidekt</span>
        </div>
        <button type="button" onclick="window.closeImportModal()" class="text-slate-400 hover:text-white text-lg"><i class="fa-solid fa-xmark"></i></button>
      </div>
      <div class="p-4 flex flex-col gap-3">
        <textarea id="import-textarea" rows="12" placeholder="Paste your deck list here...&#10;&#10;Example:&#10;4 Lightning Bolt&#10;4 Kuldotha Rebirth&#10;18 Mountain&#10;&#10;Sideboard&#10;3 Smash to Smithereens" class="w-full bg-slate-900 border border-slate-700 rounded-lg p-3 text-xs text-slate-200 font-mono focus:outline-none focus:border-sky-500"></textarea>
        <div class="flex items-center justify-between">
          <label class="text-xs text-slate-400 flex items-center gap-2 cursor-pointer">
            <input type="checkbox" id="import-replace-checkbox" checked class="rounded bg-slate-800 border-slate-700 text-sky-500 focus:ring-0">
            <span>Replace current deck list</span>
          </label>
          <button id="import-submit-btn" type="button" class="px-5 py-2 bg-sky-600 hover:bg-sky-500 text-white font-bold text-xs rounded-lg transition flex items-center gap-1.5 shadow-md">
            <i class="fa-solid fa-file-arrow-down"></i> Import & Parse
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Alternate Art Picker Modal -->
  <div id="art-modal" class="deck-modal-backdrop" style="display: none;">
    <div class="deck-modal-window">
      <div class="p-4 border-b border-slate-800 flex justify-between items-center">
        <h3 id="art-modal-title" class="font-bold text-white text-base">Select Printing Art</h3>
        <button type="button" onclick="window.closeArtModal()" class="text-slate-400 hover:text-white text-lg"><i class="fa-solid fa-xmark"></i></button>
      </div>
      <div id="art-prints-grid" class="p-4 overflow-y-auto grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4"></div>
    </div>
  </div>

  <!-- Mulligan / Sample Hand Modal -->
  <div id="hand-modal" class="deck-modal-backdrop" style="display: none;">
    <div class="deck-modal-window !max-w-4xl">
      <div class="p-4 border-b border-slate-800 flex justify-between items-center">
        <div>
          <h3 class="font-bold text-white text-base">Mulligan Simulator (Opening 7)</h3>
          <span class="text-xs text-slate-400">Simulate Pauper opening draws</span>
        </div>
        <div class="flex items-center gap-3">
          <button id="mulligan-btn" type="button" class="text-xs bg-sky-600 hover:bg-sky-500 px-3 py-1.5 rounded text-white font-bold">New 7 Hand</button>
          <button type="button" onclick="window.closeHandModal()" class="text-slate-400 hover:text-white text-lg ml-2"><i class="fa-solid fa-xmark"></i></button>
        </div>
      </div>
      <div id="sample-hand-lane" class="p-6 overflow-x-auto flex gap-4 min-h-[300px] items-center justify-center"></div>
    </div>
  </div>

  <!-- User Account Modal -->
  <div id="auth-modal" class="deck-modal-backdrop" style="display: none;">
    <div class="deck-modal-window !max-w-md">
      <div class="p-4 border-b border-slate-800 flex justify-between items-center">
        <h3 id="auth-modal-title" class="font-bold text-white text-base">Sign In to SoCal Pauper</h3>
        <button type="button" onclick="window.closeAuthModal()" class="text-slate-400 hover:text-white text-lg"><i class="fa-solid fa-xmark"></i></button>
      </div>
      <form id="auth-form" class="p-5 space-y-4">
        <div id="auth-error-msg" class="text-xs text-rose-400 bg-rose-950/60 border border-rose-900 rounded p-2 hidden"></div>
        <div>
          <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Username</label>
          <input type="text" id="auth-username" required class="w-full bg-slate-900 border border-slate-700 text-white rounded p-2 text-sm focus:border-sky-500 focus:outline-none">
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Password</label>
          <input type="password" id="auth-password" required minlength="8" class="w-full bg-slate-900 border border-slate-700 text-white rounded p-2 text-sm focus:border-sky-500 focus:outline-none">
        </div>
        <div id="auth-register-fields" style="display: none;" class="space-y-3 pt-1">
          <div>
            <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Email <span class="text-slate-500 font-normal lowercase">(optional for updates)</span></label>
            <input type="email" id="auth-email" class="w-full bg-slate-900 border border-slate-700 text-white rounded p-2 text-sm focus:border-sky-500 focus:outline-none">
          </div>
          <div class="space-y-1.5 pt-1">
            <label class="flex items-center gap-2 text-xs text-slate-400 cursor-pointer">
              <input type="checkbox" id="auth-notify-events" checked class="rounded bg-slate-900 border-slate-700 text-sky-500 focus:ring-0">
              <span>Notify me of upcoming SoCal Pauper tournaments</span>
            </label>
            <label class="flex items-center gap-2 text-xs text-slate-400 cursor-pointer">
              <input type="checkbox" id="auth-notify-promos" checked class="rounded bg-slate-900 border-slate-700 text-sky-500 focus:ring-0">
              <span>Receive partner game store promotions</span>
            </label>
          </div>
        </div>
        <button type="submit" id="auth-submit-btn" class="w-full py-2 bg-sky-600 hover:bg-sky-500 text-white font-bold rounded-lg transition text-sm">Sign In</button>
        <div class="text-center text-xs text-slate-400 pt-2">
          <span id="auth-toggle-prompt">Don't have an account?</span>
          <button type="button" id="auth-toggle-btn" class="text-sky-400 font-bold hover:underline ml-1">Create Account</button>
        </div>
      </form>
    </div>
  </div>

  <!-- User Decks Modal -->
  <div id="my-decks-modal" class="deck-modal-backdrop" style="display: none;">
    <div class="deck-modal-window !max-w-xl">
      <div class="p-4 border-b border-slate-800 flex justify-between items-center">
        <h3 class="font-bold text-white text-base">Your Saved Decks</h3>
        <button type="button" onclick="window.closeMyDecksModal()" class="text-slate-400 hover:text-white text-lg"><i class="fa-solid fa-xmark"></i></button>
      </div>
      <div id="my-decks-list" class="p-4 max-h-[60vh] overflow-y-auto space-y-2"></div>
    </div>
  </div>

  <!-- Admin Decks Modal -->
  <div id="admin-modal" class="deck-modal-backdrop" style="display: none;">
    <div class="deck-modal-window !max-w-3xl">
      <div class="p-4 border-b border-slate-800 flex justify-between items-center bg-purple-950/40">
        <div>
          <h3 class="font-bold text-purple-300 text-base">Admin Dashboard: All Community Decks</h3>
          <span class="text-xs text-slate-400">Read-only oversight of all user deck lists</span>
        </div>
        <button type="button" onclick="window.closeAdminModal()" class="text-slate-400 hover:text-white text-lg"><i class="fa-solid fa-xmark"></i></button>
      </div>
      <div id="admin-decks-table-container" class="p-4 max-h-[65vh] overflow-y-auto"></div>
    </div>
  </div>

  <!-- Version bumped to 13 to clear old JS caches -->
  <script src="js/builder.js?v=13"></script>
</body>
</html>