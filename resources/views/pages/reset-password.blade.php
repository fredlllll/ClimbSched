@extends('layout', ['title' => 'Neues Passwort', 'page' => 'reset'])
@section('body')
<main><h1>Neues Passwort</h1><p id="message"></p>
<form id="reset-form"><label>E-Mail <input type="email" name="email" required></label><label>Neues Passwort <input type="password" name="password" minlength="8" required></label><button type="submit">Speichern</button></form>
<p><a href="/login">Zum Login</a></p></main>
@endsection
