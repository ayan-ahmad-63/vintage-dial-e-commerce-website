<?php
$pageTitle = "Contact Us";
include 'includes/header.php';

$successMsg = '';
$errorMsg = '';
$oldValues = [
    'name' => '',
    'email' => '',
    'subject' => '',
    'message' => ''
];
$replyMessages = [];
$latestMessage = null;
$latestMessageId = null;
$lookupEmail = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oldValues['name'] = trim($_POST['name'] ?? '');
    $oldValues['email'] = trim($_POST['email'] ?? '');
    $oldValues['subject'] = trim($_POST['subject'] ?? '');
    $oldValues['message'] = trim($_POST['message'] ?? '');
    $lookupEmail = $oldValues['email'];

    if ($oldValues['name'] === '' || $oldValues['email'] === '' || $oldValues['message'] === '') {
        $errorMsg = 'Please complete your name, email, and message before sending.';
    } elseif (!filter_var($oldValues['email'], FILTER_VALIDATE_EMAIL)) {
        $errorMsg = 'Please enter a valid email address.';
    } else {
        try {
            $db->query("CREATE TABLE IF NOT EXISTS contact_messages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(100) NOT NULL,
                subject VARCHAR(255) NULL,
                message TEXT NOT NULL,
                admin_reply TEXT NULL,
                status VARCHAR(20) DEFAULT 'New',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
            $db->query("ALTER TABLE contact_messages ADD COLUMN IF NOT EXISTS admin_reply TEXT NULL;");
            $db->query("ALTER TABLE contact_messages ADD COLUMN IF NOT EXISTS status VARCHAR(20) DEFAULT 'New';");

            $stmt = $db->prepare("INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)");
            $stmt->execute([$oldValues['name'], $oldValues['email'], $oldValues['subject'], $oldValues['message']]);
            $insertedId = $db->lastInsertId();
            $latestMessageId = $insertedId;
            $successMsg = "Thank you, " . htmlspecialchars($oldValues['name']) . "! Your message has been saved and will be reviewed shortly.";

            $fetchNew = $db->prepare("SELECT id, subject, message, admin_reply, status, created_at FROM contact_messages WHERE id = ?");
            $fetchNew->execute([$insertedId]);
            $latestMessage = $fetchNew->fetch();

            $oldValues = ['name' => '', 'email' => '', 'subject' => '', 'message' => ''];
        } catch (Exception $e) {
            $errorMsg = 'Unable to save your message right now. Please try again later.';
        }
    }
}

if ($lookupEmail === '' && isset($_GET['email'])) {
    $lookupEmail = trim($_GET['email']);
}

if ($lookupEmail !== '' && filter_var($lookupEmail, FILTER_VALIDATE_EMAIL)) {
    try {
        $stmt = $db->prepare("SELECT id, subject, message, admin_reply, status, created_at FROM contact_messages WHERE email = ? ORDER BY created_at DESC");
        $stmt->execute([$lookupEmail]);
        $replyMessages = $stmt->fetchAll();
        if ($latestMessageId) {
            $replyMessages = array_values(array_filter($replyMessages, function ($item) use ($latestMessageId) {
                return $item['id'] != $latestMessageId;
            }));
        }
    } catch (Exception $e) {
        // ignore lookup errors; user can still submit messages.
    }
}
?>

<style>
  .contact-section {
    padding: 80px 20px;
    background: #fdfcf9;
    font-family: 'Noto Sans', sans-serif;
  }

  .contact-container {
    max-width: 1100px;
    margin: auto;
    display: flex;
    gap: 40px;
    flex-wrap: wrap;
  }

  /* LEFT BOX */
  .contact-info-box {
    flex: 1;
    background: #111;
    color: white;
    padding: 40px;
    border-radius: 15px;
  }

  .contact-info-box h2 {
    font-size: 32px;
    margin-bottom: 15px;
    font-weight: 700;
  }

  .contact-info-box p {
    font-size: 14px;
    opacity: 0.8;
    line-height: 1.6;
  }

  .info-item {
    margin-top: 15px;
    font-size: 14px;
  }

  .info-item i {
    margin-right: 10px;
    color: #c9a227;
  }

  .social-icons {
    margin-top: 25px;
  }

  .social-icons i {
    margin-right: 15px;
    font-size: 18px;
    cursor: pointer;
    color: #c9a227;
  }

  .social-icons a {
    text-decoration: none;
  }

  /* RIGHT FORM */
  .contact-form-box {
    flex: 1;
    background: white;
    padding: 40px;
    border-radius: 15px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
    border: 1px solid #eae5dc;
  }

  .contact-form-box h2 {
    margin-bottom: 20px;
    font-weight: 700;
  }

  .contact-form-box form {
    display: flex;
    flex-direction: column;
  }

  .contact-form-box input,
  .contact-form-box textarea {
    margin-bottom: 15px;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 8px;
    outline: none;
    font-size: 14px;
    background: #fdfdfd;
  }
  
  .contact-form-box input:focus,
  .contact-form-box textarea:focus {
    border-color: #c9a227;
  }

  .contact-form-box button {
    padding: 12px;
    background: #111;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: 0.3s;
    font-weight: 600;
  }

  .contact-form-box button:hover {
    background: #c9a227;
    color: black;
  }

  .contact-thread {
    margin-top: 26px;
  }

  .reply-card {
    margin-top: 18px;
    padding: 18px;
    border-radius: 14px;
    border: 1px solid #e4dfd2;
    background: #faf8f5;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
  }

  .reply-card h3 {
    margin: 0 0 10px;
    font-size: 16px;
    color: #111;
  }

  .reply-card p {
    margin: 8px 0;
    font-size: 14px;
    line-height: 1.6;
  }

  .reply-card .reply-label {
    font-weight: 700;
    color: #333;
  }

  /* RESPONSIVE */
  @media(max-width: 768px) {
    .contact-container {
      flex-direction: column;
    }
  }
