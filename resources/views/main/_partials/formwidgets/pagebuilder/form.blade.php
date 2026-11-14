<form
    class="page-builder-settings-form"
    data-page-builder-form="{{ $formHandler }}"
    @if($formCode) data-page-builder-form-code="{{ $formCode }}" @endif
>
    <div class="modal-header">
        <h4 class="modal-title">{{ $formTitle }}</h4>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="@lang('igniter::admin.button_close')"></button>
    </div>
    <div class="modal-body p-3">
        <div class="form-fields">
            @forelse($formWidget->getFields() as $field)
                {!! $formWidget->renderField($field) !!}
            @empty
                <p class="text-muted mb-0">@lang('igniter::main.builder.text_no_settings')</p>
            @endforelse
        </div>
    </div>
    <div class="modal-footer">
        <button
            type="button"
            class="btn btn-link text-danger fw-bold text-decoration-none"
            data-bs-dismiss="modal"
        >@lang('igniter::admin.button_close')</button>
        @unless($formWidget->previewMode)
            <button type="submit" class="btn btn-primary">@lang('igniter::main.builder.button_apply')</button>
        @endunless
    </div>
</form>
