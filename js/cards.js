// ==========================================================================
// SCRYFALL AUTOCOMPLETE, PRINTINGS, TCGPLAYER & BANNED LIST
// ==========================================================================
let searchDebounceTimeout = null;
let cardPrintingsList = [];
let currentPrintingIndex = 0;
let activeFinish = 'nonfoil';
let dynamicBannedCards = [];
let isBannedListFetched = false;

const BANNED_REASONS = {
  "All That Glitters": "Affinity fast-kill equipment scaling.",
  "Arcum's Astrolabe": "Pervasive 5-color mana fixing and cantrip loops.",
  "Atog": "Fling and affinity artifact sac combo lethality.",
  "Basking Broodscale": "Infinite combo token engine banned early 2025.",
  "Bonder's Ornament": "Monopolized midrange card draw loops.",
  "Chatterstorm": "Turn 2-3 storm token storm-offs.",
  "Cloud of Faeries": "Free spell flicker and mana untap loops.",
  "Cloudpost": "Exponential big-mana lockouts.",
  "Cranial Plating": "Lethal artifact equipment velocity.",
  "Cranial Ram": "Redundant affinity equipment pre-banned with MH3.",
  "Daze": "Free counter protection for blue tempo.",
  "Deadly Dispute": "Excessive black artifact sacrifice value and card draw.",
  "Disciple of the Vault": "Turned artifact sacrifices into unpreventable burn.",
  "Empty the Warrens": "Early lethal goblin token swarm.",
  "Fall from Favor": "Monarch locking creature removal.",
  "Frantic Search": "Mana-positive graveyard sculpting.",
  "Galvanic Relay": "Multi-turn delayed storm refill.",
  "Gitaxian Probe": "Free hand information and card cycling.",
  "Grapeshot": "Direct spell damage storm kill.",
  "Gush": "Free card advantage and landfall fuel.",
  "High Tide": "Infinite blue mana generation.",
  "Hymn to Tourach": "Unconditional turn-2 random discard.",
  "Invigorate": "Turn-2 infect lethality with free pump.",
  "Kuldotha Rebirth": "Fast artifact sacrifice swarm lethality.",
  "Monastery Swiftspear": "Over-concentrated red prowess meta dominance.",
  "Mystic Sanctuary": "Recurring spell lockouts with flicker spells.",
  "Peregrine Drake": "Infinite mana and ETB flicker combo.",
  "Prophetic Prism": "Colorless cantrip fixing in Tron/Affinity.",
  "Sinkhole": "Turn-2 stone rain land denial.",
  "Sojourner's Companion": "Redundant free 4/4 affinity threat.",
  "Temporal Fissure": "Turn-3 land and board wipe storm.",
  "Treasure Cruise": "Ancestral recall off graveyard delve."
};

function initCardExplorer() {
  const searchInput = document.getElementById('card-search-input');
  const autocompleteList = document.getElementById('autocomplete-list');
  const clearBtn = document.getElementById('card-search-clear');

  if (searchInput) {
    searchInput.addEventListener('input', (e) => {
      const query = e.target.value.trim();
      clearTimeout(searchDebounceTimeout);
      if (query.length < 2) {
        if (autocompleteList) autocompleteList.classList.add('hidden');
        if (clearBtn) clearBtn.classList.add('hidden');
        return;
      }
      if (clearBtn) clearBtn.classList.remove('hidden');
      searchDebounceTimeout = setTimeout(() => { fetchCardSuggestions(query); }, 250);
    });

    searchInput.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') {
        e.preventDefault();
        if (autocompleteList) autocompleteList.classList.add('hidden');
        const query = searchInput.value.trim();
        if (query) fetchCardAllPrintings(query);
      }
    });

    document.addEventListener('click', (e) => {
      if (!searchInput.contains(e.target) && autocompleteList && !autocompleteList.contains(e.target)) {
        autocompleteList.classList.add('hidden');
      }
    });
  }
}