</style>

<section class="contact-section">
  <div class="contact-container">

    <!-- Left Info -->
    <div class="contact-info-box">
      <h2>Get in Touch</h2>
      <p>
        We’d love to hear from you. Whether you have a question about our watches,
        orders, pricing, or anything else — our team is ready to help you.
      </p>

      <div class="info-item">
        <i class="fas fa-phone"></i>
        <span>+92 301 9876543</span>
      </div>

      <div class="info-item">
        <i class="fas fa-envelope"></i>
        <a style="text-decoration: none;color: #ddd;" href="mailto:vintagedial@gmail.com">vintagedial@gmail.com</a>
      </div>

      <div class="info-item">
        <i class="fas fa-location-dot"></i>
        <a style="text-decoration: none;color: #ddd;" href="https://maps.google.com/?q=Lahore+Main+Market+Shop+456+C3"
          target="_blank">
          Lahore Main Market, Shop #456 C3
        </a>
      </div>

      <div class="social-icons">
        <a href="https://facebook.com" target="_blank">
          <i class="fab fa-facebook-f"></i>
        </a>
        <a href="https://instagram.com" target="_blank">
          <i class="fab fa-instagram"></i>
        </a>
        <a href="https://x.com" target="_blank">
          <i class="fab fa-x-twitter"></i>
        </a>
      </div>
    </div>

    <!-- Right Form -->
    <div class="contact-form-box">
      <h2>Contact Form</h2>
      
      <?php if ($successMsg): ?>
        <div class="alert alert-success" style="border-radius: 8px;">
            <i class="fas fa-check-circle mr-2"></i> <?= $successMsg ?>
        </div>
      <?php elseif ($errorMsg): ?>
        <div class="alert alert-danger" style="border-radius: 8px;">
            <i class="fas fa-exclamation-circle mr-2"></i> <?= htmlspecialchars($errorMsg) ?>
        </div>
      <?php endif; ?>

      <form action="contact.php" method="POST">
        <input type="text" name="name" placeholder="Your Name" required value="<?= htmlspecialchars($oldValues['name']) ?>">
        <input type="email" name="email" placeholder="Your Email" required value="<?= htmlspecialchars($oldValues['email']) ?>">
        <input type="text" name="subject" placeholder="Subject" value="<?= htmlspecialchars($oldValues['subject']) ?>">
        <textarea name="message" rows="5" placeholder="Your Message" required><?= htmlspecialchars($oldValues['message']) ?></textarea>

        <button type="submit">Send Message</button>
      </form>

      <?php if ($latestMessage): ?>
        <div class="contact-thread">
          <h3>Just submitted message</h3>
          <div class="reply-card" style="border-color:#b08d57; background:#fff9e6;">
            <h3><?= htmlspecialchars($latestMessage['subject'] ?: 'No subject') ?></h3>
            <p><span class="reply-label">Your message:</span><br><?= nl2br(htmlspecialchars($latestMessage['message'])) ?></p>
            <?php if ($latestMessage['admin_reply']): ?>
              <p><span class="reply-label">Admin reply:</span><br><?= nl2br(htmlspecialchars($latestMessage['admin_reply'])) ?></p>
            <?php else: ?>
              <p><span class="reply-label">Admin reply:</span> Pending response</p>
            <?php endif; ?>
            <p style="font-size:13px; color:#666; margin-top:10px;">Status: <?= htmlspecialchars($latestMessage['status'] ?: 'New') ?> &bull; Sent on <?= date('d M Y H:i', strtotime($latestMessage['created_at'])) ?></p>
          </div>
        </div>
      <?php endif; ?>

      <?php if (!empty($replyMessages)): ?>
        <div class="contact-thread">
          <h3>Your previous messages</h3>
          <?php foreach ($replyMessages as $reply): ?>
            <div class="reply-card">
              <h3><?= htmlspecialchars($reply['subject'] ?: 'No subject') ?></h3>
              <p><span class="reply-label">Your message:</span><br><?= nl2br(htmlspecialchars($reply['message'])) ?></p>
              <?php if ($reply['admin_reply']): ?>
                <p><span class="reply-label">Admin reply:</span><br><?= nl2br(htmlspecialchars($reply['admin_reply'])) ?></p>
              <?php else: ?>
                <p><span class="reply-label">Admin reply:</span> Pending response</p>
              <?php endif; ?>
              <p style="font-size:13px; color:#666; margin-top:10px;">Status: <?= htmlspecialchars($reply['status'] ?: 'New') ?> &bull; Sent on <?= date('d M Y H:i', strtotime($reply['created_at'])) ?></p>
            </div>
          <?php endforeach; ?>
        </div>
      <?php elseif ($lookupEmail !== ''): ?>
        <div class="contact-thread">
          <div class="reply-card" style="border-color:#c5d7ef; background:#f2f5fb;">
            <p style="margin:0; color:#333;"><strong>No previous messages were found for</strong> <?= htmlspecialchars($lookupEmail) ?>.</p>
          </div>
        </div>
      <?php endif; ?>
    </div>

  </div>
</section>

<?php include 'includes/footer.php'; ?>
