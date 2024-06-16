<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
<head>
    {{Template::renderHook('startHead')}}

    {{html(get_metas())}}
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @if ($site_logo !== 'no_photo.png')
        <link href="{{ media_thumb($site_logo, ['width' => 64, 'height' => 64]) }}" rel="shortcut icon" type="image/ico">
    @else
        {{html(get_favicon())}}
    @endif
    @empty($pageTitle = Template::getTitle())
        <title>{{$site_name}}</title>
    @else
        <title>{{ $pageTitle }}@lang('igniter::admin.site_title_separator'){{$site_name}}</title>
    @endempty

    {{Template::renderHook('startStyles')}}

    @themeStyles

    {{Template::renderHook('endStyles')}}

    {{Template::renderHook('endHead')}}
</head>
<body class="page {{ $this->bodyClass }}">
{{Template::renderHook('startBody')}}
<div class="h-100 w-100">
    @if(AdminAuth::isLogged())
        {{Template::renderHook('startSidebar')}}

        <x-igniter.admin::aside :navItems="AdminMenu::getVisibleNavItems()" />

        {{Template::renderHook('endSidebar')}}
    @endif
    <div class="page-wrapper">
        {{Template::renderHook('startHeader')}}

        @if(AdminAuth::isLogged())
            <x-igniter.admin::header>
                {{html($this->widgets['mainmenu']->render())}}
            </x-igniter.admin::header>
        @endif

        {{Template::renderHook('endHeader')}}

        <div class="page-content pt-4">
            {{Template::getBlock('body')}}
        </div>
        <div id="notification">
            {{Template::renderHook('startFlash')}}

            {{html($this->makePartial('igniter.admin::flash'))}}

            {{Template::renderHook('endFlash')}}
        </div>
    </div>
</div>

{!! Assets::getJsVars() !!}

{{Template::renderHook('startScripts')}}

@themeScripts

{{Template::renderHook('endScripts')}}

{{Template::renderHook('endBody')}}
</body>
</html>
