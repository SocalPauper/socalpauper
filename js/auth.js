// js/auth.js
const Auth = {
  user: null,

  async check() {
    try {
      const res = await fetch('/api_auth.php?action=check');
      const data = await res.json();
      this.user = data.authenticated ? data.user : null;
      this.updateUI();
      return this.user;
    } catch (e) {
      this.user = null;
      this.updateUI();
      return null;
    }
  },

  async login(username, password) {
    const res = await fetch('/api_auth.php?action=login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ username, password })
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.error || 'Login failed');
    this.user = data.user;
    this.updateUI();
    return data.user;
  },

  async register(username, password, email, notifyEvents, notifyPromotions) {
    const res = await fetch('/api_auth.php?action=register', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        username,
        password,
        email: email || null,
        notify_events: !!notifyEvents,
        notify_promotions: !!notifyPromotions
      })
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.error || 'Registration failed');
    this.user = data.user;
    this.updateUI();
    return data.user;
  },

  async logout() {
    await fetch('/api_auth.php?action=logout');
    this.user = null;
    this.updateUI();
    window.location.reload();
  },

  updateUI() {
    const authContainer = document.getElementById('nav-auth-slot');
    if (!authContainer) return;

    if (this.user) {
      authContainer.innerHTML = `
        <div class="user-pill flex items-center gap-2">
          <span class="text-xs text-sky-400 font-bold uppercase tracking-wider">👤 ${this.escapeHtml(this.user.username)}</span>
          <button onclick="Auth.logout()" class="text-xs bg-slate-800 hover:bg-slate-700 text-slate-300 px-2.5 py-1 rounded border border-slate-700 transition">Log Out</button>
        </div>
      `;
    } else {
      authContainer.innerHTML = `
        <button onclick="openAuthModal('login')" class="text-xs text-slate-300 hover:text-white px-2 py-1 transition font-medium">Log In</button>
        <button onclick="openAuthModal('register')" class="text-xs bg-sky-600 hover:bg-sky-500 text-white font-semibold px-3 py-1.5 rounded-lg transition shadow-sm">Sign Up</button>
      `;
    }
  },

  escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
  }
};

document.addEventListener('DOMContentLoaded', () => {
  Auth.check();
});