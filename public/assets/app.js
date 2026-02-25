(() => {
  // resources/ts/app.ts
  var page = document.body.dataset.page;
  var messageEl = document.getElementById("message");
  var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "";
  var CALENDAR_DAYS_BEFORE_TODAY = 1;
  var CALENDAR_DAYS_AFTER_TODAY = 7;
  var CALENDAR_DAY_START_HOUR = 6;
  function setMessage(text, isError = false) {
    if (!messageEl) return;
    messageEl.textContent = text;
    messageEl.className = isError ? "flash flash-error" : "flash flash-success";
  }
  async function api(url, options = {}) {
    const response = await fetch(url, {
      credentials: "same-origin",
      headers: {
        "Content-Type": "application/json",
        "X-CSRF-TOKEN": csrf,
        ...options.headers || {}
      },
      ...options
    });
    const json = await response.json().catch(() => ({}));
    if (!response.ok && json?.message && !json.error) json.error = json.message;
    return json;
  }
  function queryParams() {
    return new URLSearchParams(window.location.search);
  }
  async function ensureAuth(verified = false) {
    const me = await api("/api/me");
    if (!me.authenticated || !me.user) {
      window.location.href = "/login";
      return null;
    }
    if (verified && !me.user.email_verified_at) {
      window.location.href = "/verify-email";
      return null;
    }
    return me.user;
  }
  function formatLocal(utcIso) {
    return new Intl.DateTimeFormat(void 0, { weekday: "short", day: "2-digit", month: "2-digit", hour: "2-digit", minute: "2-digit" }).format(new Date(utcIso));
  }
  function initials(name) {
    const parts = String(name || "").trim().split(/\s+/).filter(Boolean);
    if (!parts.length) return "?";
    return parts.slice(0, 2).map((part) => part[0].toUpperCase()).join("");
  }
  function avatarColor(name) {
    const palette = ["#ef4444", "#f59e0b", "#10b981", "#06b6d4", "#3b82f6", "#8b5cf6", "#ec4899"];
    const seed = String(name || "").split("").reduce((sum, char) => sum + char.charCodeAt(0), 0);
    return palette[seed % palette.length];
  }
  function attachLogoutHandler() {
    document.getElementById("logout-btn")?.addEventListener("click", async () => {
      await api("/api/logout", { method: "POST", body: "{}" });
      window.location.href = "/login";
    });
  }
  function dayKeyForDisplay(date) {
    const shifted = new Date(date);
    shifted.setHours(shifted.getHours() - CALENDAR_DAY_START_HOUR);
    const year = shifted.getFullYear();
    const month = String(shifted.getMonth() + 1).padStart(2, "0");
    const day = String(shifted.getDate()).padStart(2, "0");
    return `${year}-${month}-${day}`;
  }
  function buildCalendarRange() {
    const start = /* @__PURE__ */ new Date();
    start.setHours(0, 0, 0, 0);
    start.setDate(start.getDate() - CALENDAR_DAYS_BEFORE_TODAY);
    start.setHours(CALENDAR_DAY_START_HOUR, 0, 0, 0);
    const totalDays = CALENDAR_DAYS_BEFORE_TODAY + 1 + CALENDAR_DAYS_AFTER_TODAY;
    const end = new Date(start);
    end.setHours(end.getHours() + totalDays * 24);
    return { start, end, totalDays };
  }
  async function boot() {
    if (page === "login") {
      document.getElementById("login-form")?.addEventListener("submit", async (event) => {
        event.preventDefault();
        const fd = new FormData(event.target);
        const result = await api("/api/login", { method: "POST", body: JSON.stringify({ email: fd.get("email"), password: fd.get("password") }) });
        if (result.error) return setMessage(result.error, true);
        window.location.href = "/";
      });
    }
    if (page === "register") {
      document.getElementById("register-form")?.addEventListener("submit", async (event) => {
        event.preventDefault();
        const fd = new FormData(event.target);
        const result = await api("/api/register", { method: "POST", body: JSON.stringify({ name: fd.get("name"), email: fd.get("email"), password: fd.get("password") }) });
        if (result.error) return setMessage(result.error, true);
        window.location.href = "/verify-email";
      });
    }
    if (page === "verify") {
      const user = await ensureAuth();
      if (!user) return;
      if (user.email_verified_at) {
        window.location.href = "/";
        return;
      }
      document.getElementById("resend-btn")?.addEventListener("click", async () => {
        const result = await api("/api/email/verification-notification", { method: "POST", body: "{}" });
        setMessage(result.error ?? result.message ?? "Gesendet", !!result.error);
      });
    }
    if (page === "forgot") {
      document.getElementById("forgot-form")?.addEventListener("submit", async (event) => {
        event.preventDefault();
        const fd = new FormData(event.target);
        const result = await api("/api/password/forgot", { method: "POST", body: JSON.stringify({ email: fd.get("email") }) });
        setMessage(result.error ?? result.message ?? "Falls vorhanden, gesendet", !!result.error);
      });
    }
    if (page === "reset") {
      const params = queryParams();
      document.getElementById("reset-form")?.addEventListener("submit", async (event) => {
        event.preventDefault();
        const fd = new FormData(event.target);
        const result = await api("/api/password/reset", {
          method: "POST",
          body: JSON.stringify({ token: params.get("token"), email: fd.get("email"), password: fd.get("password") })
        });
        if (result.error) return setMessage(result.error, true);
        window.location.href = "/login";
      });
    }
    if (page === "calendar") {
      const user = await ensureAuth(true);
      if (!user) return;
      attachLogoutHandler();
      const renderEvents = async () => {
        const { start, end, totalDays } = buildCalendarRange();
        const eventsBox = document.getElementById("events");
        const data = await api(`/api/events?${new URLSearchParams({ start: start.toISOString(), end: end.toISOString() })}`);
        if (data.error) return setMessage(data.error, true);
        const eventsByDay = /* @__PURE__ */ new Map();
        for (const event of data.events) {
          const localStart = new Date(event.starts_at_utc);
          const dayKey = dayKeyForDisplay(localStart);
          if (!eventsByDay.has(dayKey)) eventsByDay.set(dayKey, []);
          eventsByDay.get(dayKey).push(event);
        }
        const daySections = [];
        for (let dayOffset = 0; dayOffset < totalDays; dayOffset++) {
          const dayStart = new Date(start);
          dayStart.setDate(dayStart.getDate() + dayOffset);
          const dayKey = dayKeyForDisplay(dayStart);
          const dayLabel = new Intl.DateTimeFormat(void 0, { weekday: "short", day: "2-digit", month: "2-digit" }).format(dayStart);
          const dayEvents = (eventsByDay.get(dayKey) || []).sort((a, b) => new Date(a.starts_at_utc) - new Date(b.starts_at_utc));
          const cards = dayEvents.map((event) => {
            const names = event.participant_names?.length ? event.participant_names : Array.from({ length: Number(event.participants || 0) }, (_, index) => `User ${index + 1}`);
            const avatars = names.map((name) => `<span class="avatar" style="background:${avatarColor(name)}" title="${name}">${initials(name)}</span>`).join("");
            return `
            <a class="event-card event-card-link" href="/events/${event.id}">
              <h4>${event.gym_name}</h4>
              <div class="participants-row">${avatars}</div>
            </a>
          `;
          }).join("");
          daySections.push(`
          <section class="calendar-day">
            <h3>${dayLabel}</h3>
            <div class="day-events">${cards || '<p class="day-empty">Keine Termine</p>'}</div>
          </section>
        `);
        }
        eventsBox.innerHTML = daySections.join("");
      };
      await renderEvents();
    }
    if (page === "event-detail") {
      const user = await ensureAuth(true);
      if (!user) return;
      attachLogoutHandler();
      const eventId = Number(document.querySelector("main")?.dataset.eventId || 0);
      if (!eventId) {
        return setMessage("Ung\xFCltige Event-ID", true);
      }
      const result = await api(`/api/events/${eventId}`);
      if (result.error || !result.event) return setMessage(result.error ?? "Termin nicht gefunden", true);
      const event = result.event;
      const names = event.participant_names?.length ? event.participant_names : Array.from({ length: Number(event.participants || 0) }, (_, index) => `User ${index + 1}`);
      const avatars = names.map((name) => `<span class="avatar" style="background:${avatarColor(name)}" title="${name}">${initials(name)}</span>`).join("");
      const container = document.getElementById("event-detail");
      container.innerHTML = `
      <h2>${event.gym_name}</h2>
      <p><strong>Start:</strong> ${formatLocal(event.starts_at_utc)}</p>
      <p><strong>Erstellt von:</strong> ${event.creator_name || "-"}</p>
      <p><strong>Teilnehmer:</strong> ${event.participants}</p>
      ${event.notes ? `<p><strong>Notiz:</strong> ${event.notes}</p>` : ""}
      <div class="participants-row">${avatars}</div>
      <div class="actions">
        <button id="join-leave-btn">${event.joined ? "Verlassen" : "Beitreten"}</button>
        ${event.creator_id === user.id ? '<button id="delete-btn">L\xF6schen</button>' : ""}
      </div>
    `;
      document.getElementById("join-leave-btn")?.addEventListener("click", async () => {
        const route = event.joined ? "/api/events/leave" : "/api/events/join";
        await api(route, { method: "POST", body: JSON.stringify({ event_id: event.id }) });
        window.location.reload();
      });
      document.getElementById("delete-btn")?.addEventListener("click", async () => {
        await api("/api/events/delete", { method: "POST", body: JSON.stringify({ event_id: event.id }) });
        window.location.href = "/";
      });
    }
    if (page === "event-create") {
      const user = await ensureAuth(true);
      if (!user) return;
      attachLogoutHandler();
      document.getElementById("event-form")?.addEventListener("submit", async (event) => {
        event.preventDefault();
        const fd = new FormData(event.target);
        const result = await api("/api/events", {
          method: "POST",
          body: JSON.stringify({ gym_name: fd.get("gym_name"), starts_at_utc: new Date(fd.get("starts_local")).toISOString(), notes: fd.get("notes") })
        });
        if (result.error) return setMessage(result.error, true);
        window.location.href = "/";
      });
    }
  }
  void boot();
})();
