CREATE TABLE User (
  user_id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  email VARCHAR(120) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE Region (
  region_id INT AUTO_INCREMENT PRIMARY KEY,
  country VARCHAR(80) NOT NULL,
  city VARCHAR(80) NOT NULL,
  UNIQUE KEY uk_region (country, city)
);

CREATE TABLE Restaurant (
  rest_id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  region_id INT NOT NULL,
  rating DECIMAL(3,2) DEFAULT 0.0,
  FOREIGN KEY (region_id) REFERENCES Region(region_id),
  KEY ix_rest_region (region_id),
  KEY ix_rest_rating (rating)
);

CREATE TABLE Category (
  category_id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL UNIQUE
);

CREATE TABLE Menu (
  menu_id INT AUTO_INCREMENT PRIMARY KEY,
  rest_id INT NOT NULL,
  category_id INT NOT NULL,
  product_name VARCHAR(120) NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  calories INT,
  image_url VARCHAR(255) NOT NULL Default 'images/food_defaultImg.jpg',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (rest_id) REFERENCES Restaurant(rest_id),
  FOREIGN KEY (category_id) REFERENCES Category(category_id),
  KEY ix_menu_rest (rest_id),
  KEY ix_menu_cat (category_id),
  KEY ix_menu_price (price)
);

CREATE TABLE OrderHistory (
  order_id BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  menu_id INT NOT NULL,
  order_date DATE NOT NULL,
  quantity INT NOT NULL DEFAULT 1,
  amount DECIMAL(12,2) NOT NULL,
  FOREIGN KEY (user_id) REFERENCES User(user_id),
  FOREIGN KEY (menu_id) REFERENCES Menu(menu_id),
  KEY ix_order_date (order_date),
  KEY ix_order_user (user_id),
  KEY ix_order_menu (menu_id)
);

CREATE TABLE Review (
  review_id BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  rest_id INT NOT NULL,
  score TINYINT NOT NULL CHECK (score BETWEEN 1 AND 5),
  comment TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES User(user_id),
  FOREIGN KEY (rest_id) REFERENCES Restaurant(rest_id),
  KEY ix_review_rest (rest_id),
  KEY ix_review_user (user_id)
);

CREATE TABLE Trend (
  trend_id INT AUTO_INCREMENT PRIMARY KEY,
  category_id INT NOT NULL,
  year YEAR NOT NULL,
  season ENUM('Spring','Summer','Autumn','Winter') NOT NULL,
  popularity_index INT NOT NULL,
  FOREIGN KEY (category_id) REFERENCES Category(category_id),
  UNIQUE KEY uk_trend (category_id, year, season)
);

CREATE TABLE Favorite (
  fav_id BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  rest_id INT NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (user_id) REFERENCES User(user_id),
  FOREIGN KEY (rest_id) REFERENCES Restaurant(rest_id),

  UNIQUE KEY uk_favorite (user_id, rest_id),
  INDEX idx_fav_user (user_id),
  INDEX idx_fav_rest (rest_id)
);

CREATE TABLE Favorite (
  fav_id BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  rest_id INT NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (user_id) REFERENCES User(user_id),
  FOREIGN KEY (rest_id) REFERENCES Restaurant(rest_id),

  UNIQUE KEY uk_favorite (user_id, rest_id),
  KEY ix_fav_user (user_id),
  KEY ix_fav_rest (rest_id)
);