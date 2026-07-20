-- ============================================================
-- SILOSMART MONITORING SYSTEM - COMPLETE DATABASE SCHEMA
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- ─────────────────────────────────────────────
-- ORGANISATIONS (Tenants)
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `organisations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(100) NOT NULL UNIQUE,
  `logo` varchar(500) DEFAULT NULL,
  `domain` varchar(255) DEFAULT NULL,
  `primary_color` varchar(7) DEFAULT '#00d4aa',
  `secondary_color` varchar(7) DEFAULT '#0a1628',
  `plan_id` int(11) DEFAULT NULL,
  `plan_expires_at` datetime DEFAULT NULL,
  `status` enum('active','suspended','trial','expired') DEFAULT 'trial',
  `mpesa_phone` varchar(20) DEFAULT NULL,
  `country` varchar(100) DEFAULT 'Kenya',
  `timezone` varchar(100) DEFAULT 'Africa/Nairobi',
  `max_silos` int(11) DEFAULT 5,
  `max_users` int(11) DEFAULT 10,
  `features` text DEFAULT NULL COMMENT 'JSON feature flags',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────
-- SUBSCRIPTION PLANS
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `subscription_plans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price_monthly` decimal(10,2) NOT NULL,
  `price_yearly` decimal(10,2) NOT NULL,
  `max_silos` int(11) DEFAULT 5,
  `max_users` int(11) DEFAULT 10,
  `data_retention_days` int(11) DEFAULT 90,
  `features` text DEFAULT NULL COMMENT 'JSON array of features',
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────
-- USERS
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `organisation_id` int(11) DEFAULT NULL COMMENT 'NULL = Super Admin',
  `role` enum('super_admin','tenant_admin','operator','viewer') NOT NULL DEFAULT 'viewer',
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL UNIQUE,
  `password_hash` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `national_id` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `occupation` varchar(100) DEFAULT NULL,
  `avatar` varchar(500) DEFAULT NULL,
  `facial_baseline` varchar(500) DEFAULT NULL COMMENT 'Path to biometric reference image',
  `auth_provider` enum('local','google','facebook','linkedin') DEFAULT 'local',
  `auth_provider_id` varchar(255) DEFAULT NULL,
  `otp_code` varchar(10) DEFAULT NULL,
  `otp_expires_at` datetime DEFAULT NULL,
  `otp_verified` tinyint(1) DEFAULT 0,
  `email_verified` tinyint(1) DEFAULT 0,
  `phone_verified` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `last_login_at` datetime DEFAULT NULL,
  `last_login_ip` varchar(45) DEFAULT NULL,
  `failed_login_attempts` int(11) DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `two_factor_enabled` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_org` (`organisation_id`),
  KEY `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────
-- USER SESSIONS
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `user_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `session_token` varchar(255) NOT NULL UNIQUE,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `device_fingerprint` varchar(500) DEFAULT NULL,
  `location_lat` decimal(10,8) DEFAULT NULL,
  `location_lng` decimal(11,8) DEFAULT NULL,
  `location_city` varchar(100) DEFAULT NULL,
  `location_country` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_token` (`session_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────
-- SILOS
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `silos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `organisation_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(50) NOT NULL,
  `site_name` varchar(255) DEFAULT NULL,
  `commodity_type` enum('grain_wheat','grain_maize','grain_rice','cement','fly_ash','plastics','wood_chips','chemicals','other') DEFAULT 'grain_maize',
  `capacity_tonnes` decimal(10,2) DEFAULT NULL,
  `capacity_cubic_m` decimal(10,2) DEFAULT NULL,
  `diameter_m` decimal(6,2) DEFAULT NULL,
  `height_m` decimal(6,2) DEFAULT NULL,
  `construction_material` enum('steel','concrete','plastic','fiberglass') DEFAULT 'steel',
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `status` enum('active','inactive','maintenance','critical') DEFAULT 'active',
  `image` varchar(500) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `installed_at` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_org` (`organisation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────
