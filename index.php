<?php
$pageTitle = "Vintage Dial — Where Time Becomes Legacy";
require_once 'includes/site_content.php';
ensure_site_content_tables($db);
$instagramPosts = get_instagram_posts($db);
$pressItems = get_press_items($db);
$brands = get_site_brands($db);
$moments = get_site_moments($db);
include 'includes/header.php';
?>

<!-- Featured Video Section -->
<div class="elementor-element elementor-element-85350d0 e-con-full jsa-video e-flex e-con e-parent e-lazyloaded"
    data-id="85350d0" data-element_type="container"
    data-settings="{&quot;jet_parallax_layout_list&quot;:[],&quot;background_background&quot;:&quot;video&quot;,&quot;background_video_link&quot;:&quot;https:\/\/seikoluxe.com\/2024-Luxe-Site-Video.mp4&quot;,&quot;background_play_on_mobile&quot;:&quot;yes&quot;}">
    <div class="elementor-background-video-container">
        <video class="elementor-background-video-hosted" autoplay="" muted="" playsinline="" loop=""
            src="images/shortclip.mp4" style="width: 1280px; height: 720px;"></video>
    </div>
</div>

<div>
    <section class="seiko-section">
        <h2 class="brand">Vintage Dial – Where Time Becomes Legacy</h2>
        <div class="divider"></div>
        <p style="color: black; max-width: 800px; margin: 0 auto 20px auto;">
            Vintage Dial is a world of timeless craftsmanship, dedicated to excellence and driven by innovation,
            quality,
            and heritage. Explore our premium selection of timepieces.
        </p>
        <a href="learn-more.php" class="btn">LEARN MORE</a>

        <!-- watch section -->
        <section class="brands-wrapper">
            <section class="watch-carousel">
                <h2 class="watch-carousel__title">LATEST WATCHES</h2>

                <div class="watch-carousel__container">
                    <button class="watch-carousel__btn watch-carousel__btn--prev"
                        aria-label="Previous">&#10094;</button>

                    <div class="watch-carousel__track">
                        <?php
              try {
                  $stmt = $db->query("SELECT * FROM products ORDER BY created_at DESC LIMIT 10");
                  $products = $stmt->fetchAll();
                  if (empty($products)) {
                      echo "<p class='text-muted text-center w-100'>No watches found in the catalog.</p>";
                  } else {
                      foreach ($products as $p) {
                          ?>
                        <a href="product-detail.php?id=<?= $p['id'] ?>" class="watch-card">
                            <img src="<?= htmlspecialchars(asset_url($p['image'])) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
                            <h3><?= htmlspecialchars($p['name']) ?></h3>
                            <p>Rs. <?= number_format($p['price']) ?></p>
                        </a>
                        <?php
                      }
                  }
              } catch (Exception $e) {
                  echo "<p class='text-danger'>Error loading products: " . $e->getMessage() . "</p>";
              }
              ?>
                    </div>

                    <button class="watch-carousel__btn watch-carousel__btn--next" aria-label="Next">&#10095;</button>
                </div>
            </section>

            <!-- brand section -->
            <div class="elementor-widget-container mt-5">
                <h2 class="elementor-heading-title elementor-size-default">OUR BRANDS</h2>
                <div class="dividers"></div>
            </div>
        </section>

        <!-- Brands Grid -->
        <div class="brands-grid">
            <?php if (!empty($brands)): foreach ($brands as $brand): ?>
            <div class="brand-box" style="background-image: url(<?= htmlspecialchars(asset_url($brand['background_image'])) ?>);">
                <div class="brand-logo">
                    <img src="<?= htmlspecialchars(asset_url($brand['logo_image'])) ?>" alt="<?= htmlspecialchars($brand['title']) ?>">
                </div>
                <p class="brand-desc"><?= htmlspecialchars($brand['description']) ?></p>
                <div class="brand-buttons">
                    <a href="<?= htmlspecialchars($brand['view_link'] ?: 'watches.php') ?>" class="btn"><?= htmlspecialchars($brand['view_text'] ?: 'View Collection') ?></a>
                    <a href="<?= htmlspecialchars($brand['learn_link'] ?: 'learn-more.php') ?>" class="btn secondary"><?= htmlspecialchars($brand['learn_text'] ?: 'Learn More') ?></a>
                </div>
            </div>
            <?php endforeach; else: ?>
            <div class="brand-box" style="background-image: url(./images/s1.jpg);">
                <div class="brand-logo">
                    <img src="./images/p1.png" alt="Prospex">
                </div>
                <p class="brand-desc">Professional specifications for the ultimate in adventure.</p>
                <div class="brand-buttons">
                    <a href="watches.php?category=Watches" class="btn">View Collection</a>
                    <a href="learn-more.php" class="btn secondary">Learn More</a>
                </div>
            </div>
            <div class="brand-box" style="background-image: url(./images/s2.jpg);">
                <div class="brand-logo">
                    <img src="https://seikoluxe.com/wp-content/uploads/2024/05/presage.svg" alt="Presage">
                </div>
                <p class="brand-desc">Fine mechanical watchmaking from Japan.</p>
                <div class="brand-buttons">
                    <a href="watches.php?category=Watches" class="btn">View Collection</a>
                    <a href="learn-more.php" class="btn secondary">Learn More</a>
                </div>
            </div>
            <div class="brand-box" style="background-image: url(./images/s3.png);">
                <div class="brand-logo">
                    <img src="https://seikoluxe.com/wp-content/uploads/2024/04/White_KS_Logo-2048x325.webp" alt="Astron">
                </div>
                <p class="brand-desc">VANAC</p>
                <div class="brand-buttons">
                    <a href="watches.php?category=Limited+Edition" class="btn">View Collection</a>
                    <a href="learn-more.php" class="btn secondary">Learn More</a>
                </div>
            </div>
            <div class="brand-box" style="background-image: url(./images/s4.png);">
                <div class="brand-logo">
                    <img src="https://seikoluxe.com/wp-content/uploads/2024/04/white-Astron.png" alt="King Seiko">
                </div>
                <p class="brand-desc">The world’s first GPS Solar watch.</p>
                <div class="brand-buttons">
                    <a href="watches.php?category=Limited+Edition" class="btn">View Collection</a>
                    <a href="learn-more.php" class="btn secondary">Learn More</a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </section>
