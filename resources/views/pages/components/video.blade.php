@if(!empty($data['video_url']))
    <div class="my-8 aspect-video rounded-xl overflow-hidden shadow-sm bg-gray-900 flex items-center justify-center">
        <!-- Assuming YouTube or Vimeo for simplicity, but a real implementation would use an embed parser -->
        <iframe src="{{ str_replace('watch?v=', 'embed/', $data['video_url']) }}" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen class="w-full h-full"></iframe>
    </div>
    @if(!empty($data['caption']))
        <p class="text-center text-sm text-gray-500 mt-2">{{ $data['caption'] }}</p>
    @endif
@endif
