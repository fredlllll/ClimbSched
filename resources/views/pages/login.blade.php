@extends('layout', ['title' => 'Login', 'page' => 'login'])
@section('body')
<main><h1>ClimbSched Login</h1><p id="message"></p>
<form id="login-form"><label>E-Mail <input type="email" name="email" required></label><label>Passwort <input type="password" name="password" required></label><button type="submit">Einloggen</button></form>
<p><a href="/register">Registrieren</a> · <a href="/forgot-password">Passwort vergessen</a></p></main>
@endsection
