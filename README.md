# Moodle Custom Helpers (local_mchelpers)

A comprehensive Moodle local plugin that extends your LMS with content management, SSO integration, custom metadata capabilities and many more add on features.

## 📋 Overview

**Moodle Custom Helpers** provides additional functionality for Moodle including:

- **Content Management** - Create and manage pages, FAQs, and testimonials
- **SSO Integration** - Single Sign-On with external systems (WordPress, Drupal, custom apps)
- **Custom Metadata** - Extended course and user metadata support
- **Google Translate** - Integration for multi-language support
- **Additional APIs** - Additional APIs support.

## 🚀 Features

### Content Management
- Create custom pages with rich HTML content
- Manage FAQs with question/answer pairs
- Handle testimonials with author information
- Draft/Publish workflow for content
- File/image upload support

### SSO Integration
- Encrypted data exchange with external systems
- Automatic user login from external platforms
- Support for WordPress, Drupal, and custom applications
- Secure shared secret authentication
- Session management with verification hashes

### API Endpoints
- User authentication and session management
- Course enrollment operations
- Role assignment and management
- Certificate generation
- Content CRUD operations

## 📦 Installation

### Requirements
- Moodle 4.2+ (requires version 2023041800 or higher)
- PHP 7.4+
- OpenSSL extension (for SSO encryption)

### Steps

1. **Copy the plugin** to your Moodle installation:
   ```bash
   cp -r local/mchelpers /path/to/moodle/local/mchelpers
   ```

2. **Set proper permissions**:
   ```bash
   chown -R www-data:www-data /path/to/moodle/local/mchelpers
   chmod -R 755 /path/to/moodle/local/mchelpers
   ```

3. **Install via Moodle Admin**:
   - Navigate to **Site administration > Notifications**
   - Follow the installation wizard
   - Complete the database upgrades

4. **Configure the plugin**:
   - Go to **Site administration > Plugins > Local plugins > Moodle Custom Helpers**

## ⚙️ Configuration

### General Settings

1. **Enable Plugin**: Toggle the plugin functionality on/off
2. **Content Management**: Access the content admin interface
3. **Upload Settings**: Configure maximum file upload sizes

### SSO Integration

Navigate to **SSO Integration** tab to configure:

1. **External Site URL**: Base URL of your external system
2. **Shared Secret Key**: Encryption key (minimum 32 characters recommended)
   - Generate a secure key: `openssl rand -base64 32`

#### SSO Flow

1. User logs into external system (WordPress, Drupal, etc.)
2. External system encrypts user data with shared secret
3. POST encrypted data to Moodle SSO endpoint
4. Moodle decrypts, verifies hash, and logs in user

#### API Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `local/mchelpers/login/sso.php` | POST | Store session data (`mch_data` parameter) |
| `local/mchelpers/login/sso.php?login_id={id}&veridy_code={hash}` | GET | Login user with verification hash |
| `local/mchelpers/login/sso.php?logout_id={id}&veridy_code={hash}` | GET | Logout user with verification hash |

## 📁 Directory Structure

```
local/mchelpers/
├── amd/                    # JavaScript modules
├── classes/                # PHP class files
│   ├── content/           # Content management classes
│   ├── datarepository/    # Data repository pattern implementations
│   ├── event/             # Event observers and handlers
│   ├── external/          # External API services
│   ├── form/              # Form definitions
│   ├── hooks/             # Moodle hooks implementations
│   ├── local/             # Local utilities
│   ├── output/            # Renderers and templates
│   ├── privacy/           # Privacy API compliance
│   └── task/              # Scheduled tasks
├── content/                # Content management UI
│   ├── faq/               # FAQ management
│   ├── testimonial/       # Testimonial management
│   ├── admin/             # Admin interface
│   ├── edit.php           # Content editor
│   ├── index.php          # Content listing
│   └── view.php           # Content viewer
├── db/                     # Database definitions
│   ├── access.php         # Capabilities
│   ├── events.php         # Event handlers
│   ├── hooks.php          # Hook definitions
│   ├── install.php        # Installation script
│   ├── install.xml        # Database schema
│   ├── messages.php       # Message providers
│   ├── services.php       # Web service definitions
│   ├── tasks.php          # Scheduled tasks
│   └── upgrade.php        # Upgrade scripts
├── lang/                   # Language files
│   └── en/
│       └── local_mchelpers.php
├── login/                  # SSO login handlers
│   ├── sso.php            # Main SSO endpoint
│   ├── WP_SSO_INTEGRATION_GUIDE.md
│   └── wp-class-moodlesso-implement.php
├── templates/              # Mustache templates
├── index.php               # Plugin entry point
├── lib.php                 # Library functions
├── settings.php            # Admin settings
└── version.php             # Version information
```

## 🔐 Capabilities

The plugin defines the following capabilities:

- `local/mchelpers:managecontent` - Manage content (create, edit, delete)
- `local/mchelpers:viewcontent` - View published content
- `local/mchelpers:viewdraft` - View draft content (admins only)

## 🛠️ Development

### Adding New Content Types

1. Create a new class in `classes/content/`
2. Add database tables in `db/install.xml`
3. Create form in `classes/form/`
4. Add language strings in `lang/en/local_mchelpers.php`

### Extending SSO

1. Review `login/sso.php` for authentication flow
2. Check `WP_SSO_INTEGRATION_GUIDE.md` for integration examples
3. Use `classes/local/` utilities for encryption helpers

## 📝 Version History

### Version 1.0.0 (2026-02-19)
- Initial release
- Content management (pages, FAQs, testimonials)
- SSO integration with external systems
- Custom course and user metadata
- Google Translate integration

## 📄 License

This plugin is licensed under the [GNU General Public License v3.0](http://www.gnu.org/copyleft/gpl.html).

## 👨‍💻 Author

**santoshmagar.com.np**

- Website: [https://santoshmagar.com.np/](https://santoshmagar.com.np/)
- Copyright: © 2026

## 🤝 Support

For issues, questions, or contributions:

1. Check the documentation in `/login/WP_SSO_INTEGRATION_GUIDE.md`
2. Review the SSO guide at: `/local/mchelpers/login/sso_guide.php` (admin only)
3. Contact the author through the website

## 📚 Documentation

- **SSO Integration Guide**: See `login/WP_SSO_INTEGRATION_GUIDE.md`
- **WordPress Implementation**: See `login/wp-class-moodlesso-implement.php`
- **Full API Documentation**: Available in the admin settings under SSO Integration tab

---

**Moodle Custom Helpers** - Extending Moodle with powerful content and integration capabilities.
