@if(!empty($data['video_url']))
    @php
        $url = $data['video_url'];
        $embedUrl = $url;
        // Parse YouTube URLs to generate embed link
        if (preg_match('/youtu\.be\/([a-zA-Z0-9_-]+)/i', $url, $matches)) {
            $embedUrl = 'https://www.youtube.com/embed/' . $matches[1];
        } elseif (preg_match('/youtube\.com.*[?&]v=([a-zA-Z0-9_-]+)/i', $url, $matches)) {
            $embedUrl = 'https://www.youtube.com/embed/' . $matches[1];
        }
    @endphp
    <div class="my-8 aspect-video rounded-xl overflow-hidden shadow-sm bg-gray-900 flex items-center justify-center">
        <iframe src="{{ $embedUrl }}" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen class="w-full h-full"></iframe>
    </div>
    @if(!empty($data['caption']))
        <p class="text-center text-sm text-gray-500 mt-2">{{ $data['caption'] }}</p>
    @endif
@endif
