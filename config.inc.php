<?php
/* ============================================================================
 * FILE: config.inc.php
 * PURPOSE: Stores database connection details as PHP constants.
 *          Centralizing them here means we only edit ONE file if the DB
 *          credentials ever change.
 *
 * USED BY: db.php (which reads these constants to open the PDO connection)
 *
 * REFERENCE: Slide LISTING 14.2 — "Defining connection details via constants
 *            in a separate file (config.inc.php)"
 *
 * SECURITY NOTE: In production, this file should be placed OUTSIDE the public
 *                web directory or protected with .htaccess. For this academic
 *                project, it lives in the project folder.
 * ============================================================================ */
// Student Name: Lujain

// Hostname of the MySQL server. 'localhost' = the same machine running PHP.
define('DBHOST', 'localhost');

// Name of the database we use. Must match the database we created
// in phpMyAdmin (via the "CREATE DATABASE" statement in the sql file).
define('DBNAME', 'pearl_accessories');

// MySQL username — XAMPP's default root user.
define('DBUSER', 'root');

// MySQL password — change this if your XAMPP root password is different.
define('DBPASS', 'zainab123');

// DSN (Data Source Name) — a single string that bundles the host, database name,
// and character set. PDO needs this to know HOW to connect.
// charset=utf8mb4 is important: it lets us store Arabic letters, emojis, etc.
define('DBCONNSTRING',
"mysql:host=" . DBHOST . ";dbname=" . DBNAME . ";charset=utf8mb4");
?>
