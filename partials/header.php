<?php
// partials/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$isLoggedIn = !empty($_SESSION['user_id']);
$username = $_SESSION['username'] ?? '';
$isAdmin = !empty($_SESSION['role']) && $_SESSION['role'] === 'admin';
?>
<header class="sticky top-0 z-40 bg-[#0c0e12]/95 backdrop-blur border-b border-[#2a3040]">
  <div class="max-w-6xl mx-auto px-4 h-16 flex items-center justify-between gap-4">

    <!-- Brand Logo -->
    <a href="index.php" class="flex items-center gap-2.5 group">
      <img src="/logo.webp" alt="SoCal Pauper" class="w-8 h-8 rounded-full border border-amber-500/40 group-hover:border-amber-400 transition object-cover" onerror="this.style.display='none'">
      <div class="flex flex-col">
        <span class="font-cinzel font-bold text-base md:text-lg text-white tracking-wide group-hover:text-amber-400 transition leading-tight">
          SoCal <span class="text-amber-500">Pauper</span>
        </span>
        <span class="text-[9px] uppercase tracking-widest text-zinc-400 font-mono -mt-0.5">MTG Community</span>
      </div>
    </a>

    <!-- Desktop Navigation Links -->
    <nav class="hidden lg:flex items-center gap-1.5 text-xs font-semibold">
      <a href="index.php#calendar" class="px-3 py-1.5 rounded-lg text-zinc-300 hover:text-white hover:bg-zinc-800/60 border border-transparent hover:border-zinc-700/60 transition flex items-center gap-1.5">
        <i class="fa-solid fa-calendar-days text-zinc-400 text-xs"></i>
        <span>Events</span>
      </a>

      <a href="index.php#stores" class="px-3 py-1.5 rounded-lg text-zinc-300 hover:text-white hover:bg-zinc-800/60 border border-transparent hover:border-zinc-700/60 transition flex items-center gap-1.5">
        <i class="fa-solid fa-store text-zinc-400 text-xs"></i>
        <span>Stores</span>
      </a>

      <a href="index.php#card-explorer" class="px-3 py-1.5 rounded-lg text-zinc-300 hover:text-white hover:bg-zinc-800/60 border border-transparent hover:border-zinc-700/60 transition flex items-center gap-1.5">
        <i class="fa-solid fa-magnifying-glass text-zinc-400 text-xs"></i>
        <span>Card Explorer</span>
      </a>

      <a href="index.php#format-primer" class="px-3 py-1.5 rounded-lg text-zinc-300 hover:text-white hover:bg-zinc-800/60 border border-transparent hover:border-zinc-700/60 transition flex items-center gap-1.5">
        <i class="fa-solid fa-book-open text-zinc-400 text-xs"></i>
        <span>Primer</span>
      </a>

      <div class="h-4 w-px bg-zinc-800 mx-1"></div>

      <!-- Deck Hub & Architect Links -->
      <a href="decks.php" class="px-3 py-1.5 rounded-lg text-amber-300 bg-amber-500/10 hover:bg-amber-500/20 border border-amber-500/30 transition flex items-center gap-1.5 shadow-sm">
        <i class="fa-solid fa-layer-group text-amber-400 text-xs"></i>
        <span>Deck Hub</span>
      </a>

      <a href="builder.php" class="px-3 py-1.5 rounded-lg text-zinc-300 hover:text-white hover:bg-zinc-800/60 border border-transparent hover:border-zinc-700/60 transition flex items-center gap-1.5">
        <i class="fa-solid fa-wand-magic-sparkles text-emerald-400 text-xs"></i>
        <span>Architect</span>
      </a>

      <!-- Admin Broadcast Center -->
      <?php if ($isAdmin): ?>
        <a href="admin_broadcast.php" class="px-3 py-1.5 rounded-lg text-purple-300 hover:text-purple-200 bg-purple-950/40 hover:bg-purple-900/60 border border-purple-800/60 transition flex items-center gap-1.5 shadow-sm">
          <i class="fa-solid fa-bullhorn text-purple-400 text-xs"></i>
          <span>Broadcast</span>
        </a>
      <?php endif; ?>

      <!-- Discord -->
      <a href="https://discord.gg/socalpauper" target="_blank" rel="noopener noreferrer" class="px-3 py-1.5 rounded-lg text-indigo-200 bg-[#5865F2]/20 hover:bg-[#5865F2]/30 border border-[#5865F2]/40 transition flex items-center gap-1.5 shadow-sm">
        <i class="fa-brands fa-discord text-indigo-400 text-xs"></i>
        <span>Discord</span>
      </a>

      <!-- Desktop Auth Slot -->
      <div id="header-auth-slot" class="ml-2 flex items-center">
        <?php if ($isLoggedIn): ?>
          <div class="flex items-center gap-2">
            <a href="decks.php" class="text-xs text-sky-400 font-bold uppercase tracking-wider hover:underline flex items-center gap-1">
              <span>👤 <?= htmlspecialchars($username) ?></span>
              <?php if ($isAdmin): ?><span class="text-[9px] uppercase bg-purple-950 border border-purple-700 text-purple-300 font-black px-1 rounded">Admin</span><?php endif; ?>
            </a>
            <button onclick="headerLogout()" class="text-xs bg-zinc-800 hover:bg-zinc-700 text-zinc-300 px-2 py-1 rounded border border-zinc-700 transition">Log Out</button>
          </div>
        <?php else: ?>
          <button onclick="openHeaderAuthModal()" class="text-xs bg-sky-600 hover:bg-sky-500 text-white font-semibold px-3 py-1.5 rounded-lg transition shadow-sm flex items-center gap-1.5">
            <i class="fa-solid fa-user text-[11px]"></i> Sign In
          </button>
        <?php endif; ?>
      </div>
    </nav>

    <!-- Mobile Hamburger Toggle -->
    <div class="flex items-center gap-2 lg:hidden">
      <a href="decks.php" class="px-2.5 py-1.5 rounded-lg text-amber-400 bg-amber-500/10 border border-amber-500/30 text-xs font-bold flex items-center gap-1">
        <i class="fa-solid fa-layer-group text-[11px]"></i> Hub
      </a>
      <button id="mobile-menu-toggle" type="button" aria-label="Toggle Navigation" class="w-9 h-9 rounded-lg bg-zinc-900 border border-zinc-800 text-zinc-300 hover:text-white flex items-center justify-center transition">
        <i class="fa-solid fa-bars text-sm" id="mobile-menu-icon"></i>
      </button>
    </div>

  </div>

  <!-- Mobile Collapsible Menu Drawer -->
  <div id="mobile-menu-drawer" class="hidden lg:hidden border-t border-[#2a3040] bg-[#0c0e12]/98 px-4 py-4 space-y-3">
    <!-- User Status Banner for Mobile -->
    <div class="p-2.5 rounded-lg bg-zinc-900 border border-zinc-800 flex items-center justify-between">
      <?php if ($isLoggedIn): ?>
        <div class="flex items-center gap-2">
          <span class="text-xs text-sky-400 font-bold uppercase">👤 <?= htmlspecialchars($username) ?></span>
          <?php if ($isAdmin): ?><span class="text-[9px] bg-purple-950 border border-purple-700 text-purple-300 font-bold px-1 rounded">Admin</span><?php endif; ?>
        </div>
        <button onclick="headerLogout()" class="text-xs bg-zinc-800 hover:bg-zinc-700 text-zinc-300 px-2 py-1 rounded border border-zinc-700">Log Out</button>
      <?php else: ?>
        <span class="text-xs text-zinc-400">Join the deck hub & tournaments</span>
        <button onclick="openHeaderAuthModal()" class="text-xs bg-sky-600 hover:bg-sky-500 text-white font-semibold px-3 py-1 rounded">Sign In</button>
      <?php endif; ?>
    </div>

    <div class="grid grid-cols-2 gap-2 text-xs font-semibold">
      <a href="index.php#calendar" class="p-2.5 rounded-lg text-zinc-300 hover:text-white bg-zinc-900/60 border border-zinc-800/80 flex items-center gap-2">
        <i class="fa-solid fa-calendar-days text-zinc-400"></i> Events
      </a>
      <a href="index.php#stores" class="p-2.5 rounded-lg text-zinc-300 hover:text-white bg-zinc-900/60 border border-zinc-800/80 flex items-center gap-2">
        <i class="fa-solid fa-store text-zinc-400"></i> Stores
      </a>
      <a href="index.php#card-explorer" class="p-2.5 rounded-lg text-zinc-300 hover:text-white bg-zinc-900/60 border border-zinc-800/80 flex items-center gap-2">
        <i class="fa-solid fa-magnifying-glass text-zinc-400"></i> Cards
      </a>
      <a href="index.php#format-primer" class="p-2.5 rounded-lg text-zinc-300 hover:text-white bg-zinc-900/60 border border-zinc-800/80 flex items-center gap-2">
        <i class="fa-solid fa-book-open text-zinc-400"></i> Primer
      </a>
    </div>

    <div class="pt-2 border-t border-zinc-800/80 space-y-2 text-xs font-semibold">
      <a href="decks.php" class="w-full p-2.5 rounded-lg text-amber-300 bg-amber-500/10 border border-amber-500/30 flex items-center justify-between">
        <span class="flex items-center gap-2"><i class="fa-solid fa-layer-group text-amber-400"></i> Deck Command Hub</span>
        <i class="fa-solid fa-arrow-right text-[10px] text-amber-400"></i>
      </a>

      <a href="builder.php" class="w-full p-2.5 rounded-lg text-zinc-300 bg-zinc-900/60 border border-zinc-800/80 flex items-center justify-between hover:text-white">
        <span class="flex items-center gap-2"><i class="fa-solid fa-wand-magic-sparkles text-emerald-400"></i> Deck Architect</span>
        <i class="fa-solid fa-arrow-right text-[10px] text-zinc-500"></i>
      </a>

      <?php if ($isAdmin): ?>
        <a href="admin_broadcast.php" class="w-full p-2.5 rounded-lg text-purple-300 bg-purple-950/40 border border-purple-800/60 flex items-center justify-between">
          <span class="flex items-center gap-2"><i class="fa-solid fa-bullhorn text-purple-400"></i> Dispatch Broadcast</span>
          <span class="text-[9px] uppercase font-mono px-1.5 py-0.5 rounded bg-purple-900/80 text-purple-200">Admin</span>
        </a>
      <?php endif; ?>

      <a href="https://discord.gg/socalpauper" target="_blank" rel="noopener noreferrer" class="w-full p-2.5 rounded-lg text-indigo-200 bg-[#5865F2]/20 border border-[#5865F2]/40 flex items-center justify-between">
        <span class="flex items-center gap-2"><i class="fa-brands fa-discord text-indigo-400"></i> Join Discord Community</span>
        <i class="fa-solid fa-arrow-up-right-from-square text-[10px] text-indigo-400"></i>
      </a>
    </div>
  </div>
