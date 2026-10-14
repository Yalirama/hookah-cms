@foreach($tableTypes->whereNull('deleted_at') as $type)
    <div class="customSelectOption" data-value="{{ $type->id }}">{{ $type->name }}</div>
@endforeach