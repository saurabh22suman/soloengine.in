# Deploying with Dokploy

This document provides instructions for deploying this PHP portfolio application using Dokploy and Docker Compose.

## Prerequisites

- Dokploy installed on your server
- Docker and Docker Compose installed
- External Traefik instance running for handling HTTPS and routing

## Deployment Steps

1. **Network Setup**
   
   Ensure the `dokploy-network` external network exists:
   ```bash
   docker network create dokploy-network
   ```

2. **Port Conflicts**

   This application is configured to work with Traefik as a reverse proxy. Make sure:
   - Port 80 is not directly bound by other containers
   - Traefik is properly configured to handle the `me.soloengine.in` domain

3. **Deployment Command**

   Deploy using Dokploy:
   ```bash
   dokploy up
   ```

## Troubleshooting

### Port Already Allocated

If you see an error like:
```
Error response from daemon: Bind for 0.0.0.0:80 failed: port is already allocated
```

Solutions:
- Check which process is using port 80: `netstat -tuln | grep :80`
- Stop the process or service using port 80
- Use a different port in your configuration if needed

### Container Naming Issues

If you experience issues with container names:
- Avoid hardcoding container names in your docker-compose.yml
- Let Dokploy handle container naming
- Make sure your nginx configuration uses the service name (`php`) for the fastcgi_pass directive

## Configuration Notes

- The `version` attribute in docker-compose.yml is obsolete and should be removed
- Container naming should be managed by Dokploy, not hardcoded
- For HTTP to HTTPS redirects, use Traefik labels instead of direct port binding
- Always test your configuration locally before deploying

## Security Considerations

- HTTPS is enforced through Traefik
- Let's Encrypt is used for SSL certificates
- Traffic is redirected from HTTP to HTTPS

## Monitoring

Monitor your application after deployment:
```bash
dokploy logs -f
```

## Updating

To update your deployment:
```bash
git pull
dokploy up
```
