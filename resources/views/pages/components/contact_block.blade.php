<div class="bg-emerald-50 rounded-xl p-8 border border-emerald-100 my-8">
    <h3 class="text-xl font-bold text-gray-900 mb-4">Informasi Kontak</h3>
    <ul class="space-y-4">
        @if(!empty($data['email']))
        <li class="flex items-center text-gray-700">
            <svg class="h-6 w-6 text-emerald-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
            <a href="mailto:{{ $data['email'] }}" class="hover:text-emerald-600 font-medium">{{ $data['email'] }}</a>
        </li>
        @endif
        @if(!empty($data['phone']))
        <li class="flex items-center text-gray-700">
            <svg class="h-6 w-6 text-emerald-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
            <a href="tel:{{ $data['phone'] }}" class="hover:text-emerald-600 font-medium">{{ $data['phone'] }}</a>
        </li>
        @endif
        @if(!empty($data['address']))
        <li class="flex items-start text-gray-700">
            <svg class="h-6 w-6 text-emerald-600 mr-3 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
            <span class="whitespace-pre-line leading-relaxed">{{ $data['address'] }}</span>
        </li>
        @endif
    </ul>
</div>
