#!/bin/bash
# Kopiert die Claude-Memory-Dateien an den richtigen Ort für Claude Code.
# Auf dem Zielrechner einmalig ausführen nach dem git clone.

PROJECT_PATH=$(pwd)
PROJECT_KEY=$(echo "$PROJECT_PATH" | sed 's|/|\-|g' | sed 's|^-||')

TARGET_DIR="$HOME/.claude/projects/${PROJECT_KEY}/memory"

mkdir -p "$TARGET_DIR"
cp .claude-memory/*.md "$TARGET_DIR/"

echo "Memory-Dateien kopiert nach: $TARGET_DIR"
echo "Enthaltene Dateien:"
ls "$TARGET_DIR"
