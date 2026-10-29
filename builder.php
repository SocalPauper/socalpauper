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
  <link rel="stylesheet" href="css/builder.css?v=4">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="builder-root">

  <!-- Top Action Bar -->
  <header class="builder-header">
    <div class="flex items-center gap-4">
      <a href="index.php" class="text-sky-400 font-bold hover:text-white transition text-sm">
        <i class="fa-solid fa-arrow-left"></i> Hub
      </a>
      <input type="text" id="deck-title-input" value="Untitled Pauper Brew" 
             class="bg-slate-900 border border-slate-700 text-white font-bold px-3 py-1.5 rounded-lg text-lg focus:outline-none focus:border-sky-500 w-64 md:w-80">
      
      <!-- Pauper Legality Status Badge -->
      <span id="pauper-badge" class="text-xs uppercase px-2.5 py-1 bg-rose-950 text-rose-400 font-bold border border-rose-800 rounded transition cursor-default">
        Not Legal
      </span>
    </div>

    <!-- Target Board Toggle & Global Actions -->
    <div class="flex items-center gap-3">
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

      <button id="save-deck-btn" type="button" class="px-4 py-1.5 bg-sky-600 hover:bg-sky-500 text-white text-xs font-bold rounded-lg shadow-md transition flex items-center gap-1.5">
        <i class="fa-solid fa-floppy-disk"></i> Save Deck
      </button>
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

      <!-- Type Breakdown -->
      <div class="stat-card">
        <div class="stat-label">Type Composition</div>
        <div class="text-xs space-y-1 text-slate-300 font-medium" id="type-counts-list"></div>
      </div>

    </aside>

    <!-- Visual Deck Surface -->
    <main class="workspace-content">
      
      <!-- Search Bar Wrap -->
      <div class="search-bar-wrap">
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

  <!-- Universal Import Modal (Moxfield, MTGGoldfish, Arena, MTGO, Archidekt) -->
  <div id="import-modal" class="deck-modal-backdrop" style="display: none;">
    <div class="deck-modal-window !max-w-2xl">
      <div class="p-4 border-b border-slate-800 flex justify-between items-center">
        <div>
          <h3 class="font-bold text-white text-base">Import Deck List</h3>
          <span class="text-xs text-slate-400">Supports text exports from Moxfield, MTGGoldfish, MTGO, MTG Arena & Archidekt</span>
        </div>
        <button type="button" onclick="window.closeImportModal()" class="text-slate-400 hover:text-white text-lg"><i class="fa-solid fa-xmark"></i></button>
      </div>
      <div class="p-4 flex flex-col gap-3">
        <textarea id="import-textarea" rows="12" placeholder="Paste your deck list here...&#10;&#10;Example:&#10;4 Lightning Bolt&#10;4 Kuldotha Rebirth&#10;18 Mountain&#10;&#10;Sideboard&#10;3 Smash to Smithereens&#10;4 Red Elemental Blast" class="w-full bg-slate-900 border border-slate-700 rounded-lg p-3 text-xs text-slate-200 font-mono focus:outline-none focus:border-sky-500"></textarea>
        <div class="flex items-center justify-between">
          <label class="text-xs text-slate-400 flex items-center gap-2 cursor-pointer">
            <input type="checkbox" id="import-replace-checkbox" checked class="rounded bg-slate-800 border-slate-700 text-sky-500 focus:ring-0">
            <span>Replace current deck list (uncheck to merge)</span>
          </label>
          <button id="import-submit-btn" type="button" class="px-5 py-2 bg-sky-600 hover:bg-sky-500 text-white font-bold text-xs rounded-lg transition flex items-center gap-1.5 shadow-md">
            <i class="fa-solid fa-file-arrow-down"></i> Import & Parse
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Mulligan / Opening Hand Modal -->
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

  <script src="js/builder.js?v=4"></script>
</body>
</html>