async function fetchCardSuggestions(query) {
  try {
    const res = await fetch(`https://api.scryfall.com/cards/autocomplete?q=${encodeURIComponent(query)}`);
    const data = await res.json();
    const autocompleteList = document.getElementById('autocomplete-list');
    if (data && data.data && data.data.length > 0 && autocompleteList) {
      autocompleteList.innerHTML = '';
      data.data.forEach(cardName => {
        const item = document.createElement('div');
        item.className = "px-4 py-2.5 text-xs text-zinc-300 hover:bg-zinc-800 hover:text-amber-300 cursor-pointer border-b border-zinc-800/50 last:border-b-0 transition flex items-center justify-between";
        item.textContent = cardName;
        item.onclick = () => {
          document.getElementById('card-search-input').value = cardName;
          autocompleteList.classList.add('hidden');
          fetchCardAllPrintings(cardName);
        };
        autocompleteList.appendChild(item);
      });
      autocompleteList.classList.remove('hidden');
    }
  } catch (err) {}
}

async function fetchCardAllPrintings(cardName) {
  const statusBox = document.getElementById('card-search-status');
  const resultBox = document.getElementById('card-result-box');
  if (statusBox) {
    statusBox.textContent = `Loading versions, market pricing & rules for "${cardName}"...`;
    statusBox.classList.remove('hidden');
  }
  if (resultBox) resultBox.classList.add('hidden');

  try {
    const res = await fetch(`https://api.scryfall.com/cards/named?exact=${encodeURIComponent(cardName)}`);
    if (!res.ok) {
      if (statusBox) statusBox.textContent = `Card "${cardName}" not found. Check spelling.`;
      return;
    }
    const baseCard = await res.json();

    if (baseCard.prints_search_uri) {
      const printsRes = await fetch(baseCard.prints_search_uri);
      if (printsRes.ok) {
        const printsData = await printsRes.json();
        cardPrintingsList = printsData.data || [baseCard];
      } else {
        cardPrintingsList = [baseCard];
      }
    } else {
      cardPrintingsList = [baseCard];
    }

    cardPrintingsList.sort((a, b) => (b.released_at || '').localeCompare(a.released_at || ''));
    currentPrintingIndex = 0;
    if (statusBox) statusBox.classList.add('hidden');

    populateVersionDropdown();
    displayActivePrinting();
    fetchAndRenderRulings(baseCard.rulings_uri);
  } catch (err) {
    if (statusBox) statusBox.textContent = "Error loading card data from Scryfall.";
  }
}

async function fetchAndRenderRulings(rulingsUri) {
  const container = document.getElementById('card-rulings-container');
  const badge = document.getElementById('rulings-count-badge');
  if (!container) return;
  container.innerHTML = '<div class="text-xs text-zinc-500 italic">Checking official rulings...</div>';

  if (!rulingsUri) {
    container.innerHTML = '<div class="text-xs text-zinc-500 italic">No specific Gatherer rulings published for this card. Standard MTG Comprehensive Rules apply.</div>';
    if (badge) badge.textContent = '0 rulings';
    return;
  }

  try {
    const res = await fetch(rulingsUri);
    const data = await res.json();

    if (data && data.data && data.data.length > 0) {
      if (badge) badge.textContent = `${data.data.length} ruling${data.data.length === 1 ? '' : 's'}`;
      container.innerHTML = '';
      data.data.forEach(r => {
        const row = document.createElement('div');
        row.className = "p-3 rounded-lg bg-zinc-950/70 border border-zinc-800/80 text-xs text-zinc-300 space-y-1";
        row.innerHTML = `
          <div class="flex items-center justify-between text-[10px] text-zinc-500 font-mono">
            <span>Oracle Note</span>
            <span>${escapeHTML(r.published_at)}</span>
          </div>
          <p class="leading-relaxed">${escapeHTML(r.comment)}</p>
        `;
        container.appendChild(row);
      });
    } else {
      if (badge) badge.textContent = '0 rulings';
      container.innerHTML = '<div class="text-xs text-zinc-500 italic">No specific Gatherer rulings published for this card. Standard MTG Comprehensive Rules apply.</div>';
    }
  } catch (err) {
    container.innerHTML = '<div class="text-xs text-zinc-500 italic">Standard MTG Comprehensive Rules apply.</div>';
    if (badge) badge.textContent = '0 rulings';
  }
}

