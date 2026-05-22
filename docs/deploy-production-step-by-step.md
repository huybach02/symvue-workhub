# Deploy Production Step By Step

Tai lieu nay la ban huong dan "cam tay chi viec" de deploy project `symvue-novatify` len production, dua tren dung qua trinh thuc te da lam.

No bao gom:

- DNS o NhanHoa
- Cloudflare + Cloudflare Tunnel
- VPS cua ChiaseGPU
- Docker Compose
- Nginx Proxy Manager
- GitHub Actions CI/CD

Tai lieu nay danh cho case cu the:

- VPS SSH: `ssh -p 27760 ubuntu@e1.chiasegpu.vn`
- Domain chinh: `huybach02.io.vn`
- Frontend: `novatify.huybach02.io.vn`
- Backend: `novatify-api.huybach02.io.vn`
- Mercure: `novatify-mercure.huybach02.io.vn`

## 0. Tong quan kien truc cuoi cung

Kien truc da chot la:

1. User vao `novatify.huybach02.io.vn`
2. DNS cua domain tro qua Cloudflare
3. Cloudflare Tunnel chay tren VPS, ket noi outbound len Cloudflare
4. Tunnel dua request vao `localhost:80` tren VPS
5. `nginx-proxy-manager` trong Docker nghe o `80/443`
6. NPM route theo domain:
   - `novatify.huybach02.io.vn` -> `fe-nginx:80`
   - `novatify-api.huybach02.io.vn` -> `be-nginx:80`
   - `novatify-mercure.huybach02.io.vn` -> `mercure:80`

Ly do phai di huong nay:

- VPS cua ChiaseGPU khong cap public IP rieng
- VPS chi expose theo dang `e1.chiasegpu.vn:port`
- DNS thuong khong di kem port
- nen khong the tro thang subdomain vao `e1.chiasegpu.vn:port`

## 1. Chuan bi truoc khi bat dau

Can san:

1. Tai khoan NhanHoa quan ly domain `huybach02.io.vn`
2. Tai khoan Cloudflare
3. SSH vao VPS duoc bang:

```bash
ssh -p 27760 ubuntu@e1.chiasegpu.vn
```

4. Docker da cai san tren VPS
5. Repo GitHub private:

```text
git@github.com:huybach02/symvue-novatify.git
```

## 2. Chuyen DNS qua Cloudflare

### 2.1. Add domain vao Cloudflare

Tren trinh duyet:

1. Dang nhap Cloudflare
2. Bam `Domains`
3. Bam `Add a site` hoac `Connect a domain`
4. Nhap:

```text
huybach02.io.vn
```

5. Chon plan `Free`
6. Continue
7. Cloudflare scan DNS hien tai
8. Bam `Continue to activation`

### 2.2. Lay 2 nameserver Cloudflare

Cloudflare se hien 2 nameserver moi.

Trong qua trinh thuc te da dung:

```text
eleanor.ns.cloudflare.com
houston.ns.cloudflare.com
```

Luu y:

- nameserver co the khac neu ban add domain o thoi diem khac
- phai dung dung 2 gia tri Cloudflare cap cho tai khoan cua ban

### 2.3. Doi nameserver o NhanHoa

Vao NhanHoa:

1. Dang nhap
2. Bam `Quan ly dich vu`
3. Vao domain `huybach02.io.vn`
4. Tim muc `Chinh sua Name Server`
5. Xoa nameserver cu
6. Dien 2 nameserver moi cua Cloudflare vao:
   - `Name Server 1`
   - `Name Server 2`
7. De trong `Name Server 3`
8. De trong `Name Server 4`
9. Bam `Cap nhat`

Nameserver cu da thay trong qua trinh thuc te:

```text
ns1.zonedns.vn
ns2.zonedns.vn
ns3.zonedns.vn
ns4.zonedns.vn
```

Luu y thuc te:

- NhanHoa co the khoa tam thoi viec doi nameserver trong 24-48h
- Neu bam `Cap nhat` ma bao:

```text
DNS dang duoc cap nhat. Vui long cap nhat DNS sau: ...
```

thi phai cho den gio do roi doi lai

