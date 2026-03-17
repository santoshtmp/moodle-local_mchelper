# Moodle SSO Integration Documentation

## Local MHelpers Plugin - External System Integration

---

## Table of Contents

1. [Overview](#overview)
2. [Architecture](#architecture)
3. [Security](#security)
4. [Moodle Configuration](#moodle-configuration)
5. [WordPress Integration](#wordpress-integration)
6. [Data Format](#data-format)
7. [API Reference](#api-reference)
8. [Integration Examples](#integration-examples)
9. [Troubleshooting](#troubleshooting)
10. [Security Best Practices](#security-best-practices)

---

## Overview

This documentation explains how to integrate any external system with Moodle's Single Sign-On (SSO) system using the `local_mchelpers` plugin.

**Supported Systems:**
- WordPress (with dedicated integration class)
- Drupal
- Joomla
- Custom PHP applications
- Node.js applications
- Python applications
- Any system with OpenSSL support

**Key Features:**
- Encrypted data exchange (AES-128-CTR)
- One-time hash verification (prevents replay attacks)
- Automatic user login on WordPress login
- Automatic logout synchronization
- Course-specific redirects
- JSON response support for API integrations
- Shortcode support for WordPress (`[moodle_link]`, `[moodle_status]`)

---

## Architecture

```
┌─────────────────────────┐                          ┌─────────────────────────┐
│   External System       │                          │      Moodle LMS         │
│   (WordPress, Drupal,   │                          │   (local_mchelpers)     │
│    Custom App, etc.)    │                          │                         │
│                         │                          │                         │
│  1. User Logs In        │                          │                         │
│                         │──2. POST mch_data───────▶│  3. Store Session       │
│                         │  (encrypted)             │     Data                │
│                         │                          │                         │
│                         │◀─4. Redirect with─────── │                         │
│                         │  login_id & hash         │                         │
│                         │                          │  5. Verify & Login      │
│                         │                          │                         │
│  6. User Clicks         │                          │                         │
│     "Go to Moodle"      │─────────────────────────▶│  7. Auto-login User     │
│                         │                          │                         │
│  8. User Logs Out       │                          │                         │
│                         │──9. Logout request──────▶│  10. Logout & Redirect  │
└─────────────────────────┘                          └─────────────────────────┘
```

---

## Security

| Feature | Description |
|---------|-------------|
| **Encryption** | AES-128-CTR with shared secret key |
| **Hash Verification** | One-time SHA256 hash prevents replay attacks |
| **Session Storage** | Encrypted data stored in `user_preferences` table |
| **Secure Transmission** | All data transmitted via HTTPS |
| **Session Cleanup** | Data removed after successful login/logout |

---

## Moodle Configuration

### Step 1: Access Plugin Settings

1. Log in to Moodle as administrator
2. Navigate to: **Site administration → Plugins → Local plugins → Moodle Custom Helpers**

### Step 2: Configure SSO Settings

| Setting | Description | Example |
|---------|-------------|---------|
| **External Site URL** | Base URL of your external system | `https://example.com` |
| **Shared Secret Key** | Encryption key (32+ characters) | `xK9$mP2vN8qR5tY7wZ1aB4cD6eF0gH3j` |

### Step 3: Save Configuration

Click **Save changes** at the bottom of the page.

**Important:** Use the same Shared Secret Key in your external system configuration.

---

## WordPress Integration

The `local_mchelpers` plugin includes a dedicated WordPress integration class (`MoodleSSO_Implement`) that provides seamless SSO between WordPress and Moodle.

### Installation

1. Copy the `wp-class-moodlesso-implement.php` file to your WordPress plugin or theme
2. Include the file in your WordPress codebase:
   ```php
   require_once __DIR__ . '/wp-class-moodlesso-implement.php';
   ```
3. Initialize the integration:
   ```php
   new \Helperbox_Plugin\sso\MoodleSSO_Implement();
   ```

### Configuration

The WordPress integration uses WordPress options for configuration:

| Option | Description | Example |
|--------|-------------|---------|
| `moodle_sso_enabled` | Enable/disable SSO (1 or 0) | `1` |
| `moodle_url` | Moodle site URL | `https://moodle.example.com` |
| `moodle_shared_secret` | Shared secret key | `xK9$mP2vN8qR5tY7wZ1aB4cD6eF0gH3j` |

### Features

#### 1. Automatic Login on WordPress Login

When a user logs into WordPress, the integration automatically:
- Sends session data to Moodle via encrypted POST request
- Stores a redirect flag to prevent redirect loops
- Redirects user to Moodle for SSO login on next page load

#### 2. Automatic Logout Synchronization

When a user logs out of WordPress:
- Modifies logout URL to include Moodle SSO parameters
- Redirects user to Moodle for logout
- Cleans up stored SSO hash data

#### 3. Shortcodes

**`[moodle_link]`** - Link to Moodle with auto-login

```php
// Basic usage
[moodle_link]Go to Moodle[/moodle_link]

// With custom redirect
[moodle_link redirect_to="https://moodle.example.com/course/view.php?id=5"]View Course[/moodle_link]

// With custom class and target
[moodle_link class="btn btn-primary" target="_self"]Moodle Dashboard[/moodle_link]
```

**`[moodle_status]`** - Show Moodle connection status

```php
// Basic usage
[moodle_status]

// Shows: Moodle Connected status, User ID, Last sync time
```

#### 4. Admin Interface

Navigate to **Settings → Moodle SSO** in WordPress admin to:
- Enable/disable SSO
- Configure Moodle URL
- Set shared secret key
- View usage instructions and testing tools

### User Mapping

The WordPress integration maps users by:
1. Checking `moodle_user_id` user meta (if previously mapped)
2. You can customize the mapping logic in `get_moodle_user_id()` method

```php
// Example: Map by email
public function get_moodle_user_id($wp_user) {
    // Check existing mapping
    $moodle_user_id = get_user_meta($wp_user->ID, 'moodle_user_id', true);
    if ($moodle_user_id) {
        return intval($moodle_user_id);
    }
    
    // Custom mapping logic here
    // e.g., query Moodle database by email
    
    return false;
}
```

### Programmatic Usage

```php
// Get instance
$sso = \Helperbox_Plugin\sso\MoodleSSO::get_instance();

// Check if enabled
if ($sso->is_enabled()) {
    // Send session to Moodle
    $result = $sso->send_session_to_moodle($wp_user);
    
    // Build login URL
    $login_data = $sso->build_login_url($wp_user, 'https://moodle.example.com/my');
    if ($login_data['status']) {
        wp_redirect($login_data['moodle_login_url']);
        exit;
    }
    
    // Build logout URL
    $logout_data = $sso->build_logout_url($wp_user, 'https://wordpress.example.com');
    if ($logout_data['status']) {
        wp_redirect($logout_data['moodle_logout_url']);
        exit;
    }
}
```

---

## Data Format

### Encrypted Data Structure (`mch_data`)

The data sent to Moodle must be encrypted key=value pairs.

**Plain Text Format (Before Encryption):**
```
moodle_user_id=123&external_user_id=456&username=john&one_time_hash=abc123xyz&timestamp=1234567890
```

**Encrypted Format (What's Sent):**
```
L9xK3mP2vN8qR5tY7wZ1aB4cD6eF0gH::a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6
```

### Parameters

| Parameter | Type | Required | Description | Example |
|-----------|------|----------|-------------|---------|
| `moodle_user_id` | int | **Yes** | Moodle user ID to authenticate | `123` |
| `external_user_id` | int | No | User ID in external system | `456` |
| `username` | string | No | Username for reference/logging | `john` |
| `one_time_hash` | string | **Yes** | SHA256 verification hash | `abc123xyz...` |
| `timestamp` | int | No | Unix timestamp for logging | `1234567890` |

**Note:** The `login_redirect` and `logout_redirect` parameters are NOT sent in the encrypted data. They are passed as query parameters in the GET request URLs.

---

## API Reference

### Endpoints

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/local/mchelpers/login/sso.php` | POST | Store session data |
| `/local/mchelpers/login/sso.php` | GET | Login user (with `login_id`) |
| `/local/mchelpers/login/sso.php` | GET | Logout user (with `logout_id`) |

### Request Parameters

#### POST - Store Session Data

```
POST /local/mchelpers/login/sso.php
Content-Type: application/x-www-form-urlencoded

mch_data={encrypted_data}
```

**Response (JSON):**
```json
{
    "success": true,
    "message": "Session data stored successfully",
    "moodle_user_id": 123
}
```

#### GET - Login User

```
GET /local/mchelpers/login/sso.php?login_id={moodle_user_id}&verify_code={one_time_hash}&redirect_to={redirect_url}
```

**Query Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `login_id` | int | **Yes** | Moodle user ID to authenticate |
| `verify_code` | string | **Yes** | One-time verification hash |
| `redirect_to` | string | No | URL to redirect after successful login |

**Behavior:**
- If `redirect_to` is provided: Redirects to that URL after login
- If not provided: Redirects to Moodle dashboard (`/my`)
- On error: Redirects to external site with error parameters

#### GET - Logout User

```
GET /local/mchelpers/login/sso.php?logout_id={moodle_user_id}&verify_code={one_time_hash}
```

**Query Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `logout_id` | int | **Yes** | Moodle user ID to logout |
| `verify_code` | string | **Yes** | One-time verification hash |

**Behavior:**
- Logs out user from Moodle
- Redirects to external site (configured in Moodle settings)
- Cleans up session data

### Response Codes

| Code | Meaning |
|------|---------|
| `200 OK` | Success (JSON response) |
| `303 See Other` | Redirect (successful login/logout) |
| `401 Unauthorized` | Invalid hash or user not found |
| `500 Internal Server Error` | Configuration error |

### JSON Response Format

Add `format=json` parameter to get JSON responses instead of redirects:

```
GET /local/mchelpers/login/sso.php?login_id=123&verify_code=abc123&format=json
```

**Success Response:**
```json
{
    "status": true,
    "message": "Login successful",
    "redirect": "https://moodle.example.com/my"
}
```

**Error Response:**
```json
{
    "status": false,
    "message": "invalid_user",
    "redirect": ""
}
```

### Error Parameters

After error, user is redirected to external site with:

| Error Parameter | Description |
|-----------------|-------------|
| `mch_sso_error=not_configured` | Shared secret not configured in Moodle |
| `mch_sso_error=invalid_user` | Invalid or empty user ID |
| `mch_sso_error=user_not_found` | User doesn't exist in Moodle |
| `mch_sso_error=invalid_user_data` | User data missing or corrupted |
| `sso_error_message={message}` | Detailed error message (if available) |

---

## Integration Examples

### WordPress Integration (Recommended)

For WordPress sites, use the dedicated integration class:

```php
<?php
/**
 * WordPress Moodle SSO Integration
 * File: wp-class-moodlesso-implement.php
 */

namespace Helperbox_Plugin\sso;

// Include the integration class
require_once __DIR__ . '/wp-class-moodlesso-implement.php';

// Initialize (typically in your plugin or theme setup)
new MoodleSSO_Implement();

// The integration automatically handles:
// 1. WordPress login → Moodle session creation
// 2. WordPress logout → Moodle logout
// 3. Shortcodes: [moodle_link] and [moodle_status]
// 4. Admin settings page
```

**Admin Configuration:**
1. Go to **Settings → Moodle SSO** in WordPress admin
2. Enter Moodle URL: `https://moodle.example.com`
3. Enter Shared Secret (must match Moodle config)
4. Enable SSO checkbox
5. Save changes

**Usage in Posts/Pages:**
```
[moodle_link]Go to Moodle[/moodle_link]
[moodle_link redirect_to="https://moodle.example.com/course/view.php?id=5"]View Course[/moodle_link]
[moodle_status]
```

---

### PHP Integration (Generic)

```php
<?php
/**
 * Moodle SSO Integration - PHP Example
 * Works with: WordPress, Drupal, Laravel, Custom PHP Apps
 */

class MoodleSSO {
    private $moodleUrl;
    private $sharedSecret;
    
    /**
     * Constructor
     * 
     * @param string $moodleUrl Moodle base URL
     * @param string $sharedSecret Shared secret key (must match Moodle config)
     */
    public function __construct($moodleUrl, $sharedSecret) {
        $this->moodleUrl = rtrim($moodleUrl, '/');
        $this->sharedSecret = $sharedSecret;
    }
    
    /**
     * Encrypt data using AES-128-CTR
     */
    private function encryptData($data) {
        $encMethod = 'AES-128-CTR';
        $encKey = hash('sha256', $this->sharedSecret, true);
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($encMethod));
        $encrypted = openssl_encrypt($data, $encMethod, $encKey, 0, $iv);
        $result = base64_encode($encrypted . '::' . bin2hex($iv));
        // URL-safe base64
        return str_replace(['+', '/', '='], ['-', '_', ''], $result);
    }
    
    /**
     * Generate one-time verification hash
     */
    private function generateHash($userId, $moodleUserId) {
        $data = [
            'user_id' => $userId,
            'moodle_user_id' => $moodleUserId,
            'timestamp' => time(),
            'nonce' => bin2hex(random_bytes(16))
        ];
        return hash('sha256', serialize($data) . $this->sharedSecret);
    }

    /**
     * Store hash for later verification (implement based on your storage)
     */
    private function storeUserHash($userId, $hash) {
        // Example: Store in user meta/table
        // update_user_meta($userId, 'moodle_one_time_hash', $hash);
    }

    /**
     * Retrieve hash from database (implement based on your storage)
     */
    private function getUserHash($userId) {
        // Example: Retrieve from user meta/table
        // return get_user_meta($userId, 'moodle_one_time_hash', true);
        return '';
    }

    /**
     * Send session data to Moodle (silent login via iframe)
     */
    public function sendSessionData($userId, $moodleUserId, $username = '') {
        $hash = $this->generateHash($userId, $moodleUserId);

        // Store hash for later verification
        $this->storeUserHash($userId, $hash);

        $data = http_build_query([
            'moodle_user_id' => $moodleUserId,
            'external_user_id' => $userId,
            'username' => $username,
            'one_time_hash' => $hash,
            'timestamp' => time()
        ]);

        $encryptedData = $this->encryptData($data);
        $moodleEndpoint = $this->moodleUrl . '/local/mchelpers/login/sso.php';

        // Output hidden form and iframe
        echo '<form id="moodle-sso-form" action="' . htmlspecialchars($moodleEndpoint) . '"
              method="post" target="moodle-sso-iframe" style="display:none;">';
        echo '<input type="hidden" name="mch_data" value="' . htmlspecialchars($encryptedData) . '">';
        echo '</form>';
        echo '<iframe id="moodle-sso-iframe" name="moodle-sso-iframe"
              style="display:none;width:1px;height:1px;border:0;"></iframe>';
        echo '<script>document.getElementById("moodle-sso-form").submit();</script>';
    }

    /**
     * Generate login redirect URL
     */
    public function getLoginUrl($userId, $moodleUserId, $redirectUrl = '') {
        $hash = $this->getUserHash($userId);

        $params = [
            'login_id' => $moodleUserId,
            'verify_code' => $hash,
        ];

        if (!empty($redirectUrl)) {
            $params['redirect_to'] = $redirectUrl;
        }

        return $this->moodleUrl . '/local/mchelpers/login/sso.php?' . http_build_query($params);
    }

    /**
     * Generate logout redirect URL
     */
    public function getLogoutUrl($userId, $moodleUserId) {
        $hash = $this->getUserHash($userId);

        $params = [
            'logout_id' => $moodleUserId,
            'verify_code' => $hash,
        ];

        return $this->moodleUrl . '/local/mchelpers/login/sso.php?' . http_build_query($params);
    }
}

// ============================================
// USAGE EXAMPLES
// ============================================

$sso = new MoodleSSO(
    'https://moodle.example.com', 
    'your-shared-secret-key-at-least-32-chars'
);

// Example 1: On user login - send session data
$moodleUserId = 123; // Get from your user mapping
$externalUserId = 456;
$username = 'john.doe';
$sso->sendSessionData($externalUserId, $moodleUserId, $username);

// Example 2: Redirect to Moodle with auto-login
header('Location: ' . $sso->getLoginUrl($externalUserId, $moodleUserId, 'https://moodle.example.com/my'));
exit;

// Example 3: Redirect to specific course
$courseUrl = 'https://moodle.example.com/course/view.php?id=5';
header('Location: ' . $sso->getLoginUrl($externalUserId, $moodleUserId, $courseUrl));
exit;

// Example 4: On user logout - logout from Moodle first
header('Location: ' . $sso->getLogoutUrl($externalUserId, $moodleUserId));
exit;
```

---

### Node.js Integration

```javascript
/**
 * Moodle SSO Integration - Node.js Example
 * Requires: crypto, axios
 */

const crypto = require('crypto');
const axios = require('axios');

class MoodleSSO {
    /**
     * Constructor
     * @param {string} moodleUrl - Moodle base URL
     * @param {string} sharedSecret - Shared secret key
     */
    constructor(moodleUrl, sharedSecret) {
        this.moodleUrl = moodleUrl.replace(/\/$/, '');
        this.sharedSecret = sharedSecret;
    }
    
    /**
     * Encrypt data using AES-128-CTR
     */
    encryptData(data) {
        const encMethod = 'aes-128-ctr';
        const encKey = crypto.createHash('sha256').update(this.sharedSecret).digest();
        const iv = crypto.randomBytes(16);
        
        const cipher = crypto.createCipheriv(encMethod, encKey, iv);
        let encrypted = cipher.update(data, 'utf8', 'base64');
        encrypted += cipher.final('base64');
        
        const result = Buffer.from(encrypted + '::' + iv.toString('hex')).toString('base64');
        return result.replace(/\+/g, '-').replace(/\//g, '_').replace(/=/g, '');
    }
    
    /**
     * Generate one-time verification hash
     */
    generateHash(userId, moodleUserId) {
        const data = {
            user_id: userId,
            moodle_user_id: moodleUserId,
            timestamp: Math.floor(Date.now() / 1000),
            nonce: crypto.randomBytes(16).toString('hex')
        };
        return crypto.createHash('sha256')
            .update(JSON.stringify(data) + this.sharedSecret)
            .digest('hex');
    }
    
    /**
     * Send session data to Moodle
     */
    async sendSessionData(userId, moodleUserId, username = '') {
        const hash = this.generateHash(userId, moodleUserId);
        await this.storeUserHash(userId, hash);
        
        const params = new URLSearchParams({
            moodle_user_id: moodleUserId,
            external_user_id: userId,
            username: username,
            one_time_hash: hash,
            timestamp: Math.floor(Date.now() / 1000)
        });
        
        const encryptedData = this.encryptData(params.toString());
        const moodleEndpoint = `${this.moodleUrl}/local/mchelpers/login/sso.php`;
        
        try {
            await axios.post(moodleEndpoint, new URLSearchParams({
                mch_data: encryptedData
            }), {
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                }
            });
            return true;
        } catch (error) {
            console.error('Moodle SSO Error:', error.message);
            return false;
        }
    }
    
    /**
     * Generate login redirect URL
     */
    getLoginUrl(userId, moodleUserId, redirectUrl = '') {
        const hash = this.getUserHash(userId);
        const params = new URLSearchParams({
            login_id: moodleUserId,
            verify_code: hash
        });
        if (redirectUrl) {
            params.append('redirect_to', redirectUrl);
        }
        return `${this.moodleUrl}/local/mchelpers/login/sso.php?${params.toString()}`;
    }

    /**
     * Generate logout redirect URL
     */
    getLogoutUrl(userId, moodleUserId) {
        const hash = this.getUserHash(userId);
        const params = new URLSearchParams({
            logout_id: moodleUserId,
            verify_code: hash
        });
        return `${this.moodleUrl}/local/mchelpers/login/sso.php?${params.toString()}`;
    }
    
    // Implement these based on your storage mechanism
    async storeUserHash(userId, hash) {
        // Store in database, Redis, etc.
    }
    
    getUserHash(userId) {
        // Retrieve from database, Redis, etc.
        return '';
    }
}

// ============================================
// USAGE EXAMPLES
// ============================================

const sso = new MoodleSSO(
    'https://moodle.example.com',
    'your-shared-secret-key-at-least-32-chars'
);

// Example 1: Send session data on login
await sso.sendSessionData(userId, moodleUserId, username);

// Example 2: Redirect to Moodle
res.redirect(sso.getLoginUrl(userId, moodleUserId, 'https://moodle.example.com/my'));

// Example 3: Logout from Moodle
res.redirect(sso.getLogoutUrl(userId, moodleUserId));
```

---

### Python Integration

```python
"""
Moodle SSO Integration - Python Example
Requires: pycryptodome, requests
Install: pip install pycryptodome requests
"""

import hashlib
import base64
import os
import time
from urllib.parse import urlencode
from Crypto.Cipher import AES
from Crypto.Util import Counter
import requests


class MoodleSSO:
    """
    Moodle SSO Integration Class
    
    Attributes:
        moodle_url (str): Moodle base URL
        shared_secret (str): Shared secret key
    """
    
    def __init__(self, moodle_url, shared_secret):
        self.moodle_url = moodle_url.rstrip('/')
        self.shared_secret = shared_secret
    
    def _encrypt_data(self, data):
        """Encrypt data using AES-128-CTR"""
        enc_key = hashlib.sha256(self.shared_secret.encode()).digest()
        iv = os.urandom(16)
        
        # AES-128-CTR mode
        ctr = Counter.new(128, initial_value=int.from_bytes(iv, 'big'))
        cipher = AES.new(enc_key[:16], AES.MODE_CTR, counter=ctr)
        
        encrypted = cipher.encrypt(data.encode())
        result = encrypted + b'::' + iv.hex().encode()
        encoded = base64.b64encode(result).decode()
        
        # URL-safe base64
        return encoded.replace('+', '-').replace('/', '_').replace('=', '')
    
    def _generate_hash(self, user_id, moodle_user_id):
        """Generate one-time verification hash"""
        data = {
            'user_id': user_id,
            'moodle_user_id': moodle_user_id,
            'timestamp': int(time.time()),
            'nonce': os.urandom(16).hex()
        }
        return hashlib.sha256(str(data).encode() + self.shared_secret.encode()).hexdigest()
    
    def send_session_data(self, user_id, moodle_user_id, username=''):
        """Send session data to Moodle"""
        hash_value = self._generate_hash(user_id, moodle_user_id)
        self._store_user_hash(user_id, hash_value)
        
        params = {
            'moodle_user_id': moodle_user_id,
            'external_user_id': user_id,
            'username': username,
            'one_time_hash': hash_value,
            'timestamp': int(time.time())
        }
        
        data = urlencode(params)
        encrypted_data = self._encrypt_data(data)
        
        moodle_endpoint = f'{self.moodle_url}/local/mchelpers/login/sso.php'
        
        try:
            response = requests.post(moodle_endpoint, data={
                'mch_data': encrypted_data
            })
            return response.status_code == 200
        except Exception as e:
            print(f'Moodle SSO Error: {e}')
            return False
    
    def get_login_url(self, user_id, moodle_user_id, redirect_url=''):
        """Generate login redirect URL"""
        hash_value = self._get_user_hash(user_id)

        params = {
            'login_id': moodle_user_id,
            'verify_code': hash_value
        }

        if redirect_url:
            params['redirect_to'] = redirect_url

        return f'{self.moodle_url}/local/mchelpers/login/sso.php?{urlencode(params)}'

    def get_logout_url(self, user_id, moodle_user_id):
        """Generate logout redirect URL"""
        hash_value = self._get_user_hash(user_id)

        params = {
            'logout_id': moodle_user_id,
            'verify_code': hash_value
        }

        return f'{self.moodle_url}/local/mchelpers/login/sso.php?{urlencode(params)}'
    
    def _store_user_hash(self, user_id, hash_value):
        """Store hash in database (implement based on your storage)"""
        pass
    
    def _get_user_hash(self, user_id):
        """Retrieve hash from database (implement based on your storage)"""
        return ''


# ============================================
# USAGE EXAMPLES
# ============================================

if __name__ == '__main__':
    sso = MoodleSSO(
        'https://moodle.example.com',
        'your-shared-secret-key-at-least-32-chars'
    )
    
    # Example 1: Send session data on login
    sso.send_session_data(user_id, moodle_user_id, username)
    
    # Example 2: Redirect to Moodle (Flask example)
    # from flask import redirect
    # return redirect(sso.get_login_url(user_id, moodle_user_id, 'https://moodle.example.com/my'))
    
    # Example 3: Logout from Moodle
    # return redirect(sso.get_logout_url(user_id, moodle_user_id))
```

---

## Troubleshooting

### Common Issues

#### 1. "Not yet configured" Error

**Problem:** Shared secret not configured in Moodle

**Solution:**
1. Go to Moodle Admin → Plugins → Local plugins → Moodle Custom Helpers
2. Set **Shared Secret Key** (32+ characters)
3. Ensure the same secret is used in your external system

---

#### 2. "Invalid user" Error

**Problem:** Empty or invalid Moodle user ID

**Solution:**
- Verify `moodle_user_id` exists in Moodle database
- Check user is not suspended or deleted
- Ensure encryption/decryption is working correctly

---

#### 3. "User not found" Error

**Problem:** User ID doesn't exist in Moodle

**Solution:**
- Create the user in Moodle first
- Verify user mapping between systems
- Check the encrypted data contains correct user ID

---

#### 4. Hash Verification Failed

**Problem:** Hash mismatch between systems

**Solution:**
- Ensure same shared secret on both sides
- Verify hash generation algorithm matches
- Check for character encoding issues

---

#### 5. Login Fails Silently

**Problem:** User not logged in after redirect

**Solution:**
- Enable Moodle debug mode
- Check browser console for errors
- Verify iframe is loading correctly
- Check session storage in database

---

### Debug Mode

Enable debugging in Moodle:

1. Go to **Site administration → Development → Debugging**
2. Set **Debug messages** to **DEVELOPER**
3. Check **Display debug messages**
4. Check Moodle logs: `Site administration → Reports → Logs`

---

## Security Best Practices

### 1. Use Strong Shared Secrets

```
✅ Good:  xK9$mP2vN8qR5tY7wZ1aB4cD6eF0gH3jL5nM7pQ9rS1tU3vW5xY7zA9bC1dE3f
❌ Bad:  password123
```

**Recommendations:**
- Minimum 32 characters
- Mix of uppercase, lowercase, numbers, symbols
- Generate using: `openssl rand -base64 32`

---

### 2. Rotate Secrets Periodically

- Change shared secret every 90 days
- Update both Moodle and external systems simultaneously
- Plan maintenance window for rotation

---

### 3. Use HTTPS Only

- Always use HTTPS for all communications
- Never transmit secrets over HTTP
- Enable HSTS on both servers

---

### 4. Implement Rate Limiting

```php
// Example: Limit SSO attempts
$maxAttempts = 5;
$windowSeconds = 300; // 5 minutes

$attempts = get_sso_attempts($userId, $windowSeconds);
if ($attempts >= $maxAttempts) {
    die('Too many attempts. Please try again later.');
}
```

---

### 5. Log All SSO Attempts

```php
// Log SSO activity
error_log(sprintf(
    'SSO Login: user_id=%d, moodle_id=%d, ip=%s, time=%s',
    $userId,
    $moodleUserId,
    $_SERVER['REMOTE_ADDR'],
    date('Y-m-d H:i:s')
));
```

---

### 6. Set Hash Expiration

```php
// Generate hash with expiration
$expiration = time() + 300; // 5 minutes
$data = [
    'user_id' => $userId,
    'moodle_user_id' => $moodleUserId,
    'timestamp' => time(),
    'expiration' => $expiration,
    'nonce' => bin2hex(random_bytes(16))
];
```

---

### 7. Validate Redirect URLs

```php
// Prevent open redirect vulnerabilities
$allowedDomains = ['moodle.example.com', 'lms.example.com'];
$parsedUrl = parse_url($redirectUrl);

if (!in_array($parsedUrl['host'], $allowedDomains)) {
    $redirectUrl = 'https://moodle.example.com/my'; // Default
}
```

---

## Version Information

| Component | Version |
|-----------|---------|
| Plugin | local_mchelpers |
| Minimum Moodle | 4.2 (2023041800) |
| PHP | 7.4+ |
| OpenSSL | Required |

---

## Support

**Documentation:** See `INTEGRATION_GUIDE.md` in plugin directory

**Copyright:** © 2026 https://santoshmagar.com.np/

**License:** GNU GPL v3 or later

---

## Changelog

### Version 1.0.0 (2026)
- Initial release
- SSO integration with external systems
- AES-128-CTR encryption
- One-time hash verification
- Login/logout synchronization
- Course-specific redirects
