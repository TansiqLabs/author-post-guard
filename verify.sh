#!/bin/bash
#
# Author Post Guard - Verification Script
# Version: 1.1.0
# Purpose: Verify plugin integrity and security before deployment
#

echo "=================================================="
echo "  Author Post Guard - Pre-Deployment Verification"
echo "  Version 1.1.0"
echo "=================================================="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Counters
PASSED=0
FAILED=0
WARNINGS=0

# Function to print test result
print_result() {
    if [ $1 -eq 0 ]; then
        echo -e "${GREEN}✓ PASS${NC} - $2"
        ((PASSED++))
    else
        echo -e "${RED}✗ FAIL${NC} - $2"
        ((FAILED++))
    fi
}

print_warning() {
    echo -e "${YELLOW}⚠ WARNING${NC} - $1"
    ((WARNINGS++))
}

echo "1. Checking File Structure..."
echo "-----------------------------------"

# Check main plugin file
if [ -f "author-post-guard.php" ]; then
    print_result 0 "Main plugin file exists"
else
    print_result 1 "Main plugin file missing"
fi

# Check required directories
if [ -d "inc" ]; then
    print_result 0 "inc/ directory exists"
else
    print_result 1 "inc/ directory missing"
fi

if [ -d "assets" ]; then
    print_result 0 "assets/ directory exists"
else
    print_result 1 "assets/ directory missing"
fi

# Check class files
if [ -f "inc/class-settings.php" ]; then
    print_result 0 "class-settings.php exists"
else
    print_result 1 "class-settings.php missing"
fi

if [ -f "inc/class-notifications.php" ]; then
    print_result 0 "class-notifications.php exists"
else
    print_result 1 "class-notifications.php missing"
fi

if [ -f "inc/class-updater.php" ]; then
    print_result 0 "class-updater.php exists"
else
    print_result 1 "class-updater.php missing"
fi

# Check asset files
if [ -f "assets/admin-style.css" ]; then
    print_result 0 "admin-style.css exists"
else
    print_result 1 "admin-style.css missing"
fi

if [ -f "assets/admin-script.js" ]; then
    print_result 0 "admin-script.js exists"
else
    print_result 1 "admin-script.js missing"
fi

if [ -f "assets/logo.svg" ]; then
    print_result 0 "logo.svg exists"
else
    print_result 1 "logo.svg missing"
fi

echo ""
echo "2. Checking Documentation..."
echo "-----------------------------------"

# Check documentation files
for doc in README.md LICENSE CHANGELOG.md TESTING.md DEPLOYMENT.md SECURITY.md FEATURES.md QUICKSTART.md; do
    if [ -f "$doc" ]; then
        print_result 0 "$doc exists"
    else
        print_result 1 "$doc missing"
    fi
done

echo ""
echo "3. Checking PHP Syntax..."
echo "-----------------------------------"

# Check PHP syntax for all PHP files
php -l author-post-guard.php > /dev/null 2>&1
print_result $? "author-post-guard.php syntax"

php -l inc/class-settings.php > /dev/null 2>&1
print_result $? "class-settings.php syntax"

php -l inc/class-notifications.php > /dev/null 2>&1
print_result $? "class-notifications.php syntax"

php -l inc/class-updater.php > /dev/null 2>&1
print_result $? "class-updater.php syntax"

echo ""
echo "4. Checking Version Consistency..."
echo "-----------------------------------"

# Extract version from main plugin file
PLUGIN_VERSION=$(grep "Version:" author-post-guard.php | awk '{print $3}')
CONSTANT_VERSION=$(grep "define( 'APG_VERSION'," author-post-guard.php | cut -d"'" -f4)

echo "Plugin Header Version: $PLUGIN_VERSION"
echo "PHP Constant Version: $CONSTANT_VERSION"

if [ "$PLUGIN_VERSION" == "$CONSTANT_VERSION" ]; then
    print_result 0 "Version numbers match"
else
    print_result 1 "Version numbers mismatch"
