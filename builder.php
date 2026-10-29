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
  <link rel="stylesheet" href="css/builder.css">
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
      
      <!-- Dynamic Pauper Legality Badge -->
      <span id="pauper-badge" class="text-xs uppercase px-2.5 py-1 bg-rose-950 text-rose-400 font-bold border border-rose-800 rounded transition cursor-default">
        Not Legal
      </span>
    </div>

    <!-- Target Board Toggle & Global Actions -->
    <div class="flex items-center gap-3">
      <div class="flex bg-slate-900 border border-slate-700 rounded-lg p-0.5">
        <button id="target-main-btn" class="px-3 py-1 text-xs font-bold rounded-md bg-sky-600 text-white transition">Mainboard</button>
        <button id="target-side-btn" class="px-3 py-1 text-xs font-bold rounded-md text-slate-400 hover:text-white transition">Sideboard</button>
      </div>

      <button id="sample-hand-btn" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold rounded-lg border border-slate-700 transition">
        <i class="fa-solid fa-hand"></i> Sample Hand
      </button>

      <button id="save-deck-btn" class="px-4 py-1.5 bg-sky-600 hover:bg-sky-500 text-white text-xs font-bold rounded-lg shadow-md transition flex items-center gap-1.5">
        <i class="fa-solid fa-floppy-disk"></i> Save Deck
      </button>
    </div>
  </header>

  <!-- Workspace Grid -->
  <div class="builder-main-grid">

    <!-- Left Analytics Column -->
    <aside class="stats-drawer">
      
      <!-- Counts -->
      <div class="grid grid-cols-2 gap-3">
        <div class="stat-card">
          <div class="stat-label">Mainboard</div>
          <div id="stat-main-count" class="text-2xl font-black text-sky-400">0</div>
          <div class="text-[10px] text-slate-400">Minimum: 60</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Sideboard</div>
          <div id="stat-side-count" class="text-2xl font-black text-emerald-400">0</div>
          <div class="text-[10px] text-slate-400">Maximum: 15</div>
        </div>
      </div>

      <!-- Real-time Legality Diagnostic Box -->
      <div id="legality-reasons"></div>

      <!-- Estimated Deck Cost -->
      <div class="stat-card">
        <div class="stat-label flex justify-between">
          <span>Estimated Deck Cost</span>
          <span class="text-sky-400 font-mono text-xs">(TCG / Cardmarket)</span>
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

      <!-- Type Composition -->
      <div class="stat-card">
        <div class="stat-label">Type Composition</div>
        <div class="text-xs space-y-1 text-slate-300 font-medium" id="type-counts-list"></div>
      </div>

    </aside>

    <!-- Visual Deck Surface -->
    <main class="workspace-content">
      
      <!-- Search Input Container -->
      <div class="search-bar-wrap">
        <div class="relative">
          <input type="text" id="card-search-input" placeholder="Search a card name (e.g. Counterspell, Kuldotha Rebirth, Lightning Bolt)..." 
                 class="w-full bg-slate-900 border border-slate-700 text-white placeholder-slate-500 px-4 py-2.5 rounded-lg text-sm focus:outline-none focus:border-sky-500" autocomplete="off">
          <div id="search-spinner" class="hidden absolute right-3 top-3 text-sky-400 text-xs">
            <i class="fa-solid fa-circle-notch fa-spin"></i>
          </div>
        </div>
        <div id="search-suggestions" class="suggestions-dropdown hidden"></div>
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
  <div id="art-modal" class="deck-modal-backdrop hidden">
    <div class="deck-modal-window">
      <div class="p-4 border-b border-slate-800 flex justify-between items-center">
        <h3 id="art-modal-title" class="font-bold text-white text-base">Select Printing Art</h3>
        <button onclick="closeArtModal()" class="text-slate-400 hover:text-white text-lg"><i class="fa-solid fa-xmark"></i></button>
      </div>
      <div id="art-prints-grid" class="p-4 overflow-y-auto grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4"></div>
    </div>
  </div>

  <!-- Mulligan / Opening Hand Modal -->
  <div id="hand-modal" class="deck-modal-backdrop hidden">
    <div class="deck-modal-window !max-w-4xl">
      <div class="p-4 border-b border-slate-800 flex justify-between items-center">
        <div>
          <h3 class="font-bold text-white text-base">Mulligan Simulator (Opening 7)</h3>
          <span class="text-xs text-slate-400">Simulate Pauper opening draws</span>
        </div>
        <div class="flex items-center gap-3">
          <button id="mulligan-btn" class="text-xs bg-sky-600 hover:bg-sky-500 px-3 py-1.5 rounded text-white font-bold">New 7 Hand</button>
          <button onclick="closeHandModal()" class="text-slate-400 hover:text-white text-lg ml-2"><i class="fa-solid fa-xmark"></i></button>
        </div>
      </div>
      <div id="sample-hand-lane" class="p-6 overflow-x-auto flex gap-4 min-h-[300px] items-center justify-center"></div>
    </div>
  </div>

  <!-- Core Scripts -->
  
  <script src="js/builder.js"></script>
</body>
</html>