### 2.4. Xac nhan Cloudflare active

Quay lai Cloudflare, vao zone `huybach02.io.vn`.

Khi thanh cong se thay thong bao dai y:

```text
Your domain is now protected by Cloudflare
```

Luc nay coi nhu DNS da qua Cloudflare thanh cong.

## 3. SSH vao VPS va chuan bi code

### 3.1. Dang nhap VPS

```bash
ssh -p 27760 ubuntu@e1.chiasegpu.vn
```

### 3.2. Cai Git

```bash
sudo apt update
sudo apt install -y git
```

### 3.3. Tao thu muc app

```bash
mkdir -p ~/apps
cd ~/apps
```

### 3.4. Clone repo private

Neu chua co deploy key cho VPS, tao key tren VPS:

```bash
ssh-keygen -t ed25519 -C "vps-novatify"
cat ~/.ssh/id_ed25519.pub
```

Sau do:

1. Copy public key
2. Vao GitHub repo `symvue-novatify`
3. Bam `Settings`
4. Bam `Deploy keys`
5. Bam `Add deploy key`
6. Dat ten, vi du:

```text
vps-novatify
```

7. Paste public key
8. Save

Roi clone repo:

```bash
git clone git@github.com:huybach02/symvue-novatify.git
cd symvue-novatify
```

## 4. Tao file env production

Trong qua trinh thuc te, da di theo cach don gian nhat:

```bash
cp .env .env.prod.local
nano .env.prod.local
```

### 4.1. Tai sao dung `.env.prod.local`

Voi repo nay:

- Symfony backend doc bien moi truong tu `.env.prod.local`
- Docker Compose build frontend theo:

```bash
docker compose --env-file .env.prod.local ...
```

- nen co the dung 1 file `.env.prod.local` cho ca backend va frontend

### 4.2. Nhung dong quan trong phai sua

Trong `.env.prod.local`, sua it nhat:

```dotenv
APP_ENV=prod
APP_DEBUG=0
APP_SECRET=mot_chuoi_bi_mat_rat_dai_khong_chua_ky_tu_dollar
DEFAULT_URI=https://novatify-api.huybach02.io.vn

MERCURE_URL=http://mercure/.well-known/mercure
MERCURE_PUBLIC_URL=https://novatify-mercure.huybach02.io.vn/.well-known/mercure

VITE_API_BASE_URL=https://novatify-api.huybach02.io.vn/api
VITE_MERCURE_URL=https://novatify-mercure.huybach02.io.vn/.well-known/mercure
```

Luu y rat quan trong:

- `APP_SECRET` khong nen chua ky tu `$`
- trong qua trinh thuc te da gap warning do `APP_SECRET` co chuoi `$BE`
- Docker Compose se co gang noi suy bien moi truong neu gap `$TEN_BIEN`

### 4.3. Giai thich `MERCURE_URL`

Tai production da doi:

```dotenv
MERCURE_URL=http://mercure/.well-known/mercure
```

thay vi:

```dotenv
MERCURE_URL=http://localhost:9999/.well-known/mercure
```

Ly do:

- `localhost` ben trong container `be-php` chi la chinh container do
- Mercure chay o container khac ten la `mercure`
- trong Docker network, service goi nhau qua service name

Nen:

- `MERCURE_URL` = URL noi bo container -> container
- `MERCURE_PUBLIC_URL` = URL public cho browser

## 5. Build va chay stack Docker

### 5.1. Build image

```bash
docker compose --env-file .env.prod.local build --no-cache
```

### 5.2. Chay stack

```bash
docker compose --env-file .env.prod.local up -d
docker compose --env-file .env.prod.local ps
```

### 5.3. Chay migration va clear cache

```bash
docker compose --env-file .env.prod.local exec -T be-php php bin/console doctrine:migrations:migrate --no-interaction --env=prod
docker compose --env-file .env.prod.local exec -T be-php php bin/console cache:clear --env=prod --no-debug
```

### 5.4. Kiem tra local service tren VPS

```bash
curl -I http://localhost
curl -I http://localhost:81
```

Muc dich:

- `http://localhost` phai ra Nginx Proxy Manager
- `http://localhost:81` phai ra giao dien admin NPM

