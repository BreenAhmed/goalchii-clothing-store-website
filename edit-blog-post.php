<?php
// Edit Blog Post
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'includes/helpers.php';
require_once 'includes/upload-handler.php';

requireLogin();

$pageTitle = 'Edit Blog Post';
$errors = [];

// Get post ID
$postId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$postId) {
    setFlashMessage('Invalid post ID', 'error');
    redirect('manage-blog.php');
}

// Get post data
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM blog_posts WHERE id = ?");
    $stmt->execute([$postId]);
    $post = $stmt->fetch();
    
    if (!$post) {
        setFlashMessage('Post not found', 'error');
        redirect('manage-blog.php');
    }
} catch (PDOException $e) {
    setFlashMessage('Error loading post', 'error');
    redirect('manage-blog.php');
}

// Get current user
$currentUser = $_SESSION['user'] ?? null;
$authorId = $currentUser['id'] ?? null;

$slugGeneratorLoaded = function_exists('generateBlogSlug');
if (!$slugGeneratorLoaded) {
    function blogSlugifyCandidate(?string $text): string {
        if (empty($text)) {
            return '';
        }
        $text = trim($text);
        $transliterated = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        if ($transliterated === false || $transliterated === '') {
            $transliterated = $text;
        }
        $slug = preg_replace('/[^A-Za-z0-9-]+/', '-', $transliterated);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        return strtolower($slug);
    }

    function generateBlogSlug(?string $titleAr, ?string $titleKu, ?string $titleEn): string {
        $candidates = [
            blogSlugifyCandidate($titleEn),
            blogSlugifyCandidate($titleKu),
            blogSlugifyCandidate($titleAr),
        ];

        foreach ($candidates as $candidate) {
            if (!empty($candidate)) {
                return $candidate;
            }
        }

        try {
            $random = bin2hex(random_bytes(4));
        } catch (Exception $e) {
            $random = uniqid();
        }
        return 'post-' . date('Ymd-His') . '-' . $random;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        // Get form data
        $titleAr = sanitize($_POST['title_ar'] ?? '');
        $titleEn = sanitize($_POST['title_en'] ?? '');
        $titleKu = sanitize($_POST['title_ku'] ?? '');
        $contentAr = $_POST['content_ar'] ?? '';
        $contentEn = $_POST['content_en'] ?? '';
        $contentKu = $_POST['content_ku'] ?? '';
        $excerptAr = sanitize($_POST['excerpt_ar'] ?? '');
        $excerptEn = sanitize($_POST['excerpt_en'] ?? '');
        $excerptKu = sanitize($_POST['excerpt_ku'] ?? '');
        $featuredImage = $post['featured_image'] ?? ''; // Keep existing image by default
        
        // Handle featured image upload
        if (isset($_FILES['featured_image_upload']) && $_FILES['featured_image_upload']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = uploadBlogImage($_FILES['featured_image_upload']);
            if ($uploadResult['success']) {
                // Delete old image if it exists and is a local file
                if ($featuredImage && strpos($featuredImage, 'assets/images/blog/') === 0) {
                    deleteProductImage($featuredImage); // Reuse the delete function
                }
                $featuredImage = $uploadResult['path'];
            } else {
                $errors[] = 'Featured image: ' . $uploadResult['error'];
            }
        }
        
        $status = sanitize($_POST['status'] ?? 'draft');
        $metaTitle = sanitize($_POST['meta_title'] ?? '');
        $metaDescription = sanitize($_POST['meta_description'] ?? '');
        $metaKeywords = sanitize($_POST['meta_keywords'] ?? '');
        
        // Validate required fields
        if (empty($titleAr)) {
            $errors[] = 'Title (Arabic) is required.';
        }
        if (empty($contentAr)) {
            $errors[] = 'Content (Arabic) is required.';
        }
        
        // Generate slug from title if changed
        $slug = $post['slug'];
        if ($titleAr !== $post['title_ar']) {
            $slug = generateBlogSlug($titleAr, $titleKu, $titleEn);
            
            // Ensure slug is unique (excluding current post)
            try {
                $pdo = getDBConnection();
                $originalSlug = $slug;
                $counter = 1;
                while (true) {
                    $stmt = $pdo->prepare("SELECT id FROM blog_posts WHERE slug = ? AND id != ?");
                    $stmt->execute([$slug, $postId]);
                    if (!$stmt->fetch()) {
                        break; // Slug is unique
                    }
                    $slug = $originalSlug . '-' . $counter;
                    $counter++;
                }
            } catch (Exception $e) {
                $errors[] = 'Error generating slug.';
            }
        }
        
        // Update blog post if no errors
        if (empty($errors)) {
            try {
                $pdo = getDBConnection();
                
                // Set published_at if status changed to published and wasn't published before
                $publishedAt = $post['published_at'];
                if ($status === 'published' && $post['status'] !== 'published') {
                    $publishedAt = date('Y-m-d H:i:s');
                } elseif ($status === 'draft') {
                    $publishedAt = null;
                }
                
                $stmt = $pdo->prepare("
                    UPDATE blog_posts SET
                        title_ar = ?,
                        title_en = ?,
                        title_ku = ?,
                        slug = ?,
                        content_ar = ?,
                        content_en = ?,
                        content_ku = ?,
                        excerpt_ar = ?,
                        excerpt_en = ?,
                        excerpt_ku = ?,
                        featured_image = ?,
                        status = ?,
                        meta_title = ?,
                        meta_description = ?,
                        meta_keywords = ?,
                        published_at = ?
                    WHERE id = ?
                ");
                
                $stmt->execute([
                    $titleAr,
                    $titleEn ?: null,
                    $titleKu ?: null,
                    $slug,
                    $contentAr,
                    $contentEn ?: null,
                    $contentKu ?: null,
                    $excerptAr ?: null,
                    $excerptEn ?: null,
                    $excerptKu ?: null,
                    $featuredImage ?: null,
                    $status,
                    $metaTitle ?: null,
                    $metaDescription ?: null,
                    $metaKeywords ?: null,
                    $publishedAt,
                    $postId
                ]);
                
                setFlashMessage('Blog post updated successfully', 'success');
                redirect('manage-blog.php');
            } catch (PDOException $e) {
                $errors[] = 'Error updating blog post: ' . $e->getMessage();
                error_log('Blog post update error: ' . $e->getMessage());
            }
        }
    }
}

include 'includes/header.php';
?>

<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="page-title mb-0">
                <i class="bi bi-pencil me-2"></i>Edit Blog Post
            </h1>
            <p class="text-muted">Update blog post information</p>
        </div>
        <a href="manage-blog.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Back to Blog
        </a>
    </div>

    <?php if ($flash = getFlashMessage()): ?>
        <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>
            <?php echo htmlspecialchars($flash['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <strong>Please fix the following errors:</strong>
            <ul class="mb-0 mt-2">
                <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card modern-card">
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="row mb-3">
                    <div class="col-lg-4">
                        <label for="title_ar" class="form-label">Title (Arabic) *</label>
                        <input type="text" class="form-control" id="title_ar" name="title_ar" 
                               required value="<?php echo htmlspecialchars($post['title_ar'] ?? ''); ?>"
                               placeholder="عنوان المقال">
                    </div>
                    <div class="col-lg-4">
                        <label for="title_en" class="form-label">Title (English)</label>
                        <input type="text" class="form-control" id="title_en" name="title_en" 
                               value="<?php echo htmlspecialchars($post['title_en'] ?? ''); ?>"
                               placeholder="Post Title">
                    </div>
                    <div class="col-lg-4">
                        <label for="title_ku" class="form-label">Title (Kurdish)</label>
                        <input type="text" class="form-control" id="title_ku" name="title_ku" 
                               value="<?php echo htmlspecialchars($post['title_ku'] ?? ''); ?>"
                               placeholder="ناونیشانی وتار">
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-lg-4">
                        <label for="excerpt_ar" class="form-label">Excerpt (Arabic)</label>
                        <textarea class="form-control" id="excerpt_ar" name="excerpt_ar" rows="2"
                                  placeholder="ملخص قصير للمقال"><?php echo htmlspecialchars($post['excerpt_ar'] ?? ''); ?></textarea>
                        <small class="form-text text-muted">Short summary of the post</small>
                    </div>
                    <div class="col-lg-4">
                        <label for="excerpt_en" class="form-label">Excerpt (English)</label>
                        <textarea class="form-control" id="excerpt_en" name="excerpt_en" rows="2"
                                  placeholder="Short post summary"><?php echo htmlspecialchars($post['excerpt_en'] ?? ''); ?></textarea>
                    </div>
                    <div class="col-lg-4">
                        <label for="excerpt_ku" class="form-label">Excerpt (Kurdish)</label>
                        <textarea class="form-control" id="excerpt_ku" name="excerpt_ku" rows="2"
                                  placeholder="پوخته‌یه‌کی كورت بۆ وتارەکە"><?php echo htmlspecialchars($post['excerpt_ku'] ?? ''); ?></textarea>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-lg-4">
                        <label for="content_ar" class="form-label">Content (Arabic) *</label>
                        <textarea class="form-control" id="content_ar" name="content_ar" rows="10" required
                                  placeholder="محتوى المقال"><?php echo htmlspecialchars($post['content_ar'] ?? ''); ?></textarea>
                    </div>
                    <div class="col-lg-4">
                        <label for="content_en" class="form-label">Content (English)</label>
                        <textarea class="form-control" id="content_en" name="content_en" rows="10"
                                  placeholder="Post content"><?php echo htmlspecialchars($post['content_en'] ?? ''); ?></textarea>
                    </div>
                    <div class="col-lg-4">
                        <label for="content_ku" class="form-label">Content (Kurdish)</label>
                        <textarea class="form-control" id="content_ku" name="content_ku" rows="10"
                                  placeholder="ناوه‌ڕۆكی وتار"><?php echo htmlspecialchars($post['content_ku'] ?? ''); ?></textarea>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="featured_image_upload" class="form-label">Featured Image</label>
                    
                    <?php if (!empty($post['featured_image'])): 
                        $currentImgSrc = $post['featured_image'];
                        if (!preg_match('/^https?:\/\//', $currentImgSrc)) {
                            $currentImgSrc = '/' . ltrim($currentImgSrc, '/');
                        }
                    ?>
                    <div class="mb-2">
                        <label class="form-label small">Current Image:</label>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <img src="<?php echo htmlspecialchars($currentImgSrc); ?>" 
                                 alt="Current" class="img-thumbnail" style="max-width: 200px; max-height: 150px;"
                                 onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'200\' height=\'150\'%3E%3Crect fill=\'%23ddd\' width=\'200\' height=\'150\'/%3E%3Ctext fill=\'%23999\' font-family=\'sans-serif\' font-size=\'14\' x=\'50%25\' y=\'50%25\' text-anchor=\'middle\'%3EImage%3C/text%3E%3C/svg%3E'">
                            <div>
                                <small class="d-block text-muted">Current featured image</small>
                                <small class="d-block text-muted text-truncate" style="max-width: 200px;"><?php echo htmlspecialchars(basename($post['featured_image'])); ?></small>
                            </div>
                        </div>
                        <small class="text-muted">Upload a new file to replace this image</small>
                    </div>
                    <?php endif; ?>
                    
                    <input type="file" class="form-control" id="featured_image_upload" name="featured_image_upload" 
                           accept="image/jpeg,image/jpg,image/png,image/gif,image/webp"
                           onchange="previewFeaturedImage(this)">
                    <small class="form-text text-muted">Upload new featured image (Max 5MB - JPEG, PNG, GIF, or WebP)</small>
                    <div id="featured-image-preview" class="mt-2" style="display: none;">
                        <img src="" alt="Preview" class="img-thumbnail" style="max-width: 300px; max-height: 200px;">
                        <p class="text-success small mt-1">✓ Image ready to upload</p>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="status" class="form-label">Status *</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="draft" <?php echo ($post['status'] === 'draft') ? 'selected' : ''; ?>>Draft</option>
                            <option value="published" <?php echo ($post['status'] === 'published') ? 'selected' : ''; ?>>Published</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Views</label>
                        <input type="text" class="form-control" value="<?php echo number_format($post['views'] ?? 0); ?>" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Created</label>
                        <input type="text" class="form-control" value="<?php echo formatDate($post['created_at'] ?? '', 'M j, Y'); ?>" readonly>
                    </div>
                </div>
                
                <hr>
                
                <h5 class="mb-3">SEO Settings (Optional)</h5>
                <div class="mb-3">
                    <label for="meta_title" class="form-label">Meta Title</label>
                    <input type="text" class="form-control" id="meta_title" name="meta_title" 
                           value="<?php echo htmlspecialchars($post['meta_title'] ?? ''); ?>"
                           placeholder="SEO title">
                    <small class="form-text text-muted">Title for search engines (max 60 characters)</small>
                </div>
                
                <div class="mb-3">
                    <label for="meta_description" class="form-label">Meta Description</label>
                    <textarea class="form-control" id="meta_description" name="meta_description" rows="2"
                              placeholder="SEO description"><?php echo htmlspecialchars($post['meta_description'] ?? ''); ?></textarea>
                    <small class="form-text text-muted">Description for search engines (max 160 characters)</small>
                </div>
                
                <div class="mb-3">
                    <label for="meta_keywords" class="form-label">Meta Keywords</label>
                    <input type="text" class="form-control" id="meta_keywords" name="meta_keywords" 
                           value="<?php echo htmlspecialchars($post['meta_keywords'] ?? ''); ?>"
                           placeholder="keyword1, keyword2, keyword3">
                    <small class="form-text text-muted">Comma-separated keywords</small>
                </div>
                
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-2"></i>Update Post
                    </button>
                    <a href="manage-blog.php" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Auto-generate excerpt from content if empty
const contentAr = document.getElementById('content_ar');
if (contentAr) {
    contentAr.addEventListener('blur', function() {
        const excerptAr = document.getElementById('excerpt_ar');
        if (excerptAr && !excerptAr.value && this.value) {
            excerptAr.value = this.value.substring(0, 150) + (this.value.length > 150 ? '...' : '');
        }
    });
}

const contentEn = document.getElementById('content_en');
if (contentEn) {
    contentEn.addEventListener('blur', function() {
        const excerptEn = document.getElementById('excerpt_en');
        if (excerptEn && !excerptEn.value && this.value) {
            excerptEn.value = this.value.substring(0, 150) + (this.value.length > 150 ? '...' : '');
        }
    });
}

const contentKu = document.getElementById('content_ku');
if (contentKu) {
    contentKu.addEventListener('blur', function() {
        const excerptKu = document.getElementById('excerpt_ku');
        if (excerptKu && !excerptKu.value && this.value) {
            excerptKu.value = this.value.substring(0, 150) + (this.value.length > 150 ? '...' : '');
        }
    });
}

// Preview featured image upload
function previewFeaturedImage(input) {
    const previewDiv = document.getElementById('featured-image-preview');
    const previewImg = previewDiv.querySelector('img');
    
    if (input.files && input.files[0]) {
        // Check file size (5MB)
        if (input.files[0].size > 5 * 1024 * 1024) {
            alert('File size exceeds 5MB limit. Please choose a smaller file.');
            input.value = '';
            previewDiv.style.display = 'none';
            return;
        }
        
        // Check file type
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        if (!allowedTypes.includes(input.files[0].type)) {
            alert('Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed.');
            input.value = '';
            previewDiv.style.display = 'none';
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            previewDiv.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    } else {
        previewDiv.style.display = 'none';
    }
}
</script>

<?php include 'includes/footer.php'; ?>