function populateVersionDropdown() {
  const versionSelect = document.getElementById('card-version-select');
  if (!versionSelect) return;
  versionSelect.innerHTML = '';
  cardPrintingsList.forEach((card, index) => {
    const option = document.createElement('option');
    option.value = index;
    const releaseYear = card.released_at ? card.released_at.slice(0, 4) : '';
    const traits = [];
    if (card.border_color === 'borderless') traits.push('Borderless');
    if (card.full_art) traits.push('Full Art');
    if (card.frame_effects && card.frame_effects.includes('showcase')) traits.push('Showcase');
    if (card.frame_effects && card.frame_effects.includes('extendedart')) traits.push('Extended Art');
    if (card.frame === '1993' || card.frame === '1997') traits.push('Retro Frame');
    if (card.promo) traits.push('Promo');

    const traitLabel = traits.length > 0 ? ` [${traits.join(', ')}]` : '';
    option.textContent = `${releaseYear ? releaseYear + ' ' : ''}${card.set_name} (#${card.collector_number})${traitLabel}`;
    versionSelect.appendChild(option);
  });
  versionSelect.value = "0";
}

function onPrintingSelected(indexStr) {
  currentPrintingIndex = parseInt(indexStr, 10) || 0;
  displayActivePrinting();
}

function cyclePrinting(direction) {
  currentPrintingIndex += direction;
  if (currentPrintingIndex < 0) currentPrintingIndex = cardPrintingsList.length - 1;
  if (currentPrintingIndex >= cardPrintingsList.length) currentPrintingIndex = 0;
  const versionSelect = document.getElementById('card-version-select');
  if (versionSelect) versionSelect.value = currentPrintingIndex.toString();
  displayActivePrinting();
}

function setFinish(finishType) {
  activeFinish = finishType;
  updateFinishUI();
}

function displayActivePrinting() {
  const card = cardPrintingsList[currentPrintingIndex];
  if (!card) return;

  let imgUri = "https://placehold.co/250x350/161922/amber?text=No+Image";
  if (card.image_uris && card.image_uris.normal) {
    imgUri = card.image_uris.normal;
  } else if (card.card_faces && card.card_faces[0].image_uris) {
    imgUri = card.card_faces[0].image_uris.normal;
  }

  document.getElementById('card-display-img').src = imgUri;
  document.getElementById('card-display-name').textContent = card.name;
  document.getElementById('card-display-type').textContent = card.type_line;
  document.getElementById('card-display-collector').textContent = `${card.set.toUpperCase()} • #${card.collector_number}`;
  document.getElementById('card-display-oracle').textContent = card.oracle_text || "(No oracle text on card face)";

  const legalityBadge = document.getElementById('card-display-legality');
  const pauperStatus = card.legalities ? card.legalities.pauper : 'not_legal';
  if (pauperStatus === 'legal') {
    legalityBadge.textContent = "PAUPER LEGAL";
    legalityBadge.className = "text-[11px] font-extrabold px-2.5 py-1 rounded bg-emerald-950/80 text-emerald-400 border border-emerald-600/40";
  } else if (pauperStatus === 'banned') {
    legalityBadge.textContent = "BANNED IN PAUPER";
    legalityBadge.className = "text-[11px] font-extrabold px-2.5 py-1 rounded bg-red-950/80 text-red-400 border border-red-600/40";
  } else {
    legalityBadge.textContent = "NOT PAUPER LEGAL";
    legalityBadge.className = "text-[11px] font-extrabold px-2.5 py-1 rounded bg-zinc-900 text-zinc-400 border border-zinc-700";
  }

  const badgesContainer = document.getElementById('card-treatments-badges');
  badgesContainer.innerHTML = '';
  const addBadge = (label, colorClasses) => {
    const badge = document.createElement('span');
    badge.className = `text-[10px] font-bold px-2 py-0.5 rounded border uppercase ${colorClasses}`;
    badge.textContent = label;
    badgesContainer.appendChild(badge);
  };

  if (card.full_art) addBadge('Full Art', 'bg-purple-950/60 border-purple-600/40 text-purple-300');
  if (card.border_color === 'borderless') addBadge('Borderless', 'bg-cyan-950/60 border-cyan-600/40 text-cyan-300');
  if (card.frame_effects && card.frame_effects.includes('showcase')) addBadge('Showcase', 'bg-amber-950/60 border-amber-600/40 text-amber-300');
  if (card.frame_effects && card.frame_effects.includes('extendedart')) addBadge('Extended Art', 'bg-blue-950/60 border-blue-600/40 text-blue-300');
  if (card.frame === '1993' || card.frame === '1997') addBadge('Retro Frame', 'bg-amber-900/40 border-amber-500/40 text-amber-200');
  if (card.promo) addBadge('Promo', 'bg-yellow-950/60 border-yellow-600/40 text-yellow-300');

  const finishes = card.finishes || ['nonfoil', 'foil'];
  const hasNonFoil = finishes.includes('nonfoil');
  const hasFoil = finishes.includes('foil') || finishes.includes('etched');

  if (!hasNonFoil && hasFoil) activeFinish = 'foil';
  else if (hasNonFoil && !hasFoil) activeFinish = 'nonfoil';

  updateFinishUI();
  document.getElementById('card-result-box').classList.remove('hidden');
}

