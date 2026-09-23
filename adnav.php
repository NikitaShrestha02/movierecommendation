<style>
header.admin-header {
    background-color: #131924;
    height: 64px;
    width: 100%;
    border-bottom: 1px solid #1f2838;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 0 24px;
    box-sizing: border-box;
    position: relative;
    z-index: 1000;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
}

.admin-header .nav-container {
    width: 100%;
    max-width: 1200px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.admin-header .nav-brand {
    display: flex;
    align-items: center;
    gap: 12px;
}

.admin-header .logo img {
    height: 36px;
    width: auto;
    display: block;
}

.admin-badge {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #f59e0b;
    background-color: rgba(245, 158, 11, 0.12);
    border: 1px solid rgba(245, 158, 11, 0.25);
    padding: 3px 8px;
    border-radius: 4px;
}

.admin-header ul.nav-menu {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 0;
    margin: 0;
    list-style: none;
}

.admin-header ul.nav-menu > li > a {
    color: #cbd5e1;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    padding: 6px 12px;
    border-radius: 5px;
    transition: color 0.2s ease, background-color 0.2s ease;
    display: inline-block;
}

.admin-header ul.nav-menu > li > a:hover {
    color: #ffffff;
    background-color: #1a2332;
}

.admin-header ul.nav-menu > li > a.btn-logout {
    background-color: rgba(239, 68, 68, 0.12);
    color: #f87171;
    border: 1px solid rgba(239, 68, 68, 0.3);
    padding: 5px 12px;
}

.admin-header ul.nav-menu > li > a.btn-logout:hover {
    background-color: #dc2626;
    color: #ffffff;
    border-color: #dc2626;
}

.admin-header ul.nav-menu > li > a.btn-viewsite {
    border: 1px solid #2d384c;
    color: #94a3b8;
    font-size: 13px;
}

.admin-header ul.nav-menu > li > a.btn-viewsite:hover {
    color: #f1f5f9;
    border-color: #3b82f6;
}

@media (max-width: 860px) {
    header.admin-header {
        height: auto;
        padding: 12px 16px;
    }
    .admin-header .nav-container {
        flex-direction: column;
        gap: 12px;
        align-items: stretch;
    }
    .admin-header ul.nav-menu {
        flex-wrap: wrap;
        justify-content: center;
    }
}

/* ── Admin Login Toast Notification ── */
.ad-toast-container {
    position: fixed;
    top: 24px;
    right: 24px;
    z-index: 999999;
    display: flex;
    flex-direction: column;
    gap: 12px;
    pointer-events: none;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
}
.ad-toast {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    background: #182234;
    border: 1px solid rgba(245, 158, 11, 0.4);
    border-radius: 10px;
    padding: 14px 16px 18px;
    min-width: 310px;
    max-width: 380px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
    pointer-events: all;
    position: relative;
    overflow: hidden;
    animation: adToastSlideIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}
@keyframes adToastSlideIn {
    from { opacity: 0; transform: translateX(70px); }
    to   { opacity: 1; transform: translateX(0); }
}
@keyframes adToastSlideOut {
    to   { opacity: 0; transform: translateX(90px); }
}
.ad-toast.hiding {
    animation: adToastSlideOut 0.3s ease forwards;
}
.ad-toast-icon {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: rgba(245, 158, 11, 0.15);
    color: #f59e0b;
    border: 1px solid rgba(245, 158, 11, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    font-weight: 700;
    flex-shrink: 0;
    margin-top: 1px;
}
.ad-toast-body { flex: 1; }
.ad-toast-title {
    color: #f8fafc;
    font-size: 13.5px;
    font-weight: 700;
    margin: 0 0 3px;
    letter-spacing: -0.01em;
}
.ad-toast-msg {
    color: #94a3b8;
    font-size: 12.5px;
    margin: 0;
    line-height: 1.45;
}
.ad-toast-msg strong {
    color: #f1f5f9;
}
.ad-toast-close {
    background: none;
    border: none;
    color: #64748b;
    font-size: 18px;
    cursor: pointer;
    padding: 0;
    line-height: 1;
    flex-shrink: 0;
    transition: color 0.15s;
}
.ad-toast-close:hover { color: #f1f5f9; }
.ad-toast-progress {
    position: absolute;
    bottom: 0;
    left: 0;
    height: 3px;
    width: 100%;
    background: rgba(245, 158, 11, 0.15);
}
.ad-toast-progress-fill {
    height: 100%;
    width: 100%;
    background: linear-gradient(90deg, #d97706, #f59e0b);
    animation: adToastProgress 4.5s linear forwards;
}
@keyframes adToastProgress {
    from { width: 100%; }
    to   { width: 0%; }
}
</style>

<?php
$adLoginToast = '';
if (!empty($_SESSION['just_logged_in'])) {
    $adLoginToast = $_SESSION['just_logged_in'];
    unset($_SESSION['just_logged_in']);
}
?>

<header class="admin-header">
    <div class="nav-container">
        <div class="nav-brand">
            <a href="admin.php" class="logo"><img src="LOGOO.png" alt="Logo"></a>
            <span class="admin-badge">Admin Panel</span>
        </div>

        <ul class="nav-menu">
            <li><a href="admin.php">Dashboard</a></li>
            <li><a href="nowshowingform.php">Add Movie</a></li>
            <li><a href="shownow.php">Manage Movies</a></li>
            <li><a href="shu.php">Manage Users</a></li>
            <li><a href="index.php" target="_blank" class="btn-viewsite">View Site &rarr;</a></li>
            <li><a href="logout.php" class="btn-logout">Logout</a></li>
        </ul>
    </div>
</header>

<!-- Admin Toast container -->
<div class="ad-toast-container" id="adToastContainer"></div>

<?php if (!empty($adLoginToast)): ?>
<script>
(function() {
    var titleName = <?php echo json_encode($adLoginToast); ?>;
    var container = document.getElementById('adToastContainer');
    if (!container) return;

    var toastEl = document.createElement('div');
    toastEl.className = 'ad-toast';
    toastEl.innerHTML =
        '<div class="ad-toast-icon">✓</div>' +
        '<div class="ad-toast-body">' +
            '<div class="ad-toast-title">Signed In Successfully</div>' +
            '<div class="ad-toast-msg">Welcome, <strong>' + (titleName.replace(/</g, "&lt;").replace(/>/g, "&gt;")) + '</strong>! Admin session active.</div>' +
        '</div>' +
        '<button class="ad-toast-close" title="Close" aria-label="Close">&times;</button>' +
        '<div class="ad-toast-progress"><div class="ad-toast-progress-fill"></div></div>';

    function dismiss() {
        toastEl.classList.add('hiding');
        setTimeout(function() { toastEl.remove(); }, 300);
    }

    toastEl.querySelector('.ad-toast-close').addEventListener('click', dismiss);
    container.appendChild(toastEl);
    setTimeout(dismiss, 4500);
})();
</script>
<?php endif; ?>
