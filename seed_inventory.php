<?php
require 'db.php';

$productsToAdd = [
    ['AMD Ryzen 9 7950X', 1, '16 Cores, 32 Threads, up to 5.7 GHz', 19900.00, 8],
    ['Intel Core i7-14700K', 1, '20 Cores, 28 Threads, up to 5.6 GHz', 15900.00, 12],
    ['NVIDIA RTX 4080 Super', 2, '16GB GDDR6X, High Performance GPU', 42900.00, 5],
    ['AMD Radeon RX 7900 XTX', 2, '24GB GDDR6, Team Red Flagship', 36500.00, 6],
    ['G.Skill Trident Z5 64GB DDR5', 3, '6400MHz CL32, RGB High Capacity', 9800.00, 10],
    ['Kingston FURY Beast 16GB', 3, 'DDR5 5200MHz, Entry Level DDR5', 2150.00, 30],
    ['WD Black SN850X 1TB', 4, 'Heatsink Model, up to 7300MB/s', 3890.00, 15],
    ['ASUS ROG STRIX Z790-E', 5, 'WiFi 6E, PCIe 5.0, Top Tier Intel Board', 18500.00, 4],
    ['MSI MAG B650 Tomahawk', 5, 'Great Value AM5 Motherboard', 7900.00, 12],
    ['Seasonic Focus GX-850', 6, '850W 80+ Gold Fully Modular', 4500.00, 10],
    ['Corsair RM1000e', 6, '1000W 80+ Gold, ATX 3.0 Ready', 6200.00, 8],
    ['Lian Li Lancool 216', 7, 'Airflow focused mid-tower case', 3600.00, 5],
    ['NZXT H9 Flow', 7, 'Dual-Chamber Mid-Tower ATX Case', 5900.00, 3],
    ['DeepCool AK620', 8, 'High-Performance Dual Tower Air Cooler', 2200.00, 20],
    ['Thermalright Peerless Assassin', 8, 'Best Value Air Cooler', 1450.00, 25],
    ['Logitech G502 X Plus', 9, 'Lightspeed Wireless RGB Mouse', 5100.00, 15],
    ['SteelSeries Apex Pro', 9, 'OmniPoint 2.0 Adjustable Switches', 8500.00, 7]
];

try {
    $stmt = $pdo->prepare("INSERT IGNORE INTO products (name, category_id, description, price, stock_quantity) VALUES (?, ?, ?, ?, ?)");
    
    $count = 0;
    foreach ($productsToAdd as $p) {
        $stmt->execute($p);
        $count++;
    }
    
    echo "Successfully processed $count items.";
} catch (Exception $e) {
    echo "Seed Error: " . $e->getMessage();
}
?>
