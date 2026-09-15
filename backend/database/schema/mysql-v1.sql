-- Qismat MySQL/MariaDB schema v1 (reference schema; Laravel migrations remain source of truth once bootstrapped)
CREATE TABLE users (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, firebase_uid VARCHAR(128) NULL UNIQUE,
 name VARCHAR(120) NOT NULL, email VARCHAR(190) NULL UNIQUE, phone VARCHAR(30) NULL UNIQUE,
 email_verified_at TIMESTAMP NULL, phone_verified_at TIMESTAMP NULL, password VARCHAR(255) NOT NULL,
 status VARCHAR(30) NOT NULL DEFAULT 'active', last_login_at TIMESTAMP NULL, remember_token VARCHAR(100) NULL,
 created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL
);
CREATE TABLE profiles (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id BIGINT UNSIGNED NOT NULL UNIQUE,
 profile_code VARCHAR(30) NULL UNIQUE, created_by VARCHAR(30) NULL, gender VARCHAR(20) NULL, date_of_birth DATE NULL,
 height_cm SMALLINT UNSIGNED NULL, marital_status VARCHAR(40) NULL, religion VARCHAR(80) NULL, community VARCHAR(100) NULL,
 mother_tongue VARCHAR(80) NULL, country VARCHAR(80) NULL, state VARCHAR(80) NULL, city VARCHAR(80) NULL,
 education VARCHAR(180) NULL, occupation VARCHAR(180) NULL, company VARCHAR(180) NULL, annual_income BIGINT UNSIGNED NULL,
 about_me TEXT NULL, family_details JSON NULL, partner_expectations JSON NULL, profile_completion TINYINT UNSIGNED NOT NULL DEFAULT 0,
 verification_status VARCHAR(30) NOT NULL DEFAULT 'unverified', visibility VARCHAR(30) NOT NULL DEFAULT 'members', last_active_at TIMESTAMP NULL,
 created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, CONSTRAINT fk_profiles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE TABLE interests (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, sender_id BIGINT UNSIGNED NOT NULL, receiver_id BIGINT UNSIGNED NOT NULL,
 status VARCHAR(20) NOT NULL DEFAULT 'pending', message VARCHAR(500) NULL, responded_at TIMESTAMP NULL, created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
 UNIQUE KEY uniq_interest(sender_id,receiver_id), FOREIGN KEY(sender_id) REFERENCES users(id) ON DELETE CASCADE, FOREIGN KEY(receiver_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE TABLE favourites (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id BIGINT UNSIGNED NOT NULL, favourite_user_id BIGINT UNSIGNED NOT NULL, created_at TIMESTAMP NULL,
 UNIQUE KEY uniq_favourite(user_id,favourite_user_id), FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE, FOREIGN KEY(favourite_user_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE TABLE activity_logs (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id BIGINT UNSIGNED NULL, event_type VARCHAR(80) NOT NULL, entity_type VARCHAR(80) NULL,
 entity_id BIGINT UNSIGNED NULL, ip_address VARCHAR(45) NULL, platform VARCHAR(30) NULL, app_version VARCHAR(30) NULL, device VARCHAR(255) NULL,
 metadata JSON NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX idx_activity_user_time(user_id,created_at)
);
CREATE TABLE admin_audit_logs (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, admin_id BIGINT UNSIGNED NOT NULL, action VARCHAR(100) NOT NULL, target_type VARCHAR(80) NULL,
 target_id BIGINT UNSIGNED NULL, old_values JSON NULL, new_values JSON NULL, ip_address VARCHAR(45) NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE reports (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, reporter_id BIGINT UNSIGNED NOT NULL, reported_user_id BIGINT UNSIGNED NOT NULL,
 reason VARCHAR(100) NOT NULL, details TEXT NULL, status VARCHAR(30) NOT NULL DEFAULT 'open', resolved_by BIGINT UNSIGNED NULL, resolved_at TIMESTAMP NULL,
 created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL
);
