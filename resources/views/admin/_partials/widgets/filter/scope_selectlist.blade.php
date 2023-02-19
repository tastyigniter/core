@php
    $isCheckboxMode = $scope->mode ?? 'checkbox';
    $selectMultiple = $isCheckboxMode == 'checkbox';
    $options = $this->getSelectOptions($scope->scopeName);
    $enableFilter = (count($options['available']) > 20);
@endphp
<div class="filter-scope selectlist form-group">
    <div class="control-selectlist w-100">
        <select
            name="{{ $this->getScopeName($scope).($selectMultiple ? '[]' : '') }}"
            data-control="selectlist"
            @if($scope->label) data-placeholder="@lang($scope->label)" @endif
            {!! $scope->disabled ? 'disabled="disabled"' : '' !!}
            {!! $selectMultiple ? 'multiple="multiple"' : '' !!}
        >
            @foreach($options['available'] as $key => $value)
                @php
                    if (!is_array($options['active'])) $options['active'] = [$options['active']];
                @endphp
                <option
                    value="{{ $key }}"
                    {!! in_array($key, $options['active']) ? 'selected="selected"' : '' !!}
                >{{ (strpos($value, 'lang:') !== FALSE) ? lang($value) : $value }}</option>
            @endforeach
        </select>
    </div>
</div>
