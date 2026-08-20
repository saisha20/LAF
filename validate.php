<?php
/**
 * validate.php
 * Shared SERVER-SIDE validation rules.
 * These are the source of truth for security - the JavaScript files
 * (register.js / login.js) implement the SAME rules for instant UX
 * feedback, but PHP is what actually protects the database.
 *
 * Every function returns: ['valid' => bool, 'message' => string]
 */

function validate_full_name($name) {
    $name = trim($name);

    if ($name === '') {
        return ['valid' => false, 'message' => 'Full name is required.'];
    }
    // Letters and single spaces only, cannot start with a space
    if (!preg_match('/^[A-Za-z]+( [A-Za-z]+)*$/', $name)) {
        return ['valid' => false, 'message' => 'Only letters and spaces are allowed.'];
    }
    if (strlen($name) < 2) {
        return ['valid' => false, 'message' => 'Full name is too short.'];
    }
    return ['valid' => true, 'message' => 'Looks good.'];
}

function validate_email($email) {
    $email = trim($email);

    if ($email === '') {
        return ['valid' => false, 'message' => 'Email is required.'];
    }
    // Basic shape check first - local part must start with a letter
    if (!preg_match('/^[A-Za-z][A-Za-z0-9._%+-]*@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/', $email)) {
        if (preg_match('/^\d/', $email)) {
            return ['valid' => false, 'message' => 'Email cannot start with a number.'];
        }
        return ['valid' => false, 'message' => 'Enter a valid email address.'];
    }
    // Must be the college domain
    if (!preg_match('/@kathford\.edu\.np$/i', $email)) {
        return ['valid' => false, 'message' => 'Please use your college email ending with @kathford.edu.np.'];
    }
    return ['valid' => true, 'message' => 'Looks good.'];
}

function validate_phone($phone) {
    $phone = trim($phone);

    if ($phone === '') {
        return ['valid' => false, 'message' => 'Phone number is required.'];
    }
    if (!preg_match('/^\d{10}$/', $phone)) {
        return ['valid' => false, 'message' => 'Phone number must contain exactly 10 digits.'];
    }
    if (!preg_match('/^(97|98)\d{8}$/', $phone)) {
        return ['valid' => false, 'message' => 'Phone number must start with 97 or 98.'];
    }
    return ['valid' => true, 'message' => 'Looks good.'];
}

function validate_password($password) {
    if ($password === '') {
        return ['valid' => false, 'message' => 'Password is required.'];
    }
    if (strlen($password) < 8) {
        return ['valid' => false, 'message' => 'Password must be at least 8 characters.'];
    }
    if (!preg_match('/[A-Za-z]/', $password)) {
        return ['valid' => false, 'message' => 'Password must contain at least one letter.'];
    }
    if (!preg_match('/\d/', $password)) {
        return ['valid' => false, 'message' => 'Password must contain at least one number.'];
    }
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        return ['valid' => false, 'message' => 'Password must contain at least one symbol (e.g. ! @ # $).'];
    }
    return ['valid' => true, 'message' => 'Looks good.'];
}
