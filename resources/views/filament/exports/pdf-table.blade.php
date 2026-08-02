<style>
    h1 { color: #0f172a; font-size: 15px; margin: 0 0 6px; }
    .meta { color: #475569; font-size: 8px; margin-bottom: 8px; }
    table { border-collapse: collapse; width: 100%; }
    th { background-color: #0f766e; color: #ffffff; font-size: 8px; font-weight: bold; padding: 5px; }
    td { border: 1px solid #cbd5e1; color: #1e293b; font-size: 7.5px; line-height: 1.35; padding: 4px; }
    .note { color: #475569; font-size: 8px; margin-top: 7px; }
</style>

<h1>{{ $reportTitle }}</h1>
<div class="meta">
    Dibuat pada {{ $generatedAt }}. Data mengikuti pencarian, filter, dan urutan tabel saat ekspor dijalankan.
</div>

<table cellpadding="4">
    <thead>
        <tr>
            @foreach ($columns as $column)
                <th>{{ $column }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @forelse ($rows as $row)
            <tr>
                @foreach ($row as $value)
                    <td>{{ $value }}</td>
                @endforeach
            </tr>
        @empty
            <tr>
                <td colspan="{{ max(count($columns), 1) }}">Tidak ada data yang sesuai dengan pencarian atau filter saat ini.</td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="note">Jumlah data: {{ number_format($recordCount, 0, ',', '.') }} baris.</div>
