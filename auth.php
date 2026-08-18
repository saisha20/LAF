<?php
/**
 * auth.php
 * Session handling + authorization helpers.
 * Include this at the TOP of any page that needs to know who is
 * logged in, or that must be protected from unauthorized access.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isLoggedIn() && $_SESSION['role'] === 'admin';
}

/**
 * Call at the top of any page only logged-in users may see.
 * Redirects to login.php if not logged in.
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Call at the top of admin-only pages.
 * A normal user typing the URL directly gets redirected away,
 * not just hidden with CSS/JS.
 */
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: user_dashboard.php');
        exit;
    }
}