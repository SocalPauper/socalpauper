// js/builder.js
const Builder = {
  deck: {
    id: null,
    title: "Untitled Pauper Brew",
    format: "pauper",
    main: [],
    side: []
  },
  activeTarget: 'main', // 'main' or 'side'
  selectedCardForPrints: null,
  searchDebounce: null,

  init() {
    this.bindEvents();
    this.render();
  },

  bindEvents() {
    // Target board selector
    const mainBtn = document.getElementById('target-main-btn');
    const sideBtn = document.getElementById('target-side-btn');

    mainBtn.addEventListener('click', () => {
      this.activeTarget = 'main';
      mainBtn.className = 'px-3 py-1 text-xs font-bold rounded-md bg-sky-600 text-white transition';
      sideBtn.className = 'px-3 py-1 text-xs font-bold rounded-md text-slate-400 hover:text-white transition';
    });

    sideBtn.addEventListener('click', () => {
      this.activeTarget = 'side';
      sideBtn.className = 'px-3 py-1 text-xs font-bold rounded-md bg-emerald-600 text-white transition';
      mainBtn.className = 'px-3 py-1 text-xs font-bold rounded-md text-slate-400 hover:text-white transition';
    });

    // Title input change
    const titleInput = document.getElementById('deck-title-input');
    titleInput.addEventListener('input', (e) => {
      this.deck.title = e.target.value;
    });

    // Autocomplete input
    const searchInput = document.getElementById('card-search-input');
    const suggestionsBox = document.getElementById('search-suggestions');

    searchInput.addEventListener('input', (e) => {
      clearTimeout(this.searchDebounce);
      const val = e.target.value.trim();
      if (val.length < 2) {
        suggestionsBox.classList.add('hidden');
        return;
      }

      document.getElementById('search-spinner').classList.remove('hidden');

      this.searchDebounce = setTimeout(async () => {
        try {
          const res = await fetch(`https://api.scryfall.com/cards/autocomplete?q=${encodeURIComponent(val)}`);
          const data = await res.json();
          this.renderSuggestions(data.data || []);
        } catch (err) {
          console.error(err);
        } finally {
          document.getElementById('search-spinner').classList.add('hidden');
        }
      }, 150);
    });

    // Save button
    document.getElementById('save-deck-btn').addEventListener('click', () => this.saveDeck());

    // Sample Hand
    document.getElementById('sample-hand-btn').addEventListener('click', () => this.openSampleHand());
    document.getElementById('mulligan-btn').addEventListener('click', () => this.openSampleHand());
  },

  renderSuggestions(names) {
    const box = document.getElementById('search-suggestions');
    if (!names.length) {
      box.classList.add('hidden');
      return;
    }

    box.innerHTML = names.map(name => `
      <div class="suggestion-row" onclick="Builder.addCardByName('${name.replace(/'/g, "\\'")}')">
        <span class="text-sm font-semibold text-white">${name}</span>
        <span class="text-xs text-sky-400 font-bold">+ Add to ${this.activeTarget.toUpperCase()}</span>
      </div>
    `).join('');
    box.classList.remove('hidden');
  },

  async addCardByName(cardName) {
    document.getElementById('search-suggestions').classList.add('hidden');
    document.getElementById('card-search-input').value = '';

    try {
      const res = await fetch(`https://api.scryfall.com/cards/named?exact=${encodeURIComponent(cardName)}`);
      if (!res.ok) return;
      const data = await res.json();

      const image = data.image_uris ? data.image_uris.normal : (data.card_faces ? data.card_faces[0].image_uris.normal : '');
      const prices = data.prices || {};

      const card = {
        id: data.id,
        oracle_id: data.oracle_id,
        name: data.name,
        mana_cost: data.mana_cost || '',
        cmc: Math.floor(data.cmc || 0),
        type_line: data.type_line || '',
        image: image,
        price_usd: parseFloat(prices.usd || 0),
        price_eur: parseFloat(prices.eur || 0),
        qty: 1
      };

      const targetList = this.activeTarget === 'main' ? this.deck.main : this.deck.side;
      const existing = targetList.find(c => c.name === card.name);

      if (existing) {
        existing.qty += 1;
      } else {
        targetList.push(card);
      }

      this.render();
    } catch (err) {
      console.error(err);
    }
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

  // Open alternate art selector modal
  async openArtModal(cardName) {
    const card = [...this.deck.main, ...this.deck.side].find(c => c.name === cardName);
    if (!card) return;
    this.selectedCardForPrints = card;

    const modal = document.getElementById('art-modal');
    const grid = document.getElementById('art-prints-grid');
    document.getElementById('art-modal-title').textContent = `Alternate Art: ${card.name}`;
    grid.innerHTML = `<div class="col-span-full text-center text-sky-400 py-8"><i class="fa-solid fa-circle-notch fa-spin text-2xl"></i></div>`;
    modal.classList.remove('hidden');

    try {
      const res = await fetch(`https://api.scryfall.com/cards/search?q=!"${encodeURIComponent(card.name)}"&unique=prints`);
      const data = await res.json();
      const prints = data.data || [];

      grid.innerHTML = prints.map(p => {
        const img = p.image_uris ? p.image_uris.normal : (p.card_faces ? p.card_faces[0].image_uris.normal : '');
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
    // Update instances in both boards
    const all = [...this.deck.main, ...this.deck.side].filter(c => c.name === this.selectedCardForPrints.name);
    all.forEach(c => {
      c.id = newId;
      c.image = newImg;
    });
    closeArtModal();
    this.render();
  },

  // Render Visual Stacks
  render() {
    this.renderBoardLane('main', 'main-stacks', 'main-group-count');
    this.renderBoardLane('side', 'side-stacks', 'side-group-count');
    this.calculateStats();
  },

  renderBoardLane(target, containerId, countId) {
    const container = document.getElementById(containerId);
    const list = target === 'main' ? this.deck.main : this.deck.side;
    const totalCount = list.reduce((sum, c) => sum + c.qty, 0);
    document.getElementById(countId).textContent = `${totalCount} Cards`;

    container.innerHTML = '';
    if (!list.length) {
      container.innerHTML = `<div class="text-xs text-slate-500 italic py-6 col-span-full">No cards in ${target}. Use the search bar above to begin brewing.</div>`;
      return;
    }

    // Category sorting
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
            <button class="quick-btn" title="Add Copy" onclick="Builder.changeCardQty('${card.name}', '${target}', 1)">+</button>
            <button class="quick-btn" title="Remove Copy" onclick="Builder.changeCardQty('${card.name}', '${target}', -1)">-</button>
            <button class="quick-btn" title="Switch Board" onclick="Builder.moveCardBoard('${card.name}', '${target}')">⇄</button>
            <button class="quick-btn" title="Change Art" onclick="Builder.openArtModal('${card.name}')">🎨</button>
          </div>
        `;
        cascade.appendChild(wrap);
      });

      col.appendChild(cascade);
      container.appendChild(col);
    }
  },

  calculateStats() {
    const mainList = this.deck.main;
    const sideList = this.deck.side;

    const mainTotal = mainList.reduce((s, c) => s + c.qty, 0);
    const sideTotal = sideList.reduce((s, c) => s + c.qty, 0);

    document.getElementById('stat-main-count').textContent = mainTotal;
    document.getElementById('stat-side-count').textContent = sideTotal;

    // Price Calculations (Main + Side)
    let totalUsd = 0;
    let totalEur = 0;
    [...mainList, ...sideList].forEach(c => {
      totalUsd += (c.price_usd || 0) * c.qty;
      totalEur += (c.price_eur || 0) * c.qty;
    });

    document.getElementById('stat-price-usd').textContent = `$${totalUsd.toFixed(2)}`;
    document.getElementById('stat-price-eur').textContent = `€${totalEur.toFixed(2)}`;

    // Mana Curve (Non-lands in mainboard)
    const curve = [0, 0, 0, 0, 0, 0, 0];
    mainList.filter(c => !c.type_line.includes('Land')).forEach(c => {
      const cmc = Math.min(c.cmc, 6);
      curve[cmc] += c.qty;
    });

    const maxCurve = Math.max(...curve, 1);
    curve.forEach((val, i) => {
      const pct = (val / maxCurve) * 100;
      document.getElementById(`curve-bar-${i}`).style.height = `${Math.max(pct, 4)}%`;
    });

    // Colored Pip Breakdown
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
    document.getElementById('pip-val-w').textContent = pips.W;
    document.getElementById('pip-val-u').textContent = pips.U;
    document.getElementById('pip-val-b').textContent = pips.B;
    document.getElementById('pip-val-r').textContent = pips.R;
    document.getElementById('pip-val-g').textContent = pips.G;
    document.getElementById('pip-val-c').textContent = pips.C;

    const bar = document.getElementById('pip-progress-bar');
    bar.innerHTML = `
      <div class="pip-segment-w" style="width: ${(pips.W / totalPips) * 100}%"></div>
      <div class="pip-segment-u" style="width: ${(pips.U / totalPips) * 100}%"></div>
      <div class="pip-segment-b" style="width: ${(pips.B / totalPips) * 100}%"></div>
      <div class="pip-segment-r" style="width: ${(pips.R / totalPips) * 100}%"></div>
      <div class="pip-segment-g" style="width: ${(pips.G / totalPips) * 100}%"></div>
      <div class="pip-segment-c" style="width: ${(pips.C / totalPips) * 100}%"></div>
    `;

    // Type Breakdown
    const types = {};
    mainList.forEach(c => {
      const primary = c.type_line.split('—')[0].trim();
      types[primary] = (types[primary] || 0) + c.qty;
    });

    const typeListContainer = document.getElementById('type-counts-list');
    typeListContainer.innerHTML = Object.entries(types).map(([k, v]) => `
      <div class="flex justify-between">
        <span class="text-slate-400">${k}</span>
        <span class="text-white font-bold">${v}</span>
      </div>
    `).join('');
  },

  // Opening 7 Hand Mulligan Simulation
  openSampleHand() {
    const flatDeck = [];
    this.deck.main.forEach(c => {
      for (let i = 0; i < c.qty; i++) {
        flatDeck.push(c);
      }
    });

    if (flatDeck.length < 7) {
      alert("Mainboard needs at least 7 cards to draw a sample hand!");
      return;
    }

    // Fisher-Yates shuffle
    for (let i = flatDeck.length - 1; i > 0; i--) {
      const j = Math.floor(Math.random() * (i + 1));
      [flatDeck[i], flatDeck[j]] = [flatDeck[j], flatDeck[i]];
    }

    const hand = flatDeck.slice(0, 7);
    const lane = document.getElementById('sample-hand-lane');
    lane.innerHTML = hand.map(c => `
      <img src="${c.image}" class="w-32 rounded-lg shadow-xl hover:-translate-y-2 transition-transform">
    `).join('');

    document.getElementById('hand-modal').classList.remove('hidden');
  },

  async saveDeck() {
    const btn = document.getElementById('save-deck-btn');
    btn.innerHTML = `<i class="fa-solid fa-circle-notch fa-spin"></i> Saving...`;

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
      btn.innerHTML = `<i class="fa-solid fa-check"></i> Saved!`;
      setTimeout(() => {
        btn.innerHTML = `<i class="fa-solid fa-floppy-disk"></i> Save Deck`;
      }, 2000);
    } catch (err) {
      alert(err.message);
      btn.innerHTML = `<i class="fa-solid fa-floppy-disk"></i> Save Deck`;
    }
  }
};

function closeArtModal() {
  document.getElementById('art-modal').classList.add('hidden');
}

function closeHandModal() {
  document.getElementById('hand-modal').classList.add('hidden');
}

document.addEventListener('DOMContentLoaded', () => {
  Builder.init();
});