// ==========================================================================
// HUB ADMIN MODAL, CRUD CONTROLLER & DATA LOADER
// ==========================================================================
async function loadSiteData() {
  try {
    const resEv = await fetch('./api_hub.php?type=events&_=' + Date.now(), { cache: 'no-store' });
    if (resEv.ok) {
      const data = await resEv.json();
      events = Array.isArray(data) ? data : [];
    }
  } catch (err) {
    try {
      const fb = await fetch('./events.json?v=' + Date.now());
      if (fb.ok) {
        const data = await fb.json();
        events = Array.isArray(data) ? data : [];
      }
    } catch (e) {}
  }

  try {
    const resSt = await fetch('./api_hub.php?type=stores&_=' + Date.now(), { cache: 'no-store' });
    if (resSt.ok) {
      const data = await resSt.json();
      stores = Array.isArray(data) ? data : [];
    }
  } catch (err) {
    try {
      const fb2 = await fetch('./stores.json?v=' + Date.now());
      if (fb2.ok) {
        const data = await fb2.json();
        stores = Array.isArray(data) ? data : [];
      }
    } catch (e) {}
  }

  if (!Array.isArray(events)) events = [];
  if (!Array.isArray(stores)) stores = [];

  renderCalendar();
  renderEvents();
  renderStores();
  if (typeof loadVisitorStats === 'function') loadVisitorStats();
}

function toggleEventRecurUI() {
  const check = document.getElementById('event-recur-check');
  const box = document.getElementById('event-recur-box');
  if (check && box) {
    box.classList.toggle('hidden', !check.checked);
  }
}

function openHubAdminModal(type, action, id, instanceDate) {
  const form = document.getElementById('hub-admin-form');
  const errBox = document.getElementById('hub-modal-error');
  const typeInput = document.getElementById('hub-entry-type');
  const actionInput = document.getElementById('hub-action');
  const idInput = document.getElementById('hub-entry-id');
  const instanceDateInput = document.getElementById('hub-instance-date');
  const titleEl = document.getElementById('hub-modal-title');
  const deleteBtn = document.getElementById('hub-delete-btn');
  const scopeBox = document.getElementById('recurrence-scope-box');
  const modal = document.getElementById('hub-admin-modal');
  const pinInput = document.getElementById('hub-pin');

  if (form) form.reset();
  if (errBox) errBox.classList.add('hidden');
  if (typeInput) typeInput.value = type;
  if (actionInput) actionInput.value = action;
  if (idInput) idInput.value = id || '';
  if (instanceDateInput) instanceDateInput.value = instanceDate || '';

  const isStore = (type === 'stores');
  const eventsField = document.getElementById('fields-events');
  const storesField = document.getElementById('fields-stores');
  if (eventsField) eventsField.classList.toggle('hidden', isStore);
  if (storesField) storesField.classList.toggle('hidden', !isStore);
  if (scopeBox) scopeBox.classList.add('hidden');

  const titlePrefix = action === 'edit' ? 'Edit' : 'Post New';
  if (titleEl) titleEl.textContent = `${titlePrefix} ${isStore ? 'Partner Store' : 'Tournament'}`;
  if (deleteBtn) deleteBtn.classList.toggle('hidden', action !== 'edit');

  if (action === 'edit' && id) {
    if (isStore) {
      const s = stores.find(item => String(item.id) === String(id));
      if (s) {
        document.getElementById('store-name').value = s.name || '';
        document.getElementById('store-city').value = s.city || '';
        document.getElementById('store-region').value = s.region || 'Los Angeles';
        document.getElementById('store-address').value = s.address || '';
        document.getElementById('store-phone').value = s.phone || '';
        document.getElementById('store-website').value = s.website || '';
        document.getElementById('store-notes').value = s.notes || '';
      }
    } else {
      const ev = events.find(item => String(item.id) === String(id));
      if (ev) {
        document.getElementById('event-title').value = ev.title || '';
        document.getElementById('event-date').value = instanceDate || ev.date || '';
        document.getElementById('event-time').value = ev.time || '';
        document.getElementById('event-location').value = ev.location || '';
        document.getElementById('event-region').value = ev.region || 'Los Angeles';
        document.getElementById('event-address').value = ev.address || '';
        document.getElementById('event-desc').value = ev.desc || '';
        
        const recurCheck = document.getElementById('event-recur-check');
        if (recurCheck) recurCheck.checked = isEventRecurring(ev);
        
        const recurFreq = document.getElementById('event-recur-freq');
        if (recurFreq) recurFreq.value = ev.recurFreq || 'weekly';

        const recurEnd = document.getElementById('event-recur-end');
        if (recurEnd) recurEnd.value = ev.recurEnd || '';
        
        toggleEventRecurUI();

        if (isEventRecurring(ev) && instanceDate && scopeBox) {
          scopeBox.classList.remove('hidden');
          const dateLabel = document.getElementById('scope-instance-date-label');
          if (dateLabel) dateLabel.textContent = instanceDate;
        }
      }
    }
  }

  if (modal) modal.classList.remove('hidden');
  if (pinInput) pinInput.focus();
}