## 6. Tao tunnel tren Cloudflare

### 6.1. Vao Zero Trust

Tren Cloudflare:

1. Ve `Account Home`
2. Tim menu `Zero Trust`
3. Bam `Get started`
4. Neu Cloudflare bat billing cho `Zero Trust Free`, them the hoac PayPal

Luu y:

- theo ly thuyet plan la `Free`
- nhung Cloudflare co the van bat them billing method
- neu chi dung dung `Cloudflare Tunnel` va khong bat tinh nang tra phi, thong thuong se khong phat sinh phi hang thang

### 6.2. Tao tunnel

Trong Zero Trust:

1. Menu trai -> `Networks`
2. Bam `Connectors`
3. Bam `Add a tunnel`
4. Chon `Cloudflared`
5. Dat ten:

```text
novatify-prod
```

### 6.3. Cai `cloudflared` tren VPS

Tren VPS:

```bash
curl -L --output cloudflared.deb https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-amd64.deb
sudo dpkg -i cloudflared.deb
cloudflared --version
```

### 6.4. Gan tunnel vao VPS

Tren man hinh Cloudflare sau khi tao tunnel, Cloudflare se hien lenh dang:

```bash
sudo cloudflared service install <TOKEN_DAI>
```

Copy nguyen lenh do va chay tren VPS.

Sau do kiem tra:

```bash
sudo systemctl status cloudflared
```

Neu thanh cong se thay:

- `active (running)`
- cac dong `Registered tunnel connection`

## 7. Tao 3 public hostname trong Cloudflare Tunnel

Sau khi tunnel chay:

1. Vao tunnel `novatify-prod`
2. Bam tab `Published application routes`
3. Them 3 route sau

### 7.1. Frontend

- `Subdomain`: `novatify`
- `Domain`: `huybach02.io.vn`
- `Path`: de trong
- `Type`: `HTTP`
- `URL`: `localhost:80`

### 7.2. Backend

- `Subdomain`: `novatify-api`
- `Domain`: `huybach02.io.vn`
- `Path`: de trong
- `Type`: `HTTP`
- `URL`: `localhost:80`

### 7.3. Mercure

- `Subdomain`: `novatify-mercure`
- `Domain`: `huybach02.io.vn`
- `Path`: de trong
- `Type`: `HTTP`
- `URL`: `localhost:80`

Luu y rat quan trong:

- URL la `localhost:80`
- khong phai `localhost:81`
- khong phai `e1.chiasegpu.vn:port`

Ly do:

- Tunnel dua request vao `localhost:80`
- `localhost:80` tren VPS la `nginx-proxy-manager`
- NPM se tiep tuc route theo `Host` header

## 8. Cau hinh Nginx Proxy Manager

### 8.1. Mo giao dien NPM

Trong qua trinh thuc te, port public map cho `81` la:

```text
http://e1.chiasegpu.vn:49349
```

Mo URL do tren may cua ban.

Neu la lan dau, NPM se hien man tao admin account.

Nhap:

- `Full Name`
- `Email address`
- `New Password`

Roi bam `Save`.

### 8.2. Tao Proxy Host cho frontend

Trong NPM:

1. Bam `Hosts`
2. Chon `Proxy Hosts`
3. Bam `Add Proxy Host`

Nhap:

- `Domain Names`: `novatify.huybach02.io.vn`
- `Scheme`: `http`
- `Forward Hostname / IP`: `fe-nginx`
- `Forward Port`: `80`
- `Access List`: `Publicly Accessible`
- `Cache Assets`: tat
- `Block Common Exploits`: bat
- `Websockets Support`: bat

Sau do bam `Save`.

### 8.3. Tao Proxy Host cho backend

Nhap:

- `Domain Names`: `novatify-api.huybach02.io.vn`
- `Scheme`: `http`
- `Forward Hostname / IP`: `be-nginx`
- `Forward Port`: `80`
- `Access List`: `Publicly Accessible`
- `Cache Assets`: tat
- `Block Common Exploits`: bat
- `Websockets Support`: bat

Roi `Save`.

