<?php
require_once 'includes/auth_check.php';
require_once 'config/db.php';
require_once '../includes/site_content.php';
require_once '../includes/asset_helpers.php';
ensure_site_content_tables($db);

$currentPage = 'content';
$pageTitle = 'Content Management';

$success = '';
$error = '';

$about = $db->query("SELECT * FROM site_about_content WHERE status = 'Active' ORDER BY id DESC LIMIT 1")->fetch();
$instagramPosts = $db->query("SELECT * FROM site_instagram_posts WHERE status = 'Active' ORDER BY sort_order, id")->fetchAll();
$pressItems = $db->query("SELECT * FROM site_press_items WHERE status = 'Active' ORDER BY sort_order, id")->fetchAll();
$brands = get_site_brands($db);
$moments = get_site_moments($db);

$editAbout = null;
$editPress = null;
$editInstagram = null;
$editBrand = null;
$editMoment = null;

if (isset($_GET['edit_press_id'])) {
    $stmt = $db->prepare("SELECT * FROM site_press_items WHERE id = ?");
    $stmt->execute([intval($_GET['edit_press_id'])]);
    $editPress = $stmt->fetch();
}

if (isset($_GET['edit_instagram_id'])) {
    $stmt = $db->prepare("SELECT * FROM site_instagram_posts WHERE id = ?");
    $stmt->execute([intval($_GET['edit_instagram_id'])]);
    $editInstagram = $stmt->fetch();
}

if (isset($_GET['edit_brand_id'])) {
    $stmt = $db->prepare("SELECT * FROM site_brands WHERE id = ?");
    $stmt->execute([intval($_GET['edit_brand_id'])]);
    $editBrand = $stmt->fetch();
}

if (isset($_GET['edit_moment_id'])) {
    $stmt = $db->prepare("SELECT * FROM site_moments WHERE id = ?");
    $stmt->execute([intval($_GET['edit_moment_id'])]);
    $editMoment = $stmt->fetch();
}

if (isset($_GET['delete_press_id'])) {
    $db->prepare("DELETE FROM site_press_items WHERE id = ?")->execute([intval($_GET['delete_press_id'])]);
    header('Location: content.php?msg=press_deleted');
    exit;
}

if (isset($_GET['delete_instagram_id'])) {
    $db->prepare("DELETE FROM site_instagram_posts WHERE id = ?")->execute([intval($_GET['delete_instagram_id'])]);
    header('Location: content.php?msg=instagram_deleted');
    exit;
}

if (isset($_GET['delete_brand_id'])) {
    $db->prepare("DELETE FROM site_brands WHERE id = ?")->execute([intval($_GET['delete_brand_id'])]);
    header('Location: content.php?msg=brand_deleted');
    exit;
}

