<?php
// Website Settings
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'includes/helpers.php';

requireLogin();

$pageTitle = 'Website Settings';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = getDBConnection();
        
        foreach ($_POST as $key => $value) {
            if ($key !== 'csrf_token') {
                $stmt = $pdo->prepare("INSERT INTO website_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
                $stmt->execute([$key, $value, $value]);
            }
        }
        
        setFlashMessage('Settings saved successfully', 'success');
        redirect('website-settings.php');
    } catch (Exception $e) {
        $error = 'Failed to save settings: ' . $e->getMessage();
    }
}

// Get current settings
try {
    $pdo = getDBConnection();
    $settings_raw = $pdo->query("SELECT setting_key, setting_value FROM website_settings")->fetchAll();
    $settings = [];
    foreach ($settings_raw as $setting) {
        $settings[$setting['setting_key']] = $setting['setting_value'];
    }
} catch (Exception $e) {
    $settings = [];
    $error = $e->getMessage();
}

include 'includes/header.php';
?>

<div class="container-fluid px-4 py-4">
    <div class="mb-4">
        <h1 class="page-title mb-0">
            <i class="bi bi-gear me-2"></i>Website Settings
        </h1>
        <p class="text-muted">Manage website information and contact details</p>
    </div>

    <?php if ($flash = getFlashMessage()): ?>
        <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>
            <?php echo htmlspecialchars($flash['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="card modern-card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>General Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Site Name (Arabic)</label>
                        <input type="text" name="site_name_ar" class="form-control" value="<?php echo htmlspecialchars($settings['site_name_ar'] ?? 'جول تشي سبورتس'); ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Site Name (English)</label>
                        <input type="text" name="site_name_en" class="form-control" value="<?php echo htmlspecialchars($settings['site_name_en'] ?? 'GoalChii Sports'); ?>">
                    </div>
                </div>
            </div>
        </div>

        <div class="card modern-card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-telephone me-2"></i>Contact Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="site_email" class="form-control" value="<?php echo htmlspecialchars($settings['site_email'] ?? 'info@goalchii.store'); ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" name="site_phone" class="form-control" value="<?php echo htmlspecialchars($settings['site_phone'] ?? '+964 751 521 5455'); ?>">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Address (Arabic)</label>
                        <input type="text" name="site_address_ar" class="form-control" value="<?php echo htmlspecialchars($settings['site_address_ar'] ?? 'الرياض، المملكة العربية السعودية'); ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Address (English)</label>
                        <input type="text" name="site_address_en" class="form-control" value="<?php echo htmlspecialchars($settings['site_address_en'] ?? 'Riyadh, Saudi Arabia'); ?>">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">WhatsApp Number</label>
                    <input type="text" name="whatsapp_number" class="form-control" value="<?php echo htmlspecialchars($settings['whatsapp_number'] ?? '+966123456789'); ?>">
                </div>
            </div>
        </div>

        <div class="card modern-card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-share me-2"></i>Social Media Links</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><i class="bi bi-facebook me-2"></i>Facebook</label>
                        <input type="url" name="facebook_url" class="form-control" value="<?php echo htmlspecialchars($settings['facebook_url'] ?? '#'); ?>" placeholder="https://facebook.com/...">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><i class="bi bi-instagram me-2"></i>Instagram</label>
                        <input type="url" name="instagram_url" class="form-control" value="<?php echo htmlspecialchars($settings['instagram_url'] ?? '#'); ?>" placeholder="https://instagram.com/...">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><i class="bi bi-twitter me-2"></i>Twitter</label>
                        <input type="url" name="twitter_url" class="form-control" value="<?php echo htmlspecialchars($settings['twitter_url'] ?? '#'); ?>" placeholder="https://twitter.com/...">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><i class="bi bi-youtube me-2"></i>YouTube</label>
                        <input type="url" name="youtube_url" class="form-control" value="<?php echo htmlspecialchars($settings['youtube_url'] ?? '#'); ?>" placeholder="https://youtube.com/...">
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-circle me-2"></i>Save Settings
            </button>
            <a href="ecommerce-dashboard.php" class="btn btn-outline-secondary">
                Cancel
            </a>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
