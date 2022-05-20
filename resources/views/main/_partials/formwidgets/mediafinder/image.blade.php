<div class="media-image{{ $isMulti ? ' image-list' : '' }}">
    @if (count($value))
        @foreach ($value as $mediaItem)
            {!! $self->makePartial('mediafinder/image_'.$mode, ['mediaItem' => $mediaItem]) !!}
        @endforeach
    @else
        {!! $self->makePartial('mediafinder/image_'.$mode, ['mediaItem' => null]) !!}
    @endif
</div>

<script type="text/template" data-blank-template>
    {!! $self->makePartial('mediafinder/image_'.$mode, ['mediaItem' => null]) !!}
</script>

<script type="text/template" data-image-template>
    {!! $self->makePartial('mediafinder/image_'.$mode, ['mediaItem' => '']) !!}
</script>
