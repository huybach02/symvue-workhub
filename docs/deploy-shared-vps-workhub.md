# Deploy Workhub Len Shared NAT VPS

Tai lieu nay tong hop lai toan bo cac buoc da thuc hien de deploy project `workhub` len VPS moi dang NAT VPS, dung `Cloudflare Tunnel` + `Nginx Proxy Manager`, dong thoi mo ta cach tai su dung cung VPS do cho cac project khac trong tuong lai, ke ca khi project moi dung tech stack hoan toan khac.

Tai lieu nay duoc viet theo dung hien trang da setup thanh cong.

## 1. Muc tieu kien truc

Kien truc cuoi cung:

1. Domain da tro Name Server qua Cloudflare.
2. `cloudflared` chay tren VPS va tao ket noi outbound len Cloudflare.
3. Tat ca public hostname trong tunnel deu tro ve `http://localhost:80`.
4. `Nginx Proxy Manager` (NPM) chay tren VPS, nghe `80/81/443`.
5. NPM route theo `Host header` toi dung container cua tung project.
6. Moi project chi chay app container rieng, khong publish `80/443/81` ra host.

Loi ich:

- Phu hop NAT VPS.
- De mo rong nhieu project tren cung VPS.
- Tach ro `ha tang dung chung` va `ung dung`.

## 2. Thong tin thuc te cua case nay

- VPS OS: Debian 12
- Public IP: `103.249.117.202`
- SSH port NAT: `21977`
- SSH user: `root`
- Domain goc: `huybach02.io.vn`
- Frontend: `workhub.huybach02.io.vn`
- Backend API: `workhub-api.huybach02.io.vn`
- Mercure: `workhub-mercure.huybach02.io.vn`
- Tunnel moi: `workhub-prod`
- Tunnel cu: `novatify-prod`
- Thu muc app tren VPS: `/srv/apps/workhub`
- Network docker dung chung: `edge`

## 3. Cac thanh phan dung chung tren VPS

Chi setup 1 lan cho ca VPS:

- Docker Engine + Docker Compose plugin
- `cloudflared`
- `Nginx Proxy Manager`
- Docker network `edge`

Khong nen moi project tu cai:

- 1 NPM rieng
- 1 map port `80/443/81` rieng
- 1 reverse proxy rieng

## 4. Setup VPS lan dau

### 4.1. Cai Docker

```bash
apt install -y ca-certificates curl git
install -m 0755 -d /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/debian/gpg -o /etc/apt/keyrings/docker.asc
chmod a+r /etc/apt/keyrings/docker.asc

cat >/etc/apt/sources.list.d/docker.sources <<'EOF'
Types: deb
URIs: https://download.docker.com/linux/debian
Suites: bookworm
Components: stable
Architectures: amd64
Signed-By: /etc/apt/keyrings/docker.asc
EOF

apt update
apt install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
systemctl enable --now docker
docker compose version
docker run --rm hello-world
```

### 4.2. Tao network dung chung

```bash
docker network create edge
```

### 4.3. Chay Nginx Proxy Manager dung chung

Tao thu muc:

```bash
mkdir -p /srv/infra/proxy
cd /srv/infra/proxy
```

Tao `docker-compose.yml`:

```yaml
services:
    npm:
        image: jc21/nginx-proxy-manager:latest
        container_name: infra_npm
        restart: unless-stopped
        ports:
            - "80:80"
            - "81:81"
            - "443:443"
        volumes:
            - npm_data:/data
            - npm_letsencrypt:/etc/letsencrypt
        networks:
            - edge

volumes:
    npm_data:
    npm_letsencrypt:

networks:
    edge:
        external: true
```

Chay:

```bash
docker compose up -d
curl -I http://127.0.0.1
curl -I http://127.0.0.1:81
```

## 5. Setup Cloudflare Tunnel

### 5.1. Cai cloudflared

```bash
cd /tmp
curl -L --output cloudflared.deb https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-amd64.deb
dpkg -i cloudflared.deb
cloudflared --version
```

### 5.2. Tao tunnel moi tren Cloudflare

Trong Cloudflare:

1. Vao `Zero Trust`
2. `Networks`
3. `Connectors`
4. `Create a tunnel`
5. Chon `Cloudflared`
6. Dat ten `workhub-prod`

Cloudflare se hien lenh dang:

```bash
cloudflared service install <TOKEN_RAT_DAI>
```

Chay lenh do tren VPS, roi kiem tra:

```bash
systemctl status cloudflared
```

