# 찰랑찰랑 온라인 예약 – NAS 설치 안내 (시놀로지 / UGREEN)

설치 방법은 두 가지입니다.

- **방법 A – Docker Hub 이미지 사용 (권장, 가장 간단)**: 이미 만들어진 이미지 `codeboxdocker/chalang-salon-easyappointments`를 받아 실행합니다. 파일 하나(`docker/docker-compose.nas.yml`)만 있으면 됩니다.
- **방법 B – 소스로 직접 빌드**: 저장소 전체를 NAS에 올리고 `docker/Dockerfile`로 이미지를 직접 만듭니다.

이미지는 인텔/AMD(amd64)와 ARM(arm64) 모두 제공되어, NAS 기종에 맞는 것이 자동으로 받아집니다.

구성: **앱 컨테이너**(PHP 8.4 + Apache) + **DB 컨테이너**(MariaDB 10.11). 예약 데이터는 `db` 폴더, 로그·업로드는 `storage` 폴더에 저장됩니다.

> ⚠️ **처음 설치 화면 주의**: DB가 비어 있을 때 접속하면 설치 화면이 나오고, **먼저 입력한 사람이 관리자**가 됩니다. 설치(또는 기존 데이터 이전)를 마칠 때까지는 **NAS 내부망에서만** 접속하고, 공유기 포트 포워딩·역방향 프록시는 그다음에 여세요.

---

## A. Docker Hub 이미지로 설치 (권장)

1. 저장소의 [`docker/docker-compose.nas.yml`](docker/docker-compose.nas.yml)을 받아 PC에서 메모장으로 엽니다.
2. **"바꾸세요" 표시된 값 4개**를 바꾸고, 이름을 `docker-compose.yml`로 저장합니다.
   | 항목 | 넣을 값 |
   |---|---|
   | `BASE_URL` | 손님이 접속할 주소 (끝에 `/` 없이). 처음엔 `http://NAS주소:8080`, HTTPS를 만든 뒤 `https://…` 로 변경 |
   | `DB_PASSWORD` / `MARIADB_PASSWORD` | 같은 값 (길고 무작위로) |
   | `MARIADB_ROOT_PASSWORD` | 다른 값 (길고 무작위로) |
   - 포트 8080이 이미 쓰이면 `"8080:80"`의 왼쪽 숫자를 바꿉니다. (NAS의 Web Station이 80·443을 쓰므로 8080 같은 다른 번호를 씁니다.)
