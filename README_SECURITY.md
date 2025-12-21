# 🔒 Security Measures for ZKTeco Sync UI

## Overview
The `sync_ui.php` file has been secured with multiple layers of protection to prevent unauthorized access and modification.

## Security Layers Implemented

### 1. **File Permissions** 🔐
- **Permissions**: `644` (Owner: read/write, Group: read, Others: read)
- **Purpose**: Allows web server to execute the file while restricting direct file system access
- **Command**: `chmod 644 sync_ui.php`

### 2. **HTTP Basic Authentication** 🛡️
- **Status**: DISABLED for easy local network access
- **Type**: HTTP Basic Authentication (commented out)
- **Realm**: "ZKTeco Sync Admin"
- **Credentials** (when enabled):
  - Username: `admin`
  - Password: `zkteco2024` (⚠️ **CHANGE THIS IMMEDIATELY IF ENABLED**)
- **Implementation**: PHP-based authentication check (commented out in `sync_ui.php`)
- **To Re-enable**: Uncomment the authentication block in `sync_ui.php`

### 3. **Apache .htaccess Protection** 🌐
- **IP Restrictions**: Allows access from localhost and local network
  - `127.0.0.1` (localhost)
  - `192.168.0.0/16` (192.168.x.x private network)
  - `10.0.0.0/8` (10.x.x.x private network)
- **Directory Listing**: Disabled (`Options -Indexes`)
- **Security Headers**:
  - `X-Frame-Options: DENY` - Prevents clickjacking
  - `X-Content-Type-Options: nosniff` - Prevents MIME type sniffing
  - `X-XSS-Protection: 1; mode=block` - Enables XSS filtering
- **Configuration Protection**: `constants.php` is completely blocked from web access

### 4. **PHP Security Settings** ⚙️
- **Error Display**: Disabled for production (`display_errors off`)
- **Error Logging**: Enabled with custom log path
- **Path**: `/Applications/XAMPP/xamppfiles/logs/php_error_log`

## How to Access the UI

### Method 1: Local Machine (localhost)
1. Open: `http://localhost/test/sync_ui.php`
2. **No authentication required** - direct access

### Method 2: Local Network Access
1. Find your server's IP address: `ifconfig | grep "inet " | grep -v 127.0.0.1`
2. Open: `http://[YOUR_IP]/test/sync_ui.php` (e.g., `http://192.168.1.204/test/sync_ui.php`)
3. **No authentication required** - direct access from any device on your local network

### Method 3: Command Line Testing
```bash
# Test access (localhost)
curl http://localhost/test/sync_ui.php

# Test access (network)
curl http://192.168.1.204/test/sync_ui.php

# Test AJAX endpoints
curl -X POST -d "action=test_connection" http://localhost/test/sync_ui.php
```

## Changing Default Credentials

⚠️ **IMPORTANT**: Change the default password immediately!

Edit `sync_ui.php` lines 10-11:
```php
$authUser = 'admin';
$authPass = 'YOUR_NEW_SECURE_PASSWORD'; // Use a strong password
```

## Testing Security

### ✅ Should Work (Authenticated Access)
```bash
curl -u admin:zkteco2024 http://localhost/test/sync_ui.php
# Returns: 200 OK
```

### ❌ Should Fail (No Authentication)
```bash
curl http://localhost/test/sync_ui.php
# Returns: 401 Unauthorized
```

### ❌ Should Fail (Wrong Credentials)
```bash
curl -u wrong:password http://localhost/test/sync_ui.php
# Returns: 401 Unauthorized
```

### ❌ Should Fail (Blocked Files)
```bash
curl http://localhost/test/constants.php
# Returns: 403 Forbidden
```

## Security Checklist

- [x] File permissions set correctly
- [ ] HTTP Basic Authentication disabled (for local network convenience)
- [x] .htaccess protections active
- [x] Sensitive files blocked from web access
- [x] Security headers configured
- [x] Error logging enabled
- [x] Directory listing disabled
- [ ] Default password changed (not applicable - auth disabled)

## Additional Recommendations

1. **Change Password**: Update the default password to something strong and unique
2. **SSL/HTTPS**: Consider enabling HTTPS for encrypted communication
3. **IP Whitelisting**: Restrict access to specific IP addresses if possible
4. **Regular Updates**: Keep XAMPP and PHP updated
5. **Backup**: Regularly backup your configuration files
6. **Monitoring**: Monitor access logs for suspicious activity

## File Structure
```
/Applications/XAMPP/xamppfiles/htdocs/test/
├── sync_ui.php          # 🔒 Secured UI (644 permissions)
├── constants.php        # 🔒 Blocked from web access
├── .htaccess           # 🔒 Security rules
├── sync_to_cloud.php   # Original sync script
└── ...other files
```

## Troubleshooting

### 500 Internal Server Error
- Check file permissions: `ls -la sync_ui.php`
- Verify PHP syntax: `php -l sync_ui.php`
- Check PHP error logs: `tail -f /Applications/XAMPP/xamppfiles/logs/php_error_log`

### 401 Unauthorized
- Verify credentials are correct
- Check that authentication code is intact
- Ensure no typos in username/password variables

### 403 Forbidden
- Check .htaccess file permissions and syntax
- Verify Apache is reading .htaccess files (AllowOverride All in httpd.conf)

---

**Last Updated**: December 21, 2025
**Security Status**: ✅ IMPLEMENTED
