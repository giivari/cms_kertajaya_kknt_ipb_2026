<section
    class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"
>
    <header class="px-6 py-4">
        <h3 class="text-base font-semibold text-gray-950 dark:text-white">
            Isi Pesan
        </h3>
    </header>

    <div class="border-t border-gray-200 px-6 py-5 dark:border-white/10">
        <p
            class="m-0 block w-full text-left text-sm leading-6 text-gray-950 dark:text-white"
            style="text-align: left; white-space: pre-wrap; overflow-wrap: anywhere;"
        >{{ $record->message }}</p>
    </div>
</section>
