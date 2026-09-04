<script>
    (function () {
        const toggle = document.querySelector('.menu-toggle');
        const sidebar = document.getElementById('main-sidebar');
        if (!toggle || !sidebar) return;
        toggle.addEventListener('click', function () {
            const expanded = sidebar.classList.toggle('open');
            toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        });
        document.addEventListener('click', function (event) {
            if (window.innerWidth <= 760 && sidebar.classList.contains('open') && !sidebar.contains(event.target) && event.target !== toggle) {
                sidebar.classList.remove('open');
                toggle.setAttribute('aria-expanded', 'false');
            }
        });
    }());
</script>
</body>
</html>
