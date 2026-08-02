<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mencegah submit form (misal: search bar, contact form di dalam preview)
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.warn('Form submission disabled in preview mode.');
        });
    });

    // Mencegah klik link yang berpindah halaman, download, atau mengeksekusi script/data
    document.querySelectorAll('a').forEach(a => {
        a.addEventListener('click', function(e) {
            let href = a.getAttribute('href');

            // Allow button-like anchors with no href
            if (!href) {
                return;
            }

            href = href.trim().toLowerCase();

            // Allow internal hashes (e.g., #section) for UI interaction
            if (href.startsWith('#')) {
                return;
            }

            // Block everything else: javascript:, data:, external, same-origin route, downloads
            e.preventDefault();
            e.stopPropagation();
            console.warn('Link navigation/execution disabled in preview mode.');
        });
    });
});
</script>
