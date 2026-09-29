@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'rounded-md border-l-4 border-emerald-500 bg-emerald-50 px-4 py-3 text-sm text-emerald-800']) }}>
        {{ $status }}
    </div>
@endif
