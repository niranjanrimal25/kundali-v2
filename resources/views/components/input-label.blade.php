@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-sm font-medium text-[#4a2c5a]']) }}>
    {{ $value ?? $slot }}
</label>
