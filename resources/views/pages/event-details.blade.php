@extends('layout', ['title' => 'Event Details', 'page' => 'event-detail'])
@section('body')
<header>
  <h1><a class="brand-link" href="/">ClimbSched</a></h1>
  <nav>
    <a href="/">Zum Kalender</a>
    <button id="logout-btn">Logout</button>
  </nav>
</header>
<main data-event-id="{{ $eventId }}">
  <p id="message"></p>
  <section id="event-detail" class="event-detail-card"></section>
</main>
@endsection
