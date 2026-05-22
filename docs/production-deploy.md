# Production Deploy Guide

Tai lieu nay danh cho project `symvue-novatify` khi deploy len VPS chi co `public tunnel`, khong co public IP rieng.

## 1. Ket luan quan trong

Voi kieu VPS hien tai, ban **khong nen** tro DNS truc tiep `novatify.huybach02.io.vn` den `e1.chiasegpu.vn`.

Ly do:

- DNS record (`A`, `AAAA`, `CNAME`) khong mang theo port.
- Trong case cua ban, nha cung cap expose dich vu theo dang `e1.chiasegpu.vn:41342`, `e1.chiasegpu.vn:52280`...
- Neu tro `CNAME novatify.huybach02.io.vn -> e1.chiasegpu.vn`, trinh duyet se goi port mac dinh `80/443`, khong phai cac port tunnel duoc cap rieng cho VPS cua ban.

Vi vay, de dung subdomain dep theo nhu cau:

- `novatify.huybach02.io.vn`
- `novatify-api.huybach02.io.vn`
- `novatify-mercure.huybach02.io.vn`

ban nen dung **Cloudflare Tunnel** hoac mot giai phap reverse tunnel/co public IP that su.

## 2. Kien truc de xuat

Khuyen nghi:

1. Docker Compose chay cac service noi bo tren VPS.
2. `nginx-proxy-manager` lang nghe trong VPS tai `80/443`.
3. `cloudflared` tao ket noi outbound tu VPS len Cloudflare.
4. Cloudflare route tung subdomain vao Nginx Proxy Manager.
5. Nginx Proxy Manager route tiep den:
   - `novatify.huybach02.io.vn` -> `fe-nginx:80`
   - `novatify-api.huybach02.io.vn` -> `be-nginx:80`
   - `novatify-mercure.huybach02.io.vn` -> `mercure:80`

## 3. Chuan bi DNS

Ban co 2 lua chon:

### Cach khuyen nghi: dua DNS qua Cloudflare

1. Tao tai khoan Cloudflare.
2. Add domain `huybach02.io.vn`.
3. Doi nameserver tai NhanHoa sang nameserver Cloudflare cap.
4. Sau khi domain active tren Cloudflare, tao Tunnel va public hostname cho 3 subdomain.

### Cach phu

Neu ChiaseGPU co tinh nang custom domain map thang vao tunnel cua tung port, ban co the dung theo huong dan cua ho. Neu khong co tinh nang nay thi khong dung duoc subdomain dep chi voi DNS thuong.

## 4. Bien moi truong production

Khong dung truc tiep file `.env` hien tai cho production vi dang chua secret that.

Tao file `.env.prod.local` tren server dua theo mau `.env.prod.example`.

Voi cau hinh hien tai cua repo:

- Symfony services doc bien tu `.env.prod.local` qua `env_file`
- Frontend Vue lay `VITE_*` khi build qua `docker compose --env-file .env.prod.local`

Nghia la ban chi can **mot file `.env.prod.local`** cho ca backend va frontend.

Can dac biet cap nhat:

- `APP_SECRET`
- `DATABASE_URL`
- `REDIS_URL`
- `MESSENGER_TRANSPORT_DSN`
- `MAILER_DSN`
- `JWT_PASSPHRASE`
- `MERCURE_JWT_SECRET`
- `MERCURE_PUBLIC_URL`
- `VITE_API_BASE_URL`
- `VITE_MERCURE_URL`

## 5. Trinh tu setup tren VPS

### 5.1. Cai Git va clone repo

```bash
sudo apt update
sudo apt install -y git
mkdir -p ~/apps
cd ~/apps
git clone git@github.com:huybach02/symvue-novatify.git
cd symvue-novatify
```

Neu chua dung SSH key voi GitHub private repo, dung PAT tam thoi hoac add deploy key.

### 5.2. Tao file env production

```bash
cp .env.prod.example .env.prod.local
nano .env.prod.local
```

Neu muon khoi dong nhanh tu cau hinh hien co, ban co the copy truc tiep:

```bash
cp .env .env.prod.local
nano .env.prod.local
```

