const page = document.body.dataset.page;
const messageEl = document.getElementById('message');
const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

const CALENDAR_DAYS_BEFORE_TODAY = 1;
const CALENDAR_DAYS_AFTER_TODAY = 7;
const CALENDAR_DAY_START_HOUR = 6;

function setMessage(text, isError = false) {
  if (!messageEl) return;
  messageEl.textContent = text;
  messageEl.className = isError ? 'flash flash-error' : 'flash flash-success';
}

async function api(url, options = {}) {
  const response = await fetch(url, {
    credentials: 'same-origin',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': csrf,
      ...(options.headers || {}),
    },
    ...options,
  });
  const json = await response.json().catch(() => ({}));
  if (!response.ok && json?.message && !json.error) json.error = json.message;
  return json;
}

function queryParams() {
  return new URLSearchParams(window.location.search);
}

async function ensureAuth(verified = false) {
  const me = await api('/api/me');
  if (!me.authenticated || !me.user) {
    window.location.href = '/login';
    return null;
  }
  if (verified && !me.user.email_verified_at) {
    window.location.href = '/verify-email';
    return null;
  }
  return me.user;
}

function formatLocal(utcIso) {
  return new Intl.DateTimeFormat(undefined, { weekday: 'short', day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' }).format(new Date(utcIso));
}

function attachLogoutHandler() {
  document.getElementById('logout-btn')?.addEventListener('click', async () => {
    await api('/api/logout', { method: 'POST', body: '{}' });
    window.location.href = '/login';
  });
}

function dayKeyForDisplay(date) {
  const shifted = new Date(date);
  shifted.setHours(shifted.getHours() - CALENDAR_DAY_START_HOUR);
  return shifted.toISOString().slice(0, 10);
}

function buildCalendarRange() {
  const start = new Date();
  start.setHours(0, 0, 0, 0);
  start.setDate(start.getDate() - CALENDAR_DAYS_BEFORE_TODAY);
  start.setHours(CALENDAR_DAY_START_HOUR, 0, 0, 0);

  const totalDays = CALENDAR_DAYS_BEFORE_TODAY + 1 + CALENDAR_DAYS_AFTER_TODAY;
  const end = new Date(start);
  end.setHours(end.getHours() + (totalDays * 24));

  return { start, end, totalDays };
}

async function boot() {
  if (page === 'login') {
    document.getElementById('login-form')?.addEventListener('submit', async (event) => {
      event.preventDefault();
      const fd = new FormData(event.target);
      const result = await api('/api/login', { method: 'POST', body: JSON.stringify({ email: fd.get('email'), password: fd.get('password') }) });
      if (result.error) return setMessage(result.error, true);
      window.location.href = '/';
    });
  }

  if (page === 'register') {
    document.getElementById('register-form')?.addEventListener('submit', async (event) => {
      event.preventDefault();
      const fd = new FormData(event.target);
      const result = await api('/api/register', { method: 'POST', body: JSON.stringify({ name: fd.get('name'), email: fd.get('email'), password: fd.get('password') }) });
      if (result.error) return setMessage(result.error, true);
      window.location.href = '/verify-email';
    });
  }

  if (page === 'verify') {
    const user = await ensureAuth();
    if (!user) return;

    if (user.email_verified_at) {
      window.location.href = '/';
      return;
    }

    document.getElementById('resend-btn')?.addEventListener('click', async () => {
      const result = await api('/api/email/verification-notification', { method: 'POST', body: '{}' });
      setMessage(result.error ?? result.message ?? 'Gesendet', !!result.error);
    });
  }

  if (page === 'forgot') {
    document.getElementById('forgot-form')?.addEventListener('submit', async (event) => {
      event.preventDefault();
      const fd = new FormData(event.target);
      const result = await api('/api/password/forgot', { method: 'POST', body: JSON.stringify({ email: fd.get('email') }) });
      setMessage(result.error ?? result.message ?? 'Falls vorhanden, gesendet', !!result.error);
    });
  }

  if (page === 'reset') {
    const params = queryParams();
    document.getElementById('reset-form')?.addEventListener('submit', async (event) => {
      event.preventDefault();
      const fd = new FormData(event.target);
      const result = await api('/api/password/reset', {
        method: 'POST',
        body: JSON.stringify({ token: params.get('token'), email: fd.get('email'), password: fd.get('password') }),
      });
      if (result.error) return setMessage(result.error, true);
      window.location.href = '/login';
    });
  }

  if (page === 'calendar') {
    const user = await ensureAuth(true);
    if (!user) return;

    attachLogoutHandler();

    const renderEvents = async () => {
      const { start, end, totalDays } = buildCalendarRange();
      const eventsBox = document.getElementById('events');
      const data = await api(`/api/events?${new URLSearchParams({ start: start.toISOString(), end: end.toISOString() })}`);
      if (data.error) return setMessage(data.error, true);

      const eventsByDayAndHour = new Map();
      for (const event of data.events) {
        const localStart = new Date(event.starts_at_utc);
        const dayKey = dayKeyForDisplay(localStart);
        const displayHour = ((localStart.getHours() - CALENDAR_DAY_START_HOUR + 24) % 24) + CALENDAR_DAY_START_HOUR;
        const slotKey = `${dayKey}-${displayHour}`;
        if (!eventsByDayAndHour.has(slotKey)) eventsByDayAndHour.set(slotKey, []);
        eventsByDayAndHour.get(slotKey).push(event);
      }

      const daySections = [];
      for (let dayOffset = 0; dayOffset < totalDays; dayOffset++) {
        const dayStart = new Date(start);
        dayStart.setDate(dayStart.getDate() + dayOffset);
        const dayKey = dayKeyForDisplay(dayStart);
        const dayLabel = new Intl.DateTimeFormat(undefined, { weekday: 'long', day: '2-digit', month: '2-digit' }).format(dayStart);

        const slotRows = [];
        for (let hour = CALENDAR_DAY_START_HOUR; hour < CALENDAR_DAY_START_HOUR + 24; hour++) {
          const slotKey = `${dayKey}-${hour}`;
          const slotEvents = (eventsByDayAndHour.get(slotKey) || []).sort((a, b) => new Date(a.starts_at_utc) - new Date(b.starts_at_utc));
          const hourLabel = `${String(hour % 24).padStart(2, '0')}:00`;

          slotRows.push(`
            <div class="calendar-slot">
              <div class="calendar-time">${hourLabel}</div>
              <div class="calendar-events">
                ${slotEvents.map((event) => `
                  <article>
                    <h4>${event.gym_name}</h4>
                    <p><strong>Start:</strong> ${formatLocal(event.starts_at_utc)}</p>
                    <p><strong>Erstellt von:</strong> ${event.creator_name}</p>
                    <p><strong>Teilnehmer:</strong> ${event.participants}</p>
                    ${event.notes ? `<p>${event.notes}</p>` : ''}
                    <div class="actions">
                      <button data-action="${event.joined ? 'leave' : 'join'}" data-id="${event.id}">${event.joined ? 'Verlassen' : 'Beitreten'}</button>
                      ${event.creator_id === user.id ? `<button data-action="delete" data-id="${event.id}">Löschen</button>` : ''}
                    </div>
                  </article>
                `).join('')}
              </div>
            </div>
          `);
        }

        daySections.push(`
          <section class="calendar-day">
            <h3>${dayLabel}</h3>
            ${slotRows.join('')}
          </section>
        `);
      }

      eventsBox.innerHTML = daySections.join('');

      eventsBox.querySelectorAll('button[data-action]').forEach((button) => {
        button.addEventListener('click', async () => {
          const route = button.dataset.action === 'join' ? '/api/events/join' : button.dataset.action === 'leave' ? '/api/events/leave' : '/api/events/delete';
          await api(route, { method: 'POST', body: JSON.stringify({ event_id: Number(button.dataset.id) }) });
          await renderEvents();
        });
      });
    };

    await renderEvents();
  }

  if (page === 'event-create') {
    const user = await ensureAuth(true);
    if (!user) return;

    attachLogoutHandler();

    document.getElementById('event-form')?.addEventListener('submit', async (event) => {
      event.preventDefault();
      const fd = new FormData(event.target);
      const result = await api('/api/events', {
        method: 'POST',
        body: JSON.stringify({ gym_name: fd.get('gym_name'), starts_at_utc: new Date(fd.get('starts_local')).toISOString(), notes: fd.get('notes') }),
      });
      if (result.error) return setMessage(result.error, true);
      window.location.href = '/';
    });
  }
}

void boot();
