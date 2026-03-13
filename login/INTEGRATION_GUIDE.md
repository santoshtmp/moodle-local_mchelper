# Moodle SSO Integration Guide

## Overview

This guide explains how to integrate any external system (WordPress, Drupal, custom PHP apps, Node.js, Python, etc.) with Moodle's Single Sign-On (SSO) system using the `local_mchelpers` plugin.

## Architecture

```
+------------------+      Encrypted Data      +------------------+
|  External Site   | ------------------------>|     Moodle       |
|  (WordPress,     |                          |  (this plugin)   |
|   Drupal, etc.)  | <------------------------|                  |
|                  |      Redirect User       |                  |
+------------------+                          +------------------+
```

## Security

- **Encryption**: AES-128-CTR with shared secret
- **Hash Verification**: One-time hash prevents replay attacks
- **Session Storage**: Encrypted data stored in user_preferences table

---

## Moodle Configuration

1. Go to: **Site administration → Plugins → Local plugins → Moodle Custom Helpers**
2. Set **External Site URL**: Your external site's base URL (e.g., `https://example.com`)
3. Set **Shared Secret Key**: A strong random string (32+ characters)
4. **Important**: Use the same shared secret in your external system

---

## Data Format

### Encrypted Data Structure

The data should be URL-encoded key=value pairs:

```
moodle_user_id=123&external_user_id=456&username=john&one_time_hash=abc123xyz&login_redirect=https://moodle.example.com/my&logout_redirect=https://external.example.com&timestamp=1234567890
```

### Parameters

| Parameter | Required | Description |
|-----------|----------|-------------|
| `moodle_user_id` | Yes | Moodle user ID |
| `external_user_id` | No | User ID in external system |
| `username` | No | Username for reference |
| `one_time_hash` | Yes | Verification hash (SHA256) |
| `login_redirect` | No | URL to redirect after login |
| `logout_redirect` | No | URL to redirect after logout |
| `moodle_course_id` | No | Course ID for direct course access |
| `timestamp` | No | Unix timestamp for logging |

---

## PHP Integration Example

```php
<?php
/**
 * PHP SSO Integration Example
 * Works with: WordPress, Drupal, Laravel, or any custom PHP app
 */

class MoodleSSO {
    private $moodleUrl;
    private $sharedSecret;
    
    public function __construct($moodleUrl, $sharedSecret) {
        $this->moodleUrl = rtrim($moodleUrl, '/');
        $this->sharedSecret = $sharedSecret;
    }
    
    /**
     * Encrypt data for Moodle using AES-128-CTR
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
     * Generate login redirect URL
     */
    public function getLoginUrl($userId, $moodleUserId, $redirectUrl = '') {
        $hash = $this->generateHash($userId, $moodleUserId);
        
        // Store hash for verification
        update_user_meta($userId, 'moodle_one_time_hash', $hash);
        
        $params = [
            'login_id' => $moodleUserId,
            'veridy_code' => $hash,
        ];
        
        if (!empty($redirectUrl)) {
            $params['login_redirect'] = $redirectUrl;
        }
        
        return $this->moodleUrl . '/local/mchelpers/login/sso_login.php?' . http_build_query($params);
    }
    
    /**
     * Generate logout redirect URL
     */
    public function getLogoutUrl($userId, $moodleUserId) {
        $hash = get_user_meta($userId, 'moodle_one_time_hash', true);
        
        $params = [
            'logout_id' => $moodleUserId,
            'veridy_code' => $hash,
        ];
        
        return $this->moodleUrl . '/local/mchelpers/login/sso_login.php?' . http_build_query($params);
    }
}

// ============================================
// USAGE EXAMPLE
// ============================================

$sso = new MoodleSSO('https://moodle.example.com', 'your-shared-secret-key');

// On user login - redirect to Moodle
$moodleUserId = 123; // Get from your user mapping table
$wpUserId = 456;
header('Location: ' . $sso->getLoginUrl($wpUserId, $moodleUserId, 'https://moodle.example.com/my'));
exit;

// On user logout - logout from Moodle first
header('Location: ' . $sso->getLogoutUrl($wpUserId, $moodleUserId));
exit;
```

---

## Node.js Integration Example