Can thay `active (running)`.

### 5.3. Tao public hostname trong tunnel

Trong tunnel `workhub-prod`, tao 3 route:

- `workhub.huybach02.io.vn` -> `http://localhost:80`
- `workhub-api.huybach02.io.vn` -> `http://localhost:80`
- `workhub-mercure.huybach02.io.vn` -> `http://localhost:80`

Luu y:

- Tat ca deu tro `localhost:80`
- Khong tro `localhost:81`
- Khong tro truc tiep vao container
- Khong tro vao public NAT port

## 6. Deploy project Workhub

### 6.1. Clone repo

Thu muc app:

```bash
mkdir -p /srv/apps
cd /srv/apps
git clone git@github.com:huybach02/symvue-workhub.git workhub
cd workhub
```

Neu repo doi ten, nho doi `origin` cho dung:

```bash
git remote set-url origin git@github.com:huybach02/symvue-workhub.git
git remote -v
```

### 6.2. Tao `.env.prod.local`

```bash
cp .env .env.prod.local
nano .env.prod.local
```

Can sua toi thieu:

```dotenv
APP_ENV=prod
APP_DEBUG=0
DEFAULT_URI=https://workhub-api.huybach02.io.vn

MERCURE_URL=http://mercure/.well-known/mercure
MERCURE_PUBLIC_URL=https://workhub-mercure.huybach02.io.vn/.well-known/mercure

VITE_API_BASE_URL=https://workhub-api.huybach02.io.vn/api
VITE_MERCURE_URL=https://workhub-mercure.huybach02.io.vn/.well-known/mercure
```

Luu y:

- File nay dung cho ca backend va frontend trong flow Docker Compose hien tai.
- `VITE_*` can dat trong root `.env.prod.local`, khong can dat rieng trong `admin/.env`.
- `APP_SECRET` phai co gia tri that.

### 6.3. Tao `docker-compose.vps.yml`

File nay dung de app join vao network `edge` va dat `container_name` rieng:

```yaml
services:
    be-php:
        container_name: workhub_be_php

    be-nginx:
        container_name: workhub_be_nginx
        networks:
            - default
            - edge

    fe-nginx:
        container_name: workhub_fe_nginx
        networks:
            - default
            - edge

    messenger:
        container_name: workhub_messenger

    scheduler:
        container_name: workhub_scheduler

    mercure:
        container_name: workhub_mercure
        environment:
            MERCURE_EXTRA_DIRECTIVES: |
                cors_origins https://workhub.huybach02.io.vn https://workhub-api.huybach02.io.vn https://workhub-mercure.huybach02.io.vn
        networks:
            - default
            - edge

networks:
    edge:
        external: true
```

### 6.4. Build va chay app

```bash
docker compose -f docker-compose.yml -f docker-compose.vps.yml --env-file .env.prod.local build
docker compose -f docker-compose.yml -f docker-compose.vps.yml --env-file .env.prod.local up -d be-php be-nginx fe-nginx mercure messenger scheduler
docker compose -f docker-compose.yml -f docker-compose.vps.yml --env-file .env.prod.local ps
docker compose -f docker-compose.yml -f docker-compose.vps.yml --env-file .env.prod.local exec -T be-php php bin/console doctrine:migrations:migrate --no-interaction --env=prod
docker compose -f docker-compose.yml -f docker-compose.vps.yml --env-file .env.prod.local exec -T be-php php bin/console cache:clear --env=prod --no-debug
```

## 7. Loi da gap trong qua trinh deploy

### 7.1. Composer fail khi dung `prefer-dist`

Loi:

- Download tu `codeload.github.com` bi `HTTP/2 400`

Cach xu ly:

Trong `Dockerfile`, doi:

```dockerfile
RUN composer install --prefer-dist --no-dev --no-autoloader --no-scripts --no-progress
```

thanh:

```dockerfile
RUN composer install --prefer-source --no-dev --no-autoloader --no-scripts --no-progress
```

Trade-off:

- Deploy se cham hon
- Nhung on dinh hon voi case VPS nay

## 8. Cau hinh Nginx Proxy Manager

De vao NPM tren VPS:

```bash
ssh -p 21977 root@103.249.117.202 -L 8081:127.0.0.1:81
```

Mo tren may local:

```text
http://127.0.0.1:8081
```

Tao 3 proxy host:

### 8.1. Frontend

