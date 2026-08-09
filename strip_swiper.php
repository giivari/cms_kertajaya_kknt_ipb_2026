<?php
$content = file_get_contents('resources/views/home.blade.php');

// Remove $isSwiper logic
$content = preg_replace('/@php\s*\$isSwiper = !\(isset\(\$isPreview\) && \$isPreview\);\s*@endphp/', '', $content);

// Remove outer container
$content = preg_replace('/<!-- Swiper Container -->\s*<div class="\{\{ \$isSwiper \? \'swiper swiper-container\' : \'\' \}\} w-full h-\[100dvh\] bg-navy">\s*<div class="\{\{ \$isSwiper \? \'swiper-wrapper\' : \'\' \}\} w-full h-full">/', '', $content);

// Remove slide wrappers (opening)
$content = preg_replace('/<div class="\{\{ \$isSwiper \? \'swiper-slide\' : \'\' \}\} h-\[100dvh\] w-full overflow-y-auto bg-[a-z]+ flex flex-col justify-(?:center|between)">\s*(<section)/', '$1', $content);

// Remove slide wrappers (closing) before next section
$content = preg_replace('/<\/section>\s*<\/div>\s*(?=<!-- (?:Introduksi|PotensiDesa|Statistik|BeritaTerbaru|GaleriDesa|DokumenPublik|FinalCTA) Section -->)/', '</section>' . "\n", $content);

// Remove the final section closing and the swiper end containers
$content = preg_replace('/<\/section>\s*@if\(\$isSwiper\)\s*@include\(\'partials\.footer\'\)\s*@endif\s*<\/div>\s*<\/div> <!-- end swiper-wrapper -->\s*<\/div> <!-- end swiper -->/s', "</section>\n", $content);

// Remove Swiper scripts
$content = preg_replace('/@if\(\$isSwiper\)\s*<script>.*?<\/script>\s*@endif/s', '', $content);

file_put_contents('resources/views/home.blade.php', $content);
echo "Done";
