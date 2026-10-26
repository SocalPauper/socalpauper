<!-- ==========================================================================
     SECTION: NAVIGATION HEADER
     ========================================================================== -->
<header class="border-b border-zinc-800 bg-zinc-950/90 backdrop-blur-md sticky top-0 z-40">
  <div class="max-w-6xl mx-auto px-4 h-16 flex items-center justify-between gap-2">
    
    <!-- Brand Logo & Title -->
    <a href="#" class="flex items-center gap-2.5 sm:gap-3 shrink-0 group">
      <img src="/logo.webp" alt="SoCal Pauper Logo" class="w-8 h-8 sm:w-9 sm:h-9 rounded-lg object-cover border border-amber-500/40 shadow-md group-hover:border-amber-400 transition-colors">
      <div class="flex items-center">
        <span class="font-cinzel font-bold text-base sm:text-lg tracking-wide text-zinc-100 group-hover:text-amber-400 transition-colors whitespace-nowrap">SoCal Pauper</span>
        <span class="text-[9px] sm:text-[10px] font-semibold tracking-wider text-amber-500/90 ml-1.5 uppercase px-1.5 py-0.5 rounded border border-amber-500/20 bg-amber-950/30">MTG</span>
      </div>
    </a>

    <!-- Desktop Navigation Links -->
    <nav class="hidden md:flex items-center gap-2 lg:gap-3">
      <a href="#card-explorer" class="text-xs lg:text-sm font-medium text-zinc-400 hover:text-zinc-100 px-2 py-1.5 transition">Cards</a>
      <a href="#events" class="text-xs lg:text-sm font-medium text-zinc-400 hover:text-zinc-100 px-2 py-1.5 transition">Calendar</a>
      <a href="#stores" class="text-xs lg:text-sm font-medium text-zinc-400 hover:text-zinc-100 px-2 py-1.5 transition">Partners</a>
      
      <button type="button" onclick="handleResourceClick('ENDSTEP')" 
              class="inline-flex items-center gap-1.5 bg-zinc-900 hover:bg-zinc-800 border border-zinc-700 hover:border-amber-500/50 text-zinc-300 hover:text-amber-300 text-xs font-medium px-2.5 py-1.5 rounded-lg transition cursor-pointer">
        <span>Endstep.cc</span>
        <svg class="w-3.5 h-3.5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
      </button>
      
      <button type="button" onclick="handleResourceClick('LIFECOUNTER')" 
              class="inline-flex items-center gap-1.5 bg-amber-950/40 hover:bg-amber-900/40 border border-amber-600/40 hover:border-amber-500 text-amber-300 text-xs font-medium px-2.5 py-1.5 rounded-lg transition cursor-pointer">
        <svg class="w-3.5 h-3.5 fill-current text-amber-400" viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
        <span>Life Counter</span>
      </button>
      
      <button type="button" onclick="handleResourceClick('NEWS')" 
              class="inline-flex items-center gap-1.5 bg-zinc-900 hover:bg-zinc-800 border border-zinc-700 hover:border-amber-500/50 text-zinc-300 hover:text-amber-300 text-xs font-medium px-2.5 py-1.5 rounded-lg transition cursor-pointer">
        <svg class="w-3.5 h-3.5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
        <span>News & Spoilers</span>
      </button>
      
      <a href="/BigMeatyPawzMods" 
         class="inline-flex items-center gap-1.5 bg-zinc-900 hover:bg-zinc-800 border border-amber-500/30 hover:border-amber-400 text-zinc-300 hover:text-amber-300 text-xs font-medium px-2.5 py-1.5 rounded-lg transition">
        <span>🛠️ Console Mods</span>
      </a>
      
      <a href="https://discord.gg/eQdgz5u5NE" target="_blank" rel="noopener noreferrer" 
         class="inline-flex items-center gap-1.5 bg-[#5865F2] hover:bg-[#4752C4] text-white text-xs lg:text-sm font-semibold px-3 py-1.5 rounded-lg transition shadow-md">
        <svg class="w-4 h-4 fill-current" viewBox="0 0 127.14 96.36"><path d="M107.7,8.07A105.15,105.15,0,0,0,81.47,0a72.06,72.06,0,0,0-3.36,6.83A97.68,97.68,0,0,0,49,6.83,72.37,72.37,0,0,0,45.64,0,105.89,105.89,0,0,0,19.39,8.09C2.79,32.65-1.71,56.6.54,80.21A105.73,105.73,0,0,0,32.71,96.36,77.7,77.7,0,0,0,39.6,85.25a68.42,68.42,0,0,1-10.85-5.18c.91-.66,1.8-1.34,2.66-2a75.57,75.57,0,0,0,64.32,0c.87.71,1.76,1.39,2.66,2a68.68,68.68,0,0,1-10.87,5.19,77,77,0,0,0,6.89,11.1A105.25,105.25,0,0,0,126.6,80.22c2.72-27.18-11.75-51.15-18.9-72.15ZM42.45,65.69C36.18,65.69,31,60,31,53s5-12.74,11.43-12.74S54,46,53.86,53,48.83,65.69,42.45,65.69Zm42.24,0C78.41,65.69,73.25,60,73.25,53s5-12.74,11.44-12.74S96.23,46,96.12,53,91.08,65.69,84.69,65.69Z"/></svg>
        <span>Join</span>
      </a>
    </nav>

    <!-- Mobile Right Controls -->
    <div class="flex md:hidden items-center gap-2">
      <a href="https://discord.gg/eQdgz5u5NE" target="_blank" rel="noopener noreferrer" 
         class="inline-flex items-center gap-1.5 bg-[#5865F2] hover:bg-[#4752C4] text-white text-xs font-semibold px-2.5 py-1.5 rounded-lg transition shadow-md">
        <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 127.14 96.36"><path d="M107.7,8.07A105.15,105.15,0,0,0,81.47,0a72.06,72.06,0,0,0-3.36,6.83A97.68,97.68,0,0,0,49,6.83,72.37,72.37,0,0,0,45.64,0,105.89,105.89,0,0,0,19.39,8.09C2.79,32.65-1.71,56.6.54,80.21A105.73,105.73,0,0,0,32.71,96.36,77.7,77.7,0,0,0,39.6,85.25a68.42,68.42,0,0,1-10.85-5.18c.91-.66,1.8-1.34,2.66-2a75.57,75.57,0,0,0,64.32,0c.87.71,1.76,1.39,2.66,2a68.68,68.68,0,0,1-10.87,5.19,77,77,0,0,0,6.89,11.1A105.25,105.25,0,0,0,126.6,80.22c2.72-27.18-11.75-51.15-18.9-72.15ZM42.45,65.69C36.18,65.69,31,60,31,53s5-12.74,11.43-12.74S54,46,53.86,53,48.83,65.69,42.45,65.69Zm42.24,0C78.41,65.69,73.25,60,73.25,53s5-12.74,11.44-12.74S96.23,46,96.12,53,91.08,65.69,84.69,65.69Z"/></svg>
        <span>Discord</span>
      </a>

      <!-- Hamburger Toggle Button -->
      <button id="mobile-menu-btn" onclick="toggleMobileNav()" aria-label="Toggle navigation menu"
              class="p-2 rounded-lg bg-zinc-900 border border-zinc-700 text-zinc-300 hover:text-white transition cursor-pointer">
        <svg id="menu-icon-bars" class="w-5 h-5 fill-none stroke-current" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        <svg id="menu-icon-close" class="hidden w-5 h-5 fill-none stroke-current" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>

  </div>

  <!-- Mobile Slide-Down Dropdown Menu -->
  <div id="mobile-nav-drawer" class="hidden md:hidden border-t border-zinc-800 bg-zinc-950/98 px-4 py-3 space-y-1.5 shadow-2xl">
    <a href="#card-explorer" onclick="toggleMobileNav()" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium text-zinc-200 hover:bg-zinc-900 hover:text-amber-300 transition">
      <span>🃏</span>
      <span>Cards & Pricing</span>
    </a>
    <a href="#events" onclick="toggleMobileNav()" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium text-zinc-200 hover:bg-zinc-900 hover:text-amber-300 transition">
      <span>📅</span>
      <span>Tournament Calendar</span>
    </a>
    <a href="#stores" onclick="toggleMobileNav()" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium text-zinc-200 hover:bg-zinc-900 hover:text-amber-300 transition">
      <span>📍</span>
      <span>Partner Stores & Maps</span>
    </a>
    <button type="button" onclick="handleResourceClick('LIFECOUNTER'); toggleMobileNav();" class="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium text-amber-300 bg-amber-950/30 hover:bg-amber-950/60 border border-amber-600/30 transition text-left cursor-pointer">
      <span>❤️</span>
      <span>Life Counter App</span>
    </button>
    <button type="button" onclick="handleResourceClick('NEWS'); toggleMobileNav();" class="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium text-zinc-200 hover:bg-zinc-900 hover:text-amber-300 transition text-left cursor-pointer">
      <span>📰</span>
      <span>News & Common Spoilers</span>
    </button>

    <!-- Mobile Drawer: Console Modding Link -->
    <a href="/BigMeatyPawzMods" onclick="toggleMobileNav()" class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm font-medium text-amber-300 bg-amber-950/20 hover:bg-amber-950/40 border border-amber-600/30 transition text-left">
      <span class="flex items-center gap-2.5">
        <span>🎮</span>
        <span>Retro Console Mods (BMP)</span>
      </span>
      <span class="text-[10px] font-mono uppercase bg-amber-500/20 text-amber-300 px-1.5 py-0.5 rounded border border-amber-500/30">Hardware</span>
    </a>

    <!-- Endstep.cc Link -->
    <button type="button" onclick="handleResourceClick('ENDSTEP'); toggleMobileNav();" class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm font-medium text-zinc-200 hover:bg-zinc-900 hover:text-amber-300 transition text-left cursor-pointer">
      <span class="flex items-center gap-2.5">
        <span>⚡</span>
        <span>Endstep.cc Pairings</span>
      </span>
      <svg class="w-3.5 h-3.5 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
    </button>
  </div>
</header>