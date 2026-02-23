<!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ $title ?? 'ClimbSched' }}</title>
  <link rel="stylesheet" href="/styles.css">
</head>
<body data-page="{{ $page ?? '' }}">
@yield('body')
<script src="/assets/app.js" defer></script>
</body>
</html>
