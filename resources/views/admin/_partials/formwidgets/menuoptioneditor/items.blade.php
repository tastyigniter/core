@forelse ($fieldItems as $fieldItem)
    <div
        id="{{ $self->getId('item-'.$loop->iteration) }}"
        class="card bg-light shadow-sm mb-2"
        data-item-index="{{ $loop->iteration }}"
    >
        <div class="card-body">
            <div class="d-flex w-100 justify-content-between">
                <div
                    class="flex-fill"
                    data-control="load-item"
                    data-item-id="{{ $fieldItem->getKey() }}"
                    role="button"
                >
                    {!! $self->makePartial('menuoptioneditor/item', ['item' => $fieldItem]) !!}
                </div>
                @unless ($self->previewMode)
                    <div class="align-self-center ml-auto">
                        <a
                            class="close text-danger"
                            aria-label="Remove"
                            data-control="delete-item"
                            data-item-id="{{ $fieldItem->getKey() }}"
                            data-item-selector="#{{ $self->getId('item-'.$loop->iteration) }}"
                            data-confirm-message="@lang($confirmMessage)"
                        ><i class="fa fa-trash-alt"></i></a>
                    </div>
                @endunless
            </div>
        </div>
    </div>
@empty
    @lang($emptyMessage)
@endforelse
