<div
    id="{{ $this->getId('modal') }}"
    class="modal fade"
    tabindex="-1"
    role="dialog"
    aria-labelledby="newSourceModal"
    aria-hidden="true"
>
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4
                    class="modal-title"
                    data-modal-text="title"
                ></h4>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>@lang('igniter::system.themes.label_file')</label>
                    <input data-modal-input="source-name" type="text" class="form-control" name="name"/>
                    <input data-modal-input="source-action" type="hidden" name="action"/>
                </div>
            </div>
            <div class="modal-footer">
                <button
                    type="button"
                    class="btn btn-secondary"
                    data-bs-dismiss="modal"
                >@lang('igniter::admin.button_close')</button>
                <button
                    type="button"
                    class="btn btn-primary"
                    data-request="{{ $this->getEventHandler('onManageSource') }}"
                >@lang('igniter::admin.button_save')</button>
            </div>
        </div>
    </div>
</div>
