<?php


require_once 'config.inc.php';   // Load the DBCONNSTRING, DBUSER, DBPASS constants

/**
 * Opens a new PDO connection to the MySQL database.
 *
 * @return PDO  An open database connection ready to use.
 *
 * If the connection fails (wrong password, MySQL not running, etc.),
 * the catch block kills the script with a clear error message.
 */
function getDB() {
    try {
        // LISTING 14.4 — Creating a PDO instance with connection string + credentials
        $pdo = new PDO(DBCONNSTRING, DBUSER, DBPASS);

        // Tell PDO: throw a PDOException whenever an SQL error happens.
        // (This is the recommended mode — easier to catch issues.)
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Default fetch mode: associative arrays only.
        // So $row['username'] works instead of $row[0] or $row['username'] AND $row[0].
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        return $pdo;
    } catch (PDOException $e) {
        // LISTING 14.7 — Show the error and halt the script.
        // In a real app we'd log this to a file instead of displaying it.
        die("Connection failed: " . $e->getMessage());
    }
}

// Open the connection ONCE here. Any page that includes db.php gets
// the same $pdo variable ready to use.
$pdo = getDB();
?>
