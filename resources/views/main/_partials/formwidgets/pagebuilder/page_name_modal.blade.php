<div class="modal-dialog">
    {!! form_open([
        'role' => 'form',
        'method' => 'POST',
        'data-request' => $formHandler,
        'class' => 'w-100',
    ]) !!}
    <input type="hidden" name="pageCode" value="{{ $pageCode }}">
    <div class="modal-content">
        <div class="modal-header">
            <h4 class="modal-title">@lang($pageCode !== '' ? 'igniter::main.builder.text_rename_page' : 'igniter::main.builder.text_new_page')</h4>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="@lang('igniter::admin.button_close')"></button>
        </div>
        <div class="modal-body">
            <label for="page-builder-page-name" class="form-label">@lang('igniter::main.builder.label_page_name')</label>
            <input
                type="text"
                id="page-builder-page-name"
                name="name"
                class="form-control"
                value="{{ $pageLabel }}"
                maxlength="160"
                required
                autofocus
                aria-describedby="page-builder-page-name-help"
            />
            <div id="page-builder-page-name-help" class="form-text">@lang('igniter::main.builder.help_page_name')</div>
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
