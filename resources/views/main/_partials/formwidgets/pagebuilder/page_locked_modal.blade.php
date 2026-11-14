<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header">
            <h4 class="modal-title">{{ sprintf(lang('igniter::main.builder.text_page_settings'), $pageLabel) }}</h4>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="@lang('igniter::admin.button_close')"></button>
        </div>
        <div class="modal-body">
            <div class="alert alert-warning d-flex gap-3 mb-0" role="alert">
                <i class="fa fa-lock mt-1"></i>
                <div>
                    <p class="fw-bold mb-1">@lang('igniter::main.builder.text_theme_locked')</p>
                    <p class="mb-0">@lang('igniter::main.builder.help_theme_locked')</p>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button
                type="button"
                class="btn btn-link text-danger fw-bold text-decoration-none"
                data-bs-dismiss="modal"
            >@lang('igniter::admin.button_close')</button>
            <button
                type="button"
                class="btn btn-primary"
                data-request="onCreateChild"
                data-progress-indicator="@lang('igniter::admin.text_saving')"
            >@lang('igniter::system.themes.button_child')</button>
        </div>
    </div>
</div>
