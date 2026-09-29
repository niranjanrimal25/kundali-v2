<button {{ $attributes->merge(['type' => 'submit', 'class' =>
    'inline-flex items-center rounded-md bg-[#4a2c5a] px-5 py-2.5 text-sm font-medium tracking-wide text-white '
    .'transition hover:bg-[#3a2245] focus:outline-none focus:ring-2 focus:ring-[#7b3f61]/40 focus:ring-offset-2 '
    .'active:bg-[#2f1b38] disabled:opacity-50']) }}>
    {{ $slot }}
</button>
