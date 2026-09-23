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

if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];

    // Disable FK checks so no related table blocks the delete
    mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 0");

    // Clean up watched_movies linked to this user
    mysqli_query($conn, "DELETE FROM watched_movies WHERE user_id = $delete_id");

    // Delete the user
    $delete_user_result = mysqli_query($conn, "DELETE FROM user WHERE id = $delete_id");
    // Capture IMMEDIATELY before any other query resets it
    $affected = mysqli_affected_rows($conn);

    // Re-enable FK checks
    mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 1");

    if ($delete_user_result && $affected > 0) {
        header('Location: shu.php?deleted=1');
        exit();
    } elseif ($delete_user_result && $affected === 0) {
        die('No user found with that ID. Nothing was deleted.');
    } else {
        die('Failed to delete user (ID: ' . $delete_id . '): ' . mysqli_error($conn));
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin Panel</title>
    <style>
        * {
            box-sizing: border-box;
        }

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

        .page-container {
            max-width: 1100px;
            width: 100%;
            margin: 32px auto;
            padding: 0 20px;
            flex: 1;
        }

        .table-card {
            background-color: #181f2c;
            border: 1px solid #242e40;
            border-radius: 8px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.25);
            padding: 28px 24px;
        }

        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid #242e40;
            flex-wrap: wrap;
            gap: 12px;
        }

        .card-title {
            color: #f8fafc;
            font-size: 20px;
            font-weight: 700;
            margin: 0;
        }

        .badge-count {
            font-size: 12px;
            color: #94a3b8;
            background-color: #131924;
            border: 1px solid #263347;
            padding: 4px 10px;
            border-radius: 4px;
        }

        .table-responsive {
            overflow-x: auto;
        }

        table.user-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13.5px;
        }

        table.user-table th {
            text-align: left;
            padding: 12px 14px;
            background-color: #131924;
            color: #94a3b8;
            font-weight: 600;
            border-bottom: 1px solid #242e40;
            white-space: nowrap;
        }

        table.user-table td {
            padding: 12px 14px;
            border-bottom: 1px solid #1e2637;
            vertical-align: middle;
        }

        table.user-table tr:hover td {
            background-color: rgba(255, 255, 255, 0.02);
        }

        .user-id-badge {
            color: #60a5fa;
            font-weight: 600;
        }

        .delete-btn {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 5px;
            background-color: rgba(239, 68, 68, 0.12);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.3);
            text-decoration: none;
            font-size: 12.5px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s ease, color 0.2s ease;
        }

        .delete-btn:hover {
            background-color: #dc2626;
            color: #ffffff;
            border-color: #dc2626;
        }
        /* ── Confirm Delete Modal ── */
        .confirm-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.65);
            backdrop-filter: blur(4px);
            align-items: center;
            justify-content: center;
            z-index: 9999;
            animation: overlayIn 0.25s ease;
        }
        .confirm-overlay.active { display: flex; }
        @keyframes overlayIn {
            from { opacity: 0; }
            to   { opacity: 1; }
        }
        .confirm-modal {
            background: linear-gradient(145deg, #1a2438, #1e2d42);
            border: 1px solid rgba(239,68,68,0.25);
            border-radius: 14px;
            padding: 36px 32px 28px;
            max-width: 380px;
            width: 90%;
            text-align: center;
            box-shadow: 0 0 50px rgba(239,68,68,0.12), 0 20px 60px rgba(0,0,0,0.5);
            animation: modalIn 0.35s cubic-bezier(0.34,1.56,0.64,1);
        }
        @keyframes modalIn {
            from { transform: scale(0.78) translateY(16px); opacity: 0; }
            to   { transform: scale(1) translateY(0);        opacity: 1; }
        }
        .confirm-icon {
            width: 56px;
            height: 56px;
            margin: 0 auto 18px;
            background: rgba(239,68,68,0.12);
            border: 1.5px solid rgba(239,68,68,0.3);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        .confirm-title {
            color: #f8fafc;
            font-size: 18px;
            font-weight: 700;
            margin: 0 0 8px;
        }
        .confirm-msg {
            color: #94a3b8;
            font-size: 13.5px;
            line-height: 1.6;
            margin: 0 0 6px;
        }
        .confirm-username {
            color: #f87171;
            font-weight: 600;
        }
        .confirm-warn {
            font-size: 12px;
            color: #64748b;
            margin: 0 0 24px;
        }
        .confirm-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
        }
        .btn-cancel {
            flex: 1;
            padding: 10px 0;
            border-radius: 8px;
            border: 1px solid #2d384c;
            background: #131924;
            color: #94a3b8;
            font-size: 13.5px;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
            transition: background 0.2s, color 0.2s;
        }
        .btn-cancel:hover { background: #1e2d42; color: #f1f5f9; }
        .btn-confirm-delete {
            flex: 1;
            padding: 10px 0;
            border-radius: 8px;
            border: none;
            background: linear-gradient(135deg, #b91c1c, #ef4444);
            color: #fff;
            font-size: 13.5px;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
            box-shadow: 0 4px 14px rgba(239,68,68,0.3);
            transition: transform 0.15s, box-shadow 0.15s;
        }
        .btn-confirm-delete:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(239,68,68,0.45);
        }
    </style>

    <!-- Confirm Delete Modal -->
    <div class="confirm-overlay" id="confirmOverlay">
        <div class="confirm-modal">
            <div class="confirm-icon">🗑️</div>
            <h2 class="confirm-title">Delete User?</h2>
            <p class="confirm-msg">You are about to permanently delete <span class="confirm-username" id="confirmUserName"></span>.</p>
            <p class="confirm-warn">This will also remove all their watched movies and ratings. This action cannot be undone.</p>
            <div class="confirm-actions">
                <button class="btn-cancel" onclick="closeConfirm()">Cancel</button>
                <a id="confirmDeleteLink" href="#" class="btn-confirm-delete">Yes, Delete</a>
            </div>
        </div>
    </div>

    <script>
        function openConfirm(userId, userName) {
            document.getElementById('confirmUserName').textContent = userName;
            document.getElementById('confirmDeleteLink').href = 'shu.php?delete=' + userId;
            document.getElementById('confirmOverlay').classList.add('active');
        }
        function closeConfirm() {
            document.getElementById('confirmOverlay').classList.remove('active');
        }
        // Close on backdrop click
        document.getElementById('confirmOverlay').addEventListener('click', function(e) {
            if (e.target === this) closeConfirm();
        });
        // Close on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeConfirm();
        });
    </script>
