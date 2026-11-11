<div class="d-flex p-3">
    <a
        class="btn shadow-none border-none ps-0"
        href="{{ admin_url('themes') }}"
    ><i class="fa fa-angle-left fs-4 align-bottom"></i></a>
    <h4 class="page-title mb-0 lh-base">
        <span>{!! Template::getHeading() !!}</span>
    </h4>
</div>
<div class="row-fluid">
    <div class="card shadow-sm mx-3">
        {!! form_open(current_url(), [
            'id' => 'edit-form',
            'role' => 'form',
            'method' => 'PATCH',
        ]) !!}

        <div class="border-bottom">
            {!! $toolbarWidget->render() !!}
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2 px-3 pt-3">
            <ul class="nav nav-pills flex-wrap">
                @foreach($builderPages as $code => $page)
                    <li class="nav-item">
                        <a
                            @class(['nav-link', 'active' => $code === $builderPageCode])
                            @if($code === $builderPageCode) aria-current="page" @endif
                            href="{{ admin_url('themes/builder/'.$builderTheme->getName().'/'.$code) }}"
                        >@lang($page['label'])</a>
                    </li>
                @endforeach
            </ul>
            @if($builderPageUrl)
                <a
                    class="btn btn-light ms-auto"
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
