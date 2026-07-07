<?php
require_once 'includes/auth_check.php';
require_once 'config/db.php';
$currentPage = 'contacts';
$pageTitle = 'Contact Messages';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $messageId = intval($_POST['message_id'] ?? 0);
    if ($messageId > 0) {
        if ($_POST['action'] === 'delete') {
            $stmt = $db->prepare("DELETE FROM contact_messages WHERE id = ?");
            $stmt->execute([$messageId]);
            header('Location: contact_messages.php?msg=deleted');
            exit;
        }

        if ($_POST['action'] === 'mark_read') {
            $stmt = $db->prepare("UPDATE contact_messages SET status = 'Read' WHERE id = ?");
            $stmt->execute([$messageId]);
            header('Location: contact_messages.php?msg=marked');
            exit;
        }

        if ($_POST['action'] === 'reply') {
            $adminReply = trim($_POST['admin_reply'] ?? '');
            if ($adminReply !== '') {
                $stmt = $db->prepare("UPDATE contact_messages SET admin_reply = ?, status = 'Replied' WHERE id = ?");
                $stmt->execute([$adminReply, $messageId]);
                header('Location: contact_messages.php?msg=replied');
                exit;
            }
        }
    }
}

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

$messages = $db->query("SELECT * FROM contact_messages ORDER BY created_at DESC")->fetchAll();
$totalMessages = count($messages);
$newMessages = $db->query("SELECT COUNT(*) as cnt FROM contact_messages WHERE status = 'New'")->fetch()['cnt'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Manage contact messages on Vintage Dial admin panel">
    <title>Contact Messages | Vintage Dial Admin</title>
    <link rel="icon" type="image/png" href="../images/footer.jpeg">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="admin.css">
</head>

<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main">
        <?php include 'includes/topbar.php'; ?>

        <section class="content">
            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
            <div
                style="background:#fee2e2; color:#991b1b; padding:12px 16px; border-radius:10px; margin-bottom:16px; font-size:13px;">
                <i class="fas fa-trash-alt"></i> Message deleted successfully.
            </div>
            <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'marked'): ?>
            <div
                style="background:#d1fae5; color:#065f46; padding:12px 16px; border-radius:10px; margin-bottom:16px; font-size:13px;">
                <i class="fas fa-check-circle"></i> Message marked as read.
            </div>
            <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'replied'): ?>
            <div
                style="background:#d1fae5; color:#065f46; padding:12px 16px; border-radius:10px; margin-bottom:16px; font-size:13px;">
                <i class="fas fa-check-circle"></i> Reply saved successfully.
            </div>
            <?php endif; ?>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="fas fa-envelope"></i></div>
                    <div class="stat-info">
                        <h3><?= number_format($totalMessages) ?></h3>
                        <p>Total Messages</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green"><i class="fas fa-star"></i></div>
                    <div class="stat-info">
                        <h3><?= number_format($newMessages) ?></h3>
                        <p>New Messages</p>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Subject</th>
                                    <th>Message</th>
                                    <th>Admin Reply</th>
                                    <th>Status</th>
                                    <th>Received</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($messages)): ?>
                                <tr>
                                    <td colspan="9" style="text-align:center; padding:24px; color:#666;">No contact
                                        messages found.</td>
                                </tr>
                                <?php else: ?>
                                <?php $i = 1; foreach ($messages as $msg): ?>
                                <tr>
                                    <td><?= $i++ ?></td>
                                    <td><?= htmlspecialchars($msg['name']) ?></td>
                                    <td><?= htmlspecialchars($msg['email']) ?></td>
                                    <td><?= htmlspecialchars($msg['subject'] ?: 'No subject') ?></td>
                                    <td
                                        style="max-width:280px; white-space:pre-wrap; overflow:hidden; text-overflow:ellipsis; font-size:13px; color:#444;">
                                        <?= htmlspecialchars($msg['message']) ?></td>
                                    <td
                                        style="max-width:220px; white-space:pre-wrap; overflow:hidden; text-overflow:ellipsis; font-size:13px; color:#333;">
                                        <?= htmlspecialchars($msg['admin_reply'] ?: '—') ?></td>
                                    <td>
                                        <span
                                            class="badge badge-<?= strtolower($msg['status'] === 'New' ? 'pending' : 'active') ?>">
                                            <?= htmlspecialchars($msg['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= date('d M Y H:i', strtotime($msg['created_at'])) ?></td>
                                    <td>
                                        <?php if ($msg['status'] === 'New'): ?>
                                        <form method="POST" style="display:inline; margin-right:6px;">
                                            <input type="hidden" name="action" value="mark_read">
                                            <input type="hidden" name="message_id" value="<?= $msg['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-secondary"
                                                title="Mark as read"><i class="fas fa-check"></i></button>
                                        </form>
                                        <?php endif; ?>
                                        <button type="button" class="btn btn-sm btn-primary"
                                            onclick="toggleReplyBox(<?= $msg['id'] ?>)" title="Reply to message">
                                            <i class="fas fa-reply"></i>
                                        </button>
                                        <form method="POST" style="display:inline; margin-left:8px;"
                                            onsubmit="return confirm('Delete this message?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="message_id" value="<?= $msg['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger"><i
                                                    class="fas fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                <tr id="reply-row-<?= $msg['id'] ?>" style="display:none; background:#fbfbfb;">
                                    <td colspan="9" style="padding:12px;">
                                        <form method="POST">
                                            <input type="hidden" name="action" value="reply">
                                            <input type="hidden" name="message_id" value="<?= $msg['id'] ?>">
                                            <textarea name="admin_reply" rows="4"
                                                style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px; font-size:13px;"
                                                placeholder="Write your reply here..."><?= htmlspecialchars($msg['admin_reply']) ?></textarea>
                                            <div
                                                style="margin-top:10px; display:flex; gap:10px; justify-content:flex-end;">
                                                <button type="button" class="btn btn-sm btn-outline"
                                                    onclick="toggleReplyBox(<?= $msg['id'] ?>)">Cancel</button>
                                                <button type="submit" class="btn btn-sm btn-primary">Save Reply</button>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <script>
    function toggleReplyBox(id) {
        const row = document.getElementById('reply-row-' + id);
        if (!row) return;
        row.style.display = row.style.display === 'table-row' ? 'none' : 'table-row';
    }
    </script>
</body>

</html>