@php
    $fieldOptions = $field->options();
    $selectMultiple = array_get($field->config, 'mode') == 'checkbox';
    $checkedValues = (array)$field->value;
    $placeholder = $field->placeholder ?: 'igniter::admin.text_please_select';
@endphp
<div class="control-selectlist">
    <select
        data-control="selectlist"
        id="{{ $field->getId() }}"
        name="{!! $field->getName().($selectMultiple ? '[]' : '') !!}"
        data-placeholder="@lang($placeholder)"
        {!! $this->previewMode ? 'disabled="disabled"' : '' !!}
        {!! $selectMultiple ? 'multiple="multiple"' : '' !!}
        {!! $field->getAttributes() !!}
    >

        @foreach($fieldOptions as $value => $option)
            @continue($field->disabled && !in_array($value, $checkedValues))
            @php
                if (!is_array($option)) $option = [$option];
            @endphp
            <option
                {!! in_array($value, $checkedValues) ? 'selected="selected"' : '' !!}
                value="{{ $value }}">
                {{ is_lang_key($option[0]) ? lang($option[0]) : $option[0] }}
                @isset($option[1])
                    <span>{{ is_lang_key($option[1]) ? lang($option[1]) : $option[1] }}</span>
                @endisset
            </option>
        @endforeach
    </select>
</div>
