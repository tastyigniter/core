<div
    id="{{ $self->getId('item-'.$index) }}"
    class="card bg-light shadow-sm mb-2"
    data-item-index="{{ $index }}"
>
    <div class="card-body">
        <div class="d-flex w-100 justify-content-between">
            @if (!$self->previewMode && $sortable)
                <input type="hidden" name="{{ $sortableInputName }}[]" value="{{ $item->getKey() }}">
                <div class="align-self-center">
                    <a
                        class="btn handle {{ $self->getId('items') }}-handle"
                        role="button">
                        <i class="fa fa-arrows-alt-v text-black-50"></i>
                    </a>
                </div>
            @endif
            <div
                class="flex-fill"
                data-control="load-item"
                data-item-id="{{ $item->getKey() }}"
                role="button"
            >
                @if ($self->partial)
                    {!! $self->makePartial($self->partial, ['item' => $item]) !!}
                @else
                    <p class="card-title font-weight-bold">{{ $item->{$nameFrom} }}</p>
                    <p class="card-subtitle mb-0">{!! $item->{$descriptionFrom} !!}</p>
                @endif
            </div>
            @unless ($self->previewMode)
                <div class="align-self-center ml-auto">
                    <a
                        class="close text-danger"
                        aria-label="Remove"
                        data-control="delete-item"
                        data-item-id="{{ $item->getKey() }}"
                        data-item-selector="#{{ $self->getId('item-'.$index) }}"
                        data-confirm-message="@lang($confirmMessage)"
                    ><i class="fa fa-trash-alt"></i></a>
                </div>
            @endunless
        </div>
    </div>
</div>
