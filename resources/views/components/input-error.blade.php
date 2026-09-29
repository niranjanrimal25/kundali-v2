@props(['messages'])

@if ($messages)
    <ul {{ $attributes->merge(['class' => 'space-y-1 text-sm text-red-600']) }}>
        @foreach ((array) $messages as $message)
            <li class="flex gap-1.5">
                <span aria-hidden="true">&middot;</span>
                <span>{{ $message }}</span>
            </li>
        @endforeach
    </ul>
@endif
