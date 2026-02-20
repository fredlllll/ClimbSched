@extends('layout', ['title' => 'Passwort vergessen', 'page' => 'forgot'])
@section('body')
<main><h1>Passwort zurücksetzen</h1><p id="message"></p>
<form id="forgot-form"><label>E-Mail <input type="email" name="email" required></label><button type="submit">Reset-Link senden</button></form>
<p><a href="/login">Zurück zum Login</a></p></main>
@endsection
