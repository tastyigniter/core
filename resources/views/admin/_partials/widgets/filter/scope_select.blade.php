<div class="filter-scope select form-group">
    <select
        name="{{ $this->getScopeName($scope) }}"
        data-control="selectlist"
        @if($scope->label) data-placeholder="@lang($scope->label)" @endif
        {!! $scope->disabled ? 'disabled="disabled"' : '' !!}
    >
        @php $options = $this->getSelectOptions($scope->scopeName) @endphp
        @foreach($options['available'] as $key => $value)
            <option
                value="{{ $key }}"
                {!! ($options['active'] == $key) ? 'selected="selected"' : '' !!}
            >{{ (strpos($value, 'lang:') !== false) ? lang($value) : $value }}</option>
        @endforeach
    </select>
</div>
