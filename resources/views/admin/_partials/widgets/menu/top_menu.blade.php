<ul
    id="{{ $self->getId() }}"
    class="navbar-nav"
    data-control="mainmenu"
    data-alias="{{ $self->alias }}"
>
    @foreach ($items as $item)
        {!! $self->renderItemElement($item) !!}
    @endforeach
</ul>
