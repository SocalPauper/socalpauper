// ==========================================================================
// TOURNAMENT CALENDAR & RECURRENCE ENGINE
// ==========================================================================
let events = [];
let currentDate = new Date();
let selectedDateStr = null;
const EVENTS_PER_PAGE = 4;
let currentEventPage = 1;

function isEventRecurring(ev) {
  return ev && (ev.recurring === true || ev.recurring === 1 || ev.recurring === "1" || ev.recurring === "true");
}

function eventMatchesDate(ev, targetDateStr) {
  if (!isEventRecurring(ev)) {
    return ev.date === targetDateStr;
  }
  
  if (targetDateStr < ev.date) return false;
  if (ev.recurEnd && targetDateStr > ev.recurEnd) return false;

  // Skip individual excluded dates
  if (Array.isArray(ev.excludeDates) && ev.excludeDates.includes(targetDateStr)) {
    return false;
  }

  const [sY, sM, sD] = ev.date.split('-').map(Number);
  const [tY, tM, tD] = targetDateStr.split('-').map(Number);

  const startUtc = Date.UTC(sY, sM - 1, sD);
  const targetUtc = Date.UTC(tY, tM - 1, tD);
  const diffDays = Math.round((targetUtc - startUtc) / 86400000);

  const freq = ev.recurFreq || 'weekly';
  if (freq === 'daily') return true;
  if (freq === 'every-other-day') return diffDays % 2 === 0;
  if (freq === 'weekly') return diffDays % 7 === 0;
  if (freq === 'biweekly' || freq === 'every-other-week') return diffDays % 14 === 0;
  if (freq === 'monthly') return sD === tD;
  return false;
}

function getEventsForDate(dateStr) {
  return events.filter(e => eventMatchesDate(e, dateStr));
}

function getEventsForRange(startStr, endStr) {
  const results = [];
  const [sY, sM, sD] = startStr.split('-').map(Number);
  const [eY, eM, eD] = endStr.split('-').map(Number);

  let cur = new Date(sY, sM - 1, sD);
  const end = new Date(eY, eM - 1, eD);

  while (cur <= end) {
    const y = cur.getFullYear();
    const m = String(cur.getMonth() + 1).padStart(2, '0');
    const d = String(cur.getDate()).padStart(2, '0');
    const dStr = `${y}-${m}-${d}`;

    getEventsForDate(dStr).forEach(e => results.push({ ...e, instanceDate: dStr }));
    cur.setDate(cur.getDate() + 1);
  }
  return results;
}

function renderCalendar() {
  const year = currentDate.getFullYear();
  const month = currentDate.getMonth();
  const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
  
  const titleEl = document.getElementById('calendar-month-year');
  if (titleEl) titleEl.textContent = `${monthNames[month]} ${year}`;

  const firstDay = new Date(year, month, 1).getDay();
  const daysInMonth = new Date(year, month + 1, 0).getDate();
  const calendarDays = document.getElementById('calendar-days');
  if (!calendarDays) return;
  calendarDays.innerHTML = '';

  for (let i = 0; i < firstDay; i++) {
    const emptyCell = document.createElement('div');
    emptyCell.className = "h-14 sm:h-16 rounded-xl bg-zinc-950/20";
    calendarDays.appendChild(emptyCell);
  }

  const todayStr = new Date().toISOString().split('T')[0];

  for (let day = 1; day <= daysInMonth; day++) {
    const dayStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
    const dayEvents = getEventsForDate(dayStr);
    const hasEvent = dayEvents.length > 0;
    const isSelected = selectedDateStr === dayStr;
    const isToday = todayStr === dayStr;

    const cell = document.createElement('button');
    cell.type = 'button';
    cell.onclick = () => selectDate(dayStr);
    cell.className = `h-14 sm:h-16 p-1.5 rounded-xl flex flex-col justify-between items-start transition border ${
      isSelected 
        ? 'border-amber-500 bg-amber-950/40 text-amber-100 font-bold ring-1 ring-amber-500' 
        : hasEvent 
          ? 'border-zinc-700 bg-zinc-900/90 text-zinc-200 hover:border-zinc-500' 
          : 'border-zinc-800/60 bg-zinc-950/40 text-zinc-400 hover:bg-zinc-900/40'
    } ${isToday && !isSelected ? 'ring-1 ring-amber-500/40' : ''}`;

    cell.innerHTML = `
      <span class="text-xs ${isToday ? 'text-amber-400 font-bold' : ''}">${day}</span>
      ${hasEvent ? `
        <div class="w-full flex items-center gap-1 overflow-hidden">
          <span class="w-1.5 h-1.5 rounded-full ${isEventRecurring(dayEvents[0]) ? 'bg-amber-400' : 'bg-blue-400'} shrink-0"></span>
          <span class="text-[10px] truncate text-zinc-300 font-medium text-left">${typeof escapeHTML === 'function' ? escapeHTML(dayEvents[0].title) : dayEvents[0].title}</span>
        </div>
      ` : ''}
    `;
    calendarDays.appendChild(cell);
  }
}

function selectDate(dateStr) {
  if (selectedDateStr === dateStr) {
    selectedDateStr = null;
    const title = document.getElementById('agenda-title');
    if (title) title.textContent = "Upcoming Tournaments";
  } else {
    selectedDateStr = dateStr;
    const title = document.getElementById('agenda-title');
    if (title) title.textContent = `Events for ${dateStr}`;
  }
  currentEventPage = 1;
  renderCalendar();
  renderEvents();
}

function changeEventPage(direction) {
  currentEventPage += direction;
  renderEvents();
}

