<?php
/**
 * Constants file for the ZKTeco Attendance System
 * This file contains all global constants used across the application
 */

// Security token for API communication with Laravel server
define('SECRET_TOKEN', 'SECRET564FDG');

// ZKTeco device default configuration
define('DEFAULT_DEVICE_IP', '192.168.1.253');
define('DEFAULT_DEVICE_PORT', 4370);

// Laravel API configuration
define('CLOUD_BASE_URL', 'http://127.0.0.1:8000');
// define('CLOUD_BASE_URL', 'https://hrm.applabltd.co'); // Production URL

// Construct cloud URL with token
define('CLOUD_URL', CLOUD_BASE_URL . '/api/zk/receive-logs?token=' . urlencode(SECRET_TOKEN));
