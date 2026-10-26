// ==========================================================================
// GLOBAL CONFIGURATION & MASTER LINKS
// ==========================================================================
const GLOBAL_CONFIG = {
  ADMIN_PIN: "Mphase2027!",
  LINKS: {
    DISCORD: "https://discord.gg/eQdgz5u5NE",
    ENDSTEP: "https://endstep.cc",
    MTGTOP8: "https://mtgtop8.com/format?f=PAU",
    ENDSTEPMETA: "https://endstep.cc/metagame/Pauper",
    MTGDECKS: "https://mtgdecks.net/Pauper",
    LIFECOUNTER: "/life.html",
    NEWS: "/news.html",
    TIPJAR: "https://buymeacoffee.com/socalpauper"
  }
};

function handleResourceClick(linkKey) {
  const targetUrl = GLOBAL_CONFIG.LINKS[linkKey];
  if (targetUrl) window.open(targetUrl, '_blank', 'noopener,noreferrer');
}

function escapeHTML(str) {
  if (!str) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

function toggleMobileNav() {
  const drawer = document.getElementById('mobile-nav-drawer');
  const iconBars = document.getElementById('menu-icon-bars');
  const iconClose = document.getElementById('menu-icon-close');
  if (!drawer) return;
  const isClosed = drawer.classList.contains('hidden');
  drawer.classList.toggle('hidden', !isClosed);
  if (iconBars) iconBars.classList.toggle('hidden', isClosed);
  if (iconClose) iconClose.classList.toggle('hidden', !isClosed);
}

async function loadVisitorStats() {
  const badge = document.getElementById('site-visit-count');
  if (!badge) return;
  try {
    const res = await fetch(`https://socalpauper.goatcounter.com/counter/%2F.json?_=${Date.now()}`, { cache: 'no-store' });
    if (!res.ok) throw new Error();
    const data = await res.json();
    if (data && data.count && data.count !== "0") badge.textContent = data.count;
    else badge.textContent = 'Active';
  } catch (err) {
    badge.textContent = 'Active';
  }
}