<?php
require 'config.php';

echo "<h2>Database Initialization Utility</h2>";

try {
    // Read the SQL file
    $sql_file = __DIR__ . '/../database/database.sql';
    if (!file_exists($sql_file)) {
        die("Error: SQL file not found at $sql_file");
    }

    $sql = file_get_contents($sql_file);

    // XAMPP usually allows multi-queries or we can split them
    // For safety with PDO, we split by semicolon (not perfect but works for this schema)
    $queries = explode(';', $sql);

    foreach ($queries as $query) {
        $q = trim($query);
        if (!empty($q)) {
            $pdo->exec($q);
        }
    }

    echo "<div style='color: green; padding: 10px; border: 1px solid green; background: #e6ffed;'>
            <strong>Success:</strong> Database tables created and seed data inserted successfully!
          </div>";
    echo "<p><a href='../frontend/login.php'>Go to Login Page</a></p>";

} catch (PDOException $e) {
    echo "<div style='color: red; padding: 10px; border: 1px solid red; background: #fff5f5;'>
            <strong>Database Error:</strong> " . htmlspecialchars($e->getMessage()) . "
          </div>";
    echo "<p>Tip: Make sure the database <strong>" . htmlspecialchars($dbname) . "</strong> exists or the user has CREATE permissions.</p>";
}
?>
