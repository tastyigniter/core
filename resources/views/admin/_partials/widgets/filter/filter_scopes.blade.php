@foreach($scopes as $scope)
    <div class="col">
        {!! $this->renderScopeElement($scope) !!}
    </div>
@endforeach
