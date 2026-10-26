<!-- ==========================================================================
     SECTION: COMMUNITY LINKS & EXTERNAL RESOURCES
     ========================================================================== -->
<div class="card-frame p-6 sm:p-8 rounded-2xl border border-zinc-800 space-y-4">
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-zinc-800/80 pb-3">
    <div>
      <h3 class="font-cinzel text-lg font-bold text-white">Tournament & Community Resources</h3>
      <p class="text-xs text-zinc-400">Pairings, deck registration, and competitive circuit tools.</p>
    </div>
  </div>

  <div id="community-links-shelf" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
    <button type="button" onclick="handleResourceClick('ENDSTEP')" 
            class="w-full text-left flex items-center justify-between p-3.5 rounded-xl bg-zinc-900/80 hover:bg-zinc-800 border border-zinc-700/70 hover:border-amber-500/50 group transition duration-150 cursor-pointer">
      <div class="flex items-center gap-3">
        <div class="w-9 h-9 rounded-lg bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 font-bold group-hover:scale-105 transition-transform">
          <svg class="w-4 h-4 fill-none stroke-current" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
        </div>
        <div>
          <div class="text-sm font-bold text-zinc-100 group-hover:text-amber-300 transition-colors">Endstep.cc</div>
          <div class="text-[11px] text-zinc-400">Play Pauper Online Free</div>
        </div>
      </div>
      <svg class="w-4 h-4 text-zinc-500 group-hover:text-zinc-200 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
    </button>

    <button type="button" onclick="handleResourceClick('MTGTOP8')" 
            class="w-full text-left flex items-center justify-between p-3.5 rounded-xl bg-zinc-900/80 hover:bg-zinc-800 border border-zinc-700/70 hover:border-amber-500/50 group transition duration-150 cursor-pointer">
      <div class="flex items-center gap-3">
        <div class="w-9 h-9 rounded-lg bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-400 font-bold group-hover:scale-105 transition-transform">
          <svg class="w-4 h-4 fill-none stroke-current" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
        </div>
        <div>
          <div class="text-sm font-bold text-zinc-100 group-hover:text-amber-300 transition-colors">MTG Top 8</div>
          <div class="text-[11px] text-zinc-400">Pauper Metagame & Tier Lists</div>
        </div>
      </div>
      <svg class="w-4 h-4 text-zinc-500 group-hover:text-zinc-200 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
    </button>
    
    <button type="button" onclick="handleResourceClick('ENDSTEPMETA')" 
            class="w-full text-left flex items-center justify-between p-3.5 rounded-xl bg-zinc-900/80 hover:bg-zinc-800 border border-zinc-700/70 hover:border-amber-500/50 group transition duration-150 cursor-pointer">
      <div class="flex items-center gap-3">
        <div class="w-9 h-9 rounded-lg bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400 font-bold group-hover:scale-105 transition-transform">
          <svg class="w-4 h-4 fill-none stroke-current" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
        </div>
        <div>
          <div class="text-sm font-bold text-zinc-100 group-hover:text-amber-300 transition-colors">EndStep Pauper Meta</div>
          <div class="text-[11px] text-zinc-400">Metagame Data on Endstep</div>
        </div>
      </div>
      <svg class="w-4 h-4 text-zinc-500 group-hover:text-zinc-200 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
    </button>

    <button type="button" onclick="handleResourceClick('MTGDECKS')" 
            class="w-full text-left flex items-center justify-between p-3.5 rounded-xl bg-zinc-900/80 hover:bg-zinc-800 border border-zinc-700/70 hover:border-amber-500/50 group transition duration-150 cursor-pointer">
      <div class="flex items-center gap-3">
        <div class="w-9 h-9 rounded-lg bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400 font-bold group-hover:scale-105 transition-transform">
          <svg class="w-4 h-4 fill-none stroke-current" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
        </div>
        <div>
          <div class="text-sm font-bold text-zinc-100 group-hover:text-amber-300 transition-colors">MTG Decks</div>
          <div class="text-[11px] text-zinc-400">Metagame Data for MTGO and Paper Magic</div>
        </div>
      </div>
      <svg class="w-4 h-4 text-zinc-500 group-hover:text-zinc-200 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
    </button>
    
    <button type="button" onclick="handleResourceClick('LIFECOUNTER')" 
            class="w-full text-left flex items-center justify-between p-3.5 rounded-xl bg-zinc-900/80 hover:bg-zinc-800 border border-zinc-700/70 hover:border-amber-500/50 group transition duration-150 cursor-pointer">
      <div class="flex items-center gap-3">
        <div class="w-9 h-9 rounded-lg bg-red-500/10 border border-red-500/20 flex items-center justify-center text-red-400 font-bold group-hover:scale-105 transition-transform">
          <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
        </div>
        <div>
          <div class="text-sm font-bold text-zinc-100 group-hover:text-amber-300 transition-colors">Pauper Life Counter</div>
          <div class="text-[11px] text-zinc-400">2-Player Mobile Tracker & Statuses</div>
        </div>
      </div>
      <svg class="w-4 h-4 text-zinc-500 group-hover:text-zinc-200 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
    </button>
    
    <button type="button" onclick="handleResourceClick('NEWS')" 
            class="w-full text-left flex items-center justify-between p-3.5 rounded-xl bg-zinc-900/80 hover:bg-zinc-800 border border-zinc-700/70 hover:border-amber-500/50 group transition duration-150 cursor-pointer">
      <div class="flex items-center gap-3">
        <div class="w-9 h-9 rounded-lg bg-red-500/10 border border-red-500/20 flex items-center justify-center text-red-400 font-bold group-hover:scale-105 transition-transform">
          <svg class="w-4 h-4 fill-none stroke-current" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
          </svg>
        </div>
        <div>
          <div class="text-sm font-bold text-zinc-100 group-hover:text-amber-300 transition-colors">MTG News & Spoilers</div>
          <div class="text-[11px] text-zinc-400">Upcoming Sets, Secret Lairs & B&R Updates</div>
        </div>
      </div>
      <svg class="w-4 h-4 text-zinc-500 group-hover:text-zinc-200 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
    </button>
  </div>
</div>