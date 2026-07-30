@extends('layouts.public')

@section('title', 'Hubungi Kami')

@section('content')
<div class="bg-warm-white min-h-screen py-12 lg:py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col lg:flex-row gap-12 lg:gap-20">
            <!-- Left Column: Info -->
            <div class="lg:w-5/12 flex flex-col justify-center">
                <h2 class="text-4xl sm:text-5xl font-display font-bold text-navy mb-6">Hubungi Kami</h2>
                <p class="text-lg text-gray-600 mb-10 font-sans leading-relaxed">
                    Jika Anda memiliki pertanyaan, masukan, atau keperluan administrasi lainnya, silakan hubungi kami melalui formulir di samping atau melalui informasi kontak di bawah ini.
                </p>
                
                @php
                    $address = \App\Services\SettingsService::get('contact_address', 'Kantor Desa Kertajaya');
                    $email = \App\Services\SettingsService::get('contact_email', 'info@desakertajaya.id');
                    $phone = \App\Services\SettingsService::get('contact_phone', '(021) 1234567');
                @endphp
                
                <div class="space-y-6 text-gray-700 font-sans mb-12">
                    @if($address)
                    <div class="flex items-start gap-4">
                        <div class="bg-cream p-3 rounded-full text-teal flex-shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        </div>
                        <div class="mt-1">
                            <p class="font-semibold text-navy">Alamat</p>
                            <p class="text-gray-600 mt-1">{{ $address }}</p>
                        </div>
                    </div>
                    @endif
                    
                    @if($email)
                    <div class="flex items-start gap-4">
                        <div class="bg-cream p-3 rounded-full text-teal flex-shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                        </div>
                        <div class="mt-1">
                            <p class="font-semibold text-navy">Email</p>
                            <p class="text-gray-600 mt-1">{{ $email }}</p>
                        </div>
                    </div>
                    @endif
                    
                    @if($phone)
                    <div class="flex items-start gap-4">
                        <div class="bg-cream p-3 rounded-full text-teal flex-shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                        </div>
                        <div class="mt-1">
                            <p class="font-semibold text-navy">Telepon</p>
                            <p class="text-gray-600 mt-1">{{ $phone }}</p>
                        </div>
                    </div>
                    @endif
                </div>
                
                <div class="p-5 bg-cream rounded-2xl border border-border">
                    <div class="flex items-start gap-3">
                        <svg class="w-6 h-6 text-teal flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                        <div>
                            <h4 class="font-semibold text-navy mb-1">Privasi Anda Terjamin</h4>
                            <p class="text-sm text-gray-600 leading-relaxed">Informasi yang dikirim digunakan untuk menindaklanjuti pesan dan tidak ditampilkan kepada publik.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Form Card -->
            <div class="lg:w-7/12">
                <div class="bg-white rounded-[24px] border border-border shadow-sm p-6 sm:p-10">
                    @if(session('success'))
                        <div class="bg-green-50 border border-green-200 rounded-2xl p-6 text-center">
                            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100 mb-4">
                                <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-green-800 mb-2">Pesan Berhasil Terkirim</h3>
                            <p class="text-green-700">{{ session('success') }}</p>
                            <a href="{{ route('public.contact.show') }}" class="mt-6 inline-block text-teal hover:text-emerald font-medium">Kirim pesan lain</a>
                        </div>
                    @else
                        <form action="{{ route('public.contact.store') }}" method="POST" class="flex flex-col gap-6" id="contact-form">
                            @csrf
                            
                            <div class="w-full">
                                <label for="name" class="block text-sm font-medium text-navy mb-2">Nama Lengkap</label>
                                <input type="text" name="name" id="name" class="h-12 w-full text-base border border-border rounded-xl px-4 focus:ring-2 focus:ring-teal focus:border-teal outline-none transition-shadow" value="{{ old('name') }}" required minlength="3" maxlength="100" placeholder="Masukkan nama Anda">
                                @error('name') <p class="mt-2 text-sm text-red-500">{{ $message }}</p> @enderror
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="w-full">
                                    <label for="contact_type" class="block text-sm font-medium text-navy mb-2">Jenis Kontak</label>
                                    <select id="contact_type" name="contact_type" class="h-12 w-full text-base border border-border rounded-xl px-4 pr-10 focus:ring-2 focus:ring-teal focus:border-teal outline-none transition-shadow bg-white appearance-none" required onchange="updateContactField()">
                                        <option value="email" {{ old('contact_type') == 'email' ? 'selected' : '' }}>Email</option>
                                        <option value="phone" {{ old('contact_type') == 'phone' ? 'selected' : '' }}>Telepon / WhatsApp</option>
                                    </select>
                                    @error('contact_type') <p class="mt-2 text-sm text-red-500">{{ $message }}</p> @enderror
                                </div>

                                <div class="w-full">
                                    <label for="contact_value" class="block text-sm font-medium text-navy mb-2">Detail Kontak</label>
                                    <input type="email" inputmode="email" name="contact_value" id="contact_value" class="h-12 w-full text-base border border-border rounded-xl px-4 focus:ring-2 focus:ring-teal focus:border-teal outline-none transition-shadow" value="{{ old('contact_value') }}" required maxlength="150" placeholder="nama@contoh.com">
                                    @error('contact_value') <p class="mt-2 text-sm text-red-500">{{ $message }}</p> @enderror
                                </div>
                            </div>

                            <div class="w-full">
                                <label for="subject" class="block text-sm font-medium text-navy mb-2">Subjek</label>
                                <input type="text" name="subject" id="subject" class="h-12 w-full text-base border border-border rounded-xl px-4 focus:ring-2 focus:ring-teal focus:border-teal outline-none transition-shadow" value="{{ old('subject') }}" required maxlength="150" placeholder="Ringkasan singkat pesan Anda">
                                @error('subject') <p class="mt-2 text-sm text-red-500">{{ $message }}</p> @enderror
                            </div>

                            <div class="w-full">
                                <label for="message" class="block text-sm font-medium text-navy mb-2">Pesan</label>
                                <textarea id="message" name="message" rows="5" class="w-full text-base border border-border rounded-xl py-3 px-4 focus:ring-2 focus:ring-teal focus:border-teal outline-none transition-shadow resize-none" required minlength="10" maxlength="2000" placeholder="Tuliskan pesan Anda secara detail di sini...">{{ old('message') }}</textarea>
                                @error('message') <p class="mt-2 text-sm text-red-500">{{ $message }}</p> @enderror
                            </div>

                            <div class="w-full relative min-h-[70px]">
                                <x-turnstile />
                                @error('cf-turnstile-response') <p class="mt-2 text-sm text-red-500">{{ $message }}</p> @enderror
                                <p id="turnstile-error" class="mt-2 text-sm text-red-500" style="display: none;">Verifikasi keamanan gagal dimuat. Silakan muat ulang halaman.</p>
                            </div>

                            <div class="w-full mt-2">
                                <button type="submit" class="w-full h-12 flex items-center justify-center px-8 border border-transparent text-base font-semibold rounded-xl text-white bg-teal hover:bg-emerald focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal transition-colors shadow-sm">
                                    Kirim Pesan
                                </button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function updateContactField() {
        const typeSelect = document.getElementById('contact_type');
        const contactInput = document.getElementById('contact_value');
        if (typeSelect.value === 'phone') {
            contactInput.type = 'tel';
            contactInput.inputMode = 'tel';
            contactInput.placeholder = '08xxxxxxxxxx';
        } else {
            contactInput.type = 'email';
            contactInput.inputMode = 'email';
            contactInput.placeholder = 'nama@contoh.com';
        }
    }
    
    document.addEventListener('DOMContentLoaded', updateContactField);

    function turnstileCallback(token) {
        document.getElementById('turnstile-error').style.display = 'none';
    }
    setTimeout(function() {
        if (!document.querySelector('input[name="cf-turnstile-response"]')) {
            document.getElementById('turnstile-error').style.display = 'block';
        }
    }, 5000);
</script>
@endsection