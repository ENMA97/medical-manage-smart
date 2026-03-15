#!/bin/bash
# ══════════════════════════════════════════════
# Medical ERP - Deployment Script
# ══════════════════════════════════════════════

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DOCKER_DIR="$SCRIPT_DIR/docker"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

log() { echo -e "${GREEN}[✓]${NC} $1"; }
warn() { echo -e "${YELLOW}[!]${NC} $1"; }
error() { echo -e "${RED}[✗]${NC} $1"; exit 1; }

# ── Check prerequisites ──
command -v docker >/dev/null 2>&1 || error "Docker is not installed"
command -v docker compose >/dev/null 2>&1 || error "Docker Compose is not installed"

# ── Parse arguments ──
ENV="${1:-production}"
ACTION="${2:-up}"

case "$ACTION" in
  up|start)
    log "Starting Medical ERP ($ENV)..."

    # Check .env
    if [ ! -f "$DOCKER_DIR/.env.production" ]; then
      error ".env.production not found. Copy from template and configure."
    fi

    # Check APP_KEY
    if grep -q "APP_KEY=$" "$DOCKER_DIR/.env.production" 2>/dev/null; then
      warn "APP_KEY is empty. Generating..."
      cd "$SCRIPT_DIR/backend"
      APP_KEY=$(php artisan key:generate --show 2>/dev/null || echo "base64:$(openssl rand -base64 32)")
      sed -i "s|APP_KEY=.*|APP_KEY=$APP_KEY|" "$DOCKER_DIR/.env.production"
      log "APP_KEY generated"
    fi

    # Build and start
    cd "$DOCKER_DIR"
    if [ "$ENV" = "production" ]; then
      docker compose --env-file .env.production --profile production up -d --build
    else
      docker compose --env-file .env.production up -d --build
    fi

    log "Waiting for services to be healthy..."
    sleep 10

    # Run migrations
    docker exec medical_erp_backend php artisan migrate --force
    log "Migrations complete"

    # Seed demo data (only in non-production)
    if [ "$ENV" != "production" ]; then
      docker exec medical_erp_backend php artisan db:seed --class=FoundationSeeder --force 2>/dev/null || true
      log "Demo data seeded"
    fi

    log "Medical ERP is running!"
    echo ""
    echo "  Backend API:  http://localhost:8000"
    echo "  Frontend:     http://localhost:3000"
    if [ "$ENV" = "production" ]; then
      echo "  Nginx:        https://localhost"
    fi
    echo ""
    ;;

  down|stop)
    log "Stopping Medical ERP..."
    cd "$DOCKER_DIR"
    docker compose --profile production --profile with-biometric down
    log "All services stopped"
    ;;

  restart)
    $0 "$ENV" down
    $0 "$ENV" up
    ;;

  logs)
    cd "$DOCKER_DIR"
    docker compose logs -f "${3:-}"
    ;;

  status)
    cd "$DOCKER_DIR"
    docker compose ps
    echo ""
    log "Health checks:"
    curl -sf http://localhost:8000/health 2>/dev/null | python3 -m json.tool 2>/dev/null || warn "Backend not reachable"
    curl -sf http://localhost:3000 >/dev/null 2>&1 && log "Frontend: OK" || warn "Frontend not reachable"
    ;;

  backup)
    BACKUP_FILE="backup_$(date +%Y%m%d_%H%M%S).sql"
    log "Creating database backup: $BACKUP_FILE"
    docker exec medical_erp_db pg_dump -U erp_user medical_erp > "$DOCKER_DIR/$BACKUP_FILE"
    log "Backup saved to docker/$BACKUP_FILE"
    ;;

  *)
    echo "Usage: $0 [environment] [action]"
    echo ""
    echo "  Environments: production, staging"
    echo "  Actions:      up, down, restart, logs, status, backup"
    echo ""
    echo "Examples:"
    echo "  $0 production up       # Start in production mode"
    echo "  $0 staging up          # Start in staging mode (with demo data)"
    echo "  $0 production logs     # View logs"
    echo "  $0 production status   # Check health"
    echo "  $0 production backup   # Backup database"
    ;;
esac
