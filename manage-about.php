<?php
// Manage About Page Content
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'includes/helpers.php';

requireLogin();

$pageTitle = 'Manage About Page';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = getDBConnection();
        
                // Update or insert sections
                foreach ($_POST['sections'] as $section_key => $section_data) {
                    // Check if section exists
                    $stmt = $pdo->prepare("SELECT id FROM about_content WHERE section_key = ?");
                    $stmt->execute([$section_key]);
                    $exists = $stmt->fetch();
                    
                    if ($exists) {
                        // Update existing section
                        $stmt = $pdo->prepare("
                            UPDATE about_content 
                            SET title_ar = ?, title_en = ?, title_ku = ?, content_ar = ?, content_en = ?, content_ku = ?, 
                                image_url = ?, display_order = ?, is_active = ?
                            WHERE section_key = ?
                        ");
                        $stmt->execute([
                            $section_data['title_ar'] ?? '',
                            $section_data['title_en'] ?? '',
                            $section_data['title_ku'] ?? '',
                            $section_data['content_ar'] ?? '',
                            $section_data['content_en'] ?? '',
                            $section_data['content_ku'] ?? '',
                            $section_data['image_url'] ?? '',
                            (int)($section_data['display_order'] ?? 0),
                            isset($section_data['is_active']) ? 1 : 0,
                            $section_key
                        ]);
                    } else {
                        // Insert new section
                        $stmt = $pdo->prepare("
                            INSERT INTO about_content 
                            (section_key, title_ar, title_en, title_ku, content_ar, content_en, content_ku, image_url, display_order, is_active)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([
                            $section_key,
                            $section_data['title_ar'] ?? '',
                            $section_data['title_en'] ?? '',
                            $section_data['title_ku'] ?? '',
                            $section_data['content_ar'] ?? '',
                            $section_data['content_en'] ?? '',
                            $section_data['content_ku'] ?? '',
                            $section_data['image_url'] ?? '',
                            (int)($section_data['display_order'] ?? 0),
                            isset($section_data['is_active']) ? 1 : 0
                        ]);
                    }
        }
        
        setFlashMessage('About page content updated successfully', 'success');
        redirect('manage-about.php');
    } catch (Exception $e) {
        $error = 'Failed to update content: ' . $e->getMessage();
    }
}

// Get all about content sections
try {
    $pdo = getDBConnection();
    $sections = $pdo->query("SELECT * FROM about_content ORDER BY display_order ASC")->fetchAll(PDO::FETCH_ASSOC);
    
    // Create array keyed by section_key
    $sections_by_key = [];
    foreach ($sections as $section) {
        $sections_by_key[$section['section_key']] = $section;
    }
    
    // Default sections if not exist
    $default_sections = [
        'mission' => ['title_ar' => 'مهمتنا', 'title_en' => 'Our Mission', 'content_ar' => '', 'content_en' => ''],
        'vision' => ['title_ar' => 'رؤيتنا', 'title_en' => 'Our Vision', 'content_ar' => '', 'content_en' => ''],
        'values' => ['title_ar' => 'قيمنا', 'title_en' => 'Our Values', 'content_ar' => '', 'content_en' => ''],
        'story' => ['title_ar' => 'قصتنا', 'title_en' => 'Our Story', 'content_ar' => '', 'content_en' => '']
    ];
    
    // Merge defaults with existing
    foreach ($default_sections as $key => $default) {
        if (!isset($sections_by_key[$key])) {
            $sections_by_key[$key] = array_merge($default, ['section_key' => $key, 'image_url' => '', 'display_order' => 0, 'is_active' => 1]);
        }
    }
    
} catch (Exception $e) {
    $sections_by_key = [];
    $error = $e->getMessage();
}

include 'includes/header.php';
?>

