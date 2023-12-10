<div class="card p-4 shadow-sm m-4">
    <div class="text-center my-5 m-auto">
        @if($class == 'success')
        @elseif($class == 'danger')
            <i class="fa fa-circle-exclamation fa-4x text-{{$class}} mb-4"></i>
        @endif
        <h1>{{$title}}</h1>
        <p class="lead mt-3">{{$text}}</p>
    </div>
</div>
