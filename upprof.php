<?php
if (!isset($conn)) {
    include('connection.php');
}

$profileSuccess = '';
$profileError = '';

if (isset($_SESSION['uemail'])) {
    $email = $_SESSION['uemail'];

    // Handle form submission
    if (isset($_POST['submit'])) {
        $new_name = trim($_POST['name'] ?? '');
        $new_address = trim($_POST['address'] ?? '');
        $new_email = trim($_POST['email'] ?? '');
        $new_contact = trim($_POST['contact'] ?? '');

        if (empty($new_name) || empty($new_email)) {
            $profileError = "Name and Email are required fields.";
        } else {
            // Check if email changed and if it is unique
            if ($new_email !== $email) {
                $check_stmt = $conn->prepare("SELECT id FROM user WHERE email = ? AND email != ?");
                $check_stmt->bind_param("ss", $new_email, $email);
                $check_stmt->execute();
                $check_res = $check_stmt->get_result();

                if ($check_res && $check_res->num_rows > 0) {
                    $profileError = "The email address is already in use by another account.";
                } else {
                    $upd_email_stmt = $conn->prepare("UPDATE user SET email = ? WHERE email = ?");
                    $upd_email_stmt->bind_param("ss", $new_email, $email);
                    $upd_email_stmt->execute();
                    $_SESSION['uemail'] = $new_email;
                    setcookie('uemail', $new_email, [
                        'expires'  => 0,
                        'path'     => '/',
                        'httponly' => true,
                        'samesite' => 'Lax',
                    ]);
                    $email = $new_email;
                }
            }

            if (empty($profileError)) {
                $upd_stmt = $conn->prepare("UPDATE user SET name = ?, address = ?, contact = ? WHERE email = ?");
                $upd_stmt->bind_param("ssss", $new_name, $new_address, $new_contact, $email);
                if ($upd_stmt->execute()) {
                    $profileSuccess = "Profile updated successfully!";
                    if (isset($userName)) {
                        $userName = $new_name;
                    }
                } else {
                    $profileError = "Failed to update profile. Please try again.";
                }
            }
        }
    }

    // Always fetch latest user details
    $user_query = $conn->prepare("SELECT * FROM user WHERE email = ?");
    $user_query->bind_param("s", $email);
    $user_query->execute();
    $user_res = $user_query->get_result();

    if ($user_res && $user_res->num_rows === 1) {
        $user_row = $user_res->fetch_assoc();
        $name = $user_row['name'];
        $address = $user_row['address'];
        $contact = $user_row['contact'];
        $email = $user_row['email'];
    }
}
?>

<div class="profile-form-wrapper">
    <?php if (!empty($profileSuccess)): ?>
        <div class="dash-alert dash-alert-success">
            <?php echo htmlspecialchars($profileSuccess); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($profileError)): ?>
        <div class="dash-alert dash-alert-error">
            <?php echo htmlspecialchars($profileError); ?>
        </div>
    <?php endif; ?>

    <form action="" method="post">
        <div class="form-row">
            <div class="form-group">
                <label class="dash-label">Full Name</label>
                <input type="text" name="name" class="dash-input" value="<?php echo htmlspecialchars($name ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label class="dash-label">Email Address</label>
                <input type="email" name="email" class="dash-input" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="dash-label">Contact Number</label>
                <input type="text" name="contact" class="dash-input" value="<?php echo htmlspecialchars($contact ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label class="dash-label">Address / Location</label>
                <input type="text" name="address" class="dash-input" value="<?php echo htmlspecialchars($address ?? ''); ?>" required>
            </div>
        </div>

        <div class="form-actions">
            <input type="submit" name="submit" value="Save Changes" class="btn btn-primary">
        </div>
    </form>
</div>