### 8.4. Tao Proxy Host cho Mercure

Nhap:

- `Domain Names`: `novatify-mercure.huybach02.io.vn`
- `Scheme`: `http`
- `Forward Hostname / IP`: `mercure`
- `Forward Port`: `80`
- `Access List`: `Publicly Accessible`
- `Cache Assets`: tat
- `Block Common Exploits`: bat
- `Websockets Support`: bat

Roi `Save`.

Luu y:

- chua can dung tab `SSL`
- vi SSL public dang do Cloudflare xu ly o ngoai

## 9. Xu ly loi Mercure thuc te da gap

### 9.1. Loi gap phai

Luc dau frontend vao duoc, nhung Mercure loi CORS.

Console hien dang:

```text
No 'Access-Control-Allow-Origin' header is present
```

### 9.2. Nguyen nhan

Repo tren VPS van dang dung `docker-compose.yml` cu, phan Mercure CORS van tham chieu domain cu.

### 9.3. Cach sua dung

Trong `docker-compose.yml`, service `mercure` phai co:

```yaml
MERCURE_EXTRA_DIRECTIVES: |
    cors_origins https://novatify.huybach02.io.vn https://novatify-api.huybach02.io.vn https://novatify-mercure.huybach02.io.vn
```

Sau do rebuild lai:

```bash
docker compose --env-file .env.prod.local up -d --build
```

### 9.4. Test CORS cua Mercure

```bash
curl -i -X OPTIONS 'https://novatify-mercure.huybach02.io.vn/.well-known/mercure?topic=test' \
  -H 'Origin: https://novatify.huybach02.io.vn' \
  -H 'Access-Control-Request-Method: GET'
```

Can co header dang:

```text
Access-Control-Allow-Origin: https://novatify.huybach02.io.vn
```

## 10. Xu ly loi login `502 Bad Gateway` thuc te da gap

### 10.1. Hien tuong

Frontend goi:

```text
https://novatify-api.huybach02.io.vn/api/auth/login
```

nhung bi:

```text
502 Bad Gateway
```

### 10.2. Nguyen nhan

`be-php` da bi recreate, nhung `be-nginx` van la container cu.

Trong thuc te:

- `be-nginx` tao truoc do lau
- `be-php` moi recreate
- Nginx backend upstream khong con dong bo

### 10.3. Cach sua

```bash
docker compose --env-file .env.prod.local restart be-nginx
docker compose --env-file .env.prod.local restart npm
```

Hoac recreate chac chan hon:

```bash
docker compose --env-file .env.prod.local up -d --force-recreate be-php be-nginx
```

Sau do login tro lai duoc.

## 11. GitHub Actions CI/CD

### 11.1. Workflow dang dung

Workflow da sua lai de dung ten secret theo VPS, khong dung prefix `EC2` nua.

File:

```text
.github/workflows/deploy.yml
```

Workflow hien tai:

- deploy khi push len `main`
- SSH vao VPS
- `git fetch` + `git reset --hard origin/main`
- `docker compose --env-file .env.prod.local build --no-cache`
- `docker compose --env-file .env.prod.local up -d --force-recreate ...`
- chay migration
- clear cache
- in logs

### 11.2. Cac GitHub Secrets can tao

Trong repo GitHub:

1. Vao `Settings`
2. Vao `Secrets and variables`
3. Vao `Actions`
4. Tao 5 repository secrets:

- `VPS_HOST` = `e1.chiasegpu.vn`
- `VPS_PORT` = `27760`
- `VPS_USERNAME` = `ubuntu`
- `VPS_APP_DIR` = `/home/ubuntu/apps/symvue-novatify`
- `VPS_SSH_KEY` = private key de GitHub Actions SSH vao VPS

### 11.3. Van de thuc te da gap voi SSH

Ban dau local dang SSH vao VPS bang password, khong phai bang key.

Log thuc te da cho thay:

```text
Authenticated ... using "password"
```

Neu de nhu vay, GitHub Actions khong the dung password de deploy.

### 11.4. Cach tao key cho GitHub Actions

Trong qua trinh thuc te cuoi cung da lam theo cach:

