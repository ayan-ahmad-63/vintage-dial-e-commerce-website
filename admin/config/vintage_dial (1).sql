-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 29, 2026 at 11:24 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `vintage_dial`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) DEFAULT 'Administrator',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `full_name`, `email`, `phone`, `password`, `role`, `created_at`) VALUES
(1, 'Ayan Ahmad', 'aa@vintagedial.com', '', '$2y$10$nn9YsOM/bYh60UZH2vUKEOaSmRC5tGNoNVC1e6ewLan1sZuTTEM4m', 'Super Administrator', '2026-05-25 17:02:59');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `image`, `status`, `created_at`) VALUES
(1, 'Watches', 'Fine mechanical watchmaking from Japan.', 'images/s1.jpg', 'Active', '2026-05-25 17:02:59'),
(2, 'Limited Edition', 'Rare and unique limited edition timepieces.', 'images/s2.jpg', 'Active', '2026-05-25 17:02:59'),
(5, 'Hand Made', '', 'uploads/cat_1780089677_2cd632b5.webp', 'Active', '2026-05-29 21:21:17');

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `status` varchar(20) DEFAULT 'New',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `admin_reply` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contact_messages`
--

INSERT INTO `contact_messages` (`id`, `name`, `email`, `subject`, `message`, `status`, `created_at`, `admin_reply`) VALUES
(1, 'Ayan Ahmad', 'ayanahmad3950@gmail.com', 'broken case', 'box is damage at the time of delivery', 'Replied', '2026-05-29 19:13:30', 'issue noted'),
(2, 'Ayan Ahmad', 'ayanahmad3950@gmail.com', 'broken case', 'at the time of delivery', 'Replied', '2026-05-29 19:20:15', 'noted');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `total_orders` int(11) DEFAULT 0,
  `total_spent` decimal(10,2) DEFAULT 0.00,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expiry` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `full_name`, `email`, `phone`, `city`, `address`, `password`, `gender`, `dob`, `total_orders`, `total_spent`, `reset_token`, `reset_token_expiry`, `created_at`) VALUES
(4, 'aya', 'ayanahmad3950@gmail.com', '03421234567', 'vehari', 'h#406', '$2y$10$PDMuMymgsmNWvvxNdNkim.VYCnyv6LxboeRXFI8BspetMrOi2VSP2', 'Male', '2004-01-05', 4, 2940000.00, '7bd938617e46832dd75ae0f2f82bf3c7', '2026-05-29 21:36:38', '2026-05-25 18:40:16');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `order_code` varchar(50) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(50) DEFAULT 'Pending',
  `note` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `order_code`, `customer_id`, `product_id`, `quantity`, `total_amount`, `order_date`, `status`, `note`) VALUES
(4, 'VD-483610', 4, 3, 1, 345000.00, '2026-05-25 18:43:54', 'Cancelled', ''),
(5, 'VD-651200', 4, 1, 3, 1800000.00, '2026-05-25 19:41:20', 'Delivered', ''),
(7, 'VD-857566', 4, 3, 1, 345000.00, '2026-05-29 18:37:58', 'Delivered', ''),
(8, 'VD-998329', 4, 8, 1, 500000.00, '2026-05-29 19:04:32', 'Shipped', '');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `subtitle` varchar(255) DEFAULT NULL,
  `category_id` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) DEFAULT 0,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `subtitle`, `category_id`, `price`, `stock`, `description`, `image`, `created_at`) VALUES