3. NAS의 `docker` 공유 폴더 안에 `chalang-booking` 폴더를 만들고 이 파일 **하나만** 올립니다. (`db`, `storage` 폴더는 처음 실행할 때 자동으로 생깁니다.)
   - 기존 사이트 데이터를 옮길 때는 먼저 아래 **[3. 기존 사이트 데이터 옮기기](#3-기존-사이트-데이터-옮기기)**를 보세요.
4. 프로젝트 생성
   - **시놀로지**: Container Manager → 프로젝트 → 생성 → 경로 `/docker/chalang-booking` → "기존 docker-compose.yml 사용" → 다음 → 웹 포털 건너뜀 → 완료
   - **UGREEN**: Docker 앱 → 프로젝트 → 생성 → 경로 `chalang-booking` 폴더, compose 내용 확인 → 배포
   - **SSH**: `cd /volume1/docker/chalang-booking && sudo docker compose up -d`
5. 이미지를 받고 DB가 준비되면(처음 1~3분) `http://NAS주소:8080` 접속
   - 새로 시작: 설치 화면에서 관리자 계정·매장 정보 입력
   - 기존 데이터를 넣었다면: 예약 페이지가 바로 나오고, 기존 관리자 계정으로 로그인
6. https 주소 만들기는 아래 **1-2(시놀로지) / 2-2(UGREEN)**를 따릅니다. 만든 뒤 `BASE_URL`을 https 주소로 바꾸고 프로젝트를 다시 시작합니다.

**업데이트** (데이터는 그대로 유지)
- 시놀로지: Container Manager → 프로젝트 → `chalang-booking` → 정지 → **작업 → 빌드**(새 이미지를 받음) → 시작
  - 또는 **이미지** 메뉴에서 `codeboxdocker/chalang-salon-easyappointments` → 업데이트 가능 표시가 있으면 **업데이트**
- UGREEN: Docker 앱 → 이미지 → 다시 받기(Pull) → 프로젝트 재배포
- SSH: `sudo docker compose pull && sudo docker compose up -d`

> `latest` 대신 `codeboxdocker/chalang-salon-easyappointments:1.0.0`처럼 버전을 적어 두면, 업데이트 후 문제가 있을 때 이전 버전 번호로 바꿔 되돌릴 수 있습니다. (버전 이미지는 `v1.0.0` 같은 git 태그를 올리면 만들어집니다.)

---

# 방법 B – 소스로 직접 빌드

NAS의 Docker 기능이 `docker/Dockerfile`로 이미지를 직접 만들고(빌드) 실행합니다.

1. GitHub 저장소 → **Code → Download ZIP** → 압축을 풉니다.
2. NAS의 `docker/chalang-booking` 폴더에 압축 푼 파일 **전체**를 올립니다 (`index.php`, `application`, `assets`, `docker` … 가 이 폴더 바로 아래에 있어야 함).
3. `docker/.env.example`을 복사해 `docker/.env`로 만들고 `BASE_URL`, `DB_PASSWORD`, `DB_ROOT_PASSWORD`를 채웁니다.
4. 빌드·실행
   - **SSH** (가장 확실):
     ```bash
     cd /volume1/docker/chalang-booking
     sudo docker compose -f docker/docker-compose.yml --env-file docker/.env up -d --build
     ```
   - **Container Manager / UGREEN 프로젝트**: compose 파일 위치가 `docker/` 안이므로, 프로젝트 경로를 `chalang-booking/docker`로 지정하고 `.env`가 같은 폴더에 있는지 확인합니다. 화면에서 빌드가 안 되면 SSH를 사용하세요.
5. 처음 빌드는 5~10분 걸립니다. 이후 접속·HTTPS 설정은 방법 A와 같습니다.

> 방법 B에서 데이터는 Docker 볼륨(`db-data`, `app-storage`)에 저장됩니다. NAS 백업에는 방법 A(폴더 저장)가 더 편합니다.

---

## 1. 시놀로지 NAS (DSM 7.2 이상, Container Manager)

> DSM 7.2 미만은 "Docker" 패키지를 사용하며, 프로젝트(compose)를 지원하지 않을 수 있습니다. 가능하면 DSM을 7.2 이상으로 업데이트하세요.

### 1-1. 설치

1. **패키지 센터** → `Container Manager` 검색 → 설치
2. **File Station** → `docker` 공유 폴더(Container Manager 설치 시 자동 생성)를 엽니다.
3. `docker` 안에 `chalang-booking` 폴더를 만들고 `docker-compose.yml`(방법 A) 또는 저장소 파일 전체(방법 B)를 올립니다.
4. **Container Manager** → 왼쪽 **프로젝트** → **생성**
   - 프로젝트 이름: `chalang-booking`
   - 경로: `/docker/chalang-booking` 선택
   - 소스: **"기존 docker-compose.yml 사용"** → **다음**
   - 웹 포털 설정(Web Station)은 **건너뜀** → **다음** → **완료**
5. 프로젝트 상태가 **실행 중**이 되고 컨테이너 2개(`chalang-booking`, `chalang-booking-db`)가 모두 초록색이면 완료.
6. 브라우저에서 `http://NAS주소:8080` 접속.

### 1-2. HTTPS 주소 만들기 (카카오 로그인·공유, 휴대폰 공유에 필요)

카카오 로그인·카카오톡 공유와 휴대폰 공유창은 **https 주소**에서 제대로 동작합니다.

1. **제어판 → 외부 액세스 → DDNS**: `우리가게.synology.me` 같은 주소를 만듭니다. (이미 있으면 생략)
2. **제어판 → 보안 → 인증서**: 추가 → Let's Encrypt 인증서 받기 (위 주소, 또는 `salon.우리가게.synology.me`)
3. **제어판 → 로그인 포털 → 고급 → 역방향 프록시 → 생성**
   | 항목 | 소스 | 대상 |
   |---|---|---|
   | 프로토콜 | HTTPS | HTTP |
   | 호스트 이름 | `salon.우리가게.synology.me` (또는 DDNS 주소) | `localhost` |
   | 포트 | `443` (또는 `8443`) | `8080` |
   - **사용자 지정 머리글** 탭 → 생성 → `X-Forwarded-Proto` = `https` 추가
4. **제어판 → 보안 → 인증서 → 설정**에서 방금 만든 역방향 프록시 항목에 인증서를 지정
5. 공유기에서 443(또는 8443) 포트를 NAS로 **포트 포워딩**
6. `docker-compose.yml`의 `BASE_URL`을 `https://salon.우리가게.synology.me`로 바꾸고 프로젝트를 정지 → 시작.
   - 8080 포트는 외부에 열지 말고 NAS 내부용으로만 둡니다.

### 1-3. 업데이트 / 백업

- **업데이트**: 위 「업데이트」 참고. `db`, `storage` 폴더는 그대로 유지됩니다.
- **백업**: Hyper Backup 작업에 `docker/chalang-booking/db`와 `docker/chalang-booking/storage`를 포함. (DB 폴더를 확실하게 백업하려면 백업 시간에 프로젝트를 잠시 정지하거나, 관리자 화면의 DB 백업 기능·`mysqldump`를 함께 사용하세요.)
- **초기화**(모든 데이터 삭제): 프로젝트 정지 → `db` 폴더 안 파일 삭제 → 시작 → 설치 화면부터 다시.

---

## 2. UGREEN NAS (UGOS Pro, Docker 앱)

### 2-1. 설치

1. **앱 센터** → `Docker` 설치
2. **파일** 앱 → `docker` 폴더(없으면 공유 폴더 하나를 만들어 사용)에 `chalang-booking` 폴더를 만들고 `docker-compose.yml`(방법 A) 또는 저장소 파일 전체(방법 B)를 올립니다.
3. **Docker** 앱 → **프로젝트(Project)** → **생성(Create)**
   - 프로젝트 이름: `chalang-booking`
   - 저장 경로: 방금 만든 `chalang-booking` 폴더 선택
   - Compose 설정: 폴더 안의 `docker-compose.yml`을 가져오거나, 그 내용을 붙여넣기
   - **배포(Deploy)** → 이미지를 받고 컨테이너 2개가 실행됩니다(처음 1~3분).
4. 브라우저에서 `http://NAS주소:8080` 접속.

> Docker 앱 화면에서 실행이 잘 안 되는 UGOS 버전이라면 SSH로 직접 실행하세요.
> 1. **제어판 → 터미널 → SSH 켜기**
> 2. PC에서 `ssh 관리자아이디@NAS주소` 접속 후
>    ```bash
>    cd /volume1/docker/chalang-booking      # 실제 폴더 경로에 맞게
>    sudo docker compose up -d
>    ```

### 2-2. HTTPS 주소 만들기

UGOS Pro 버전에 따라 기본 메뉴가 다릅니다. 아래 중 하나를 사용하세요.

- **(A) UGOS 제어판에 "역방향 프록시(Reverse Proxy)" 메뉴가 있는 경우**: 소스 `HTTPS / 도메인 / 443` → 대상 `HTTP / localhost / 8080` 으로 등록하고 인증서(Let's Encrypt)를 연결합니다.
- **(B) 메뉴가 없는 경우 – Nginx Proxy Manager 컨테이너 사용**
  1. Docker 앱 → 이미지 검색 `jc21/nginx-proxy-manager` 로 컨테이너(또는 프로젝트) 생성, 포트 `80`, `443`, `81` 연결
  2. `http://NAS주소:81` 관리 화면 → Proxy Hosts → Add
     - Domain: `salon.내도메인` / Forward: `http`, `NAS주소`, `8080`
     - SSL 탭: Let's Encrypt 인증서 요청, Force SSL 켜기
  3. 공유기에서 80·443 포트를 NAS로 포트 포워딩, 도메인(DDNS)이 NAS 공인 IP를 가리키도록 설정
- **(C) 포트를 열고 싶지 않다면**: Cloudflare Tunnel(`cloudflare/cloudflared` 컨테이너)로 `https://salon.내도메인` → `http://NAS주소:8080` 연결

HTTPS 주소를 만든 뒤 `BASE_URL`을 그 주소로 바꾸고 프로젝트를 재배포합니다.

### 2-3. 업데이트 / 백업

- **업데이트**: Docker 앱 → 이미지 → 다시 받기(Pull) → 프로젝트 재배포 (또는 SSH에서 `sudo docker compose pull && sudo docker compose up -d`)
- **백업**: UGOS 백업 작업에 `chalang-booking/db`, `chalang-booking/storage` 폴더 포함

---

## 3. 기존 사이트 데이터 옮기기

지금 운영 중인 사이트(예: `https://minsol.pe.kr/crcr`, 시놀로지 Web Station)의 예약·고객·설정을 그대로 가져옵니다.

1. **기존 DB 내보내기** (둘 중 하나)
   - phpMyAdmin → `easyappointments` DB 선택 → **내보내기** → 형식 SQL → 실행 → `.sql` 파일 저장
   - SSH: `mysqldump -u root -p easyappointments > crcr.sql`
2. `chalang-booking` 폴더 안에 `initdb` 폴더를 만들고 `crcr.sql`을 넣습니다.
3. 프로젝트를 **처음** 시작하면 `db` 폴더가 비어 있으므로 이 파일을 자동으로 불러옵니다. (이미 한 번 시작했다면: 정지 → `db` 폴더 안 파일 삭제 → 시작)
4. 업로드한 파일이 있으면 기존 사이트의 `storage/uploads` 안 파일을 새 `chalang-booking/storage/uploads`에 복사합니다.
5. 접속해서 기존 관리자 계정으로 로그인되는지, 예약·고객이 보이는지 확인합니다.
6. 불러오기가 끝나면 `initdb` 폴더의 `.sql` 파일은 지웁니다 (고객 정보가 들어 있음).

옮긴 뒤 바뀌는 것:
- 상황판 TV 주소, 텔레그램·카카오 설정, 전화 버튼, 색상 설정은 DB에 들어 있어 **그대로 유지**됩니다.
- **주소가 바뀌었다면** 카카오 개발자 사이트에서 **Web 플랫폼 도메인**과 **Redirect URI**(`<BASE_URL>/index.php/kakao_login/callback`)를 새 주소로 바꿔야 카카오 공유·로그인이 동작합니다. 손님에게 보냈던 예전 예약 링크도 예전 주소로 되어 있습니다.

---

## 4. 문제 해결

| 증상 | 해결 |
|---|---|
| 접속하면 "Database Migration Required" | 관리자로 로그인 후 안내된 `…/index.php/update` 주소에서 업데이트 실행 |
| 앱 컨테이너가 계속 재시작 / "environment variable … is required" | `docker-compose.yml`의 `DB_…` 값이 비어 있음 → 값 채우고 재시작 |
| "Unable to connect to the database" | `DB_PASSWORD`와 `MARIADB_PASSWORD`가 다름, 또는 DB 컨테이너가 아직 준비 중(1~2분 기다림). 이미 만든 `db` 폴더는 비밀번호를 바꿔도 예전 값 유지 → 예전 값으로 맞추거나 `db` 폴더를 비우고 다시 시작(데이터 삭제) |
| 8080 포트 충돌로 시작 안 됨 | `"8080:80"`의 왼쪽 번호 변경 후 재배포 |
| 링크·로그인 후 주소가 이상하게 바뀜 | `BASE_URL`이 실제 접속 주소와 다름 → 맞게 고치고 재시작 |
| 카카오 공유·로그인 오류 | https 주소인지, 카카오 개발자 사이트의 도메인·Redirect URI가 `BASE_URL`과 같은지 확인 |
| 관리자 비밀번호 분실 | 로그인 화면의 「비밀번호를 잊으셨나요?」(메일 설정 필요) 또는 다른 관리자 계정으로 재설정 |

## 5. (선택) 이미지 파일로 옮기기

인터넷이 안 되는 NAS라면, Docker가 설치된 PC에서 이미지를 받아 파일로 옮길 수 있습니다. NAS CPU 종류와 맞춰야 합니다(대부분의 시놀로지 Plus 모델·UGREEN DXP 모델은 `amd64`).

```bash
docker pull --platform linux/amd64 codeboxdocker/chalang-salon-easyappointments:latest
docker pull --platform linux/amd64 mariadb:10.11
docker save codeboxdocker/chalang-salon-easyappointments:latest mariadb:10.11 -o chalang-booking-amd64.tar
```

- 시놀로지: Container Manager → **이미지 → 추가 → 파일에서 추가** → tar 선택
- UGREEN: Docker → **이미지 → 가져오기(Import)** → tar 선택

가져온 뒤에는 방법 A와 같은 방법으로 프로젝트를 만들면 됩니다.
