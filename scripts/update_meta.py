import json
import re
import asyncio
from playwright.async_api import async_playwright

MTGDECKS_PAUPER_URL = "https://mtgdecks.net/Pauper"
OUTPUT_PATH = "data/top_decks.json"

BASIC_LANDS = {
    "plains", "island", "swamp", "mountain", "forest",
    "snow-covered plains", "snow-covered island", "snow-covered swamp", 
    "snow-covered mountain", "snow-covered forest"
}

def clean_card_name(raw_name: str) -> str:
    name = re.sub(r"[.,]?\s*\$[\d\.]+(?:\s+[\d\.]+\s*(?:TIX|\$)?)?(?:\s+[CUMR])?.*$", "", raw_name, flags=re.IGNORECASE)
    name = name.split(" // ")[0].split(" / ")[0].strip()
    return name.rstrip(".,").strip()

def determine_dynamic_signature_card(archetype_name: str, decks: list) -> str:
    if not decks:
        return "Brainstorm"

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
            if word in low and word not in ["mono", "red", "blue", "black", "white", "green"]:
                score += 400
        return score

    sorted_cards = sorted(card_scores.items(), key=rank_card, reverse=True)
    return sorted_cards[0][0]

async def scrape_metagame():
    print("[*] Launching Chromium with headful emulation...")
    async with async_playwright() as p:
        browser = await p.chromium.launch(
            headless=False,
            args=[
                "--disable-blink-features=AutomationControlled",
                "--no-sandbox",
                "--disable-setuid-sandbox",
                "--disable-dev-shm-usage"
            ]
        )
        context = await browser.new_context(
            user_agent="Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36",
            locale="en-US",
            timezone_id="America/Los_Angeles",
            viewport={"width": 1920, "height": 1080},
            extra_http_headers={
                "Accept-Language": "en-US,en;q=0.9",
                "sec-ch-ua": '"Chromium";v="124", "Google Chrome";v="124", "Not-A.Brand";v="99"',
                "sec-ch-ua-mobile": "?0",
                "sec-ch-ua-platform": '"Windows"'
            }
        )
        page = await context.new_page()

        print(f"[*] Navigating to {MTGDECKS_PAUPER_URL}...")
        await page.goto(MTGDECKS_PAUPER_URL, wait_until="domcontentloaded", timeout=60000)
        await page.wait_for_timeout(3000)

        # 1. Scrape Top 10 Metagame Archetypes
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
                if (!name || name.