<div
    class="control-repeater"
    data-control="repeater"
    data-append-to="#{{ $self->getId('append-to') }}"
    data-sortable-container="#{{ $self->getId('append-to') }}"
    data-sortable-handle=".{{ $self->getId('items') }}-handle">

    <div id="{{ $self->getId('items') }}" class="repeater-items">
        <div class="table-responsive">
            <table
                class="table {{ ($sortable) ? 'is-sortable' : '' }} mb-0">
                <thead>
                <tr>
                    @if (!$self->previewMode && $sortable)
                        <th class="list-action"></th>
                    @endif
                    @if (!$self->previewMode && $showRemoveButton)
                        <th class="list-action"></th>
                    @endif
                    @foreach ($self->getVisibleColumns() as $label)
                        <th>{{ $label ? lang($label) : '' }}</th>
                    @endforeach
                </tr>
                </thead>
                <tbody id="{{ $self->getId('append-to') }}">
                @forelse ($self->formWidgets as $index => $widget)
                    {!! $self->makePartial('repeater/repeater_item', [
                        'widget' => $widget,
                        'indexValue' => $index,
                    ]) !!}
                @empty
                    <tr class="repeater-item-placeholder">
                        <td colspan="99" class="text-center">{{ is_lang_key($emptyMessage) ? lang($emptyMessage) : $emptyMessage }}</td>
                    </tr>
                @endforelse
                </tbody>
                @if ($showAddButton && !$self->previewMode)
                    <tfoot>
                    <tr>
                        <th colspan="99">
                            <div class="list-action">
                                <button
                                    class="btn btn-primary"
                                    data-control="add-item"
                                    type="button">
                                    <i class="fa fa-plus"></i>
                                    {{ $prompt ? lang($prompt) : '' }}
                                </button>
                            </div>
                        </th>
                    </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>

    <script
        type="text/template"
        data-find="{{ $indexSearch }}"
        data-replace="{{ $nextIndex }}"
        data-repeater-template>
        {!! $self->makePartial('repeater/repeater_item', ['widget' => $widgetTemplate, 'indexValue' => $indexSearch]) !!}
    </script>
</div>