1. Tao key file `github_actions_vps`
2. Them public key vao `~/.ssh/authorized_keys` cua user `ubuntu` tren VPS
3. Dung private key do lam secret `VPS_SSH_KEY`

Sau khi key hop le, co the test:

```bash
ssh -i ~/.ssh/github_actions_vps -p 27760 ubuntu@e1.chiasegpu.vn
```

Neu vao duoc ma khong hoi password la dung.

### 11.5. Cach test CI/CD

Commit mot thay doi nho, vi du workflow:

```bash
git add .github/workflows/deploy.yml
git commit -m "update production deploy workflow"
git push origin main
```

Sau do vao:

1. `Actions`
2. Mo workflow `Deploy to Production VPS`
3. Kiem tra job `Deploy App`

Neu thanh cong:

- workflow se SSH vao server duoc
- build Docker duoc
- recreate service duoc
- migration xong
- cache clear xong

## 12. Lenh debug huu ich

### 12.1. Xem service Docker

```bash
docker compose --env-file .env.prod.local ps
```

### 12.2. Xem log backend

```bash
docker compose --env-file .env.prod.local logs --tail=100 be-nginx
docker compose --env-file .env.prod.local logs --tail=100 be-php
```

### 12.3. Xem log Mercure

```bash
docker compose --env-file .env.prod.local logs --tail=100 mercure
```

### 12.4. Xem log NPM

```bash
docker compose --env-file .env.prod.local logs --tail=100 npm
```

### 12.5. Restart service quan trong

```bash
docker compose --env-file .env.prod.local restart be-nginx
docker compose --env-file .env.prod.local restart be-php
docker compose --env-file .env.prod.local restart mercure
docker compose --env-file .env.prod.local restart npm
```

### 12.6. Rebuild lai toan bo

```bash
docker compose --env-file .env.prod.local up -d --build
```

### 12.7. Recreate service de tranh lech version container

```bash
docker compose --env-file .env.prod.local up -d --force-recreate be-php be-nginx fe-nginx mercure messenger scheduler npm
```

## 13. Checklist cuoi cung

### 13.1. Da xong

1. Domain da chuyen qua Cloudflare
2. Tunnel da tao
3. `cloudflared` da chay tren VPS
4. 3 hostname public da tao
5. NPM da route 3 domain dung service
6. Docker stack da chay
7. Migration da chay
8. Mercure CORS da sua
9. Login backend da sua
10. GitHub Actions da doi sang secret `VPS_*`

### 13.2. Can duy tri

1. Khong commit secret that vao repo
2. Luu `.env.prod.local` tren server
3. Kiem tra GitHub Actions sau moi lan push
4. Neu thay `502`, nghi ngay den viec recreate `be-nginx`
5. Neu Mercure loi, kiem tra `MERCURE_EXTRA_DIRECTIVES`

## 14. Gia tri thuc te da dung trong qua trinh nay

```text
Domain goc: huybach02.io.vn
Frontend: novatify.huybach02.io.vn
Backend: novatify-api.huybach02.io.vn
Mercure: novatify-mercure.huybach02.io.vn

SSH VPS: ssh -p 27760 ubuntu@e1.chiasegpu.vn
VPS_HOST: e1.chiasegpu.vn
VPS_PORT: 27760
VPS_USERNAME: ubuntu
VPS_APP_DIR: /home/ubuntu/apps/symvue-novatify
```

## 15. Lenh full deploy thu cong

Neu mot ngay can deploy lai bang tay tu dau sau khi da clone repo va co `.env.prod.local`, dung nguyen bo lenh sau:

```bash
cd ~/apps/symvue-novatify
git fetch origin main
git checkout main
git reset --hard origin/main
docker compose --env-file .env.prod.local build --no-cache
docker compose --env-file .env.prod.local up -d --force-recreate be-php be-nginx fe-nginx mercure messenger scheduler npm
docker compose --env-file .env.prod.local exec -T be-php php bin/console doctrine:migrations:migrate --no-interaction --env=prod
docker compose --env-file .env.prod.local exec -T be-php php bin/console cache:clear --env=prod --no-debug
docker compose --env-file .env.prod.local ps
```
