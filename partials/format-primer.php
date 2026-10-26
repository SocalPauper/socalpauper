<!-- ==========================================================================
     SECTION: WHAT IS PAUPER PRIMER
     ========================================================================== -->
<div class="card-frame p-6 sm:p-8 rounded-2xl border border-zinc-800 space-y-6">
  <div class="space-y-2 border-b border-zinc-800/80 pb-4">
    <div class="inline-flex items-center gap-2 px-3 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-amber-950/60 border border-amber-600/30 text-amber-300">Format Primer</div>
    <h2 class="font-cinzel text-2xl sm:text-3xl font-extrabold text-white">What is Pauper?</h2>
    <p class="text-xs sm:text-sm text-zinc-400 max-w-3xl leading-relaxed">
      Pauper is an official competitive Magic: The Gathering Constructed format built around a simple restriction: 
      <strong>every card in your deck must have been printed at Common rarity</strong>. The result is a high-velocity, highly interactive Eternal format where games hinge on tight sequencing and deep card pools rather than high secondary-market prices.
    </p>
  </div>

  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    <div class="p-4 rounded-xl bg-zinc-900/60 border border-zinc-800 flex flex-col justify-between space-y-2">
      <div class="space-y-1.5">
        <div class="w-8 h-8 rounded-lg bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 font-cinzel font-bold text-sm">C</div>
        <h3 class="text-sm font-bold text-white">The Common Rule</h3>
        <p class="text-[11px] text-zinc-400 leading-relaxed">Any card ever printed as a Common in a paper MTG set, supplemental product (Commander, Masters, Conspiracy), or Magic Online (MTGO) release is legal.</p>
      </div>
      <div class="pt-2 border-t border-zinc-800/60 text-[10px] text-amber-400 font-semibold">Printed once as common = legal forever</div>
    </div>

    <div class="p-4 rounded-xl bg-zinc-900/60 border border-zinc-800 flex flex-col justify-between space-y-2">
      <div class="space-y-1.5">
        <div class="w-8 h-8 rounded-lg bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-400 font-bold text-sm">✨</div>
        <h3 class="text-sm font-bold text-white">Any Printing Legal</h3>
        <p class="text-[11px] text-zinc-400 leading-relaxed">As long as the card qualifies as a common, you can play <em>any</em> physical version in your deck—including Rares, Mythics, Foils, Promos, or Secret Lairs.</p>
      </div>
      <div class="pt-2 border-t border-zinc-800/60 text-[10px] text-blue-400 font-semibold">All printings & art variants permitted</div>
    </div>

    <div class="p-4 rounded-xl bg-zinc-900/60 border border-zinc-800 flex flex-col justify-between space-y-2">
      <div class="space-y-1.5">
        <div class="w-8 h-8 rounded-lg bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400 font-bold text-sm">60</div>
        <h3 class="text-sm font-bold text-white">Deck Construction</h3>
        <p class="text-[11px] text-zinc-400 leading-relaxed">Standard 60-card minimum main deck with an optional 15-card sideboard. No maximum deck size. Maximum 4 copies of any individual card, excluding basic lands.</p>
      </div>
      <div class="pt-2 border-t border-zinc-800/60 text-[10px] text-emerald-400 font-semibold">Standard 60/15 tournament limits</div>
    </div>

    <div class="p-4 rounded-xl bg-zinc-900/60 border border-zinc-800 flex flex-col justify-between space-y-2">
      <div class="space-y-1.5">
        <div class="w-8 h-8 rounded-lg bg-red-500/10 border border-red-500/20 flex items-center justify-center text-red-400 font-bold text-sm">⚔️</div>
        <h3 class="text-sm font-bold text-white">Eternal & Non-Rotating</h3>
        <p class="text-[11px] text-zinc-400 leading-relaxed">Pauper never rotates. Cards from 1993 Alpha to modern sets remain legal, managed by an active Banned list curated by the official Pauper Format Panel (PFP).</p>
      </div>
      <div class="pt-2 border-t border-zinc-800/60 text-[10px] text-red-400 font-semibold">Zero set rotation over time</div>
    </div>
  </div>

  <div class="pt-4 border-t border-zinc-800/80 grid grid-cols-1 md:grid-cols-3 gap-4 text-xs text-zinc-400">
    <div class="bg-zinc-950/40 border border-zinc-800/60 p-3.5 rounded-xl space-y-1">
      <div class="font-bold text-zinc-200">Paper & MTGO Unified</div>
      <p class="text-[11px] leading-relaxed">Legal if printed at common in any physical set or on Magic Online. Arena-only (Alchemy/Historic) downshifts are not recognized.</p>
    </div>
    <div class="bg-zinc-950/40 border border-zinc-800/60 p-3.5 rounded-xl space-y-1">
      <div class="font-bold text-zinc-200">Global Mechanic Bans</div>
      <p class="text-[11px] leading-relaxed">Ante cards, manual dexterity cards, subgame cards, conspiracies, and all Sticker/Attraction cards are completely prohibited.</p>
    </div>
    
    <div class="bg-zinc-950/40 border border-zinc-800/60 p-3.5 rounded-xl flex flex-col justify-between space-y-2">
      <div class="space-y-1">
        <div class="font-bold text-zinc-200">Curated Format Panel</div>
        <p class="text-[11px] leading-relaxed">The Pauper Format Panel (PFP) actively manages an independent banned list to regulate fast mana, combo enablers, and affinity dominance.</p>
      </div>
      <button type="button" onclick="openBannedModal()" 
              class="inline-flex items-center justify-center gap-1.5 w-full py-1.5 px-3 rounded-lg bg-red-950/60 hover:bg-red-900/60 border border-red-700/50 hover:border-red-500 text-red-300 text-xs font-bold transition cursor-pointer">
        <svg class="w-3.5 h-3.5 fill-none stroke-current" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
        <span>View Banned List (Live Scryfall)</span>
      </button>
    </div>
  </div>
</div>