  <?php
  $footerCategories = [];
  try {
      $footerCategories = $db->query("SELECT DISTINCT name FROM categories WHERE status='Active' ORDER BY name")->fetchAll(DB_FETCH_COLUMN);
  } catch (Exception $e) {
      $footerCategories = [];
  }
  if (empty($footerCategories)) {
      $footerCategories = ['Watches', 'Limited Edition'];
  }
  ?>

  <!-- Footer -->
  <footer>
      <div class="footer-container text-left">
          <!-- Logo -->
          <div class="footer-column branding-footer">
              <a href="index.php" style="text-decoration: none;">
                  <h1 class="logo footer-logo-text" style="color: white;">Vintage Dial</h1>
              </a>

          </div>

          <!-- Collections -->
          <div class="footer-column">
              <h3><a href="watches.php" style="color: white; text-decoration: none;">COLLECTIONS</a></h3>
              <ul>
                  <?php foreach ($footerCategories as $footerCategory): ?>
                  <li><a
                          href="watches.php?category=<?= urlencode($footerCategory) ?>"><?= htmlspecialchars($footerCategory) ?></a>
                  </li>
                  <?php endforeach; ?>
                  <li><a href="watches.php">View All</a></li>
              </ul>
          </div>

          <!-- About -->
          <div class="footer-column">
              <h3><a href="about.php" style="color: white; text-decoration: none;">ABOUT</a></h3>
              <ul>
                  <li><a href="about.php">Press Lounge</a></li>
                  <li><a href="heritage.php">Heritage</a></li>
              </ul>
          </div>

          <!-- Support -->
          <div class="footer-column">
              <h3><a href="contact.php" style="color: white; text-decoration: none;">SUPPORT</a></h3>
              <ul>
                  <li><a href="contact.php">Warranty & Claims</a></li>
                  <li><a href="contact.php">Product Registration</a></li>
                  <li><a href="contact.php">User Manuals</a></li>
                  <li><a href="contact.php">Accessibility</a></li>
                  <li><a href="contact.php">Privacy Policy</a></li>
                  <li><a href="contact.php">Terms & Conditions</a></li>
                  <li><a href="contact.php">Service & Repairs</a></li>
                  <li><a href="contact.php">FAQs</a></li>
              </ul>
          </div>

          <!-- Contact & Newsletter -->
          <div class="footer-column contact-info">
              <h3><a href="contact.php" style="color: white; text-decoration: none;">CONTACT</a></h3>
              <p style="color: #bbb; font-size: 13px;"><i class="fas fa-headset mr-2"></i> <a href="contact.php"
                      style="color: #bbb;">Customer Support Service</a></p>
              <p style="color: #bbb; font-size: 13px;"><i class="fas fa-envelope mr-2"></i> support@vintagedial.com</p>
              <p style="color: #bbb; font-size: 13px;"><i class="fas fa-phone mr-2"></i> +92 (42) 111-VINTAGE</p>

              <!-- Newsletter Subscription -->
              <div class="newsletter mt-3">
                  <form id="newsletterForm" onsubmit="subscribeNewsletter(event)">
                      <input type="email" id="newsletterEmail" placeholder="SUBSCRIBE TO OUR NEWSLETTER" required
                          style="width: 100%; margin-bottom: 8px;">
                      <button type="submit" style="width: 100%;">✈ SUBSCRIBE</button>
                  </form>
                  <div id="newsletterSuccess" style="display:none; color:#c9a227; font-size:12px; margin-top:8px;">
                      <i class="fas fa-check-circle"></i> Thank you! You have successfully subscribed to our newsletter.
                  </div>
              </div>
          </div>
      </div>

      <!-- Bottom -->
      <div class="footer-bottom">
          © <?= date('Y') ?> <a href="#" style="color: #f1efea; text-decoration: none;">Vintage Dial. All rights
              reserved.</a>
      </div>
  </footer>

  <!-- Bootstrap JS Dependencies -->
  <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

  <!-- Global scripts -->
  <script>
function subscribeNewsletter(e) {
    e.preventDefault();
    const email = document.getElementById('newsletterEmail').value;
    if (email) {
        document.getElementById('newsletterForm').reset();
        document.getElementById('newsletterSuccess').style.display = 'block';
        setTimeout(() => {
            document.getElementById('newsletterSuccess').style.display = 'none';
        }, 5000);
    }
}
  </script>
  </body>

  </html>