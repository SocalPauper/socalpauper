<!-- ==========================================================================
     SECTION: TOURNAMENT CALENDAR
     ========================================================================== -->
<div id="events" class="space-y-6">
  <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 pb-4 border-b border-zinc-800">
    <div>
      <h2 class="text-2xl sm:text-3xl font-bold text-zinc-100 flex items-center gap-3">
        Tournament Calendar
      </h2>
      <p class="text-zinc-400 text-xs sm:text-sm mt-1">Select any date to view scheduled weeklies, monthly 1Ks, and store addresses.</p>
    </div>

    <div class="flex items-center gap-2">
      <button type="button" onclick="openHubAdminModal('events', 'create')" class="hidden staff-only inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-lg bg-amber-600 hover:bg-amber-500 text-white transition shadow">
        + Post Event
      </button>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
    <!-- Calendar UI -->
    <div class="lg:col-span-7 card-frame p-4 sm:p-6 rounded-2xl">
      <div class="flex items-center justify-between mb-4">
        <h3 id="calendar-month-year" class="font-cinzel font-bold text-lg text-amber-200"></h3>
        <div class="flex items-center gap-1">
          <button onclick="prevMonth()" aria-label="Previous Month" class="p-2 rounded-lg bg-zinc-900 border border-zinc-700 hover:bg-zinc-800 text-zinc-300 transition flex items-center justify-center">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
          </button>
          <button onclick="nextMonth()" aria-label="Next Month" class="p-2 rounded-lg bg-zinc-900 border border-zinc-700 hover:bg-zinc-800 text-zinc-300 transition flex items-center justify-center">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
          </button>
        </div>
      </div>

      <div class="grid grid-cols-7 gap-1 text-center font-semibold text-[11px] text-zinc-500 mb-2 uppercase">
        <span>Sun</span><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span>
      </div>

      <div id="calendar-days" class="grid grid-cols-7 gap-1.5 text-sm"></div>
    </div>

    <!-- Agenda / Event Cards -->
    <div class="lg:col-span-5 space-y-4">
      <div class="flex items-center justify-between">
        <h3 class="font-cinzel text-lg font-bold text-zinc-200" id="agenda-title">Upcoming Tournaments</h3>
        <span class="text-xs text-zinc-400" id="event-count-badge"></span>
      </div>

      <div id="events-list" class="space-y-3"></div>

      <div id="events-pagination" class="hidden flex items-center justify-between pt-2 border-t border-zinc-800/80 text-xs text-zinc-400">
        <button id="page-prev-btn" onclick="changeEventPage(-1)" class="px-3 py-1.5 rounded-lg border border-zinc-700 bg-zinc-900 hover:bg-zinc-800 text-zinc-300 disabled:opacity-30 disabled:pointer-events-none transition font-medium">
          &larr; Previous
        </button>
        <span id="page-indicator">Page 1 of 1</span>
        <button id="page-next-btn" onclick="changeEventPage(1)" class="px-3 py-1.5 rounded-lg border border-zinc-700 bg-zinc-900 hover:bg-zinc-800 text-zinc-300 disabled:opacity-30 disabled:pointer-events-none transition font-medium">
          Next &rarr;
        </button>
      </div>
    </div>
  </div>
</div>