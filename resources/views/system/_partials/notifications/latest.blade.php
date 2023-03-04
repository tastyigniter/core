<li class="dropdown-header">
    <div class="d-flex justify-content-between">
        <div class="flex-fill">@lang($item->label)</div>
        <div>
            <a
                class="cursor-pointer"
                data-request="{{$this->getEventHandler('onMarkOptionsAsRead')}}"
                data-request-data="'item':'{{$item->itemName}}'"
                title="@lang('igniter::system.notifications.button_mark_as_read')"
            ><i class="fa fa-check"></i></a>
        </div>
    </div>
</li>
<ul class="menu menu-lg">
    @forelse($itemOptions as $notification)
        <li class="menu-item{{ !$notification->read_at ? ' active' : '' }}">
            <a href="{{ $notification->url }}" class="menu-link">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        @if($icon = $notification->icon)
                            <i class="fa fs-4 {{$icon}} text-{{$notification->iconColor ?? 'muted'}}"></i>
                        @endif
                    </div>
                    <div @class(['ms-3' => $notification->icon])>
                        <div class="text-muted">{{ $notification->title }}</div>
                        <div class="menu-item-meta">{!! $notification->message !!}</div>
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
