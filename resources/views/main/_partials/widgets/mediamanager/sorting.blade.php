@php
    $sortName = isset($sortBy[0]) ? $sortBy[0] : null;
    $sortDirection = isset($sortBy[1]) ? $sortBy[1] : 'ascending';
    $sortIcon = ($sortDirection === 'ascending') ? '-up' : '-down';
@endphp
<div class="dropdown-menu" role="menu">
    <h6 class="dropdown-header">@lang('igniter::main.media_manager.text_sort_by')</h6>
    <div class="dropdown-divider"></div>
    <a
        role="button"
        class="dropdown-item {{ ($sortName == 'name') ? 'active' : '' }}"
        data-media-sort="name"
    >@lang('igniter::main.media_manager.label_name')</a>
    <a
        role="button"
        class="dropdown-item {{ ($sortName == 'date') ? 'active' : ''}}"
        data-media-sort="date"
    >@lang('igniter::main.media_manager.label_date')</a>
    <a
        role="button"
        class="dropdown-item {{ ($sortName == 'size') ? 'active' : ''}}"
        data-media-sort="size"
    >@lang('igniter::main.media_manager.label_size')</a>
    <a
        role="button"
        class="dropdown-item {{ ($sortName == 'extension') ? 'active' : ''}}"
        data-media-sort="extension"
    >@lang('igniter::main.media_manager.label_type')</a>
</div>
