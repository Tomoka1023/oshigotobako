CREATE DATABASE IF NOT EXISTS oshigotobako
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;

USE oshigotobako;

-- =========================
-- ユーザー
-- =========================
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  email VARCHAR(255) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================
-- 顧客
-- =========================
CREATE TABLE customers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  company_name VARCHAR(100),
  email VARCHAR(255),
  phone VARCHAR(50),
  address TEXT,
  memo TEXT,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================
-- 案件
-- =========================
CREATE TABLE deals (
  id INT AUTO_INCREMENT PRIMARY KEY,
  customer_id INT NOT NULL,
  title VARCHAR(150) NOT NULL,
  description TEXT,
  status ENUM('new', 'proposal', 'estimate', 'ordered', 'completed', 'lost') NOT NULL DEFAULT 'new',
  amount INT DEFAULT 0,
  due_date DATE,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  CONSTRAINT fk_deals_customer
    FOREIGN KEY (customer_id)
    REFERENCES customers(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================
-- タスク
-- =========================
CREATE TABLE tasks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  deal_id INT,
  customer_id INT,
  title VARCHAR(150) NOT NULL,
  description TEXT,
  due_date DATE,
  status ENUM('todo', 'doing', 'done') NOT NULL DEFAULT 'todo',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  CONSTRAINT fk_tasks_deal
    FOREIGN KEY (deal_id)
    REFERENCES deals(id)
    ON DELETE SET NULL,

  CONSTRAINT fk_tasks_customer
    FOREIGN KEY (customer_id)
    REFERENCES customers(id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================
-- 添付ファイル
-- =========================
CREATE TABLE attachments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  target_type VARCHAR(50) NOT NULL,
  target_id INT NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  saved_name VARCHAR(255) NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  mime_type VARCHAR(100) DEFAULT NULL,
  file_size INT DEFAULT NULL,
  uploaded_by INT DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================
-- 操作ログ
-- =========================
CREATE TABLE operation_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  action VARCHAR(50) NOT NULL,
  target_type VARCHAR(50) DEFAULT NULL,
  target_id INT DEFAULT NULL,
  target_name VARCHAR(255) DEFAULT NULL,
  message TEXT NOT NULL,
  ip_address VARCHAR(45) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  CONSTRAINT fk_operation_logs_user
    FOREIGN KEY (user_id)
    REFERENCES users(id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;