<?php
$pageTitle = "Our Heritage";
include 'includes/header.php';
?>

<style>
  .heritage-page {
    position: relative;
    min-height: 80vh;
    background: url("https://images.unsplash.com/photo-1524592094714-0f0654e20314") no-repeat center/cover;
    font-family: 'Noto Sans', sans-serif;
  }

  /* DARK OVERLAY */
  .heritage-overlay {
    background: rgba(0, 0, 0, 0.65);
    min-height: 80vh;
    padding: 80px 20px;
  }

  /* CENTER WRAPPER */
  .heritage-wrapper {
    max-width: 1100px;
    margin: auto;
    text-align: center;
    color: white;
  }

  .heritage-wrapper h1 {
    font-size: 42px;
    margin-bottom: 10px;
    font-weight: 700;
  }

  .subtitle {
    color: #ddd;
    margin-bottom: 40px;
    font-size: 15px;
  }

  /* GRID */
  .heritage-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 25px;
  }

  /* GLASS CARDS */
  .heritage-card {
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    padding: 30px;
    border-radius: 15px;
    transition: 0.3s;
  }

  .heritage-card:hover {
    transform: translateY(-6px);
    background: rgba(255, 255, 255, 0.15);
  }

  .heritage-card i {
    font-size: 28px;
    color: #c9a227;
    margin-bottom: 15px;
  }

  .heritage-card h3 {
    margin-bottom: 10px;
    color: white;
  }

  .heritage-card p {
    font-size: 14px;
    color: #ddd;
    line-height: 1.6;
  }

  /* RESPONSIVE */
  @media(max-width: 768px) {
    .heritage-grid {
      grid-template-columns: 1fr;
    }

    .heritage-wrapper h1 {
      font-size: 32px;
    }
  }
</style>

<section class="heritage-page">
  <div class="heritage-overlay">
    <div class="heritage-wrapper">
      <h1>Our Heritage</h1>
      <p class="subtitle">
        A legacy of timeless craftsmanship, precision, and luxury watchmaking.
      </p>

      <div class="heritage-grid">
        <div class="heritage-card">
          <i class="fas fa-landmark"></i>
          <h3>Our Beginning</h3>
          <p>We started with a passion for classic horology and timeless design.</p>
        </div>

        <div class="heritage-card">
          <i class="fas fa-gem"></i>
          <h3>Craftsmanship</h3>
          <p>Each watch is crafted with precision, detail, and premium materials.</p>
        </div>

        <div class="heritage-card">
          <i class="fas fa-clock"></i>
          <h3>Timeless Design</h3>
          <p>We merge vintage elegance with modern innovation.</p>
        </div>

        <div class="heritage-card">
          <i class="fas fa-globe"></i>
          <h3>Global Vision</h3>
          <p>Bringing luxury watches to customers worldwide.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<?php include 'includes/footer.php'; ?>
