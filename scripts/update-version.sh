#!/bin/bash
# Script to update version numbers in WordPress plugin files
# Usage: ./scripts/update-version.sh <version>

set -e

VERSION=$1

if [ -z "$VERSION" ]; then
    echo "Usage: $0 <version>"
    exit 1
fi

echo "Updating version to: $VERSION"

# Update plugin header Version:
sed -i "s/^ \* Version:.*/ * Version:         $VERSION/" verify-phone-number-shift64.php

# Update SHIFT64_PHONE_VALIDATION_VERSION constant
sed -i "s/define( 'SHIFT64_PHONE_VALIDATION_VERSION', '[^']*' );/define( 'SHIFT64_PHONE_VALIDATION_VERSION', '$VERSION' );/" verify-phone-number-shift64.php

# Update readme.txt Stable tag:
sed -i "s/^Stable tag: .*/Stable tag: $VERSION/" readme.txt

echo "Version updated successfully!"

# Verify changes
echo ""
echo "Verification:"
grep "Version:" verify-phone-number-shift64.php | head -1
grep "SHIFT64_PHONE_VALIDATION_VERSION" verify-phone-number-shift64.php
grep "Stable tag:" readme.txt
