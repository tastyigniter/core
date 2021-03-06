@php
    $activeTab = $activeTab ? $activeTab : '#'.$tabs->section.'tab-1';
@endphp
<div class="tab-heading">
    <ul class="form-nav nav nav-tabs">
        @foreach ($tabs as $name => $fields)
            <li class="nav-item">
                <a
                    class="nav-link{{ (('#'.$tabs->section.'tab-'.$loop->iteration) == $activeTab) ? ' active' : '' }}"
                    href="{{ '#'.$tabs->section.'tab-'.$loop->iteration }}"
                    data-bs-toggle="tab"
                >@lang($name)</a>
            </li>
        @endforeach
    </ul>
</div>

<div class="tab-content">
    @foreach ($tabs as $name => $fields)
        <div
            class="tab-pane {{ (('#'.$tabs->section.'tab-'.$loop->iteration) == $activeTab) ? 'active' : '' }}"
            id="{{ $tabs->section.'tab-'.$loop->iteration }}"
        >
            @if($name === 'lang:igniter::admin.locations.text_tab_options')
                {!! $self->makePartial('locations/form_accordions', ['accordions' => $self->controller->getAccordionFields($fields)]) !!}
            @else
                <div class="form-fields">
                    {!! $self->makePartial('form/form_fields', ['fields' => $fields]) !!}
                </div>
            @endif
        </div>
    @endforeach
</div>