<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="page-title mb-0">
                <i class="bi bi-info-circle me-2"></i>Manage About Page
            </h1>
            <p class="text-muted">Update company information and about page content</p>
        </div>
        <a href="store/about.php" target="_blank" class="btn btn-outline-primary">
            <i class="bi bi-eye me-2"></i>Preview Page
        </a>
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
                <h5 class="mb-0"><i class="bi bi-bullseye me-2"></i>Mission Section</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Title (Arabic)</label>
                        <input type="text" name="sections[mission][title_ar]" class="form-control" 
                               value="<?php echo htmlspecialchars($sections_by_key['mission']['title_ar'] ?? 'مهمتنا'); ?>" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Title (English)</label>
                        <input type="text" name="sections[mission][title_en]" class="form-control" 
                               value="<?php echo htmlspecialchars($sections_by_key['mission']['title_en'] ?? 'Our Mission'); ?>" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Title (Kurdish)</label>
                        <input type="text" name="sections[mission][title_ku]" class="form-control" 
                               value="<?php echo htmlspecialchars($sections_by_key['mission']['title_ku'] ?? 'ئەرکەکەمان'); ?>" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Content (Arabic)</label>
                        <textarea name="sections[mission][content_ar]" class="form-control" rows="5" required><?php echo htmlspecialchars($sections_by_key['mission']['content_ar'] ?? ''); ?></textarea>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Content (English)</label>
                        <textarea name="sections[mission][content_en]" class="form-control" rows="5"><?php echo htmlspecialchars($sections_by_key['mission']['content_en'] ?? ''); ?></textarea>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Content (Kurdish)</label>
                        <textarea name="sections[mission][content_ku]" class="form-control" rows="5"><?php echo htmlspecialchars($sections_by_key['mission']['content_ku'] ?? ''); ?></textarea>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Image URL (Optional)</label>
                    <input type="url" name="sections[mission][image_url]" class="form-control" 
                           value="<?php echo htmlspecialchars($sections_by_key['mission']['image_url'] ?? ''); ?>" 
                           placeholder="https://example.com/image.jpg">
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Display Order</label>
                        <input type="number" name="sections[mission][display_order]" class="form-control" 
                               value="<?php echo $sections_by_key['mission']['display_order'] ?? 1; ?>" min="0">
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="form-check mt-4">
                            <input type="checkbox" name="sections[mission][is_active]" class="form-check-input" 
                                   id="mission_active" <?php echo ($sections_by_key['mission']['is_active'] ?? 1) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="mission_active">Active</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card modern-card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-eye me-2"></i>Vision Section</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Title (Arabic)</label>
                        <input type="text" name="sections[vision][title_ar]" class="form-control" 
                               value="<?php echo htmlspecialchars($sections_by_key['vision']['title_ar'] ?? 'رؤيتنا'); ?>" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Title (English)</label>
                        <input type="text" name="sections[vision][title_en]" class="form-control" 
                               value="<?php echo htmlspecialchars($sections_by_key['vision']['title_en'] ?? 'Our Vision'); ?>" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Title (Kurdish)</label>
                        <input type="text" name="sections[vision][title_ku]" class="form-control" 
                               value="<?php echo htmlspecialchars($sections_by_key['vision']['title_ku'] ?? 'بینینەکەمان'); ?>" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Content (Arabic)</label>
                        <textarea name="sections[vision][content_ar]" class="form-control" rows="5" required><?php echo htmlspecialchars($sections_by_key['vision']['content_ar'] ?? ''); ?></textarea>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Content (English)</label>
                        <textarea name="sections[vision][content_en]" class="form-control" rows="5"><?php echo htmlspecialchars($sections_by_key['vision']['content_en'] ?? ''); ?></textarea>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Content (Kurdish)</label>
                        <textarea name="sections[vision][content_ku]" class="form-control" rows="5"><?php echo htmlspecialchars($sections_by_key['vision']['content_ku'] ?? ''); ?></textarea>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Image URL (Optional)</label>
                    <input type="url" name="sections[vision][image_url]" class="form-control" 
                           value="<?php echo htmlspecialchars($sections_by_key['vision']['image_url'] ?? ''); ?>" 
                           placeholder="https://example.com/image.jpg">
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Display Order</label>
                        <input type="number" name="sections[vision][display_order]" class="form-control" 
                               value="<?php echo $sections_by_key['vision']['display_order'] ?? 2; ?>" min="0">
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="form-check mt-4">
                            <input type="checkbox" name="sections[vision][is_active]" class="form-check-input" 
                                   id="vision_active" <?php echo ($sections_by_key['vision']['is_active'] ?? 1) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="vision_active">Active</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card modern-card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-star me-2"></i>Values Section</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Title (Arabic)</label>
                        <input type="text" name="sections[values][title_ar]" class="form-control" 
                               value="<?php echo htmlspecialchars($sections_by_key['values']['title_ar'] ?? 'قيمنا'); ?>" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Title (English)</label>
                        <input type="text" name="sections[values][title_en]" class="form-control" 
                               value="<?php echo htmlspecialchars($sections_by_key['values']['title_en'] ?? 'Our Values'); ?>" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Title (Kurdish)</label>
                        <input type="text" name="sections[values][title_ku]" class="form-control" 
                               value="<?php echo htmlspecialchars($sections_by_key['values']['title_ku'] ?? 'بەهاکانمان'); ?>" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Content (Arabic)</label>
                        <textarea name="sections[values][content_ar]" class="form-control" rows="5" required><?php echo htmlspecialchars($sections_by_key['values']['content_ar'] ?? ''); ?></textarea>
                        <small class="text-muted">You can list values separated by bullets or commas</small>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Content (English)</label>
                        <textarea name="sections[values][content_en]" class="form-control" rows="5"><?php echo htmlspecialchars($sections_by_key['values']['content_en'] ?? ''); ?></textarea>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Content (Kurdish)</label>
                        <textarea name="sections[values][content_ku]" class="form-control" rows="5"><?php echo htmlspecialchars($sections_by_key['values']['content_ku'] ?? ''); ?></textarea>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Image URL (Optional)</label>
                    <input type="url" name="sections[values][image_url]" class="form-control" 
                           value="<?php echo htmlspecialchars($sections_by_key['values']['image_url'] ?? ''); ?>" 
                           placeholder="https://example.com/image.jpg">
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Display Order</label>
                        <input type="number" name="sections[values][display_order]" class="form-control" 
                               value="<?php echo $sections_by_key['values']['display_order'] ?? 3; ?>" min="0">
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="form-check mt-4">
                            <input type="checkbox" name="sections[values][is_active]" class="form-check-input" 
                                   id="values_active" <?php echo ($sections_by_key['values']['is_active'] ?? 1) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="values_active">Active</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card modern-card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-book me-2"></i>Story Section (Optional)</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Title (Arabic)</label>
                        <input type="text" name="sections[story][title_ar]" class="form-control" 
                               value="<?php echo htmlspecialchars($sections_by_key['story']['title_ar'] ?? 'قصتنا'); ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Title (English)</label>
                        <input type="text" name="sections[story][title_en]" class="form-control" 
                               value="<?php echo htmlspecialchars($sections_by_key['story']['title_en'] ?? 'Our Story'); ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Title (Kurdish)</label>
                        <input type="text" name="sections[story][title_ku]" class="form-control" 
                               value="<?php echo htmlspecialchars($sections_by_key['story']['title_ku'] ?? 'چیرۆکەکەمان'); ?>">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Content (Arabic)</label>
                        <textarea name="sections[story][content_ar]" class="form-control" rows="5"><?php echo htmlspecialchars($sections_by_key['story']['content_ar'] ?? ''); ?></textarea>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Content (English)</label>
                        <textarea name="sections[story][content_en]" class="form-control" rows="5"><?php echo htmlspecialchars($sections_by_key['story']['content_en'] ?? ''); ?></textarea>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Content (Kurdish)</label>
                        <textarea name="sections[story][content_ku]" class="form-control" rows="5"><?php echo htmlspecialchars($sections_by_key['story']['content_ku'] ?? ''); ?></textarea>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Image URL (Optional)</label>
                    <input type="url" name="sections[story][image_url]" class="form-control" 
                           value="<?php echo htmlspecialchars($sections_by_key['story']['image_url'] ?? ''); ?>" 
                           placeholder="https://example.com/image.jpg">
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Display Order</label>
                        <input type="number" name="sections[story][display_order]" class="form-control" 
                               value="<?php echo $sections_by_key['story']['display_order'] ?? 4; ?>" min="0">
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="form-check mt-4">
                            <input type="checkbox" name="sections[story][is_active]" class="form-check-input" 
                                   id="story_active" <?php echo ($sections_by_key['story']['is_active'] ?? 1) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="story_active">Active</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-circle me-2"></i>Save Changes
            </button>
            <a href="ecommerce-dashboard.php" class="btn btn-outline-secondary">
                Cancel
            </a>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>

