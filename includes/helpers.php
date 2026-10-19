<?php
// Helper functions

// Sanitize input
function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

// Format currency (Iraqi Dinar)
function formatCurrency($amount) {
    return number_format($amount, 0) . ' د.ع';
}

// Format date
function formatDate($date, $format = 'Y-m-d') {
    if (empty($date)) return '';
    $timestamp = strtotime($date);
    if ($timestamp === false) return $date; // Return original if invalid
    return date($format, $timestamp);
}

// Validate email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Generate random string
function generateRandomString($length = 10) {
    return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )), 1, $length);
}

// Get current page name
function getCurrentPage() {
    return basename($_SERVER['PHP_SELF'], '.php');
}

// Check if current page
function isCurrentPage($page) {
    return getCurrentPage() == $page;
}

// Redirect function
function redirect($url) {
    // If URL doesn't start with http:// or https://, treat as relative
    if (strpos($url, 'http://') !== 0 && strpos($url, 'https://') !== 0) {
        // If URL doesn't start with /, add current directory
        if (strpos($url, '/') !== 0) {
            $base_url = dirname($_SERVER['PHP_SELF']);
            if ($base_url == '/') $base_url = '';
            $url = $base_url . '/' . $url;
        }
    }
    header("Location: $url");
    exit();
}

// Get month name
function getMonthName($month) {
    return date('F', mktime(0, 0, 0, $month, 1));
}

// Calculate percentage
function calculatePercentage($value, $total) {
    if ($total == 0) return 0;
    return round(($value / $total) * 100, 2);
}

// Get size display order
function getSizeOrder($size) {
    $sizeOrder = ['XS' => 1, 'S' => 2, 'M' => 3, 'L' => 4, 'XL' => 5, 'XXL' => 6, 'XXXL' => 7];
    return isset($sizeOrder[$size]) ? $sizeOrder[$size] : 999;
}

// Sort sizes
function sortSizes($sizes) {
    usort($sizes, function($a, $b) {
        return getSizeOrder($a) - getSizeOrder($b);
    });
    return $sizes;
}

// Get alert class based on type
function getAlertClass($type) {
    switch($type) {
        case 'success':
            return 'alert-success';
        case 'error':
        case 'danger':
            return 'alert-danger';
        case 'warning':
            return 'alert-warning';
        case 'info':
            return 'alert-info';
        default:
            return 'alert-primary';
    }
}
