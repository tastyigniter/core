@if(count($records))
    @foreach($records as $record)
        <tr>
            @if ($showDragHandle)
                <td class="list-action">
                    <div class="btn btn-handle shadow-none">
                        <i class="fa fa-arrows-alt-v"></i>
                    </div>
                </td>
            @endif

            @if ($showCheckboxes)
                <td class="list-action">
                    <div class="form-check">
                        <input
                            type="checkbox"
                            id="{{ 'checkbox-'.$record->getKey() }}"
                            class="form-check-input"
                            value="{{ $record->getKey()}}" name="checked[]"
                        />
                        <label class="form-check-label" for="{{ 'checkbox-'.$record->getKey() }}">&nbsp;</label>
                    </div>
                </td>
            @endif

            @foreach($columns as $key => $column)
                @continue ($column->type != 'button')
                <td class="list-action {{ $column->cssClass }}">
                    {!! $this->makePartial('lists/list_button', ['record' => $record, 'column' => $column]) !!}
                </td>
            @endforeach

            @foreach($columns as $key => $column)
                @continue($column->type == 'button')
                <td
                    class="list-col-index-{{ $loop->index }} list-col-name-{{ $column->getName() }} list-col-type-{{ $column->type }} {{ $column->cssClass }}"
                    @if($loop->last)colspan="4"@endif
                >
                    {!! $this->getColumnValue($record, $column) !!}
                </td>
            @endforeach
        </tr>
    @endforeach
@else
    <tr>
        <td colspan="99" class="text-center">{{ $emptyMessage }}</td>
    </tr>
@endif
