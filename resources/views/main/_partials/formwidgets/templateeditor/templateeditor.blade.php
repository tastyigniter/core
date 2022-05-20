@unless ($self->previewMode)
    <div
        id="{{ $self->getId() }}"
        class="control-template-editor progress-indicator-container"
        data-control="template-editor"
        data-alias="{{ $self->alias }}"
    >
        {!! $self->makePartial('templateeditor/toolbar') !!}

        {!! $self->makePartial('templateeditor/modal') !!}

        @if ($templateWidget)
            <div
                id="{{ $self->getId($templatePrimaryTabs->section.'-tabs') }}"
                class="{{ $templatePrimaryTabs->cssClass }}">
                <div class="py-3">
                    {!! $templateWidget->makePartial('form/form_fields', ['fields' => $templatePrimaryTabs]) !!}
                </div>
            </div>

            <div
                id="{{ $self->getId($templateSecondaryTabs->section.'-tabs') }}"
                class="{{ $templateSecondaryTabs->section }}-tabs {{ $templateSecondaryTabs->cssClass }} mx-n3"
                data-control="form-tabs"
                data-store-name="{{ $templateWidget->getCookieKey() }}">
                {!! $templateWidget->makePartial('form/form_tabs', [
                    'activeTab' => $templateWidget->getActiveTab(),
                    'tabs' => $templateSecondaryTabs
                ]) !!}
            </div>
        @endif

    </div>
@endunless