if (isset($_GET['delete_moment_id'])) {
    $db->prepare("DELETE FROM site_moments WHERE id = ?")->execute([intval($_GET['delete_moment_id'])]);
    header('Location: content.php?msg=moment_deleted');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_about') {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $cta_text = trim($_POST['cta_text'] ?? '');
        $cta_link = trim($_POST['cta_link'] ?? '');
        $image = $about['image'] ?? '';

        $upload = store_uploaded_asset($_FILES['image_file'] ?? [], 'about');
        if ($upload['ok']) {
            $image = $upload['path'];
        }

        if ($title === '' || $description === '' || $image === '') {
            $error = 'Please complete the title, description, and upload an about image.';
        } else {
            if ($about) {
                $stmt = $db->prepare("UPDATE site_about_content SET title = ?, description = ?, image = ?, cta_text = ?, cta_link = ?, status = 'Active' WHERE id = ?");
                $stmt->execute([$title, $description, $image, $cta_text, $cta_link, $about['id']]);
            } else {
                $stmt = $db->prepare("INSERT INTO site_about_content (title, description, image, cta_text, cta_link) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$title, $description, $image, $cta_text, $cta_link]);
            }
            $success = 'About content updated successfully.';
            $about = $db->query("SELECT * FROM site_about_content WHERE status = 'Active' ORDER BY id DESC LIMIT 1")->fetch();
        }
    }

    if ($action === 'save_press') {
        $badge = trim($_POST['badge'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $link = trim($_POST['link'] ?? '#');
        $sort_order = intval($_POST['sort_order'] ?? 0);
        $editId = intval($_POST['edit_id'] ?? 0);
        $image = $editPress['image'] ?? '';

        $upload = store_uploaded_asset($_FILES['image_file'] ?? [], 'press');
        if ($upload['ok']) {
            $image = $upload['path'];
        }

        if ($badge === '' || $description === '' || $image === '') {
            $error = 'Please complete the badge, description, and upload a press image.';
        } else {
            if ($editId > 0) {
                $stmt = $db->prepare("UPDATE site_press_items SET badge = ?, description = ?, image = ?, link = ?, sort_order = ?, status = 'Active' WHERE id = ?");
                $stmt->execute([$badge, $description, $image, $link, $sort_order, $editId]);
                $success = 'Press item updated successfully.';
            } else {
                $stmt = $db->prepare("INSERT INTO site_press_items (badge, description, image, link, sort_order) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$badge, $description, $image, $link, $sort_order]);
                $success = 'Press item added successfully.';
            }
            $pressItems = $db->query("SELECT * FROM site_press_items WHERE status = 'Active' ORDER BY sort_order, id")->fetchAll();
            $editPress = null;
        }
    }

    if ($action === 'save_instagram') {
        $caption = trim($_POST['caption'] ?? '');
        $link = trim($_POST['link'] ?? '#');
        $sort_order = intval($_POST['sort_order'] ?? 0);
        $editId = intval($_POST['edit_id'] ?? 0);
        $image = $editInstagram['image'] ?? '';

        $upload = store_uploaded_asset($_FILES['image_file'] ?? [], 'instagram');
        if ($upload['ok']) {
            $image = $upload['path'];
        }

        if ($image === '' || $caption === '') {
            $error = 'Please complete the caption and upload an Instagram image.';
        } else {
            if ($editId > 0) {
                $stmt = $db->prepare("UPDATE site_instagram_posts SET image = ?, caption = ?, link = ?, sort_order = ?, status = 'Active' WHERE id = ?");
                $stmt->execute([$image, $caption, $link, $sort_order, $editId]);
                $success = 'Instagram post updated successfully.';
            } else {
                $stmt = $db->prepare("INSERT INTO site_instagram_posts (image, caption, link, sort_order) VALUES (?, ?, ?, ?)");
                $stmt->execute([$image, $caption, $link, $sort_order]);
                $success = 'Instagram post added successfully.';
            }
            $instagramPosts = $db->query("SELECT * FROM site_instagram_posts WHERE status = 'Active' ORDER BY sort_order, id")->fetchAll();
            $editInstagram = null;
        }
    }

    if ($action === 'save_brand') {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $view_text = trim($_POST['view_text'] ?? 'View Collection');
        $view_link = trim($_POST['view_link'] ?? 'watches.php');
        $learn_text = trim($_POST['learn_text'] ?? 'Learn More');
        $learn_link = trim($_POST['learn_link'] ?? 'learn-more.php');
        $sort_order = intval($_POST['sort_order'] ?? 0);
        $editId = intval($_POST['edit_id'] ?? 0);

        $background = $editBrand['background_image'] ?? '';
        $logo = $editBrand['logo_image'] ?? '';

        $backgroundUpload = store_uploaded_asset($_FILES['background_file'] ?? [], 'brand_bg');
        if ($backgroundUpload['ok']) {
            $background = $backgroundUpload['path'];
        }

        $logoUpload = store_uploaded_asset($_FILES['logo_file'] ?? [], 'brand_logo');
        if ($logoUpload['ok']) {
            $logo = $logoUpload['path'];
        }

        if ($title === '' || $description === '' || $background === '' || $logo === '') {
            $error = 'Please provide a title, description, and both brand images.';
        } else {
            if ($editId > 0) {
                $stmt = $db->prepare("UPDATE site_brands SET title = ?, description = ?, background_image = ?, logo_image = ?, view_text = ?, view_link = ?, learn_text = ?, learn_link = ?, sort_order = ?, status = 'Active' WHERE id = ?");
                $stmt->execute([$title, $description, $background, $logo, $view_text, $view_link, $learn_text, $learn_link, $sort_order, $editId]);
                $success = 'Brand updated successfully.';
            } else {
                $stmt = $db->prepare("INSERT INTO site_brands (title, description, background_image, logo_image, view_text, view_link, learn_text, learn_link, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$title, $description, $background, $logo, $view_text, $view_link, $learn_text, $learn_link, $sort_order]);
                $success = 'Brand added successfully.';
            }
            $brands = get_site_brands($db);
            $editBrand = null;
        }
    }

    if ($action === 'save_moment') {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $link = trim($_POST['link'] ?? 'learn-more.php');
        $sort_order = intval($_POST['sort_order'] ?? 0);
        $editId = intval($_POST['edit_id'] ?? 0);
        $image = $editMoment['image'] ?? '';

        $upload = store_uploaded_asset($_FILES['image_file'] ?? [], 'moment');
        if ($upload['ok']) {
            $image = $upload['path'];
        }

        if ($title === '' || $description === '' || $image === '') {
            $error = 'Please complete the title, description, and upload a moment image.';
        } else {
            if ($editId > 0) {
                $stmt = $db->prepare("UPDATE site_moments SET title = ?, description = ?, image = ?, link = ?, sort_order = ?, status = 'Active' WHERE id = ?");
                $stmt->execute([$title, $description, $image, $link, $sort_order, $editId]);
                $success = 'Moment updated successfully.';
            } else {
                $stmt = $db->prepare("INSERT INTO site_moments (title, description, image, link, sort_order) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$title, $description, $image, $link, $sort_order]);
                $success = 'Moment added successfully.';
            }
            $moments = get_site_moments($db);
            $editMoment = null;
        }
    }
}

if (!empty($about)) {
    $editAbout = $about;
}

?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?> | Vintage Dial Admin</title>
  <link rel="icon" type="image/png" href="../images/footer.jpeg">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="admin.css">
  <style>
    .content-grid { display:grid; gap:24px; grid-template-columns:repeat(auto-fit, minmax(320px, 1fr)); }
    .content-card { background:#fff; border:1px solid rgba(0,0,0,0.06); border-radius:18px; padding:20px; box-shadow:0 10px 30px rgba(0,0,0,0.05); }
    .content-card h3 { margin-bottom:12px; font-size:20px; }
    .meta { color:#6b7280; font-size:13px; margin-bottom:10px; }
    .content-list { display:grid; gap:12px; }
    .content-row { padding:12px; border:1px solid #e5e7eb; border-radius:12px; background:#fafafa; }
    .content-row small { display:block; color:#6b7280; margin-top:4px; }
    .tiny-actions { display:flex; gap:8px; margin-top:10px; flex-wrap:wrap; }
    .tiny-actions a, .tiny-actions button { font-size:12px; }
    .field-grid { display:grid; gap:12px; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); }
    textarea { min-height:140px; }
  </style>
</head>
<body>
  <?php include 'includes/sidebar.php'; ?>
  <div class="main">
    <?php include 'includes/topbar.php'; ?>

    <section class="content">
      <div class="content-card" style="margin-bottom:24px;">
        <h2 style="margin-bottom:8px;">Content Management</h2>
        <p class="meta">Update the public-facing About, Press Lounge, and Instagram content directly from the admin panel.</p>

        <?php if ($success): ?>
        <div style="background:#d1fae5; color:#065f46; padding:12px 14px; border-radius:10px; margin-bottom:16px; font-size:13px;">
          <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div style="background:#fee2e2; color:#991b1b; padding:12px 14px; border-radius:10px; margin-bottom:16px; font-size:13px;">
          <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'press_deleted'): ?>
        <div style="background:#dbeafe; color:#1d4ed8; padding:12px 14px; border-radius:10px; margin-bottom:16px; font-size:13px;">
          <i class="fas fa-info-circle"></i> Press item deleted.
        </div>
        <?php endif; ?>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'instagram_deleted'): ?>
        <div style="background:#dbeafe; color:#1d4ed8; padding:12px 14px; border-radius:10px; margin-bottom:16px; font-size:13px;">
          <i class="fas fa-info-circle"></i> Instagram post deleted.
        </div>
        <?php endif; ?>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'brand_deleted'): ?>
        <div style="background:#dbeafe; color:#1d4ed8; padding:12px 14px; border-radius:10px; margin-bottom:16px; font-size:13px;">
          <i class="fas fa-info-circle"></i> Brand deleted.
        </div>
        <?php endif; ?>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'moment_deleted'): ?>
        <div style="background:#dbeafe; color:#1d4ed8; padding:12px 14px; border-radius:10px; margin-bottom:16px; font-size:13px;">
          <i class="fas fa-info-circle"></i> Moment deleted.
        </div>
        <?php endif; ?>
      </div>

      <div class="content-grid">
        <div class="content-card">
          <h3>About Us</h3>
          <p class="meta">Update the hero copy and CTA shown on the About page.</p>
          <form method="POST" action="content.php" enctype="multipart/form-data">
            <input type="hidden" name="action" value="save_about">
            <div class="field-grid">
              <div class="form-group">
                <label>Heading</label>
                <input type="text" name="title" value="<?= htmlspecialchars($editAbout['title'] ?? '') ?>" required>
              </div>
              <div class="form-group">
                <label>About Image Upload</label>
                <input type="file" name="image_file" accept="image/*">
                <small class="text-muted">Leave empty to keep the current image.</small>
              </div>
              <div class="form-group">
                <label>CTA Text</label>
                <input type="text" name="cta_text" value="<?= htmlspecialchars($editAbout['cta_text'] ?? '') ?>">
              </div>
              <div class="form-group">
                <label>CTA Link</label>
                <input type="text" name="cta_link" value="<?= htmlspecialchars($editAbout['cta_link'] ?? '') ?>">
              </div>
            </div>
            <div class="form-group">
              <label>Description</label>
              <textarea name="description" required><?= htmlspecialchars($editAbout['description'] ?? '') ?></textarea>
            </div>
            <?php if (!empty($editAbout['image'])): ?>
            <div class="form-group">
              <label>Current Image</label>
              <img src="<?= htmlspecialchars(asset_url($editAbout['image'])) ?>" alt="Current about image" style="max-width:180px; border-radius:12px; margin-top:8px;">
            </div>
            <?php endif; ?>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save About Content</button>
          </form>
        </div>

        <div class="content-card">
          <h3>Instagram</h3>
          <p class="meta">Add or edit posts that appear on the homepage Instagram gallery.</p>
          <form method="POST" action="content.php" enctype="multipart/form-data">
            <input type="hidden" name="action" value="save_instagram">
            <?php if ($editInstagram): ?>
            <input type="hidden" name="edit_id" value="<?= (int) $editInstagram['id'] ?>">
            <?php endif; ?>
            <div class="field-grid">
              <div class="form-group">
                <label>Image Upload</label>
                <input type="file" name="image_file" accept="image/*">
                <small class="text-muted">Leave empty to keep the current image.</small>
              </div>
              <div class="form-group">
                <label>Sort Order</label>
                <input type="number" name="sort_order" value="<?= htmlspecialchars((string) ($editInstagram['sort_order'] ?? 0)) ?>">
              </div>
              <div class="form-group">
                <label>Link</label>
                <input type="text" name="link" value="<?= htmlspecialchars($editInstagram['link'] ?? '#') ?>">
              </div>
            </div>
            <div class="form-group">
              <label>Caption</label>
              <textarea name="caption" required><?= htmlspecialchars($editInstagram['caption'] ?? '') ?></textarea>
            </div>
            <?php if (!empty($editInstagram['image'])): ?>
            <div class="form-group">
              <label>Current Image</label><br>
              <img src="<?= htmlspecialchars(asset_url($editInstagram['image'])) ?>" alt="Current Instagram image" style="max-width:140px; border-radius:12px; margin-top:8px;">
            </div>
            <?php endif; ?>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?= $editInstagram ? 'Update Instagram Post' : 'Add Instagram Post' ?></button>
            <?php if ($editInstagram): ?>
            <a href="content.php" class="btn btn-outline">Cancel</a>
            <?php endif; ?>
          </form>

          <div class="content-list" style="margin-top:16px;">
            <?php foreach ($instagramPosts as $post): ?>
            <div class="content-row">
              <strong><?= htmlspecialchars($post['caption']) ?></strong>
              <small>Sort order: <?= (int) $post['sort_order'] ?> | Image: <?= htmlspecialchars($post['image']) ?></small>
              <div style="margin-top:8px;">
                <img src="<?= htmlspecialchars(asset_url($post['image'])) ?>" alt="Instagram preview" style="max-width:120px; border-radius:10px;">
              </div>
              <div class="tiny-actions">
                <a href="content.php?edit_instagram_id=<?= (int) $post['id'] ?>" class="btn btn-sm btn-outline"><i class="fas fa-edit"></i> Edit</a>
                <a href="content.php?delete_instagram_id=<?= (int) $post['id'] ?>" class="btn btn-sm btn-outline" onclick="return confirm('Delete this Instagram post?')"><i class="fas fa-trash"></i> Delete</a>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="content-card">
          <h3>Press Lounge</h3>
          <p class="meta">Add or edit press cards shown in the homepage carousel.</p>
          <form method="POST" action="content.php" enctype="multipart/form-data">
            <input type="hidden" name="action" value="save_press">
            <?php if ($editPress): ?>
            <input type="hidden" name="edit_id" value="<?= (int) $editPress['id'] ?>">
            <?php endif; ?>
            <div class="field-grid">
              <div class="form-group">
                <label>Badge / Label</label>
                <input type="text" name="badge" value="<?= htmlspecialchars($editPress['badge'] ?? '') ?>" required>
              </div>
              <div class="form-group">
                <label>Image Upload</label>
                <input type="file" name="image_file" accept="image/*">
                <small class="text-muted">Leave empty to keep the current image.</small>
              </div>
              <div class="form-group">
                <label>Sort Order</label>
                <input type="number" name="sort_order" value="<?= htmlspecialchars((string) ($editPress['sort_order'] ?? 0)) ?>">
              </div>
              <div class="form-group">
                <label>Link</label>
                <input type="text" name="link" value="<?= htmlspecialchars($editPress['link'] ?? '#') ?>">
              </div>
            </div>
            <div class="form-group">
              <label>Description</label>
              <textarea name="description" required><?= htmlspecialchars($editPress['description'] ?? '') ?></textarea>
            </div>
            <?php if (!empty($editPress['image'])): ?>
            <div class="form-group">
              <label>Current Image</label><br>
              <img src="<?= htmlspecialchars(asset_url($editPress['image'])) ?>" alt="Current press image" style="max-width:140px; border-radius:12px; margin-top:8px;">
            </div>
            <?php endif; ?>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?= $editPress ? 'Update Press Item' : 'Add Press Item' ?></button>
            <?php if ($editPress): ?>
            <a href="content.php" class="btn btn-outline">Cancel</a>
            <?php endif; ?>
          </form>

          <div class="content-list" style="margin-top:16px;">
            <?php foreach ($pressItems as $item): ?>
            <div class="content-row">
              <strong><?= htmlspecialchars($item['badge']) ?></strong>
              <p style="margin:8px 0 0;"><?= htmlspecialchars($item['description']) ?></p>
              <small>Sort order: <?= (int) $item['sort_order'] ?> | Image: <?= htmlspecialchars($item['image']) ?></small>
              <div style="margin-top:8px;">
                <img src="<?= htmlspecialchars(asset_url($item['image'])) ?>" alt="Press preview" style="max-width:120px; border-radius:10px;">
              </div>
              <div class="tiny-actions">
                <a href="content.php?edit_press_id=<?= (int) $item['id'] ?>" class="btn btn-sm btn-outline"><i class="fas fa-edit"></i> Edit</a>
                <a href="content.php?delete_press_id=<?= (int) $item['id'] ?>" class="btn btn-sm btn-outline" onclick="return confirm('Delete this press item?')"><i class="fas fa-trash"></i> Delete</a>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="content-card">
          <h3>Moments</h3>
          <p class="meta">Manage the movement cards shown on the homepage.</p>
          <form method="POST" action="content.php" enctype="multipart/form-data">
            <input type="hidden" name="action" value="save_moment">
            <?php if ($editMoment): ?>
            <input type="hidden" name="edit_id" value="<?= (int) $editMoment['id'] ?>">
            <?php endif; ?>
            <div class="field-grid">
              <div class="form-group">
                <label>Title</label>
                <input type="text" name="title" value="<?= htmlspecialchars($editMoment['title'] ?? '') ?>" required>
              </div>
              <div class="form-group">
                <label>Image Upload</label>
                <input type="file" name="image_file" accept="image/*">
                <small class="text-muted">Leave empty to keep the current image.</small>
              </div>
              <div class="form-group">
                <label>Sort Order</label>
                <input type="number" name="sort_order" value="<?= htmlspecialchars((string) ($editMoment['sort_order'] ?? 0)) ?>">
              </div>
              <div class="form-group">
                <label>Link</label>
                <input type="text" name="link" value="<?= htmlspecialchars($editMoment['link'] ?? 'learn-more.php') ?>">
              </div>
            </div>
            <div class="form-group">
              <label>Description</label>
              <textarea name="description" required><?= htmlspecialchars($editMoment['description'] ?? '') ?></textarea>
            </div>
            <?php if (!empty($editMoment['image'])): ?>
            <div class="form-group">
              <label>Current Image</label><br>
              <img src="<?= htmlspecialchars(asset_url($editMoment['image'])) ?>" alt="Current moment preview" style="max-width:160px; border-radius:12px; margin-top:8px;">
            </div>
            <?php endif; ?>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?= $editMoment ? 'Update Moment' : 'Add Moment' ?></button>
            <?php if ($editMoment): ?>
            <a href="content.php" class="btn btn-outline">Cancel</a>
            <?php endif; ?>
          </form>

          <div class="content-list" style="margin-top:16px;">
            <?php foreach ($moments as $moment): ?>
            <div class="content-row">
              <strong><?= htmlspecialchars($moment['title']) ?></strong>
              <p style="margin:8px 0 0;"><?= htmlspecialchars($moment['description']) ?></p>
              <small>Sort order: <?= (int) $moment['sort_order'] ?> | Link: <?= htmlspecialchars($moment['link']) ?></small>
              <div style="margin-top:8px;">
                <img src="<?= htmlspecialchars(asset_url($moment['image'])) ?>" alt="Moment preview" style="max-width:140px; border-radius:10px;">
              </div>
              <div class="tiny-actions">
                <a href="content.php?edit_moment_id=<?= (int) $moment['id'] ?>" class="btn btn-sm btn-outline"><i class="fas fa-edit"></i> Edit</a>
                <a href="content.php?delete_moment_id=<?= (int) $moment['id'] ?>" class="btn btn-sm btn-outline" onclick="return confirm('Delete this moment?')"><i class="fas fa-trash"></i> Delete</a>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="content-card">
          <h3>Our Brands</h3>
          <p class="meta">Manage the brand cards shown on the homepage.</p>
          <form method="POST" action="content.php" enctype="multipart/form-data">
            <input type="hidden" name="action" value="save_brand">
            <?php if ($editBrand): ?>
            <input type="hidden" name="edit_id" value="<?= (int) $editBrand['id'] ?>">
            <?php endif; ?>
            <div class="field-grid">
              <div class="form-group">
                <label>Brand Title</label>
                <input type="text" name="title" value="<?= htmlspecialchars($editBrand['title'] ?? '') ?>" required>
              </div>
              <div class="form-group">
                <label>Background Image</label>
                <input type="file" name="background_file" accept="image/*">
                <small class="text-muted">Leave empty to keep the current image.</small>
              </div>
              <div class="form-group">
                <label>Logo Image</label>
                <input type="file" name="logo_file" accept="image/*">
                <small class="text-muted">Leave empty to keep the current image.</small>
              </div>
              <div class="form-group">
                <label>Sort Order</label>
                <input type="number" name="sort_order" value="<?= htmlspecialchars((string) ($editBrand['sort_order'] ?? 0)) ?>">
              </div>
            </div>
            <div class="form-group">
              <label>Description</label>
              <textarea name="description" required><?= htmlspecialchars($editBrand['description'] ?? '') ?></textarea>
            </div>
            <div class="field-grid">
              <div class="form-group">
                <label>View Button Text</label>
                <input type="text" name="view_text" value="<?= htmlspecialchars($editBrand['view_text'] ?? 'View Collection') ?>">
              </div>
              <div class="form-group">
                <label>View Button Link</label>
                <input type="text" name="view_link" value="<?= htmlspecialchars($editBrand['view_link'] ?? 'watches.php') ?>">
              </div>
              <div class="form-group">
                <label>Learn Button Text</label>
                <input type="text" name="learn_text" value="<?= htmlspecialchars($editBrand['learn_text'] ?? 'Learn More') ?>">
              </div>
              <div class="form-group">
                <label>Learn Button Link</label>
                <input type="text" name="learn_link" value="<?= htmlspecialchars($editBrand['learn_link'] ?? 'learn-more.php') ?>">
              </div>
            </div>
            <?php if (!empty($editBrand)): ?>
            <div class="field-grid">
              <div class="form-group">
                <label>Current Background</label><br>
                <img src="<?= htmlspecialchars(asset_url($editBrand['background_image'])) ?>" alt="Current background preview" style="max-width:160px; border-radius:12px; margin-top:8px;">
              </div>
              <div class="form-group">
                <label>Current Logo</label><br>
                <img src="<?= htmlspecialchars(asset_url($editBrand['logo_image'])) ?>" alt="Current logo preview" style="max-width:160px; border-radius:12px; margin-top:8px;">
              </div>
            </div>
            <?php endif; ?>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?= $editBrand ? 'Update Brand' : 'Add Brand' ?></button>
            <?php if ($editBrand): ?>
            <a href="content.php" class="btn btn-outline">Cancel</a>
            <?php endif; ?>
          </form>

          <div class="content-list" style="margin-top:16px;">
            <?php foreach ($brands as $brand): ?>
            <div class="content-row">
              <strong><?= htmlspecialchars($brand['title']) ?></strong>
              <p style="margin:8px 0 0;"><?= htmlspecialchars($brand['description']) ?></p>
              <div style="margin-top:8px; display:flex; gap:12px; flex-wrap:wrap;">
                <img src="<?= htmlspecialchars(asset_url($brand['background_image'])) ?>" alt="Brand background preview" style="max-width:140px; border-radius:10px;">
                <img src="<?= htmlspecialchars(asset_url($brand['logo_image'])) ?>" alt="Brand logo preview" style="max-width:120px; border-radius:10px;">
              </div>
              <small>Sort order: <?= (int) $brand['sort_order'] ?> | View: <?= htmlspecialchars($brand['view_link']) ?></small>
              <div class="tiny-actions">
                <a href="content.php?edit_brand_id=<?= (int) $brand['id'] ?>" class="btn btn-sm btn-outline"><i class="fas fa-edit"></i> Edit</a>
                <a href="content.php?delete_brand_id=<?= (int) $brand['id'] ?>" class="btn btn-sm btn-outline" onclick="return confirm('Delete this brand?')"><i class="fas fa-trash"></i> Delete</a>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </section>
  </div>
</body>
</html>
