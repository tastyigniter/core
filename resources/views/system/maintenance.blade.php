<!DOCTYPE html>
<html lang="{{ App::getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@lang('igniter::main.text_maintenance_enabled')</title>
    <link rel="shortcut icon" href="{{ asset('vendor/igniter/images/favicon.svg') }}" type="image/ico">
    <style>{{Template::renderStaticCss()}}</style>
</head>
<body>
<article>
    {!! $message !!}
</article>
</body>
</html>
