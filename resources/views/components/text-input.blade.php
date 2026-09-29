@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge([
    'class' => 'rounded-md border-[#d9d0be] bg-white text-[#2b2520] shadow-sm placeholder:text-gray-300 focus:border-[#7b3f61] focus:ring-2 focus:ring-[#7b3f61]/30 disabled:bg-gray-50 disabled:text-gray-400'
]) }}>
