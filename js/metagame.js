document.addEventListener("DOMContentLoaded", async () => {
    const metaContainer = document.getElementById("meta-archetypes-grid");
    const subdecksSection = document.getElementById("subdecks-section");
    const subdecksTitle = document.getElementById("subdecks-archetype-title");
    const subdecksTableBody = document.getElementById("subdecks-table-body");
    const modal = document.getElementById("deck-modal");
    const modalCloseBtn = document.getElementById("deck-modal-close");
    const modalDeckTitle = document.getElementById("modal-deck-title");
    const modalDeckMeta = document.getElementById("modal-deck-meta");
    const modalCardsContainer = document.getElementById("modal-cards-container");
    const cardTooltip = document.getElementById("scryfall-card-tooltip");

    let archetypesData = [];
    const cardImageCache = {};

    try {
        const res = await fetch("/data/top_decks.json");
        archetypesData = await res.json();
    } catch (e) {
        console.error("Failed to load top_decks.json", e);
        return;
    }

    // Helper: Fetch Scryfall card info (cached in localStorage)
    async function getScryfallCard(cardName) {
        if (cardImageCache[cardName]) return cardImageCache[cardName];
        const local = localStorage.getItem(`scry_${cardName}`);
        if (local) {
            const parsed = JSON.parse(local);
            cardImageCache[cardName] = parsed;
            return parsed;
        }
        try {
            const resp = await fetch(`https://api.scryfall.com/cards/named?fuzzy=${encodeURIComponent(cardName)}`);
            if (!resp.ok) return null;
            const data = await resp.json();
            const cardInfo = {
                image: data.image_uris?.normal || data.card_faces?.[0]?.image_uris?.normal,
                artCrop: data.image_uris?.art_crop || data.card_faces?.[0]?.image_uris?.art_crop,
                type: data.type_line || ""
            };
            localStorage.setItem(`scry_${cardName}`, JSON.stringify(cardInfo));
            cardImageCache[cardName] = cardInfo;
            return cardInfo;
        } catch {
            return null;
        }
    }

    // 1. Render Top 10 Archetype Tiles with Opaque Card Art Backgrounds
    metaContainer.innerHTML = "";
    for (const arch of archetypesData) {
        const cardTile = document.createElement("div");
        cardTile.className = "archetype-card";
        cardTile.innerHTML = `
            <div class="archetype-art-bg" id="bg-${arch.id}"></div>
            <div class="archetype-info">
                <h3>${arch.name}</h3>
                <div class="archetype-meta-badges">
                    <span class="badge meta-share">${arch.metaShare} Meta</span>
                    <span class="badge win-rate">${arch.winRate} WR</span>
                </div>
                <p class="sample-count">${arch.decks?.length || 0} Recent Decks</p>
            </div>
        `;

        // Load background art asynchronously
        if (arch.signatureCard) {
            getScryfallCard(arch.signatureCard).then(info => {
                if (info?.artCrop) {
                    const bgEl = document.getElementById(`bg-${arch.id}`);
                    if (bgEl) bgEl.style.backgroundImage = `url('${info.artCrop}')`;
                }
            });
        }

        // On Click Archetype -> Show its 10 latest decks
        cardTile.addEventListener("click", () => {
            document.querySelectorAll(".archetype-card").forEach(c => c.classList.remove("active"));
            cardTile.classList.add("active");
            displaySubDecks(arch);
        });

        metaContainer.appendChild(cardTile);
    }

    // 2. Render 10 Latest Tournament Decks for Selected Archetype
    function displaySubDecks(arch) {
        subdecksSection.classList.remove("hidden");
        subdecksTitle.innerText = `${arch.name} — Recent Tournament Decks`;
        subdecksTableBody.innerHTML = "";

        if (!arch.decks || arch.decks.length === 0) {
            subdecksTableBody.innerHTML = `<tr><td colspan="6" class="text-center">No recent tournament lists available.</td></tr>`;
            return;
        }

        arch.decks.forEach(deck => {
            const tr = document.createElement("tr");
            tr.className = "deck-row-clickable";
            tr.innerHTML = `
                <td><strong>${deck.date}</strong></td>
                <td><span class="placement-pill placement-${deck.placement.toLowerCase().replace(/[^a-z0-9]/g, '')}">${deck.placement}</span></td>
                <td>${deck.pilot}</td>
                <td>${deck.record}</td>
                <td>${deck.players} Players</td>
                <td><span class="event-name">${deck.eventName}</span></td>
            `;

            tr.addEventListener("click", () => openDeckModal(arch, deck));
            subdecksTableBody.appendChild(tr);
        });

        subdecksSection.scrollIntoView({ behavior: "smooth" });
    }

    // 3. Render Categorized 75-Card Decklist Modal
    async function openDeckModal(arch, deck) {
        modalDeckTitle.innerText = `${arch.name} (${deck.pilot})`;
        modalDeckMeta.innerText = `${deck.eventName} · ${deck.placement} (${deck.record}) · ${deck.date}`;
        modalCardsContainer.innerHTML = "<p>Loading categorized decklist...</p>";
        modal.classList.remove("hidden");

        // Categorize mainboard cards
        const categories = {
            "Creatures": [],
            "Spells": [],
            "Artifacts & Enchantments": [],
            "Lands": []
        };

        for (const card of deck.mainboard) {
            const scry = await getScryfallCard(card.name);
            const type = (scry?.type || "").toLowerCase();

            if (type.includes("creature")) categories["Creatures"].push(card);
            else if (type.includes("land")) categories["Lands"].push(card);
            else if (type.includes("artifact") || type.includes("enchantment")) categories["Artifacts & Enchantments"].push(card);
            else categories["Spells"].push(card);
        }

        modalCardsContainer.innerHTML = "";

        function createSection(title, list) {
            if (!list || list.length === 0) return;
            const totalCount = list.reduce((sum, c) => sum + c.count, 0);
            const sec = document.createElement("div");
            sec.className = "deck-type-column";
            sec.innerHTML = `<h4>${title} (${totalCount})</h4>`;
            const ul = document.createElement("ul");

            list.forEach(card => {
                const li = document.createElement("li");
                li.className = "card-list-item";
                li.innerHTML = `<span class="card-count">${card.count}</span> <span class="card-name-hover">${card.name}</span>`;

                // Scryfall Hover Preview
                li.addEventListener("mouseenter", async (e) => {
                    const info = await getScryfallCard(card.name);
                    if (info?.image) {
                        cardTooltip.src = info.image;
                        cardTooltip.style.display = "block";
                        cardTooltip.style.top = `${e.clientY + 15}px`;
                        cardTooltip.style.left = `${e.clientX + 20}px`;
                    }
                });
                li.addEventListener("mousemove", (e) => {
                    cardTooltip.style.top = `${e.clientY + 15}px`;
                    cardTooltip.style.left = `${e.clientX + 20}px`;
                });
                li.addEventListener("mouseleave", () => {
                    cardTooltip.style.display = "none";
                });

                ul.appendChild(li);
            });
            sec.appendChild(ul);
            modalCardsContainer.appendChild(sec);
        }

        createSection("Creatures", categories["Creatures"]);
        createSection("Spells", categories["Spells"]);
        createSection("Artifacts & Enchantments", categories["Artifacts & Enchantments"]);
        createSection("Lands", categories["Lands"]);
        createSection("Sideboard", deck.sideboard);
    }

    // Modal Close
    modalCloseBtn.addEventListener("click", () => modal.classList.add("hidden"));
    modal.addEventListener("click", (e) => {
        if (e.target === modal) modal.classList.add("hidden");
    });
});
