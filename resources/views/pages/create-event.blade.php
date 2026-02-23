@extends('layout', ['title' => 'Termin erstellen', 'page' => 'event-create'])
@section('body')
<header>
  <h1>ClimbSched</h1>
  <nav>
    <a href="/">Zum Kalender</a>
    <button id="logout-btn">Logout</button>
  </nav>
</header>
<main>
  <p id="message"></p>
  <h2>Neuen Termin erstellen</h2>
  <form id="event-form">
    <label>Halle <input name="gym_name" required></label>
    <label>Startzeit (lokal) <input type="datetime-local" name="starts_local" required></label>
    <label>Notiz <textarea name="notes"></textarea></label>
    <button type="submit">Termin erstellen</button>
  </form>
</main>
@endsection