- Domain: `workhub.huybach02.io.vn`
- Scheme: `http`
- Forward Hostname: `workhub_fe_nginx`
- Forward Port: `80`
- Access List: `Publicly Accessible`
- Cache Assets: tat
- Block Common Exploits: bat
- Websockets Support: bat

### 8.2. Backend

- Domain: `workhub-api.huybach02.io.vn`
- Scheme: `http`
- Forward Hostname: `workhub_be_nginx`
- Forward Port: `80`
- Access List: `Publicly Accessible`
- Cache Assets: tat
- Block Common Exploits: bat
- Websockets Support: bat

### 8.3. Mercure

- Domain: `workhub-mercure.huybach02.io.vn`
- Scheme: `http`
- Forward Hostname: `workhub_mercure`
- Forward Port: `80`
- Access List: `Publicly Accessible`
- Cache Assets: tat
- Block Common Exploits: bat
- Websockets Support: bat

Khong can cau hinh SSL trong NPM cho case nay, vi Cloudflare dang xu ly public HTTPS.

## 9. Test sau deploy

### 9.1. Test noi bo tren VPS

```bash
curl -I -H 'Host: workhub.huybach02.io.vn' http://127.0.0.1
curl -I -H 'Host: workhub-api.huybach02.io.vn' http://127.0.0.1
curl -I -H 'Host: workhub-mercure.huybach02.io.vn' http://127.0.0.1/.well-known/mercure
```

Ket qua dung:

- FE: `200 OK`
- API `/`: `404 Not Found` van co the la dung neu route goc khong ton tai
- Mercure: `401 Unauthorized` la dung

Test them API co that:

```bash
curl -I -H 'Host: workhub-api.huybach02.io.vn' http://127.0.0.1/api/auth/me
```

Neu ra `401 Unauthorized` thi API da len dung.

### 9.2. Test public

- `https://workhub.huybach02.io.vn`
- `https://workhub-api.huybach02.io.vn/api/auth/me`
- `https://workhub-mercure.huybach02.io.vn/.well-known/mercure`

Ket qua mong doi:

- FE vao duoc
- API `401`
- Mercure `401`

## 10. GitHub Actions CI/CD cho Workhub

Workflow da dung:

- `.github/workflows/deploy.yml`

File override duoc commit:

- `docker-compose.vps.yml`

### 10.1. SSH key cho GitHub Actions

Tao key tren VPS:

```bash
mkdir -p /root/.ssh
chmod 700 /root/.ssh
ssh-keygen -t ed25519 -C "github-actions-workhub-deploy" -f /root/.ssh/github_actions_workhub -N ""
cat /root/.ssh/github_actions_workhub.pub >> /root/.ssh/authorized_keys
chmod 600 /root/.ssh/authorized_keys
```

Lay private key:

```bash
cat /root/.ssh/github_actions_workhub
```

Dung toan bo noi dung do cho secret `VPS_SSH_KEY`.

### 10.2. GitHub Secrets

Trong repo `symvue-workhub`, set:

- `VPS_HOST` = `103.249.117.202`
- `VPS_PORT` = `21977`
- `VPS_USERNAME` = `root`
- `VPS_APP_DIR` = `/srv/apps/workhub`
- `VPS_SSH_KEY` = private key vua tao

### 10.3. Luu y ve workflow

Workflow hien tai:

- deploy khi push len `main`
- co the chay tay qua `workflow_dispatch`
- fail neu VPS working tree dang dirty
- `git pull` truoc
- check `docker-compose.vps.yml` sau khi pull
- build lai image tren chinh VPS

### 10.4. Tai sao deploy mat ~13 phut

Dieu nay binh thuong voi VPS `2 vCPU / 2GB RAM` vi:

- build backend image
- `composer install --prefer-source`
- build frontend `npm ci` + `npm run build`
- migration + cache clear

Workflow chay thanh cong la quan trong hon viec qua nhanh o giai doan dau.

## 11. Sau nay deploy project khac len cung VPS

### 11.1. Nguyen tac

Khong quan trong project moi dung:

- Symfony
- Laravel
- NestJS
- Next.js
- Django
- Golang
- Node
- app tinh

Van dung duoc cung VPS neu project moi obey cac nguyen tac:

1. Khong bind host port `80/81/443`.
2. Co service web noi bo nghe tren 1 port nao do (`80`, `3000`, `8000`, ...).
3. Service can public phai join network `edge`.
4. Dat `container_name` rieng, khong trung project cu.
5. Dung subdomain rieng.

### 11.2. Quy uoc ten

