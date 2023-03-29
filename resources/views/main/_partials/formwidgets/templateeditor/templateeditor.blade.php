@unless($this->previewMode)
    <div
        id="{{ $this->getId() }}"
        data-control="template-editor"
        data-alias="{{ $this->alias }}"
    >
        {!! $this->makePartial('templateeditor/modal') !!}

        <div
            id="{{ $this->getId('container') }}"
        >
            {!! $this->makePartial('templateeditor/container') !!}
        </div>
    </div>
@endunless
