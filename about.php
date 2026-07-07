<?php
$pageTitle = "About Us";
require_once 'includes/site_content.php';
ensure_site_content_tables($db);
$about = get_about_content($db);
include 'includes/header.php';
?>

  <!-- About Us Section -->
  <section class="about-us py-5">
    <div class="container">
      <div class="row">
        <!-- Left Column: Image -->
        <div class="col-md-6">
          <img src="<?= htmlspecialchars(asset_url($about['image'] ?: './images/footer.jpeg')) ?>" alt="<?= htmlspecialchars($about['title'] ?: 'About Vintage Dial') ?>" class="img-fluid rounded shadow" style="width: 100%; max-height: 450px; object-fit: cover;">
        </div>

        <!-- Right Column: Text -->
        <div class="col-md-6 d-flex flex-column justify-content-center mt-4 mt-md-0">
          <h2 class="mb-4"><?= htmlspecialchars($about['title'] ?: 'About Vintage Dial') ?></h2>
          <p><?= nl2br(htmlspecialchars($about['description'] ?? 'At Vintage Dial, we believe timepieces are more than just instruments to tell time — they are stories of heritage, craftsmanship, and timeless style.')) ?></p>
          <?php if (!empty($about['cta_text'])): ?>
          <a href="<?= htmlspecialchars($about['cta_link'] ?: 'watches.php') ?>" class="btn btn-dark mt-3 px-4 py-2" style="border-radius: 20px; font-weight:600; width:fit-content; background: #111;"><?= htmlspecialchars($about['cta_text']) ?></a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </section>

<?php include 'includes/footer.php'; ?>