Vi du project moi ten `crm-demo`:

- Thu muc: `/srv/apps/crm-demo`
- FE: `crm.huybach02.io.vn`
- API: `crm-api.huybach02.io.vn`
- Mercure hoac WS: `crm-mercure.huybach02.io.vn`
- Container prefix: `crm_demo_*`

### 11.3. Cac buoc cho project moi

1. Clone vao thu muc rieng:

```bash
git clone <repo-moi> /srv/apps/crm-demo
cd /srv/apps/crm-demo
```

2. Tao env production rieng.

3. Tao `docker-compose.vps.yml` rieng:

- service can public join `edge`
- dat `container_name` rieng
- neu project khong co Mercure thi bo service do di

4. Trong Cloudflare Tunnel dang dung, them public hostname moi:

- `crm.huybach02.io.vn` -> `http://localhost:80`
- `crm-api.huybach02.io.vn` -> `http://localhost:80`
- `crm-mercure.huybach02.io.vn` -> `http://localhost:80`

Khong bat buoc tao tunnel moi. Dung chung 1 tunnel cho nhieu project la du.

5. Trong NPM, tao proxy host moi:

- FE -> container FE cua project moi
- API -> container backend cua project moi
- WS/Mercure -> container realtime cua project moi

6. Tao GitHub Action cho repo moi:

- `VPS_APP_DIR` tro dung thu muc moi
- co the dung lai cung `VPS_HOST`, `VPS_PORT`, `VPS_USERNAME`, `VPS_SSH_KEY`

### 11.4. Vi du project khac tech hoan toan

Neu project moi la `Next.js`:

- app container co the lang nghe `3000`
- NPM route `demo.huybach02.io.vn` -> `next_demo_app:3000`

Neu project moi la `Django`:

- app lang nghe `8000`
- NPM route `erp-api.huybach02.io.vn` -> `erp_api:8000`

Neu project moi la `Node API`:

- app lang nghe `4000`
- NPM route `service-api.huybach02.io.vn` -> `service_api:4000`

Reverse proxy va tunnel khong quan tam ben trong dung tech nao, chi can route duoc toi dung container va port.

## 12. Nhung dieu can nho de tranh tu pha setup

- Khong chay them 1 NPM khac cho moi project.
- Khong map them `80:80`, `81:81`, `443:443` cho project app.
- Khong de `container_name` trung nhau giua cac project.
- Khong sua tay file tracked tren VPS neu dang dung GitHub Actions, vi workflow se fail khi working tree dirty.
- `.env.prod.local` nen de tren VPS, khong commit len Git.
- Neu doi ten repo GitHub, nho doi `git remote set-url origin ...` o ca local va VPS.

## 13. Doi ten repo

Neu repo doi tu `symvue-novatify` sang `symvue-workhub`:

- Workflow va secrets van theo cung repo do.
- Nhung phai cap nhat `origin` o local va VPS:

```bash
git remote set-url origin git@github.com:huybach02/symvue-workhub.git
git remote -v
```

## 14. Khi nao nen xoa tunnel cu

Tunnel cu `novatify-prod` chua can xoa ngay.

Nen xoa khi:

- Public URL cua `workhub` da test ok
- GitHub Actions da deploy ok
- Khong con y dinh rollback nhanh ve he thong cu

Sau khi chac chan:

- xoa route trong tunnel cu
- hoac xoa han tunnel cu
- neu server cu van con chay `cloudflared`, disable no

## 15. Checklist van hanh nhanh

### 15.1. Khi deploy lai Workhub

1. Push code len `main`
2. GitHub Actions tu chay
3. Workflow SSH vao VPS
4. `git pull`
5. `docker compose build`
6. `docker compose up -d`
7. migration
8. clear cache
9. done

### 15.2. Khi them project moi

1. Clone repo vao `/srv/apps/<ten-project>`
2. Tao `.env.prod.local`
3. Tao `docker-compose.vps.yml`
4. Join `edge` cho service can public
5. Them hostname trong Cloudflare Tunnel
6. Them proxy host trong NPM
7. Tao secrets GitHub Actions
8. Chay deploy

---

Neu sau nay can toi uu thoi gian deploy, huong nang cap hop ly nhat la:

- GitHub Actions build image truoc
- push image len GHCR hoac Docker Hub
- VPS chi `docker pull` + `docker compose up -d`

Nhung voi giai doan hien tai, cach build tren chinh VPS la don gian, de van hanh, va du tot cho nhieu project demo.
