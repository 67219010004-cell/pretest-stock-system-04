-- Create Categories Table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    icon VARCHAR(50) DEFAULT '📦',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create Products Table
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    category_id INT,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    stock_quantity INT NOT NULL DEFAULT 0,
    image_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Insert Default Categories (PC Shop context)
INSERT IGNORE INTO categories (name, icon) VALUES 
('CPU', '🧠'),
('GPU', '🎮'),
('RAM', '💾'),
('Storage', '💿'),
('Motherboard', '🔌'),
('Power Supply', '⚡'),
('Case', '🖥️'),
('Cooling', '❄️'),
('Gaming Gear', '🎧');

-- Insert Sample Products
INSERT IGNORE INTO products (name, category_id, description, price, stock_quantity) VALUES 
('Intel Core i9-14900K', 1, '24 Cores, 32 Threads, up to 6.0 GHz', 24900.00, 10),
('AMD Ryzen 7 7800X3D', 1, 'Best for Gaming, 3D V-Cache', 16500.00, 15),
('NVIDIA GeForce RTX 4090', 2, '24GB GDDR6X, The Ultimate GPU', 75900.00, 3),
('Corsair Vengeance RGB 32GB', 3, 'DDR5 6000MHz CL36', 5290.00, 25),
('Samsung 990 PRO 2TB', 4, 'PCIe 4.0 NVMe SSD', 6590.00, 20);
