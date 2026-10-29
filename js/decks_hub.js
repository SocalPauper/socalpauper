// js/decks_hub.js
window.Hub = {
  user: null,
  authMode: 'login',
  activeTab: 'my_decks', // 'my_decks', 'community', or 'admin'
  activeFolderId: null,
  decks: [],
  folders: [],
  adminUsers: [],
  activeDeckActionId: null,

  async init() {
    this.bindEvents();
    await this.checkAuth();
  },

  async checkAuth() {
    try {
      const res = await fetch('/api_auth.php?action=check');
      const data = await res.json();
      this.user = data.authenticated ? data.user : null;
    } catch (e) {
      this.user = null;
    }

    this.updateAuthUI();
    this.loadDecks();
  },

  updateAuthUI() {
    const slot = document.getElementById('nav-auth-slot');
    const adminTab = document.getElementById('tab-admin');
    const broadcastLink = document.getElementById('link-admin-broadcast');

    if (this.user) {
      if (adminTab) {
        if (this.user.role === 'admin') adminTab.classList.remove('hidden');
        else adminTab.classList.add('hidden');
      }

      if (broadcastLink) {
        if (this.user.role === 'admin') broadcastLink.classList.remove('hidden');
        else broadcastLink.classList.add('hidden');
      }

      if (slot) {
        slot.innerHTML = '<div class="flex items-center gap-2">' +
          '<span class="text-xs text-sky-400 font-bold uppercase tracking-wider">👤 ' + this.escapeHtml(this.user.username) + '</span>' +
          '<button onclick="Hub.logout()" class="text-xs bg-slate-800 hover:bg-slate-700 text-slate-300 px-2 py-1 rounded border border-slate-700 transition">Log Out</button>' +
          '</div>';
      }
    } else {
      if (adminTab) adminTab.classList.add('hidden');
      if (broadcastLink) broadcastLink.classList.add('hidden');
      if (slot) {
        slot.innerHTML = '<button onclick="openAuthModal()" class="text-xs bg-sky-600 hover:bg-sky-500 text-white font-semibold px-3 py-1.5 rounded-lg transition shadow-sm">Sign In</button>';
      }
    }
  },

  toggleAuthMode() {
    this.authMode = this.authMode === 'login' ? 'register' : 'login';
    const title = document.getElementById('auth-modal-title');
    const submitBtn = document.getElementById('auth-submit-btn');
    const prompt = document.getElementById('auth-toggle-prompt');
    const toggleBtn = document.getElementById('auth-toggle-mode-btn');
    const extraFields = document.getElementById('auth-register-extra');
    const errEl = document.getElementById('auth-error-msg');
    if (errEl) errEl.classList.add('hidden');

    if (this.authMode === 'register') {
      if (title) title.textContent = 'Create Account';
      if (submitBtn) submitBtn.textContent = 'Sign Up';
      if (prompt) prompt.textContent = 'Already have an account?';
      if (toggleBtn) toggleBtn.textContent = 'Sign In';
      if (extraFields) extraFields.classList.remove('hidden');
    } else {
      if (title) title.textContent = 'Sign In';
      if (submitBtn) submitBtn.textContent = 'Sign In';
      if (prompt) prompt.textContent = "Don't have an account?";
      if (toggleBtn) toggleBtn.textContent = 'Create Account';
      if (extraFields) extraFields.classList.add('hidden');
    }
  },

  handleNewDeck() {
    if (!this.user) {
      openAuthModal();
      return;
    }
    window.location.href = 'builder.php';
  },

  bindEvents() {
    const myDecksTab = document.getElementById('tab-my-decks');
    const commTab = document.getElementById('tab-community');
    const adminTab = document.getElementById('tab-admin');

    const resetTabs = () => {
      [myDecksTab, commTab, adminTab].forEach(t => {
        if (t) t.className = 'px-4 py-1.5 rounded-md text-slate-400 hover:text-white transition';
      });
      document.getElementById('folders-panel')?.classList.remove('hidden');
      document.getElementById('decks-grid')?.classList.remove('hidden');
      document.getElementById('admin-users-tree')?.classList.add('hidden');
      const label = document.getElementById('filter-label');
      if (label) label.textContent = 'Search Decks';
    };

    if (myDecksTab) {
      myDecksTab.onclick = () => {
        resetTabs();
        this.activeTab = 'my_decks';
        myDecksTab.className = 'px-4 py-1.5 rounded-md bg-sky-600 text-white transition';
        const title = document.getElementById('view-title');
        if (title) title.textContent = "My Decks";
        this.loadDecks();
      };
    }

    if (commTab) {
      commTab.onclick = () => {
        resetTabs();
        this.activeTab = 'community';
        commTab.className = 'px-4 py-1.5 rounded-md bg-sky-600 text-white transition';
        const title = document.getElementById('view-title');
        if (title) title.textContent = "Community Brews";
        this.loadDecks();
      };
    }

    if (adminTab) {
      adminTab.onclick = () => {
        resetTabs();
        this.activeTab = 'admin';
        adminTab.className = 'px-4 py-1.5 rounded-md bg-purple-700 text-white transition';
        const title = document.getElementById('view-title');
        if (title) title.textContent = "Admin Explorer: All Users & Decks";
        document.getElementById('folders-panel')?.classList.add('hidden');
        document.getElementById('decks-grid')?.classList.add('hidden');
        document.getElementById('admin-users-tree')?.classList.remove('hidden');
        const label = document.getElementById('filter-label');
        if (label) label.textContent = 'Filter Users / Decks';
        this.loadAdminTree();
      };
    }

    const filterInput = document.getElementById('deck-filter-input');
    if (filterInput) {
      filterInput.addEventListener('input', () => {
        if (this.activeTab === 'admin') this.renderAdminTree();
        else this.renderDecks();
      });
    }

    const folderSubmitBtn = document.getElementById('folder-submit-btn');
    if (folderSubmitBtn) folderSubmitBtn.onclick = () => this.handleCreateFolder();

    const renameSubmitBtn = document.getElementById('rename-submit-btn');
    if (renameSubmitBtn) renameSubmitBtn.onclick = () => this.handleRenameDeck();

    const moveSubmitBtn = document.getElementById('move-submit-btn');
    if (moveSubmitBtn) moveSubmitBtn.onclick = () => this.handleMoveDeck();

    const authForm = document.getElementById('auth-form');
    if (authForm) {
      authForm.onsubmit = async (e) => {
        e.preventDefault();
        const username = document.getElementById('auth-username').value.trim();
        const password = document.getElementById('auth-password').value;
        const errEl = document.getElementById('auth-error-msg');

        const endpoint = this.authMode === 'register'
          ? '/api_auth.php?action=register'
          : '/api_auth.php?action=login';

        const payload = { username, password };
        if (this.authMode === 'register') {
          payload.email = document.getElementById('auth-email').value.trim();
          payload.notify_events = document.getElementById('auth-notify-events').checked;
          payload.notify_promotions = document.getElementById('auth-notify-promos').checked;
        }

        try {
          const res = await fetch(endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
          });
          const data = await res.json();
          if (!res.ok) throw new Error(data.error || 'Authentication failed');
          this.user = data.user;
          this.updateAuthUI();
          closeAuthModal();
          this.loadDecks();
        } catch (err) {
          if (errEl) {
            errEl.textContent = err.message;
            errEl.classList.remove('hidden');
          }
        }
      };
    }
  },

  async loadDecks() {
    const grid = document.getElementById('decks-grid');
    if (!grid) return;

    grid.innerHTML = '<div class="col-span-full text-center py-12 text-sky-400"><i class="fa-solid fa-circle-notch fa-spin text-3xl"></i></div>';

    if (this.activeTab === 'my_decks' && !this.user) {
      grid.innerHTML = '<div class="col-span-full text-center py-12 space-y-3">' +
        '<p class="text-sm text-slate-400">Sign in to organize and manage your personal deck collection.</p>' +
        '<button onclick="openAuthModal()" class="px-4 py-2 bg-sky-600 hover:bg-sky-500 font-bold text-xs rounded-lg transition text-white">Sign In</button>' +
        '</div>';
      const countEl = document.getElementById('filtered-deck-count');
      if (countEl) countEl.textContent = '0 decks';
      return;
    }

    try {
      const endpoint = this.activeTab === 'my_decks'
        ? '/api_decks.php?action=my_decks'
        : '/api_decks.php?action=community_decks';

      const res = await fetch(endpoint);
      let data;
      try {
        data = await res.json();
      } catch (e) {
        throw new Error('Invalid JSON received from server.');
      }

      if (!res.ok) throw new Error(data.error || ('Server error: ' + res.status));

      this.decks = data.decks || [];
      this.folders = data.folders || [];

      this.renderFolders();
      this.renderDecks();
    } catch (err) {
      grid.innerHTML = '<div class="col-span-full text-center text-rose-400 py-8 bg-rose-950/20 border border-rose-900/60 rounded-xl p-4">' +
        '<div class="font-bold text-sm">Failed to load decks</div>' +
        '<div class="text-xs text-rose-300 mt-1">' + this.escapeHtml(err.message) + '</div>' +
        '</div>';
    }
  },

  async loadAdminTree() {
    const container = document.getElementById('admin-users-tree');
    if (!container) return;

    container.innerHTML = '<div class="text-center py-12 text-purple-400"><i class="fa-solid fa-circle-notch fa-spin text-3xl"></i><div class="mt-2 text-xs font-bold uppercase tracking-wider">Loading user directories...</div></div>';

    try {
      const res = await fetch('/api_decks.php?action=admin_users_and_decks');
      const data = await res.json();
      if (!res.ok) throw new Error(data.error || 'Failed to fetch admin explorer.');

      this.adminUsers = data.users || [];
      this.renderAdminTree();
    } catch (err) {
      container.innerHTML = '<div class="text-center text-rose-400 py-8 bg-rose-950/20 border border-rose-900/60 rounded-xl p-4">' +
        '<div class="font-bold text-sm">Admin Access Error</div>' +
        '<div class="text-xs text-rose-300 mt-1">' + this.escapeHtml(err.message) + '</div>' +
        '</div>';
    }
  },

  renderAdminTree() {
    const container = document.getElementById('admin-users-tree');
    if (!container) return;

    const term = (document.getElementById('deck-filter-input')?.value || '').toLowerCase().trim();
    let users = this.adminUsers;

    if (term) {
      users = users.filter(u => {
        const matchUser = u.username.toLowerCase().includes(term) || (u.email && u.email.toLowerCase().includes(term));
        const matchDeck = u.decks && u.decks.some(d => d.title.toLowerCase().includes(term));
        return matchUser || matchDeck;
      });
    }

    const countEl = document.getElementById('filtered-deck-count');
    const totalDecks = users.reduce((acc, u) => acc + (u.deck_count || 0), 0);
    if (countEl) countEl.textContent = users.length + ' users (' + totalDecks + ' total decks)';

    if (!users.length) {
      container.innerHTML = '<div class="text-center py-12 text-slate-500 italic">No users found matching your search.</div>';
      return;
    }

    container.innerHTML = users.map(u => {
      const isAutoOpen = term !== '';
      const emailText = u.email ? this.escapeHtml(u.email) : 'No email attached';

      const decksHtml = (!u.decks || !u.decks.length)
        ? '<div class="p-3 text-xs text-slate-500 italic">No decks created by this user yet.</div>'
        : '<div class="divide-y divide-slate-800/80">' +
          u.decks.map(d => {
            const isPub = parseInt(d.is_public || 0, 10) === 1;
            const badge = isPub
              ? '<span class="text-[9px] uppercase px-1.5 py-0.5 rounded bg-emerald-950 text-emerald-400 border border-emerald-800 font-bold">Public</span>'
              : '<span class="text-[9px] uppercase px-1.5 py-0.5 rounded bg-slate-900 text-amber-400 border border-slate-700 font-bold">Private</span>';

            return '<div class="p-3 flex items-center justify-between hover:bg-slate-950/40 transition">' +
              '<div class="flex items-center gap-3">' +
              '<span class="font-bold text-white text-xs">' + this.escapeHtml(d.title) + '</span>' +
              badge +
              '<span class="text-[11px] text-slate-400 font-mono">' + (d.card_count || 0) + ' Cards</span>' +
              '</div>' +
              '<div class="flex items-center gap-2">' +
              '<span class="text-[10px] text-slate-500">Updated: ' + new Date(d.updated_at).toLocaleDateString() + '</span>' +
              '<button onclick="Hub.editDeck(' + d.id + ')" class="px-2.5 py-1 bg-purple-900/60 hover:bg-purple-800 border border-purple-700 text-purple-200 text-xs font-bold rounded transition">' +
              'Inspect' +
              '</button>' +
              '</div>' +
              '</div>';
          }).join('') +
          '</div>';

      return '<div class="bg-slate-900 border border-slate-800 rounded-xl overflow-hidden">' +
        '<button onclick="Hub.toggleUserFolder(' + u.id + ')" class="w-full p-4 flex items-center justify-between text-left bg-slate-900 hover:bg-slate-800/60 transition">' +
        '<div class="flex items-center gap-3">' +
        '<i id="user-folder-icon-' + u.id + '" class="fa-solid ' + (isAutoOpen ? 'fa-folder-open' : 'fa-folder') + ' text-purple-400 text-lg"></i>' +
        '<div>' +
        '<div class="font-bold text-white text-sm flex items-center gap-2">' +
        this.escapeHtml(u.username) +
        (u.role === 'admin' ? '<span class="text-[9px] uppercase bg-purple-950 border border-purple-700 text-purple-300 font-extrabold px-1.5 rounded">Admin</span>' : '') +
        '</div>' +
        '<div class="text-[11px] text-slate-400 font-mono">' + emailText + ' • Joined ' + new Date(u.created_at).toLocaleDateString() + '</div>' +
        '</div>' +
        '</div>' +
        '<div class="flex items-center gap-3">' +
        '<span class="text-xs bg-slate-800 text-slate-300 font-bold px-2.5 py-1 rounded-full">' + (u.deck_count || 0) + ' Decks</span>' +
        '<i id="user-chevron-' + u.id + '" class="fa-solid ' + (isAutoOpen ? 'fa-chevron-up' : 'fa-chevron-down') + ' text-slate-500 text-xs"></i>' +
        '</div>' +
        '</button>' +
        '<div id="user-folder-content-' + u.id + '" class="bg-slate-950/80 border-t border-slate-800 ' + (isAutoOpen ? '' : 'hidden') + '">' +
        decksHtml +
        '</div>' +
        '</div>';
    }).join('');
  },

  toggleUserFolder(userId) {
    const content = document.getElementById('user-folder-content-' + userId);
    const icon = document.getElementById('user-folder-icon-' + userId);
    const chevron = document.getElementById('user-chevron-' + userId);
    if (!content) return;

    const isHidden = content.classList.contains('hidden');
    if (isHidden) {
      content.classList.remove('hidden');
      if (icon) icon.className = 'fa-solid fa-folder-open text-purple-400 text-lg';
      if (chevron) chevron.className = 'fa-solid fa-chevron-up text-slate-500 text-xs';
    } else {
      content.classList.add('hidden');
      if (icon) icon.className = 'fa-solid fa-folder text-purple-400 text-lg';
      if (chevron) chevron.className = 'fa-solid fa-chevron-down text-slate-500 text-xs';
    }
  },

  renderFolders() {
    const container = document.getElementById('folder-list-container');
    if (!container) return;

    const allCount = document.getElementById('all-decks-count');
    if (allCount) allCount.textContent = this.decks.length;

    while (container.children.length > 1) {
      container.removeChild(container.lastChild);
    }

    if (this.activeTab !== 'my_decks') return;

    this.folders.forEach(f => {
      const count = this.decks.filter(d => d.folder_id == f.id).length;
      const btn = document.createElement('div');
      const isSelected = this.activeFolderId == f.id;

      btn.className = 'w-full px-3 py-2 rounded-lg text-xs font-semibold flex items-center justify-between cursor-pointer transition ' +
        (isSelected ? 'bg-sky-950 text-sky-400 border border-sky-800' : 'text-slate-400 hover:bg-slate-800 hover:text-white');

      btn.innerHTML = '<span class="flex items-center gap-2 truncate">' +
        '<i class="fa-solid fa-folder"></i> ' + this.escapeHtml(f.name) +
        '</span>' +
        '<div class="flex items-center gap-2">' +
        '<span class="text-[10px] bg-slate-800 px-1.5 py-0.5 rounded">' + count + '</span>' +
        '<button onclick="event.stopPropagation(); Hub.deleteFolder(' + f.id + ')" class="text-slate-500 hover:text-rose-400"><i class="fa-solid fa-trash-can"></i></button>' +
        '</div>';

      btn.onclick = () => this.selectFolder(f.id);
      container.appendChild(btn);
    });
  },

  selectFolder(folderId) {
    this.activeFolderId = folderId;
    const allBtn = document.getElementById('folder-all-btn');

    if (allBtn) {
      if (folderId === null) {
        allBtn.className = 'w-full text-left px-3 py-2 rounded-lg bg-sky-950 text-sky-400 font-bold border border-sky-800 flex items-center justify-between';
      } else {
        allBtn.className = 'w-full text-left px-3 py-2 rounded-lg text-slate-400 hover:bg-slate-800 hover:text-white flex items-center justify-between';
      }
    }

    this.renderFolders();
    this.renderDecks();
  },

  renderDecks() {
    const grid = document.getElementById('decks-grid');
    if (!grid) return;

    const filterTerm = (document.getElementById('deck-filter-input')?.value || '').toLowerCase().trim();
    let filtered = this.decks;

    if (this.activeTab === 'my_decks' && this.activeFolderId !== null) {
      filtered = filtered.filter(d => d.folder_id == this.activeFolderId);
    }

    if (filterTerm) {
      filtered = filtered.filter(d => d.title.toLowerCase().includes(filterTerm));
    }

    const countEl = document.getElementById('filtered-deck-count');
    if (countEl) countEl.textContent = filtered.length + ' deck' + (filtered.length === 1 ? '' : 's');

    if (!filtered.length) {
      grid.innerHTML = '<div class="col-span-full text-center py-12 text-slate-500 italic">' +
        'No decks found in this view. Click "New Deck" above to create one.' +
        '</div>';
      return;
    }

    grid.innerHTML = filtered.map(d => {
      let rawArt = d.featured_card_art || 'https://cards.scryfall.io/art_crop/front/b/4/b4a530f2-772b-449e-b24d-e9c56f671c5d.jpg';
      if (!rawArt.startsWith('https://')) {
        rawArt = 'https://cards.scryfall.io/art_crop/front/b/4/b4a530f2-772b-449e-b24d-e9c56f671c5d.jpg';
      }
      const art = encodeURI(rawArt);

      const isOwner = this.user && (this.activeTab === 'my_decks');
      const isPublic = parseInt(d.is_public || 0, 10) === 1;

      const visBadge = isPublic
        ? '<span class="text-[9px] uppercase font-black px-1.5 py-0.5 rounded bg-emerald-950/80 text-emerald-400 border border-emerald-800 ml-1.5"><i class="fa-solid fa-globe mr-0.5"></i> Public</span>'
        : '<span class="text-[9px] uppercase font-black px-1.5 py-0.5 rounded bg-slate-900/80 text-amber-400 border border-slate-700 ml-1.5"><i class="fa-solid fa-lock mr-0.5"></i> Private</span>';

      const actionButtonLabel = isOwner
        ? '<i class="fa-solid fa-pen-to-square mr-1"></i> Edit'
        : '<i class="fa-solid fa-eye mr-1"></i> View Deck';

      const escTitle = this.escapeHtml(d.title).replace(/'/g, "\\'");

      let ownerActions = '';
      if (isOwner) {
        ownerActions = '<div class="flex items-center gap-1.5 opacity-90 group-hover:opacity-100 transition">' +
          '<button onclick="Hub.toggleDeckVisibility(' + d.id + ')" class="p-1 bg-slate-900/80 hover:bg-sky-600 text-slate-300 hover:text-white rounded text-xs transition" title="' + (isPublic ? 'Make Private' : 'Make Public') + '"><i class="fa-solid ' + (isPublic ? 'fa-globe text-emerald-400' : 'fa-lock text-amber-400') + '"></i></button>' +
          '<button onclick="Hub.openRenameModal(' + d.id + ', \'' + escTitle + '\')" class="p-1 bg-slate-900/80 hover:bg-sky-600 text-slate-300 hover:text-white rounded text-xs transition" title="Rename"><i class="fa-solid fa-pen"></i></button>' +
          '<button onclick="Hub.openMoveModal(' + d.id + ')" class="p-1 bg-slate-900/80 hover:bg-sky-600 text-slate-300 hover:text-white rounded text-xs transition" title="Move Folder"><i class="fa-solid fa-folder-tree"></i></button>' +
          '<button onclick="Hub.cloneDeck(' + d.id + ')" class="p-1 bg-slate-900/80 hover:bg-emerald-600 text-slate-300 hover:text-white rounded text-xs transition" title="Duplicate"><i class="fa-solid fa-copy"></i></button>' +
          '<button onclick="Hub.deleteDeck(' + d.id + ')" class="p-1 bg-slate-900/80 hover:bg-rose-600 text-slate-300 hover:text-white rounded text-xs transition" title="Delete"><i class="fa-solid fa-trash"></i></button>' +
          '</div>';
      }

      return '<div class="hub-card border rounded-xl overflow-hidden shadow-lg transition flex flex-col justify-between group">' +
        '<div class="hub-art-header h-28 p-3 flex flex-col justify-between" style="background-image: url(\'' + art + '\');">' +
        '<div class="hub-art-gradient absolute inset-0"></div>' +
        '<div class="relative flex justify-between items-start z-10">' +
        '<div class="flex items-center">' +
        '<span class="text-[10px] font-black uppercase tracking-wider px-2 py-0.5 rounded bg-sky-950/90 text-sky-400 border border-sky-700">' + (d.format || 'Pauper') + '</span>' +
        (isOwner ? visBadge : '') +
        '</div>' +
        ownerActions +
        '</div>' +
        '<div class="relative z-10">' +
        '<h3 class="font-extrabold text-white text-base leading-snug drop-shadow-md truncate">' + this.escapeHtml(d.title) + '</h3>' +
        '<div class="text-[11px] text-slate-300 flex items-center gap-2 mt-0.5">' +
        '<span>' + (d.card_count || 0) + ' Cards</span>' +
        '<span>•</span>' +
        '<span>' + (d.username ? 'by ' + this.escapeHtml(d.username) : new Date(d.updated_at).toLocaleDateString()) + '</span>' +
        '</div>' +
        '</div>' +
        '</div>' +
        '<div class="p-3 bg-slate-950 flex items-center justify-between border-t border-slate-800/80">' +
        '<span class="text-[10px] text-slate-500 font-mono">Updated ' + new Date(d.updated_at).toLocaleDateString() + '</span>' +
        '<button onclick="Hub.editDeck(' + d.id + ')" class="px-3 py-1.5 ' + (isOwner ? 'bg-sky-600 hover:bg-sky-500 text-white' : 'bg-slate-800 hover:bg-slate-700 text-slate-200') + ' font-bold text-xs rounded-lg transition flex items-center shadow">' +
        actionButtonLabel +
        '</button>' +
        '</div>' +
        '</div>';
    }).join('');
  },

  async toggleDeckVisibility(deckId) {
    try {
      const res = await fetch('/api_decks.php?action=toggle_visibility', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: deckId })
      });
      const data = await res.json();
      if (!res.ok) throw new Error(data.error || 'Failed to update visibility');

      const target = this.decks.find(d => d.id == deckId);
      if (target) target.is_public = data.is_public;
      this.renderDecks();
    } catch (err) {
      alert(err.message);
    }
  },

  editDeck(deckId) {
    window.location.href = 'builder.php?id=' + deckId;
  },

  async handleCreateFolder() {
    const input = document.getElementById('folder-name-input');
    const name = input ? input.value.trim() : '';
    if (!name) return;

    try {
      const res = await fetch('/api_decks.php?action=create_folder', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name })
      });
      const data = await res.json();
      if (!res.ok) throw new Error(data.error || 'Failed to create folder');
      closeFolderModal();
      this.loadDecks();
    } catch (err) {
      alert(err.message);
    }
  },

  async deleteFolder(folderId) {
    if (!confirm("Are you sure you want to delete this folder?")) return;

    try {
      const res = await fetch('/api_decks.php?action=delete_folder', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: folderId })
      });
      if (!res.ok) throw new Error('Failed to delete folder');
      if (this.activeFolderId == folderId) this.activeFolderId = null;
      this.loadDecks();
    } catch (err) {
      alert(err.message);
    }
  },

  openRenameModal(deckId, currentTitle) {
    this.activeDeckActionId = deckId;
    const input = document.getElementById('rename-deck-input');
    if (input) input.value = currentTitle;
    document.getElementById('rename-modal')?.classList.remove('hidden');
  },

  async handleRenameDeck() {
    const title = document.getElementById('rename-deck-input').value.trim();
    if (!title || !this.activeDeckActionId) return;

    try {
      const res = await fetch('/api_decks.php?action=rename', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: this.activeDeckActionId, title })
      });
      if (!res.ok) throw new Error('Failed to rename deck');
      closeRenameModal();
      this.loadDecks();
    } catch (err) {
      alert(err.message);
    }
  },

  async deleteDeck(deckId) {
    if (!confirm("Are you sure you want to delete this deck? This action cannot be undone.")) return;

    try {
      const res = await fetch('/api_decks.php?action=delete', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: deckId })
      });
      if (!res.ok) throw new Error('Failed to delete deck');
      this.loadDecks();
    } catch (err) {
      alert(err.message);
    }
  },

  async cloneDeck(deckId) {
    try {
      const res = await fetch('/api_decks.php?action=clone', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: deckId })
      });
      const data = await res.json();
      if (!res.ok) throw new Error(data.error || 'Failed to duplicate deck');
      this.loadDecks();
    } catch (err) {
      alert(err.message);
    }
  },

  openMoveModal(deckId) {
    this.activeDeckActionId = deckId;
    const select = document.getElementById('move-folder-select');
    if (select) {
      select.innerHTML = '<option value="">Uncategorized (No Folder)</option>' + 
        this.folders.map(f => '<option value="' + f.id + '">' + this.escapeHtml(f.name) + '</option>').join('');

      const targetDeck = this.decks.find(d => d.id == deckId);
      if (targetDeck && targetDeck.folder_id) {
        select.value = targetDeck.folder_id;
      }
    }

    document.getElementById('move-modal')?.classList.remove('hidden');
  },

  async handleMoveDeck() {
    const select = document.getElementById('move-folder-select');
    const folderId = select.value ? parseInt(select.value, 10) : null;

    try {
      const res = await fetch('/api_decks.php?action=move_folder', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: this.activeDeckActionId, folder_id: folderId })
      });
      if (!res.ok) throw new Error('Failed to move deck');
      closeMoveModal();
      this.loadDecks();
    } catch (err) {
      alert(err.message);
    }
  },

  async logout() {
    await fetch('/api_auth.php?action=logout');
    this.user = null;
    this.updateAuthUI();
    this.loadDecks();
  },

  escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
  }
};

function openFolderModal() { document.getElementById('folder-modal')?.classList.remove('hidden'); }
function closeFolderModal() { document.getElementById('folder-modal')?.classList.add('hidden'); }
function closeRenameModal() { document.getElementById('rename-modal')?.classList.add('hidden'); }
function closeMoveModal() { document.getElementById('move-modal')?.classList.add('hidden'); }
function openAuthModal() { document.getElementById('auth-modal')?.classList.remove('hidden'); }
function closeAuthModal() { document.getElementById('auth-modal')?.classList.add('hidden'); }

document.addEventListener('DOMContentLoaded', () => window.Hub.init());