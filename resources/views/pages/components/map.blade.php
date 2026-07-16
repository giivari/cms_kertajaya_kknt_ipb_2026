@if(!empty($data['latitude']) && !empty($data['longitude']))
    <div class="my-8 aspect-video rounded-xl overflow-hidden shadow-sm bg-gray-100 relative">
        <iframe 
            width="100%" 
            height="100%" 
            frameborder="0" 
            scrolling="no" 
            marginheight="0" 
            marginwidth="0" 
            src="https://maps.google.com/maps?q={{ $data['latitude'] }},{{ $data['longitude'] }}&t=&z={{ $data['zoom'] ?? 15 }}&ie=UTF8&iwloc=&output=embed"
            class="w-full h-full"
        ></iframe>
    </div>
@endif
