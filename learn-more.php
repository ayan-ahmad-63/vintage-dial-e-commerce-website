<?php
$pageTitle = "Discover Vintage Dial";
include 'includes/header.php';
?>

<style>
  .learn-more {
    position: relative;
    padding: 100px 10%;
    background: url("https://images.unsplash.com/photo-1524592094714-0f0654e20314") no-repeat center/cover;
    color: white;
    text-align: center;
    min-height: 80vh;
  }

  .learn-more .overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.75);
  }

  .learn-more .content {
    position: relative;
    z-index: 2;
  }

  .learn-more h1 {
    font-size: 48px;
    letter-spacing: 2px;
    margin-bottom: 10px;
    font-weight: 700;
  }

  .subtitle {
    font-size: 18px;
    color: #c0a16b;
    margin-bottom: 50px;
  }

  /* GRID */
  .grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 25px;
  }

  /* CARDS */
  .learn-more-card {
    background: rgba(255, 255, 255, 0.08);
    padding: 25px;
    border-radius: 15px;
    backdrop-filter: blur(8px);
    transition: 0.4s ease;
    border: 1px solid rgba(255, 255, 255, 0.1);
    color: white;
  }

  .learn-more-card:hover {
    transform: translateY(-10px);
    background: rgba(255, 255, 255, 0.15);
  }

  .learn-more-card h2 {
    margin-bottom: 15px;
    color: #c0a16b;
    font-weight: 600;
    font-size: 22px;
  }

  .learn-more-card p {
    font-size: 14px;
    line-height: 1.6;
    color: #ddd;
  }

  /* BUTTON */
  .btn-explore {
    display: inline-block;
    margin-top: 50px;
    padding: 14px 30px;
    background: #c0a16b;
    color: black;
    text-decoration: none;
    border-radius: 30px;
    font-weight: bold;
    transition: 0.3s;
    border: none;
  }

  .btn-explore:hover {
    background: white;
    color: black;
    text-decoration: none;
  }
</style>

<section class="learn-more">
  <div class="overlay"></div>

  <div class="content">
    <h1>Discover Vintage Dial</h1>
    <p class="subtitle">Where Time Meets Elegance</p>

    <div class="grid">
      <div class="learn-more-card">
        <h2>Our Story</h2>
        <p>
          Vintage Dial brings together heritage and modern craftsmanship,
          offering timepieces that reflect elegance, precision, and timeless
          beauty.
        </p>
      </div>

      <div class="learn-more-card">
        <h2>Craftsmanship</h2>
        <p>
          Every detail is carefully designed, from refined dials to premium
          straps, ensuring each watch delivers both durability and
          sophistication.
        </p>
      </div>

      <div class="learn-more-card">
        <h2>Our Promise</h2>
        <p>
          We are committed to quality, innovation, and customer
          satisfaction, delivering watches that stand the test of time.
        </p>
      </div>

      <div class="learn-more-card">
        <h2>Our Vision</h2>
        <p>
          To redefine timeless fashion by offering elegant and reliable
          watches for every generation.
        </p>
      </div>
    </div>

    <a href="watches.php" class="btn-explore">Explore Collection</a>
  </div>
</section>

<?php include 'includes/footer.php'; ?>
