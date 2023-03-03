<li class="dropdown-header">
    <div class="d-flex justify-content-between">
        <div class="flex-fill">@lang($item->label)</div>
        <div>
            <a
                class="cursor-pointer mr-4"
                href="{{ admin_url('notifications/settings') }}"
            ><i class="fa fa-cog"></i></a>
            <a
                class="cursor-pointer"
                data-request="{{$this->getEventHandler('onMarkOptionsAsRead')}}"
                data-request-data="'item':'{{$item->itemName}}'"
            ><i class="fa fa-check"></i></a>
        </div>
    </div>
</li>
<ul class="menu menu-lg">
    @forelse($itemOptions as $notification)
        <li class="menu-item{{ !$notification->read_at ? ' active' : '' }}">
            <a href="{{ array_get($notification->data, 'url') }}" class="menu-link">
                <div class="d-flex">
                    @if($icon = array_get($notification->data, 'icon'))
                        <div><i class="{{$icon}} text-{{array_get($notification->data, 'iconColor')}}"></i></div>
                    @endif
                    <div>
                        <div class="menu-item-meta">{!! array_get($notification->data, 'message') !!}</div>
                        <span class="small menu-item-meta text-muted">
                            {{ time_elapsed($notification->created_at) }}
                        </span>
                    </div>
                </div>
            </a>
        </li>
        <li class="divider"></li>
    @empty
        <li class="text-center">@lang('igniter::admin.text_empty_activity')</li>
    @endforelse
</ul>
<li class="dropdown-footer">
    <a class="text-center" href="{{ admin_url('notifications') }}"><i class="fa fa-ellipsis-h"></i></a>
</li>
