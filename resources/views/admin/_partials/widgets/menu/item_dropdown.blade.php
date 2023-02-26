<li
    id="{{$this->getId($item->itemName)}}"
    @class(['nav-item dropdown'])
    data-mainmenu-item="{{$item->itemName}}"
>
    <a
        class="nav-link"
        data-bs-toggle="dropdown"
        data-bs-auto-close="outside"
    >
        <i class="fa {{ $item->icon }}" role="button"></i>
        @if ($item->unreadCount())
            <span class="badge badge-danger">&nbsp;</span>
        @endif
    </a>
    <ul class="dropdown-menu">
        <li class="dropdown-header">@lang($item->label)</li>
        <li id="{{ $this->getId($item->itemName.'-options') }}" class="dropdown-body">
            <p class="wrap-all text-muted text-center">
                <span class="ti-loading spinner-border fa-3x fa-fw"></span>
            </p>
        </li>
        <li class="dropdown-footer">
            <a class="text-center" href="{{ $item->viewMoreUrl }}"><i class="fa fa-ellipsis-h"></i></a>
        </li>
    </ul>
</li>
