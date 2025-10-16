# Release Process Documentation

This document explains how to create and distribute releases of the WP Na bežky Connector plugin using the automated release system.

## Overview

The release system consists of:
- **Manual Release Script** (`release.sh`) - For local testing and manual releases
- **GitHub Actions Workflow** (`.github/workflows/release.yml`) - For automated releases
- **Git Tags** - Version control and release triggers

## Prerequisites

- Git repository with the plugin code
- GitHub account with repository access
- Local development environment (for manual releases)

## Release Methods

### Method 1: Automated GitHub Releases (Recommended)

This method automatically creates releases when you push version tags to GitHub.

#### Steps:

1. **Update the plugin version** in `wp-nabezky-connector.php`:
   ```php
   Version: 1.2.0
   ```

2. **Commit your changes**:
   ```bash
   git add .
   git commit -m "Release version 1.2.0"
   ```

3. **Create and push a version tag**:
   ```bash
   git tag v1.2.0
   git push origin v1.2.0
   ```

4. **GitHub Actions will automatically**:
   - Detect the new tag
   - Extract the version from the plugin file
   - Validate that tag and plugin versions match
   - Create a zip file with the plugin
   - Create a GitHub release with the zip file attached

5. **Access your release**:
   - Go to your GitHub repository
   - Click on "Releases" in the right sidebar
   - Find your new release
   - Download the zip file or share the release URL

#### Benefits:
- ✅ Fully automated
- ✅ Version validation
- ✅ Professional release page
- ✅ Direct download links
- ✅ Release notes included

### Method 2: Manual Release Script

Use this method for testing or when you need more control over the release process.

#### Steps:

1. **Make the script executable** (if not already):
   ```bash
   chmod +x release.sh
   ```

2. **Run the release script**:
   ```bash
   ./release.sh
   ```

3. **The script will**:
   - Validate the plugin structure
   - Extract the version from the plugin file
   - Create a clean release directory
   - Copy all necessary files
   - Create a zip file named `wp-nabezky-connector-X.X.X.zip`

4. **Test the zip file**:
   - Install it on a test WordPress site
   - Verify all functionality works

5. **Upload to GitHub** (if using manual method):
   - Go to GitHub → Releases → Create a new release
   - Upload the generated zip file
   - Add release notes

#### Script Options:

```bash
# Show help
./release.sh --help

# Show current version
./release.sh --version

# Clean up release files only
./release.sh --clean

# Create release (default)
./release.sh
```

## File Structure

The release script includes these files in the zip:

```
wp-nabezky-connector/
├── wp-nabezky-connector.php    # Main plugin file
├── uninstall.php               # Uninstall script
├── README.md                   # Documentation
├── admin/                      # Admin interface
├── includes/                   # PHP classes
├── assets/                     # CSS, JS, images
└── languages/                  # Translation files
```

## Version Management

### Version Format
- Use semantic versioning: `MAJOR.MINOR.PATCH` (e.g., 1.2.0)
- Update the version in `wp-nabezky-connector.php`
- Create corresponding Git tag: `v1.2.0`

### Version Validation
The automated system validates that:
- Git tag version matches plugin version
- Plugin version is properly formatted
- All required files are present

## Distribution Options

### 1. GitHub Releases (Recommended)
- **URL Format**: `https://github.com/username/repo/releases/tag/v1.2.0`
- **Download Link**: Direct link to zip file
- **Benefits**: Professional, versioned, easy to share

### 2. Direct Download Links
- Share the GitHub release download URL directly
- Users can download without visiting the repository page

### 3. Private Distribution
- Create releases as "draft" initially
- Share with specific users
- Publish when ready for broader distribution

## Installation Instructions for Users

Provide these instructions to users downloading your plugin:

1. **Download** the zip file from the release page
2. **Go to** WordPress Admin → Plugins → Add New
3. **Click** "Upload Plugin"
4. **Choose** the downloaded zip file
5. **Click** "Install Now"
6. **Activate** the plugin
7. **Configure** at Settings → Na Bežky Map

## Troubleshooting

### Common Issues:

1. **Version mismatch error**:
   - Ensure Git tag version matches plugin version exactly
   - Example: Tag `v1.2.0` should match plugin version `1.2.0`

2. **Missing files in release**:
   - Check that all files are committed to Git
   - Verify file permissions are correct

3. **GitHub Actions failure**:
   - Check the Actions tab in GitHub for error details
   - Ensure the workflow file is in `.github/workflows/`

4. **Zip file corruption**:
   - Re-run the release script
   - Check for special characters in filenames

### Getting Help:

- Check GitHub Actions logs for automated releases
- Run `./release.sh --help` for script options
- Verify all dependencies are installed (zip, git)

## Best Practices

1. **Test before releasing**:
   - Always test the zip file on a clean WordPress installation
   - Verify all functionality works as expected

2. **Version consistency**:
   - Keep Git tags and plugin versions in sync
   - Use semantic versioning consistently

3. **Release notes**:
   - Include meaningful release notes
   - Document breaking changes
   - List new features and bug fixes

4. **Backup**:
   - Keep backups of previous versions
   - Tag stable releases appropriately

## Security Considerations

- The release process doesn't include sensitive files (API keys, etc.)
- All files are publicly visible in the repository
- Use environment variables for sensitive configuration in production

## Next Steps

After setting up the release process:

1. **Test the workflow** with a test release
2. **Document your distribution process** for your team
3. **Set up monitoring** for release downloads
4. **Consider automated testing** before releases
5. **Plan your release schedule** and communication strategy
