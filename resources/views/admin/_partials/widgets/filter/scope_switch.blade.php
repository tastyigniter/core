<div class="filter-scope switch form-group mb-0">
    <select
        name="{!! $this->getScopeName($scope) !!}"
        class="form-select"
        {!! $scope->disabled ? 'disabled="disabled"' : '' !!}
    >
        <option value="">@lang($scope->label)</option>
        <option value="1" {!! ($scope->value == '1') ? 'selected="selected"' : '' !!}>@lang('igniter::admin.text_enabled')</option>
        <option value="0" {!! ($scope->value == '0') ? 'selected="selected"' : '' !!}>@lang('igniter::admin.text_disabled')</option>
    </select>
</div>
