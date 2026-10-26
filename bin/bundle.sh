#!/usr/bin/env bash
# Zips up the plugin into dist/ for distribution, leaving out dev-only files.
set -euo pipefail

cd "$(dirname "$0")/.."

SLUG="development-mode-indicator"
VERSION=$(grep -m1 "Version:" "${SLUG}.php" | sed -E 's/.*Version:[[:space:]]*//')
BUILD_DIR="dist/${SLUG}"
ZIP_NAME="${SLUG}-${VERSION}.zip"

rm -rf dist
mkdir -p "$BUILD_DIR"

cp "${SLUG}.php" readme.txt "$BUILD_DIR/"
cp -R languages "$BUILD_DIR/"
rm -f "$BUILD_DIR"/languages/*.pot

(cd dist && zip -rq "$ZIP_NAME" "$SLUG")
rm -rf "$BUILD_DIR"

echo "Created dist/${ZIP_NAME}"