(1, 'SNR055', 'Prospex LX GMT U.S. Special Edition', 2, 600000.00, 8, 'Prospex LX U.S. Special Edition designed for those who treat life as the ultimate adventure. Inspired by the sunset over Earth marking the boundary between planet and space. Crafted with titanium, sapphire crystal, and Spring Drive GMT movement delivering extreme precision.', 'images/w1.png', '2026-05-25 17:02:59'),
(2, 'SRQ051', 'Prospex Speedtimer Chronograph', 1, 250000.00, 8, 'Seiko Prospex Speedtimer mechanical chronograph. Hand-assembled by skilled watchmakers, combining a high-frequency movement with a column wheel and vertical clutch mechanism to ensure maximum timing accuracy.', 'images/w2.png', '2026-05-25 17:02:59'),
(3, 'SRQ053', 'Prospex Speedtimer Chronograph Special Edition', 1, 345000.00, 4, 'Speedtimer Chronograph Special Edition featuring a unique textured dial and high-performance caliber with 45 hours power reserve. Built for professionals.', 'images/w3.png', '2026-05-25 17:02:59'),
(4, 'SPB463', 'Presage Classic Series', 1, 78000.00, 0, 'Presage Classic Series offering the finest in Japanese mechanical watchmaking, blending precision technology with superior craftsmanship and timeless design. Inspired by traditional Japanese textiles in \"shiro-iro\" (unbleached silk), the dial reflects ambient light with deep texture and elegance. Built with stainless steel case, curved sapphire crystal, and automatic movement, this watch expresses pure Japanese heritage and modern engineering.', 'images/w4.png', '2026-05-25 17:02:59'),
(5, 'SPB465', 'Presage Classic Series Limited Edition', 2, 95000.00, 15, 'Presage Classic Series Limited Edition. Features a stunning deep green dial textured like unspun silk. Powered by the reliable Caliber 6R55 automatic movement with a 72-hour power reserve. Stainless steel case with super-hard coating.', 'images/w5.png', '2026-05-25 17:02:59'),
(8, 'SPB4650', 'Presage Cocktail', 2, 500000.00, 9, '', '[\"uploads\\/prod_1780081357_87ab7412.webp\",\"uploads\\/prod_1780081357_a2c79d2a.webp\",\"uploads\\/prod_1780081357_d4a41d73.webp\",\"uploads\\/prod_1780081357_3bb2287a.webp\"]', '2026-05-29 19:02:37');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `review_text` text NOT NULL,
  `review_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `admin_reply` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `customer_id`, `product_id`, `rating`, `review_text`, `review_date`, `admin_reply`, `status`) VALUES
(3, 4, 2, 5, 'good quality', '2026-05-25 19:10:46', NULL, 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `site_about_content`
--

CREATE TABLE `site_about_content` (
  `id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `cta_text` varchar(100) DEFAULT NULL,
  `cta_link` varchar(255) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'Active',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `site_about_content`
--

INSERT INTO `site_about_content` (`id`, `title`, `description`, `image`, `cta_text`, `cta_link`, `status`, `updated_at`) VALUES
(1, 'About Vintage Dial', 'At Vintage Dial, we believe timepieces are more than just instruments to tell time ? they are stories of heritage, craftsmanship, and timeless style. Our mission is to bring together the finest collection of watches, from classic designs to modern innovations, ensuring every wrist tells a unique story.\n\nFounded with passion for horology, we curate multi-brand collections that blend tradition with contemporary elegance. Whether you?re a collector, enthusiast, or someone seeking the perfect gift, Vintage Dial is your trusted destination.', './images/footer.jpeg', 'Explore Our Collection', 'watches.php', 'Active', '2026-05-25 18:38:41');

-- --------------------------------------------------------

--
-- Table structure for table `site_brands`
--

