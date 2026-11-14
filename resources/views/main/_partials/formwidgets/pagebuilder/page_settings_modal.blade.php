<div class="modal-dialog modal-dialog-scrollable modal-lg">
    {!! form_open([
        'role' => 'form',
        'method' => 'PATCH',
        'data-request' => $formHandler,
        'class' => 'page-builder-settings-form w-100',
    ]) !!}
    <input type="hidden" name="pageCode" value="{{ $pageCode }}">
    <div class="modal-content">
        <div class="modal-header">
            <h4 class="modal-title">{{ sprintf(lang('igniter::main.builder.text_page_settings'), $pageLabel) }}</h4>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="@lang('igniter::admin.button_close')"></button>
        </div>
        <div class="modal-body p-0">
            {!! $formWidget->render() !!}
        </div>
        <div class="modal-footer">
            <button
                type="button"
                class="btn btn-link text-danger fw-bold text-decoration-none"
                data-bs-dismiss="modal"
            >@lang('igniter::admin.button_close')</button>
            <button type="submit" class="btn btn-primary">@lang('igniter::admin.button_save')</button>
        </div>
    </div>
    {!! form_close() !!}
</div>
