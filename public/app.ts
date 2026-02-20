type ApiResult = { ok?: boolean; error?: string; message?: string; authenticated?: boolean; user?: { id: number; email_verified_at: string | null } };

const page = document.body.dataset.page;
const messageEl = document.getElementById('message');
let csrf = '';

function setMessage(text: string, isError = false): void {
  if (!messageEl) return;
  messageEl.textContent = text;
  messageEl.className = isError ? 'flash flash-error' : 'flash flash-success';
}

async function api<T = ApiResult>(url: string, options: RequestInit = {}): Promise<T> {
  const response = await fetch(url, {
    credentials: 'same-origin',
    headers: { 'Content-Type': 'application/json' },
    ...options,
  });
  return response.json() as Promise<T>;
}

async function loadCsrf(): Promise<void> {
  const data = await api<{ csrf: string }>('/api/csrf');
  csrf = data.csrf;
}

function queryParams(): URLSearchParams {
  return new URLSearchParams(window.location.search);
}

async function ensureAuth(verified = false): Promise<{ id: number; email_verified_at: string | null } | null> {
  const me = await api<ApiResult>('/api/me');
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

function formatLocal(utcIso: string): string {
  const date = new Date(utcIso);
  return new Intl.DateTimeFormat(undefined, { weekday: 'short', day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' }).format(date);
}

async function boot(): Promise<void> {
  await loadCsrf();

  if (page === 'login') {
    const form = document.getElementById('login-form') as HTMLFormElement;
    form?.addEventListener('submit', async (event) => {
      event.preventDefault();
      const fd = new FormData(form);
      const result = await api<ApiResult>('/api/login', { method: 'POST', body: JSON.stringify({ _csrf: csrf, email: fd.get('email'), password: fd.get('password') }) });
      if (result.error) return setMessage(result.error, true);
      window.location.href = '/';
    });
  }

  if (page === 'register') {
    const form = document.getElementById('register-form') as HTMLFormElement;
    form?.addEventListener('submit', async (event) => {
      event.preventDefault();
      const fd = new FormData(form);
      const result = await api<ApiResult>('/api/register', { method: 'POST', body: JSON.stringify({ _csrf: csrf, name: fd.get('name'), email: fd.get('email'), password: fd.get('password') }) });
      if (result.error) return setMessage(result.error, true);
      setMessage(result.message ?? 'Registriert');
      window.location.href = '/verify-email';
    });
  }

  if (page === 'verify') {
    const user = await ensureAuth();
    if (!user) return;

    const params = queryParams();
    const uid = params.get('uid');
    const token = params.get('token');
    if (uid && token) {
      const result = await api<ApiResult>('/api/verify-email/confirm', { method: 'POST', body: JSON.stringify({ uid: Number(uid), token }) });
      if (result.error) setMessage(result.error, true);
      else {
        setMessage('E-Mail bestätigt.');
        window.location.href = '/';
      }
    }

    const resend = document.getElementById('resend-btn');
    resend?.addEventListener('click', async () => {
      const result = await api<ApiResult>('/api/verify-email/resend', { method: 'POST', body: JSON.stringify({ _csrf: csrf }) });
      setMessage(result.error ?? result.message ?? 'Gesendet', !!result.error);
    });
  }

  if (page === 'forgot') {
    const form = document.getElementById('forgot-form') as HTMLFormElement;
    form?.addEventListener('submit', async (event) => {
      event.preventDefault();
      const fd = new FormData(form);
      const result = await api<ApiResult>('/api/password/forgot', { method: 'POST', body: JSON.stringify({ _csrf: csrf, email: fd.get('email') }) });
      setMessage(result.error ?? result.message ?? 'Fertig', !!result.error);
    });
  }

  if (page === 'reset') {
    const form = document.getElementById('reset-form') as HTMLFormElement;
    const params = queryParams();
    form?.addEventListener('submit', async (event) => {
      event.preventDefault();
      const fd = new FormData(form);
      const result = await api<ApiResult>('/api/password/reset', {
        method: 'POST',
        body: JSON.stringify({ _csrf: csrf, uid: Number(params.get('uid')), token: params.get('token'), password: fd.get('password') }),
      });
      if (result.error) return setMessage(result.error, true);
      window.location.href = '/login';
    });
  }

  if (page === 'calendar') {
    const user = await ensureAuth(true);
    if (!user) return;

    const logout = document.getElementById('logout-btn');
    logout?.addEventListener('click', async () => {
      await api('/api/logout', { method: 'POST', body: JSON.stringify({ _csrf: csrf }) });
      window.location.href = '/login';
    });

    const renderEvents = async () => {
      const start = new Date();
      start.setDate(start.getDate() - 1);
      start.setHours(0, 0, 0, 0);
      const end = new Date(start);
      end.setDate(end.getDate() + 8);

      const eventsBox = document.getElementById('events') as HTMLDivElement;
      const qs = new URLSearchParams({ start: start.toISOString(), end: end.toISOString() });
      const data = await api<{ events: Array<{ id: number; creator_id: number; creator_name: string; gym_name: string; starts_at_utc: string; notes: string | null; participants: number; joined: boolean; }> }>(`/api/events?${qs.toString()}`);

      if (!data.events.length) {
        eventsBox.innerHTML = '<p>Noch keine Termine.</p>';
        return;
      }

      eventsBox.innerHTML = data.events.map((event) => `
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
      `).join('');

      eventsBox.querySelectorAll<HTMLButtonElement>('button[data-action]').forEach((button) => {
        button.addEventListener('click', async () => {
          const action = button.dataset.action;
          const eventId = Number(button.dataset.id);
          const route = action === 'join' ? '/api/events/join' : action === 'leave' ? '/api/events/leave' : '/api/events/delete';
          await api(route, { method: 'POST', body: JSON.stringify({ _csrf: csrf, event_id: eventId }) });
          await renderEvents();
        });
      });
    };

    const form = document.getElementById('event-form') as HTMLFormElement;
    form?.addEventListener('submit', async (event) => {
      event.preventDefault();
      const fd = new FormData(form);
      const result = await api<ApiResult>('/api/events', {
        method: 'POST',
        body: JSON.stringify({
          _csrf: csrf,
          gym_name: fd.get('gym_name'),
          starts_local: fd.get('starts_local'),
          notes: fd.get('notes'),
        }),
      });

      if (result.error) return setMessage(result.error, true);
      form.reset();
      await renderEvents();
    });

    await renderEvents();
  }
}

void boot();
