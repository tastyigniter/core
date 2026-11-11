<div class="d-flex p-3">
    <a
        class="btn shadow-none border-none ps-0"
        href="{{ admin_url('themes') }}"
    ><i class="fa fa-angle-left fs-4 align-bottom"></i></a>
    <h4 class="page-title mb-0 lh-base">
        <span>{!! Template::getHeading() !!}</span>
    </h4>
</div>
<div class="page-builder-screen d-lg-flex align-items-start gap-3 px-3">
    <aside class="page-builder-pages card shadow-sm mb-3" aria-label="@lang('igniter::main.builder.text_pages')">
        <div class="card-header">
            <span>@lang('igniter::main.builder.text_pages')</span>
            <span class="badge text-bg-light ms-2">{{ count($builderPages) }}</span>
        </div>
        <nav class="list-group list-group-flush" tabindex="0" data-page-builder-pages>
            @foreach($builderPages as $code => $page)
                <a
                    @class(['list-group-item list-group-item-action', 'active' => $code === $builderPageCode])
                    @if($code === $builderPageCode) aria-current="page" @endif
                    href="{{ admin_url('themes/builder/'.$builderTheme->getName().'/'.$code) }}"
                >
                    <span class="d-block text-truncate">@lang($page['label'])</span>
                    <small @class(['d-block text-truncate', 'text-muted' => $code !== $builderPageCode])>{{ $code }}</small>
                </a>
            @endforeach
        </nav>
        <script>
            // Keep the current page in view when the list is scrolled
            document.querySelector('[data-page-builder-pages] [aria-current="page"]')
                ?.scrollIntoView({block: 'nearest'});
        </script>
    </aside>

    <div class="page-builder-main card shadow-sm mb-3 flex-grow-1">
        {!! form_open(current_url(), [
            'id' => 'edit-form',
            'role' => 'form',
            'method' => 'PATCH',
        ]) !!}

        <div class="border-bottom d-flex align-items-center flex-wrap">
            <div class="flex-grow-1">
                {!! $toolbarWidget->render() !!}
            </div>
            @if($builderPageUrl)
                <a
                    class="btn btn-light mx-3 my-2"
                    href="{{ $builderPageUrl }}"
                    target="_blank"
                    rel="noopener"
                ><i class="fa fa-up-right-from-square"></i>&nbsp;@lang('igniter::main.builder.button_view_page')</a>
            @endif
        </div>

        {!! $formWidget->render() !!}

        {!! form_close() !!}
    </div>
</div>
