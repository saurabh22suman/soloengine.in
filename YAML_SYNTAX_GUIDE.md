# Docker Compose YAML Syntax Guide for Dokploy

This guide helps troubleshoot YAML syntax issues in Docker Compose files, particularly when deploying with Dokploy.

## Common YAML Syntax Issues

### Indentation Errors

YAML relies on consistent indentation for structure. Errors typically look like:
```
bad indentation of a mapping entry (3:15)
```

**Correct Indentation Pattern:**
```yaml
services:
  web:           # 2 spaces
    build:       # 4 spaces
      context: .docker/php  # 6 spaces
```

**Common Mistakes:**
- Mixing tabs and spaces
- Inconsistent spacing
- Putting multiple keys on the same line

### Missing or Extra Colons

Every key-value pair needs a colon and a space:
```yaml
# Correct
key: value

# Wrong
key : value   # Extra space before colon
key:value     # Missing space after colon
key value     # Missing colon entirely
```

### Quotes Around Strings

Some strings need quotes, especially if they contain special characters:
```yaml
# Strings with special characters need quotes
label: "value:with:colons"
command: "echo 'hello world'"

# Simple strings often don't
image: nginx:latest
```

## Dokploy-Specific Formatting

### Environment Variables

When using `${VARIABLE}` syntax with Dokploy:
```yaml
# Correct
- traefik.http.routers.${DOKPLOY_PROJECT_NAME}-web.rule=Host(`me.soloengine.in`)

# Wrong - mixing quote types incorrectly
- traefik.http.routers.${DOKPLOY_PROJECT_NAME}-web.rule=Host("me.soloengine.in")
```

### Service Labels

Properly formatted Dokploy service labels:
```yaml
labels:
  - dokploy.enable=true
  - dokploy.type=web
  - dokploy.port=9000
```

## Validating Your Docker Compose File

### Using the Provided Script
```bash
./validate-compose.sh
```

### Manual Validation
```bash
docker-compose -f docker-compose.yml config
```

### Online YAML Validators
You can also use online validators like:
- [YAML Lint](http://www.yamllint.com/)
- [YAML Validator](https://codebeautify.org/yaml-validator)

## Further Resources

- [Official YAML Specification](https://yaml.org/spec/1.2.2/)
- [Dokploy Documentation](https://docs.dokploy.com/docs/core/docker-compose/example)
- [Docker Compose File Reference](https://docs.docker.com/compose/compose-file/)