fi

# Check if version is 1.1.0
if [ "$PLUGIN_VERSION" == "1.1.0" ]; then
    print_result 0 "Version is 1.1.0"
else
    print_warning "Version is not 1.1.0 (found: $PLUGIN_VERSION)"
fi

echo ""
echo "5. Checking Security Features..."
echo "-----------------------------------"

# Check for manage_options capability checks
CAPABILITY_COUNT=$(grep -r "manage_options" --include="*.php" . | wc -l)
echo "Found $CAPABILITY_COUNT capability checks"

if [ $CAPABILITY_COUNT -ge 6 ]; then
    print_result 0 "Sufficient capability checks ($CAPABILITY_COUNT found)"
else
    print_warning "Few capability checks ($CAPABILITY_COUNT found, expected 6+)"
fi

# Check for nonce verification
NONCE_COUNT=$(grep -r "check_ajax_referer\|wp_verify_nonce" --include="*.php" . | wc -l)
echo "Found $NONCE_COUNT nonce verifications"

if [ $NONCE_COUNT -ge 1 ]; then
    print_result 0 "Nonce verification present ($NONCE_COUNT found)"
else
    print_result 1 "No nonce verification found"
fi

# Check for sanitization functions
SANITIZE_COUNT=$(grep -r "sanitize_text_field\|esc_html\|esc_url" --include="*.php" . | wc -l)
echo "Found $SANITIZE_COUNT sanitization calls"

if [ $SANITIZE_COUNT -ge 10 ]; then
    print_result 0 "Good sanitization coverage ($SANITIZE_COUNT calls)"
else
    print_warning "Limited sanitization ($SANITIZE_COUNT calls, more recommended)"
fi

# Check for block_direct_access function
if grep -q "block_direct_access" author-post-guard.php; then
    print_result 0 "Direct URL blocking function present"
else
    print_result 1 "Direct URL blocking function missing"
fi

# Check for admin_only_pages array
if grep -q "admin_only_pages" author-post-guard.php; then
    print_result 0 "Admin-only pages array present"
else
    print_result 1 "Admin-only pages array missing"
fi

echo ""
echo "6. Checking Code Quality..."
echo "-----------------------------------"

# Check for PHP errors/warnings
ERROR_KEYWORDS="parse error|fatal error|notice:|warning:"
ERROR_COUNT=$(grep -ri "$ERROR_KEYWORDS" --include="*.php" . | wc -l)

if [ $ERROR_COUNT -eq 0 ]; then
    print_result 0 "No obvious PHP errors in comments"
else
    print_warning "Found $ERROR_COUNT lines with error keywords (may be comments)"
fi

# Check for debugging code
DEBUG_CODE=$(grep -r "var_dump\|print_r" --include="*.php" . | grep -v "error_log" | wc -l)

if [ $DEBUG_CODE -eq 0 ]; then
    print_result 0 "No debug code (var_dump/print_r) found"
else
    print_warning "Found $DEBUG_CODE debug statements"
fi

# Check for commented code (very rough estimate)
COMMENT_RATIO=$(grep -r "^\s*//\|^\s*/\*" --include="*.php" . | wc -l)
echo "Found approximately $COMMENT_RATIO comment lines"

echo ""
echo "7. Checking Asset Files..."
echo "-----------------------------------"

# Check CSS file size
if [ -f "assets/admin-style.css" ]; then
    CSS_SIZE=$(wc -c < assets/admin-style.css)
    CSS_LINES=$(wc -l < assets/admin-style.css)
    echo "CSS: $CSS_LINES lines, $CSS_SIZE bytes"
    
    if [ $CSS_LINES -ge 700 ]; then
        print_result 0 "CSS file has substantial content"
    else
        print_warning "CSS file seems small ($CSS_LINES lines)"
    fi
fi

