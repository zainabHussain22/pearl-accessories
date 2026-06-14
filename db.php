<?php
/* ============================================================================
 * FILE: db.php
 * PURPOSE: Opens a single PDO database connection that every page can use.
 *          Wraps the connection in try/catch so we handle errors gracefully
 *          instead of showing PHP warnings to the user.
 *
 * HOW IT WORKS:
 *   1. Reads connection constants from config.inc.php
 *   2. Creates a PDO object
 *   3. Configures PDO to throw exceptions on errors (easier to debug)
 *   4. Configures PDO to return rows as associative arrays by default
 *   5. Stores the connection in $pdo for the calling page to use
 *
 * USED BY: Almost every PHP file uses `include 'db.php';` near the top
 *
 * REFERENCE: Slide LISTING 14.7 — "Handling connection errors with PDO"
 * ============================================================================ */

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
