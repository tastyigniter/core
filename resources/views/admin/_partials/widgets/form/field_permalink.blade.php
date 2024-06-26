@if($this->previewMode)
    <p class="form-control-static">{{ $field->value }}</p>
@else
    <div class="field-permalink">
        <div class="input-group">
            <span class="input-group-text">{{  page_url() }}</span>
            <input
                type="text"
                name="{{  $field->getName()  }}"
                id="input-slug"
                class="form-control"
                value="{{ $field->value }}"
                {!! $field->hasAttribute('maxlength') ? '' : 'maxlength="255"' !!}
                {!! $field->getAttributes() !!}
            />
        </div>
    </div>
@endif
