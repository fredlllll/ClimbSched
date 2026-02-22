@extends('layout', ['title' => 'Registrieren', 'page' => 'register'])
@section('body')
<main><h1>Registrierung</h1><p id="message"></p>
<form id="register-form"><label>Name <input name="name" required></label><label>E-Mail <input type="email" name="email" required></label><label>Passwort <input type="password" name="password" minlength="8" required></label><button type="submit">Account erstellen</button></form>
<p><a href="/login">Zum Login</a></p></main>
@endsection