</header>

<!-- Header Auth Modal (Sign In / Register from any page) -->
<div id="header-auth-modal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
  <div class="bg-slate-900 border border-slate-800 rounded-xl max-w-md w-full p-5 space-y-4 shadow-2xl">
    <div class="flex justify-between items-center border-b border-slate-800 pb-2">
      <h3 id="header-auth-title" class="font-bold text-white text-base">Sign In</h3>
      <button onclick="closeHeaderAuthModal()" class="text-slate-400 hover:text-white text-lg"><i class="fa-solid fa-xmark"></i></button>
    </div>

    <form id="header-auth-form" class="space-y-4">
      <div id="header-auth-error" class="text-xs text-rose-400 bg-rose-950/60 border border-rose-900 rounded p-2.5 hidden"></div>

      <div>
        <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Username</label>
        <input type="text" id="header-auth-username" required class="w-full bg-slate-950 border border-slate-700 text-white rounded-lg p-2 text-sm focus:border-sky-500 focus:outline-none">
      </div>

      <div>
        <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Password</label>
        <input type="password" id="header-auth-password" required minlength="8" class="w-full bg-slate-950 border border-slate-700 text-white rounded-lg p-2 text-sm focus:border-sky-500 focus:outline-none">
      </div>

      <div id="header-auth-extra" class="space-y-3 hidden">
        <div>
          <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Email <span class="text-slate-500 font-normal lowercase">(optional)</span></label>
          <input type="email" id="header-auth-email" class="w-full bg-slate-950 border border-slate-700 text-white rounded-lg p-2 text-sm focus:border-sky-500 focus:outline-none">
        </div>
        <div class="space-y-1.5 pt-1">
          <label class="flex items-center gap-2 text-xs text-slate-400 cursor-pointer">
            <input type="checkbox" id="header-auth-events" checked class="rounded bg-slate-950 border-slate-700 text-sky-500 focus:ring-0">
            <span>Notify me of upcoming SoCal tournaments</span>
          </label>
          <label class="flex items-center gap-2 text-xs text-slate-400 cursor-pointer">
            <input type="checkbox" id="header-auth-promos" checked class="rounded bg-slate-950 border-slate-700 text-sky-500 focus:ring-0">
            <span>Receive local LGS promos</span>
          </label>
        </div>
      </div>

      <button type="submit" id="header-auth-submit-btn" class="w-full py-2 bg-sky-600 hover:bg-sky-500 text-white font-bold rounded-lg transition text-sm shadow">Sign In</button>

      <div class="text-center text-xs text-slate-400 pt-2 border-t border-slate-800">
        <span id="header-auth-prompt">Don't have an account?</span>
        <button type="button" id="header-auth-toggle-btn" onclick="toggleHeaderAuthMode()" class="text-sky-400 font-bold hover:underline ml-1">Create Account</button>
      </div>
    </form>
  </div>
