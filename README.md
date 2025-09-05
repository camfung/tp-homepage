# Traffic Portal Link Shortener WordPress Plugin

A WordPress plugin that integrates with the Traffic Portal API to provide link shortening functionality through a shortcode interface.

## Features

- **Shortcode Integration**: Use `[traffic_portal]` to display the link creation form
- **WordPress User Authentication**: Seamlessly integrates with WordPress login system
- **API Proxy**: Secure communication with Traffic Portal API through WordPress REST API
- **Admin Dashboard**: Manage created links through WordPress admin interface
- **Responsive Design**: Mobile-friendly interface matching Traffic Portal branding
- **Security Best Practices**: WordPress nonces, input sanitization, output escaping

## Requirements

- WordPress 6.0 or higher
- PHP 8.2 or higher
- Active Traffic Portal account (optional for basic usage)

## Installation

1. Download the plugin files
2. Upload to `/wp-content/plugins/traffic-portal-link-shortener/`
3. Activate through the 'Plugins' menu in WordPress
4. Use the shortcode `[traffic_portal]` on any page or post

## Usage

### Basic Shortcode
```
[traffic_portal]
```

### With Custom Domain
```
[traffic_portal domain="trafficportal.dev"]
```

### With Theme Options
```
[traffic_portal theme="minimal"]
```

## API Integration

The plugin communicates with the Traffic Portal API at `https://dev.trfc.link` using two main endpoints:

- `GET /items/validate` - Validates key availability
- `POST /items` - Creates new short links

## Development

### Setup Development Environment

1. Install dependencies:
```bash
composer install
```

2. Run coding standards check:
```bash
composer run phpcs
```

3. Auto-fix coding standards issues:
```bash
composer run phpcbf
```

4. Run tests:
```bash
composer run test
```

### File Structure

```
traffic-portal-link-shortener/
├── traffic-portal-link-shortener.php  # Main plugin file
├── includes/                          # Plugin classes
│   ├── class-traffic-portal-admin.php
│   ├── class-traffic-portal-api.php
│   ├── class-traffic-portal-assets.php
│   ├── class-traffic-portal-database.php
│   └── class-traffic-portal-shortcode.php
├── assets/                            # CSS and JavaScript files
│   ├── css/
│   └── js/
├── templates/                         # PHP templates (if needed)
├── tests/                            # Unit tests
├── composer.json                     # Dependencies and scripts
└── phpunit.xml                       # Test configuration
```

## Security

- All user inputs are sanitized using WordPress functions
- All outputs are escaped to prevent XSS
- WordPress nonces are used for CSRF protection
- User capability checks for admin functions
- Prepared statements for database queries

## License

GPL v2 or later - see WordPress.org plugin guidelines

## Support

For issues and feature requests, please contact the Traffic Portal team.

## Changelog

### 1.0.0
- Initial release
- Shortcode integration
- Admin dashboard
- API proxy functionality
- WordPress coding standards compliance