function renderEvents() {
  const listContainer = document.getElementById('events-list');
  const paginationContainer = document.getElementById('events-pagination');
  const prevBtn = document.getElementById('page-prev-btn');
  const nextBtn = document.getElementById('page-next-btn');
  const pageIndicator = document.getElementById('page-indicator');
  if (!listContainer) return;
  
  listContainer.innerHTML = '';
  let fullList = [];

  if (selectedDateStr) {
    fullList = getEventsForDate(selectedDateStr).map(e => ({ ...e, instanceDate: selectedDateStr }));
  } else {
    const today = new Date();
    const startStr = today.toISOString().split('T')[0];
    const futureDate = new Date();
    futureDate.setDate(today.getDate() + 90);
    const endStr = futureDate.toISOString().split('T')[0];
    fullList = getEventsForRange(startStr, endStr);
  }

  const badge = document.getElementById('event-count-badge');
  if (badge) badge.textContent = `${fullList.length} scheduled`;

  if (fullList.length === 0) {
    if (paginationContainer) paginationContainer.classList.add('hidden');
    listContainer.innerHTML = `
      <div class="p-6 rounded-2xl border border-dashed border-zinc-800 text-center text-zinc-500 text-xs">
        No events scheduled for this selection.
      </div>
    `;
    return;
  }

  const totalPages = Math.ceil(fullList.length / EVENTS_PER_PAGE);
  if (currentEventPage > totalPages) currentEventPage = totalPages;
  if (currentEventPage < 1) currentEventPage = 1;

  const startIndex = (currentEventPage - 1) * EVENTS_PER_PAGE;
  const visibleList = fullList.slice(startIndex, startIndex + EVENTS_PER_PAGE);

  visibleList.forEach(item => {
    const card = document.createElement('div');
    card.className = "card-frame p-4 rounded-xl space-y-3 relative group";

    const destinationQuery = encodeURIComponent(item.address ? `${item.location}, ${item.address}` : `${item.location}, California`);
    const mapsUrl = `https://www.google.com/maps/dir/?api=1&destination=${destinationQuery}`;
    const activeDate = item.instanceDate || item.date;
    const safeTitle = typeof escapeHTML === 'function' ? escapeHTML(item.title) : item.title;
    const safeRegion = typeof escapeHTML === 'function' ? escapeHTML(item.region || 'SoCal') : (item.region || 'SoCal');
    const safeLoc = typeof escapeHTML === 'function' ? escapeHTML(item.location) : item.location;
    const safeAddr = item.address ? (typeof escapeHTML === 'function' ? escapeHTML(item.address) : item.address) : '';
    const safeDesc = item.desc ? (typeof escapeHTML === 'function' ? escapeHTML(item.desc) : item.desc) : '';

    const freqLabel = item.recurFreq === 'daily' ? 'Daily' : 
                      item.recurFreq === 'every-other-day' ? 'Every Other Day' : 
                      (item.recurFreq === 'biweekly' || item.recurFreq === 'every-other-week') ? 'Biweekly' : 
                      item.recurFreq === 'weekly' ? 'Weekly' : 'Monthly';

    card.innerHTML = `
      <div class="flex items-start justify-between gap-2">
        <div>
          <div class="flex items-center gap-1.5 flex-wrap">
            <span class="text-[10px] font-bold uppercase tracking-wider text-amber-400 px-2 py-0.5 rounded bg-amber-950/60 border border-amber-600/30">
              ${safeRegion}
            </span>
            ${isEventRecurring(item) ? `
              <span class="text-[10px] font-semibold text-zinc-400 px-1.5 py-0.5 rounded bg-zinc-800 border border-zinc-700">
                ↻ ${freqLabel}
              </span>
            ` : ''}
          </div>
          <h4 class="text-base font-bold text-white mt-1.5">${safeTitle}</h4>
        </div>
        <button onclick="openHubAdminModal('events', 'edit', '${item.id}', '${activeDate}')" class="text-xs text-zinc-500 hover:text-amber-400 font-mono transition p-1" title="Edit Event">
          ✏️
        </button>
      </div>

      <div class="text-xs text-zinc-300 space-y-1.5">
        <div class="flex items-center gap-2 text-zinc-400">
          <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
          <span>${activeDate} • ${item.time || ''}</span>
        </div>
        
        <div class="flex items-start gap-2 text-zinc-400">
          <svg class="w-3.5 h-3.5 mt-0.5 shrink-0 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
          <div>
            <span class="text-zinc-200 font-medium">${safeLoc}</span>
            ${safeAddr ? `<div class="text-[11px] text-zinc-400">${safeAddr}</div>` : ''}
          </div>
        </div>

        ${safeDesc ? `<p class="pt-2 text-zinc-400 text-xs border-t border-zinc-800/80">${safeDesc}</p>` : ''}
      </div>

      <div class="pt-1 flex items-center justify-between">
        <a href="${mapsUrl}" target="_blank" rel="noopener noreferrer" 
           class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-lg bg-zinc-900 hover:bg-zinc-800 border border-zinc-700/80 text-amber-400 hover:text-amber-300 transition">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
          Directions
        </a>
      </div>
    `;
    listContainer.appendChild(card);
  });

  if (totalPages > 1 && paginationContainer) {
    paginationContainer.classList.remove('hidden');
    pageIndicator.textContent = `Page ${currentEventPage} of ${totalPages}`;
    prevBtn.disabled = currentEventPage === 1;
    nextBtn.disabled = currentEventPage === totalPages;
  } else if (paginationContainer) {
    paginationContainer.classList.add('hidden');
  }
}

function prevMonth() {
  currentDate.setMonth(currentDate.getMonth() - 1);
  renderCalendar();
}

function nextMonth() {
  currentDate.setMonth(currentDate.getMonth() + 1);
  renderCalendar();
}

window.addEventListener('DOMContentLoaded', renderCalendar);