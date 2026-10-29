<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SoCal Pauper — Deck Command Hub</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .hub-bg { background-color: #090d16; }
    .hub-card { background-color: #0f172a; border-color: #1e293b; }
    .hub-card:hover { border-color: #38bdf8; }
    .hub-art-header {
      background-size: cover;
      background-position: center top;
      background-repeat: no-repeat;
      position: relative;
    }
    .hub-art-gradient {
      background: linear-gradient(180deg, rgba(15, 23, 42, 0.2) 0%, rgba(15, 23, 42, 0.95) 100%);
    }
  </style>
</head>
<body class="hub-bg text-slate-100 min-h-screen flex flex-col font-sans">

  <!-- Hub Header -->
  <header class="bg-slate-900 border-b border-slate-800 px-6 py-4 flex flex-wrap items-center justify-between gap-4 sticky top-0 z-40">
    <div class="flex items-center gap-6">
      <a href="index.php" class="text-sky-400 font-black tracking-wider text-lg flex items-center gap-2 hover:text-white transition">
        <span>⚡ SoCal Pauper</span>
      </a>
      <nav class="flex items-center gap-2 bg-slate-950 p-1 rounded-lg border border-slate-800 text-xs font-bold">
        <button id="tab-my-decks" type="button" class="px-4 py-1.5 rounded-md bg-sky-600 text-white transition">My Decks</button>
        <button id="tab-community" type="button" class="px-4 py-1.5 rounded-md text-slate-400 hover:text-white transition">Community Brews</button>
        <button id="tab-admin" type="button" class="px-4 py-1.5 rounded-md text-purple-400 hover:text-white transition hidden"><i class="fa-solid fa-shield-halved mr-1"></i> Admin Explorer</button>
        <a href="admin_broadcast.php" id="link-admin-broadcast" class="px-3 py-1.5 rounded-md text-purple-300 bg-purple-950/60 border border-purple-800 hover:bg-purple-900 transition hidden text-xs font-bold flex items-center gap-1.5">
          <i class="fa-solid fa-paper-plane text-purple-400"></i> Broadcast
        </a>
      </nav>
    </div>

    <div class="flex items-center gap-3">
      <button onclick="Hub.handleNewDeck()" class="px-4 py-2 bg-sky-600 hover:bg-sky-500 text-white text-xs font-bold rounded-lg shadow-lg transition flex items-center gap-2">
        <i class="fa-solid fa-plus"></i> New Deck
      </button>

      <div id="nav-auth-slot"></div>
    </div>
  </header>

  <!-- Main Grid Workspace -->
  <div class="flex-1 max-w-7xl w-full mx-auto p-6 grid grid-cols-1 md:grid-cols-4 gap-6">

    <!-- Left Sidebar: Folders & Filters -->
    <aside id="hub-sidebar" class="md:col-span-1 space-y-4">
      <div id="folders-panel" class="bg-slate-900 border border-slate-800 rounded-xl p-4 space-y-3">
        <div class="flex items-center justify-between border-b border-slate-800 pb-2">
          <span class="text-xs font-black uppercase tracking-wider text-slate-400">Folders</span>
          <button onclick="openFolderModal()" class="text-sky-400 hover:text-white text-xs font-bold transition flex items-center gap-1">
            <i class="fa-solid fa-folder-plus"></i> New
          </button>
        </div>

        <nav id="folder-list-container" class="space-y-1 text-xs font-medium">
          <button onclick="Hub.selectFolder(null)" id="folder-all-btn" class="w-full text-left px-3 py-2 rounded-lg bg-sky-950 text-sky-400 font-bold border border-sky-800 flex items-center justify-between">
            <span class="flex items-center gap-2"><i class="fa-solid fa-layer-group"></i> All Decks</span>
            <span id="all-decks-count" class="text-[10px] bg-sky-900/60 px-2 py-0.5 rounded-full">0</span>
          </button>
        </nav>
      </div>

      <!-- Quick Search Filter -->
      <div class="bg-slate-900 border border-slate-800 rounded-xl p-4">
        <label id="filter-label" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Search Decks</label>
        <div class="relative">
          <input type="text" id="deck-filter-input" placeholder="Filter decks..." 
                 class="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-sky-500">
          <i class="fa-solid fa-magnifying-glass absolute right-3 top-2.5 text-slate-500 text-xs"></i>
        </div>
      </div>
    </aside>

    <!-- Right Content: Decks Grid / Admin Tree -->
    <main id="hub-main-content" class="md:col-span-3 space-y-4">
      <div class="flex items-center justify-between">
        <h1 id="view-title" class="text-xl font-black text-white">All Decks</h1>
        <span id="filtered-deck-count" class="text-xs text-slate-400 font-mono">Loading decks...</span>
      </div>

      <!-- Standard Decks Container -->
      <div id="decks-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4"></div>

      <!-- Admin User-Folders Tree Container -->
      <div id="admin-users-tree" class="space-y-3 hidden"></div>
    </main>
  </div>

  <!-- Rename Deck Modal -->
  <div id="rename-modal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-slate-900 border border-slate-800 rounded-xl max-w-md w-full p-5 space-y-4 shadow-2xl">
      <div class="flex justify-between items-center border-b border-slate-800 pb-2">
        <h3 class="font-bold text-white text-base">Rename Deck</h3>
        <button onclick="closeRenameModal()" class="text-slate-400 hover:text-white text-lg"><i class="fa-solid fa-xmark"></i></button>
      </div>
      <input type="text" id="rename-deck-input" class="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-sky-500">
      <div class="flex justify-end gap-2">
        <button onclick="closeRenameModal()" class="px-3 py-1.5 text-xs text-slate-300 hover:text-white">Cancel</button>
        <button id="rename-submit-btn" class="px-4 py-1.5 bg-sky-600 hover:bg-sky-500 text-white font-bold text-xs rounded-lg transition">Save</button>
      </div>
    </div>
  </div>

  <!-- Create Folder Modal -->
  <div id="folder-modal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-slate-900 border border-slate-800 rounded-xl max-w-md w-full p-5 space-y-4 shadow-2xl">
      <div class="flex justify-between items-center border-b border-slate-800 pb-2">
        <h3 class="font-bold text-white text-base">Create Folder</h3>
        <button onclick="closeFolderModal()" class="text-slate-400 hover:text-white text-lg"><i class="fa-solid fa-xmark"></i></button>
      </div>
      <input type="text" id="folder-name-input" placeholder="e.g. Competitive Meta, Sideboard Tech..." class="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-sky-500">
      <div class="flex justify-end gap-2">
        <button onclick="closeFolderModal()" class="px-3 py-1.5 text-xs text-slate-300 hover:text-white">Cancel</button>
        <button id="folder-submit-btn" class="px-4 py-1.5 bg-sky-600 hover:bg-sky-500 text-white font-bold text-xs rounded-lg transition">Create Folder</button>
      </div>
    </div>
  </div>

  <!-- Move to Folder Modal -->
  <div id="move-modal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-slate-900 border border-slate-800 rounded-xl max-w-md w-full p-5 space-y-4 shadow-2xl">
      <div class="flex justify-between items-center border-b border-slate-800 pb-2">
        <h3 class="font-bold text-white text-base">Move Deck to Folder</h3>
        <button onclick="closeMoveModal()" class="text-slate-400 hover:text-white text-lg"><i class="fa-solid fa-xmark"></i></button>
      </div>
      <select id="move-folder-select" class="w-full bg-slate-950 border border-slate-700 text-white rounded-lg p-2 text-sm focus:border-sky-500 focus:outline-none">
        <option value="">Uncategorized (No Folder)</option>
      </select>
      <div class="flex justify-end gap-2">
        <button onclick="closeMoveModal()" class="px-3 py-1.5 text-xs text-slate-300 hover:text-white">Cancel</button>
        <button id="move-submit-btn" class="px-4 py-1.5 bg-sky-600 hover:bg-sky-500 text-white font-bold text-xs rounded-lg transition">Move</button>
      </div>
    </div>
  </div>

  <!-- Auth Modal -->
  <div id="auth-modal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-slate-900 border border-slate-800 rounded-xl max-w-md w-full p-5 space-y-4 shadow-2xl">
      <div class="flex justify-between items-center border-b border-slate-800 pb-2">
        <h3 id="auth-modal-title" class="font-bold text-white text-base">Sign In</h3>
        <button onclick="closeAuthModal()" class="text-slate-400 hover:text-white text-lg"><i class="fa-solid fa-xmark"></i></button>
      </div>

      <form id="auth-form" class="space-y-4">
        <div id="auth-error-msg" class="text-xs text-rose-400 bg-rose-950/60 border border-rose-900 rounded p-2.5 hidden"></div>

        <div>
          <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Username</label>
          <input type="text" id="auth-username" required autocomplete="username" class="w-full bg-slate-950 border border-slate-700 text-white rounded-lg p-2 text-sm focus:border-sky-500 focus:outline-none">
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Password</label>
          <input type="password" id="auth-password" required minlength="8" autocomplete="current-password" class="w-full bg-slate-950 border border-slate-700 text-white rounded-lg p-2 text-sm focus:border-sky-500 focus:outline-none">
        </div>

        <div id="auth-register-extra" class="space-y-3 hidden">
          <div>
            <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Email <span class="text-slate-500 font-normal lowercase">(optional)</span></label>
            <input type="email" id="auth-email" autocomplete="email" class="w-full bg-slate-950 border border-slate-700 text-white rounded-lg p-2 text-sm focus:border-sky-500 focus:outline-none">
          </div>
          <div class="space-y-1.5 pt-1">
            <label class="flex items-center gap-2 text-xs text-slate-400 cursor-pointer">
              <input type="checkbox" id="auth-notify-events" checked class="rounded bg-slate-950 border-slate-700 text-sky-500 focus:ring-0">
              <span>Notify me of upcoming SoCal tournaments</span>
            </label>
            <label class="flex items-center gap-2 text-xs text-slate-400 cursor-pointer">
              <input type="checkbox" id="auth-notify-promos" checked class="rounded bg-slate-950 border-slate-700 text-sky-500 focus:ring-0">
              <span>Receive local LGS promos</span>
            </label>
          </div>
        </div>

        <button type="submit" id="auth-submit-btn" class="w-full py-2 bg-sky-600 hover:bg-sky-500 text-white font-bold rounded-lg transition text-sm shadow">Sign In</button>

        <div class="text-center text-xs text-slate-400 pt-2 border-t border-slate-800">
          <span id="auth-toggle-prompt">Don't have an account?</span>
          <button type="button" id="auth-toggle-mode-btn" onclick="Hub.toggleAuthMode()" class="text-sky-400 font-bold hover:underline ml-1">Create Account</button>
        </div>
      </form>
    </div>
  </div>

  <script src="js/decks_hub.js?v=11"></script>
</body>
</html>