@if(count($records))
    @php
        $groupedRecords = $records->groupBy(function ($item) {
            return day_elapsed($item->created_at, false);
        });
    @endphp
    <div class="list-group list-group-flush">
        @foreach($groupedRecords as $dateAdded => $notifications)
            <div class="list-group-item bg-transparent border-0 pt-3">
                <span>{{ $dateAdded }}</span>
            </div>
            <div class="list-group-item rounded px-2">
                <div class="list-group list-group-flush">
                    @foreach($notifications as $notification)
                        <a
                            @class(['list-group-item list-group-item-action rounded', 'opacity-50' => $notification->read_at])
                            href="{{ $notification->url }}">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    @if($icon = $notification->icon)
                                        <i class="fa fs-4 {{$icon}} text-{{$notification->iconColor ?? 'muted'}}"></i>
                                    @endif
                                </div>
                                <div @class(['ms-3' => $notification->icon])>
                                    <div class="text-muted">{{ $notification->title }}</div>
                                    <div>{!! $notification->message !!}</div>
                                    <small class="text-muted">{{ time_elapsed($notification->created_at) }}</small>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
@else
    <p class="p-4 text-center">@lang('igniter::system.activities.text_empty')</p>
@endif

{!! $this->makePartial('lists/list_pagination') !!}
