-- Gym Management System Database Backup
-- Generated on 2025-10-25 17:53:10

SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE `activity_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `module` varchar(50) DEFAULT NULL,
  `details` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `attendance` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `role` enum('trainer','member') NOT NULL,
  `date` date NOT NULL,
  `check_in` time DEFAULT NULL,
  `check_out` time DEFAULT NULL,
  `status` enum('present','absent') DEFAULT 'present',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `attendance` VALUES
('1','2','member','2024-10-20','08:00:00','10:00:00','present'),
('2','3','member','2024-10-20','09:00:00','11:00:00','present'),
('3','1','trainer','2024-10-20','07:30:00','12:00:00','present');

CREATE TABLE `branches` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `address` text,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `manager_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `manager_id` (`manager_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `class_bookings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `class_id` int NOT NULL,
  `member_id` int NOT NULL,
  `booking_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('confirmed','cancelled','attended') DEFAULT 'confirmed',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_booking` (`class_id`,`member_id`),
  KEY `member_id` (`member_id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `class_bookings` VALUES
('1','1','1','2025-10-25 22:15:46','confirmed'),
('2','1','2','2025-10-25 22:15:46','confirmed'),
('3','2','1','2025-10-25 22:15:46','confirmed'),
('4','2','3','2025-10-25 22:15:46','attended'),
('5','3','2','2025-10-25 22:15:46','confirmed');

CREATE TABLE `diet_plans` (
  `id` int NOT NULL AUTO_INCREMENT,
  `trainer_id` int NOT NULL,
  `member_id` int NOT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `trainer_id` (`trainer_id`),
  KEY `member_id` (`member_id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `diet_plans` VALUES
('1','1','1','High protein, low carb diet','2025-10-25 22:15:46'),
('2','2','2','Vegetarian diet with supplements','2025-10-25 22:15:46'),
('3','3','3','Balanced diet with focus on hydration','2025-10-25 22:15:46');

CREATE TABLE `equipment` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `category` varchar(50) NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `purchase_date` date DEFAULT NULL,
  `purchase_cost` decimal(10,2) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `status` enum('available','in_use','maintenance','out_of_order') DEFAULT 'available',
  `description` text,
  `maintenance_schedule` varchar(100) DEFAULT NULL,
  `last_maintenance` date DEFAULT NULL,
  `next_maintenance` date DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `equipment` VALUES
('1','Treadmill','Cardio','5','2023-01-15','25000.00','Cardio Section','available','Commercial treadmills with heart rate monitors','Monthly','2024-09-01','2024-10-01'),
('2','Dumbbells Set','Strength','20','2023-03-20','15000.00','Weight Section','available','Complete set from 5kg to 50kg','Weekly','2024-10-20','2024-10-27'),
('3','Bench Press','Strength','3','2022-11-10','30000.00','Weight Section','maintenance','Olympic bench press stations','Bi-weekly','2024-10-15','2024-10-29'),
('4','Yoga Mats','Accessories','50','2024-01-05','2500.00','Yoga Studio','available','Non-slip yoga mats','Monthly','2024-09-15','2024-10-15'),
('5','Stationary Bike','Cardio','8','2023-06-12','40000.00','Cardio Section','available','Spin bikes with digital displays','Weekly','2024-10-18','2024-10-25');

CREATE TABLE `expenses` (
  `id` int NOT NULL AUTO_INCREMENT,
  `category` varchar(100) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `expense_date` date NOT NULL,
  `description` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `expenses` VALUES
('1','Equipment','1500.00','2024-09-01','New dumbbells and weights'),
('2','Maintenance','500.00','2024-09-10','AC repair'),
('3','Marketing','800.00','2024-09-15','Social media ads');

CREATE TABLE `feedback` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `type` enum('feedback','complaint') NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `rating` int DEFAULT NULL,
  `status` enum('pending','reviewed','resolved') DEFAULT 'pending',
  `admin_response` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `feedback_chk_1` CHECK (((`rating` >= 1) and (`rating` <= 5)))
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `group_classes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `trainer_id` int DEFAULT NULL,
  `description` text,
  `capacity` int NOT NULL,
  `duration_minutes` int NOT NULL,
  `class_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `status` enum('scheduled','completed','cancelled') DEFAULT 'scheduled',
  PRIMARY KEY (`id`),
  KEY `trainer_id` (`trainer_id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `group_classes` VALUES
('1','Morning Yoga','2','Relaxing yoga session for all levels','20','60','2024-10-26','07:00:00','08:00:00','scheduled'),
('2','Strength Training','1','Full body strength workout','15','90','2024-10-26','18:00:00','19:30:00','scheduled'),
('3','Cardio Blast','3','High intensity cardio session','25','45','2024-10-27','19:00:00','19:45:00','scheduled'),
('4','Pilates Core','2','Core strengthening with Pilates','12','60','2024-10-28','10:00:00','11:00:00','scheduled');

CREATE TABLE `inventory` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `quantity` int NOT NULL DEFAULT '0',
  `unit_price` decimal(10,2) DEFAULT NULL,
  `supplier_id` int DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `description` text,
  PRIMARY KEY (`id`),
  KEY `supplier_id` (`supplier_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `member_progress` (
  `id` int NOT NULL AUTO_INCREMENT,
  `member_id` int NOT NULL,
  `measurement_date` date NOT NULL,
  `weight` decimal(5,2) DEFAULT NULL,
  `height` decimal(5,2) DEFAULT NULL,
  `chest` decimal(5,2) DEFAULT NULL,
  `waist` decimal(5,2) DEFAULT NULL,
  `hips` decimal(5,2) DEFAULT NULL,
  `biceps` decimal(5,2) DEFAULT NULL,
  `thighs` decimal(5,2) DEFAULT NULL,
  `notes` text,
  PRIMARY KEY (`id`),
  KEY `member_id` (`member_id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `member_progress` VALUES
('1','1','2024-01-05','70.50','165.00','95.00','80.00','100.00','30.00','55.00','Initial measurements'),
('2','1','2024-02-05','69.20','165.00','93.50','78.50','98.50','31.20','56.20','Good progress after 1 month'),
('3','2','2024-02-10','85.00','175.00','105.00','90.00','105.00','35.00','60.00','Starting measurements'),
('4','3','2024-03-15','78.00','170.00','98.00','85.00','102.00','32.00','58.00','Initial assessment');

CREATE TABLE `members` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `contact` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text,
  `join_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `plan_id` int DEFAULT NULL,
  `trainer_id` int DEFAULT NULL,
  `status` enum('active','expired','inactive') DEFAULT 'active',
  `photo` varchar(255) DEFAULT NULL,
  `branch_id` int DEFAULT '1',
  `qr_code` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `plan_id` (`plan_id`),
  KEY `trainer_id` (`trainer_id`),
  KEY `branch_id` (`branch_id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `members` VALUES
('1','Alice Brown','female','1995-05-12','+1-555-2001','alice.brown@example.com','456 Wellness Ave, Health City','2024-01-05','2024-02-05','1','1','active','alice.jpg','1','GMS_MEMBER_1_bd8e7335d8d25d08a958a47c398619b0'),
('2','Bob Green','male','1990-08-22','+1-555-2002','bob.green@example.com','789 Power St, Health City','2024-02-10','2024-05-10','2','2','active','bob.jpg','1','GMS_MEMBER_2_875fd7f6888bd041eeb4ec06145d785f'),
('3','Charlie Black','male','1988-11-30','+1-555-2003','charlie.black@example.com','321 Energy Rd, Health City','2024-03-15','2025-03-15','3','3','expired','charlie.jpg','1','GMS_MEMBER_3_62670c05c0c8ea5ea1f1dd326c6d25ff');

CREATE TABLE `payments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `member_id` int NOT NULL,
  `plan_id` int NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` date NOT NULL,
  `method` enum('cash','card','upi','bank_transfer') NOT NULL,
  `invoice_no` varchar(50) DEFAULT NULL,
  `status` enum('paid','pending','failed') DEFAULT 'paid',
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoice_no` (`invoice_no`),
  KEY `member_id` (`member_id`),
  KEY `plan_id` (`plan_id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `payments` VALUES
('1','1','1','500.00','2024-01-05','cash','INV1001','paid'),
('2','2','2','1200.00','2024-02-10','card','INV1002','paid'),
('3','3','3','5000.00','2024-03-15','upi','INV1003','pending');

CREATE TABLE `payroll` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `month` year NOT NULL,
  `year` year NOT NULL,
  `base_salary` decimal(10,2) DEFAULT NULL,
  `hours_worked` decimal(5,2) DEFAULT NULL,
  `overtime_hours` decimal(5,2) DEFAULT NULL,
  `overtime_rate` decimal(10,2) DEFAULT NULL,
  `deductions` decimal(10,2) DEFAULT NULL,
  `net_salary` decimal(10,2) DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `status` enum('pending','paid') DEFAULT 'pending',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `permissions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `module` varchar(50) NOT NULL,
  `action` varchar(20) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_permission` (`module`,`action`)
) ENGINE=MyISAM AUTO_INCREMENT=83 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `permissions` VALUES
('1','dashboard','view'),
('2','members','view'),
('3','members','add'),
('4','members','edit'),
('5','members','delete'),
('6','trainers','view'),
('7','trainers','add'),
('8','trainers','edit'),
('9','trainers','delete'),
('10','plans','view'),
('11','plans','add'),
('12','plans','edit'),
('13','plans','delete'),
('14','attendance','view'),
('15','attendance','add'),
('16','attendance','edit'),
('17','attendance','delete'),
('18','payments','view'),
('19','payments','add'),
('20','payments','edit'),
('21','payments','delete'),
('22','expenses','view'),
('23','expenses','add'),
('24','expenses','edit'),
('25','expenses','delete'),
('26','equipment','view'),
('27','equipment','add'),
('28','equipment','edit'),
('29','equipment','delete'),
('30','member_progress','view'),
('31','member_progress','add'),
('32','member_progress','edit'),
('33','member_progress','delete'),
('34','group_classes','view'),
('35','group_classes','add'),
('36','group_classes','edit'),
('37','group_classes','delete'),
('38','notifications','view'),
('39','notifications','add'),
('40','notifications','edit'),
('41','notifications','delete'),
('42','reports','view'),
('43','settings','view'),
('44','settings','edit'),
('45','profile','view'),
('46','profile','edit'),
('47','rbac','view'),
('48','rbac','add'),
('49','rbac','edit'),
('50','rbac','delete'),
('51','reception','view'),
('52','reception','add'),
('53','reception','edit'),
('54','inventory','view'),
('55','inventory','add'),
('56','inventory','edit'),
('57','inventory','delete'),
('58','suppliers','view'),
('59','suppliers','add'),
('60','suppliers','edit'),
('61','suppliers','delete'),
('62','sales','view'),
('63','sales','add'),
('64','sales','edit'),
('65','sales','delete'),
('66','payroll','view'),
('67','payroll','add'),
('68','payroll','edit'),
('69','payroll','delete'),
('70','feedback','view'),
('71','feedback','add'),
('72','feedback','edit'),
('73','feedback','delete'),
('74','activity_log','view'),
('75','backup','view'),
('76','backup','add'),
('77','branches','view'),
('78','branches','add'),
('79','branches','edit'),
('80','branches','delete'),
('81','api','view'),
('82','api','edit');

CREATE TABLE `plans` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `duration_months` int NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `plans` VALUES
('1','Basic Plan','1','500.00','Basic membership with access to gym equipment'),
('2','Premium Plan','3','1200.00','Premium membership with trainer sessions'),
('3','VIP Plan','12','5000.00','VIP membership with personal trainer and diet plans');

CREATE TABLE `role_permissions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `role_id` int NOT NULL,
  `permission_id` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_role_permission` (`role_id`,`permission_id`),
  KEY `permission_id` (`permission_id`)
) ENGINE=MyISAM AUTO_INCREMENT=95 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `role_permissions` VALUES
('1','1','1'),
('2','1','2'),
('3','1','3'),
('4','1','4'),
('5','1','5'),
('6','1','6'),
('7','1','7'),
('8','1','8'),
('9','1','9'),
('10','1','10'),
('11','1','11'),
('12','1','12'),
('13','1','13'),
('14','1','14'),
('15','1','15'),
('16','1','16'),
('17','1','17'),
('18','1','18'),
('19','1','19'),
('20','1','20'),
('21','1','21'),
('22','1','22'),
('23','1','23'),
('24','1','24'),
('25','1','25'),
('26','1','26'),
('27','1','27'),
('28','1','28'),
('29','1','29'),
('30','1','30'),
('31','1','31'),
('32','1','32'),
('33','1','33'),
('34','1','34'),
('35','1','35'),
('36','1','36'),
('37','1','37'),
('38','1','38'),
('39','1','39'),
('40','1','40'),
('41','1','41'),
('42','1','42'),
('43','1','43'),
('44','1','44'),
('45','1','45'),
('46','1','46'),
('47','1','47'),
('48','1','48'),
('49','1','49'),
('50','1','50'),
('51','1','51'),
('52','1','52'),
('53','1','53'),
('54','1','54'),
('55','1','55'),
('56','1','56'),
('57','1','57'),
('58','1','58'),
('59','1','59'),
('60','1','60'),
('61','1','61'),
('62','1','62'),
('63','1','63'),
('64','1','64'),
('65','1','65'),
('66','1','66'),
('67','1','67'),
('68','1','68'),
('69','1','69'),
('70','1','70'),
('71','1','71'),
('72','1','72'),
('73','1','73'),
('74','1','74'),
('75','1','75'),
('76','1','76'),
('77','1','77'),
('78','1','78'),
('79','1','79'),
('80','1','80'),
('81','1','81'),
('82','1','82'),
('83','2','15'),
('84','2','16'),
('85','2','14'),
('86','2','1'),
('87','2','3'),
('88','2','4'),
('89','2','2'),
('90','2','46'),
('91','2','45'),
('92','3','14'),
('93','3','1'),
('94','3','45');

CREATE TABLE `roles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `roles` VALUES
('1','Admin','Full system access with all permissions'),
('2','Trainer','Manage assigned members, attendance, and plans'),
('3','Member','Access personal profile, attendance, and plans');

CREATE TABLE `sales` (
  `id` int NOT NULL AUTO_INCREMENT,
  `item_id` int NOT NULL,
  `quantity` int NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `sale_date` date NOT NULL,
  `customer_name` varchar(100) DEFAULT NULL,
  `payment_method` enum('cash','card','upi') DEFAULT 'cash',
  PRIMARY KEY (`id`),
  KEY `item_id` (`item_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `gym_name` varchar(100) NOT NULL,
  `tagline` varchar(255) DEFAULT NULL,
  `contact` varchar(20) DEFAULT NULL,
  `address` text,
  `email` varchar(100) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `settings` VALUES
('1','FitZone Gym','Transform Your Body, Transform Your Life','+1-555-0123','123 Fitness Street, Health City, HC 12345','info@fitzone.com','');

CREATE TABLE `suppliers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `suppliers` VALUES
('1','Fitness Supplies Inc','John Supplier','+1-555-3001','john@fitnesssupplies.com','100 Supply St, Supplier City'),
('2','Health Gear Ltd','Jane Gear','+1-555-3002','jane@healthgear.com','200 Gear Ave, Gear Town');

CREATE TABLE `trainers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `specialization` varchar(100) DEFAULT NULL,
  `contact` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `experience` int DEFAULT NULL,
  `salary` decimal(10,2) DEFAULT NULL,
  `join_date` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `trainers` VALUES
('1','John Doe','Strength Training','+1-555-1001','john.doe@fitzone.com','5','3000.00','2022-01-10'),
('2','Jane Smith','Yoga','+1-555-1002','jane.smith@fitzone.com','3','2500.00','2023-03-15'),
('3','Mike Lee','Cardio','+1-555-1003','mike.lee@fitzone.com','4','2800.00','2021-07-20');

CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role_id` int DEFAULT '3',
  `phone` varchar(20) DEFAULT NULL,
  `address` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `role_id` (`role_id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `users` VALUES
('1','Admin','admin@gym.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','1','','','2025-10-25 22:15:46'),
('2','Trainer One','trainer1@gym.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','2','+1-555-1001','','2025-10-25 22:15:46'),
('3','Member One','member1@gym.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','3','+1-555-2001','','2025-10-25 22:15:46');

CREATE TABLE `working_hours` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `date` date NOT NULL,
  `hours_worked` decimal(4,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `workout_plans` (
  `id` int NOT NULL AUTO_INCREMENT,
  `trainer_id` int NOT NULL,
  `member_id` int NOT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `trainer_id` (`trainer_id`),
  KEY `member_id` (`member_id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `workout_plans` VALUES
('1','1','1','Strength training: Monday, Wednesday, Friday','2025-10-25 22:15:46'),
('2','2','2','Yoga and flexibility: Tuesday, Thursday','2025-10-25 22:15:46'),
('3','3','3','Cardio: Daily morning sessions','2025-10-25 22:15:46');

SET FOREIGN_KEY_CHECKS = 1;
