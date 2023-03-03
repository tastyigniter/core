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
        <span @class(['badge text-bg-danger', 'hide' => !$item->unreadCount()])>&nbsp;</span>
    </a>
    <ul id="{{ $this->getId($item->itemName.'-options') }}" class="dropdown-menu overflow-hidden">
        <li class="dropdown-body">
            <p class="wrap-all text-muted text-center">
                <span class="ti-loading spinner-border fa-3x fa-fw"></span>
            </p>
        </li>
    </ul>
</li>
