@extends('layout', ['title' => 'E-Mail bestätigen', 'page' => 'verify'])
@section('body')
<main><h1>E-Mail bestätigen</h1><p id="message">Bitte bestätige deine E-Mail über den Link in der E-Mail.</p>
<button id="resend-btn">Verifizierungs-E-Mail erneut senden</button><p><a href="/">Zur App</a></p></main>
@endsection