</head>
<body>

<?php include("adnav.php"); ?>

<div class="page-container">
    <div class="table-card">
        <div class="card-header">
            <h1 class="card-title">Manage Registered Users</h1>
            <span class="badge-count">Member Database</span>
        </div>

        <div class="table-responsive">
            <table class="user-table">
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>Name</th>
                        <th>Email Address</th>
                        <th>Contact</th>
                        <th>Address / City</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $select_users = mysqli_query($conn, "SELECT * FROM user ORDER BY id DESC") or die('Query failed');
                    if (mysqli_num_rows($select_users) > 0) {
                        while ($fetch_user = mysqli_fetch_assoc($select_users)) {
                    ?>
                        <tr>
                            <td><span class="user-id-badge">#<?php echo $fetch_user['id']; ?></span></td>
                            <td><strong><?php echo htmlspecialchars($fetch_user['name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($fetch_user['email']); ?></td>
                            <td><?php echo htmlspecialchars($fetch_user['contact'] ?? '—'); ?></td>
                            <td><?php echo htmlspecialchars($fetch_user['address'] ?? '—'); ?></td>
                            <td>
                                <button class="delete-btn"
                                        onclick="openConfirm(<?php echo $fetch_user['id']; ?>, '<?php echo addslashes(htmlspecialchars($fetch_user['name'])); ?>')">Delete User</button>
                            </td>
                        </tr>
                    <?php
                        }    
                    } else {
                        echo "<tr><td colspan='6' style='text-align:center; padding: 24px; color:#94a3b8;'>No registered users found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>


