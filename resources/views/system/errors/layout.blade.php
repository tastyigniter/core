<!DOCTYPE html>
<html lang="{{ App::getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title')</title>
    <link rel="shortcut icon" href="{{ asset('vendor/igniter/images/favicon.svg') }}" type="image/ico">
    <style>{{ asset('vendor/igniter/css/static.css') }}</style>
</head>
<body>
<article>
    <p class="lead">@yield('message')</p>
</article>
</body>
</html>
