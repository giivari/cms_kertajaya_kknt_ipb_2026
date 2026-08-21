<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\News;
use App\Models\NewsCategory;
use App\Models\GalleryAlbum;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\Location;
use App\Models\LocationCategory;
use App\Models\Media;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\PageComponent;

class KertajayaSeeder extends Seeder
{
    public function run()
    {
        // 1. Create Media Dummy
        $dummyMedia = Media::firstOrCreate([
            'filename' => 'dummy-file.pdf'
        ], [
            'disk' => 'public',
            'directory' => 'dummy',
            'original_filename' => 'dummy-file.pdf',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size' => 1024,
            'processing_status' => 'completed',
            'invisible_watermark_status' => 'verified',
        ]);

        $dummyImage = Media::firstOrCreate([
            'filename' => 'dummy-image.jpg'
        ], [
            'disk' => 'public',
            'directory' => 'dummy',
            'original_filename' => 'dummy-image.jpg',
            'mime_type' => 'image/jpeg',
            'extension' => 'jpg',
            'size' => 1024,
            'processing_status' => 'completed',
            'invisible_watermark_status' => 'verified',
        ]);

        // 2. Kategori Berita & Berita Aktual Kertajaya (Dari Notulensi)
        $katPotensi = NewsCategory::firstOrCreate(['slug' => 'potensi-desa'], ['name' => 'Potensi Desa', 'description' => 'Potensi SDM, SDA, dan UMKM']);
        $katBerita = NewsCategory::firstOrCreate(['slug' => 'berita-desa'], ['name' => 'Berita Desa', 'description' => 'Kabar terkini Kertajaya']);
        $katSejarah = NewsCategory::firstOrCreate(['slug' => 'sejarah-budaya'], ['name' => 'Sejarah & Budaya', 'description' => 'Asal usul dan budaya']);

        $newsData = [
            [
                'title' => 'Sejarah Panjang Desa Kertajaya dan Jejak Perkebunan Belanda',
                'cat' => $katSejarah->id,
                'excerpt' => 'Menelusuri sejarah Kertajaya yang dulunya merupakan area PTPN Sangiang produsen karet dan cengkeh terbesar.',
                'content' => '<p>Desa Kertajaya menyimpan sejarah panjang yang bermula dari masa kolonial Belanda. Dulunya, kawasan ini merupakan area perkebunan raksasa (PTPN Sangiang/Halimun) yang memproduksi karet dan cengkeh. Jejak-jejak masa lalu masih bisa dilihat dari sisa pondasi jembatan peninggalan Belanda yang menghubungkan Kertajaya dengan Loji (Gunung Rompang).</p><p>Kawasan Kiarakoneng (RT 28) sendiri berawal dari Setu (sumber air) yang menjadi jalur perlintasan pejalan kaki dan pedagang dari Surade menuju Jampang.</p>',
            ],
            [
                'title' => 'Mengembangkan Potensi UMKM Lokal: Sale Pisang dan Makaroni',
                'cat' => $katPotensi->id,
                'excerpt' => 'Masyarakat Tipar dan Cikeresek terus mengembangkan UMKM rumahan untuk mengimbangi ketergantungan pada sektor tambang.',
                'content' => '<p>Selain sektor pertambangan emas yang mendominasi, Desa Kertajaya juga memiliki potensi UMKM yang menjanjikan. Di Dusun Tipar, terdapat produksi Makaroni dan Keripik rumahan yang memberdayakan ibu-ibu, janda, dan warga setempat. Omzet dari penjualan ini sangat baik, bahkan keuntungannya rutin disisihkan untuk patungan Qurban.</p><p>Sementara itu, wilayah Buniasih memproduksi Sale Pisang yang dikemas rapi dan didistribusikan hingga ke luar daerah. Bahan baku pisang ini banyak disuplai dari para petani di Cikeresek. Ini membuktikan bahwa warga Kertajaya mampu mandiri dari sektor ekonomi kreatif.</p>',
            ],
            [
                'title' => 'Dinamika Pertanian: Dari Padi Organik hingga Kelapa Kopyor',
                'cat' => $katPotensi->id,
                'excerpt' => 'Meski didominasi tambang, Kertajaya memiliki inovasi Padi Organik Cijangkar dan budidaya Kelapa Kopyor di Tipar.',
                'content' => '<p>Kondisi tanah Kertajaya yang banyak mengandung mineral seringkali kurang ideal untuk penanaman padi konvensional (kecuali padi gogo/pare huma). Namun, inovasi terus dilakukan. Di Cijangkar, melalui Kelompok Tani Inayah 2, berhasil dikembangkan Padi Organik berprotein tinggi yang dijuluki "Lumbung Padi Cicero Kaja".</p><p>Selain itu, pengembangan sektor perkebunan terus berjalan. Saat ini tengah dirintis penanaman 500 pohon kelapa Kopyor (jenis genjah) di wilayah Tipar. Di kawasan lain, warga juga menanam Jahe Merah, Cengkeh, dan Pisang sebagai komoditas palawija unggulan.</p>',
            ],
            [
                'title' => 'Menyusuri Potensi Wisata Kertajaya: Curug dan Goa Belanda',
                'cat' => $katPotensi->id,
                'excerpt' => 'Dua peninggalan Goa Belanda dan Curug perbatasan menjadi potensi pariwisata yang kelak bisa digarap maksimal.',
                'content' => '<p>Desa Kertajaya menyimpan potensi pariwisata yang sangat memukau. Terdapat dua Goa peninggalan zaman Belanda yang bisa menjadi objek wisata sejarah dan agro wisata yang edukatif.</p><p>Selain itu, bagi pecinta alam, terdapat Curug (Air Terjun) indah yang berlokasi di perbatasan menuju Desa Langkapjaya (area Cikumbung). Meski saat ini aksesnya harus ditempuh dengan berjalan kaki 2 jam, kelak kawasan ini berpotensi menjadi destinasi wisata alam unggulan jika mendapat pengelolaan sarana yang baik.</p>',
            ],
            [
                'title' => 'Semangat Gotong Royong Warga Membangun Jembatan Penghubung Loji',
                'cat' => $katBerita->id,
                'excerpt' => 'Swadaya masyarakat Kertajaya berhasil membangun jembatan darurat 50 Juta setelah fasilitas vital tersebut rusak akibat bencana.',
                'content' => '<p>Infrastruktur jalan adalah urat nadi kehidupan masyarakat. "Hidup itu mencari jalan, mati pun sama. Jalan yang utama," tegas Kepala Dusun. Ketika jembatan penghubung utama antara Kertajaya dan Desa Loji rusak akibat longsor, warga tidak tinggal diam menanti bantuan yang lama turun.</p><p>Dengan inisiatif Kadus dan semangat gotong royong swadaya, warga berhasil mengumpulkan dana 50 Juta Rupiah (dari desa dan iuran masyarakat) untuk membangun jembatan sementara dari besi. Sikap kekeluargaan ini menjadi bukti nyata kelestarian sosial masyarakat Kertajaya.</p>',
            ]
        ];

        foreach ($newsData as $n) {
            News::firstOrCreate(['slug' => Str::slug($n['title'])], [
                'title' => $n['title'],
                'content' => $n['content'],
                'excerpt' => $n['excerpt'],
                'news_category_id' => $n['cat'],
                'featured_media_id' => $dummyImage->id,
                'status' => 'published',
                'published_at' => now(),
            ]);
        }

        // 3. Lokasi/Fasilitas Kertajaya
        $katFasum = LocationCategory::firstOrCreate(['slug' => 'fasilitas-umum'], ['name' => 'Fasilitas Umum & Infrastruktur']);
        $katWisata = LocationCategory::firstOrCreate(['slug' => 'pariwisata'], ['name' => 'Pariwisata & Sejarah']);
        $katPertanian = LocationCategory::firstOrCreate(['slug' => 'pertanian'], ['name' => 'Pertanian & UMKM']);

        $locations = [
            ['name' => 'Goa Belanda Kertajaya 1', 'cat' => $katWisata->id, 'desc' => 'Goa peninggalan zaman kolonial Belanda di Kertajaya.'],
            ['name' => 'Goa Belanda Kertajaya 2', 'cat' => $katWisata->id, 'desc' => 'Goa peninggalan bersejarah di area agro wisata.'],
            ['name' => 'Curug Perbatasan Langkapjaya', 'cat' => $katWisata->id, 'desc' => 'Air terjun alami dengan pemandangan asri, jarak tempuh jalan kaki 2 jam dari Cikeresek.'],
            ['name' => 'Lumbung Padi Organik Cijangkar', 'cat' => $katPertanian->id, 'desc' => 'Sentra padi organik berprotein tinggi yang dikelola oleh Pak Irwan (Selesa Kina).'],
            ['name' => 'Sentra Makaroni Tipar', 'cat' => $katPertanian->id, 'desc' => 'Pusat pemberdayaan UMKM makanan ringan ibu-ibu Dusun Tipar.'],
            ['name' => 'Sentra Sale Pisang Buniasih', 'cat' => $katPertanian->id, 'desc' => 'Produksi Sale Pisang kemasan yang dikirim hingga ke luar daerah.'],
            ['name' => 'Jembatan Swadaya Loji-Kertajaya', 'cat' => $katFasum->id, 'desc' => 'Jembatan besi penghubung vital yang dibangun dari gotong royong warga senilai 50 Juta.'],
            ['name' => 'Hutan Perhutanan Sosial (HDPK) Sampora', 'cat' => $katFasum->id, 'desc' => 'Kawasan Hutan Desa seluas 531 Hektar.'],
            ['name' => 'Tambang Mangan Loji-Kertajaya', 'cat' => $katPertanian->id, 'desc' => 'Area tambang batu mangan seluas 30 Hektar.'],
        ];

        foreach ($locations as $loc) {
            Location::firstOrCreate(['slug' => Str::slug($loc['name'])], [
                'name' => $loc['name'],
                'location_category_id' => $loc['cat'],
                'description' => $loc['desc'],
                'latitude' => -7.0543, // koordinat kasar pelabuhan ratu/simpenan
                'longitude' => 106.5821, 
                'status' => 'published',
            ]);
        }

        // 4. Dokumen Publik
        $katDoc = DocumentCategory::firstOrCreate(['slug' => 'laporan-desa'], ['name' => 'Laporan & Pemetaan Desa', 'description' => 'Dokumen resmi dan laporan penelitian desa.']);
        Document::firstOrCreate(['slug' => 'laporan-pemetaan-kkn-2026'], [
            'title' => 'Laporan Pemetaan Potensi dan Batas Dusun (KKN IPB 2026)',
            'description' => 'Dokumen laporan hasil wawancara dan survei lapangan mahasiswa KKN IPB terkait Tipar, Cigadog, Cikeresek, dan Kiarakoneng.',
            'document_category_id' => $katDoc->id,
            'file_media_id' => $dummyMedia->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        // 5. Galeri
        GalleryAlbum::firstOrCreate(['slug' => 'pembangunan-jembatan-loji'], [
            'title' => 'Gotong Royong Pembangunan Jembatan Loji',
            'description' => 'Dokumentasi warga Kertajaya bersatu membangun jembatan darurat.',
            'cover_media_id' => $dummyImage->id,
            'status' => 'published',
            'published_at' => now(),
        ]);
        GalleryAlbum::firstOrCreate(['slug' => 'potensi-wisata-alam'], [
            'title' => 'Potensi Alam: Goa Belanda & Curug',
            'description' => 'Dokumentasi Goa peninggalan Belanda dan eksotisme Air Terjun Cikumbung.',
            'cover_media_id' => $dummyImage->id,
            'status' => 'published',
            'published_at' => now(),
        ]);
        GalleryAlbum::firstOrCreate(['slug' => 'kegiatan-umkm-warga'], [
            'title' => 'Kegiatan Pemberdayaan UMKM Warga',
            'description' => 'Potret ibu-ibu Kertajaya memproduksi Sale Pisang dan Makaroni.',
            'cover_media_id' => $dummyImage->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        // 6. Halaman Statis (Profil Desa)
        $page = Page::firstOrCreate(['slug' => 'profil-desa'], [
            'title' => 'Profil Desa',
            'excerpt' => 'Informasi umum tentang sejarah, wilayah, dan kependudukan Desa Kertajaya.',
            'status' => 'published',
            'published_at' => now(),
            'featured_media_id' => $dummyImage->id,
        ]);

        $section = PageSection::firstOrCreate(['page_id' => $page->id, 'position' => 0], [
            'name' => 'Konten Utama',
            'layout_type' => 'single_column',
            'is_visible' => true,
        ]);

        PageComponent::firstOrCreate(['section_id' => $section->id, 'position' => 0], [
            'component_type' => 'heading',
            'content_data' => ['text' => 'Sejarah Desa Kertajaya', 'level' => 'h2', 'alignment' => 'left'],
            'is_visible' => true,
        ]);

        PageComponent::firstOrCreate(['section_id' => $section->id, 'position' => 1], [
            'component_type' => 'rich_text',
            'content_data' => ['content' => '<p>Desa Kertajaya menyimpan sejarah panjang yang bermula dari masa kolonial Belanda. Dulunya, kawasan ini merupakan area perkebunan raksasa (PTPN Sangiang/Halimun) yang memproduksi karet dan cengkeh. Jejak-jejak masa lalu masih bisa dilihat dari sisa pondasi jembatan peninggalan Belanda yang menghubungkan Kertajaya dengan Loji (Gunung Rompang).</p><p>Hingga saat ini, dengan populasi sekitar 4.000 jiwa yang sebagian besar bergantung pada sektor pertambangan, Kertajaya terus melestarikan kearifan lokal seperti gotong royong serta mulai merintis UMKM untuk menopang kemandirian desa.</p>'],
            'is_visible' => true,
        ]);
    }
}
