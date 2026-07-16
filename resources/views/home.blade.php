@extends('layouts.public')

@section('title', 'Beranda')

@section('content')
<div class="relative bg-emerald-700 text-white overflow-hidden">
    <div class="absolute inset-0">
        <div class="absolute inset-0 bg-gradient-to-r from-emerald-800 to-emerald-600 mix-blend-multiply"></div>
        <div class="absolute inset-0 bg-black/40"></div>
    </div>
    <div class="relative max-w-7xl mx-auto py-24 px-4 sm:py-32 sm:px-6 lg:px-8 flex flex-col items-center text-center">
        <h1 class="text-4xl font-extrabold tracking-tight sm:text-5xl lg:text-6xl text-white drop-shadow-md">
            Selamat Datang di <span class="text-emerald-300">Desa</span>
        </h1>
        <p class="mt-6 text-xl max-w-3xl text-gray-100 drop-shadow">
            Portal informasi dan pelayanan masyarakat terpadu. Wujud transparansi dan komitmen kami untuk membangun desa yang lebih baik.
        </p>
        <div class="mt-10 max-w-sm mx-auto sm:max-w-none sm:flex sm:justify-center gap-4">
            <a href="#" class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-emerald-700 bg-white hover:bg-gray-50 shadow-sm transition-colors duration-200">
                Profil Desa
            </a>
            <a href="#" class="mt-3 sm:mt-0 inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-emerald-600 bg-opacity-60 hover:bg-opacity-70 border-emerald-400 shadow-sm transition-colors duration-200">
                Layanan Publik
            </a>
        </div>
    </div>
</div>

<div class="bg-white py-16 sm:py-24">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h2 class="text-3xl font-extrabold text-gray-900 sm:text-4xl">Layanan Kami</h2>
            <p class="mt-4 text-lg text-gray-500 max-w-2xl mx-auto">Kami menyediakan berbagai layanan administrasi dan informasi untuk memudahkan kebutuhan masyarakat.</p>
        </div>

        <div class="mt-16 grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="bg-gray-50 rounded-xl p-8 text-center hover:shadow-md transition-shadow">
                <div class="mx-auto w-16 h-16 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">Administrasi Surat</h3>
                <p class="text-gray-500 text-sm">Pelayanan pembuatan surat keterangan, pengantar, dan administrasi kependudukan lainnya.</p>
            </div>
            
            <div class="bg-gray-50 rounded-xl p-8 text-center hover:shadow-md transition-shadow">
                <div class="mx-auto w-16 h-16 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"></path></svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">Berita & Pengumuman</h3>
                <p class="text-gray-500 text-sm">Informasi terkini seputar kegiatan desa, program pemerintah, dan pengumuman penting.</p>
            </div>

            <div class="bg-gray-50 rounded-xl p-8 text-center hover:shadow-md transition-shadow">
                <div class="mx-auto w-16 h-16 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">Potensi Desa</h3>
                <p class="text-gray-500 text-sm">Pengembangan potensi ekonomi lokal, UMKM, pariwisata, dan pemberdayaan masyarakat.</p>
            </div>
        </div>
    </div>
</div>
@endsection
