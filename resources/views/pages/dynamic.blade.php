@extends('layouts.public')

@section('title', $page->seo_title ?? $page->title ?? '')
@section('seo_description', $page->seo_description ?? $page->excerpt ?? '')

@section('content')
<div class="bg-white">
    <!-- Page Header -->
    @php
        $bgUrl = null;
        if ($page->featured_media_id) {
            try {
                $media = \App\Models\Media::find($page->featured_media_id);
                if ($media && $media->invisible_watermark_status?->value === 'verified') {
                    $deriv = $media->getPublicDerivative('large');
                    if ($deriv) $bgUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($deriv->filename);
                }
            } catch (\Exception $e) {}
        }
    @endphp
    
    @include('partials.page-header', [
        'title' => $page->title,
        'description' => $page->excerpt,
        'breadcrumbs' => [
            ['label' => 'Beranda', 'url' => route('home')],
            ['label' => $page->title, 'url' => null]
        ],
        'bgImage' => $bgUrl
    ])

    <!-- Page Content -->
    <div class="w-full mx-auto px-4 sm:px-6 md:px-8 lg:px-12 xl:px-24 2xl:px-32 py-12">
        @foreach($page->sections as $section)
            @if($section->is_visible)
                <div class="mb-12 {{ $section->layout_type === 'full_width' ? 'w-full max-w-none px-0' : '' }}">
                    @if($section->name && $section->layout_type !== 'hero' && $section->layout_type !== 'full_width')
                        <h2 class="text-2xl font-bold text-navy-900 mb-6 border-b pb-2 font-display">{{ $section->name }}</h2>
                    @endif

                    <div class="
                        @if($section->layout_type === 'two_columns') grid grid-cols-1 md:grid-cols-2 gap-8
                        @elseif($section->layout_type === 'three_columns') grid grid-cols-1 md:grid-cols-3 gap-8
                        @else flex flex-col gap-6
                        @endif
                    ">
                        @foreach($section->components as $component)
                            @if($component->is_visible)
                                <div class="component-wrapper">
                                    @include('pages.components.' . $component->component_type, ['data' => $component->content_data])
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif
        @endforeach
    </div>
</div>
@endsection
