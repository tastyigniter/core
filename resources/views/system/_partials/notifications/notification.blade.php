<div class="d-flex align-items-center">
    @if($icon = $notification->icon)
        <div class="col-1">
            <i class="fa fs-4 {{$icon}} text-{{$notification->iconColor ?? 'muted'}}"></i>
        </div>
    @endif
    <div @class(['col-12' => !$notification->icon, 'col-11 ms-2' => $notification->icon])>
        <div class="text-truncate text-muted">{{ $notification->title }}</div>
        <div class="menu-item-meta">{!! $notification->message !!}</div>
        <span class="small menu-item-meta text-muted">
            {{ time_elapsed($notification->created_at) }}
        </span>
    </div>
</div>
