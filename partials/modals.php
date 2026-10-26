<!-- ==========================================================================
     MODAL 1: BANNED LIST REFERENCE
     ========================================================================== -->
<div id="banned-modal" class="hidden fixed inset-0 z-50 bg-black/85 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto">
  <div class="bg-zinc-900 border border-zinc-700 w-full max-w-2xl rounded-2xl p-6 shadow-2xl text-left space-y-4 my-8 max-h-[85vh] flex flex-col">
    <div class="flex items-center justify-between border-b border-zinc-800 pb-3 shrink-0">
      <div class="flex items-center gap-2">
        <div class="w-8 h-8 rounded-lg bg-red-950/80 border border-red-700/50 flex items-center justify-center text-red-400 font-bold text-sm">✕</div>
        <div>
          <h3 class="font-cinzel font-bold text-lg text-white">Official Pauper Banned List</h3>
          <p class="text-[11px] text-zinc-400">Live Scryfall Index synced with Wizards of the Coast & the PFP</p>
        </div>
      </div>
      <button type="button" onclick="closeBannedModal()" class="text-zinc-400 hover:text-white text-xl px-2">&times;</button>
    </div>

    <div class="shrink-0">
      <input type="text" id="banned-search-input" oninput="filterBannedList(this.value)" placeholder="Filter banned cards (e.g. Swiftspear, Atog, Gush)..." 
             class="w-full bg-zinc-950 border border-zinc-700 focus:border-red-500 rounded-xl px-3.5 py-2 text-xs text-zinc-100 placeholder-zinc-500 focus:outline-none transition">
    </div>

    <div class="overflow-y-auto flex-1 pr-1 space-y-2 border border-zinc-800/80 rounded-xl p-2 bg-zinc-950/50 min-h-[300px]" id="banned-cards-container"></div>

    <div class="pt-3 border-t border-zinc-800 flex flex-col sm:flex-row items-center justify-between gap-2 text-[11px] text-zinc-500 shrink-0">
      <span>Stickers & Attractions banned globally May 13, 2024.</span>
      <a href="https://magic.wizards.com/en/banned-restricted-list" target="_blank" rel="noopener noreferrer" 
         class="text-amber-400 hover:underline inline-flex items-center gap-1 font-semibold">
        <span>Official WotC B&R Policy</span>
        <span>&rarr;</span>
      </a>
    </div>
  </div>
</div>

<!-- ==========================================================================
     MODAL 2: PHONE-FRIENDLY CRUD CONTROLLER (API_HUB)
     ========================================================================== -->
