// ==========================================================================
// PARTNER STORES DIRECTORY & MAPS CONTROLLER
// ==========================================================================
let stores = [];
let activeStoreRegion = 'ALL';
let storeSearchQuery = '';
const STORES_PER_PAGE = 6;
let currentStorePage = 1;

function setStoreRegionFilter(region) {
  activeStoreRegion = region;
  currentStorePage = 1;
  const buttons = document.querySelectorAll('.store-filter-btn');
  buttons.forEach(btn => {
    const match = (region === 'ALL' && btn.textContent.includes('All')) || btn.textContent === region;
    if (match) {
      btn.className = "store-filter-btn px-3 py-1.5 rounded-lg text-xs font-bold border border-amber-500 bg-amber-500/20 text-amber-300";
    } else {
      btn.className = "store-filter-btn px-3 py-1.5 rounded-lg text-xs font-semibold border border-zinc-800 bg-zinc-900 text-zinc-400 hover:text-zinc-200";
    }
  });
  renderStores();
}

function onStoreSearch(query) {
  storeSearchQuery = query.toLowerCase().trim();
  currentStorePage = 1;
  renderStores();
}

function changeStorePage(direction) {
  currentStorePage += direction;
  renderStores();
}

function renderStores() {
  const grid = document.getElementById('stores-grid');
  const paginationContainer = document.getElementById('stores-pagination');
  const prevBtn = document.getElementById('store-page-prev-btn');
  const nextBtn = document.getElementById('store-page-next-btn');
  const pageIndicator = document.getElementById('store-page-indicator');
  if (!grid) return;
  
  grid.innerHTML = '';

  let filtered = stores.filter(store => {
    const storeRegion = store.region || 'Los Angeles';
    const matchesRegion = (activeStoreRegion === 'ALL') || storeRegion === activeStoreRegion;
    const matchesQuery = !storeSearchQuery || 
      (store.name && store.name.toLowerCase().includes(storeSearchQuery)) || 
      (store.city && store.city.toLowerCase().includes(storeSearchQuery)) ||
      (store.address && store.address.toLowerCase().includes(storeSearchQuery));
    return matchesRegion && matchesQuery;
  });

  if (filtered.length === 0) {
    if (paginationContainer) paginationContainer.classList.add('hidden');
    grid.innerHTML = `
      <div class="col-span-full p-8 text-center text-xs text-zinc-500 border border-dashed border-zinc-800 rounded-2xl">
        No partner stores match the selected region or search query.
      </div>
    `;
    return;
  }

  const totalPages = Math.ceil(filtered.length / STORES_PER_PAGE);
  if (currentStorePage > totalPages) currentStorePage = totalPages;
  if (currentStorePage < 1) currentStorePage = 1;

  const startIndex = (currentStorePage - 1) * STORES_PER_PAGE;
  const visibleStores = filtered.slice(startIndex, startIndex + STORES_PER_PAGE);

  visibleStores.forEach(store => {
    const card = document.createElement('div');
    card.className = "card-frame p-5 rounded-2xl flex flex-col justify-between space-y-3 relative group";
    
    const destinationQuery = encodeURIComponent(`${store.name}, ${store.address}`);
    const directionsUrl = `https://www.google.com/maps/dir/?api=1&destination=${destinationQuery}`;

    card.innerHTML = `
      <div class="space-y-2">
        <div class="flex items-start justify-between gap-2">
          <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded ${
            store.region === 'Los Angeles' ? 'bg-amber-950/60 border border-amber-600/40 text-amber-300' :
            store.region === 'Orange County' ? 'bg-blue-950/60 border border-blue-600/40 text-blue-300' :
            store.region === 'Inland Empire' ? 'bg-emerald-950/60 border border-emerald-600/40 text-emerald-300' :
            'bg-purple-950/60 border border-purple-600/40 text-purple-300'
          }">
            ${escapeHTML(store.region || 'Partner')}
          </span>
          <button onclick="openHubAdminModal('stores', 'edit', '${escapeHTML(store.id)}')" class="text-xs text-zinc-500 hover:text-amber-400 font-mono transition p-1" title="Edit Store">
            ✏️
          </button>
        </div>

        <div>
          <h4 class="text-base font-bold text-white group-hover:text-amber-300 transition-colors">${escapeHTML(store.name)}</h4>
          <p class="text-xs text-zinc-400">${escapeHTML(store.city)}, California</p>
        </div>

        <p class="text-[11px] text-zinc-400 leading-relaxed pt-1 border-t border-zinc-800/80">${escapeHTML(store.address)}</p>
        ${store.phone ? `<p class="text-[11px] text-zinc-500 font-mono">${escapeHTML(store.phone)}</p>` : ''}
        ${store.notes ? `<p class="text-[11px] text-amber-400/90 italic">${escapeHTML(store.notes)}</p>` : ''}
      </div>

      <div class="pt-3 border-t border-zinc-800 flex items-center justify-between gap-2 text-xs">
        <a href="${directionsUrl}" target="_blank" rel="noopener noreferrer" 
           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-zinc-900 hover:bg-zinc-800 border border-zinc-700 text-amber-400 hover:text-amber-300 font-semibold transition">
          <svg class="w-3.5 h-3.5 fill-none stroke-currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
          Directions
        </a>
        ${store.website ? `
          <a href="${escapeHTML(store.website)}" target="_blank" rel="noopener noreferrer" class="text-[11px] text-zinc-400 hover:text-white underline">
            Website &rarr;
          </a>
        ` : ''}
      </div>
    `;
    grid.appendChild(card);
  });

  if (totalPages > 1 && paginationContainer) {
    paginationContainer.classList.remove('hidden');
    pageIndicator.textContent = `Page ${currentStorePage} of ${totalPages}`;
    prevBtn.disabled = currentStorePage === 1;
    nextBtn.disabled = currentStorePage === totalPages;
  } else if (paginationContainer) {
    paginationContainer.classList.add('hidden');
  }
}

function handleLiveMapSearch(e) {
  e.preventDefault();
  const input = document.getElementById('gmap-search-input');
  const query = input.value.trim();
  const frame = document.getElementById('gmap-live-frame');
  if (!query) {
    frame.src = "https://maps.google.com/maps?q=Magic+the+Gathering+stores+in+Southern+California&t=&z=10&ie=UTF8&iwloc=&output=embed";
    return;
  }
  frame.src = `https://maps.google.com/maps?q=${encodeURIComponent(`Magic the Gathering stores near ${query}`)}&t=&z=12&ie=UTF8&iwloc=&output=embed`;
}