Sau do sua it nhat:

- `APP_ENV=prod`
- `APP_SECRET=<gia-tri-bi-mat>`
- `DEFAULT_URI=https://novatify-api.huybach02.io.vn`
- `MERCURE_URL=http://mercure/.well-known/mercure`
- `MERCURE_PUBLIC_URL=https://novatify-mercure.huybach02.io.vn/.well-known/mercure`
- `VITE_API_BASE_URL=https://novatify-api.huybach02.io.vn/api`
- `VITE_MERCURE_URL=https://novatify-mercure.huybach02.io.vn/.well-known/mercure`

### 5.3. Build va chay Docker

```bash
docker compose --env-file .env.prod.local build --no-cache
docker compose --env-file .env.prod.local up -d
docker compose --env-file .env.prod.local ps
```

### 5.4. Chay migration

```bash
docker compose --env-file .env.prod.local exec -T be-php php bin/console doctrine:migrations:migrate --no-interaction --env=prod
docker compose --env-file .env.prod.local exec -T be-php php bin/console cache:clear --env=prod --no-debug
```

## 6. Cau hinh Nginx Proxy Manager

Dang nhap NPM tai:

- `http://<dia-chi-tunnel-port-81-cua-ban>`

Tao 3 Proxy Host:

1. Domain `novatify.huybach02.io.vn`
   - Scheme: `http`
   - Forward Hostname: `fe-nginx`
   - Forward Port: `80`

2. Domain `novatify-api.huybach02.io.vn`
   - Scheme: `http`
   - Forward Hostname: `be-nginx`
   - Forward Port: `80`

3. Domain `novatify-mercure.huybach02.io.vn`
   - Scheme: `http`
   - Forward Hostname: `mercure`
   - Forward Port: `80`

Neu di qua Cloudflare Tunnel, SSL public do Cloudflare xu ly o lop ngoai. Tuy theo cach ban cau hinh, ban co the dung HTTP giua `cloudflared` va NPM.

## 7. Cau hinh Cloudflare Tunnel

Tai Cloudflare Tunnel, map:

- `novatify.huybach02.io.vn` -> `http://localhost:80`
- `novatify-api.huybach02.io.vn` -> `http://localhost:80`
- `novatify-mercure.huybach02.io.vn` -> `http://localhost:80`

Phan route den dung container se do NPM xu ly theo `Host` header.

Neu ban muon bo NPM, co the cho `cloudflared` route truc tiep den tung cong local khac nhau, nhung voi repo nay NPM da co san nen de van hanh hon.

## 8. GitHub Actions

Repo da co workflow [`.github/workflows/deploy.yml`](../.github/workflows/deploy.yml) deploy qua SSH.

Ban co the tai su dung workflow nay bang cach tao cac GitHub Secrets:

- `EC2_HOST=e1.chiasegpu.vn`
- `EC2_PORT=27760`
- `EC2_USERNAME=ubuntu`
- `EC2_SSH_KEY=<private key>`
- `EC2_APP_DIR=/home/ubuntu/apps/symvue-novatify`
- `VITE_API_BASE_URL=https://novatify-api.huybach02.io.vn/api`
- `VITE_MERCURE_URL=https://novatify-mercure.huybach02.io.vn/.well-known/mercure`

Ten secret dang de `EC2_*` nhung van dung tot cho VPS nay.

## 9. Viec can lam ngay ve bao mat

File `.env` trong repo hien dang chua thong tin that cua:

- database
- redis
- mailer
- mercure secret

Ban nen:

1. Rotate lai cac secret nay.
2. Dua secret that vao `.env.prod.local` tren server.
3. Neu can CI/CD, dua secret vao GitHub Secrets.
4. Tranh commit secret that vao repo nua.

## 10. Checklist cuoi

1. Confirm co dung Cloudflare Tunnel hay khong.
2. Clone repo len VPS.
3. Tao `.env.prod.local`.
4. `docker compose --env-file .env.prod.local build && docker compose --env-file .env.prod.local up -d`
5. Chay migration.
6. Cau hinh NPM.
7. Cau hinh 3 subdomain.
8. Test login, API, upload va Mercure realtime.