</div>

<!-- Movement Section -->
<section class="movement-section">
    <h2 class="section-title">MOVEMENT</h2>
    <div class="movement-grid">
        <?php if (!empty($moments)): foreach ($moments as $moment): ?>
        <div class="movement-card">
            <h3><?= htmlspecialchars($moment['title']) ?></h3>
            <a href="<?= htmlspecialchars($moment['link'] ?: 'learn-more.php') ?>">
                <img src="<?= htmlspecialchars(asset_url($moment['image'])) ?>" alt="<?= htmlspecialchars($moment['title']) ?>">
            </a>
        </div>
        <?php endforeach; else: ?>
        <div class="movement-card">
            <h3>MECHANICAL CALIBER 6L37</h3>
            <a href="learn-more.php">
                <img src="<?= htmlspecialchars(asset_url('./images/m1.jpg')) ?>" alt="Mechanical Caliber 6L37">
            </a>
        </div>
        <div class="movement-card">
            <h3>MECHANICAL CALIBER 6L37</h3>
            <a href="learn-more.php">
                <img src="<?= htmlspecialchars(asset_url('./images/m2.jpg')) ?>" alt="Mechanical Caliber 6L37">
            </a>
        </div>
        <div class="movement-card">
            <h3>MECHANICAL CALIBER 6L37 MECHANICAL CALIBER</h3>
            <a href="learn-more.php">
                <img src="<?= htmlspecialchars(asset_url('./images/m3.jpg')) ?>" alt="Mechanical Caliber 6L37">
            </a>
        </div>
        <div class="movement-card">
            <h3>MECHANICAL CALIBER 6L37</h3>
            <a href="learn-more.php">
                <img src="<?= htmlspecialchars(asset_url('./images/m4.jpg')) ?>" alt="Mechanical Caliber 6L37">
            </a>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- Instagram Social Section -->
<section class="instagram-section">
    <h2>FOLLOW US ON INSTAGRAM</h2>
    <div class="instagram-gallery">
        <?php if (empty($instagramPosts)): ?>
        <div class="w-100 text-center text-muted">Instagram content will appear here once it is added in the admin
            panel.</div>
        <?php else: ?>
        <?php foreach ($instagramPosts as $post): ?>
        <a href="<?= htmlspecialchars($post['link'] ?: 'https://instagram.com') ?>" target="_blank" class="insta-item">
            <img src="<?= htmlspecialchars(asset_url($post['image'])) ?>" alt="Instagram post">
            <div class="insta-overlay">
                <p><?= htmlspecialchars($post['caption']) ?></p>
            </div>
        </a>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<!-- Press Lounge Section -->
<section class="press-area">
    <div class="press-container">
        <div class="press-heading">
            <h2>PRESS LOUNGE</h2>
        </div>

        <div id="pressCarousel" class="carousel slide" data-ride="carousel">
            <div class="carousel-inner">
                <?php
          $pressSlides = array_chunk($pressItems, 3);
          if (empty($pressSlides)) {
              echo '<div class="carousel-item active"><div class="row"><div class="col-md-12"><div class="press-card"><div class="press-card-body"><p>Press Lounge content will appear here once it is added in the admin panel.</p></div></div></div></div></div>';
          } else {
              foreach ($pressSlides as $slideIndex => $slideItems) {
                  $isActive = $slideIndex === 0 ? ' active' : '';
                  echo '<div class="carousel-item' . $isActive . '"><div class="row">';
                  foreach ($slideItems as $item) {
                      echo '<div class="col-md-4"><div class="press-card">';
                      if (!empty($item['link'])) {
                          echo '<a href="' . htmlspecialchars($item['link']) . '" target="_blank">';
                      }
                      echo '<img src="' . htmlspecialchars(asset_url($item['image'])) . '" alt="' . htmlspecialchars($item['badge']) . '">';
                      if (!empty($item['link'])) {
                          echo '</a>';
                      }
                      echo '<div class="press-card-body">';
                      echo '<span class="press-label">' . htmlspecialchars(strtoupper($item['badge'])) . '</span>';
                      echo '<p>' . htmlspecialchars($item['description']) . '</p>';
                      echo '</div></div></div>';
                  }
                  echo '</div></div>';
              }
          }
          ?>
            </div>

            <a class="carousel-control-prev" href="#pressCarousel" role="button" data-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            </a>
            <a class="carousel-control-next" href="#pressCarousel" role="button" data-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
            </a>
        </div>
    </div>
</section>

<!-- Slider controls script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const track = document.querySelector('.watch-carousel__track');
    const prevBtn = document.querySelector('.watch-carousel__btn--prev');
    const nextBtn = document.querySelector('.watch-carousel__btn--next');

    if (track && prevBtn && nextBtn) {
        let scrollAmount = 0;
        const cardWidth = 240; // Approx card width + margin

        nextBtn.addEventListener('click', function() {
            track.scrollBy({
                left: cardWidth,
                behavior: 'smooth'
            });
        });

        prevBtn.addEventListener('click', function() {
            track.scrollBy({
                left: -cardWidth,
                behavior: 'smooth'
            });
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>