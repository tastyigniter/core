<div
    id="{{ $toolbarId }}"
    class="toolbar btn-toolbar {{ $cssClasses }}"
>
    @if ($availableButtons)
        <div class="toolbar-action">
            <div class="progress-indicator-container">
                @foreach ($availableButtons as $buttonObj)
                    {!! $self->renderButtonMarkup($buttonObj) !!}
                @endforeach
            </div>
        </div>
    @endif
</div>