-- SENSORS
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `sensors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `silo_id` int(11) NOT NULL,
  `organisation_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `sensor_type` enum('level_radar','level_ultrasonic','temperature','humidity','co2','dust','pressure','vibration','load_cell','moisture') NOT NULL,
  `unit` varchar(20) NOT NULL,
  `protocol` enum('MQTT','HTTP','Modbus','OPC-UA','LoRaWAN','manual') DEFAULT 'MQTT',
  `device_id` varchar(255) DEFAULT NULL,
  `api_endpoint` varchar(500) DEFAULT NULL,
  `min_value` decimal(12,4) DEFAULT NULL,
  `max_value` decimal(12,4) DEFAULT NULL,
  `alert_low` decimal(12,4) DEFAULT NULL,
  `alert_high` decimal(12,4) DEFAULT NULL,
  `critical_low` decimal(12,4) DEFAULT NULL,
  `critical_high` decimal(12,4) DEFAULT NULL,
  `calibration_offset` decimal(10,4) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `last_reading` decimal(12,4) DEFAULT NULL,
  `last_reading_at` datetime DEFAULT NULL,
  `battery_level` int(3) DEFAULT NULL,
  `firmware_version` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_silo` (`silo_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────
-- SENSOR READINGS
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `sensor_readings` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `sensor_id` int(11) NOT NULL,
  `silo_id` int(11) NOT NULL,
  `organisation_id` int(11) NOT NULL,
  `value` decimal(12,4) NOT NULL,
  `raw_value` decimal(12,4) DEFAULT NULL,
  `quality` enum('good','uncertain','bad') DEFAULT 'good',
  `source` enum('automatic','manual','simulated') DEFAULT 'automatic',
  `recorded_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sensor` (`sensor_id`),
  KEY `idx_silo_time` (`silo_id`,`recorded_at`),
  KEY `idx_org_time` (`organisation_id`,`recorded_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────
-- ALERTS
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `alerts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `organisation_id` int(11) NOT NULL,
  `silo_id` int(11) NOT NULL,
  `sensor_id` int(11) DEFAULT NULL,
  `type` enum('threshold','rate_of_change','multi_variable','ai_anomaly','predictive','system') NOT NULL,
  `severity` enum('info','warning','critical','emergency') NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `value` decimal(12,4) DEFAULT NULL,
  `threshold` decimal(12,4) DEFAULT NULL,
  `status` enum('active','acknowledged','resolved','false_positive') DEFAULT 'active',
  `acknowledged_by` int(11) DEFAULT NULL,
  `acknowledged_at` datetime DEFAULT NULL,
  `resolved_by` int(11) DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `resolution_notes` text DEFAULT NULL,
  `notified_channels` text DEFAULT NULL COMMENT 'JSON: SMS,email,push sent',
  `ai_confidence` decimal(5,2) DEFAULT NULL,
  `triggered_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_org` (`organisation_id`),
  KEY `idx_silo` (`silo_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────
-- TASKS / WORK ORDERS
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `organisation_id` int(11) NOT NULL,
  `silo_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `type` enum('inspection','maintenance','calibration','cleaning','emergency','safety_check','other') NOT NULL,
  `priority` enum('low','medium','high','critical') DEFAULT 'medium',
  `status` enum('pending','assigned','in_progress','completed','verified','cancelled','overdue') DEFAULT 'pending',
  `assigned_to` int(11) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `due_date` datetime DEFAULT NULL,
  `started_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `checklist` text DEFAULT NULL COMMENT 'JSON checklist items',
  `completion_notes` text DEFAULT NULL,
  `photos` text DEFAULT NULL COMMENT 'JSON array of photo paths',
  `estimated_hours` decimal(5,2) DEFAULT NULL,
  `actual_hours` decimal(5,2) DEFAULT NULL,
  `cost` decimal(10,2) DEFAULT NULL,
  `alert_id` int(11) DEFAULT NULL COMMENT 'Linked alert if any',
  `is_recurring` tinyint(1) DEFAULT 0,
  `recurrence_rule` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_org` (`organisation_id`),
  KEY `idx_assigned` (`assigned_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────
-- ACTIVITY LOGS (Forensic)
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `organisation_id` int(11) DEFAULT NULL,
  `session_id` int(11) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `category` enum('auth','silo','sensor','alert','task','payment','report','user','admin','system') NOT NULL,
  `description` text DEFAULT NULL,
  `entity_type` varchar(100) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `isp` varchar(255) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `browser` varchar(100) DEFAULT NULL,
  `os` varchar(100) DEFAULT NULL,
  `device_type` enum('desktop','mobile','tablet','unknown') DEFAULT 'unknown',
  `device_fingerprint` varchar(500) DEFAULT NULL,
  `location_lat` decimal(10,8) DEFAULT NULL,
  `location_lng` decimal(11,8) DEFAULT NULL,
  `location_city` varchar(100) DEFAULT NULL,
  `location_country` varchar(100) DEFAULT NULL,
  `camera_snapshot` varchar(500) DEFAULT NULL COMMENT 'Path to encrypted snapshot',
  `is_vpn` tinyint(1) DEFAULT 0,
  `is_suspicious` tinyint(1) DEFAULT 0,
  `risk_score` int(3) DEFAULT 0,
  `extra_data` text DEFAULT NULL COMMENT 'JSON extra context',
  `logged_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_org` (`organisation_id`),
  KEY `idx_action` (`action`),
  KEY `idx_logged_at` (`logged_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────
-- PAYMENTS
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `organisation_id` int(11) NOT NULL,
  `plan_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(10) DEFAULT 'KES',
  `gateway` enum('mpesa','stripe','paypal','manual') DEFAULT 'mpesa',
  `gateway_ref` varchar(255) DEFAULT NULL COMMENT 'MpesaReceiptNumber / Stripe charge ID',
  `mpesa_phone` varchar(20) DEFAULT NULL,
  `checkout_request_id` varchar(255) DEFAULT NULL,
  `merchant_request_id` varchar(255) DEFAULT NULL,
  `status` enum('pending','completed','failed','refunded','cancelled') DEFAULT 'pending',
  `payment_type` enum('subscription','upgrade','addon') DEFAULT 'subscription',
  `billing_period` enum('monthly','yearly') DEFAULT 'monthly',
  `invoice_number` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `metadata` text DEFAULT NULL COMMENT 'JSON',
  `paid_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_org` (`organisation_id`),
  KEY `idx_gateway_ref` (`gateway_ref`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────
-- NOTIFICATIONS
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `organisation_id` int(11) DEFAULT NULL,
  `type` enum('alert','task','payment','system','announcement') NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `icon` varchar(50) DEFAULT 'bell',
  `color` varchar(7) DEFAULT '#00d4aa',
  `link` varchar(500) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `read_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_read` (`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────
-- INVENTORY / STOCK
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `inventory_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `silo_id` int(11) NOT NULL,
  `organisation_id` int(11) NOT NULL,
  `book_quantity` decimal(12,4) DEFAULT NULL COMMENT 'From flow meters',
  `physical_quantity` decimal(12,4) DEFAULT NULL COMMENT 'From level sensors',
  `variance` decimal(12,4) DEFAULT NULL,
  `variance_pct` decimal(8,4) DEFAULT NULL,
  `commodity_value` decimal(15,2) DEFAULT NULL,
  `market_price_per_tonne` decimal(10,2) DEFAULT NULL,
  `reconciled_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `recorded_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_silo` (`silo_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────
-- REPORT SCHEDULES
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `report_schedules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `organisation_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `report_type` varchar(100) NOT NULL,
  `frequency` enum('daily','weekly','monthly','quarterly') DEFAULT 'weekly',
  `format` enum('excel','pdf','csv') DEFAULT 'excel',
  `recipients` text DEFAULT NULL COMMENT 'JSON array of emails',
  `config` text DEFAULT NULL COMMENT 'JSON report config',
  `last_run_at` datetime DEFAULT NULL,
  `next_run_at` datetime DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────
-- SYSTEM SETTINGS
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `system_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL UNIQUE,
  `setting_value` text DEFAULT NULL,
  `setting_group` varchar(100) DEFAULT 'general',
  `description` text DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────
-- SUPPORT TICKETS
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `support_tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `organisation_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `status` enum('open','in_progress','resolved','closed') DEFAULT 'open',
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `assigned_to` int(11) DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────
-- SEED DATA
-- ─────────────────────────────────────────────

-- Default Super Admin
INSERT INTO `users` (`id`,`role`,`first_name`,`last_name`,`email`,`password_hash`,`phone`,`otp_verified`,`email_verified`,`phone_verified`,`is_active`) VALUES
(1,'super_admin','Super','Admin','admin@silosmart.io','$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+254700000000',1,1,1,1);
-- Default password: password

-- Default Plans
INSERT INTO `subscription_plans` (`name`,`description`,`price_monthly`,`price_yearly`,`max_silos`,`max_users`,`data_retention_days`,`features`,`sort_order`) VALUES
('Starter','Perfect for small farms with up to 5 silos',2999.00,29999.00,5,10,90,'["basic_monitoring","alerts","tasks","reports"]',1),
('Professional','Growing operations needing advanced analytics',7999.00,79999.00,20,50,365,'["basic_monitoring","alerts","tasks","reports","ai_insights","excel_reports","digital_twin","api_access"]',2),
('Enterprise','Unlimited power for large scale operations',19999.00,199999.00,999,999,1095,'["basic_monitoring","alerts","tasks","reports","ai_insights","excel_reports","digital_twin","api_access","blockchain","ar_maintenance","white_label","dedicated_support"]',3);

-- Default system settings
INSERT INTO `system_settings` (`setting_key`,`setting_value`,`setting_group`,`description`) VALUES
('site_name','SiloSmart','general','Platform name'),
('site_tagline','Intelligent Silo Management','general','Platform tagline'),
('mpesa_consumer_key','','mpesa','M-Pesa API consumer key'),
('mpesa_consumer_secret','','mpesa','M-Pesa API consumer secret'),
('mpesa_shortcode','174379','mpesa','M-Pesa paybill/till number'),
('mpesa_passkey','','mpesa','M-Pesa passkey'),
('mpesa_environment','sandbox','mpesa','sandbox or production'),
('smtp_host','','email','SMTP host'),
('smtp_port','587','email','SMTP port'),
('smtp_user','','email','SMTP username'),
('smtp_pass','','email','SMTP password'),
('smtp_from','noreply@silosmart.io','email','From email address'),
('session_lifetime','86400','security','Session lifetime in seconds'),
('max_login_attempts','5','security','Max failed login attempts before lockout'),
('otp_expiry_minutes','5','security','OTP expiry in minutes'),
('camera_snapshot_enabled','1','security','Enable background camera snapshots'),
('data_retention_days','90','general','Default data retention in days');

-- Demo Organisation
INSERT INTO `organisations` (`id`,`name`,`slug`,`plan_id`,`status`,`max_silos`,`max_users`) VALUES
(1,'AgriStore Kenya Ltd','agristore-kenya',2,'active',20,50);

-- Demo Tenant Admin
INSERT INTO `users` (`id`,`organisation_id`,`role`,`first_name`,`last_name`,`email`,`password_hash`,`phone`,`otp_verified`,`email_verified`,`phone_verified`,`is_active`) VALUES
(2,1,'tenant_admin','James','Mwangi','james@agristore.co.ke','$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+254711111111',1,1,1,1);

-- Demo Operator
INSERT INTO `users` (`id`,`organisation_id`,`role`,`first_name`,`last_name`,`email`,`password_hash`,`phone`,`otp_verified`,`email_verified`,`phone_verified`,`is_active`) VALUES
(3,1,'operator','Grace','Akinyi','grace@agristore.co.ke','$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','+254722222222',1,1,1,1);

-- Demo Silos
INSERT INTO `silos` (`id`,`organisation_id`,`name`,`code`,`site_name`,`commodity_type`,`capacity_tonnes`,`capacity_cubic_m`,`diameter_m`,`height_m`,`status`,`latitude`,`longitude`) VALUES
(1,1,'Silo Alpha','S-001','Nairobi Central Store','grain_maize',500.00,625.00,10.00,8.00,'active',-1.2921,36.8219),
(2,1,'Silo Beta','S-002','Nairobi Central Store','grain_wheat',300.00,375.00,8.00,7.50,'active',-1.2935,36.8225),
(3,1,'Silo Gamma','S-003','Mombasa Port Depot','cement',800.00,533.33,12.00,9.00,'active',-4.0435,39.6682),
(4,1,'Silo Delta','S-004','Mombasa Port Depot','grain_maize',250.00,312.50,7.00,8.00,'maintenance',-4.0440,39.6690),
(5,1,'Silo Epsilon','S-005','Kisumu Lakeside','grain_rice',400.00,500.00,9.00,8.00,'critical',-0.1022,34.7617);

-- Demo Sensors
INSERT INTO `sensors` (`id`,`silo_id`,`organisation_id`,`name`,`sensor_type`,`unit`,`alert_low`,`alert_high`,`critical_low`,`critical_high`,`last_reading`,`last_reading_at`) VALUES
(1,1,1,'Level Radar Alpha','level_radar','%',10.00,90.00,5.00,95.00,67.40,NOW()),
(2,1,1,'Temperature Alpha','temperature','°C',10.00,35.00,5.00,45.00,24.60,NOW()),
(3,1,1,'Humidity Alpha','humidity','%',40.00,70.00,30.00,80.00,58.20,NOW()),
(4,1,1,'CO2 Alpha','co2','ppm',NULL,1000.00,NULL,2000.00,420.00,NOW()),
(5,2,1,'Level Radar Beta','level_radar','%',10.00,90.00,5.00,95.00,45.80,NOW()),
(6,2,1,'Temperature Beta','temperature','°C',10.00,30.00,5.00,40.00,22.10,NOW()),
(7,3,1,'Level Radar Gamma','level_radar','%',15.00,85.00,5.00,95.00,78.90,NOW()),
(8,3,1,'Pressure Gamma','pressure','kPa',NULL,150.00,NULL,200.00,98.50,NOW()),
(9,5,1,'Temperature Epsilon','temperature','°C',10.00,32.00,5.00,40.00,38.50,NOW()),
(10,5,1,'Humidity Epsilon','humidity','%',40.00,65.00,30.00,75.00,73.80,NOW());

-- Demo Alerts
INSERT INTO `alerts` (`organisation_id`,`silo_id`,`sensor_id`,`type`,`severity`,`title`,`message`,`value`,`threshold`,`status`,`triggered_at`) VALUES
(1,5,9,'threshold','critical','High Temperature – Silo Epsilon','Temperature 38.5°C exceeds critical threshold of 32°C. Immediate action required.',38.50,32.00,'active',DATE_SUB(NOW(), INTERVAL 2 HOUR)),
(1,5,10,'threshold','warning','High Humidity – Silo Epsilon','Humidity 73.8% exceeds warning threshold of 65%.', 73.80,65.00,'active',DATE_SUB(NOW(), INTERVAL 1 HOUR)),
(1,4,NULL,'system','warning','Silo Delta Under Maintenance','Silo Delta is currently undergoing scheduled maintenance.',NULL,NULL,'acknowledged',DATE_SUB(NOW(), INTERVAL 1 DAY)),
(1,1,1,'ai_anomaly','info','Unusual Fill Pattern Detected','AI model detected an unusual consumption pattern in Silo Alpha over the last 48 hours.',NULL,NULL,'active',DATE_SUB(NOW(), INTERVAL 3 HOUR));

-- Demo Tasks
INSERT INTO `tasks` (`organisation_id`,`silo_id`,`title`,`description`,`type`,`priority`,`status`,`assigned_to`,`created_by`,`due_date`) VALUES
(1,5,'Emergency Temperature Check – Silo Epsilon','Inspect temperature sensors and check aeration fan operation immediately.','emergency','critical','assigned',3,2,DATE_ADD(NOW(), INTERVAL 2 HOUR)),
(1,4,'Monthly Maintenance – Silo Delta','Perform full structural inspection, lubricate mechanisms, test sensors.','maintenance','high','in_progress',3,2,DATE_ADD(NOW(), INTERVAL 3 DAY)),
(1,1,'Quarterly Calibration – Level Sensor S-001','Calibrate radar level sensor against manual dip measurement.','calibration','medium','pending',3,2,DATE_ADD(NOW(), INTERVAL 7 DAY));
