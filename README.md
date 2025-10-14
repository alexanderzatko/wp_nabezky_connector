# WP Na bežky! Connector

A WordPress plugin to connect with Na bežky! services.

## Features

- Admin settings page for API configuration
- Database table creation for data storage
- Frontend and admin assets management
- AJAX connection testing
- Translation ready

## Installation

1. Upload the plugin files to `/wp-content/plugins/wp-nabezky-connector/` directory
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Go to Settings > Na bežky! Connector to configure the plugin

## Configuration

1. Navigate to **Settings > Na bežky! Connector** in your WordPress admin
2. Enter your API key and API URL
3. Enable the plugin
4. Test the connection to verify your settings

## Development Setup

### Prerequisites

- WordPress development environment (local or staging)
- PHP 7.4 or higher
- MySQL 5.6 or higher

### Local Development Options

#### Option 1: Local by Flywheel
1. Download and install [Local by Flywheel](https://localwp.com/)
2. Create a new WordPress site
3. Copy this plugin to `/wp-content/plugins/` directory
4. Activate the plugin

#### Option 2: XAMPP/MAMP
1. Install [XAMPP](https://www.apachefriends.org/) or [MAMP](https://www.mamp.info/)
2. Download WordPress and set up a local site
3. Copy this plugin to `/wp-content/plugins/` directory
4. Activate the plugin

#### Option 3: Docker
1. Use WordPress Docker image
2. Mount this plugin directory
3. Access via localhost

### File Structure

```
wp-nabezky-connector/
├── wp-nabezky-connector.php    # Main plugin file
├── admin/
│   └── admin-page.php          # Admin settings page
├── assets/
│   ├── css/
│   │   ├── admin.css          # Admin styles
│   │   └── frontend.css       # Frontend styles
│   ├── js/
│   │   ├── admin.js           # Admin JavaScript
│   │   └── frontend.js        # Frontend JavaScript
│   └── images/                # Plugin images
├── includes/                  # Additional PHP classes
├── languages/                 # Translation files
└── README.md                  # This file
```

### Development Workflow

1. **Make changes** to plugin files
2. **Test locally** using your WordPress development environment
3. **Debug** using WordPress debug tools
4. **Version control** your changes with Git

### Debugging

Enable WordPress debugging by adding these lines to your `wp-config.php`:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### Testing

- Test plugin activation/deactivation
- Test admin settings page functionality
- Test API connection
- Test frontend functionality
- Test with different WordPress themes

## API Integration

The plugin is set up to integrate with Na bežky! services. You'll need to:

1. Obtain API credentials from Na bežky!
2. Configure the API endpoint URL
3. Implement specific API calls based on your requirements

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License

GPL v2 or later

## Support

For support and questions, please contact the plugin author.
