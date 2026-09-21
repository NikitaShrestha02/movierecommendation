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

// Check if bookings table exists before any DML
$tableCheck = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bookings'");
$bookingsTableExists = ($tableCheck && (int)mysqli_fetch_assoc($tableCheck)['cnt'] > 0);

if ($bookingsTableExists && isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];

    // Perform deletion of the booking
    $delete_query = "DELETE FROM bookings WHERE id='$delete_id'";
    $result = mysqli_query($conn, $delete_query);

    if ($result) {
        header('Location: show.php');
        exit();
    } else {
        die('Failed to delete booking: ' . mysqli_error($conn));
    }
}

// Count total bookings for the badge (only if table exists)
$total_bookings = 0;
if ($bookingsTableExists) {
    $count_result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM bookings");
    if ($count_result) $total_bookings = (int)mysqli_fetch_assoc($count_result)['total'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Bookings - Admin Panel</title>
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
            max-width: 1200px;
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

        table.booking-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13.5px;
        }

        table.booking-table th {
            text-align: left;
            padding: 12px 14px;
            background-color: #131924;
            color: #94a3b8;
            font-weight: 600;
            border-bottom: 1px solid #242e40;
            white-space: nowrap;
        }

        table.booking-table td {
            padding: 12px 14px;
            border-bottom: 1px solid #1e2637;
            vertical-align: middle;
        }

        table.booking-table tr:last-child td {
            border-bottom: none;
        }

        table.booking-table tr:hover td {
            background-color: rgba(255, 255, 255, 0.02);
        }

        .booking-id-badge {
            color: #60a5fa;
            font-weight: 600;
        }

        .price-badge {
            color: #4ade80;
            font-weight: 600;
        }

        .seats-badge {
            display: inline-block;
            background-color: rgba(37, 99, 235, 0.12);
            color: #93c5fd;
            border: 1px solid rgba(37, 99, 235, 0.25);
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }

        .selected-seats-text {
            color: #94a3b8;
            font-size: 12px;
            max-width: 160px;
            word-break: break-all;
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

        .empty-state {
            text-align: center;
            padding: 48px 24px;
            color: #94a3b8;
        }

        .empty-state-icon {
            font-size: 36px;
            margin-bottom: 12px;
        }

        .empty-state p {
            margin: 0;
            font-size: 14px;
        }
    </style>
    <script>
        function confirmDelete() {
            return confirm('Are you sure you want to delete this booking?');
        }
    </script>
</head>
<body>

<?php include("adnav.php"); ?>


</body>
</html>
