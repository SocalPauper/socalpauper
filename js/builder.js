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

// Hypergeometric combination helper: nCr = n! / (r! * (n - r)!)
function combinations(n, r) {
  if (r < 0 || r > n) return 0;
  if (r === 0 || r === n) return 1;
  let c = 1;
  for (let i = 1; i <= r; i++) {
    c = c * (n - (i - 1)) / i;
  }
  return c;
}

// Hypergeometric probability: P(X = k)
function hypergeomPdf(k, N, K, n) {
  return (combinations(K, k) * combinations(N - K, n - k)) / combinations(N, n);
}

window.Builder = {
  deck: {
    id: null,
    title: "Untitled Pauper Brew",
    main: [],
    side: []
  },
  user: null,
  activeTarget: 'main',
  selectedCardForPrints: null,
  searchDebounce: null,
  authMode: 'login',

  init() {
    this.checkAuth();
    this.bindEvents();
    this.render();
  },

  async checkAuth() {
    try {
      const res = await fetch('/api_auth.php?action=check');
      const data = await res.json();
      this.user = data.authenticated ? data.user : null;
    } catch (e) {
      this.user = null;
    }
    this.updateAuthUI();
  },

  updateAuthUI() {
    const slot = document.getElementById('nav-auth-slot');
    const myDecksBtn = document.getElementById('my-decks-btn');
    const adminBtn = document.getElementById('admin-panel-btn');

    if (!slot) return;

    if (this.user) {
      if (myDecksBtn) myDecksBtn.classList.remove('hidden');
      if (adminBtn) {
        if (this.user.role === 'admin') adminBtn.classList.remove('hidden');
        else adminBtn.classList.add('hidden');
      }

      slot.innerHTML = `
        <div class="flex items-center gap-2">
          <span class="text-xs text-sky-400 font-bold uppercase tracking-wider">👤 ${this.escapeHtml(this.user.username)}</span>
          <button type="button" onclick="Builder.logout()" class="text-xs bg-slate-800 hover:bg-slate-700 text-slate-300 px-2 py-1 rounded border border-slate-700 transition">Log Out</button>
        </div>
      `;
    } else {
      if (myDecksBtn) myDecksBtn.classList.add('hidden');
      if (adminBtn) adminBtn.classList.add('hidden');

      slot.innerHTML = `
        <button type="button" onclick="Builder.openAuthModal('login')" class="text-xs text-slate-300 hover:text-white px-2 py-1 transition font-medium">Log In</button>
        <button type="button" onclick="Builder.openAuthModal('register')" class="text-xs bg-sky-600 hover:bg-sky-500 text-white font-semibold px-2.5 py-1 rounded transition shadow-sm">Sign Up</button>
      `;
    }
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

    const importBtn = document.getElementById('import-deck-btn');
    if (importBtn) importBtn.onclick = () => window.openImportModal();

    const importSubmitBtn = document.getElementById('import-submit-btn');
    if (importSubmitBtn) importSubmitBtn.onclick = () => this.handleImportDeck();

    const myDecksBtn = document.getElementById('my-decks-btn');
    if (myDecksBtn) myDecksBtn.onclick = () => this.openMyDecksModal();

    const adminBtn = document.getElementById('admin-panel-btn');
    if (adminBtn) adminBtn.onclick = () => this.openAdminModal();

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
            console.error(err);
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

    const authForm = document.getElementById('auth-form');
    if (authForm) {
      authForm.onsubmit = (e) => {
        e.preventDefault();
        this.handleAuthSubmit();
      };
    }

    const authToggleBtn = document.getElementById('auth-toggle-btn');
    if (authToggleBtn) {
      authToggleBtn.onclick = () => {
        this.openAuthModal(this.authMode === 'login' ? 'register' : 'login');
      };
    }
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
        alert("No valid card lines found.");
        return;
      }

      if (replaceCurrent) {
        this.deck.main = [];
        this.deck.side = [];
      }

      const allEntries = [...parsedEntries.main, ...parsedEntries.side];
      const uniqueNames = [...new Set(allEntries.map(e => e.name))];

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

      parsedEntries.main.forEach(entry => {
        const scryfallCard = cardLookupMap.get(entry.name.toLowerCase());
        if (scryfallCard) this.insertCardIntoDeck(scryfallCard, 'main', entry.qty);
      });

      parsedEntries.side.forEach(entry => {
        const scryfallCard = cardLookupMap.get(entry.name.toLowerCase());
        if (scryfallCard) this.insertCardIntoDeck(scryfallCard, 'side', entry.qty);
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
      if (line.toUpperCase().startsWith('SB:')) {
        lineTarget = 'side';
        line = line.substring(3).trim();
      }

      const match = line.match(/^(\d+)[xX]?\s+(.+)$/);
      if (!match) continue;

      const qty = parseInt(match[1], 10);
      let cardName = match[2].trim();
      cardName = cardName.replace(/\s*\([A-Za-z0-9_]+\)\s+[0-9a-zA-Z]+.*$/, '').trim();
      cardName = cardName.replace(/\s*\*F\*\s*$/, '').trim();
      cardName = cardName.replace(/\s*#.*$/, '').trim();

      if (lineTarget === 'main') main.push({ name: cardName, qty });
      else side.push({ name: cardName, qty });
    }

    return { main, side };
  },

  changeCardQty(name, target, delta) {
    const list = target === 'main' ? this.deck.main : this.deck.side;
    const card = list.find(c => c.name === name);
    if (!card) return;

    card.qty += delta;
    if (card.qty <= 0) {
      if (target === 'main') this.deck.main = this.deck.main.filter(c => c.name !== name);
      else this.deck.side = this.deck.side.filter(c => c.name !== name);
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
    if (card.qty <= 0) sourceList.splice(idx, 1);
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

  // Dynamic Type Determiner (ensures no card is ever swallowed/hidden)
  getCardCategory(card) {
    const tl = card.type_line || '';
    if (tl.includes('Planeswalker')) return 'Planeswalkers';
    if (tl.includes('Creature')) return 'Creatures';
    if (tl.includes('Instant')) return 'Instants';
    if (tl.includes('Sorcery')) return 'Sorceries';
    if (tl.includes('Artifact')) return 'Artifacts';
    if (tl.includes('Enchantment')) return 'Enchantments';
    if (tl.includes('Battle')) return 'Battles';
    if (tl.includes('Land')) return 'Lands';
    return 'Other';
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

    // Dynamic categorizer covering all Magic types & edge cases
    const categoryOrder = [
      'Creatures',
      'Planeswalkers',
      'Instants',
      'Sorceries',
      'Artifacts',
      'Enchantments',
      'Battles',
      'Lands',
      'Other'
    ];

    const buckets = {};
    categoryOrder.forEach(cat => { buckets[cat] = []; });

    list.forEach(card => {
      const cat = this.getCardCategory(card);
      if (!buckets[cat]) buckets[cat] = [];
      buckets[cat].push(card);
    });

    for (const title of categoryOrder) {
      const cards = buckets[title];
      if (!cards || !cards.length) continue;

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
        if (pips[char] !== undefined) pips[char] += c.qty;
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

    this.renderTypeBreakdown(mainList, 'main-type-counts-list');
    this.renderTypeBreakdown(sideList, 'side-type-counts-list');
    this.calculateHandProbabilities(mainList, mainTotal);
  },

  renderTypeBreakdown(cardsList, containerId) {
    const types = {};
    cardsList.forEach(c => {
      const primary = c.type_line.split('—')[0].trim();
      types[primary] = (types[primary] || 0) + c.qty;
    });

    const container = document.getElementById(containerId);
    if (!container) return;

    if (!cardsList.length) {
      container.innerHTML = `<span class="text-slate-500 italic">No cards added</span>`;
      return;
    }

    container.innerHTML = Object.entries(types).map(([k, v]) => `
      <div class="flex justify-between py-0.5 border-b border-slate-800/60 last:border-0">
        <span class="text-slate-400">${k}</span>
        <span class="text-white font-bold">${v}</span>
      </div>
    `).join('');
  },

  calculateHandProbabilities(mainList, mainTotal) {
    const N = mainTotal;
    const n = 7;

    const probStdEl = document.getElementById('prob-standard-lands');
    const barStdEl = document.getElementById('bar-standard-lands');
    const probAggroEl = document.getElementById('prob-aggro-lands');
    const barAggroEl = document.getElementById('bar-aggro-lands');
    const probOneEl = document.getElementById('prob-one-drop');
    const barOneEl = document.getElementById('bar-one-drop');

    if (N < 7) {
      if (probStdEl) probStdEl.textContent = '0.0%';
      if (probAggroEl) probAggroEl.textContent = '0.0%';
      if (probOneEl) probOneEl.textContent = '0.0%';
      return;
    }

    const totalLands = mainList
      .filter(c => c.type_line.includes('Land'))
      .reduce((sum, c) => sum + c.qty, 0);

    const totalOneDrops = mainList
      .filter(c => !c.type_line.includes('Land') && c.cmc === 1)
      .reduce((sum, c) => sum + c.qty, 0);

    let probStd = 0;
    for (let k = 2; k <= 4; k++) {
      probStd += hypergeomPdf(k, N, totalLands, n);
    }

    let probAggro = 0;
    for (let k = 1; k <= 3; k++) {
      probAggro += hypergeomPdf(k, N, totalLands, n);
    }

    const probZeroOneDrops = hypergeomPdf(0, N, totalOneDrops, n);
    const probOneDrop = Math.max(0, 1 - probZeroOneDrops);

    const pctStd = Math.min(100, Math.max(0, probStd * 100));
    const pctAggro = Math.min(100, Math.max(0, probAggro * 100));
    const pctOne = Math.min(100, Math.max(0, probOneDrop * 100));

    if (probStdEl) probStdEl.textContent = `${pctStd.toFixed(1)}%`;
    if (barStdEl) barStdEl.style.width = `${pctStd}%`;

    if (probAggroEl) probAggroEl.textContent = `${pctAggro.toFixed(1)}%`;
    if (barAggroEl) barAggroEl.style.width = `${pctAggro}%`;

    if (probOneEl) probOneEl.textContent = `${pctOne.toFixed(1)}%`;
    if (barOneEl) barOneEl.style.width = `${pctOne}%`;
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
    if (!this.user) {
      this.openAuthModal('login');
      return;
    }

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
        if (btn) btn.innerHTML = `<i class="fa-solid fa-floppy-disk"></i> Save`;
      }, 2000);
    } catch (err) {
      alert(err.message);
      if (btn) btn.innerHTML = `<i class="fa-solid fa-floppy-disk"></i> Save`;
    }
  },

  openAuthModal(mode = 'login') {
    this.authMode = mode;
    const modal = document.getElementById('auth-modal');
    const title = document.getElementById('auth-modal-title');
    const submitBtn = document.getElementById('auth-submit-btn');
    const togglePrompt = document.getElementById('auth-toggle-prompt');
    const toggleBtn = document.getElementById('auth-toggle-btn');
    const registerFields = document.getElementById('auth-register-fields');
    const errBox = document.getElementById('auth-error-msg');

    if (errBox) errBox.classList.add('hidden');

    if (mode === 'register') {
      title.textContent = "Create Account";
      submitBtn.textContent = "Sign Up";
      togglePrompt.textContent = "Already have an account?";
      toggleBtn.textContent = "Sign In";
      registerFields.style.display = 'block';
    } else {
      title.textContent = "Sign In to SoCal Pauper";
      submitBtn.textContent = "Sign In";
      togglePrompt.textContent = "Don't have an account?";
      toggleBtn.textContent = "Create Account";
      registerFields.style.display = 'none';
    }

    modal.style.display = 'flex';
  },

  async handleAuthSubmit() {
    const errBox = document.getElementById('auth-error-msg');
    const username = document.getElementById('auth-username').value.trim();
    const password = document.getElementById('auth-password').value;
    const email = document.getElementById('auth-email').value.trim();
    const notifyEvents = document.getElementById('auth-notify-events').checked;
    const notifyPromos = document.getElementById('auth-notify-promos').checked;

    const endpoint = this.authMode === 'register' 
      ? '/api_auth.php?action=register' 
      : '/api_auth.php?action=login';

    const payload = this.authMode === 'register'
      ? { username, password, email, notify_events: notifyEvents, notify_promotions: notifyPromos }
      : { username, password };

    try {
      const res = await fetch(endpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });

      const data = await res.json();
      if (!res.ok) throw new Error(data.error || 'Authentication error');

      this.user = data.user;
      this.updateAuthUI();
      window.closeAuthModal();

      if (this.deck.main.length > 0) {
        this.saveDeck();
      }
    } catch (err) {
      if (errBox) {
        errBox.textContent = err.message;
        errBox.classList.remove('hidden');
      }
    }
  },

  async logout() {
    await fetch('/api_auth.php?action=logout');
    this.user = null;
    this.updateAuthUI();
  },

  async openMyDecksModal() {
    const modal = document.getElementById('my-decks-modal');
    const list = document.getElementById('my-decks-list');
    list.innerHTML = `<div class="text-center py-6 text-sky-400"><i class="fa-solid fa-circle-notch fa-spin"></i> Loading decks...</div>`;
    modal.style.display = 'flex';

    try {
      const res = await fetch('/api_decks.php?action=my_decks');
      const data = await res.json();
      if (!res.ok) throw new Error(data.error || 'Failed to fetch decks');

      const decks = data.decks || [];
      if (!decks.length) {
        list.innerHTML = `<div class="text-slate-400 text-xs italic py-4 text-center">You haven't saved any decks yet.</div>`;
        return;
      }

      list.innerHTML = decks.map(d => `
        <div class="flex items-center justify-between p-3 bg-slate-900 border border-slate-800 rounded-lg hover:border-slate-700 transition">
          <div>
            <div class="font-bold text-white text-sm">${this.escapeHtml(d.title)}</div>
            <div class="text-[10px] text-slate-400">Updated: ${new Date(d.updated_at).toLocaleDateString()}</div>
          </div>
          <button type="button" onclick="Builder.loadDeck(${d.id})" class="px-3 py-1 bg-sky-600 hover:bg-sky-500 text-white font-bold text-xs rounded transition">
            Load
          </button>
        </div>
      `).join('');
    } catch (err) {
      list.innerHTML = `<div class="text-rose-400 text-xs py-4 text-center">${err.message}</div>`;
    }
  },

  async loadDeck(deckId) {
    try {
      const res = await fetch(`/api_decks.php?action=get&id=${deckId}`);
      const data = await res.json();
      if (!res.ok) throw new Error(data.error || 'Failed to load deck');

      const d = data.deck;
      this.deck.id = d.id;
      this.deck.title = d.title;
      this.deck.main = d.cards_data.main || [];
      this.deck.side = d.cards_data.side || [];

      document.getElementById('deck-title-input').value = d.title;
      window.closeMyDecksModal();
      window.closeAdminModal();
      this.render();
    } catch (err) {
      alert(err.message);
    }
  },

  async openAdminModal() {
    const modal = document.getElementById('admin-modal');
    const container = document.getElementById('admin-decks-table-container');
    container.innerHTML = `<div class="text-center py-8 text-purple-400"><i class="fa-solid fa-circle-notch fa-spin"></i> Fetching site-wide decks...</div>`;
    modal.style.display = 'flex';

    try {
      const res = await fetch('/api_decks.php?action=admin_all_decks');
      const data = await res.json();
      if (!res.ok) throw new Error(data.error || 'Admin request failed');

      const decks = data.decks || [];
      if (!decks.length) {
        container.innerHTML = `<div class="text-slate-400 text-xs italic text-center py-4">No decks found in database.</div>`;
        return;
      }

      container.innerHTML = `
        <table class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="border-b border-slate-700 text-slate-400 font-bold uppercase text-[10px]">
              <th class="pb-2">Deck Title</th>
              <th class="pb-2">Author</th>
              <th class="pb-2">Email</th>
              <th class="pb-2">Updated</th>
              <th class="pb-2 text-right">Inspect</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-800">
            ${decks.map(d => `
              <tr class="hover:bg-slate-900/60">
                <td class="py-2.5 font-bold text-white">${this.escapeHtml(d.title)}</td>
                <td class="py-2.5 text-sky-400 font-mono">${this.escapeHtml(d.username)}</td>
                <td class="py-2.5 text-slate-400">${d.email ? this.escapeHtml(d.email) : '<span class="italic text-slate-600">none</span>'}</td>
                <td class="py-2.5 text-slate-400">${new Date(d.updated_at).toLocaleDateString()}</td>
                <td class="py-2.5 text-right">
                  <button type="button" onclick="Builder.loadDeck(${d.id})" class="px-2.5 py-1 bg-purple-900/80 hover:bg-purple-800 border border-purple-700 text-purple-200 font-bold rounded text-[11px] transition">
                    View
                  </button>
                </td>
              </tr>
            `).join('')}
          </tbody>
        </table>
      `;
    } catch (err) {
      container.innerHTML = `<div class="text-rose-400 text-xs py-4 text-center">${err.message}</div>`;
    }
  },

  escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
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

window.closeAuthModal = function() {
  const modal = document.getElementById('auth-modal');
  if (modal) modal.style.display = 'none';
};

window.closeMyDecksModal = function() {
  const modal = document.getElementById('my-decks-modal');
  if (modal) modal.style.display = 'none';
};

window.closeAdminModal = function() {
  const modal = document.getElementById('admin-modal');
  if (modal) modal.style.display = 'none';
};

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => window.Builder.init());
} else {
  window.Builder.init();
}