let decksDatabase = [];
let activeDeck = null;
const CARD_CACHE_KEY = 'socal_card_meta_v1';

// Load persistent card cache from localStorage
function getLocalCache() {
  try {
    return JSON.parse(localStorage.getItem(CARD_CACHE_KEY)) || {};
  } catch (e) {
    return {};
  }
}

function setLocalCache(cache) {
  try {
    localStorage.setItem(CARD_CACHE_KEY, JSON.stringify(cache));
  } catch (e) {}
}

document.addEventListener('DOMContentLoaded', initMetagame);

async function initMetagame() {
  try {
    const res = await fetch('/data/top_decks.json');
    if (!res.ok) throw new Error('Failed to load metagame data');
    decksDatabase = await res.json();

    if (!decksDatabase || decksDatabase.length === 0) {
      renderEmptyState();
      return;
    }

    // Resolve key card art for tile banners first
    await hydrateBanners(decksDatabase);
    renderDeckTiles(decksDatabase);
  } catch (err) {
    console.error('Metagame error:', err);
    renderEmptyState();
  }
}

function renderEmptyState() {
  document.getElementById('deck-grid').innerHTML = `
    <div style="grid-column: 1 / -1; text-align: center; padding: 3rem 1rem; color: #a1a1aa;">
      <p style="font-size: 1.1rem; color: #fff; margin-bottom: 0.5rem;">Metagame data is updating.</p>
      <p style="font-size: 0.9rem;">Check back shortly for the latest tournament breakdown.</p>
    </div>
  `;
}

// Batch query Scryfall for missing cards
async function resolveCardsWithScryfall(cardNames) {
  const cache = getLocalCache();
  const missing = [...new Set(cardNames.map(n => n.split(' // ')[0].trim()))]
    .filter(name => !cache[name.toLowerCase()]);

  if (missing.length === 0) return cache;

  // Scryfall allows up to 75 identifiers per POST
  for (let i = 0; i < missing.length; i += 75) {
    const chunk = missing.slice(i, i + 75);
    try {
      const resp = await fetch('https://api.scryfall.com/cards/collection', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ identifiers: chunk.map(name => ({ name })) })
      });

      if (resp.ok) {
        const result = await resp.json();
        for (const card of (result.data || [])) {
          const cleanName = card.name.split(' // ')[0].trim().toLowerCase();
          const typeLine = card.type_line || '';

          let primaryType = 'Other';
          const candidates = ['Creature', 'Artifact', 'Enchantment', 'Planeswalker', 'Instant', 'Sorcery', 'Battle', 'Land'];
          for (const cand of candidates) {
            if (typeLine.includes(cand)) {
              primaryType = cand;
              break;
            }
          }

          let artCrop = card.image_uris?.art_crop || '';
          if (!artCrop && card.card_faces) {
            artCrop = card.card_faces[0]?.image_uris?.art_crop || '';
          }

          cache[cleanName] = {
            id: card.id,
            type: primaryType,
            artCrop: artCrop
          };
        }
      }
    } catch (e) {
      console.warn('Scryfall lookup error:', e);
    }
  }

  setLocalCache(cache);
  return cache;
}

// Preload banner art for the top tiles
async function hydrateBanners(decks) {
  const keyCards = decks.map(d => d.keyCard).filter(Boolean);
  const cache = await resolveCardsWithScryfall(keyCards);

  for (const d of decks) {
    const key = (d.keyCard || '').toLowerCase();
    d.bannerArt = cache[key]?.artCrop || 'https://cards.scryfall.io/art_crop/front/4/f/4f30bc04-2477-4def-97ff-50a97dc3d56d.jpg';
  }
}

function renderDeckTiles(decks) {
  const container = document.getElementById('deck-grid');
  container.innerHTML = decks.map(deck => `
    <article class="deck-tile" onclick="openDeckModal('${deck.id}')">
      <img src="${deck.bannerArt}" alt="${deck.name}" class="deck-banner" loading="lazy" />
      <div class="deck-content">
        <h3 class="deck-title">${deck.name}</h3>
        <div class="deck-stat-badges">
          <span class="badge">Share: ${deck.metaShare}</span>
          <span class="badge">Win: ${deck.overallWinRate}</span>
        </div>
      </div>
    </article>
  `).join('');
}

async function openDeckModal(deckId) {
  activeDeck = decksDatabase.find(d => d.id === deckId);
  if (!activeDeck) return;

  document.getElementById('modal-deck-header').innerHTML = `
    <h2 id="modal-deck-title" style="color: #fff; font-size: 1.4rem;">${activeDeck.name}</h2>
    <div style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.2rem;">
      Meta Share: ${activeDeck.metaShare} &bull; Sample: ${activeDeck.sampleMatches} Matches
    </div>
  `;

  // Collect card names in this deck
  const allCardNames = [
    ...(activeDeck.mainboard || []).map(c => c.name),
    ...(activeDeck.sideboard || []).map(c => c.name)
  ];

  // Resolve types & UUIDs via cache / Scryfall collection API
  const cardDb = await resolveCardsWithScryfall(allCardNames);

  renderCategorizedDecklist(activeDeck, cardDb);
  renderMatchups(activeDeck);
  switchModalTab('decklist');

  document.getElementById('deck-modal').classList.remove('hidden');
}

function closeDeckModal() {
  document.getElementById('deck-modal').classList.add('hidden');
  clearCardPreview();
}

