-- ============================================
-- GATOOSO - Database Schema
-- ============================================

CREATE DATABASE IF NOT EXISTS gatooso_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE gatooso_db;

-- --------------------------------------------
-- 1. USERS
-- --------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    email       VARCHAR(255) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    rating      DECIMAL(3,2) DEFAULT 0.00,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- --------------------------------------------
-- 2. BOARDS
-- --------------------------------------------
CREATE TABLE IF NOT EXISTS boards (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    title       VARCHAR(255) NOT NULL,
    description TEXT NULL,
    rating_icon VARCHAR(10) DEFAULT '⭐',
    created_by  INT NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- --------------------------------------------
-- 3. BOARD_USERS (pivot table)
-- --------------------------------------------
CREATE TABLE IF NOT EXISTS board_users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    board_id    INT NOT NULL,
    user_id     INT NOT NULL,
    role        ENUM('owner','admin','member') DEFAULT 'member',
    joined_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_board_user (board_id, user_id),
    FOREIGN KEY (board_id) REFERENCES boards(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE CASCADE
) ENGINE=InnoDB;

-- --------------------------------------------
-- 4. CHALLENGES
-- --------------------------------------------
CREATE TABLE IF NOT EXISTS challenges (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    board_id       INT NOT NULL,
    title          VARCHAR(255) NOT NULL,
    description    TEXT NULL,
    created_by     INT NULL,
    duration_hours INT DEFAULT 72,
    status         ENUM('queued', 'active', 'completed') DEFAULT 'queued',
    starts_at      TIMESTAMP NULL,
    ends_at        TIMESTAMP NULL,
    is_library     TINYINT(1) DEFAULT 0,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (board_id)   REFERENCES boards(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id)  ON DELETE SET NULL
) ENGINE=InnoDB;

-- --------------------------------------------
-- 5. IMAGES
-- --------------------------------------------
CREATE TABLE IF NOT EXISTS images (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    challenge_id  INT NOT NULL,
    user_id       INT NOT NULL,
    image_url     VARCHAR(500) NOT NULL,
    caption       TEXT NULL,
    rating        DECIMAL(3,2) DEFAULT 0.00,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (challenge_id) REFERENCES challenges(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)      REFERENCES users(id)      ON DELETE CASCADE
) ENGINE=InnoDB;

-- --------------------------------------------
-- 6. IMAGE_RATINGS
-- --------------------------------------------
CREATE TABLE IF NOT EXISTS image_ratings (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    image_id    INT NOT NULL,
    user_id     INT NOT NULL,
    rating      TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_image_user (image_id, user_id),
    FOREIGN KEY (image_id) REFERENCES images(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE CASCADE
) ENGINE=InnoDB;

-- --------------------------------------------
-- 7. CHALLENGE_LIBRARY (optional / premium)
-- --------------------------------------------
CREATE TABLE IF NOT EXISTS challenge_library (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    title       VARCHAR(255) NOT NULL,
    description TEXT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- --------------------------------------------
-- 8. IMAGE_COMMENTS
-- --------------------------------------------
CREATE TABLE IF NOT EXISTS image_comments (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    image_id    INT NOT NULL,
    user_id     INT NOT NULL,
    comment     TEXT NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (image_id) REFERENCES images(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE CASCADE
) ENGINE=InnoDB;

-- --------------------------------------------
-- 9. ACTIVITY_LOG
-- --------------------------------------------
CREATE TABLE IF NOT EXISTS activity_log (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    user_id       INT NOT NULL,
    board_id      INT NULL,
    action        VARCHAR(50) NOT NULL,
    reference_id  INT NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE CASCADE,
    FOREIGN KEY (board_id) REFERENCES boards(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- --------------------------------------------
-- INDEXES for performance
-- --------------------------------------------
CREATE INDEX idx_images_challenge ON images(challenge_id);
CREATE INDEX idx_images_user ON images(user_id);
CREATE INDEX idx_image_ratings_image ON image_ratings(image_id);
CREATE INDEX idx_activity_user ON activity_log(user_id);
CREATE INDEX idx_activity_board ON activity_log(board_id);
CREATE INDEX idx_challenges_board ON challenges(board_id);
CREATE INDEX idx_image_comments_image ON image_comments(image_id);
