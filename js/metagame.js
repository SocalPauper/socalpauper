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
    const cardMemoryCache = {};

    // Load top_decks.json scraped by GitHub Actions
    try {
        const res = await fetch("data/top_decks.json");
        if (!res.ok) throw new Error(`HTTP error ${res.status}`);
        archetypesData = await res.json();
    } catch (err) {
        console.error("Failed to load data/top_decks.json:", err);
        if (metaContainer) {
            metaContainer.innerHTML = `<p class="error-msg">Metagame data is currently unavailable. Please check back shortly.</p>`;
        }
        return;
    }

    // Scryfall query helper with localStorage + in-memory caching
    async function getScryfallCard(cardName) {
        if (!cardName) return null;
        const normalized = cardName.trim().toLowerCase();

        if (cardMemoryCache[normalized]) {
            return cardMemoryCache[normalized];
        }

        const storageKey = `scry_${normalized}`;
        const cached = localStorage.getItem(storageKey);
        if (cached) {
            try {
                const parsed = JSON.parse(cached);
                cardMemoryCache[normalized] = parsed;
                return parsed;
            } catch {
                localStorage.removeItem(storageKey);
            }
        }

        try {
            const resp = await fetch(`https://api.scryfall.com/cards/named?fuzzy=${encodeURIComponent(cardName)}`);
            if (!resp.ok) return null;

            const data = await resp.json();
            const cardInfo = {
                image: data.image_uris?.normal || data.card_faces?.[0]?.image_uris?.normal || "",
                artCrop: data.image_uris?.art_crop || data.card_faces?.[0]?.image_uris?.art_crop || "",
                type: data.type_line || ""
            };

            localStorage.setItem(storageKey, JSON.stringify(cardInfo));
            cardMemoryCache[normalized] = cardInfo;
            return cardInfo;
        } catch (e) {
            console.warn(`Scryfall lookup failed for: ${cardName}`, e);
            return null;
        }
    }

    // Position tooltip safely within screen bounds
    function updateTooltipPosition(e) {
        if (!cardTooltip || cardTooltip.style.display !== "block") return;
        const offset = 18;
        const tooltipWidth = cardTooltip.offsetWidth || 220;
        const tooltipHeight = cardTooltip.offsetHeight || 310;

        let left = e.clientX + offset;
        let top = e.clientY + offset;

        if (left + tooltipWidth > window.innerWidth) {
            left = e.clientX - tooltipWidth - offset;
        }
        if (top + tooltipHeight > window.innerHeight) {
            top = window.innerHeight - tooltipHeight - 10;
        }
        if (top < 10) top = 10;

        cardTooltip.style.left = `${left}px`;
        cardTooltip.style.top = `${top}px`;
    }

    // 1. Render Tier 1: Top 10 Archetype Tiles
    function renderArchetypeGrid() {
        if (!metaContainer) return;
        metaContainer.innerHTML = "";

        archetypesData.forEach((arch, index) => {
            const cardTile = document.createElement("div");
            cardTile.className = "archetype-card";
            cardTile.setAttribute("data-id", arch.id);

            cardTile.innerHTML = `
                <div class="archetype-art-bg" id="art-${arch.id}"></div>
                <div class="archetype-info">
                    <div class="archetype-header-row">
                        <span class="archetype-rank">#${index + 1}</span>
                        <h3 class="archetype-name">${arch.name}</h3>
                    </div>
                    <div class="archetype-meta-badges">
                        <span class="badge meta-share">${arch.metaShare || "N/A"} Meta</span>
                        <span class="badge win-rate">${arch.winRate || "50.0%"} WR</span>
                    </div>
                    <p class="sample-count">${(arch.decks || []).length} Recorded Finishes</p>
                </div>
            `;

            // Dynamic translucent art background
            const sigCard = arch.signatureCard;
            if (sigCard) {
                getScryfallCard(sigCard).then(info => {
                    if (info?.artCrop) {
                        const bgEl = document.getElementById(`art-${arch.id}`);
                        if (bgEl) {
                            bgEl.style.backgroundImage = `url('${info.artCrop}')`;
                        }
                    }
                });
            }

            // Click listener -> Open Tier 2
            cardTile.addEventListener("click", () => {
                document.querySelectorAll(".archetype-card").forEach(c => c.classList.remove("active"));
                cardTile.classList.add("active");
                displaySubDecks(arch);
            });

            metaContainer.appendChild(cardTile);
        });
    }

    // 2. Render Tier 2: 10 Latest Tournament Decks for Selected Archetype
    function displaySubDecks(arch) {
        if (!subdecksSection || !subdecksTableBody) return;

        subdecksSection.classList.remove("hidden");
        if (subdecksTitle) {
            subdecksTitle.innerText = `${arch.name} — Recent Tournament Finishes`;
        }
        subdecksTableBody.innerHTML = "";

        const decks = arch.decks || [];
        if (decks.length === 0) {
            subdecksTableBody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-4">No recent tournament finishes found for this archetype.</td>
                </tr>
            `;
            return;
        }

        decks.forEach((deck) => {
            const tr = document.createElement("tr");
            tr.className = "deck-row-clickable";

            const rawPlacement = (deck.placement || "Finish").toLowerCase().replace(/[^a-z0-9]/g, "");
            const pilotDisplay = deck.user || deck.submitter || deck.pilot || "Anonymous";

            tr.innerHTML = `
                <td class="col-date"><strong>${deck.date || "Recent"}</strong></td>
                <td class="col-placement"><span class="placement-pill placement-${rawPlacement}">${deck.placement || "Placed"}</span></td>
                <td class="col-pilot"><span class="pilot-name">${pilotDisplay}</span></td>
                <td class="col-record">${deck.record || "—"}</td>
                <td class="col-attendance">${deck.players ? `${deck.players} Players` : "League"}</td>
                <td class="col-event"><span class="event-name">${deck.eventName || "Pauper Event"}</span></td>
            `;

            // Click listener -> Open Tier 3 Modal
            tr.addEventListener("click", () => openDeckModal(arch, deck));
            subdecksTableBody.appendChild(tr);
        });

        subdecksSection.scrollIntoView({ behavior: "smooth", block: "start" });
    }

    // 3. Render Tier 3: Categorized 75-Card Decklist Modal
    async function openDeckModal(arch, deck) {
        if (!modal || !modalCardsContainer) return;

        const pilotDisplay = deck.user || deck.submitter || deck.pilot || "Anonymous";
        if (modalDeckTitle) {
            modalDeckTitle.innerText = `${arch.name} — ${pilotDisplay}`;
        }
        if (modalDeckMeta) {
            modalDeckMeta.innerText = `${deck.eventName || "Pauper Event"} · ${deck.placement || "Placed"} (${deck.record || "N/A"}) · ${deck.date || "Recent"}`;
        }

        modalCardsContainer.innerHTML = `<div class="loading-spinner">Categorizing decklist via Scryfall...</div>`;
        modal.classList.remove("hidden");
        document.body.style.overflow = "hidden";

        // Categorize mainboard cards by type
        const categories = {
            "Creatures": [],
            "Spells": [],
            "Artifacts": [],
            "Enchantments": [],
            "Lands": []
        };

        const mainboard = deck.mainboard || [];
        const sideboard = deck.sideboard || [];

        // Resolve types in parallel
        await Promise.all(
            mainboard.map(async (card) => {
                const scry = await getScryfallCard(card.name);
                const type = (scry?.type || "").toLowerCase();

                if (type.includes("creature")) {
                    categories["Creatures"].push(card);
                } else if (type.includes("land")) {
                    categories["Lands"].push(card);
                } else if (type.includes("artifact")) {
                    categories["Artifacts"].push(card);
                } else if (type.includes("enchantment")) {
                    categories["Enchantments"].push(card);
                } else {
                    categories["Spells"].push(card);
                }
            })
        );

        modalCardsContainer.innerHTML = "";

        function createColumn(sectionTitle, cards) {
            if (!cards || cards.length === 0) return;
            const countSum = cards.reduce((sum, c) => sum + (c.count || 1), 0);

            const col = document.createElement("div");
            col.className = "deck-type-column";
            col.innerHTML = `<h4 class="column-header">${sectionTitle} <span class="type-count">(${countSum})</span></h4>`;

            const ul = document.createElement("ul");
            ul.className = "card-list";

            cards.forEach((card) => {
                const li = document.createElement("li");
                li.className = "card-item-row";
                li.innerHTML = `
                    <span class="card-count">${card.count}</span>
                    <span class="card-name-hover" data-name="${card.name}">${card.name}</span>
                `;

                // Scryfall Hover Previews
                li.addEventListener("mouseenter", async (e) => {
                    const info = await getScryfallCard(card.name);
                    if (info?.image && cardTooltip) {
                        cardTooltip.src = info.image;
                        cardTooltip.style.display = "block";
                        updateTooltipPosition(e);
                    }
                });

                li.addEventListener("mousemove", updateTooltipPosition);

                li.addEventListener("mouseleave", () => {
                    if (cardTooltip) cardTooltip.style.display = "none";
                });

                ul.appendChild(li);
            });

            col.appendChild(ul);
            modalCardsContainer.appendChild(col);
        }

        createColumn("Creatures", categories["Creatures"]);
        createColumn("Spells", categories["Spells"]);
        createColumn("Artifacts", categories["Artifacts"]);
        createColumn("Enchantments", categories["Enchantments"]);
        createColumn("Lands", categories["Lands"]);
        createColumn("Sideboard", sideboard);
    }

    // Modal Close Logic
    function closeModal() {
        if (!modal) return;
        modal.classList.add("hidden");
        document.body.style.overflow = "";
        if (cardTooltip) cardTooltip.style.display = "none";
    }

    if (modalCloseBtn) {
        modalCloseBtn.addEventListener("click", closeModal);
    }

    if (modal) {
        modal.addEventListener("click", (e) => {
            if (e.target === modal) closeModal();
        });
    }

    window.addEventListener("keydown", (e) => {
        if (e.key === "Escape" && modal && !modal.classList.contains("hidden")) {
            closeModal();
        }
    });

    // Initialize display
    renderArchetypeGrid();
});