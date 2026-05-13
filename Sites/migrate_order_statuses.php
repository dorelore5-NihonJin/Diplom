<?php
/**
 * Migration script to update order statuses in database
 * Run this once to add support for new order statuses
 */

require_once 'config.php';

echo "Starting order status migration...\n";

try {
    // Check current column definition
    $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'status'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Current status column type: " . $column['Type'] . "\n";
    
    // Update the status column to support all new statuses
    $sql = "ALTER TABLE orders 
            MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'pending'
            COMMENT 'Order status: pending, confirmed, processing, assembling, shipping, shipped, ready_pickup, delivered, completed, cancelled'";
    
    $pdo->exec($sql);
    echo "✓ Status column updated successfully\n";
    
    // Check if there are any orders with old statuses that need updating
    $stmt = $pdo->query("SELECT DISTINCT status FROM orders");
    $existingStatuses = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "\nExisting statuses in database:\n";
    foreach ($existingStatuses as $status) {
        echo "  - $status\n";
    }
    
    // Count orders by status
    $stmt = $pdo->query("
        SELECT 
            status,
            COUNT(*) as count
        FROM orders
        GROUP BY status
        ORDER BY count DESC
    ");
    
    echo "\nOrder counts by status:\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "  - {$row['status']}: {$row['count']} orders\n";
    }
    
    echo "\n✓ Migration completed successfully!\n";
    echo "\nAll order statuses are now supported:\n";
    echo "  1. pending - Ожидает подтверждения\n";
    echo "  2. confirmed - Подтвержден\n";
    echo "  3. processing - В обработке\n";
    echo "  4. assembling - Собирается\n";
    echo "  5. shipping - В пути\n";
    echo "  6. shipped - Отправлен\n";
    echo "  7. ready_pickup - Ждет получения\n";
    echo "  8. delivered - Доставлен\n";
    echo "  9. completed - Получен\n";
    echo "  10. cancelled - Отменён\n";
    
} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
