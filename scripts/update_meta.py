import json
import re
import asyncio
from playwright.async_api import async_playwright

MTGDECKS_PAUPER_URL = "https://mtgdecks.net/Pauper"
OUTPUT_PATH = "data/top_decks.json"

SIGNATURE_CARDS = {
    "mono-red-madness": "Sneaky Snacker",
    "elves": "Priest of Titania",
    "mono-blue-terror": "Tolarian Terror",
    "rogue": "Slither Blade",
    "grixis-affinity": "Blood Fountain",
    "esper-affinity": "Refurbished Familiar",
    "monster-tron": "Myr Enforcer",
    "spy-combo": "Balustrade Spy",
    "mono-red-rally": "Kuldotha Rebirth",
    "ub-faeries": "Spellstutter Sprite",
    "dimir-control": "Cast Down",
    "gw-bogles": "Slippery Bogle",
    "white-weenie": "Thraben Inspector",
    "jund-wildfire": "Cleansing Wildfire",
    "golgari-gardens": "Crypt Rats"
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

        # 1. Scrape Top 10 Metagame Archetypes
        archetypes = await page.evaluate('''() => {
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
                if (/\\/deck\\/|decklist|winrates|staples|metagame:/i.test(href)) continue;

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
        }''')

        # Filter out utility links and limit to top 10
        unique_archetypes = []
        for a in archetypes:
            if not any(x["id"] == a["id"] for x in unique_archetypes):
                unique_archetypes.append(a)
            if len(unique_archetypes) >= 10:
                break

        print(f"[✓] Found Top {len(unique_archetypes)} Archetypes:")
        for idx, a in enumerate(unique_archetypes, 1):
            print(f"    {idx}. {a['name']} ({a['metaShare']})")

        output_archetypes = []

        # 2. Iterate each archetype and scrape its 10 latest tournament decks
        for arch_idx, arch in enumerate(unique_archetypes, start=1):
            print(f"\n[{arch_idx}/10] Scraping tournament history for: {arch['name']}...")
            try:
                await page.goto(arch["url"], wait_until="domcontentloaded", timeout=45000)
                await page.wait_for_timeout(2500)

                # Extract the 10 latest tournament placements from table
                deck_entries = await page.evaluate('''() => {
                    const items = [];
                    const rows = document.querySelectorAll("table tr");

                    for (const row of rows) {
                        const a = row.querySelector("a[href*='-decklist-by-'], a[href*='/deck/']");
                        if (!a) continue;

                        const href = a.getAttribute("href") || "";
                        const rowText = row.innerText;
                        const cells = Array.from(row.querySelectorAll("td")).map(td => td.innerText.trim());

                        // Date (e.g. 25-Oct-2026 or 2026-10-25)
                        let dateStr = "";
                        const dateMatch = rowText.match(/\\b\\d{1,2}-[A-Za-z]{3}(?:-\\d{4})?\\b|\\b\\d{4}-\\d{2}-\\d{2}\\b/);
                        if (dateMatch) dateStr = dateMatch[0];

                        // Placement (1st, 2nd, Top4, Top8, 5-0)
                        let placement = "Top 8";
                        if (/1st|winner|champion/i.test(rowText)) placement = "1st";
                        else if (/2nd|finalist/i.test(rowText)) placement = "2nd";
                        else if (/top\\s*4|semifinal/i.test(rowText)) placement = "Top 4";
                        else if (/top\\s*8/i.test(rowText)) placement = "Top 8";
                        else if (/top\\s*16/i.test(rowText)) placement = "Top 16";
                        else if (/5\\s*-\\s*0/i.test(rowText)) placement = "5-0";

                        // Record (W-L or W-L-T)
                        let record = "";
                        const recordMatch = rowText.match(/\\((\\d{1,2}\\s*-\\s*\\d{1,2}(?:\\s*-\\s*\\d{1,2})?)\\)/);
                        if (recordMatch) {
                            record = recordMatch[1].replace(/\\s+/g, "");
                        } else {
                            const rawRecord = rowText.match(/\\b(\\d{1,2}\\s*-\\s*\\d{1,2})\\b/);
                            if (rawRecord) record = rawRecord[1].replace(/\\s+/g, "");
                        }

                        // Event Name
                        let eventName = "Pauper Tournament";
                        for (const cell of cells) {
                            if (/challenge|league|championship|open|showcase|scg|qualifier|fnm|battle|autumn|stage/i.test(cell)) {
                                eventName = cell.split("\\n")[0].trim();
                                break;
                            }
                        }

                        // Players Count
                        let players = "32+";
                        const pMatch = rowText.match(/(\\d{1,3})\\s*(?:players|player|p\\b)/i);
                        if (pMatch) players = pMatch[1];
                        else if (/challenge/i.test(rowText)) players = "64";
                        else if (/showcase/i.test(rowText)) players = "128";
                        else if (/league/i.test(rowText)) players = "League";

                        // Pilot
                        let pilot = a.innerText.trim();
                        const byMatch = rowText.match(/by\\s+([^\\n·,]+)/i);
                        if (byMatch) pilot = byMatch[1].trim();

                        items.push({
                            title: a.innerText.trim() || pilot,
                            pilot: pilot,
                            url: href.startsWith("http") ? href : "https://mtgdecks.net" + href,
                            date: dateStr,
                            placement: placement,
                            record: record,
                            eventName: eventName,
                            players: players
                        });

                        if (items.length >= 10) break;
                    }
                    return items;
                }''')

                print(f"    Found {len(deck_entries)} tournament placement entries.")
                scraped_decks = []

                for d_idx, deck_entry in enumerate(deck_entries, start=1):
                    try:
                        print(f"      [{d_idx}/{len(deck_entries)}] Pulling list: {deck_entry['pilot']} ({deck_entry['placement']})...")
                        await page.goto(deck_entry["url"], wait_until="domcontentloaded", timeout=30000)
                        await page.wait_for_timeout(1500)

                        # Try clicking plaintext export modal
                        try:
                            btn = page.locator("a:has-text('Export'), button:has-text('Export'), .export-deck")
                            if await btn.count() > 0:
                                await btn.first.click()
                                await page.wait_for_timeout(800)
                        except Exception:
                            pass

                        raw_txt = await page.evaluate('''() => {
                            const tas = document.querySelectorAll("textarea");
                            for (const t of tas) {
                                if (t.value && t.value.length > 50 && /\\d+\\s+[A-Za-z]/.test(t.value)) {
                                    return t.value;
                                }
                            }
                            return null;
                        }''')

                        mainboard, sideboard = [], []
                        if raw_txt:
                            mainboard, sideboard = parse_deck_text(raw_txt)

                        # DOM card table fallback
                        if not mainboard:
                            card_data = await page.evaluate('''() => {
                                const main = [];
                                const side = [];
                                let isSide = false;
                                const rows = document.querySelectorAll("tr, li, .card-item");
                                for (const row of rows) {
                                    const text = row.innerText.trim();
                                    if (/\\b(Sideboard|Side)\\b/i.test(text) && !row.querySelector("a")) {
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
                                        if (/^\\d{1,2}$/.test(v)) { qty = parseInt(v, 10); break; }
                                    }
                                    if (!qty) {
                                        const m = text.match(/^(\\d{1,2})\\b/);
                                        if (m) qty = parseInt(m[1], 10);
                                    }
                                    if (qty && qty >= 1 && qty <= 30) {
                                        const t = isSide ? side : main;
                                        if (!t.some(x => x.name === cname)) t.push({ name: cname, count: qty });
                                    }
                                }
                                return { main, side };
                            }''')
                            mainboard = [{"name": clean_card_name(c["name"]), "count": c["count"]} for c in card_data["main"]]
                            sideboard = [{"name": clean_card_name(c["name"]), "count": c["count"]} for c in card_data["side"]]

                        if mainboard:
                            scraped_decks.append({
                                "id": f"{arch['id']}-{d_idx}",
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

                # Choose signature key card for archetype artwork
                sig_card = SIGNATURE_CARDS.get(arch["id"])
                if not sig_card and scraped_decks and scraped_decks[0]["mainboard"]:
                    sig_card = scraped_decks[0]["mainboard"][0]["name"]

                output_archetypes.append({
                    "id": arch["id"],
                    "name": arch["name"],
                    "metaShare": arch["metaShare"],
                    "winRate": arch["winRate"],
                    "signatureCard": sig_card or "Brainstorm",
                    "decks": scraped_decks
                })

            except Exception as e:
                print(f"    [!] Error loading archetype {arch['name']}: {e}")

        await browser.close()

        if len(output_archetypes) < 4:
            raise RuntimeError(f"Retrieved only {len(output_archetypes)} archetypes. Halting update.")

        with open(OUTPUT_PATH, "w", encoding="utf-8") as f:
            json.dump(output_archetypes, f, indent=2, ensure_ascii=False)

        print(f"\n[✓] Successfully compiled {len(output_archetypes)} archetypes and their tournament decks into {OUTPUT_PATH}")

if __name__ == "__main__":
    asyncio.run(scrape_metagame())
