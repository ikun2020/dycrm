# DYCRM VPS 部署说明

这套部署方式面向 Debian 12 VPS，使用 Docker Compose 运行 Laravel + Filament 后台、Nginx、MySQL、Redis、队列和定时任务。项目默认只监听 VPS 本机的 `127.0.0.1:3100`，适合用你的域名反向代理访问。

## 1. 服务器准备

```bash
sudo apt update
sudo apt install -y ca-certificates curl git
curl -fsSL https://get.docker.com | sudo sh
sudo usermod -aG docker $USER
```

重新登录 VPS 后确认：

```bash
docker --version
docker compose version
```

## 2. 拉取项目

```bash
git clone <你的仓库地址> dycrm
cd dycrm
cp .env.production.example .env
```

编辑 `.env`：

- `APP_URL` 改成你的域名，例如 `https://crm.yourdomain.com`
- `APP_PORT` 保持 `3100`
- `APP_IMAGE` 改成 GitHub Actions 生成的镜像，例如 `ghcr.io/your-github-user/dycrm:latest`
- `DB_PASSWORD` 和 `DB_ROOT_PASSWORD` 改成强密码
- 后续接 AI 时填写 `OPENAI_API_KEY`

## 3. 初始化 Laravel 项目

当前仓库还没有完整 Laravel 源码时，在 VPS 项目目录执行：

```bash
docker run --rm -v "$PWD":/app -w /app composer:2 sh -lc '
  rm -rf /app/.laravel-tmp
  composer create-project laravel/laravel /app/.laravel-tmp
  find /app/.laravel-tmp -mindepth 1 -maxdepth 1 ! -name .env ! -name .git -exec cp -a {} /app/ \;
  rm -rf /app/.laravel-tmp
'
```

然后安装 Filament：

```bash
docker run --rm -v "$PWD":/app -w /app composer:2 require filament/filament
docker compose -f docker-compose.prod.yml run --rm app php artisan filament:install --panels
```

也可以使用首次部署脚本：

```bash
bash scripts/debian12-first-install.sh
```

脚本第一次运行会安装 Docker 或创建 `.env` 后停下，让你重新登录或填写生产配置；配置好以后再次运行即可继续初始化项目。

## 4. 启动服务

```bash
docker compose -f docker-compose.prod.yml build
docker compose -f docker-compose.prod.yml up -d
docker compose -f docker-compose.prod.yml exec app php artisan key:generate
docker compose -f docker-compose.prod.yml exec app php artisan migrate
docker compose -f docker-compose.prod.yml exec app php artisan make:filament-user
```

如果你已经通过 GitHub Actions 生成了镜像，可以直接拉取镜像启动：

```bash
docker compose -f docker-compose.prod.yml pull
docker compose -f docker-compose.prod.yml up -d
```

如果 GHCR 镜像是私有的，需要先登录：

```bash
echo <你的 GitHub Token> | docker login ghcr.io -u <你的 GitHub 用户名> --password-stdin
```

本机服务监听：

```text
http://127.0.0.1:3100/admin
```

你的域名反代到：

```text
http://127.0.0.1:3100
```

最终访问：

```text
https://你的域名/admin
```

## 5. 域名反代

推荐让你现有的反向代理负责 HTTPS，然后把域名转发到本项目的 `127.0.0.1:3100`。

如果你使用 Nginx，示例配置如下：

```nginx
server {
    listen 80;
    server_name crm.yourdomain.com;

    location / {
        proxy_pass http://127.0.0.1:3100;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

## 6. 日常运维命令

查看服务：

```bash
docker compose -f docker-compose.prod.yml ps
```

查看日志：

```bash
docker compose -f docker-compose.prod.yml logs -f app
```

执行迁移：

```bash
docker compose -f docker-compose.prod.yml exec app php artisan migrate
```

更新代码：

```bash
git pull
docker compose -f docker-compose.prod.yml build
docker compose -f docker-compose.prod.yml up -d
docker compose -f docker-compose.prod.yml exec app php artisan migrate --force
docker compose -f docker-compose.prod.yml exec app php artisan optimize:clear
```

备份数据库：

```bash
docker compose -f docker-compose.prod.yml exec mysql mysqldump -udycrm -p dycrm > dycrm-backup.sql
```
