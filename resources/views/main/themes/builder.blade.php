<div class="d-flex flex-wrap align-items-center gap-2 px-3 pt-3">
    <a
        class="btn shadow-none border-none ps-0"
        href="{{ admin_url('themes') }}"
    ><i class="fa fa-angle-left fs-4 align-bottom"></i></a>
    <h4 class="page-title mb-0 lh-base">
        <span>{!! Template::getHeading() !!}</span>
    </h4>
    <div class="page-builder-header-actions d-flex gap-2 ms-auto">
        {!! Template::getButtonList() !!}
    </div>
</div>
{!! form_open(current_url(), [
    'id' => 'edit-form',
    'role' => 'form',
    'method' => 'PATCH',
]) !!}

{!! $formWidget->render() !!}

{!! form_close() !!}
