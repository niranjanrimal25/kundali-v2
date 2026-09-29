<button {{ $attributes->merge(['type' => 'submit', 'class' =>
    'inline-flex items-center rounded-md border border-red-300 bg-white px-4 py-2 text-sm font-medium text-red-700 '
    .'transition hover:bg-red-600 hover:text-white focus:outline-none focus:ring-2 focus:ring-red-400 focus:ring-offset-2 disabled:opacity-50']) }}>
    {{ $slot }}
</button>