</div>

<script>
  let headerAuthMode = 'login';

  function openHeaderAuthModal() {
    document.getElementById('header-auth-modal')?.classList.remove('hidden');
  }

  function closeHeaderAuthModal() {
    document.getElementById('header-auth-modal')?.classList.add('hidden');
  }

  function toggleHeaderAuthMode() {
    headerAuthMode = headerAuthMode === 'login' ? 'register' : 'login';
    const title = document.getElementById('header-auth-title');
    const submitBtn = document.getElementById('header-auth-submit-btn');
    const prompt = document.getElementById('header-auth-prompt');
    const toggleBtn = document.getElementById('header-auth-toggle-btn');
    const extra = document.getElementById('header-auth-extra');
    const err = document.getElementById('header-auth-error');
    if (err) err.classList.add('hidden');

    if (headerAuthMode === 'register') {
      if (title) title.textContent = 'Create Account';
      if (submitBtn) submitBtn.textContent = 'Sign Up';
      if (prompt) prompt.textContent = 'Already have an account?';
      if (toggleBtn) toggleBtn.textContent = 'Sign In';
      if (extra) extra.classList.remove('hidden');
    } else {
      if (title) title.textContent = 'Sign In';
      if (submitBtn) submitBtn.textContent = 'Sign In';
      if (prompt) prompt.textContent = "Don't have an account?";
      if (toggleBtn) toggleBtn.textContent = 'Create Account';
      if (extra) extra.classList.add('hidden');
    }
  }

  async function headerLogout() {
    await fetch('/api_auth.php?action=logout');
    window.location.reload();
  }

  document.getElementById('header-auth-form')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const username = document.getElementById('header-auth-username').value.trim();
    const password = document.getElementById('header-auth-password').value;
    const err = document.getElementById('header-auth-error');

    const endpoint = headerAuthMode === 'register' ? '/api_auth.php?action=register' : '/api_auth.php?action=login';
    const payload = { username, password };

    if (headerAuthMode === 'register') {
      payload.email = document.getElementById('header-auth-email').value.trim();
      payload.notify_events = document.getElementById('header-auth-events').checked;
      payload.notify_promotions = document.getElementById('header-auth-promos').checked;
    }

    try {
      const res = await fetch(endpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      if (!res.ok) throw new Error(data.error || 'Authentication failed');
      window.location.reload();
    } catch (error) {
      if (err) {
        err.textContent = error.message;
        err.classList.remove('hidden');
      }
    }
  });

  // Mobile menu button toggle
  (function() {
    const toggle = document.getElementById('mobile-menu-toggle');
    const drawer = document.getElementById('mobile-menu-drawer');
    const icon = document.getElementById('mobile-menu-icon');

    if (toggle && drawer) {
      toggle.addEventListener('click', function() {
        const isClosed = drawer.classList.contains('hidden');
        if (isClosed) {
          drawer.classList.remove('hidden');
          if (icon) icon.className = 'fa-solid fa-xmark text-sm';
        } else {
          drawer.classList.add('hidden');
          if (icon) icon.className = 'fa-solid fa-bars text-sm';
        }
      });
    }
  })();
</script>