@extends('layout', ['title' => 'ClimbSched', 'page' => 'calendar'])
@section('body')
<header><h1>ClimbSched</h1><nav><button id="logout-btn">Logout</button></nav></header>
<main><p id="message"></p><h2>Nächste 8 Tage (gestern + heute + 6 Tage)</h2>
<form id="event-form"><label>Halle <input name="gym_name" required></label><label>Startzeit (lokal) <input type="datetime-local" name="starts_local" required></label><label>Notiz <textarea name="notes"></textarea></label><button type="submit">Termin erstellen</button></form>
<section><h3>Termine</h3><div id="events" class="events"></div></section></main>
@endsection