```javascript
const crypto = require('crypto');

class MoodleSSO {
    constructor(moodleUrl, sharedSecret) {
        this.moodleUrl = moodleUrl.replace(/\/$/, '');
        this.sharedSecret = sharedSecret;
    }
    
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
    
    getLoginUrl(userId, moodleUserId, redirectUrl = '') {
        const hash = this.generateHash(userId, moodleUserId);
        const params = new URLSearchParams({
            login_id: moodleUserId,
            veridy_code: hash
        });
        if (redirectUrl) {
            params.append('login_redirect', redirectUrl);
        }
        return `${this.moodleUrl}/local/mchelpers/login/sso_login.php?${params.toString()}`;
    }
}

// Usage
const sso = new MoodleSSO('https://moodle.example.com', 'your-shared-secret-key');
res.redirect(sso.getLoginUrl(userId, moodleUserId, 'https://moodle.example.com/my'));
```

---

## Python Integration Example

```python
import hashlib
import base64
import os
from urllib.parse import urlencode
from Crypto.Cipher import AES
from Crypto.Util import Counter

class MoodleSSO:
    def __init__(self, moodle_url, shared_secret):
        self.moodle_url = moodle_url.rstrip('/')
        self.shared_secret = shared_secret
    
    def _encrypt_data(self, data):
        enc_key = hashlib.sha256(self.shared_secret.encode()).digest()
        iv = os.urandom(16)
        ctr = Counter.new(128, initial_value=int.from_bytes(iv, 'big'))
        cipher = AES.new(enc_key[:16], AES.MODE_CTR, counter=ctr)
        encrypted = cipher.encrypt(data.encode())
        result = base64.b64encode(encrypted + b'::' + iv.hex().encode()).decode()
        return result.replace('+', '-').replace('/', '_').replace('=', '')
    
    def _generate_hash(self, user_id, moodle_user_id):
        import time
        data = {
            'user_id': user_id,
            'moodle_user_id': moodle_user_id,
            'timestamp': int(time.time()),
            'nonce': os.urandom(16).hex()
        }
        return hashlib.sha256(str(data).encode() + self.shared_secret.encode()).hexdigest()
    
    def get_login_url(self, user_id, moodle_user_id, redirect_url=''):
        hash_value = self._generate_hash(user_id, moodle_user_id)
        params = {
            'login_id': moodle_user_id,
            'veridy_code': hash_value
        }
        if redirect_url:
            params['login_redirect'] = redirect_url
        return f'{self.moodle_url}/local/mchelpers/login/sso_login.php?{urlencode(params)}'

# Usage
sso = MoodleSSO('https://moodle.example.com', 'your-shared-secret-key')
return redirect(sso.get_login_url(user_id, moodle_user_id, 'https://moodle.example.com/my'))
```

---

## API Reference

### Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/local/mchelpers/login/sso_login.php` | POST | Store session data |
| `/local/mchelpers/login/sso_login.php?login_id={id}&veridy_code={hash}` | GET | Login user |
| `/local/mchelpers/login/sso_login.php?logout_id={id}&veridy_code={hash}` | GET | Logout user |

### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `mch_data` | POST | Encrypted user data |
| `login_id` | GET | Moodle user ID for login |
| `logout_id` | GET | Moodle user ID for logout |
| `veridy_code` | GET | One-time verification hash |
| `login_redirect` | GET | URL to redirect after login |
| `logout_redirect` | GET | URL to redirect after logout |

---

## Troubleshooting

### Common Issues

1. **"Not yet configured" error**
   - Check that shared secret is set in Moodle admin
   - Ensure the same secret is used in your external system

2. **"Invalid user" error**
   - Verify moodle_user_id exists in Moodle
   - Check user is not suspended or deleted

3. **"User not found" error**
   - User ID doesn't exist in Moodle
   - Check encryption/decryption is working correctly

4. **Hash verification failed**
   - Ensure same shared secret on both sides
   - Check hash generation algorithm matches

### Debug Mode

Enable debugging in Moodle:
1. Go to **Site administration → Development → Debugging**
2. Set **Debug messages** to **DEVELOPER**
3. Check **Display debug messages**

---

## Security Best Practices

1. **Use strong shared secrets** (32+ characters, random)
2. **Rotate secrets periodically**
3. **Use HTTPS** for all communications
4. **Log all SSO attempts** for auditing
5. **Set short expiration** on one-time hashes

---

© 2026 https://santoshmagar.com.np/
