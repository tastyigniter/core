<div class="accordion accordion-flush mt-3" id="accordion{{$self->arrayName}}">
    @foreach($accordions as $accordion => $fields)
        <div class="accordion-item">
            <h2 class="accordion-header" id="heading{{$self->arrayName}}{{$loop->index}}">
                <button
                    @class(['accordion-button bg-transparent fw-bold', 'collapsed' => !$loop->first])
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#collapse{{$self->arrayName}}{{$loop->index}}"
                    aria-expanded="{{$loop->first ? 'true' : 'false'}}"
                    aria-controls="collapse{{$self->arrayName}}{{$loop->index}}"
                >@lang($accordion)</button>
            </h2>
            <div
                id="collapse{{$self->arrayName}}{{$loop->index}}"
                @class(['accordion-collapse collapse', 'show' => $loop->first])
                aria-labelledby="heading{{$self->arrayName}}{{$loop->index}}"
                data-bs-parent="#accordion{{$self->arrayName}}"
            >
                <div class="accordion-body p-0">
                    <div class="form-fields mb-0">
                        {!! $self->makePartial('form/form_fields', ['fields' => $fields]) !!}
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>
