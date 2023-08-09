<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
<head>
    {!! get_metas() !!}
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @if ($site_logo !== 'no_photo.png')
        <link href="{{ media_thumb($site_logo, ['width' => 64, 'height' => 64]) }}" rel="shortcut icon" type="image/ico">
    @else
        {!! get_favicon() !!}
    @endif
    @empty($pageTitle = Template::getTitle())
        <title>{{$site_name}}</title>
    @else
        <title>{{ $pageTitle }}@lang('igniter::admin.site_title_separator'){{$site_name}}</title>
    @endempty
    @styles
</head>
<body class="page {{ $this->bodyClass }}">
<div class="h-100 w-100">
    @if(AdminAuth::isLogged())
        <x-igniter.admin::aside :navItems="AdminMenu::getVisibleNavItems()" />
    @endif
    <div class="page-wrapper">
        @if(AdminAuth::isLogged())
            <x-igniter.admin::header>
                {!! $this->widgets['mainmenu']->render() !!}
            </x-igniter.admin::header>
        @endif
        <div class="page-content pt-4">
            {!! Template::getBlock('body') !!}
        </div>
        <div id="notification">
            @partial('igniter.admin::flash')
        </div>
    </div>
    {{--    <div class="sidebar border border-right col-md-3 col-lg-2 p-0 bg-body-tertiary"></div>--}}
    {{--    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4"></main>--}}
</div>
@scripts
</body>
</html>
