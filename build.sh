#!/bin/bash
#
# Builds paypal-for-woocommerce-v<version>.zip for sharing.
#
#   ./build.sh            asks for the version
#   ./build.sh 4.6.17     uses the version given
#
# Sets the version in paypal-for-woocommerce.php, builds the minified JS and
# CSS with grunt, and zips the plugin in a paypal-for-woocommerce folder so it
# installs from Plugins > Add New > Upload. Files are taken from git: tracked
# files plus new ones that are not ignored, so node_modules, .DS_Store and
# other local files never reach the zip. Development-only files are skipped.
set -e
set -o pipefail

cd "$(dirname "$0")"
REPO_PATH=$PWD
PLUGIN_SLUG='paypal-for-woocommerce'
MAIN_FILE="$PLUGIN_SLUG.php"

# Prefer the project's own grunt over a global one.
export PATH="$REPO_PATH/node_modules/.bin:$PATH"

current_version=$(sed -nE 's/^[[:space:]]*\*[[:space:]]*Version:[[:space:]]*([^[:space:]]+).*/\1/p' "$MAIN_FILE" | head -n 1)

version=$1
if [ -z "$version" ]; then
    read -r -p "Version to build [$current_version]: " version
    version=${version:-$current_version}
fi
if ! [[ "$version" =~ ^[0-9]+(\.[0-9]+){1,3}([-+][0-9A-Za-z.-]+)?$ ]]; then
    echo "Error: '$version' is not a valid version (e.g. 4.6.17 or 4.6.17-beta1)."
    exit 1
fi

# --- Version in the plugin header and the VERSION_PFW constant ---
tmp_file=$(mktemp)
sed -E \
    -e "s/^([[:space:]]*\*[[:space:]]*Version:[[:space:]]*).*/\1$version/" \
    -e "s/('VERSION_PFW' => ')[^']*(')/\1$version\2/" \
    "$MAIN_FILE" > "$tmp_file"
cat "$tmp_file" > "$MAIN_FILE"
rm -f "$tmp_file"
if [ "$(grep -cE "Version:[[:space:]]*$version\$|'VERSION_PFW' => '$version'" "$MAIN_FILE")" -ne 2 ]; then
    echo "Error: could not set version $version in $MAIN_FILE."
    exit 1
fi
echo "Version set to $version in $MAIN_FILE"

# --- Minified JS and CSS ---
if [ ! -x "$REPO_PATH/node_modules/.bin/grunt" ]; then
    echo "Installing build dependencies..."
    npm ci --no-audit --no-fund
fi
grunt

# --- Copy the plugin files into a staging folder ---
stage_dir=$(mktemp -d)
trap 'rm -rf "$stage_dir"' EXIT
plugin_dir="$stage_dir/$PLUGIN_SLUG"
mkdir -p "$plugin_dir"

file_list="$stage_dir/files.txt"
: > "$file_list"
git ls-files -z --cached --others --exclude-standard -- . \
    ':(exclude,glob)**/.*' \
    ':(exclude,glob)**/*.md' \
    ':(exclude,glob)*.zip' \
    ':(exclude,glob)*.sha256' \
    ':(exclude)build.sh' \
    ':(exclude)package.json' \
    ':(exclude)package-lock.json' \
    ':(exclude)gruntfile.js' \
    ':(exclude)composer.json' \
    ':(exclude)composer.lock' \
    ':(exclude)phpcs.xml.dist' \
    ':(exclude)phpcs-errors.xml.dist' \
| while IFS= read -r -d '' file; do
    # Tracked files deleted in the working tree are listed too; skip them.
    if [ -f "$file" ]; then
        printf '%s\0' "$file" >> "$file_list"
    fi
done
tar --null -T "$file_list" -cf - | tar -xf - -C "$plugin_dir"

# --- Zip ---
zip_file="$REPO_PATH/$PLUGIN_SLUG-v$version.zip"
rm -f "$zip_file"
(cd "$stage_dir" && zip -rqX "$zip_file" "$PLUGIN_SLUG")

file_count=$(find "$plugin_dir" -type f | wc -l | tr -d ' ')
echo ""
echo "Built: $zip_file"
echo "Files: $file_count, size: $(du -h "$zip_file" | cut -f1)"
echo "SHA-256: $(shasum -a 256 "$zip_file" | cut -d ' ' -f1)"
