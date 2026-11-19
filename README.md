# 찰랑찰랑 미용실 온라인 예약

[Easy!Appointments](https://github.com/alextselegidis/easyappointments) 1.6.1을 **찰랑찰랑 미용실**에 맞게 한국어로 고친 손님용 온라인 예약 사이트 + 관리자 화면입니다 (PHP 8.4 + MariaDB, 휴대폰 화면 우선).

> **수정본 안내 (GPL-3.0)** — 이 저장소는 원본 Easy!Appointments(© Alex Tselegidis)를 2026년 11월에 수정한 버전입니다. 원본과 같은 [GPL-3.0](LICENSE) 라이선스를 따릅니다. 이 수정본에 대한 문의는 원제작자가 아니라 이 저장소의 Issues로 해 주세요. 원본 README는 [맨 아래](#원본-easyappointments-readme)에 그대로 있습니다.

## 실행 (Docker, php:8.4-apache 이미지)

```bash
docker compose -f docker/docker-compose.yml --env-file docker/.env up -d --build   # 소스로 빌드
docker compose -f docker/docker-compose.deploy.yml --env-file docker/.env up -d     # 배포된 이미지 사용
```

이미지 (amd64 / arm64, 공개):
- Docker Hub: `codeboxdocker/chalang-salon-easyappointments`
- GitHub: `ghcr.io/codebox77/chalang-salon-easyappointments`

처음 한 번:
1. `docker/.env.example` → `docker/.env` 로 복사하고 `BASE_URL`(손님이 접속할 주소), `DB_PASSWORD`, `DB_ROOT_PASSWORD`, `APP_IMAGE` 를 채웁니다.
2. 실행 후 `http://서버:8080` 접속 → DB가 비어 있으면 설치 화면에서 관리자 계정을 만듭니다.
3. 기존 사이트 데이터를 옮길 때는 DB 덤프를 `docker/initdb/` 에 넣고 처음 실행합니다 (빈 DB일 때 한 번만 불러옴).

- `config.php` 는 이미지에 들어가지 않고, 컨테이너가 시작할 때 환경 변수로 만들어집니다 (비밀번호·키가 이미지에 남지 않음).
- DB는 `db-data`, 로그·세션·업로드는 `app-storage` 볼륨에 저장됩니다.
- 손님용으로는 앞에 HTTPS 역방향 프록시(시놀로지, Nginx 등)를 두세요. 주소가 바뀌면 카카오 개발자 사이트의 도메인·Redirect URI도 바꿔야 합니다.
- 자세한 내용(데이터 이전, 업로드 파일 복사 등): [docker/README.md](docker/README.md)

## 업데이트 배포 (GitHub Actions)

`.github/workflows/docker-publish.yml` 이 GitHub에서 이미지를 빌드(amd64 + arm64)해 GHCR과 Docker Hub에 올립니다.

처음 한 번 (Docker Hub, 이미 설정됨):
1. Docker Hub(codeboxdocker) → Account settings → **Personal access tokens** → 새 토큰(Read & Write)
2. GitHub 저장소 → Settings → Secrets and variables → Actions
   - Secrets: `DOCKERHUB_USERNAME` = `codeboxdocker`, `DOCKERHUB_TOKEN` = 토큰
   - Variables: `DOCKERHUB_IMAGE` = `codeboxdocker/chalang-salon-easyappointments`

이후:
- `main`에 push → `:latest`, `:sha-<커밋>` 갱신 (문서(`*.md`)만 바꾼 push는 빌드하지 않음)
- 버전 배포: `git tag v1.0.0 && git push origin v1.0.0` → `:1.0.0`, `:1.0`
- 서버에서 새 이미지 적용: `docker compose -f docker/docker-compose.deploy.yml --env-file docker/.env pull` 후 `up -d`
- GitHub → Actions 탭에서 진행 상황 확인, 수동 실행(Run workflow)도 가능

## 일반 웹호스팅에 올리기 (예: https://minsol.pe.kr/crcr/)

저장소 파일을 그대로 업로드하면 됩니다. PHP 8.1 이상(mysqli, gd, curl) + MySQL/MariaDB 필요.

- `config-sample.php` 를 `config.php` 로 복사해 주소·DB 정보를 넣습니다. `config.php` 는 저장소에 올리지 않습니다(.gitignore).
- 운영에서는 `DEBUG_MODE = false` 로 둡니다. 이때 `*.min.js` 를 불러오므로, `*.js` 를 고치면 같은 이름의 `*.min.js` 도 갱신해야 합니다.
- `storage/`, `application/`, `system/` 은 `.htaccess` 로 웹 접근이 막혀 있습니다. 저장소 폴더(`.git`, `.github`, `docker/`)도 막아 두었습니다.
- 스크립트·스타일을 고친 뒤에는 `application/config/app.php` 의 `cache_busting_token` 을 바꿔 손님 휴대폰이 새 파일을 받게 합니다.

## 보안

- 한국어 전용: 언어는 항상 한국어(로그인 세션·URL 파라미터로 바뀌지 않음)
- 운영 모드: 오류 상세·SQL은 화면에 보이지 않고 로그에만 기록, 로그에는 함수 인자(비밀번호·고객 정보)를 남기지 않음
- CSRF 토큰, 모든 변경 요청은 POST, 권한(관리자/담당자/비서) 확인
- 고객 카카오 로그인: OAuth `state`(128비트, 1회용) 확인, 로그인 시 세션 ID 재발급, 이동 주소는 허용 목록만
- 카카오 계정 ↔ 고객 연결은 **새로 만든 고객 / 이미 연결된 고객 / 카카오가 인증한 이메일과 같은 고객**만 (남의 이메일을 입력해 예약을 가로챌 수 없음)
- 예약 변경 가능 기간(예약 전 N분)은 화면뿐 아니라 서버에서도 확인
- 비밀 값(텔레그램 봇 토큰, 카카오 Client Secret, 상황판 TV 키 등)은 브라우저·API 응답에 나오지 않음
- 상황판 TV 주소: 128비트 무작위 키, 고객 이름 가림(홍*동)·전화번호 미표시, 새 주소 만들면 이전 주소 즉시 무효
- 보안 헤더: CSP, X-Frame-Options, Referrer-Policy, Permissions-Policy, HSTS(https)

## 기능 요약

| 구분 | 내용 |
|---|---|
| 한국어 | 전체 한국어 번역, 한국식 날짜·요일, 원화 가격(소수점 표시 여부·기본 통화 설정), 휴대폰 번호 안내(010 11자리면 정상 표시) |
| 예약 화면 | 서비스별 소요 시간, 담당자·날짜·시간 고르면 다음 단계로 자동 스크롤, 확인 단계에서 "아직 예약 미완료" 안내와 하단 고정 [예약 확정하기] 버튼, 이름·전화번호 기억 |
| 예약 완료 | 예약 내용 표시, **카카오톡으로 예약 내용 보내기**(나에게 보내기, [예약 확인/변경] 버튼 링크) |
| 예약 변경 방식 | 관리자가 선택: ① 고객이 직접 변경·취소 ② 변경 불가(상태만 보기) ③ **담당자·관리자 승인 후 변경** |
| 변경 요청 | 관리자 「변경 요청」 메뉴(대기 건수 배지)에서 승인/거절, 승인 시 시간 겹침 확인 |
| 카카오 로그인 | 사용 안 함 / 선택(이름·연락처 자동 입력) / 필수, **내 예약**(예정 예약·이용 기록·방문 횟수), 다른 카카오 계정으로 로그인, 관리자 고객 목록에 카카오 프로필 사진 |
| 알림 | **텔레그램**: 새 예약·변경·취소·고객 변경 요청 (채팅 여러 개, 채팅 ID 찾기·테스트 버튼) |
| 전화 버튼 | 관리자가 표시 이름·전화번호 여러 개 등록, 표시할 페이지 선택, 여러 개면 팝업에서 선택 |
| 캘린더 | 예약 팝업에서 **상태를 드롭다운으로 바로 변경**, 담당자별 색상, 상태별 색상·배경 밝기, 30초마다 자동 새로고침 |
| 상황판 | 매장 16:9 TV용: 담당자별 오늘 일정(현재 시각 선), 시술 중·다음 손님, 이번 주 예약 수, 로그인 없는 TV 주소 |
| 브랜딩 | 로고·파비콘·페이지 제목을 매장 것으로, Easy!Appointments 문구·Premium·About 링크 제거 |

### 관리자 설정 위치

| 설정 | 메뉴 |
|---|---|
| 기본 통화·가격 소수점 | 설정 → 일반 → 현지화 |
| 예약 변경 방식, 전화 버튼 | 설정 → 예약 |
| 담당자·상태별 색상 | 설정 → 캘린더 색상 |
| 카카오(공유 키, 고객 로그인), 텔레그램 | 설정 → 연동 |
| 변경 요청 승인 | 상단 메뉴 「변경 요청」 |
| 상황판·TV 주소 | 상단 메뉴 「상황판」 → 📺 TV 주소 |

## 추가·수정한 주요 파일

```
docker/                                   Dockerfile, compose(빌드·배포), 환경 변수 → config.php 생성
.github/workflows/docker-publish.yml      GHCR + Docker Hub 이미지 자동 배포
application/controllers/
  Change_requests.php                     변경 요청 승인 화면
  Dashboard.php                           상황판(TV)
  Kakao_login.php, My_appointments.php    고객 카카오 로그인, 내 예약
  Kakao_settings.php, Telegram_settings.php, Calendar_colors.php   연동·색상 설정
application/libraries/
  Kakao_auth.php                          카카오 로그인(OAuth)·고객 연결
  Telegram_notifier.php                   텔레그램 알림
  Appointment_summary.php                 예약 요약(공유 문구·상태 화면)
application/models/Appointment_change_requests_model.php   변경 요청 저장(테이블 자동 생성)
application/language/korean/              한국어 번역
assets/js/utils/calendar_colors.js        캘린더 색상·상태 드롭다운
assets/js/utils/calendar_auto_refresh.js  캘린더 자동 새로고침
```

새 테이블(`ea_appointment_change_requests`, `ea_kakao_customer_links`)은 처음 쓸 때 자동으로 만들어집니다 (DB 마이그레이션 실행 필요 없음).

---

## 원본 Easy!Appointments README

<h1 align="center">
    <br>
    <a href="https://easyappointments.org">
        <img src="https://raw.githubusercontent.com/alextselegidis/easyappointments/develop/logo.png" alt="Easy!Appointments" width="150">
    </a>
    <br>
    Easy!Appointments
    <br>
</h1>

<h4 align="center">
    A powerful, self-hosted appointment scheduling platform built for flexibility.
</h4>

<p align="center">
  <img alt="License" src="https://img.shields.io/github/license/alextselegidis/easyappointments?style=for-the-badge">
  <img alt="Latest Release" src="https://img.shields.io/github/v/release/alextselegidis/easyappointments?style=for-the-badge">
  <img alt="Downloads" src="https://img.shields.io/github/downloads/alextselegidis/easyappointments/total?style=for-the-badge">
  <a href="https://discord.com/invite/UeeSkaw">
    <img alt="Discord" src="https://img.shields.io/badge/chat-on%20discord-7289da?style=for-the-badge&logo=discord&logoColor=white">
  </a>
</p>

<p align="center">
  <a href="#why-easyappointments">Why Easy!Appointments</a> •
  <a href="#features">Features</a> •
  <a href="#quick-start">Quick Start</a> •
  <a href="#installation">Installation</a> •
  <a href="#license">License</a>
</p>

---

<p align="center">
  <strong>Looking for advanced capabilities?</strong><br>
  Explore premium features and professional services at
  <a href="https://easyappointments.org/premium" target="_blank">easyappointments.org/premium</a>.
</p>

---

![screenshot](screenshot.png)

## 🚀 Why Easy!Appointments

**Easy!Appointments** is an open-source scheduling system that gives you full control over your booking workflow.

It is designed to adapt to your business — whether you need simple appointment booking or more advanced scheduling logic.

**Key advantages:**

- Fully self-hosted — your data stays under your control
- Highly customizable and flexible
- Integrates with your existing website and database
- Free for both personal and commercial use

---

## ✨ Features

Built to support a wide range of scheduling needs:

- Appointment and customer management
- Service and provider organization
- Working plans and booking rules
- Google Calendar synchronization
- Email notification system
- Multi-language interface
- Self-hosted deployment
- Active open-source community

---

## ⚡ Quick Start (Development)

Clone and run the project locally using the provided Docker Compose environment:

```bash
# Clone the repository
git clone https://github.com/alextselegidis/easyappointments.git

# Navigate into the project
cd easyappointments

# Start the Docker environment
docker compose up
```

Then open a second terminal and enter the application container:

```bash id="app-shell"
docker compose exec php-fpm bash
```

Inside the container, install dependencies:

```bash id="deps"
npm install && composer install
```

Start the development watcher:

```bash id="dev"
npm start
```

Build production assets:

```bash id="build"
npm run build
```

> Note: Works on Windows (WSL recommended), macOS, and Linux using Docker Compose.

---

## 🏗️ Installation (Production)

### Requirements

- Apache or Nginx
- PHP 8.2+
- MySQL database

### Steps

1. Create a database (or use an existing one)
2. Upload the `easyappointments` folder to your server
3. Ensure the `storage` directory is writable
4. Rename `config-sample.php` to `config.php`
5. Update configuration values
6. Open the application in your browser and follow the setup wizard

Once completed, the system is ready to use.

---

## 📚 Resources

- Website: [https://easyappointments.org](https://easyappointments.org)
- Issues: [https://github.com/alextselegidis/easyappointments/issues](https://github.com/alextselegidis/easyappointments/issues)
- Support Group: [https://groups.google.com/forum/#!forum/easy-appointments](https://groups.google.com/forum/#!forum/easy-appointments)
- Discord: [https://discord.com/invite/UeeSkaw](https://discord.com/invite/UeeSkaw)

---

## 📜 License

- Code: GPL v3.0
- Content: CC BY 3.0

---

## 👤 Author

- Website: [https://alextselegidis.com](https://alextselegidis.com)
- GitHub: [https://github.com/alextselegidis](https://github.com/alextselegidis)
- Twitter: [https://twitter.com/AlexTselegidis](https://twitter.com/AlexTselegidis)

---

## 🔥 More Projects

- [Plainpad · Self-Hosted Note Taking](https://github.com/alextselegidis/plainpad)
- [Clientverse · CRM Application](https://github.com/alextselegidis/clientverse)
- [Timecrack · Time Tracking](https://github.com/alextselegidis/timecrack)
