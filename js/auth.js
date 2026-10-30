// js/auth.js
const Auth = {
  user: null,

  async check() {
    try {
      const res = await fetch('/api_auth.php?action=check');
      const data = await res.json();
      if (data.authenticated && data.requires_setup) {
        window.location.href = 'setup_account.php';
        return null;
      }
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
    
    // Intercept first-time provisioned moderators
    if (data.requires_setup && data.redirect) {
      window.location.href = data.redirect;
      return null;
    }

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
    const isStaff = this.user && (this.user.role === 'admin' || this.user.role === 'moderator');
    const isAdmin = this.user && this.user.role === 'admin';

    // 1. Navigation Auth Slot
    if (authContainer) {
      if (this.user) {
        authContainer.innerHTML = `
          <div class="user-pill flex items-center gap-2">
            ${isAdmin ? `
              <a href="admin_moderators.php" title="Staff Management" class="text-xs bg-emerald-950/80 hover:bg-emerald-900 text-emerald-300 px-2 py-1 rounded border border-emerald-700/80 transition font-mono flex items-center gap-1">
                ⚙️ Staff
              </a>
            ` : ''}
            <span class="text-xs ${isAdmin ? 'text-purple-400' : isStaff ? 'text-amber-400' : 'text-sky-400'} font-bold uppercase tracking-wider">
              👤 ${this.escapeHtml(this.user.username)}
            </span>
            <button onclick="Auth.logout()" class="text-xs bg-slate-800 hover:bg-slate-700 text-slate-300 px-2.5 py-1 rounded border border-slate-700 transition">Log Out</button>
          </div>
        `;
      } else {
        authContainer.innerHTML = `
          <button onclick="openAuthModal('login')" class="text-xs text-slate-300 hover:text-white px-2 py-1 transition font-medium">Log In</button>
          <button onclick="openAuthModal('register')" class="text-xs bg-sky-600 hover:bg-sky-500 text-white font-semibold px-3 py-1.5 rounded-lg transition shadow-sm">Sign Up</button>
        `;
      }
    }

    // 2. Hide/Show Staff action buttons across the site
    document.querySelectorAll('[data-staff-only], .staff-only, .admin-only').forEach(el => {
      el.classList.toggle('hidden', !isStaff);
    });

    document.querySelectorAll('button[onclick*="openHubAdminModal"]').forEach(btn => {
      btn.classList.toggle('hidden', !isStaff);
    });
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