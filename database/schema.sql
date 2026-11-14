-- =========================================================
-- uni wise — database structure
-- Import order in phpMyAdmin:  1) schema.sql   2) seed.sql   3) your real university data
-- WARNING: re-importing this file DELETES all existing tables + data (including user accounts).
-- =========================================================

CREATE DATABASE IF NOT EXISTS uniwise CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE uniwise;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS users, university_deadlines, facilities, majors, universities, portals, regions;
SET FOREIGN_KEY_CHECKS = 1;

-- World regions used for filters and the portals page
CREATE TABLE regions (
  id          TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(50)  NOT NULL UNIQUE,
  slug        VARCHAR(50)  NOT NULL UNIQUE,      -- used in URLs / anchors, e.g. 'uk-ireland'
  sort_order  TINYINT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB;

-- Central application portals (UCAS, Common App, …)
CREATE TABLE portals (
  id                 SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  region_id          TINYINT UNSIGNED NOT NULL,
  code               VARCHAR(6)   NOT NULL,       -- short badge text, e.g. 'UC'
  name               VARCHAR(80)  NOT NULL,
  scope              VARCHAR(120),                -- 'United Kingdom · all universities'
  description        VARCHAR(300),
  opens              VARCHAR(40),                 -- free text: 'September'
  typical_deadlines  VARCHAR(60),                 -- free text: 'Oct (Oxbridge) · Jan'
  website            VARCHAR(150),
  sort_order         TINYINT UNSIGNED NOT NULL DEFAULT 0,
  FOREIGN KEY (region_id) REFERENCES regions(id)
) ENGINE=InnoDB;

-- One row per university (target: top 500 QS)
CREATE TABLE universities (
  id                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug                  VARCHAR(100) NOT NULL UNIQUE,   -- URL id: university.php?u=mit
  name                  VARCHAR(150) NOT NULL,
  city                  VARCHAR(80),
  country               VARCHAR(80)  NOT NULL,
  region_id             TINYINT UNSIGNED NOT NULL,
  portal_id             SMALLINT UNSIGNED NULL,         -- NULL = apply directly
  qs_rank               SMALLINT UNSIGNED NULL,
  type                  ENUM('Public','Private') NULL,
  founded_year          SMALLINT NULL,
  students              INT UNSIGNED NULL,
  website               VARCHAR(150),
  -- Language
  teaching_language     VARCHAR(80),                    -- 'English', 'German / English'
  ielts_min             DECIMAL(2,1) NULL,
  toefl_min             SMALLINT UNSIGNED NULL,
  -- Cost (USD per year)
  tuition_domestic_usd  INT UNSIGNED NULL,
  tuition_intl_usd      INT UNSIGNED NULL,
  living_cost_usd       INT UNSIGNED NULL,
  -- Graduate outcomes
  employment_rate       DECIMAL(4,1) NULL,              -- % employed / in study within 6 months
  median_salary_usd     INT UNSIGNED NULL,
  -- Display
  crest_color           CHAR(7) NOT NULL DEFAULT '#6249d1',
  updated_at            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_rank (qs_rank),
  FOREIGN KEY (region_id) REFERENCES regions(id),
  FOREIGN KEY (portal_id) REFERENCES portals(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Majors offered (is_top + top_rank mark the "Top majors")
CREATE TABLE majors (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  university_id  INT UNSIGNED NOT NULL,
  name           VARCHAR(100) NOT NULL,
  faculty        VARCHAR(80),                 -- grouping on the profile page
  is_top         BOOLEAN NOT NULL DEFAULT FALSE,
  top_rank       TINYINT UNSIGNED NULL,       -- 1 = strongest
  rank_label     VARCHAR(20),                 -- e.g. 'Top 10' (subject ranking)
  INDEX idx_major_name (name),
  FOREIGN KEY (university_id) REFERENCES universities(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE facilities (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  university_id  INT UNSIGNED NOT NULL,
  name           VARCHAR(80) NOT NULL,        -- 'Research labs'
  description    VARCHAR(200),
  FOREIGN KEY (university_id) REFERENCES universities(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Key dates shown on a university profile
CREATE TABLE university_deadlines (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  university_id  INT UNSIGNED NOT NULL,
  label          VARCHAR(100) NOT NULL,       -- 'Application deadline'
  due_date       DATE NOT NULL,
  note           VARCHAR(150),
  FOREIGN KEY (university_id) REFERENCES universities(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Student accounts. Passwords are stored as password_hash() hashes — never plain text.
CREATE TABLE users (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  first_name     VARCHAR(50)  NOT NULL,
  last_name      VARCHAR(50)  NOT NULL,
  email          VARCHAR(190) NOT NULL UNIQUE,
  password_hash  VARCHAR(255) NULL,            -- NULL for Google-only accounts
  google_id      VARCHAR(64)  NULL UNIQUE,     -- Google's "sub" id when signed in with Google
  region_id      TINYINT UNSIGNED NULL,
  start_year     SMALLINT UNSIGNED NULL,
  language       VARCHAR(5) NULL,              -- 'en', 'es', … (site language preference)
  avatar_path    VARCHAR(120) NULL,            -- e.g. uploads/avatars/<random>.png
  bio            VARCHAR(200) NULL,
  school         VARCHAR(120) NULL,            -- current high school
  -- notification preferences (emails are not sent yet)
  notify_deadlines TINYINT(1) NOT NULL DEFAULT 1,
  notify_days      TINYINT UNSIGNED NOT NULL DEFAULT 7,   -- remind N days before
  notify_weekly    TINYINT(1) NOT NULL DEFAULT 0,
  notify_news      TINYINT(1) NOT NULL DEFAULT 0,
  -- study preferences (for future recommendations)
  pref_regions     VARCHAR(60) NULL,          -- comma-separated region ids
  pref_majors      VARCHAR(200) NULL,
  pref_budget      TINYINT UNSIGNED NULL,     -- 1–4 tuition tier
  pref_study_level ENUM('bachelor','master','phd') NULL,
  created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_login_at  TIMESTAMP NULL,
  FOREIGN KEY (region_id) REFERENCES regions(id) ON DELETE SET NULL
) ENGINE=InnoDB;
