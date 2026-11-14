@if($showPages)
    <div class="page-builder-screen d-lg-flex align-items-start gap-3">
        {!! $this->makePartial('pagebuilder/pages') !!}

        <div class="page-builder-main card shadow-sm mb-3 flex-grow-1">
            {!! $this->makePartial('pagebuilder/toolbar') !!}

            <div class="p-3">
                <div class="form-label">{{ sprintf(lang('igniter::main.builder.label_page_sections'), $pageLabel) }}</div>
                {!! $this->makePartial('pagebuilder/editor') !!}
            </div>
        </div>
    </div>
@else
    {!! $this->makePartial('pagebuilder/editor') !!}
@endif
