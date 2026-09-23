<?php
session_start();
if (!isset($_SESSION['uemail']) && isset($_COOKIE['uemail'])) {
    $_SESSION['uemail'] = $_COOKIE['uemail'];
}
if (!isset($_SESSION['uemail']) || $_SESSION['uemail'] !== 'snadmin@gmail.com') {
    header("Location: adform.php");
    exit();
}

include('connection.php');

$toastMessage = '';
$toastType = 'success';

// Handle Delete Inquiry
if (isset($_GET['delete'])) {
    $deleteId = (int)$_GET['delete'];
    $delStmt = $conn->prepare("DELETE FROM contact_inquiries WHERE id = ?");
    if ($delStmt) {
        $delStmt->bind_param("i", $deleteId);
        if ($delStmt->execute() && $delStmt->affected_rows > 0) {
            $_SESSION['admin_flash'] = "Inquiry #INQ-" . str_pad($deleteId, 4, '0', STR_PAD_LEFT) . " has been removed.";
            $_SESSION['admin_flash_type'] = "success";
        } else {
            $_SESSION['admin_flash'] = "Could not delete inquiry (ID not found).";
            $_SESSION['admin_flash_type'] = "error";
        }
        $delStmt->close();
    }
    header("Location: admin_inquiries.php");
    exit();
}

// Handle Update Status & Admin Reply Note
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_inquiry') {
    $inqId = (int)($_POST['inquiry_id'] ?? 0);
    $newStatus = trim($_POST['status'] ?? 'unread');
    $adminReply = trim($_POST['admin_reply'] ?? '');

    $validStatuses = ['unread', 'in_progress', 'resolved'];
    if (!in_array($newStatus, $validStatuses)) {
        $newStatus = 'unread';
    }

    $upStmt = $conn->prepare("UPDATE contact_inquiries SET status = ?, admin_reply = ?, replied_at = NOW() WHERE id = ?");
    if ($upStmt) {
        $upStmt->bind_param("ssi", $newStatus, $adminReply, $inqId);
        if ($upStmt->execute()) {
            $_SESSION['admin_flash'] = "Inquiry #INQ-" . str_pad($inqId, 4, '0', STR_PAD_LEFT) . " updated successfully.";
            $_SESSION['admin_flash_type'] = "success";
        } else {
            $_SESSION['admin_flash'] = "Failed to update inquiry.";
            $_SESSION['admin_flash_type'] = "error";
        }
        $upStmt->close();
    }
    header("Location: admin_inquiries.php");
    exit();
}

// Check flash messages
if (!empty($_SESSION['admin_flash'])) {
    $toastMessage = $_SESSION['admin_flash'];
    $toastType = $_SESSION['admin_flash_type'] ?? 'success';
    unset($_SESSION['admin_flash'], $_SESSION['admin_flash_type']);
}

// Filters & Search
$statusFilter = trim($_GET['status'] ?? 'all');
$searchQuery = trim($_GET['q'] ?? '');

// Metrics counters
$totalCount = 0;
$unreadCount = 0;
$inProgressCount = 0;
$resolvedCount = 0;

$cRes = $conn->query("SELECT 
    COUNT(*) AS total,
    SUM(CASE WHEN status = 'unread' THEN 1 ELSE 0 END) AS unread_cnt,
    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) AS in_prog_cnt,
    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) AS res_cnt
    FROM contact_inquiries");
if ($cRes && $cRow = $cRes->fetch_assoc()) {
    $totalCount = (int)$cRow['total'];
    $unreadCount = (int)($cRow['unread_cnt'] ?? 0);
    $inProgressCount = (int)($cRow['in_prog_cnt'] ?? 0);
    $resolvedCount = (int)($cRow['res_cnt'] ?? 0);
}

// Build query
$whereClauses = [];
$params = [];
$types = '';

if ($statusFilter !== 'all' && in_array($statusFilter, ['unread', 'in_progress', 'resolved'])) {
    $whereClauses[] = "ci.status = ?";
    $params[] = $statusFilter;
    $types .= 's';
}

if (!empty($searchQuery)) {
    $whereClauses[] = "(ci.name LIKE ? OR ci.email LIKE ? OR ci.subject LIKE ? OR ci.message LIKE ?)";
    $like = '%' . $searchQuery . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= 'ssss';
}

