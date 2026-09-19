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
    $check_bookings_query = "SELECT id FROM bookings WHERE user_id='$delete_id'";
    $check_result = mysqli_query($conn, $check_bookings_query);
    if ($check_result) {
        while ($row = mysqli_fetch_assoc($check_result)) {
            $booking_id = $row['id'];
            mysqli_query($conn, "DELETE FROM bookings WHERE id='$booking_id'");
        }
    }
    // Also clean up watched movies by this user to maintain relational integrity
    mysqli_query($conn, "DELETE FROM watched_movies WHERE user_id='$delete_id'");

    $delete_user_query = "DELETE FROM user WHERE id='$delete_id'";
    $delete_user_result = mysqli_query($conn, $delete_user_query);
    
    if ($delete_user_result) {
        header('Location: shu.php'); 
        exit();
    } else {
        die('Failed to delete user: ' . mysqli_error($conn));
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
    </style>
    <script>
        function confirmDelete() {
            return confirm('Are you sure you want to permanently delete this user and all associated records?');
        }
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
                                <a href="shu.php?delete=<?php echo $fetch_user['id']; ?>" class="delete-btn" 
                                   onclick="return confirmDelete();">Delete User</a>
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


