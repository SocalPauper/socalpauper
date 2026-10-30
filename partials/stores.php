<!-- ==========================================================================
     SECTION: PARTNER STORES & MAPS
     ========================================================================== -->
<div id="stores" class="space-y-8">
  <div class="card-frame p-6 sm:p-8 rounded-2xl border border-zinc-800 space-y-6">
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 border-b border-zinc-800/80 pb-4">
      <div>
        <div class="inline-flex items-center gap-2 px-3 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-amber-950/60 border border-amber-600/30 text-amber-300 mb-2">
          ⭐ Featured Community Partners
        </div>
        <h2 class="font-cinzel text-2xl sm:text-3xl font-extrabold text-white">Friends of SoCal Pauper</h2>
        <p class="text-xs sm:text-sm text-zinc-400 mt-1">
          Verified local game shops that host official Pauper weeklies, stock format staples, and support the community.
        </p>
      </div>

      <div class="flex items-center gap-2 shrink-0 flex-wrap">
        <button type="button" onclick="openHubAdminModal('stores', 'create')" class="hidden staff-only inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-lg bg-amber-600 hover:bg-amber-500 text-white transition shadow">
          + Post Store
        </button>
        
        <a href="mailto:support@socalpauper.com?subject=Partner%20Store%20Application%20-%20SoCal%20Pauper&body=Hi%20SoCal%20Pauper%20Team%2C%0A%0AWe%20would%20like%20our%20game%20store%20to%20be%20featured%20as%20a%20partner%20in%20the%20%22Friends%20of%20SoCal%20Pauper%22%20section%3A%0A%0A-%20Store%20Name%3A%20%0A-%20City%20%26%20Region%20(LA%20%2F%20OC%20%2F%20IE%20%2F%20SD)%3A%20%0A-%20Full%20Street%20Address%3A%20%0A-%20Store%20Phone%20Number%3A%20%0A-%20Store%20Website%20%2F%20TCGplayer%20Link%3A%20%0A-%20Pauper%20Tournaments%20%26%20Event%20Details%3A%20%0A-%20Store%20Discord%20%2F%20Socials%3A%20%0A%0AThank%20you!" 
           class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-lg bg-amber-950/60 hover:bg-amber-900/60 border border-amber-600/50 hover:border-amber-500 text-amber-300 transition shadow-sm cursor-pointer">
          <svg class="w-3.5 h-3.5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
          <span>Request to be Added</span>
        </a>
      </div>
    </div>

    <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
      <div class="flex items-center gap-1.5 overflow-x-auto pb-1 sm:pb-0" id="store-region-filters">
        <button onclick="setStoreRegionFilter('ALL')" class="store-filter-btn px-3 py-1.5 rounded-lg text-xs font-bold border border-amber-500 bg-amber-500/20 text-amber-300">All Partners</button>
        <button onclick="setStoreRegionFilter('Los Angeles')" class="store-filter-btn px-3 py-1.5 rounded-lg text-xs font-semibold border border-zinc-800 bg-zinc-900 text-zinc-400 hover:text-zinc-200">Los Angeles</button>
        <button onclick="setStoreRegionFilter('Orange County')" class="store-filter-btn px-3 py-1.5 rounded-lg text-xs font-semibold border border-zinc-800 bg-zinc-900 text-zinc-400 hover:text-zinc-200">Orange County</button>
        <button onclick="setStoreRegionFilter('Inland Empire')" class="store-filter-btn px-3 py-1.5 rounded-lg text-xs font-semibold border border-zinc-800 bg-zinc-900 text-zinc-400 hover:text-zinc-200">Inland Empire</button>
        <button onclick="setStoreRegionFilter('San Diego')" class="store-filter-btn px-3 py-1.5 rounded-lg text-xs font-semibold border border-zinc-800 bg-zinc-900 text-zinc-400 hover:text-zinc-200">San Diego</button>
      </div>

      <div class="relative w-full sm:w-64 shrink-0">
        <input type="text" id="store-search-input" oninput="onStoreSearch(this.value)" placeholder="Search partner store or city..." 
               class="w-full bg-zinc-950 border border-zinc-700 focus:border-amber-500 rounded-xl px-3 py-1.5 text-xs text-zinc-100 placeholder-zinc-500 focus:outline-none transition">
      </div>
    </div>

    <!-- Partner Stores Grid -->
    <div id="stores-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4"></div>

    <div id="stores-pagination" class="hidden flex items-center justify-between pt-4 border-t border-zinc-800/80 text-xs text-zinc-400">
      <button id="store-page-prev-btn" onclick="changeStorePage(-1)" class="px-3 py-1.5 rounded-lg border border-zinc-700 bg-zinc-900 hover:bg-zinc-800 text-zinc-300 disabled:opacity-30 disabled:pointer-events-none transition font-medium">
        &larr; Previous
      </button>
      <span id="store-page-indicator">Page 1 of 1</span>
      <button id="store-page-next-btn" onclick="changeStorePage(1)" class="px-3 py-1.5 rounded-lg border border-zinc-700 bg-zinc-900 hover:bg-zinc-800 text-zinc-300 disabled:opacity-30 disabled:pointer-events-none transition font-medium">
        Next &rarr;
      </button>
    </div>
  </div>

  <!-- Google Maps Explorer -->
  <div class="card-frame p-6 sm:p-8 rounded-2xl border border-zinc-800 space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-zinc-800/80 pb-4">
      <div>
        <h3 class="font-cinzel text-lg sm:text-xl font-bold text-white flex items-center gap-2">
          <span>Find Any MTG Store in SoCal</span>
          <span class="text-[10px] font-bold text-blue-400 bg-blue-950/60 border border-blue-600/30 px-2 py-0.5 rounded uppercase">Google Maps Live</span>
        </h3>
        <p class="text-xs text-zinc-400">Looking for shops in another neighborhood? Enter a ZIP code, city, or cross-street to search live.</p>
      </div>

      <form onsubmit="handleLiveMapSearch(event)" class="flex items-center gap-2 w-full sm:w-auto">
        <input type="text" id="gmap-search-input" placeholder="Enter ZIP or City (e.g. 91350, Burbank, Anaheim)..." 
               class="bg-zinc-950 border border-zinc-700 focus:border-amber-500 rounded-xl px-3.5 py-2 text-xs text-zinc-100 placeholder-zinc-500 focus:outline-none transition w-full sm:w-64">
        <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-500 text-white text-xs font-bold rounded-xl transition shrink-0 active:scale-95 cursor-pointer">
          Search
        </button>
      </form>
    </div>

    <div class="w-full h-80 sm:h-96 rounded-xl overflow-hidden border border-zinc-800 bg-zinc-950">
      <iframe id="gmap-live-frame" 
              width="100%" 
              height="100%" 
              frameborder="0" 
              style="border:0;" 
              loading="lazy" 
              allowfullscreen 
              referrerpolicy="no-referrer-when-downgrade"
              src="https://maps.google.com/maps?q=Magic+the+Gathering+stores+in+Southern+California&t=&z=10&ie=UTF8&iwloc=&output=embed">
      </iframe>
    </div>
  </div>
</div>