CREATE TABLE `site_brands` (
  `id` int(11) NOT NULL,
  `title` varchar(120) NOT NULL,
  `description` text NOT NULL,
  `background_image` varchar(255) NOT NULL,
  `logo_image` varchar(255) NOT NULL,
  `view_text` varchar(80) DEFAULT 'View Collection',
  `view_link` varchar(255) DEFAULT NULL,
  `learn_text` varchar(80) DEFAULT 'Learn More',
  `learn_link` varchar(255) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `status` varchar(20) NOT NULL DEFAULT 'Active',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `site_brands`
--

INSERT INTO `site_brands` (`id`, `title`, `description`, `background_image`, `logo_image`, `view_text`, `view_link`, `learn_text`, `learn_link`, `sort_order`, `status`, `updated_at`) VALUES
(1, 'Prospex', 'Professional specifications for the ultimate in adventure.', './images/s1.jpg', './images/p1.png', 'View Collection', 'watches.php?category=Watches', 'Learn More', 'learn-more.php', 1, 'Active', '2026-05-25 19:16:54'),
(2, 'Presage', 'Fine mechanical watchmaking from Japan.', './images/s2.jpg', 'https://seikoluxe.com/wp-content/uploads/2024/05/presage.svg', 'View Collection', 'watches.php?category=Watches', 'Learn More', 'learn-more.php', 2, 'Active', '2026-05-25 19:16:54'),
(3, 'Astron', 'VANAC', './images/s3.png', 'https://seikoluxe.com/wp-content/uploads/2024/04/White_KS_Logo-2048x325.webp', 'View Collection', 'watches.php?category=Limited+Edition', 'Learn More', 'learn-more.php', 3, 'Active', '2026-05-25 19:16:54'),
(4, 'King Seiko', 'The world’s first GPS Solar watch.', './images/s4.png', 'https://seikoluxe.com/wp-content/uploads/2024/04/white-Astron.png', 'View Collection', 'watches.php?category=Limited+Edition', 'Learn More', 'learn-more.php', 4, 'Active', '2026-05-25 19:16:54');

-- --------------------------------------------------------

--
-- Table structure for table `site_instagram_posts`
--

CREATE TABLE `site_instagram_posts` (
  `id` int(11) NOT NULL,
  `image` varchar(255) NOT NULL,
  `caption` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `status` varchar(20) NOT NULL DEFAULT 'Active',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `site_instagram_posts`
--

INSERT INTO `site_instagram_posts` (`id`, `image`, `caption`, `link`, `sort_order`, `status`, `updated_at`) VALUES
(1, './images/img1.jpg', 'Bold design, crafted with precision. #SeikoWatch', 'https://instagram.com', 1, 'Active', '2026-05-25 18:34:53'),
(2, './images/img2.jpg', 'The coastal blue dial with 300m water resistance. #SPB483', 'https://instagram.com', 2, 'Active', '2026-05-25 18:34:53'),
(3, './images/img3.jpg', 'Perfectly bold for all your adventures. #SeikoProspex', 'https://instagram.com', 3, 'Active', '2026-05-25 18:34:53'),
(4, './images/img4.jpg', 'Crafted for divers and explorers. #DiverWatch', 'https://instagram.com', 4, 'Active', '2026-05-25 18:34:53'),
(5, './images/img5.jpg', 'Adventure awaits with Seiko on your wrist.', 'https://instagram.com', 5, 'Active', '2026-05-25 18:34:53');

-- --------------------------------------------------------

--
-- Table structure for table `site_moments`
--

CREATE TABLE `site_moments` (
  `id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text NOT NULL,
  `image` varchar(255) NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `status` varchar(20) NOT NULL DEFAULT 'Active',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `site_moments`
--

INSERT INTO `site_moments` (`id`, `title`, `description`, `image`, `link`, `sort_order`, `status`, `updated_at`) VALUES
(1, 'MECHANICAL CALIBER 6L37', 'Precision-crafted movement with a refined balance between elegance and engineering.', './images/m1.jpg', 'learn-more.php', 1, 'Active', '2026-05-25 19:43:54'),
(2, 'MECHANICAL CALIBER 6L37', 'A classic mechanical finish built to deliver reliability, balance, and character.', './images/m2.jpg', 'learn-more.php', 2, 'Active', '2026-05-25 19:43:54'),
(3, 'MECHANICAL CALIBER 6L37 MECHANICAL CALIBER', 'A signature mechanical showcase with a bold visual story and timeless craftsmanship.', './images/m3.jpg', 'learn-more.php', 3, 'Active', '2026-05-25 19:43:54'),
(4, 'MECHANICAL CALIBER 6L37', 'An elevated take on mechanical artistry with clean finishing and standout detail.', './images/m4.jpg', 'learn-more.php', 4, 'Active', '2026-05-25 19:43:54');

-- --------------------------------------------------------

--
-- Table structure for table `site_press_items`
--

CREATE TABLE `site_press_items` (
  `id` int(11) NOT NULL,
  `badge` varchar(80) NOT NULL,
  `description` text NOT NULL,
  `image` varchar(255) NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `status` varchar(20) NOT NULL DEFAULT 'Active',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `site_press_items`
--

INSERT INTO `site_press_items` (`id`, `badge`, `description`, `image`, `link`, `sort_order`, `status`, `updated_at`) VALUES
(1, 'SEIKO', 'Ryohei Suzuki, who has been appointed as a global ambassador, talks about the appeal of King Seiko and the modern interpretation of Japanese watchmaking.', './images/n1.jpg', '#', 1, 'Active', '2026-05-25 18:34:53'),
(2, 'PRESAGE', 'Since its introduction in 2016, Presage has melded Japanese artistry with Seiko’s longstanding mastery to create a quietly luxurious aesthetic.', 'images/n2.jpg', '#', 2, 'Active', '2026-05-25 18:34:53'),
(3, 'PROSPEX', 'Inspired by a lifestyle steeped in marine sports, the watch has a blue ceramic bezel and silvery white dial built for durability.', 'images/n3.jpg', '#', 3, 'Active', '2026-05-25 18:34:53'),
(4, 'SEIKO', 'A modern take on heritage, blending precision engineering with iconic design language for contemporary collectors.', 'images/n4.jpg', '#', 4, 'Active', '2026-05-25 18:34:53'),
(5, 'PRESAGE', 'The new Presage collection showcases subtle texture, refined indices, and elegant finishing inspired by Japanese textiles.', './images/img2.jpg', '#', 5, 'Active', '2026-05-25 18:34:53'),
(6, 'PROSPEX', 'A performance-first watch story built for ocean-ready adventures and everyday resilience.', './images/img4.jpg', '#', 6, 'Active', '2026-05-25 18:34:53');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `site_about_content`
--
ALTER TABLE `site_about_content`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `site_brands`
--
ALTER TABLE `site_brands`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `site_instagram_posts`
--
ALTER TABLE `site_instagram_posts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `site_moments`
--
ALTER TABLE `site_moments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `site_press_items`
--
ALTER TABLE `site_press_items`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `site_about_content`
--
ALTER TABLE `site_about_content`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `site_brands`
--
ALTER TABLE `site_brands`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `site_instagram_posts`
--
ALTER TABLE `site_instagram_posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `site_moments`
--
ALTER TABLE `site_moments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `site_press_items`
--
ALTER TABLE `site_press_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
