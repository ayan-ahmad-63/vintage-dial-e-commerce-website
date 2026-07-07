<?php
require_once __DIR__ . '/../admin/config/db.php';

if (!function_exists('ensure_site_content_tables')) {
    function ensure_site_content_tables($db): void
    {
        $db->exec("CREATE TABLE IF NOT EXISTS site_about_content (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(150) NOT NULL,
            description TEXT NOT NULL,
            image VARCHAR(255) DEFAULT NULL,
            cta_text VARCHAR(100) DEFAULT NULL,
            cta_link VARCHAR(255) DEFAULT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'Active',
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        $db->exec("CREATE TABLE IF NOT EXISTS site_instagram_posts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            image VARCHAR(255) NOT NULL,
            caption TEXT NOT NULL,
            link VARCHAR(255) DEFAULT NULL,
            sort_order INT NOT NULL DEFAULT 0,
            status VARCHAR(20) NOT NULL DEFAULT 'Active',
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        $db->exec("CREATE TABLE IF NOT EXISTS site_press_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            badge VARCHAR(80) NOT NULL,
            description TEXT NOT NULL,
            image VARCHAR(255) NOT NULL,
            link VARCHAR(255) DEFAULT NULL,
            sort_order INT NOT NULL DEFAULT 0,
            status VARCHAR(20) NOT NULL DEFAULT 'Active',
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        $db->exec("CREATE TABLE IF NOT EXISTS site_moments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(150) NOT NULL,
            description TEXT NOT NULL,
            image VARCHAR(255) NOT NULL,
            link VARCHAR(255) DEFAULT NULL,
            sort_order INT NOT NULL DEFAULT 0,
            status VARCHAR(20) NOT NULL DEFAULT 'Active',
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        $db->exec("CREATE TABLE IF NOT EXISTS site_brands (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(120) NOT NULL,
            description TEXT NOT NULL,
            background_image VARCHAR(255) NOT NULL,
            logo_image VARCHAR(255) NOT NULL,
            view_text VARCHAR(80) DEFAULT 'View Collection',
            view_link VARCHAR(255) DEFAULT NULL,
            learn_text VARCHAR(80) DEFAULT 'Learn More',
            learn_link VARCHAR(255) DEFAULT NULL,
            sort_order INT NOT NULL DEFAULT 0,
            status VARCHAR(20) NOT NULL DEFAULT 'Active',
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        seed_site_content_defaults($db);
    }

    function seed_site_content_defaults($db): void
    {
        $aboutCount = (int) $db->query('SELECT COUNT(*) FROM site_about_content')->fetchColumn();
        if ($aboutCount === 0) {
            $stmt = $db->prepare("INSERT INTO site_about_content (title, description, image, cta_text, cta_link) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                'About Vintage Dial',
                "At Vintage Dial, we believe timepieces are more than just instruments to tell time — they are stories of heritage, craftsmanship, and timeless style. Our mission is to bring together the finest collection of watches, from classic designs to modern innovations, ensuring every wrist tells a unique story.\n\nFounded with passion for horology, we curate multi-brand collections that blend tradition with contemporary elegance. Whether you’re a collector, enthusiast, or someone seeking the perfect gift, Vintage Dial is your trusted destination.",
                './images/footer.jpeg',
                'Explore Our Collection',
                'watches.php'
            ]);
        }

        $instagramCount = (int) $db->query('SELECT COUNT(*) FROM site_instagram_posts')->fetchColumn();
        if ($instagramCount === 0) {
            $rows = [
                ['image' => './images/img1.jpg', 'caption' => 'Bold design, crafted with precision. #SeikoWatch', 'link' => 'https://instagram.com', 'sort_order' => 1],
                ['image' => './images/img2.jpg', 'caption' => 'The coastal blue dial with 300m water resistance. #SPB483', 'link' => 'https://instagram.com', 'sort_order' => 2],
                ['image' => './images/img3.jpg', 'caption' => 'Perfectly bold for all your adventures. #SeikoProspex', 'link' => 'https://instagram.com', 'sort_order' => 3],
                ['image' => './images/img4.jpg', 'caption' => 'Crafted for divers and explorers. #DiverWatch', 'link' => 'https://instagram.com', 'sort_order' => 4],
                ['image' => './images/img5.jpg', 'caption' => 'Adventure awaits with Seiko on your wrist.', 'link' => 'https://instagram.com', 'sort_order' => 5],
            ];

            $stmt = $db->prepare("INSERT INTO site_instagram_posts (image, caption, link, sort_order) VALUES (?, ?, ?, ?)");
            foreach ($rows as $row) {
                $stmt->execute([$row['image'], $row['caption'], $row['link'], $row['sort_order']]);
            }
        }

        $pressCount = (int) $db->query('SELECT COUNT(*) FROM site_press_items')->fetchColumn();
        if ($pressCount === 0) {
            $rows = [
                ['badge' => 'SEIKO', 'description' => 'Ryohei Suzuki, who has been appointed as a global ambassador, talks about the appeal of King Seiko and the modern interpretation of Japanese watchmaking.', 'image' => './images/n1.jpg', 'link' => '#', 'sort_order' => 1],
                ['badge' => 'PRESAGE', 'description' => 'Since its introduction in 2016, Presage has melded Japanese artistry with Seiko’s longstanding mastery to create a quietly luxurious aesthetic.', 'image' => 'images/n2.jpg', 'link' => '#', 'sort_order' => 2],
                ['badge' => 'PROSPEX', 'description' => 'Inspired by a lifestyle steeped in marine sports, the watch has a blue ceramic bezel and silvery white dial built for durability.', 'image' => 'images/n3.jpg', 'link' => '#', 'sort_order' => 3],
                ['badge' => 'SEIKO', 'description' => 'A modern take on heritage, blending precision engineering with iconic design language for contemporary collectors.', 'image' => 'images/n4.jpg', 'link' => '#', 'sort_order' => 4],
                ['badge' => 'PRESAGE', 'description' => 'The new Presage collection showcases subtle texture, refined indices, and elegant finishing inspired by Japanese textiles.', 'image' => './images/img2.jpg', 'link' => '#', 'sort_order' => 5],
                ['badge' => 'PROSPEX', 'description' => 'A performance-first watch story built for ocean-ready adventures and everyday resilience.', 'image' => './images/img4.jpg', 'link' => '#', 'sort_order' => 6],
            ];

            $stmt = $db->prepare("INSERT INTO site_press_items (badge, description, image, link, sort_order) VALUES (?, ?, ?, ?, ?)");
            foreach ($rows as $row) {
                $stmt->execute([$row['badge'], $row['description'], $row['image'], $row['link'], $row['sort_order']]);
            }
        }

        $brandCount = (int) $db->query('SELECT COUNT(*) FROM site_brands')->fetchColumn();
        if ($brandCount === 0) {
            $rows = [
                ['title' => 'Prospex', 'description' => 'Professional specifications for the ultimate in adventure.', 'background_image' => './images/s1.jpg', 'logo_image' => './images/p1.png', 'view_text' => 'View Collection', 'view_link' => 'watches.php?category=Watches', 'learn_text' => 'Learn More', 'learn_link' => 'learn-more.php', 'sort_order' => 1],
                ['title' => 'Presage', 'description' => 'Fine mechanical watchmaking from Japan.', 'background_image' => './images/s2.jpg', 'logo_image' => 'https://seikoluxe.com/wp-content/uploads/2024/05/presage.svg', 'view_text' => 'View Collection', 'view_link' => 'watches.php?category=Watches', 'learn_text' => 'Learn More', 'learn_link' => 'learn-more.php', 'sort_order' => 2],
                ['title' => 'Astron', 'description' => 'VANAC', 'background_image' => './images/s3.png', 'logo_image' => 'https://seikoluxe.com/wp-content/uploads/2024/04/White_KS_Logo-2048x325.webp', 'view_text' => 'View Collection', 'view_link' => 'watches.php?category=Limited+Edition', 'learn_text' => 'Learn More', 'learn_link' => 'learn-more.php', 'sort_order' => 3],
                ['title' => 'King Seiko', 'description' => 'The world’s first GPS Solar watch.', 'background_image' => './images/s4.png', 'logo_image' => 'https://seikoluxe.com/wp-content/uploads/2024/04/white-Astron.png', 'view_text' => 'View Collection', 'view_link' => 'watches.php?category=Limited+Edition', 'learn_text' => 'Learn More', 'learn_link' => 'learn-more.php', 'sort_order' => 4],
            ];

            $stmt = $db->prepare("INSERT INTO site_brands (title, description, background_image, logo_image, view_text, view_link, learn_text, learn_link, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            foreach ($rows as $row) {
                $stmt->execute([$row['title'], $row['description'], $row['background_image'], $row['logo_image'], $row['view_text'], $row['view_link'], $row['learn_text'], $row['learn_link'], $row['sort_order']]);
            }
        }

        $momentCount = (int) $db->query('SELECT COUNT(*) FROM site_moments')->fetchColumn();
        if ($momentCount === 0) {
            $rows = [
                ['title' => 'MECHANICAL CALIBER 6L37', 'description' => 'Precision-crafted movement with a refined balance between elegance and engineering.', 'image' => './images/m1.jpg', 'link' => 'learn-more.php', 'sort_order' => 1],
                ['title' => 'MECHANICAL CALIBER 6L37', 'description' => 'A classic mechanical finish built to deliver reliability, balance, and character.', 'image' => './images/m2.jpg', 'link' => 'learn-more.php', 'sort_order' => 2],
                ['title' => 'MECHANICAL CALIBER 6L37 MECHANICAL CALIBER', 'description' => 'A signature mechanical showcase with a bold visual story and timeless craftsmanship.', 'image' => './images/m3.jpg', 'link' => 'learn-more.php', 'sort_order' => 3],
                ['title' => 'MECHANICAL CALIBER 6L37', 'description' => 'An elevated take on mechanical artistry with clean finishing and standout detail.', 'image' => './images/m4.jpg', 'link' => 'learn-more.php', 'sort_order' => 4],
            ];

            $stmt = $db->prepare("INSERT INTO site_moments (title, description, image, link, sort_order) VALUES (?, ?, ?, ?, ?)");
            foreach ($rows as $row) {
                $stmt->execute([$row['title'], $row['description'], $row['image'], $row['link'], $row['sort_order']]);
            }
        }
    }

    function get_about_content($db)
    {
        $stmt = $db->query("SELECT * FROM site_about_content WHERE status = 'Active' ORDER BY id DESC LIMIT 1");
        $row = $stmt->fetch();

        if ($row) {
            return $row;
        }

        return [
            'title' => 'About Vintage Dial',
            'description' => "At Vintage Dial, we believe timepieces are more than just instruments to tell time — they are stories of heritage, craftsmanship, and timeless style. Our mission is to bring together the finest collection of watches, from classic designs to modern innovations, ensuring every wrist tells a unique story.\n\nFounded with passion for horology, we curate multi-brand collections that blend tradition with contemporary elegance. Whether you’re a collector, enthusiast, or someone seeking the perfect gift, Vintage Dial is your trusted destination.",
            'image' => './images/footer.jpeg',
            'cta_text' => 'Explore Our Collection',
            'cta_link' => 'watches.php'
        ];
    }

    function get_instagram_posts($db): array
    {
        $stmt = $db->query("SELECT * FROM site_instagram_posts WHERE status = 'Active' ORDER BY sort_order, id");
        return $stmt->fetchAll() ?: [];
    }

    function get_press_items($db): array
    {
        $stmt = $db->query("SELECT * FROM site_press_items WHERE status = 'Active' ORDER BY sort_order, id");
        return $stmt->fetchAll() ?: [];
    }

    function get_site_brands($db): array
    {
        $stmt = $db->query("SELECT * FROM site_brands WHERE status = 'Active' ORDER BY sort_order, id");
        return $stmt->fetchAll() ?: [];
    }

    function get_site_moments($db): array
    {
        $stmt = $db->query("SELECT * FROM site_moments WHERE status = 'Active' ORDER BY sort_order, id");
        return $stmt->fetchAll() ?: [];
    }
}