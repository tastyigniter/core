@php
$currencyModel = \Igniter\System\Models\Currency::getDefault();
$symbolAfter = $currencyModel->getSymbolPosition();
$symbol = $currencyModel->getSymbol();
@endphp
@if ($self->previewMode)
    <p class="form-control-static">{{ $field->value ? currency_format($field->value) : '0' }}</p>
@else
    <div class="input-group">
        @unless ($symbolAfter)
            <span class="input-group-text"><b>{{$symbol}}</b></span>
        @endunless
        <input
            name="{{ $field->getName() }}"
            id="{{ $field->getId() }}"
            class="form-control"
            value="{{ number_format($field->value, 2, '.', '') }}"
            placeholder="@lang($field->placeholder)"
            autocomplete="off"
            step="any"
            {!! $field->hasAttribute('pattern') ? '' : 'pattern="-?\d+(\.\d+)?"' !!}
            {!! $field->hasAttribute('maxlength') ? '' : 'maxlength="255"' !!}
            {!! $field->getAttributes() !!}
        />
        @if ($symbolAfter)
            <span class="input-group-text"><b>{{$symbol}}</b></span>
        @endif
    </div>
@endif
