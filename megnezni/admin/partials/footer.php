</div><!-- /.main-wrap -->

<script>
// Sidebar toggle
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
}

// Modalok bezárása
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal-overlay')) {
        e.target.classList.remove('open');
    }
    if (e.target.classList.contains('modal-close') || e.target.closest('.modal-close')) {
        e.target.closest('.modal-overlay')?.classList.remove('open');
    }
});

// Törlés megerősítés
document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', function(e) {
        if (!confirm(this.dataset.confirm)) e.preventDefault();
    });
});

// Alert auto hide
setTimeout(() => {
    document.querySelectorAll('.alert').forEach(a => {
        a.style.transition = 'opacity .5s';
        a.style.opacity = '0';
        setTimeout(() => a.remove(), 500);
    });
}, 4000);
</script>
</body>
</html>