$whereSql = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';
$sql = "SELECT ci.*, u.name AS registered_name, u.email AS registered_email 
        FROM contact_inquiries ci 
        LEFT JOIN user u ON ci.user_id = u.id 
        $whereSql 
        ORDER BY ci.created_at DESC LIMIT 100";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$inquiriesResult = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inquiries &amp; Messages - Admin Panel</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: #0f141c;
            color: #cbd5e1;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .admin-container {
            max-width: 1200px;
            width: 100%;
            margin: 28px auto;
            padding: 0 20px;
            flex: 1;
        }

        .hero-card {
            background-color: #181f2c;
            border: 1px solid #242e40;
            border-radius: 8px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.25);
            padding: 24px 28px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
        }

        .hero-title-group h1 {
            color: #f8fafc;
            font-size: 24px;
            font-weight: 700;
            margin: 0 0 6px 0;
            letter-spacing: -0.01em;
        }
        .hero-title-group p {
            color: #94a3b8;
            font-size: 14px;
            margin: 0;
        }

        /* Metric Cards */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .metric-card {
            background-color: #181f2c;
            border: 1px solid #242e40;
            border-radius: 8px;
            padding: 18px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            text-decoration: none;
            color: inherit;
            transition: border-color 0.2s, transform 0.15s;
        }
        .metric-card:hover {
            border-color: #3b82f6;
            transform: translateY(-2px);
        }
        .metric-card.active {
            border-color: #3b82f6;
            background-color: #1c2637;
        }
        .metric-label {
            font-size: 13px;
            font-weight: 600;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 4px;
        }
        .metric-value {
            font-size: 26px;
            font-weight: 700;
            color: #f8fafc;
        }
        .metric-icon {
            font-size: 28px;
            opacity: 0.85;
        }

        /* Filter & Search Bar */
        .filter-card {
            background-color: #181f2c;
            border: 1px solid #242e40;
            border-radius: 8px;
            padding: 16px 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 14px;
        }
        .filter-tabs {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .filter-tab {
            padding: 6px 14px;
            border-radius: 5px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            color: #94a3b8;
            background-color: #131924;
            border: 1px solid #283449;
            transition: all 0.2s;
        }
        .filter-tab:hover {
            color: #f1f5f9;
            border-color: #3b82f6;
        }
        .filter-tab.active {
            background-color: #2563eb;
            color: #ffffff;
            border-color: #2563eb;
        }

        .search-form {
            display: flex;
            align-items: center;
            gap: 8px;
            flex: 1;
            max-width: 380px;
        }
        .search-input {
            flex: 1;
            background-color: #131924;
            border: 1px solid #283449;
            border-radius: 5px;
            padding: 8px 12px;
            color: #f1f5f9;
            font-size: 13.5px;
            outline: none;
        }
        .search-input:focus { border-color: #3b82f6; }
        .search-btn {
            background-color: #2563eb;
            color: #fff;
            border: none;
            padding: 8px 14px;
            border-radius: 5px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }

        /* Table Card */
        .table-card {
            background-color: #181f2c;
            border: 1px solid #242e40;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.25);
        }
        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }
        table.inq-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 13.5px;
        }
        table.inq-table th {
            background-color: #131924;
            color: #94a3b8;
            padding: 14px 16px;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 1px solid #242e40;
            white-space: nowrap;
        }
        table.inq-table td {
            padding: 14px 16px;
            border-bottom: 1px solid #1f2838;
            vertical-align: middle;
        }
        table.inq-table tr:hover td {
            background-color: #1c2637;
        }
        table.inq-table tr.is-unread td {
            background-color: rgba(59, 130, 246, 0.05);
            font-weight: 500;
        }

        /* Badges */
        .badge {
            display: inline-block;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            padding: 3px 8px;
            border-radius: 4px;
            white-space: nowrap;
        }
        .badge-unread {
            background-color: rgba(245, 158, 11, 0.15);
            color: #fbbf24;
            border: 1px solid rgba(245, 158, 11, 0.35);
        }
        .badge-progress {
            background-color: rgba(59, 130, 246, 0.15);
            color: #60a5fa;
            border: 1px solid rgba(59, 130, 246, 0.35);
        }
        .badge-resolved {
            background-color: rgba(16, 185, 129, 0.15);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.35);
        }
        .badge-subject {
            background-color: #131924;
            border: 1px solid #2c384d;
            color: #cbd5e1;
        }
        .badge-user {
            font-size: 10px;
            background-color: rgba(139, 92, 246, 0.15);
            color: #a78bfa;
            border: 1px solid rgba(139, 92, 246, 0.3);
            margin-left: 6px;
        }

        /* Action Buttons */
        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 6px 12px;
            font-size: 12.5px;
            font-weight: 600;
            border-radius: 4px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all 0.15s;
        }
        .btn-view {
            background-color: #2563eb;
            color: #ffffff;
        }
        .btn-view:hover { background-color: #1d4ed8; }
        .btn-delete {
            background-color: rgba(239, 68, 68, 0.12);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        .btn-delete:hover {
            background-color: #dc2626;
            color: #ffffff;
        }

        /* Toast Alert */
        .flash-alert {
            padding: 12px 18px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .flash-success {
            background-color: rgba(16, 185, 129, 0.12);
            border: 1px solid rgba(16, 185, 129, 0.35);
            color: #34d399;
        }
        .flash-error {
            background-color: rgba(239, 68, 68, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.35);
            color: #f87171;
        }

        /* Empty State */
        .empty-state {
            padding: 48px 20px;
            text-align: center;
            color: #64748b;
        }
        .empty-state-icon { font-size: 42px; margin-bottom: 12px; }

        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.75);
            backdrop-filter: blur(4px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 2000;
            padding: 20px;
        }
        .modal-overlay.active { display: flex; }
        .modal-box {
            background-color: #181f2c;
            border: 1px solid #283449;
            border-radius: 8px;
            width: 100%;
            max-width: 650px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.6);
            display: flex;
            flex-direction: column;
        }
        .modal-header {
            padding: 18px 24px;
            border-bottom: 1px solid #242e40;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .modal-header h3 {
            margin: 0;
            font-size: 18px;
            color: #f8fafc;
        }
        .modal-close {
            background: none;
            border: none;
            color: #94a3b8;
            font-size: 22px;
            cursor: pointer;
            padding: 0;
            line-height: 1;
        }
        .modal-close:hover { color: #f1f5f9; }
        .modal-body {
            padding: 24px;
            flex: 1;
        }

        .detail-row {
            margin-bottom: 14px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px 24px;
        }
        .detail-item {
            font-size: 13px;
        }
        .detail-label {
            color: #64748b;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.05em;
            margin-bottom: 3px;
        }
        .detail-value {
            color: #f1f5f9;
            font-weight: 500;
        }
        .message-box {
            background-color: #131924;
            border: 1px solid #242e40;
            border-radius: 6px;
            padding: 14px 16px;
            font-size: 14px;
            line-height: 1.6;
            color: #e2e8f0;
            white-space: pre-wrap;
            margin: 16px 0 24px;
        }

        .reply-form {
            border-top: 1px solid #242e40;
            padding-top: 18px;
        }
        .reply-form label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #cbd5e1;
            margin-bottom: 6px;
        }
        .reply-select, .reply-textarea {
            width: 100%;
            background-color: #131924;
            border: 1px solid #283449;
            border-radius: 6px;
            color: #f1f5f9;
            padding: 9px 12px;
            font-size: 13.5px;
            font-family: inherit;
            margin-bottom: 14px;
            outline: none;
        }
        .reply-select:focus, .reply-textarea:focus { border-color: #3b82f6; }
        .reply-textarea { resize: vertical; min-height: 90px; }

        .template-pills {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            margin-bottom: 12px;
        }
        .template-pill {
            font-size: 11px;
            background-color: #1f2838;
            border: 1px solid #2d384c;
            color: #94a3b8;
            padding: 3px 8px;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.15s;
        }
        .template-pill:hover {
            color: #60a5fa;
            border-color: #3b82f6;
        }

        .modal-footer {
            padding: 16px 24px;
            border-top: 1px solid #242e40;
            background-color: #141a25;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
        }
        .btn-email-link {
            font-size: 13px;
            color: #60a5fa;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-email-link:hover { text-decoration: underline; }
    </style>
</head>
<body>

<?php include('adnav.php'); ?>

<div class="admin-container">

    <!-- Hero Card -->
    <div class="hero-card">
        <div class="hero-title-group">
            <h1>Inquiries &amp; Messages</h1>
            <p>Review contact submissions, feedback on recommendations, and user movie requests.</p>
        </div>
        <div>
            <a href="contact.php" target="_blank" class="btn-action btn-view" style="padding: 8px 16px;">
                Open Contact Page &rarr;
            </a>
        </div>
    </div>

    <?php if (!empty($toastMessage)): ?>
        <div class="flash-alert <?php echo $toastType === 'error' ? 'flash-error' : 'flash-success'; ?>">
            <span><?php echo htmlspecialchars($toastMessage); ?></span>
            <button onclick="this.parentElement.remove();" style="background:none; border:none; color:inherit; cursor:pointer; font-size:16px;">&times;</button>
        </div>
    <?php endif; ?>

    <!-- Metrics Grid -->
    <div class="metrics-grid">
        <a href="admin_inquiries.php?status=all" class="metric-card <?php echo $statusFilter === 'all' ? 'active' : ''; ?>">
            <div>
                <div class="metric-label">Total Inquiries</div>
                <div class="metric-value"><?php echo number_format($totalCount); ?></div>
            </div>
            <div class="metric-icon">📬</div>
        </a>

        <a href="admin_inquiries.php?status=unread" class="metric-card <?php echo $statusFilter === 'unread' ? 'active' : ''; ?>">
            <div>
                <div class="metric-label" style="color: #fbbf24;">Unread</div>
                <div class="metric-value" style="color: #fbbf24;"><?php echo number_format($unreadCount); ?></div>
            </div>
            <div class="metric-icon">🔔</div>
        </a>

        <a href="admin_inquiries.php?status=in_progress" class="metric-card <?php echo $statusFilter === 'in_progress' ? 'active' : ''; ?>">
            <div>
                <div class="metric-label" style="color: #60a5fa;">In Progress</div>
                <div class="metric-value" style="color: #60a5fa;"><?php echo number_format($inProgressCount); ?></div>
            </div>
            <div class="metric-icon">⏳</div>
        </a>

        <a href="admin_inquiries.php?status=resolved" class="metric-card <?php echo $statusFilter === 'resolved' ? 'active' : ''; ?>">
            <div>
                <div class="metric-label" style="color: #34d399;">Resolved</div>
                <div class="metric-value" style="color: #34d399;"><?php echo number_format($resolvedCount); ?></div>
            </div>
            <div class="metric-icon">✅</div>
        </a>
    </div>

    <!-- Filter & Search Bar -->
    <div class="filter-card">
        <div class="filter-tabs">
            <a href="admin_inquiries.php?status=all<?php echo !empty($searchQuery) ? '&q=' . urlencode($searchQuery) : ''; ?>" class="filter-tab <?php echo $statusFilter === 'all' ? 'active' : ''; ?>">All</a>
            <a href="admin_inquiries.php?status=unread<?php echo !empty($searchQuery) ? '&q=' . urlencode($searchQuery) : ''; ?>" class="filter-tab <?php echo $statusFilter === 'unread' ? 'active' : ''; ?>">Unread (<?php echo $unreadCount; ?>)</a>
            <a href="admin_inquiries.php?status=in_progress<?php echo !empty($searchQuery) ? '&q=' . urlencode($searchQuery) : ''; ?>" class="filter-tab <?php echo $statusFilter === 'in_progress' ? 'active' : ''; ?>">In Progress</a>
            <a href="admin_inquiries.php?status=resolved<?php echo !empty($searchQuery) ? '&q=' . urlencode($searchQuery) : ''; ?>" class="filter-tab <?php echo $statusFilter === 'resolved' ? 'active' : ''; ?>">Resolved</a>
        </div>

        <form action="admin_inquiries.php" method="GET" class="search-form">
            <?php if ($statusFilter !== 'all'): ?>
                <input type="hidden" name="status" value="<?php echo htmlspecialchars($statusFilter); ?>">
            <?php endif; ?>
            <input type="text" name="q" class="search-input" placeholder="Search by name, email, subject..." value="<?php echo htmlspecialchars($searchQuery); ?>">
            <button type="submit" class="search-btn">Search</button>
            <?php if (!empty($searchQuery)): ?>
                <a href="admin_inquiries.php?status=<?php echo urlencode($statusFilter); ?>" class="filter-tab" style="padding: 8px 10px;">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Inquiries Table -->
    <div class="table-card">
        <div class="table-responsive">
            <table class="inq-table">
                <thead>
                    <tr>
                        <th>Ref ID</th>
                        <th>Sender</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th>Date Received</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($inquiriesResult && $inquiriesResult->num_rows > 0): ?>
                    <?php while ($row = $inquiriesResult->fetch_assoc()): ?>
                        <?php 
                            $ref = '#INQ-' . str_pad($row['id'], 4, '0', STR_PAD_LEFT);
                            $dateStr = date('M d, Y h:i A', strtotime($row['created_at']));
                            $statusClass = 'badge-unread';
                            $statusText = 'Unread';
                            if ($row['status'] === 'in_progress') {
                                $statusClass = 'badge-progress';
                                $statusText = 'In Progress';
                            } elseif ($row['status'] === 'resolved') {
                                $statusClass = 'badge-resolved';
                                $statusText = 'Resolved';
                            }

                            $jsonRow = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                        ?>
                        <tr class="<?php echo ($row['status'] === 'unread') ? 'is-unread' : ''; ?>">
                            <td style="font-family: monospace; font-weight: 700; color: #60a5fa;">
                                <?php echo $ref; ?>
                            </td>
                            <td>
                                <div style="color: #f8fafc; font-weight: 600;">
                                    <?php echo htmlspecialchars($row['name']); ?>
                                    <?php if (!empty($row['user_id'])): ?>
                                        <span class="badge badge-user" title="Registered User">Registered</span>
                                    <?php endif; ?>
                                </div>
                                <div style="font-size: 12px; color: #94a3b8;">
                                    <?php echo htmlspecialchars($row['email']); ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-subject"><?php echo htmlspecialchars($row['subject']); ?></span>
                                <div style="font-size: 12.5px; color: #94a3b8; max-width: 320px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; margin-top: 3px;">
                                    <?php echo htmlspecialchars($row['message']); ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                            </td>
                            <td style="color: #94a3b8; font-size: 12.5px; white-space: nowrap;">
                                <?php echo $dateStr; ?>
                            </td>
                            <td style="text-align: right; white-space: nowrap;">
                                <button type="button" class="btn-action btn-view" onclick="openInquiryModal(<?php echo $jsonRow; ?>)">
                                    View &amp; Reply
                                </button>
                                <a href="admin_inquiries.php?delete=<?php echo $row['id']; ?>" class="btn-action btn-delete" onclick="return confirm('Are you sure you want to delete inquiry <?php echo $ref; ?>?');">
                                    Delete
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <div class="empty-state-icon">📭</div>
                                <h3 style="color:#cbd5e1; margin:0 0 6px 0; font-size:16px;">No Inquiries Found</h3>
                                <p style="margin:0; font-size:13.5px;">There are currently no messages matching your criteria.</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal for Viewing & Updating Inquiry -->
<div class="modal-overlay" id="inquiryModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3 id="modalRefTitle">Inquiry Details</h3>
            <button type="button" class="modal-close" onclick="closeInquiryModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="detail-row">
                <div class="detail-item" style="flex:1;">
                    <div class="detail-label">Sender Name</div>
                    <div class="detail-value" id="modalSenderName">-</div>
                </div>
                <div class="detail-item" style="flex:1;">
                    <div class="detail-label">Email Address</div>
                    <div class="detail-value" id="modalSenderEmail">-</div>
                </div>
            </div>
            <div class="detail-row">
                <div class="detail-item" style="flex:1;">
                    <div class="detail-label">Subject Category</div>
                    <div class="detail-value" id="modalSubject">-</div>
                </div>
                <div class="detail-item" style="flex:1;">
                    <div class="detail-label">Date Submitted</div>
                    <div class="detail-value" id="modalCreatedAt">-</div>
                </div>
            </div>

            <div class="detail-label" style="margin-top: 14px;">User Message:</div>
            <div class="message-box" id="modalMessage">-</div>

            <!-- Reply / Status Update Form -->
            <form action="admin_inquiries.php" method="POST" class="reply-form">
                <input type="hidden" name="action" value="update_inquiry">
                <input type="hidden" name="inquiry_id" id="modalInquiryId" value="">

                <label for="statusSelect">Update Status:</label>
                <select name="status" id="statusSelect" class="reply-select">
                    <option value="unread">Unread</option>
                    <option value="in_progress">In Progress</option>
                    <option value="resolved">Resolved</option>
                </select>

                <label for="adminReply">Admin Reply (Visible on User Dashboard):</label>
                <span style="font-size:12px; color:#94a3b8; display:block; margin-top:-3px; margin-bottom:10px;">
                    💡 This response will be shown directly on the user's dashboard under <strong>My Inquiries &amp; Support Messages</strong>.
                </span>
                <div class="template-pills">
                    <span style="font-size:11px; color:#64748b; margin-right:4px;">Canned response:</span>
                    <button type="button" class="template-pill" onclick="applyTemplate('movie')">Movie Added</button>
                    <button type="button" class="template-pill" onclick="applyTemplate('feedback')">Feedback Noted</button>
                    <button type="button" class="template-pill" onclick="applyTemplate('bug')">Bug Resolved</button>
                </div>
                <textarea name="admin_reply" id="adminReply" class="reply-textarea" placeholder="Write your response to the user here..."></textarea>

                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" class="btn-action" style="background:#283449; color:#cbd5e1;" onclick="closeInquiryModal()">Close</button>
                    <button type="submit" class="btn-action btn-view" style="padding:8px 18px;">Save &amp; Send to Dashboard</button>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <span style="font-size: 12.5px; color: #94a3b8;" id="modalRepliedInfo"></span>
            <div style="display:flex; align-items:center; gap:12px;">
                <span style="font-size:12px; color:#34d399;">✓ Published to User Dashboard</span>
                <a href="#" id="modalMailto" class="btn-email-link" target="_blank" style="font-size:12px; opacity:0.75;">
                    ✉️ Optional: Also Email
                </a>
            </div>
        </div>
    </div>
</div>

<script>
var currentInquiry = null;

function openInquiryModal(row) {
    currentInquiry = row;
    var ref = '#INQ-' + String(row.id).padStart(4, '0');
    document.getElementById('modalRefTitle').textContent = 'Inquiry ' + ref;
    document.getElementById('modalInquiryId').value = row.id;
    document.getElementById('modalSenderName').textContent = row.name + (row.user_id ? ' (Registered User #' + row.user_id + ')' : ' (Guest)');
    document.getElementById('modalSenderEmail').textContent = row.email;
    document.getElementById('modalSubject').textContent = row.subject;
    document.getElementById('modalCreatedAt').textContent = row.created_at;
    document.getElementById('modalMessage').textContent = row.message;
    document.getElementById('statusSelect').value = row.status || 'unread';
    document.getElementById('adminReply').value = row.admin_reply || '';

    var repliedInfo = document.getElementById('modalRepliedInfo');
    if (row.replied_at) {
        repliedInfo.textContent = 'Last updated: ' + row.replied_at;
    } else {
        repliedInfo.textContent = 'Not yet addressed';
    }

    var mailSubject = encodeURIComponent('Re: [' + ref + '] ' + row.subject);
    var mailBody = encodeURIComponent('Hi ' + row.name + ',\n\nRegarding your inquiry:\n"' + row.message.substring(0, 100) + '..."\n\n');
    document.getElementById('modalMailto').href = 'mailto:' + encodeURIComponent(row.email) + '?subject=' + mailSubject + '&body=' + mailBody;

    document.getElementById('inquiryModal').classList.add('active');
}

function closeInquiryModal() {
    document.getElementById('inquiryModal').classList.remove('active');
}

function applyTemplate(type) {
    var textarea = document.getElementById('adminReply');
    var statusSelect = document.getElementById('statusSelect');
    if (type === 'movie') {
        textarea.value = "Evaluated user's movie suggestion and updated the catalog. Movie is now accessible in the database.";
        statusSelect.value = 'resolved';
    } else if (type === 'feedback') {
        textarea.value = "Reviewed recommendation tuning feedback and verified KNN vector weights.";
        statusSelect.value = 'resolved';
    } else if (type === 'bug') {
        textarea.value = "Investigated and fixed reported issue. Verified functionality.";
        statusSelect.value = 'resolved';
    }
}

// Close on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeInquiryModal();
});

// Close when clicking backdrop
document.getElementById('inquiryModal').addEventListener('click', function(e) {
    if (e.target === this) closeInquiryModal();
});
</script>

</body>
</html>
