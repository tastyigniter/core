<div class="d-flex p-3">
    <a
        class="btn shadow-none border-none ps-0"
        href="{{ admin_url($builderUrl) }}"
        aria-label="@lang('igniter::admin.button_back')"
    ><i class="fa fa-angle-left fs-4 align-bottom"></i></a>
    <h4 class="page-title mb-0 lh-base">
        <span>{!! Template::getHeading() !!}</span>
    </h4>
</div>
<div class="mail-builder-main card shadow-sm mx-3 mb-3">
    {!! form_open(current_url(), [
        'id' => 'edit-form',
        'role' => 'form',
        'method' => 'PATCH',
        'data-mail-builder-can-reset' => $builderCanReset ? 'true' : null,
    ]) !!}

    {!! $formWidget->render() !!}

    {!! form_close() !!}
</div>
