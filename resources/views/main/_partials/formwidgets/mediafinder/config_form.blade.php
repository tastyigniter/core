<div class="modal-content">
    <div class="modal-header">
        <h4 class="modal-title">@lang('igniter::main.media_manager.help_attachment_config')</h4>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
    </div>
    {!! form_open([
        'id' => 'attachment-config-form',
        'role' => 'form',
        'method' => 'POST',
        'data-request' => $self->alias.'::onSaveAttachmentConfig',
    ]) !!}
    <input type="hidden" name="media_id" value="{{ $formMediaId }}">
    <div class="modal-body">
        <div class="form-fields p-0">
            @foreach ($formWidget->getFields() as $field)
                {!! $formWidget->renderField($field) !!}
            @endforeach
        </div>
    </div>
    <div class="modal-footer text-right">
        <a
            class="btn-link mr-auto"
            href="{{ $formWidget->model->getPath() }}"
            target="_blank"
        ><i class="fa fa-link"></i></a>
        <button
            type="button"
            class="btn btn-link"
            data-bs-dismiss="modal"
        >@lang('igniter::admin.button_close')</button>
        <button
            type="submit"
            class="btn btn-primary"
        >@lang('igniter::admin.button_save')</button>
    </div>
    {!! form_close() !!}
</div>
