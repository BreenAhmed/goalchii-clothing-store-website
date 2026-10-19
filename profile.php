<?php
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'includes/helpers.php';

// Require login
requireLogin();

$pageTitle = 'Profile';
$errors = [];
$success = false;

// Get user data
try {
    $stmt = db()->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    setFlashMessage('error', 'Error loading profile.');
    redirect('dashboard.php');
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        $action = $_POST['action'];
        
        if ($action == 'update_profile') {
            // Update profile information
            $fullName = sanitize($_POST['full_name']);
            $email = sanitize($_POST['email']);
            
            if (empty($fullName)) {
                $errors[] = 'Full name is required.';
            }
            if (!isValidEmail($email)) {
                $errors[] = 'Valid email is required.';
            }
            
            // Check if email already exists (excluding current user)
            if (empty($errors)) {
                try {
                    $stmt = db()->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                    $stmt->execute([$email, $_SESSION['user_id']]);
                    if ($stmt->fetch()) {
                        $errors[] = 'Email already in use.';
                    }
                } catch (PDOException $e) {
                    $errors[] = 'Database error.';
                }
            }
            
            if (empty($errors)) {
                try {
                    $stmt = db()->prepare("UPDATE users SET full_name = ?, email = ? WHERE id = ?");
                    $stmt->execute([$fullName, $email, $_SESSION['user_id']]);
                    $_SESSION['user_name'] = $fullName;
                    $success = true;
                    $successMessage = 'Profile updated successfully.';
                    
                    // Refresh user data
                    $stmt = db()->prepare("SELECT * FROM users WHERE id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $user = $stmt->fetch();
                } catch (PDOException $e) {
                    $errors[] = 'Error updating profile.';
                }
            }
        } elseif ($action == 'change_password') {
            // Change password
            $currentPassword = $_POST['current_password'];
            $newPassword = $_POST['new_password'];
            $confirmPassword = $_POST['confirm_password'];
            
            if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                $errors[] = 'All password fields are required.';
            } elseif ($newPassword !== $confirmPassword) {
                $errors[] = 'New passwords do not match.';
            } elseif (strlen($newPassword) < 6) {
                $errors[] = 'Password must be at least 6 characters long.';
            } else {
                // Verify current password
                if (!password_verify($currentPassword, $user['password'])) {
                    $errors[] = 'Current password is incorrect.';
                } else {
                    // Update password
                    try {
                        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                        $stmt = db()->prepare("UPDATE users SET password = ? WHERE id = ?");
                        $stmt->execute([$hashedPassword, $_SESSION['user_id']]);
                        $success = true;
                        $successMessage = 'Password changed successfully.';
                    } catch (PDOException $e) {
                        $errors[] = 'Error changing password.';
                    }
                }
            }
        }
    }
}
?>

<?php include 'includes/header.php'; ?>

<div class="container-fluid px-4 py-4">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <h1 class="page-title mb-2">
                👤 My Profile
            </h1>
            <p class="text-muted mb-4">Manage your account settings and preferences</p>
            
            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Error!</strong>
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <strong>Success!</strong> <?php echo $successMessage; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-md-6">
                    <!-- Profile Information -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Profile Information</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="profile.php">
                                <input type="hidden" name="action" value="update_profile">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="username" 
                                           value="<?php echo htmlspecialchars($user['username']); ?>" readonly disabled>
                                    <small class="form-text text-muted">Username cannot be changed</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="full_name" class="form-label">Full Name *</label>
                                    <input type="text" class="form-control" id="full_name" name="full_name" 
                                           required value="<?php echo htmlspecialchars($user['full_name']); ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address *</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           required value="<?php echo htmlspecialchars($user['email']); ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="role" class="form-label">Role</label>
                                    <input type="text" class="form-control" id="role" 
                                           value="<?php echo ucfirst($user['role']); ?>" readonly disabled>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Update Profile
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Account Information -->
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Account Information</h5>
                            <dl>
                                <dt>Account Created</dt>
                                <dd><?php echo formatDate($user['created_at'], 'F d, Y'); ?></dd>
                                
                                <dt>Last Updated</dt>
                                <dd><?php echo formatDate($user['updated_at'], 'F d, Y g:i A'); ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <!-- Change Password -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Change Password</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="profile.php">
                                <input type="hidden" name="action" value="change_password">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Current Password *</label>
                                    <input type="password" class="form-control" id="current_password" 
                                           name="current_password" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password *</label>
                                    <input type="password" class="form-control" id="new_password" 
                                           name="new_password" required minlength="6">
                                    <small class="form-text text-muted">Minimum 6 characters</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm New Password *</label>
                                    <input type="password" class="form-control" id="confirm_password" 
                                           name="confirm_password" required minlength="6">
                                </div>
                                
                                <button type="submit" class="btn btn-warning">
                                    <i class="bi bi-key"></i> Change Password
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
