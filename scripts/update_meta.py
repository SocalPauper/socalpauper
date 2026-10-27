import json
import re
import asyncio
from playwright.async_api import async_playwright

MTGDECKS_PAUPER_URL = "https://mtgdecks.net/Pauper"
OUTPUT_PATH = "data/top_decks.json"

EXCLUDED_SLUGS = {
    "pauperstaples", "winrates", "staples", "mtgo", "meta", "major", 
    "events", "tournaments", "filter", "decklists", "price", "archetype", "decks", "articles"
}

BASIC_LANDS = {
    "plains", "island", "swamp", "mountain", "forest",
    "snow-covered plains", "snow-covered island", "snow-covered swamp", 
    "snow-covered mountain", "snow-covered forest"
}

def clean_card_name(raw_name: str) -> str:
    name = re.sub(r"\.\s*\$[\d\.]+", "", raw_name)
    name = re.sub(r"\$[\d\.]+", "", name)
    name = re.sub(r"[\d\.]+\s*TIX", "", name, flags=re.IGNORECASE)
    name = name.split(" // ")[0].split(" / ")[0].strip()
    return name.rstrip(".")

def parse_deck_text(raw_text: str):
    mainboard, sideboard = [], []
    is_side = False

    for line in raw_text.splitlines():
        line = line.strip()
        if not line or line.startswith("//"):
            continue
        if re.search(r"^(sideboard|side|sb:)", line, re.IGNORECASE):
            is_side = True
            continue
        m = re.match(r"^(\d+)[xX]?\s+(.+)$", line)
        if m:
            qty = int(m.group(1))
            cname = clean_card_name(m.group(2))
            if len(cname) > 1 and cname.lower() not in ["lands", "creatures", "spells", "artifacts", "instants", "sorceries"]:
                target = sideboard if is_side else mainboard
                target.append({"name": cname, "count": qty})

    return mainboard, sideboard

def determine_dynamic_signature_card(archetype_name: str, decks: list, page_staple_card: str = None) -> str:
    if page_staple_card and page_staple_card.lower() not in BASIC_LANDS and len(page_staple_card) > 2:
        return page_staple_card

    if not decks:
        words = [w for w in archetype_name.split() if w.lower() not in ["mono", "red", "blue", "black", "white", "green", "combo", "rally", "tron"]]
        return words[0] if words else "Brainstorm"

    card_scores = {}
    arch_words = [w.lower() for w in re.findall(r"[a-zA-Z]{3,}", archetype_name)]

    for d in decks:
        seen = set()
        for c in d.get("mainboard", []):
            name = c["name"]
            low = name.lower()
            if low in BASIC_LANDS or len(name) < 3:
                continue

            if name not in card_scores:
                card_scores[name] = {"count": 0, "deck_count": 0}

            card_scores[name]["count"] += c["count"]
            if name not in seen:
                card_scores[name]["deck_count"] += 1
                seen.add(name)

    if not card_scores:
        return "Brainstorm"

    def rank_card(item):
        name, stats = item
        low = name.lower()
        score = (stats["deck_count"] * 100) + stats["count"]
        for word in arch_words:
            if word in low:
                score += 300
        return score

    sorted_cards = sorted(card_scores.items(), key=rank_card, reverse=True)
    return sorted_cards[0][0]

