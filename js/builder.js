// js/builder.js
const BASIC_LANDS = new Set([
  'Plains', 'Island', 'Swamp', 'Mountain', 'Forest', 'Wastes',
  'Snow-Covered Plains', 'Snow-Covered Island', 'Snow-Covered Swamp',
  'Snow-Covered Mountain', 'Snow-Covered Forest', 'Snow-Covered Wastes'
]);

const UNLIMITED_COPIES = new Set([
  'Relentless Rats', 'Shadowborn Apostle', 'Persistent Petitioners',
  'Dragon\'s Approach', 'Slime Against Humanity', 'Templar Knight'
]);

window.Builder = {
  deck: {
    id: null,
    title: "Untitled Pauper Brew",
    main: [],
    side: []
  },
  activeTarget: 'main',
  selectedCardForPrints: null,
  searchDebounce: null,

  init() {
    this.bindEvents();
    this.render();
  },

  bindEvents() {
    const mainBtn = document.getElementById('target-main-btn');
    const sideBtn = document.getElementById('target-side-btn');

    if (mainBtn && sideBtn) {
      mainBtn.onclick = () => {
        this.activeTarget = 'main';
        mainBtn.className = 'px-3 py-1 text-xs font-bold rounded-md bg-sky-600 text-white transition';
        sideBtn.className = 'px-3 py-1 text-xs font-bold rounded-md text-slate-400 hover:text-white transition';
      };

      sideBtn.onclick = () => {
        this.activeTarget = 'side';
        sideBtn.className = 'px-3 py-1 text-xs font-bold rounded-md bg-emerald-600 text-white transition';
        mainBtn.className = 'px-3 py-1 text-xs font-bold rounded-md text-slate-400 hover:text-white transition';
      };
    }

    const titleInput = document.getElementById('deck-title-input');
    if (titleInput) {
      titleInput.oninput = (e) => {
        this.deck.title = e.target.value;
      };
    }

    // Import modal triggers
    const importBtn = document.getElementById('import-deck-btn');
    if (importBtn) importBtn.onclick = () => window.openImportModal();

    const importSubmitBtn = document.getElementById('import-submit-btn');
    if (importSubmitBtn) importSubmitBtn.onclick = () => this.handleImportDeck();

    const searchInput = document.getElementById('card-search-input');
    const suggestionsBox = document.getElementById('search-suggestions');

    if (searchInput && suggestionsBox) {
      searchInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
          e.preventDefault();
          const firstRow = suggestionsBox.querySelector('.suggestion-row');
          if (firstRow) {
            const cardName = firstRow.getAttribute('data-card');
            this.addCardByName(cardName);
          } else if (searchInput.value.trim().length >= 2) {
            this.addCardByName(searchInput.value.trim());
          }
        }
      });

      searchInput.addEventListener('input', (e) => {
        clearTimeout(this.searchDebounce);
        const val = e.target.value.trim();

        if (val.length < 2) {
          suggestionsBox.classList.remove('is-open');
          suggestionsBox.style.display = 'none';
          return;
        }

        const spinner = document.getElementById('search-spinner');
        if (spinner) spinner.style.display = 'block';

        this.searchDebounce = setTimeout(async () => {
          try {
            const endpoint = `https://api.scryfall.com/cards/autocomplete?q=${encodeURIComponent(val)}`;
            const res = await fetch(endpoint);
            if (!res.ok) return;
            const data = await res.json();
            this.renderSuggestions(data.data || []);
          } catch (err) {
            console.error("Autocomplete error:", err);
          } finally {
            if (spinner) spinner.style.display = 'none';
          }
        }, 160);
      });

      document.addEventListener('click', (e) => {
        if (!searchInput.contains(e.target) && !suggestionsBox.contains(e.target)) {
          suggestionsBox.classList.remove('is-open');
          suggestionsBox.style.display = 'none';
        }
      });
    }

    const saveBtn = document.getElementById('save-deck-btn');
    if (saveBtn) saveBtn.onclick = () => this.saveDeck();

    const handBtn = document.getElementById('sample-hand-btn');
    if (handBtn) handBtn.onclick = () => this.openSampleHand();

    const mulliBtn = document.getElementById('mulligan-btn');
    if (mulliBtn) mulliBtn.onclick = () => this.openSampleHand();
  },

  renderSuggestions(names) {
    const box = document.getElementById('search-suggestions');
    if (!box) return;

    if (!names.length) {
      box.innerHTML = `<div class="p-3 text-xs text-slate-400 italic">No matching cards found.</div>`;
      box.classList.add('is-open');
      box.style.display = 'block';
      return;
    }

    box.innerHTML = names.map(name => {
      const cleanName = name.replace(/"/g, '&quot;');
      return `
        <div class="suggestion-row" data-card="${cleanName}">
          <span class="text-sm font-semibold text-white pointer-events-none">${name}</span>
          <span class="text-xs text-sky-400 font-bold pointer-events-none">+ Add to ${this.activeTarget.toUpperCase()}</span>
        </div>
      `;
    }).join('');

    box.querySelectorAll('.suggestion-row').forEach(row => {
      row.onmousedown = (e) => {
        e.preventDefault();
        const targetCard = row.getAttribute('data-card');
        this.addCardByName(targetCard);
      };
    });

    box.classList.add('is-open');
    box.style.display = 'block';
  },

  async addCardByName(cardName) {
    const box = document.getElementById('search-suggestions');
    const input = document.getElementById('card-search-input');
    if (box) {
      box.classList.remove('is-open');
      box.style.display = 'none';
    }
    if (input) input.value = '';

    const spinner = document.getElementById('search-spinner');
    if (spinner) spinner.style.display = 'block';

    try {
      const res = await fetch(`https://api.scryfall.com/cards/named?exact=${encodeURIComponent(cardName)}`);
      if (!res.ok) throw new Error(`Lookup failed (${res.status})`);
      const data = await res.json();
      this.insertCardIntoDeck(data, this.activeTarget, 1);
      this.render();
    } catch (err) {
      console.error(err);
      alert(`Could not fetch data for "${cardName}".`);
    } finally {
      if (spinner) spinner.style.display = 'none';
    }
  },

  insertCardIntoDeck(scryfallCard, target, qty = 1) {
    const image = scryfallCard.image_uris?.normal 
      || scryfallCard.card_faces?.[0]?.image_uris?.normal 
      || '';
    const prices = scryfallCard.prices || {};

    const card = {
      id: scryfallCard.id,
      oracle_id: scryfallCard.oracle_id,
      name: scryfallCard.name,
      mana_cost: scryfallCard.mana_cost || (scryfallCard.card_faces ? scryfallCard.card_faces[0].mana_cost : ''),
      cmc: Math.floor(scryfallCard.cmc || 0),
      type_line: scryfallCard.type_line || '',
      pauper_legal: scryfallCard.legalities ? scryfallCard.legalities.pauper === 'legal' : false,
      image: image,
      price_usd: parseFloat(prices.usd || 0),
      price_eur: parseFloat(prices.eur || 0),
      qty: qty
    };

    const targetList = target === 'main' ? this.deck.main : this.deck.side;
    const existing = targetList.find(c => c.name === card.name);

    if (existing) {
      existing.qty += qty;
    } else {
      targetList.push(card);
    }
  },

  // --- Universal Deck List Parser (Moxfield, MTGGoldfish, Arena, MTGO, Archidekt) ---
  async handleImportDeck() {
    const textarea = document.getElementById('import-textarea');
    const text = textarea ? textarea.value.trim() : '';
    if (!text) {
      alert("Please paste a deck list into the box.");
      return;
    }

    const replaceCurrent = document.getElementById('import-replace-checkbox').checked;
    const submitBtn = document.getElementById('import-submit-btn');
    submitBtn.innerHTML = `<i class="fa-solid fa-circle-notch fa-spin"></i> Parsing cards...`;
    submitBtn.disabled = true;

    try {
      const parsedEntries = this.parseRawDeckText(text);

      if (!parsedEntries.main.length && !parsedEntries.side.length) {
        alert("No valid card lines found. Ensure quantities and card names are present.");
        return;
      }

      if (replaceCurrent) {
        this.deck.main = [];
        this.deck.side = [];
      }

      // Collect all unique names to fetch from Scryfall Collection endpoint
      const allEntries = [...parsedEntries.main, ...parsedEntries.side];
      const uniqueNames = [...new Set(allEntries.map(e => e.name))];

      // Scryfall allows up to 75 identifiers per batch request
      const batches = [];
      for (let i = 0; i < uniqueNames.length; i += 75) {
        batches.push(uniqueNames.slice(i, i + 75));
      }

      const cardLookupMap = new Map();

      for (const batch of batches) {
        const identifiers = batch.map(name => ({ name: name }));
        const res = await fetch('https://api.scryfall.com/cards/collection', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ identifiers })
        });

        if (!res.ok) throw new Error("Scryfall bulk lookup failed.");
        const data = await res.json();

        (data.data || []).forEach(card => {
          cardLookupMap.set(card.name.toLowerCase(), card);
        });
      }

      // Insert parsed cards into deck
      parsedEntries.main.forEach(entry => {
        const scryfallCard = cardLookupMap.get(entry.name.toLowerCase());
        if (scryfallCard) {
          this.insertCardIntoDeck(scryfallCard, 'main', entry.qty);
        }
      });

      parsedEntries.side.forEach(entry => {
        const scryfallCard = cardLookupMap.get(entry.name.toLowerCase());
        if (scryfallCard) {
          this.insertCardIntoDeck(scryfallCard, 'side', entry.qty);
        }
      });

      window.closeImportModal();
      this.render();
    } catch (err) {
      console.error(err);
      alert(`Import failed: ${err.message}`);
    } finally {
      submitBtn.innerHTML = `<i class="fa-solid fa-file-arrow-down"></i> Import & Parse`;
      submitBtn.disabled = false;
    }
  },

  parseRawDeckText(text) {
    const lines = text.split(/\r?\n/);
    const main = [];
    const side = [];
    let currentBoard = 'main';

    for (let rawLine of lines) {
      let line = rawLine.trim();
      if (!line || line.startsWith('//') || line.startsWith('#')) continue;

      // Detect sideboard section headers
      const lower = line.toLowerCase();
      if (lower === 'sideboard' || lower === 'sideboard:' || lower === 'side:' || lower === '--sideboard--') {
        currentBoard = 'side';
        continue;
      }
      if (lower === 'deck' || lower === 'main' || lower === 'mainboard:' || lower === 'main:') {
        currentBoard = 'main';
        continue;
      }

      let lineTarget = currentBoard;

      // Handle lines with "SB:" prefix (MTGGoldfish / MTGO format)
      if (line.toUpperCase().startsWith('SB:')) {
        lineTarget = 'side';
        line = line.substring(3).trim();
      }

      // Regex matches: "4 Lightning Bolt" or "4x Lightning Bolt (CLB) 187 *F*"
      const match = line.match(/^(\d+)[xX]?\s+(.+)$/);
      if (!match) continue;

      const qty = parseInt(match[1], 10);
      let cardName = match[2].trim();

      // Clean set codes, collector numbers, and tags (e.g., "(CLB) 187 *F*")
      cardName = cardName.replace(/\s*\([A-Za-z0-9_]+\)\s+[0-9a-zA-Z]+.*$/, '').trim();
      cardName = cardName.replace(/\s*\*F\*\s*$/, '').trim();
      cardName = cardName.replace(/\s*#.*$/, '').trim();

      if (lineTarget === 'main') {
        main.push({ name: cardName, qty });
      } else {
        side.push({ name: cardName, qty });
      }
    }

    return { main, side };
  },

  changeCardQty(name, target, delta) {
    const list = target === 'main' ? this.deck.main : this.deck.side;
    const card = list.find(c => c.name === name);
    if (!card) return;

    card.qty += delta;
    if (card.qty <= 0) {
      if (target === 'main') {
        this.deck.main = this.deck.main.filter(c => c.name !== name);
      } else {
        this.deck.side = this.deck.side.filter(c => c.name !== name);
      }
    }
    this.render();
  },

  moveCardBoard(name, fromTarget) {
    const sourceList = fromTarget === 'main' ? this.deck.main : this.deck.side;
    const destList = fromTarget === 'main' ? this.deck.side : this.deck.main;

    const idx = sourceList.findIndex(c => c.name === name);
    if (idx === -1) return;

    const card = sourceList[idx];
    const existing = destList.find(c => c.name === name);

    if (existing) {
      existing.qty += 1;
    } else {
      destList.push({ ...card, qty: 1 });
    }

    card.qty -= 1;
    if (card.qty <= 0) {
      sourceList.splice(idx, 1);
    }

    this.render();
  },

  async openArtModal(cardName) {
    const card = [...this.deck.main, ...this.deck.side].find(c => c.name === cardName);
    if (!card) return;
    this.selectedCardForPrints = card;

    const modal = document.getElementById('art-modal');
    const grid = document.getElementById('art-prints-grid');
    document.getElementById('art-modal-title').textContent = `Alternate Art: ${card.name}`;
    grid.innerHTML = `<div class="col-span-full text-center text-sky-400 py-8"><i class="fa-solid fa-circle-notch fa-spin text-2xl"></i></div>`;
    modal.style.display = 'flex';

    try {
      const res = await fetch(`https://api.scryfall.com/cards/search?q=!"${encodeURIComponent(card.name)}"&unique=prints`);
      const data = await res.json();
      const prints = data.data || [];

      grid.innerHTML = prints.map(p => {
        const img = p.image_uris?.normal || p.card_faces?.[0]?.image_uris?.normal || '';
        if (!img) return '';
        return `
          <div class="cursor-pointer group flex flex-col items-center" onclick="Builder.selectArt('${p.id}', '${img}')">
            <img src="${img}" class="rounded-lg border-2 border-transparent group-hover:border-sky-500 transition shadow-lg">
            <span class="text-[10px] text-slate-400 font-bold uppercase mt-1.5">${p.set_name} (${p.set.toUpperCase()})</span>
          </div>
        `;
      }).join('');
    } catch (err) {
      grid.innerHTML = `<div class="col-span-full text-red-400 text-center py-4">Failed to fetch printings.</div>`;
    }
  },

  selectArt(newId, newImg) {
    const all = [...this.deck.main, ...this.deck.side].filter(c => c.name === this.selectedCardForPrints.name);
    all.forEach(c => {
      c.id = newId;
      c.image = newImg;
    });
    window.closeArtModal();
    this.render();
  },

  render() {
    this.renderBoardLane('main', 'main-stacks', 'main-group-count');
    this.renderBoardLane('side', 'side-stacks', 'side-group-count');
    this.calculateStats();
    this.checkPauperLegality();
  },

  renderBoardLane(target, containerId, countId) {
    const container = document.getElementById(containerId);
    if (!container) return;

    const list = target === 'main' ? this.deck.main : this.deck.side;
    const totalCount = list.reduce((sum, c) => sum + c.qty, 0);
    const countEl = document.getElementById(countId);
    if (countEl) countEl.textContent = `${totalCount} Cards`;

    container.innerHTML = '';
    if (!list.length) {
      container.innerHTML = `<div class="text-xs text-slate-500 italic py-6 col-span-full">No cards in ${target}. Use the search bar or import a deck list above.</div>`;
      return;
    }

    const categories = {
      'Creatures': list.filter(c => c.type_line.includes('Creature')),
      'Instants': list.filter(c => c.type_line.includes('Instant')),
      'Sorceries': list.filter(c => c.type_line.includes('Sorcery')),
      'Enchantments': list.filter(c => c.type_line.includes('Enchantment') && !c.type_line.includes('Creature')),
      'Artifacts': list.filter(c => c.type_line.includes('Artifact') && !c.type_line.includes('Creature') && !c.type_line.includes('Land')),
      'Lands': list.filter(c => c.type_line.includes('Land'))
    };

    for (const [title, cards] of Object.entries(categories)) {
      if (!cards.length) continue;

      const groupQty = cards.reduce((sum, c) => sum + c.qty, 0);
      const col = document.createElement('div');
      col.className = 'stack-column';

      col.innerHTML = `
        <div class="stack-column-header">
          <span>${title}</span>
          <span>${groupQty}</span>
        </div>
      `;

      const cascade = document.createElement('div');
      cascade.className = 'card-cascade-container';
      cascade.style.height = `${240 + (cards.length - 1) * 36}px`;

      cards.forEach((card, idx) => {
        const wrap = document.createElement('div');
        wrap.className = 'visual-card-wrapper';
        wrap.style.top = `${idx * 34}px`;
        wrap.style.zIndex = idx + 1;

        wrap.innerHTML = `
          <img src="${card.image}" class="visual-card-img" alt="${card.name}">
          <span class="card-badge-qty">${card.qty}</span>
          <div class="card-quick-actions">
            <button class="quick-btn" title="Add Copy" onclick="Builder.changeCardQty('${card.name.replace(/'/g, "\\'")}', '${target}', 1)">+</button>
            <button class="quick-btn" title="Remove Copy" onclick="Builder.changeCardQty('${card.name.replace(/'/g, "\\'")}', '${target}', -1)">-</button>
            <button class="quick-btn" title="Switch Board" onclick="Builder.moveCardBoard('${card.name.replace(/'/g, "\\'")}', '${target}')">⇄</button>
            <button class="quick-btn" title="Change Art" onclick="Builder.openArtModal('${card.name.replace(/'/g, "\\'")}')">🎨</button>
          </div>
        `;
        cascade.appendChild(wrap);
      });

      col.appendChild(cascade);
      container.appendChild(col);
    }
  },

  checkPauperLegality() {
    const badge = document.getElementById('pauper-badge');
    const reasonsContainer = document.getElementById('legality-reasons');
    if (!badge) return;

    const reasons = [];
    const mainCount = this.deck.main.reduce((s, c) => s + c.qty, 0);
    const sideCount = this.deck.side.reduce((s, c) => s + c.qty, 0);

    if (mainCount < 60) {
      reasons.push(`Mainboard requires at least 60 cards (${mainCount}/60).`);
    }

    if (sideCount > 15) {
      reasons.push(`Sideboard exceeds maximum of 15 cards (${sideCount}/15).`);
    }

    const combinedCounts = {};
    const legalityFlags = {};

    [...this.deck.main, ...this.deck.side].forEach(c => {
      combinedCounts[c.name] = (combinedCounts[c.name] || 0) + c.qty;
      legalityFlags[c.name] = c.pauper_legal;
    });

    for (const [name, qty] of Object.entries(combinedCounts)) {
      if (legalityFlags[name] === false) {
        reasons.push(`"${name}" is banned or not legal in Pauper.`);
      }

      if (!BASIC_LANDS.has(name) && !UNLIMITED_COPIES.has(name)) {
        const limit = (name === 'Seven Dwarves') ? 7 : 4;
        if (qty > limit) {
          reasons.push(`"${name}" exceeds the ${limit}-copy limit (${qty} copies present).`);
        }
      }
    }

    if (reasons.length === 0 && mainCount >= 60) {
      badge.textContent = 'Pauper Legal';
      badge.className = 'text-xs uppercase px-2.5 py-1 bg-emerald-950 text-emerald-400 font-bold border border-emerald-700 rounded transition cursor-default';
      badge.title = 'Deck meets all tournament requirements!';
      if (reasonsContainer) reasonsContainer.innerHTML = '';
    } else {
      badge.textContent = 'Not Legal';
      badge.className = 'text-xs uppercase px-2.5 py-1 bg-rose-950 text-rose-400 font-bold border border-rose-800 rounded transition cursor-pointer';
      badge.title = reasons.join('\n');
      
      if (reasonsContainer) {
        reasonsContainer.innerHTML = `
          <div class="text-[11px] text-rose-300 bg-rose-950/60 border border-rose-900 rounded p-2.5 space-y-1">
            <div class="font-bold uppercase tracking-wider text-rose-400">Legality Requirements:</div>
            ${reasons.map(r => `<div>• ${r}</div>`).join('')}
          </div>
        `;
      }
    }
  },

  calculateStats() {
    const mainList = this.deck.main;
    const sideList = this.deck.side;

    const mainTotal = mainList.reduce((s, c) => s + c.qty, 0);
    const sideTotal = sideList.reduce((s, c) => s + c.qty, 0);

    const mainCountEl = document.getElementById('stat-main-count');
    const sideCountEl = document.getElementById('stat-side-count');
    if (mainCountEl) mainCountEl.textContent = mainTotal;
    if (sideCountEl) sideCountEl.textContent = sideTotal;

    let totalUsd = 0;
    let totalEur = 0;
    [...mainList, ...sideList].forEach(c => {
      totalUsd += (c.price_usd || 0) * c.qty;
      totalEur += (c.price_eur || 0) * c.qty;
    });

    const usdEl = document.getElementById('stat-price-usd');
    const eurEl = document.getElementById('stat-price-eur');
    if (usdEl) usdEl.textContent = `$${totalUsd.toFixed(2)}`;
    if (eurEl) eurEl.textContent = `€${totalEur.toFixed(2)}`;

    const curve = [0, 0, 0, 0, 0, 0, 0];
    mainList.filter(c => !c.type_line.includes('Land')).forEach(c => {
      const cmc = Math.min(c.cmc, 6);
      curve[cmc] += c.qty;
    });

    const maxCurve = Math.max(...curve, 1);
    curve.forEach((val, i) => {
      const bar = document.getElementById(`curve-bar-${i}`);
      if (bar) {
        const pct = (val / maxCurve) * 100;
        bar.style.height = `${Math.max(pct, 4)}%`;
      }
    });

    const pips = { W: 0, U: 0, B: 0, R: 0, G: 0, C: 0 };
    mainList.forEach(c => {
      const cost = c.mana_cost || '';
      for (const char of cost) {
        if (pips[char] !== undefined) {
          pips[char] += c.qty;
        }
      }
    });

    const totalPips = Object.values(pips).reduce((a, b) => a + b, 0) || 1;
    const wEl = document.getElementById('pip-val-w');
    const uEl = document.getElementById('pip-val-u');
    const bEl = document.getElementById('pip-val-b');
    const rEl = document.getElementById('pip-val-r');
    const gEl = document.getElementById('pip-val-g');
    const cEl = document.getElementById('pip-val-c');

    if (wEl) wEl.textContent = pips.W;
    if (uEl) uEl.textContent = pips.U;
    if (bEl) bEl.textContent = pips.B;
    if (rEl) rEl.textContent = pips.R;
    if (gEl) gEl.textContent = pips.G;
    if (cEl) cEl.textContent = pips.C;

    const bar = document.getElementById('pip-progress-bar');
    if (bar) {
      bar.innerHTML = `
        <div class="pip-segment-w" style="width: ${(pips.W / totalPips) * 100}%"></div>
        <div class="pip-segment-u" style="width: ${(pips.U / totalPips) * 100}%"></div>
        <div class="pip-segment-b" style="width: ${(pips.B / totalPips) * 100}%"></div>
        <div class="pip-segment-r" style="width: ${(pips.R / totalPips) * 100}%"></div>
        <div class="pip-segment-g" style="width: ${(pips.G / totalPips) * 100}%"></div>
        <div class="pip-segment-c" style="width: ${(pips.C / totalPips) * 100}%"></div>
      `;
    }

    const types = {};
    mainList.forEach(c => {
      const primary = c.type_line.split('—')[0].trim();
      types[primary] = (types[primary] || 0) + c.qty;
    });

    const typeListContainer = document.getElementById('type-counts-list');
    if (typeListContainer) {
      typeListContainer.innerHTML = Object.entries(types).map(([k, v]) => `
        <div class="flex justify-between">
          <span class="text-slate-400">${k}</span>
          <span class="text-white font-bold">${v}</span>
        </div>
      `).join('');
    }
  },

  openSampleHand() {
    const flatDeck = [];
    this.deck.main.forEach(c => {
      for (let i = 0; i < c.qty; i++) flatDeck.push(c);
    });

    if (flatDeck.length < 7) {
      alert("Mainboard needs at least 7 cards to draw a sample hand!");
      return;
    }

    for (let i = flatDeck.length - 1; i > 0; i--) {
      const j = Math.floor(Math.random() * (i + 1));
      [flatDeck[i], flatDeck[j]] = [flatDeck[j], flatDeck[i]];
    }

    const hand = flatDeck.slice(0, 7);
    const lane = document.getElementById('sample-hand-lane');
    if (lane) {
      lane.innerHTML = hand.map(c => `
        <img src="${c.image}" class="w-32 rounded-lg shadow-xl hover:-translate-y-2 transition-transform">
      `).join('');
    }

    const modal = document.getElementById('hand-modal');
    if (modal) modal.style.display = 'flex';
  },

  async saveDeck() {
    const btn = document.getElementById('save-deck-btn');
    if (btn) btn.innerHTML = `<i class="fa-solid fa-circle-notch fa-spin"></i> Saving...`;

    try {
      const res = await fetch('/api_decks.php?action=save', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          id: this.deck.id,
          title: this.deck.title,
          cards: {
            main: this.deck.main,
            side: this.deck.side
          }
        })
      });

      const data = await res.json();
      if (!res.ok) throw new Error(data.error || 'Failed to save');

      this.deck.id = data.deck_id;
      if (btn) btn.innerHTML = `<i class="fa-solid fa-check"></i> Saved!`;
      setTimeout(() => {
        if (btn) btn.innerHTML = `<i class="fa-solid fa-floppy-disk"></i> Save Deck`;
      }, 2000);
    } catch (err) {
      alert(err.message);
      if (btn) btn.innerHTML = `<i class="fa-solid fa-floppy-disk"></i> Save Deck`;
    }
  }
};

window.openImportModal = function() {
  const modal = document.getElementById('import-modal');
  if (modal) modal.style.display = 'flex';
};

window.closeImportModal = function() {
  const modal = document.getElementById('import-modal');
  if (modal) modal.style.display = 'none';
};

window.closeArtModal = function() {
  const modal = document.getElementById('art-modal');
  if (modal) modal.style.display = 'none';
};

window.closeHandModal = function() {
  const modal = document.getElementById('hand-modal');
  if (modal) modal.style.display = 'none';
};

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => window.Builder.init());
} else {
  window.Builder.init();
}