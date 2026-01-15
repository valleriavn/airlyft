<!-- Top Navbar -->
<nav class="navbar navbar-expand-lg bg-white shadow-sm mb-4 rounded">
    <div class="container-fluid">
        <button class="btn btn-link d-lg-none text-dark" type="button" id="sidebarToggle">
            <i class="fas fa-bars fa-lg"></i>
        </button>
        
        <span class="navbar-text ms-2">
            Welcome back, <strong><?= htmlspecialchars($admin_name ?? 'Admin') ?></strong>
        </span>
        
        <div class="ms-auto">
            <span class="badge bg-primary me-3">
                <?= date("F d, Y") ?>
            </span>
        </div>
    </div>
</nav>

<script>
    // Simple toggle script for mobile sidebar
    document.addEventListener('DOMContentLoaded', function() {
        const toggleBtn = document.getElementById('sidebarToggle');
        const sidebar = document.querySelector('.sidebar');
        const body = document.body;
        
        // Create overlay if not exists
        let overlay = document.querySelector('.sidebar-overlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.className = 'sidebar-overlay';
            body.appendChild(overlay);
        }

        if(toggleBtn) {
            toggleBtn.addEventListener('click', function(e) {
                e.preventDefault();
                sidebar.classList.toggle('show');
                overlay.classList.toggle('show');
            });
        }

        // Close when clicking overlay
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        });
    });
</script>
