#!/bin/bash

# WordPress Plugin Release Script
# This script creates a release-ready zip file for the WP Na bežky Connector plugin

set -e  # Exit on any error

# Configuration
PLUGIN_NAME="wp-nabezky-connector"
PLUGIN_VERSION=$(grep "Version:" wp-nabezky-connector.php | sed 's/.*Version: *//' | tr -d ' ')
RELEASE_DIR="release"
ZIP_NAME="${PLUGIN_NAME}-${PLUGIN_VERSION}.zip"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Function to check if required tools are available
check_dependencies() {
    print_status "Checking dependencies..."
    
    if ! command -v zip &> /dev/null; then
        print_error "zip command not found. Please install zip utility."
        exit 1
    fi
    
    if ! command -v git &> /dev/null; then
        print_error "git command not found. Please install git."
        exit 1
    fi
    
    print_success "All dependencies found"
}

# Function to validate plugin structure
validate_plugin() {
    print_status "Validating plugin structure..."
    
    # Check if main plugin file exists
    if [ ! -f "wp-nabezky-connector.php" ]; then
        print_error "Main plugin file wp-nabezky-connector.php not found"
        exit 1
    fi
    
    # Check if version is set
    if [ -z "$PLUGIN_VERSION" ]; then
        print_error "Plugin version not found in wp-nabezky-connector.php"
        exit 1
    fi
    
    # Check if required directories exist
    local required_dirs=("admin" "includes" "assets" "languages")
    for dir in "${required_dirs[@]}"; do
        if [ ! -d "$dir" ]; then
            print_warning "Directory $dir not found"
        fi
    done
    
    print_success "Plugin structure validated"
}

# Function to clean up previous releases
cleanup() {
    print_status "Cleaning up previous releases..."
    
    if [ -d "$RELEASE_DIR" ]; then
        rm -rf "$RELEASE_DIR"
        print_success "Previous release directory removed"
    fi
    
    if [ -f "$ZIP_NAME" ]; then
        rm -f "$ZIP_NAME"
        print_success "Previous zip file removed"
    fi
}

# Function to create release directory structure
create_release_structure() {
    print_status "Creating release directory structure..."
    
    mkdir -p "$RELEASE_DIR/$PLUGIN_NAME"
    print_success "Release directory created: $RELEASE_DIR/$PLUGIN_NAME"
}

# Function to copy plugin files
copy_files() {
    print_status "Copying plugin files..."
    
    # Files to include in the release
    local files_to_copy=(
        "wp-nabezky-connector.php"
        "uninstall.php"
        "README.md"
    )
    
    # Directories to include
    local dirs_to_copy=(
        "admin"
        "includes"
        "assets"
        "languages"
    )
    
    # Copy main files
    for file in "${files_to_copy[@]}"; do
        if [ -f "$file" ]; then
            cp "$file" "$RELEASE_DIR/$PLUGIN_NAME/"
            print_status "Copied: $file"
        else
            print_warning "File not found: $file"
        fi
    done
    
    # Copy directories
    for dir in "${dirs_to_copy[@]}"; do
        if [ -d "$dir" ]; then
            cp -r "$dir" "$RELEASE_DIR/$PLUGIN_NAME/"
            print_status "Copied directory: $dir"
        else
            print_warning "Directory not found: $dir"
        fi
    done
    
    print_success "All files copied successfully"
}

# Function to create zip file
create_zip() {
    print_status "Creating zip file: $ZIP_NAME"
    
    cd "$RELEASE_DIR"
    zip -r "../$ZIP_NAME" "$PLUGIN_NAME" -x "*.DS_Store" "*.git*" "*.svn*" "*~" "*.tmp"
    cd ..
    
    if [ -f "$ZIP_NAME" ]; then
        local zip_size=$(du -h "$ZIP_NAME" | cut -f1)
        print_success "Zip file created successfully: $ZIP_NAME ($zip_size)"
    else
        print_error "Failed to create zip file"
        exit 1
    fi
}

# Function to display release information
display_release_info() {
    echo ""
    echo "=========================================="
    echo "  RELEASE INFORMATION"
    echo "=========================================="
    echo "Plugin Name: $PLUGIN_NAME"
    echo "Version: $PLUGIN_VERSION"
    echo "Zip File: $ZIP_NAME"
    echo "Size: $(du -h "$ZIP_NAME" | cut -f1)"
    echo "=========================================="
    echo ""
    echo "Next steps:"
    echo "1. Test the zip file by installing it on a WordPress site"
    echo "2. Create a GitHub release with tag: v$PLUGIN_VERSION"
    echo "3. Upload $ZIP_NAME as a release asset"
    echo "4. Update your documentation with the download link"
    echo ""
}

# Function to show help
show_help() {
    echo "Usage: $0 [OPTIONS]"
    echo ""
    echo "Options:"
    echo "  -h, --help     Show this help message"
    echo "  -v, --version  Show plugin version"
    echo "  -c, --clean    Clean up release files only"
    echo ""
    echo "This script creates a release-ready zip file for the WordPress plugin."
    echo "The zip file will be named: ${PLUGIN_NAME}-${PLUGIN_VERSION}.zip"
    echo ""
}

# Function to show version
show_version() {
    echo "Plugin Version: $PLUGIN_VERSION"
}

# Function to clean only
clean_only() {
    cleanup
    print_success "Cleanup completed"
}

# Main execution
main() {
    echo "=========================================="
    echo "  WordPress Plugin Release Script"
    echo "=========================================="
    echo ""
    
    # Parse command line arguments
    case "${1:-}" in
        -h|--help)
            show_help
            exit 0
            ;;
        -v|--version)
            show_version
            exit 0
            ;;
        -c|--clean)
            clean_only
            exit 0
            ;;
        "")
            # No arguments, proceed with release
            ;;
        *)
            print_error "Unknown option: $1"
            show_help
            exit 1
            ;;
    esac
    
    # Execute release process
    check_dependencies
    validate_plugin
    cleanup
    create_release_structure
    copy_files
    create_zip
    display_release_info
    
    print_success "Release process completed successfully!"
}

# Run main function
main "$@"
