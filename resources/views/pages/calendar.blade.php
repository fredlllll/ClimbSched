@extends('layout', ['title' => 'ClimbSched', 'page' => 'calendar'])
@section('body')
<header>
  <h1>ClimbSched</h1>
  <nav>
    <a href="/events/new">Termin erstellen</a>
    <button id="logout-btn">Logout</button>
  </nav>
</header>
<main>
  <p id="message"></p>
  <h2>Kalender</h2>
  <p class="hint">Zeigt gestern, heute und die nächsten 7 Tage. Tagesansicht startet jeweils um 06:00 Uhr.</p>
  <section id="events" class="calendar-grid"></section>
</main>
@endsection