# Check JS file size
if [ -f "assets/admin-script.js" ]; then
    JS_SIZE=$(wc -c < assets/admin-script.js)
    JS_LINES=$(wc -l < assets/admin-script.js)
    echo "JavaScript: $JS_LINES lines, $JS_SIZE bytes"
    
    if [ $JS_LINES -ge 150 ]; then
        print_result 0 "JavaScript file has substantial content"
    else
        print_warning "JavaScript file seems small ($JS_LINES lines)"
    fi
fi

echo ""
echo "8. Checking for Common Issues..."
echo "-----------------------------------"

# Check for hardcoded URLs (security concern)
HARDCODED_URLS=$(grep -r "http://\|https://" --include="*.php" . | grep -v "example.com\|//\s" | wc -l)

if [ $HARDCODED_URLS -lt 5 ]; then
    print_result 0 "Few hardcoded URLs ($HARDCODED_URLS found)"
else
    print_warning "Many hardcoded URLs ($HARDCODED_URLS found)"
fi

# Check for eval() usage (security risk)
EVAL_COUNT=$(grep -r "\beval\s*(" --include="*.php" . | wc -l)

if [ $EVAL_COUNT -eq 1 ]; then
    print_warning "Found $EVAL_COUNT eval() usage (in custom PHP feature)"
elif [ $EVAL_COUNT -eq 0 ]; then
    print_result 0 "No eval() usage"
else
    print_warning "Found $EVAL_COUNT eval() usages"
fi

# Check for global variables
GLOBAL_COUNT=$(grep -r "global \$" --include="*.php" . | wc -l)
echo "Found $GLOBAL_COUNT global variable usages"

# Check for database queries without prepare
UNSAFE_QUERY=$(grep -r "->query(" --include="*.php" . | wc -l)

if [ $UNSAFE_QUERY -eq 0 ]; then
    print_result 0 "No potentially unsafe database queries"
else
    print_warning "Found $UNSAFE_QUERY ->query() calls (verify they're safe)"
fi

echo ""
echo "9. Checking File Permissions..."
echo "-----------------------------------"

# Check if files are readable
UNREADABLE=$(find . -type f ! -readable 2>/dev/null | wc -l)

if [ $UNREADABLE -eq 0 ]; then
    print_result 0 "All files are readable"
else
    print_result 1 "Found $UNREADABLE unreadable files"
fi

# Check for executable PHP files (should not be needed)
EXECUTABLE_PHP=$(find . -name "*.php" -executable 2>/dev/null | wc -l)

if [ $EXECUTABLE_PHP -eq 0 ]; then
    print_result 0 "No executable PHP files (good)"
else
    print_warning "Found $EXECUTABLE_PHP executable PHP files"
fi

echo ""
echo "10. Checking Package Size..."
echo "-----------------------------------"

# Calculate total size
TOTAL_SIZE=$(du -sh . | awk '{print $1}')
echo "Total plugin size: $TOTAL_SIZE"

# Count total files
TOTAL_FILES=$(find . -type f ! -path "./.git/*" | wc -l)
echo "Total files: $TOTAL_FILES"

# Count lines of code
if command -v cloc &> /dev/null; then
    echo ""
    echo "Lines of Code:"
    cloc --quiet . 2>/dev/null | tail -5
else
    echo "(Install 'cloc' for detailed code statistics)"
fi

echo ""
echo "=================================================="
echo "  Verification Summary"
echo "=================================================="
echo -e "${GREEN}Passed: $PASSED${NC}"
echo -e "${RED}Failed: $FAILED${NC}"
echo -e "${YELLOW}Warnings: $WARNINGS${NC}"
echo ""

# Overall result
if [ $FAILED -eq 0 ]; then
    if [ $WARNINGS -eq 0 ]; then
        echo -e "${GREEN}✓ All checks passed! Plugin is ready for deployment.${NC}"
        exit 0
    else
        echo -e "${YELLOW}⚠ All critical checks passed, but review warnings above.${NC}"
        exit 0
    fi
else
    echo -e "${RED}✗ Some checks failed. Review issues above before deployment.${NC}"
    exit 1
fi