async def scrape_metagame():
    print("[*] Launching Chromium via Playwright...")
    async with async_playwright() as p:
        browser = await p.chromium.launch(headless=True)
        context = await browser.new_context(
            user_agent="Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36",
            viewport={"width": 1920, "height": 1080}
        )
        page = await context.new_page()

        print(f"[*] Navigating to {MTGDECKS_PAUPER_URL}...")
        await page.goto(MTGDECKS_PAUPER_URL, wait_until="domcontentloaded", timeout=60000)
        await page.wait_for_timeout(3000)

        # 1. Scrape Top 10 Archetypes
        archetypes = await page.evaluate(r"""() => {
            const results = [];
            const tables = Array.from(document.querySelectorAll("table"));
            let metaTable = null;
            for (const table of tables) {
                const text = table.innerText.toLowerCase();
                if ((text.includes("archetype") || text.includes("deck")) && (text.includes("meta") || text.includes("%"))) {
                    metaTable = table;
                    break;
                }
            }
            if (!metaTable && tables.length > 0) metaTable = tables[0];

            const rows = metaTable ? Array.from(metaTable.querySelectorAll("tr")) : [];
            for (const tr of rows) {
                const a = tr.querySelector("a[href*='/Pauper/']");
                if (!a) continue;

                const href = a.getAttribute("href") || "";
                if (/\/deck\/|decklist|winrates|staples|metagame:/i.test(href)) continue;

                const slug = href.split("/").pop().toLowerCase();
                const name = a.innerText.trim();
                if (!name || name.length < 3 || /^(pauper|decks|articles|filter)$/i.test(slug)) continue;

                const tds = Array.from(tr.querySelectorAll("td")).map(td => td.innerText.trim());
                let metaShare = "5.0%";
                let winRate = "52.0%";

                for (const t of tds) {
                    if (t.includes("%") && metaShare === "5.0%") {
                        metaShare = t;
                    } else if (t.includes("%") && winRate === "52.0%") {
                        winRate = t;
                    }
                }

                results.push({
                    id: slug,
                    name: name,
                    url: href.startsWith("http") ? href : "https://mtgdecks.net" + href,
                    metaShare: metaShare,
                    winRate: winRate
                });
            }
            return results;
        }""")

        unique_archetypes = []
        for a in archetypes:
            if not any(x["id"] == a["id"] for x in unique_archetypes):
                unique_archetypes.append(a)
            if len(unique_archetypes) >= 10:
                break

        print(f"[✓] Identified Top {len(unique_archetypes)} Archetypes:")
        for idx, a in enumerate(unique_archetypes, 1):
            print(f"    {idx}. {a['name']} ({a['metaShare']})")

        output_archetypes = []

        # 2. Iterate each Archetype
        for arch_idx, arch in enumerate(unique_archetypes, start=1):
            print(f"\n[{arch_idx}/10] Visiting archetype: {arch['name']} ({arch['url']})...")
            try:
                await page.goto(arch["url"], wait_until="domcontentloaded", timeout=45000)
                await page.wait_for_timeout(2500)

                # Extract dynamic staple card & 10 tournament placement finishes
                page_data = await page.evaluate(r"""(archSlug) => {
                    const allAnchors = Array.from(document.querySelectorAll("a[href]"));

                    // A. Signature staple card detection
                    let topStapleCard = null;
                    const possibleCardLinks = document.querySelectorAll("a[href*='/cards/'], a[href*='/card/'], td a");
                    for (const ca of possibleCardLinks) {
                        const txt = ca.innerText.trim();
                        if (txt.length > 3 && !/mountain|island|swamp|plains|forest|pauper|decks|articles|filter|more|view/i.test(txt)) {
                            topStapleCard = txt;
                            break;
                        }
                    }

                    // B. Target tournament deck links (contains 'decklist', excluding '-stats-' and '-swing-')
                    const deckItems = [];

                    for (const a of allAnchors) {
                        const href = a.getAttribute("href") || "";
                        if (href.includes("-stats-") || href.includes("-swing-")) continue;

                        const isTournamentDeck = href.includes("decklist") || /\/deck\/\d+/i.test(href);

                        if (isTournamentDeck) {
                            const fullUrl = href.startsWith("http") ? href : "https://mtgdecks.net" + href;
                            if (deckItems.some(x => x.url === fullUrl)) continue;

                            const row = a.closest("tr") || a.parentElement;
                            const rowText = row ? row.innerText : "";
                            const cells = row ? Array.from(row.querySelectorAll("td")).map(td => td.innerText.trim()) : [];

                            // Submitter / User / Pilot
                            let pilot = "";
                            const byMatch = rowText.match(/(?:by|builder:?)\s+([^\n\r·,()]+)/i);
                            if (byMatch) {
                                pilot = byMatch[1].trim();
                            } else {
                                const urlByMatch = href.match(/-decklist-by-([^-]+)-\d+/i);
                                if (urlByMatch) {
                                    pilot = urlByMatch[1].trim();
                                } else {
                                    pilot = a.innerText.trim() || "Anonymous";
                                }
                            }

                            // Date
                            let dateStr = "";
                            const dateMatch = rowText.match(/\b\d{1,2}-[A-Za-z]{3}(?:-\d{4})?\b|\b\d{4}-\d{2}-\d{2}\b/);
                            if (dateMatch) dateStr = dateMatch[0];

                            // Placement
                            let placement = "Top 8";
                            if (/1st|winner|champion/i.test(rowText)) placement = "1st";
                            else if (/2nd|finalist/i.test(rowText)) placement = "2nd";
                            else if (/top\s*4|semifinal/i.test(rowText)) placement = "Top 4";
                            else if (/top\s*8/i.test(rowText)) placement = "Top 8";
                            else if (/top\s*16/i.test(rowText)) placement = "Top 16";
                            else if (/5\s*-\s*0/i.test(rowText)) placement = "5-0";

                            // Record
                            let record = "";
                            const recordMatch = rowText.match(/\((\d{1,2}\s*-\s*\d{1,2}(?:\s*-\s*\d{1,2})?)\)/);
                            if (recordMatch) {
                                record = recordMatch[1].replace(/\s+/g, "");
                            } else {
                                const rawRecord = rowText.match(/\b(\d{1,2}\s*-\s*\d{1,2})\b/);
                                if (rawRecord) record = rawRecord[1].replace(/\s+/g, "");
                            }

                            // Event name
                            let eventName = "Pauper Tournament";
                            for (const cell of cells) {
                                if (/challenge|league|championship|open|showcase|scg|qualifier|fnm|battle|autumn|stage|last sun/i.test(cell)) {
                                    eventName = cell.split("\n")[0].trim();
                                    break;
                                }
                            }

                            // Attendance
                            let players = "32+";
                            const pMatch = rowText.match(/(\d{1,3})\s*(?:players|player|p\b)/i);
                            if (pMatch) players = pMatch[1];
                            else if (/challenge/i.test(rowText)) players = "64";
                            else if (/showcase/i.test(rowText)) players = "128";
                            else if (/league/i.test(rowText)) players = "League";

                            deckItems.push({
                                user: pilot,
                                submitter: pilot,
                                pilot: pilot,
                                url: fullUrl,
                                date: dateStr,
                                placement: placement,
                                record: record,
                                eventName: eventName,
                                players: players
                            });

                            if (deckItems.length >= 10) break;
                        }
                    }

                    return {
                        topStapleCard: topStapleCard,
                        deckItems: deckItems
                    };
                }""", arch["id"])

                deck_entries = page_data.get("deckItems", [])
                top_staple = page_data.get("topStapleCard")
                print(f"    Found {len(deck_entries)} tournament placement decks.")

                scraped_decks = []

                for d_idx, deck_entry in enumerate(deck_entries, start=1):
                    try:
                        print(f"      [{d_idx}/{len(deck_entries)}] Parsing list by {deck_entry['user']} ({deck_entry['placement']})...")
                        await page.goto(deck_entry["url"], wait_until="domcontentloaded", timeout=30000)
                        await page.wait_for_timeout(1200)

                        # Plaintext export modal
                        try:
                            btn = page.locator("a:has-text('Export'), button:has-text('Export'), .export-deck")
                            if await btn.count() > 0:
                                await btn.first.click()
                                await page.wait_for_timeout(600)
                        except Exception:
                            pass

                        raw_txt = await page.evaluate(r"""() => {
                            const tas = document.querySelectorAll("textarea");
                            for (const t of tas) {
                                if (t.value && t.value.length > 50 && /\d+\s+[A-Za-z]/.test(t.value)) {
                                    return t.value;
                                }
                            }
                            return null;
                        }""")

                        mainboard, sideboard = [], []
                        if raw_txt:
                            mainboard, sideboard = parse_deck_text(raw_txt)

                        # DOM card table fallback
                        if not mainboard:
                            card_data = await page.evaluate(r"""() => {
                                const main = [];
                                const side = [];
                                let isSide = false;
                                const rows = document.querySelectorAll("tr, li, .card-item");
                                for (const row of rows) {
                                    const text = row.innerText.trim();
                                    if (/\b(Sideboard|Side)\b/i.test(text) && !row.querySelector("a")) {
                                        isSide = true;
                                        continue;
                                    }
                                    const a = row.querySelector("a[href*='/cards/'], a[href*='/card/'], a");
                                    if (!a) continue;
                                    const cname = a.innerText.trim();
                                    if (!cname || cname.length < 2) continue;
                                    if (["lands", "creatures", "spells", "artifacts", "instants", "sorceries"].includes(cname.toLowerCase())) continue;

                                    let qty = null;
                                    const tds = row.querySelectorAll("td");
                                    for (const td of tds) {
                                        const v = td.innerText.trim();
                                        if (/^\d{1,2}$/.test(v)) { qty = parseInt(v, 10); break; }
                                    }
                                    if (!qty) {
                                        const m = text.match(/^(\d{1,2})\b/);
                                        if (m) qty = parseInt(m[1], 10);
                                    }
                                    if (qty && qty >= 1 && qty <= 30) {
                                        const t = isSide ? side : main;
                                        if (!t.some(x => x.name === cname)) t.push({ name: cname, count: qty });
                                    }
                                }
                                return { main, side };
                            }""")
                            mainboard = [{"name": clean_card_name(c["name"]), "count": c["count"]} for c in card_data["main"]]
                            sideboard = [{"name": clean_card_name(c["name"]), "count": c["count"]} for c in card_data["side"]]

                        if mainboard:
                            scraped_decks.append({
                                "id": f"{arch['id']}-{d_idx}",
                                "user": deck_entry["user"],
                                "submitter": deck_entry["submitter"],
                                "pilot": deck_entry["pilot"],
                                "eventName": deck_entry["eventName"],
                                "date": deck_entry["date"] or "Recent",
                                "placement": deck_entry["placement"],
                                "record": deck_entry["record"] or "N/A",
                                "players": deck_entry["players"],
                                "url": deck_entry["url"],
                                "mainboard": mainboard,
                                "sideboard": sideboard
                            })
                    except Exception as e:
                        print(f"        [!] Error scraping {deck_entry['url']}: {e}")

                dynamic_sig_card = determine_dynamic_signature_card(arch["name"], scraped_decks, top_staple)
                print(f"    [★] Dynamic Signature Card: {dynamic_sig_card}")

                output_archetypes.append({
                    "id": arch["id"],
                    "name": arch["name"],
                    "metaShare": arch["metaShare"],
                    "winRate": arch["winRate"],
                    "signatureCard": dynamic_sig_card,
                    "decks": scraped_decks
                })

            except Exception as e:
                print(f"    [!] Error loading archetype {arch['name']}: {e}")

        await browser.close()

        with open(OUTPUT_PATH, "w", encoding="utf-8") as f:
            json.dump(output_archetypes, f, indent=2, ensure_ascii=False)

        total_scraped_lists = sum(len(a["decks"]) for a in output_archetypes)
        print(f"\n[✓] Compiled {len(output_archetypes)} archetypes with {total_scraped_lists} total tournament decklists into {OUTPUT_PATH}")

if __name__ == "__main__":
    asyncio.run(scrape_metagame())