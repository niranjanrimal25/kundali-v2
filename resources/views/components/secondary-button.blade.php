<button {{ $attributes->merge(['type' => 'button', 'class' =>
    'inline-flex items-center rounded-md border border-[#d9d0be] bg-white px-4 py-2 text-sm font-medium text-gray-700 '
    .'transition hover:bg-[#f4f1ea] focus:outline-none focus:ring-2 focus:ring-[#7b3f61]/30 focus:ring-offset-2 disabled:opacity-50']) }}>
    {{ $slot }}
</button>
