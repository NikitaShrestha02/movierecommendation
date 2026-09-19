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
</style>

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
            <li><a href="show.php">Bookings</a></li>
            <li><a href="index.php" target="_blank" class="btn-viewsite">View Site &rarr;</a></li>
            <li><a href="logout.php" class="btn-logout">Logout</a></li>
        </ul>
    </div>
</header>