<?php
require 'c:/xampp/htdocs/my/backend/config.php';
echo "Students Table:\n";
try {
    $stmt = $pdo->query("DESCRIBE students");
    while($row = $stmt->fetch()) {
        echo $row['Field'] . " (" . $row['Type'] . ")\n";
    }
} catch(Exception $e) { echo $e->getMessage() . "\n"; }

echo "\nPrescriptions Table:\n";
try {
    $stmt = $pdo->query("DESCRIBE prescriptions");
    while($row = $stmt->fetch()) {
        echo $row['Field'] . " (" . $row['Type'] . ")\n";
    }
} catch(Exception $e) { echo $e->getMessage() . "\n"; }
// unlink(__FILE__);