function updateFinishUI() {
  const card = cardPrintingsList[currentPrintingIndex];
  if (!card) return;

  const btnNonFoil = document.getElementById('btn-finish-nonfoil');
  const btnFoil = document.getElementById('btn-finish-foil');
  const sheenOverlay = document.getElementById('foil-sheen-overlay');
  const priceDisplay = document.getElementById('active-market-price');
  const finishLabel = document.getElementById('active-finish-label');
  const tcgLink = document.getElementById('card-tcg-link');

  const finishes = card.finishes || ['nonfoil', 'foil'];
  const hasNonFoil = finishes.includes('nonfoil');
  const hasFoil = finishes.includes('foil') || finishes.includes('etched');

  btnNonFoil.disabled = !hasNonFoil;
  btnFoil.disabled = !hasFoil;
  btnNonFoil.classList.toggle('opacity-30', !hasNonFoil);
  btnFoil.classList.toggle('opacity-30', !hasFoil);

  if (activeFinish === 'foil' && hasFoil) {
    btnFoil.className = "px-3 py-1.5 rounded-lg border border-amber-500 bg-amber-500/20 text-amber-300 text-xs font-bold shadow-sm";
    btnNonFoil.className = "px-3 py-1.5 rounded-lg border border-zinc-700 bg-zinc-900 text-zinc-400 text-xs font-semibold hover:text-zinc-200";
    sheenOverlay.classList.remove('hidden');

    const foilPrice = card.prices && (card.prices.usd_foil || card.prices.usd_etched);
    priceDisplay.textContent = foilPrice ? `$${foilPrice}` : 'N/A';
    finishLabel.textContent = card.prices && card.prices.usd_etched ? 'Foil Etched' : 'Foil Market';
  } else {
    btnNonFoil.className = "px-3 py-1.5 rounded-lg border border-amber-500 bg-amber-500/20 text-amber-300 text-xs font-bold shadow-sm";
    btnFoil.className = "px-3 py-1.5 rounded-lg border border-zinc-700 bg-zinc-900 text-zinc-400 text-xs font-semibold hover:text-zinc-200";
    sheenOverlay.classList.add('hidden');

    const regPrice = card.prices && card.prices.usd;
    priceDisplay.textContent = regPrice ? `$${regPrice}` : 'N/A';
    finishLabel.textContent = 'Regular Market';
  }

  if (card.purchase_uris && card.purchase_uris.tcgplayer) {
    tcgLink.href = card.purchase_uris.tcgplayer;
  } else {
    tcgLink.href = `https://www.tcgplayer.com/search/magic/product?q=${encodeURIComponent(card.name)}`;
  }
}

function clearCardSearch() {
  document.getElementById('card-search-input').value = '';
  document.getElementById('card-search-clear').classList.add('hidden');
  document.getElementById('autocomplete-list').classList.add('hidden');
  document.getElementById('card-result-box').classList.add('hidden');
  document.getElementById('card-search-status').classList.add('hidden');
  cardPrintingsList = [];
}

async function openBannedModal() {
  document.getElementById('banned-modal').classList.remove('hidden');
  if (!isBannedListFetched) await fetchLiveBannedList();
  else renderBannedList(dynamicBannedCards);
}

function closeBannedModal() {
  document.getElementById('banned-modal').classList.add('hidden');
}