<div id="hub-admin-modal" class="fixed inset-0 z-50 bg-black/85 backdrop-blur-sm hidden flex items-center justify-center p-4">
  <div class="bg-zinc-950 border border-amber-500/40 max-w-md w-full p-5 sm:p-6 rounded-2xl shadow-2xl space-y-4 max-h-[92vh] overflow-y-auto">
    
    <div class="flex items-center justify-between border-b border-zinc-800 pb-3">
      <h3 id="hub-modal-title" class="font-bold text-sm sm:text-base text-zinc-100 uppercase tracking-wide">Manage Entry</h3>
      <button type="button" onclick="closeHubAdminModal()" class="text-zinc-400 hover:text-white text-lg">✕</button>
    </div>

    <form id="hub-admin-form" onsubmit="handleHubSubmit(event)" class="space-y-3 text-xs sm:text-sm text-left">
      <input type="hidden" id="hub-entry-type" value="events">
      <input type="hidden" id="hub-action" value="create">
      <input type="hidden" id="hub-entry-id" value="">
      <input type="hidden" id="hub-instance-date" value="">

      <div>
        <label class="block text-zinc-300 font-semibold mb-1">Admin Security PIN</label>
        <input type="password" id="hub-pin" required placeholder="Enter PIN" class="w-full bg-zinc-900 border border-zinc-700 rounded-lg px-3 py-2 text-zinc-100 font-mono focus:outline-none focus:border-amber-400">
      </div>

      <!-- RECURRENCE SCOPE SELECTOR -->
      <div id="recurrence-scope-box" class="hidden p-2.5 rounded-xl bg-amber-950/40 border border-amber-600/40 space-y-1.5 text-xs">
        <span class="font-semibold text-amber-300 block">Recurring Event Scope:</span>
        <div class="flex flex-col gap-1.5 text-zinc-300">
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="radio" name="event-edit-scope" value="instance" checked class="text-amber-500 focus:ring-0">
            <span>This Date Only (<span id="scope-instance-date-label" class="font-mono text-amber-200"></span>)</span>
          </label>
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="radio" name="event-edit-scope" value="series" class="text-amber-500 focus:ring-0">
            <span>All Occurrences (Entire Series)</span>
          </label>
        </div>
      </div>

      <!-- EVENTS FIELDS -->
      <div id="fields-events" class="space-y-3">
        <div>
          <label class="block text-zinc-300 font-semibold mb-1">Tournament Title</label>
          <input type="text" id="event-title" placeholder="e.g. Friday Night Pauper Weekly" class="w-full bg-zinc-900 border border-zinc-700 rounded-lg px-3 py-2 text-zinc-100 focus:outline-none focus:border-amber-400">
        </div>

        <div class="grid grid-cols-2 gap-2">
          <div>
            <label class="block text-zinc-300 font-semibold mb-1">Date</label>
            <input type="date" id="event-date" class="w-full bg-zinc-900 border border-zinc-700 rounded-lg px-3 py-2 text-zinc-100 focus:outline-none focus:border-amber-400">
          </div>
          <div>
            <label class="block text-zinc-300 font-semibold mb-1">Start Time</label>
            <input type="text" id="event-time" placeholder="e.g. 7:00 PM" class="w-full bg-zinc-900 border border-zinc-700 rounded-lg px-3 py-2 text-zinc-100 focus:outline-none focus:border-amber-400">
          </div>
        </div>

        <div class="p-3 rounded-lg bg-zinc-900/60 border border-zinc-800 space-y-2">
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" id="event-recur-check" onchange="toggleEventRecurUI()" class="w-4 h-4 rounded text-amber-600 bg-zinc-900 border-zinc-700 focus:ring-0">
            <span class="text-zinc-300 font-semibold text-xs">Recurring Tournament</span>
          </label>

          <div id="event-recur-box" class="hidden grid grid-cols-2 gap-2 pt-1 border-t border-zinc-800">
            <div>
              <label class="block text-zinc-400 text-[11px] mb-1">Frequency</label>
              <select id="event-recur-freq" class="w-full bg-zinc-950 border border-zinc-700 rounded px-2 py-1.5 text-zinc-200 text-xs">
                <option value="weekly" selected>Weekly</option>
                <option value="every-other-day">Every Other Day</option>
                <option value="daily">Daily</option>
                <option value="monthly">Monthly</option>
              </select>
            </div>
            <div>
              <label class="block text-zinc-400 text-[11px] mb-1">End Date (Opt)</label>
              <input type="date" id="event-recur-end" class="w-full bg-zinc-950 border border-zinc-700 rounded px-2 py-1 text-zinc-200 text-xs">
            </div>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-2">
          <div>
            <label class="block text-zinc-300 font-semibold mb-1">Store / Venue</label>
            <input type="text" id="event-location" placeholder="Store name" class="w-full bg-zinc-900 border border-zinc-700 rounded-lg px-3 py-2 text-zinc-100 focus:outline-none focus:border-amber-400">
          </div>
          <div>
            <label class="block text-zinc-300 font-semibold mb-1">Region</label>
            <select id="event-region" class="w-full bg-zinc-900 border border-zinc-700 rounded-lg px-3 py-2 text-zinc-100 focus:outline-none focus:border-amber-400">
              <option value="Los Angeles">Los Angeles</option>
              <option value="Orange County">Orange County</option>
              <option value="Inland Empire">Inland Empire</option>
              <option value="San Diego">San Diego</option>
            </select>
          </div>
        </div>

        <div>
          <label class="block text-zinc-300 font-semibold mb-1">Street Address</label>
          <input type="text" id="event-address" placeholder="e.g. 1234 Main St, City, CA" class="w-full bg-zinc-900 border border-zinc-700 rounded-lg px-3 py-2 text-zinc-100 focus:outline-none focus:border-amber-400">
        </div>

        <div>
          <label class="block text-zinc-300 font-semibold mb-1">Entry Fee & Notes</label>
          <input type="text" id="event-desc" placeholder="e.g. $10 Entry, Store credit prizes, Proxy friendly" class="w-full bg-zinc-900 border border-zinc-700 rounded-lg px-3 py-2 text-zinc-100 focus:outline-none focus:border-amber-400">
        </div>
      </div>

      <!-- STORES FIELDS -->
      <div id="fields-stores" class="space-y-3 hidden">
        <div>
          <label class="block text-zinc-300 font-semibold mb-1">Store Name</label>
          <input type="text" id="store-name" placeholder="e.g. Geeky Teas & Games" class="w-full bg-zinc-900 border border-zinc-700 rounded-lg px-3 py-2 text-zinc-100 focus:outline-none focus:border-amber-400">
        </div>

        <div class="grid grid-cols-2 gap-2">
          <div>
            <label class="block text-zinc-300 font-semibold mb-1">City</label>
            <input type="text" id="store-city" placeholder="e.g. Burbank" class="w-full bg-zinc-900 border border-zinc-700 rounded-lg px-3 py-2 text-zinc-100 focus:outline-none focus:border-amber-400">
          </div>
          <div>
            <label class="block text-zinc-300 font-semibold mb-1">Region</label>
            <select id="store-region" class="w-full bg-zinc-900 border border-zinc-700 rounded-lg px-3 py-2 text-zinc-100 focus:outline-none focus:border-amber-400">
              <option value="Los Angeles">Los Angeles</option>
              <option value="Orange County">Orange County</option>
              <option value="Inland Empire">Inland Empire</option>
              <option value="San Diego">San Diego</option>
            </select>
          </div>
        </div>

        <div>
          <label class="block text-zinc-300 font-semibold mb-1">Full Street Address</label>
          <input type="text" id="store-address" placeholder="e.g. 900 W Alameda Ave, Burbank, CA 91506" class="w-full bg-zinc-900 border border-zinc-700 rounded-lg px-3 py-2 text-zinc-100 focus:outline-none focus:border-amber-400">
        </div>

        <div class="grid grid-cols-2 gap-2">
          <div>
            <label class="block text-zinc-300 font-semibold mb-1">Phone (Opt)</label>
            <input type="text" id="store-phone" placeholder="e.g. (818) 394-0065" class="w-full bg-zinc-900 border border-zinc-700 rounded-lg px-3 py-2 text-zinc-100 focus:outline-none focus:border-amber-400">
          </div>
          <div>
            <label class="block text-zinc-300 font-semibold mb-1">Website URL (Opt)</label>
            <input type="text" id="store-website" placeholder="e.g. https://store.com" class="w-full bg-zinc-900 border border-zinc-700 rounded-lg px-3 py-2 text-zinc-100 focus:outline-none focus:border-amber-400">
          </div>
        </div>

        <div>
          <label class="block text-zinc-300 font-semibold mb-1">Pauper / Community Notes</label>
          <input type="text" id="store-notes" placeholder="e.g. Weekly Friday Pauper at 7PM, Proxy friendly" class="w-full bg-zinc-900 border border-zinc-700 rounded-lg px-3 py-2 text-zinc-100 focus:outline-none focus:border-amber-400">
        </div>
      </div>

      <div id="hub-modal-error" class="text-rose-400 text-xs hidden"></div>

      <div class="pt-3 border-t border-zinc-800 flex items-center justify-between">
        <button type="button" id="hub-delete-btn" onclick="handleHubDelete()" class="px-3 py-1.5 rounded-lg bg-rose-950 hover:bg-rose-900 border border-rose-700 text-rose-300 text-xs font-semibold hidden transition">
          Delete
        </button>
        <div class="flex items-center gap-2 ml-auto">
          <button type="button" onclick="closeHubAdminModal()" class="px-3.5 py-1.5 rounded-lg bg-zinc-900 hover:bg-zinc-800 text-zinc-300 text-xs transition">Cancel</button>
          <button type="submit" id="hub-submit-btn" class="px-4 py-1.5 rounded-lg bg-amber-500 hover:bg-amber-400 text-zinc-950 font-bold text-xs transition">Save</button>
        </div>
      </div>
    </form>
  </div>
</div>