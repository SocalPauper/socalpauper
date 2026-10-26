<!-- ==========================================================================
     SECTION: CARD EXPLORER & PRICING
     ========================================================================== -->
<div id="card-explorer" class="card-frame p-6 sm:p-8 rounded-2xl border border-zinc-800 space-y-6">
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-zinc-800/80 pb-3">
    <div>
      <h3 class="font-cinzel text-xl sm:text-2xl font-bold text-white flex items-center gap-2">
        <span>Card Price & Version Explorer</span>
        <span class="text-[10px] font-bold text-amber-400 bg-amber-950/60 border border-amber-600/30 px-2 py-0.5 rounded uppercase">TCGplayer Live</span>
      </h3>
      <p class="text-xs text-zinc-400">Search any MTG card to browse all printings, sets, full-art treatments, foil market prices, and official Gatherer rulings.</p>
    </div>
  </div>

  <!-- Search Input -->
  <div class="relative max-w-xl">
    <div class="relative">
      <input type="text" id="card-search-input" autocomplete="off" placeholder="Type a card name (e.g. Counterspell, Lightning Bolt, Lotus Petal)..." 
             class="w-full bg-zinc-950 border border-zinc-700 focus:border-amber-500 rounded-xl px-4 py-3 text-sm text-zinc-100 placeholder-zinc-500 focus:outline-none transition shadow-inner">
      <button id="card-search-clear" onclick="clearCardSearch()" class="hidden absolute right-3 top-3 text-zinc-500 hover:text-zinc-300 text-sm">✕</button>
    </div>
    <div id="autocomplete-list" class="hidden absolute left-0 right-0 top-full mt-1.5 bg-zinc-900 border border-zinc-700 rounded-xl overflow-hidden shadow-2xl z-50 max-h-60 overflow-y-auto"></div>
  </div>

  <div id="card-search-status" class="hidden text-xs text-zinc-400 font-medium"></div>

  <!-- Card Result Card Display -->
  <div id="card-result-box" class="hidden pt-2 border-t border-zinc-800/80 space-y-6">
    <div class="p-3.5 rounded-xl bg-zinc-900/90 border border-zinc-800 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
      <div class="flex items-center gap-2 flex-1 min-w-0">
        <span class="text-xs text-zinc-400 font-semibold whitespace-nowrap">Printing:</span>
        <button onclick="cyclePrinting(-1)" class="p-2 rounded-lg bg-zinc-800 border border-zinc-700 hover:bg-zinc-700 text-zinc-200 transition text-xs font-bold" title="Previous version">&lt;</button>
        <select id="card-version-select" onchange="onPrintingSelected(this.value)" 
                class="bg-zinc-950 border border-zinc-700 text-zinc-100 text-xs font-medium rounded-lg px-3 py-2 flex-1 truncate focus:outline-none focus:border-amber-500">
        </select>
        <button onclick="cyclePrinting(1)" class="p-2 rounded-lg bg-zinc-800 border border-zinc-700 hover:bg-zinc-700 text-zinc-200 transition text-xs font-bold" title="Next version">&gt;</button>
      </div>

      <div class="flex items-center gap-1.5 self-start sm:self-auto shrink-0" id="finish-toggle-group">
        <button id="btn-finish-nonfoil" onclick="setFinish('nonfoil')" class="px-3 py-1.5 rounded-lg border text-xs font-semibold transition cursor-pointer">Regular</button>
        <button id="btn-finish-foil" onclick="setFinish('foil')" class="px-3 py-1.5 rounded-lg border text-xs font-semibold transition cursor-pointer">✨ Foil</button>
      </div>
    </div>

    <div class="flex flex-col md:flex-row items-center md:items-start gap-6 bg-zinc-950/60 border border-zinc-800/80 p-5 rounded-xl">
      <div class="w-48 sm:w-56 shrink-0 text-center relative group">
        <div id="foil-sheen-overlay" class="hidden absolute inset-0 rounded-xl pointer-events-none z-10 opacity-40 bg-gradient-to-tr from-transparent via-cyan-400/30 to-amber-300/40 mix-blend-color-dodge"></div>
        <img id="card-display-img" src="" alt="MTG Card" class="w-full rounded-xl shadow-2xl border border-zinc-800/80 hover:scale-105 transition-transform duration-200">
      </div>

      <div class="flex-1 w-full space-y-3.5">
        <div class="flex flex-wrap items-start justify-between gap-2">
          <div>
            <h4 id="card-display-name" class="font-cinzel text-xl font-bold text-white"></h4>
            <div id="card-display-type" class="text-xs text-zinc-400 mt-0.5"></div>
          </div>
          <span id="card-display-legality" class="text-xs font-bold px-2.5 py-1 rounded"></span>
        </div>

        <div id="card-treatments-badges" class="flex flex-wrap items-center gap-1.5"></div>

        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 pt-2">
          <div class="bg-zinc-900/90 border border-zinc-800 p-3 rounded-lg text-center">
            <span class="text-[10px] uppercase font-bold text-zinc-500 tracking-wider block">Market Price</span>
            <span id="active-market-price" class="text-lg font-extrabold text-amber-400">--</span>
          </div>
          <div class="bg-zinc-900/90 border border-zinc-800 p-3 rounded-lg text-center">
            <span class="text-[10px] uppercase font-bold text-zinc-500 tracking-wider block">Finish</span>
            <span id="active-finish-label" class="text-xs font-bold text-zinc-200 uppercase mt-1 block">Normal</span>
          </div>
          <div class="col-span-2 sm:col-span-1 bg-zinc-900/90 border border-zinc-800 p-3 rounded-lg text-center flex flex-col justify-center">
            <span class="text-[10px] uppercase font-bold text-zinc-500 tracking-wider block">Collector #</span>
            <span id="card-display-collector" class="text-xs font-semibold text-zinc-200 truncate mt-0.5">--</span>
          </div>
        </div>

        <div class="bg-zinc-900/50 border border-zinc-800/60 p-3 rounded-lg">
          <p id="card-display-oracle" class="text-xs text-zinc-300 leading-relaxed font-sans whitespace-pre-line"></p>
        </div>

        <div class="pt-2 flex flex-wrap items-center gap-3">
          <a id="card-tcg-link" href="#" target="_blank" rel="noopener noreferrer" 
             class="inline-flex items-center gap-2 bg-[#1b6bba] hover:bg-[#165696] text-white text-xs font-bold px-4 py-2.5 rounded-lg transition shadow-md">
            <span id="tcg-btn-text">View This Version on TCGplayer</span>
            <svg class="w-3.5 h-3.5 fill-none stroke-current" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
          </a>
          <span class="text-[11px] text-zinc-500">Live Scryfall & TCGplayer Market Price sync</span>
        </div>
      </div>
    </div>

    <div class="border-t border-zinc-800/80 pt-5 space-y-3">
      <div class="flex items-center justify-between">
        <h5 class="font-cinzel text-base font-bold text-zinc-200 flex items-center gap-2">
          <span>Official Rules & Rulings</span>
          <span id="rulings-count-badge" class="text-[10px] font-semibold text-zinc-400 bg-zinc-800 px-2 py-0.5 rounded">0 rulings</span>
        </h5>
        <span class="text-[11px] text-zinc-500">Official Gatherer / WotC Oracle Notes</span>
      </div>
      <div id="card-rulings-container" class="space-y-2"></div>
    </div>
  </div>
</div>