function closeHubAdminModal() {
  const modal = document.getElementById('hub-admin-modal');
  if (modal) modal.classList.add('hidden');
}

async function handleHubSubmit(e) {
  e.preventDefault();
  const errBox = document.getElementById('hub-modal-error');
  const submitBtn = document.getElementById('hub-submit-btn');
  if (errBox) errBox.classList.add('hidden');
  if (submitBtn) submitBtn.disabled = true;

  const type = document.getElementById('hub-entry-type').value;
  const action = document.getElementById('hub-action').value;
  const id = document.getElementById('hub-entry-id').value;
  const instanceDate = document.getElementById('hub-instance-date').value;
  
  const scopeRadio = document.querySelector('input[name="event-edit-scope"]:checked');
  const editScope = scopeRadio ? scopeRadio.value : 'series';

  const formData = new FormData();
  formData.append('pin', document.getElementById('hub-pin').value);
  formData.append('type', type);
  formData.append('action', action);
  formData.append('id', id);

  if (type === 'events') {
    formData.append('editScope', editScope);
    formData.append('instanceDate', instanceDate);

    const isRecur = (action === 'edit' && editScope === 'instance') 
      ? false 
      : document.getElementById('event-recur-check').checked;

    formData.append('title', document.getElementById('event-title').value);
    formData.append('date', document.getElementById('event-date').value);
    formData.append('time', document.getElementById('event-time').value);
    formData.append('location', document.getElementById('event-location').value);
    formData.append('region', document.getElementById('event-region').value);
    formData.append('address', document.getElementById('event-address').value);
    formData.append('desc', document.getElementById('event-desc').value);
    formData.append('recurring', isRecur ? '1' : '0');
    formData.append('recurFreq', isRecur ? document.getElementById('event-recur-freq').value : '');
    formData.append('recurEnd', isRecur ? document.getElementById('event-recur-end').value : '');
  } else {
    formData.append('name', document.getElementById('store-name').value);
    formData.append('city', document.getElementById('store-city').value);
    formData.append('region', document.getElementById('store-region').value);
    formData.append('address', document.getElementById('store-address').value);
    formData.append('phone', document.getElementById('store-phone').value);
    formData.append('website', document.getElementById('store-website').value);
    formData.append('notes', document.getElementById('store-notes').value);
  }

  try {
    const res = await fetch('./api_hub.php', { method: 'POST', body: formData });
    const data = await res.json();
    if (!res.ok) throw new Error(data.error || 'Server error occurred');
    closeHubAdminModal();
    loadSiteData();
  } catch (err) {
    if (errBox) {
      errBox.textContent = err.message;
      errBox.classList.remove('hidden');
    }
  } finally {
    if (submitBtn) submitBtn.disabled = false;
  }
}

async function handleHubDelete() {
  const pin = document.getElementById('hub-pin').value;
  const id = document.getElementById('hub-entry-id').value;
  const type = document.getElementById('hub-entry-type').value;
  const instanceDate = document.getElementById('hub-instance-date').value;
  const errBox = document.getElementById('hub-modal-error');

  if (!pin) {
    if (errBox) {
      errBox.textContent = 'Enter PIN to delete.';
      errBox.classList.remove('hidden');
    }
    return;
  }

  const ev = (type === 'events') ? events.find(item => String(item.id) === String(id)) : null;
  const isRecur = ev && isEventRecurring(ev);

  let deleteScope = 'series';
  if (isRecur && instanceDate) {
    if (confirm(`Do you want to delete the ENTIRE recurring series from the calendar?`)) {
      deleteScope = 'series';
    } else if (confirm(`Delete ONLY the single session on ${instanceDate}?`)) {
      deleteScope = 'instance';
    } else {
      return; // Cancelled
    }
  } else {
    if (!confirm('Are you sure you want to permanently delete this entry?')) return;
  }

  const formData = new FormData();
  formData.append('pin', pin);
  formData.append('type', type);
  formData.append('action', 'delete');
  formData.append('id', id);
  formData.append('deleteScope', deleteScope);
  formData.append('instanceDate', instanceDate);

  try {
    const res = await fetch('./api_hub.php', { method: 'POST', body: formData });
    const data = await res.json();
    if (!res.ok) throw new Error(data.error || 'Failed to delete');
    closeHubAdminModal();
    loadSiteData();
  } catch (err) {
    if (errBox) {
      errBox.textContent = err.message;
      errBox.classList.remove('hidden');
    }
  }
}

// Modal backdrop dismissal
document.addEventListener('click', (e) => {
  const bannedModal = document.getElementById('banned-modal');
  if (e.target === bannedModal && typeof closeBannedModal === 'function') closeBannedModal();
  const hubModal = document.getElementById('hub-admin-modal');
  if (e.target === hubModal) closeHubAdminModal();
});

// Bootstrapping on DOM Ready
window.addEventListener('DOMContentLoaded', () => {
  if (typeof initCardExplorer === 'function') initCardExplorer();
  loadSiteData();
});