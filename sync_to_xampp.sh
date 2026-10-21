#!/usr/bin/env bash
# Synchronize local workspace code to XAMPP htdocs
DEST="/Applications/XAMPP/xamppfiles/htdocs/hostel"
SRC="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

echo "Syncing $SRC -> $DEST..."
rsync -av --exclude '.git' --exclude '.sessions' "$SRC/" "$DEST/"
echo "Sync complete! You can reload http://localhost/hostel in your browser."
