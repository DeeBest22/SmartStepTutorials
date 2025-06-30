<?php
/**
 * Installation script for Smart Steps Tutorial Quiz System
 * Run this file once to set up the database and initial configuration
 */

require_once 'config/database.php';

echo "<h1>Smart Steps Tutorial - Installation</h1>";

try {
    // Create database connection
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("Could not connect to database. Please check your database configuration in .env file.");
    }
    
    echo "<p>✓ Database connection successful</p>";
    
    // Create tables
    if ($database->createTables()) {
        echo "<p>✓ Database tables created successfully</p>";
    } else {
        throw new Exception("Failed to create database tables");
    }
    
    // Check if composer dependencies are installed
    if (!file_exists('vendor/autoload.php')) {
        echo "<p style='color: orange;'>⚠ Warning: Composer dependencies not found. Please run 'composer install' to install required packages.</p>";
    } else {
        echo "<p>✓ Composer dependencies found</p>";
    }
    
    // Check .env file
    if (!file_exists('.env')) {
        echo "<p style='color: orange;'>⚠ Warning: .env file not found. Please create one based on the provided template.</p>";
    } else {
        echo "<p>✓ Environment configuration file found</p>";
    }
    
    echo "<h2>Installation Complete!</h2>";
    echo "<p>Your Smart Steps Tutorial Quiz System is now ready to use.</p>";
    echo "<p><strong>Next steps:</strong></p>";
    echo "<ul>";
    echo "<li>Make sure to run 'composer install' if you haven't already</li>";
    echo "<li>Configure your database settings in the .env file</li>";
    echo "<li>Visit <a href='/teacher-register'>Teacher Registration</a> to create your first teacher account</li>";
    echo "<li>Delete this install.php file for security</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Installation failed: " . $e->getMessage() . "</p>";
    echo "<p>Please check your configuration and try again.</p>";
}
?>