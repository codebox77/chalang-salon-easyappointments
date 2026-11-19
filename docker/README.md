# 찰랑찰랑 예약 사이트 Docker 이미지

PHP 8.4 + Apache 앱 컨테이너와 MariaDB 10.11 컨테이너입니다(운영 서버와 같은 버전).

- **비밀번호·키는 이미지에 들어가지 않습니다.** `config.php`는 컨테이너가 시작할 때 환경 변수로 만들어집니다.
- `storage/`(로그·세션·업로드·백업)와 데이터베이스는 볼륨에 저장되어 컨테이너를 다시 만들어도 남습니다.
- 운영 모드(`DEBUG_MODE=false`)로 실행되고, 수정된 스크립트가 반영되도록 빌드 때 `*.min.js`를 새로 맞춥니다.

## 1. 처음 실행

프로젝트 폴더(`index.php`가 있는 폴더)에서:

```bash
cp docker/.env.example docker/.env
```

`docker/.env`를 열어 `BASE_URL`(손님이 접속할 주소), `DB_PASSWORD`, `DB_ROOT_PASSWORD`를 채웁니다.

```bash
docker compose -f docker/docker-compose.yml --env-file docker/.env up -d --build
```

`http://서버주소:8080` 으로 접속합니다. 데이터베이스가 비어 있으면 설치 화면이 나옵니다.

## 2. 지금 운영 중인 데이터 옮기기

1. 운영 서버에서 DB를 내보냅니다 (예: `mysqldump -u root -p easyappointments > crcr.sql`).
2. `docker/initdb/crcr.sql` 로 넣습니다 (`docker/initdb` 폴더를 만듭니다).
3. **DB 볼륨이 비어 있을 때 처음 한 번만** 자동으로 불러옵니다:

```bash
docker compose -f docker/docker-compose.yml --env-file docker/.env up -d --build
```

4. 업로드 파일이 있으면 `storage/uploads`를 앱 볼륨으로 복사합니다:

```bash
docker compose -f docker/docker-compose.yml cp storage/uploads/. app:/var/www/html/storage/uploads/
```

## 3. 이미지만 만들기

```bash
docker build -f docker/Dockerfile -t crcr-booking:latest .
```

환경 변수: `BASE_URL`, `DB_HOST`, `DB_NAME`, `DB_USERNAME`, `DB_PASSWORD`(또는 `DB_PASSWORD_FILE`), 선택 `KAKAO_JAVASCRIPT_KEY`, `DEBUG_MODE`(기본 false), `LANGUAGE`(기본 korean).

## 4. HTTPS와 카카오

- 손님용으로는 앞단에 HTTPS 역방향 프록시(시놀로지 역방향 프록시, Nginx, Caddy 등)를 두고 `BASE_URL`을 `https://…` 주소로 맞춥니다.
- 주소가 바뀌면 카카오 개발자 사이트의 **Web 플랫폼 도메인**과 **Redirect URI**(`<BASE_URL>/index.php/kakao_login/callback`)도 새 주소로 바꿉니다.
- 상황판 TV 주소, 텔레그램·카카오 설정은 DB에 저장되므로 DB를 옮기면 그대로 유지됩니다.
