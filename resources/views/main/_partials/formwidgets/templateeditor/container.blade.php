<div
    class="control-template-editor progress-indicator-container"
>
    {!! $this->makePartial('templateeditor/toolbar') !!}

    @if($templateWidget)
        <div
            id="{{ $this->getId($templateSecondaryTabs->section.'-tabs') }}"
            class="{{ $templateSecondaryTabs->section }}-tabs mt-5 {{ $templateSecondaryTabs->cssClass }}"
            data-control="form-tabs"
            data-store-name="{{ $templateWidget->getCookieKey() }}"
        >
            <div class="row">
                <div class="col-md-3">
                    <div
                        id="{{ $this->getId($templatePrimaryTabs->section.'-tabs') }}"
                        class="{{ $templatePrimaryTabs->cssClass }}">
                        <div class="py-3">
                            {!! $templateWidget->makePartial('form/form_fields', ['fields' => $templatePrimaryTabs]) !!}
                        </div>
                    </div>
                </div>
                <div @class(['col-md-9' => $templatePrimaryTabs->hasFields(), 'col-md-12' => !$templatePrimaryTabs->hasFields()])>
                    <div
                        id="{{ $this->getId($templateSecondaryTabs->section.'-tabs') }}"
                        class="{{ $templateSecondaryTabs->section }}-tabs {{ $templateSecondaryTabs->cssClass }} border rounded"
                        data-control="form-tabs"
                        data-store-name="{{ $templateWidget->getCookieKey() }}"
                    >
                        <div class="tab-heading border-bottom py-2">
                            <ul class="form-nav nav nav-pills fw-bold py-0">
                                @foreach($templateSecondaryTabs as $name => $fields)
                                    <li class="nav-item mr-3">
                                        <a
                                            @class([
                                                'nav-link px-2 py-1',
                                                'active' => (('#'.$templateSecondaryTabs->section.'tab-'.$loop->iteration) == $templateWidget->getActiveTab())
                                            ])
                                            href="{{ '#'.$templateSecondaryTabs->section.'tab-'.$loop->iteration }}"
                                            data-bs-toggle="tab"
                                        >@lang($name)</a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                        <div class="tab-content">
                            @foreach($templateSecondaryTabs as $name => $fields)
                                <div
                                    class="tab-pane {{ (('#'.$templateSecondaryTabs->section.'tab-'.$loop->iteration) == $templateWidget->getActiveTab()) ? 'active' : '' }}"
                                    id="{{ $templateSecondaryTabs->section.'tab-'.$loop->iteration }}">
                                    <div class="form-fields px-0 py-3">
                                        {!! $templateWidget->makePartial('form/form_fields', ['fields' => $fields]) !!}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
