<div class="d-flex">
    <div
        class="input-group control-colorpicker dropend d-inline-flex border border-2 rounded w-auto p-1"
        data-control="colorpicker"
    >
        <label class="input-group-text border-none rounded p-0">
            <input
                id="{{ $this->getId('color') }}"
                class="form-control form-control-color border-0 rounded p-0"
                type="color"
                value="{{ $value ?: '#000000' }}"
                title="Choose your color"
                data-colorpicker-color
                {!! ($this->disabled || $this->previewMode) ? 'disabled="disabled"' : '' !!}
                {!! ($this->readOnly) ? 'readonly="readonly"' : '' !!}
            />
        </label>
        <input
            id="{{ $this->getId('input') }}"
            type="text"
            class="form-control border-0 shadow-none px-2 py-0 font-monospace"
            name="{{ $name }}"
            value="{{ $value }}"
            placeholder="#000000"
            size="7"
            maxlength="7"
            spellcheck="false"
            autocomplete="off"
            data-colorpicker-text
            {!! ($this->disabled || $this->previewMode) ? 'disabled="disabled"' : '' !!}
            {!! ($this->readOnly) ? 'readonly="readonly"' : '' !!}
        />
        <button
            class="btn btn-outline-secondary border-0 dropdown-toggle shadow-none py-0"
            type="button"
            data-bs-toggle="dropdown"
            aria-expanded="false"
            {!! ($this->disabled || $this->previewMode || $this->readOnly) ? 'disabled="disabled"' : '' !!}
        ></button>
        <ul class="dropdown-menu dropdown-menu-end">
            @foreach($availableColors as $color)
                <li>
                    <button
                        class="dropdown-item mb-2"
                        type="button"
                        data-swatches-color="{{$color}}"
                        style="background-color: {{$color}};"
                    ></button>
                </li>
            @endforeach
        </ul>
    </div>
</div>