async function fetchLiveBannedList() {
  const container = document.getElementById('banned-cards-container');
  container.innerHTML = `
    <div class="py-16 text-center text-xs text-zinc-400 animate-pulse space-y-2">
      <svg class="w-6 h-6 animate-spin text-amber-500 mx-auto" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
      </svg>
      <p>Syncing live banned list from Scryfall / WotC...</p>
    </div>
  `;

  try {
    let cards = [];
    let nextUrl = 'https://api.scryfall.com/cards/search?q=banned:pauper&order=name&unique=cards';

    while (nextUrl && cards.length < 300) {
      const res = await fetch(nextUrl);
      const data = await res.json();
      if (data && data.data) cards = cards.concat(data.data);
      nextUrl = (data.has_more && data.next_page) ? data.next_page : null;
    }

    dynamicBannedCards = cards.map(c => {
      const isStickerOrAttraction = c.type_line.includes('Sticker') || c.type_line.includes('Attraction') || (c.oracle_text && c.oracle_text.includes('sticker'));
      return {
        name: c.name,
        type: c.type_line,
        category: isStickerOrAttraction ? 'Stickers & Attractions' : (c.type_line.split('—')[0] || 'Card').trim(),
        note: BANNED_REASONS[c.name] || (isStickerOrAttraction ? 'Banned across Eternal formats May 13, 2024.' : (c.oracle_text ? c.oracle_text.slice(0, 100) + '...' : 'Official format ban.'))
      };
    });

    isBannedListFetched = true;
    renderBannedList(dynamicBannedCards);
  } catch (err) {
    container.innerHTML = `
      <div class="p-6 text-center text-xs text-red-400 space-y-2">
        <p>Failed to sync live data from Scryfall.</p>
        <button onclick="fetchLiveBannedList()" class="px-3 py-1 bg-zinc-800 border border-zinc-700 rounded text-zinc-200 hover:text-white">Retry Sync</button>
      </div>
    `;
  }
}

function renderBannedList(cards) {
  const container = document.getElementById('banned-cards-container');
  container.innerHTML = '';
  if (cards.length === 0) {
    container.innerHTML = `<div class="p-8 text-center text-xs text-zinc-500">No banned cards match your query.</div>`;
    return;
  }

  cards.forEach(card => {
    const row = document.createElement('div');
    row.className = "p-3 rounded-lg bg-zinc-900/90 border border-zinc-800/80 flex flex-col sm:flex-row sm:items-center justify-between gap-2 hover:border-zinc-700 transition";
    row.innerHTML = `
      <div class="space-y-0.5 flex-1 min-w-0">
        <div class="flex items-center gap-2 flex-wrap">
          <span class="text-sm font-bold text-white truncate">${escapeHTML(card.name)}</span>
          <span class="text-[10px] font-semibold text-red-400 bg-red-950/60 border border-red-700/40 px-2 py-0.5 rounded shrink-0">${escapeHTML(card.category)}</span>
        </div>
        <p class="text-[11px] text-zinc-400 leading-snug">${escapeHTML(card.note)}</p>
      </div>
      <button type="button" onclick="lookupBannedCard('${card.name.replace(/'/g, "\\'")}')" class="text-[11px] font-bold text-amber-400 hover:text-amber-300 self-start sm:self-auto whitespace-nowrap px-2.5 py-1 rounded bg-zinc-800 border border-zinc-700 hover:bg-zinc-700 transition shrink-0">
        Lookup &rarr;
      </button>
    `;
    container.appendChild(row);
  });
}

function filterBannedList(query) {
  const q = query.toLowerCase().trim();
  const filtered = dynamicBannedCards.filter(c => 
    c.name.toLowerCase().includes(q) || 
    c.type.toLowerCase().includes(q) || 
    c.category.toLowerCase().includes(q) || 
    c.note.toLowerCase().includes(q)
  );
  renderBannedList(filtered);
}

function lookupBannedCard(cardName) {
  closeBannedModal();
  const searchBox = document.getElementById('card-search-input');
  if (searchBox) {
    searchBox.value = cardName;
    searchBox.scrollIntoView({ behavior: 'smooth' });
    fetchCardAllPrintings(cardName);
  }
}