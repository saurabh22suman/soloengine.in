#!/bin/bash
# YAML Validation Script for Docker Compose
# This script validates docker-compose.yml for syntax errors

echo "🔍 Validating docker-compose.yml syntax..."

# Check if docker-compose is installed
if ! command -v docker-compose &> /dev/null; then
    echo "❌ Error: docker-compose is not installed or not in PATH"
    exit 1
fi

# Validate docker-compose.yml syntax
if docker-compose -f docker-compose.yml config > /dev/null 2>&1; then
    echo "✅ docker-compose.yml syntax is valid"
else
    echo "❌ Syntax error in docker-compose.yml"
    # Show detailed error with line numbers
    docker-compose -f docker-compose.yml config
    exit 1
fi

# Check for common issues in docker-compose.yml
echo "🔍 Checking for common YAML issues..."

# Check for tabs (which can cause YAML issues)
if grep -P "\t" docker-compose.yml > /dev/null; then
    echo "⚠️ Warning: docker-compose.yml contains tab characters, which might cause indentation issues"
fi

# Check for inconsistent indentation
if ! grep -E "^  [a-zA-Z]" docker-compose.yml > /dev/null && grep -E "^    [a-zA-Z]" docker-compose.yml > /dev/null; then
    echo "⚠️ Warning: docker-compose.yml might have inconsistent indentation"
fi

echo "🔍 Validating Dokploy-specific configuration..."

# Check for dokploy.enable label
if ! grep -E "dokploy.enable=true" docker-compose.yml > /dev/null; then
    echo "⚠️ Warning: dokploy.enable=true label is missing"
fi

# Check for dokploy.type label
if ! grep -E "dokploy.type" docker-compose.yml > /dev/null; then
    echo "⚠️ Warning: dokploy.type label is missing"
fi

echo "✅ Validation complete"
exit 0
