<div class="border-bottom d-flex align-items-center flex-wrap">
    <div class="toolbar btn-toolbar flex-grow-1">
        <div class="toolbar-action px-3 py-2">
            <div class="progress-indicator-container">
                <button
                    type="button"
                    class="btn btn-primary"
                    data-request="{{ $this->getEventHandler('onSavePage') }}"
                    data-progress-indicator="@lang('igniter::admin.text_saving')"
                >@lang('igniter::admin.button_save')</button>
                <button
                    type="button"
                    class="btn btn-default"
                    data-request="{{ $this->getEventHandler('onResetPage') }}"
                    data-request-confirm="@lang('igniter::main.builder.confirm_reset')"
                    data-progress-indicator="@lang('igniter::admin.text_saving')"
                >@lang('igniter::main.builder.button_reset')</button>
            </div>
        </div>
    </div>
    @if($viewPageUrl)
        <a
            class="btn btn-light mx-3 my-2"
            href="{{ $viewPageUrl }}"
            target="_blank"
            rel="noopener"
        ><i class="fa fa-up-right-from-square"></i>&nbsp;@lang('igniter::main.builder.button_view_page')</a>
    @endif
</div>