function switchModalTab(targetTab) {
  const btnList = document.getElementById('tab-btn-decklist');
  const btnMatch = document.getElementById('tab-btn-matchups');
  const viewList = document.getElementById('tab-decklist');
  const viewMatch = document.getElementById('tab-matchups');

  if (targetTab === 'decklist') {
    btnList.classList.add('active');
    btnMatch.classList.remove('active');
    viewList.classList.remove('hidden');
    viewMatch.classList.add('hidden');
  } else {
    btnMatch.classList.add('active');
    btnList.classList.remove('active');
    viewMatch.classList.remove('hidden');
    viewList.classList.add('hidden');
  }
}

function renderCategorizedDecklist(deck, cardDb) {
  const container = document.getElementById('deck-breakdown');
  
  // Group mainboard by resolved supertype
  const categorized = {};
  for (const item of (deck.mainboard || [])) {
    const key = item.name.split(' // ')[0].trim().toLowerCase();
    const meta = cardDb[key] || { id: '', type: 'Other' };
    const t = meta.type || 'Other';

    if (!categorized[t]) categorized[t] = [];
    categorized[t].push({ ...item, scryfallId: meta.id });
  }

  let markup = '';
  // Preferred type ordering
  const typeOrder = ['Creature', 'Instant', 'Sorcery', 'Artifact', 'Enchantment', 'Land', 'Other'];
  const sortedTypes = Object.keys(categorized).sort((a, b) => {
    let ia = typeOrder.indexOf(a);
    let ib = typeOrder.indexOf(b);
    if (ia === -1) ia = 99;
    if (ib === -1) ib = 99;
    return ia - ib;
  });

  for (const type of sortedTypes) {
    const cards = categorized[type];
    const total = cards.reduce((sum, c) => sum + c.count, 0);
    markup += `
      <div class="type-group">
        <div class="type-group-title">${type} (${total})</div>
        ${cards.map(c => `
          <div class="card-item" 
               onmouseenter="setCardPreview('${c.scryfallId}')" 
               onclick="setCardPreview('${c.scryfallId}')">
            <span class="card-count">${c.count}</span>
            <span class="card-name">${c.name}</span>
          </div>
        `).join('')}
      </div>
    `;
  }

  // Render sideboard
  if (deck.sideboard && deck.sideboard.length > 0) {
    const totalSide = deck.sideboard.reduce((sum, c) => sum + c.count, 0);
    markup += `
      <div class="type-group">
        <div class="type-group-title">Sideboard (${totalSide})</div>
        ${deck.sideboard.map(c => {
          const key = c.name.split(' // ')[0].trim().toLowerCase();
          const sid = cardDb[key]?.id || '';
          return `
            <div class="card-item" 
                 onmouseenter="setCardPreview('${sid}')" 
                 onclick="setCardPreview('${sid}')">
              <span class="card-count">${c.count}</span>
              <span class="card-name">${c.name}</span>
            </div>
          `;
        }).join('')}
      </div>
    `;
  }

  container.innerHTML = markup;
}

function renderMatchups(deck) {
  document.getElementById('stat-winrate').innerText = deck.overallWinRate;
  document.getElementById('stat-share').innerText = deck.metaShare;
  document.getElementById('stat-matches').innerText = deck.sampleMatches;

  const favBox = document.getElementById('matchups-favorable');
  const evenBox = document.getElementById('matchups-even');
  const unfavBox = document.getElementById('matchups-unfavorable');

  favBox.innerHTML = '';
  evenBox.innerHTML = '';
  unfavBox.innerHTML = '';

  (deck.matchups || []).forEach(m => {
    const element = `
      <div class="matchup-card ${m.advantage}">
        <div class="matchup-line">
          <span>${m.opponentName}</span>
          <span>${m.winRate}%</span>
        </div>
        <div class="bar-track">
          <div class="bar-fill ${m.advantage}" style="width: ${Math.min(m.winRate, 100)}%;"></div>
        </div>
        <div class="matchup-sub">Record: ${m.record}</div>
      </div>
    `;

    if (m.advantage === 'favorable') favBox.innerHTML += element;
    else if (m.advantage === 'unfavorable') unfavBox.innerHTML += element;
    else evenBox.innerHTML += element;
  });
}

function setCardPreview(scryfallId) {
  if (!scryfallId) return;
  const img = document.getElementById('card-preview-img');
  const emptyText = document.getElementById('card-preview-placeholder');

  img.src = `https://cards.scryfall.io/normal/front/${scryfallId[0]}/${scryfallId[1]}/${scryfallId}.jpg`;
  img.classList.remove('hidden');
  emptyText.classList.add('hidden');
}

function clearCardPreview() {
  const img = document.getElementById('card-preview-img');
  const emptyText = document.getElementById('card-preview-placeholder');
  img.classList.add('hidden');
  img.src = '';
  emptyText.classList.remove('hidden');
}

function copyDecklistText() {
  if (!activeDeck) return;
  let text = '';

  for (const c of (activeDeck.mainboard || [])) {
    text += `${c.count} ${c.name}\n`;
  }

  if (activeDeck.sideboard && activeDeck.sideboard.length > 0) {
    text += `\nSideboard\n`;
    for (const c of activeDeck.sideboard) {
      text += `${c.count} ${c.name}\n`;
    }
  }

  navigator.clipboard.writeText(text.trim()).then(() => {
    alert(`Decklist for ${activeDeck.name} copied to clipboard!`);
  });
}