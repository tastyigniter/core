<!DOCTYPE html>
<html lang="{{ App::getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>System Error (500)</title>
    <link rel="shortcut icon" href="{{ asset('vendor/igniter/images/favicon.svg') }}" type="image/ico">
    <link href="{{ asset('vendor/igniter/css/static.css') }}" rel="stylesheet">
</head>
<body>
<article>
    <p class="lead">@lang('igniter::main.alert_custom_error')</p>
    <p>{{$exception->getMessage()}}</p>
</article>
</body>
</html>
