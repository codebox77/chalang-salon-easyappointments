<?php
/**
 * Dashboard (상황판) for a 16:9 screen in the salon. Standalone page (no backend layout), dark theme, sized with
 * viewport units so that it fills a TV. Data: initial_data (server) + dashboard/data every 30 seconds.
 */
?>
<!doctype html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>상황판 - <?= e(vars('company_name')) ?></title>
    <link rel="icon" type="image/x-icon" href="<?= base_url('assets/img/crcr-favicon.ico') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@300;400;500;700;900&display=swap"
          rel="stylesheet">
    <style>
        :root {
            --bg: #0d0f13;
            --panel: rgba(255, 255, 255, 0.045);
            --panel-border: rgba(255, 255, 255, 0.08);
            --text: #f4f1ea;
            --muted: rgba(244, 241, 234, 0.55);
            --faint: rgba(244, 241, 234, 0.12);
            --accent: #d9b26f;
            --now: #ff5a5f;
            --radius: 1.1vh;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        html, body {
            width: 100%;
            height: 100%;
            overflow: hidden;
            background: radial-gradient(ellipse at 15% 0%, #1d2230 0%, var(--bg) 55%) fixed;
            color: var(--text);
            font-family: 'Noto Sans KR', 'Apple SD Gothic Neo', 'Malgun Gothic', sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        body.idle { cursor: none; }

        #board {
            display: grid;
            grid-template-rows: 11vh 1fr 4vh;
            height: 100vh;
            padding: 2.2vh 2vw 1.2vh;
            gap: 2vh;
        }

        /* Header */
        header {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .brand { display: flex; align-items: center; gap: 1.6vw; }
        .brand img { height: 8vh; filter: drop-shadow(0 0 1.5vh rgba(217, 178, 111, 0.25)); }
        .brand .title { font-size: 2.1vh; letter-spacing: 0.5vh; color: var(--accent); font-weight: 500; }
        .brand .subtitle { font-size: 3.6vh; font-weight: 700; margin-top: 0.3vh; }

        .clock { text-align: right; }
        .clock .time { font-size: 7.4vh; font-weight: 300; line-height: 1; letter-spacing: 0.2vh; font-variant-numeric: tabular-nums; }
        .clock .time .sec { font-size: 3.4vh; color: var(--muted); margin-left: 0.6vh; }
        .clock .date { font-size: 2.3vh; color: var(--muted); margin-top: 0.6vh; }

        /* Main */
        main {
            display: grid;
            grid-template-columns: 1fr 31vw;
            gap: 1.6vw;
            min-height: 0;
        }

        .panel {
            background: var(--panel);
            border: 1px solid var(--panel-border);
            border-radius: calc(var(--radius) * 1.6);
            backdrop-filter: blur(6px);
        }

        .panel-title {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            font-size: 2.2vh;
            font-weight: 700;
            letter-spacing: 0.15vh;
            color: var(--accent);
            margin-bottom: 1.4vh;
        }

        .panel-title small { font-size: 1.7vh; color: var(--muted); font-weight: 400; letter-spacing: 0; }

        /* Schedule (timeline per provider) */
        #schedule { display: flex; flex-direction: column; padding: 2vh 1.4vw 1.6vh; min-height: 0; }

        #schedule-grid {
            position: relative;
            flex: 1;
            display: grid;
            grid-template-columns: 4.2vw repeat(var(--columns, 1), 1fr);
            grid-template-rows: 6vh 1fr;
            column-gap: 0.8vw;
            min-height: 0;
        }

        .col-head {
            display: flex;
            align-items: center;
            gap: 0.8vw;
            padding: 0 0.8vw;
            border-radius: var(--radius);
            background: rgba(255, 255, 255, 0.05);
            font-size: 2.4vh;
            font-weight: 700;
            overflow: hidden;
            white-space: nowrap;
        }

        .col-head .dot { width: 1.6vh; height: 1.6vh; border-radius: 50%; flex: none; box-shadow: 0 0 1.2vh currentColor; }
        .col-head .count { margin-left: auto; font-size: 1.8vh; font-weight: 400; color: var(--muted); }

        .axis, .column { position: relative; margin-top: 1vh; }

        .axis .hour {
            position: absolute;
            right: 0.4vw;
            transform: translateY(-50%);
            font-size: 1.6vh;
            color: var(--muted);
            font-variant-numeric: tabular-nums;
        }

        .column {
            border-radius: var(--radius);
            background: repeating-linear-gradient(to bottom, transparent 0, transparent calc(var(--hour-h) - 1px), var(--faint) calc(var(--hour-h) - 1px), var(--faint) var(--hour-h));
        }

        .break {
            position: absolute;
            left: 0;
            right: 0;
            background: repeating-linear-gradient(45deg, rgba(255, 255, 255, 0.035) 0 0.6vh, transparent 0.6vh 1.2vh);
            border-radius: 0.6vh;
        }

        .appt {
            position: absolute;
            left: 0.3vw;
            right: 0.3vw;
            border-radius: 0.9vh;
            padding: 0.6vh 0.7vw;
            overflow: hidden;
            border-left: 0.6vh solid var(--c);
            background: #262b36; /* Fallback for older TV browsers without color-mix(). */
            background: linear-gradient(135deg, color-mix(in srgb, var(--c) 42%, #14171d) 0%, color-mix(in srgb, var(--c) 22%, #14171d) 100%);
            box-shadow: 0 0.4vh 1.4vh rgba(0, 0, 0, 0.35);
            transition: opacity 0.6s;
        }

        .appt .t { font-size: 1.55vh; color: rgba(255, 255, 255, 0.75); font-variant-numeric: tabular-nums; }
        .appt .n { font-size: 2.1vh; font-weight: 700; line-height: 1.25; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .appt .s { font-size: 1.6vh; color: rgba(255, 255, 255, 0.8); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .appt.short .s, .appt.short .t { display: none; }
        .appt.tiny { padding: 0 0.5vw; display: flex; align-items: center; }
        .appt.tiny .n { font-size: 1.45vh; }
        .appt.tiny .badge { display: none; }
        .appt.past { opacity: 0.38; }
        .appt.cancelled { opacity: 0.3; text-decoration: line-through; }

        .appt.current {
            box-shadow: 0 0 0 0.25vh var(--c), 0 0 3vh color-mix(in srgb, var(--c) 70%, transparent);
            animation: glow 2.4s ease-in-out infinite;
        }

        @keyframes glow { 50% { box-shadow: 0 0 0 0.25vh var(--c), 0 0 1.2vh color-mix(in srgb, var(--c) 40%, transparent); } }

        .badge {
            display: inline-block;
            font-size: 1.3vh;
            padding: 0.15vh 0.5vw;
            border-radius: 2vh;
            background: rgba(0, 0, 0, 0.35);
            margin-left: 0.4vw;
            vertical-align: middle;
        }

        #now-line {
            position: absolute;
            left: 3.6vw;
            right: 0;
            height: 0;
            border-top: 0.3vh solid var(--now);
            z-index: 5;
            pointer-events: none;
            filter: drop-shadow(0 0 0.8vh var(--now));
        }

        #now-line::before {
            content: attr(data-time);
            position: absolute;
            left: -3.9vw;
            top: -1.4vh;
            background: var(--now);
            color: #fff;
            font-size: 1.5vh;
            font-weight: 700;
            padding: 0.2vh 0.4vw;
            border-radius: 0.6vh;
        }

        .empty {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: var(--muted);
            font-size: 2.6vh;
            gap: 1vh;
        }

        .empty b { font-size: 4vh; color: var(--text); font-weight: 300; }

        /* Side */
        aside { display: grid; grid-template-rows: auto auto 1fr auto; gap: 1.8vh; min-height: 0; }

        #stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.8vw; }

        .stat { padding: 1.6vh 0.8vw; text-align: center; }
        .stat .v { font-size: 5.4vh; font-weight: 300; line-height: 1.05; font-variant-numeric: tabular-nums; }
        .stat .l { font-size: 1.6vh; color: var(--muted); margin-top: 0.4vh; }
        .stat.hl .v { color: var(--accent); font-weight: 500; }

        #now-serving, #up-next { padding: 2vh 1.3vw; min-height: 0; overflow: hidden; }

        .serving {
            display: grid;
            grid-template-columns: 0.6vh 1fr auto;
            gap: 1vw;
            align-items: center;
            padding: 1.2vh 0;
            border-top: 1px solid var(--panel-border);
        }

        .serving:first-of-type { border-top: 0; padding-top: 0; }
        .serving .bar { align-self: stretch; border-radius: 0.6vh; background: var(--c); }
        .serving .who { font-size: 2.6vh; font-weight: 700; }
        .serving .what { font-size: 1.8vh; color: var(--muted); margin-top: 0.2vh; }
        .serving .left { text-align: right; font-size: 1.7vh; color: var(--muted); }
        .serving .left b { display: block; font-size: 3vh; color: var(--text); font-weight: 500; font-variant-numeric: tabular-nums; }

        .progress { grid-column: 2 / 4; height: 0.6vh; border-radius: 1vh; background: var(--faint); overflow: hidden; }
        .progress span { display: block; height: 100%; background: var(--c); border-radius: 1vh; transition: width 1s; }

        .next {
            display: grid;
            grid-template-columns: 6.2vw 1fr auto;
            gap: 1vw;
            align-items: center;
            padding: 1.1vh 0;
            border-top: 1px solid var(--panel-border);
        }

        .next:first-of-type { border-top: 0; padding-top: 0; }
        .next .time { font-size: 3.2vh; font-weight: 500; font-variant-numeric: tabular-nums; }
        .next .who { font-size: 2.3vh; font-weight: 700; }
        .next .what { font-size: 1.7vh; color: var(--muted); }
        .next .in { font-size: 1.7vh; padding: 0.4vh 0.7vw; border-radius: 2vh; background: rgba(217, 178, 111, 0.14); color: var(--accent); white-space: nowrap; }
        .next .in.soon { background: rgba(255, 90, 95, 0.18); color: #ff8a8e; }

        .muted-line { color: var(--muted); font-size: 2vh; padding: 1vh 0; }

        #week { padding: 1.8vh 1.3vw 1.4vh; }
        #week-bars { display: grid; grid-template-columns: repeat(7, 1fr); gap: 0.6vw; align-items: end; height: 9vh; }
        .wb { display: flex; flex-direction: column; align-items: center; justify-content: flex-end; height: 100%; gap: 0.4vh; }
        .wb .bar { width: 64%; border-radius: 0.6vh 0.6vh 0.2vh 0.2vh; background: linear-gradient(to top, rgba(217, 178, 111, 0.35), var(--accent)); min-height: 0.4vh; transition: height 0.8s; }
        .wb .c { font-size: 1.6vh; font-weight: 700; }
        .wb .d { font-size: 1.3vh; color: var(--muted); white-space: nowrap; }
        .wb.today .d { color: var(--accent); font-weight: 700; }

        footer { display: flex; align-items: center; justify-content: space-between; font-size: 1.5vh; color: var(--muted); }
        #requests-alert { display: none; color: #ffb4b6; font-weight: 500; }
        #requests-alert.show { display: inline; animation: blink 2s ease-in-out infinite; }
        @keyframes blink { 50% { opacity: 0.45; } }
        #offline { display: none; color: #ff8a8e; }

        /* Controls (fade out when idle) */
        /* Bottom center, so that it never covers the footer texts on the left and right. */
        #controls { position: fixed; left: 50%; transform: translateX(-50%); bottom: 0.8vh; display: flex; gap: 0.6vw; transition: opacity 0.5s; z-index: 20; }
        body.idle #controls { opacity: 0; pointer-events: none; }
        #controls button, #controls a {
            font: inherit; font-size: 1.6vh; color: var(--text); background: rgba(255, 255, 255, 0.1);
            border: 1px solid var(--panel-border); border-radius: 2vh; padding: 0.7vh 1vw; cursor: pointer; text-decoration: none;
        }

        #tv-panel {
            display: none; position: fixed; left: 50%; transform: translateX(-50%); bottom: 6vh; width: 38vw; padding: 2vh 1.4vw; z-index: 21;
            background: #1b1f27; border: 1px solid var(--panel-border); border-radius: calc(var(--radius) * 1.6); font-size: 1.7vh;
        }
        #tv-panel.open { display: block; }
        #tv-panel input { width: 100%; font: inherit; padding: 0.8vh 0.6vw; margin: 1vh 0; border-radius: 0.6vh; border: 1px solid var(--panel-border); background: #0d0f13; color: var(--text); }
        #tv-panel p { color: var(--muted); line-height: 1.6; }
        #tv-panel .row { display: flex; gap: 0.6vw; margin-top: 1vh; }
        #tv-panel button { font: inherit; padding: 0.7vh 1vw; border-radius: 0.8vh; border: 0; cursor: pointer; background: var(--accent); color: #1b1f27; font-weight: 700; }
        #tv-panel button.secondary { background: rgba(255, 255, 255, 0.12); color: var(--text); }
    </style>
</head>
<body>

<div id="board">
    <header>
        <div class="brand">
            <img src="<?= e(vars('company_logo')) ?>" alt="logo">
            <div>
                <div class="title">TODAY'S SCHEDULE</div>
                <div class="subtitle">오늘의 예약 현황</div>
            </div>
        </div>
        <div class="clock">
            <div class="time"><span id="clock-hm">--:--</span><span class="sec" id="clock-s">--</span></div>
            <div class="date" id="date-text"></div>
        </div>
    </header>

    <main>
        <section id="schedule" class="panel">
            <div class="panel-title">
                <span>담당자별 일정</span>
                <small id="hours-text"></small>
            </div>
            <div id="schedule-grid"></div>
        </section>

        <aside>
            <div id="stats">
                <div class="stat panel hl"><div class="v" id="stat-total">0</div><div class="l">오늘 예약</div></div>
                <div class="stat panel"><div class="v" id="stat-now">0</div><div class="l">시술 중</div></div>
                <div class="stat panel"><div class="v" id="stat-left">0</div><div class="l">남은 예약</div></div>
                <div class="stat panel"><div class="v" id="stat-done">0</div><div class="l">완료</div></div>
            </div>

            <section id="now-serving" class="panel">
                <div class="panel-title"><span>지금 시술 중</span></div>
                <div id="serving-list"></div>
            </section>

            <section id="up-next" class="panel">
                <div class="panel-title"><span>다음 손님</span><small id="next-count"></small></div>
                <div id="next-list"></div>
            </section>

            <section id="week" class="panel">
                <div class="panel-title"><span>이번 주 예약</span><small id="week-total"></small></div>
                <div id="week-bars"></div>
            </section>
        </aside>
    </main>

    <footer>
        <span><?= e(vars('company_name')) ?> · 예약은 온라인으로 간편하게</span>
        <span>
            <span id="requests-alert"></span>
            <span id="offline">⚠ 연결이 끊겼습니다. 다시 연결 중…</span>
            <span id="updated"></span>
        </span>
    </footer>
</div>

<div id="controls">
    <button type="button" id="fullscreen">⛶ 전체 화면</button>
    <?php if (!vars('is_tv')): ?>
        <a href="<?= site_url('calendar') ?>">캘린더</a>
        <?php if (vars('can_manage_tv')): ?>
            <button type="button" id="tv-toggle">📺 TV 주소</button>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php if (vars('can_manage_tv')): ?>
    <div id="tv-panel">
        <b>매장 TV용 주소</b>
        <p>TV(또는 TV에 연결한 기기)의 브라우저에서 이 주소를 열면 로그인 없이 상황판이 나옵니다. 고객 이름은 가려지고 전화번호는 나오지 않습니다.</p>
        <?php if (vars('tv_url')): ?>
            <input type="text" id="tv-url" readonly value="<?= e(vars('tv_url')) ?>">
        <?php else: ?>
            <p><b>아직 TV 주소가 없습니다.</b> 아래 버튼으로 만드세요.</p>
        <?php endif; ?>
        <div class="row">
            <?php if (vars('tv_url')): ?>
                <button type="button" id="tv-copy">주소 복사</button>
            <?php endif; ?>
            <form method="post" action="<?= site_url('dashboard/regenerate_token') ?>"
                  onsubmit="return <?= vars('tv_url') ? "confirm('새 주소를 만들면 기존 TV 주소는 더 이상 열리지 않습니다. 계속할까요?')" : 'true' ?>;">
                <input type="hidden" name="csrf_token" value="<?= e(get_instance()->security->get_csrf_hash()) ?>">
                <button type="submit" class="<?= vars('tv_url') ? 'secondary' : '' ?>">
                    <?= vars('tv_url') ? '새 주소 만들기' : 'TV 주소 만들기' ?>
                </button>
            </form>
        </div>
    </div>
<?php endif; ?>

<script>
    (function () {
        const DATA_URL = <?= json_encode(vars('data_url'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const REFRESH_MS = 30 * 1000;
        const LIGHTEN = {normal: 0, light: 0.45, lighter: 0.75};

        let data = <?= json_encode(vars('initial_data'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        let serverOffset = new Date(data.now.replace(' ', 'T')).getTime() - Date.now(); // Use the server clock (Korea time).

        const $ = (id) => document.getElementById(id);
        const esc = (text) => String(text ?? '').replace(/[&<>"']/g, (c) => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c]));
        const pad = (n) => String(n).padStart(2, '0');
        const now = () => new Date(Date.now() + serverOffset);
        const nowMinute = () => { const d = now(); return d.getHours() * 60 + d.getMinutes() + d.getSeconds() / 60; };
        const toMinute = (hhmm) => { const [h, m] = String(hhmm).split(':').map(Number); return h * 60 + m; };
        const fmt = (minute) => pad(Math.floor(minute / 60)) + ':' + pad(Math.round(minute % 60));
        const isCancelled = (a) => /취소|cancel/i.test(a.status);

        function statusColor(a, base) {
            const style = (data.status_styles || {})[a.status] || {};
            return /^#[0-9a-f]{6}$/i.test(style.color || '') ? style.color : base;
        }

        // Clock
        function tickClock() {
            const d = now();
            $('clock-hm').textContent = pad(d.getHours()) + ':' + pad(d.getMinutes());
            $('clock-s').textContent = pad(d.getSeconds());
        }

        // Time range of the board: business hours, widened to include every appointment.
        function range() {
            let start = data.day.open ? toMinute(data.day.open) : 10 * 60;
            let end = data.day.close ? toMinute(data.day.close) : 20 * 60;

            data.appointments.forEach((a) => {
                start = Math.min(start, a.start_minute);
                end = Math.max(end, a.end_minute);
            });

            return {start: Math.floor(start / 60) * 60, end: Math.ceil(end / 60) * 60};
        }

        /**
         * Give overlapping appointments their own lane: returns [{a, lane, lanes}], where lanes is the number of
         * lanes in the group of appointments that overlap each other.
         */
        function layoutLanes(items) {
            const sorted = [...items].sort((x, y) => x.start_minute - y.start_minute || y.end_minute - x.end_minute);
            const result = [];
            let group = [];
            let groupEnd = -1;
            let laneEnds = [];

            const closeGroup = () => {
                const lanes = laneEnds.length;
                group.forEach((entry) => (entry.lanes = lanes));
                result.push(...group);
                group = [];
                laneEnds = [];
            };

            sorted.forEach((a) => {
                if (a.start_minute >= groupEnd && group.length) closeGroup();

                let lane = laneEnds.findIndex((end) => end <= a.start_minute);
                if (lane === -1) lane = laneEnds.push(0) - 1;
                laneEnds[lane] = a.end_minute;

                group.push({a, lane, lanes: 1});
                groupEnd = Math.max(groupEnd, a.end_minute);
            });

            if (group.length) closeGroup();

            return result;
        }

        function renderSchedule() {
            const grid = $('schedule-grid');
            const providers = data.providers;
            const r = range();
            const hours = (r.end - r.start) / 60;

            grid.style.setProperty('--columns', Math.max(providers.length, 1));
            $('hours-text').textContent = data.day.open ? `영업 ${data.day.open} – ${data.day.close}` : '오늘 휴무';

            let html = '<div></div>';

            providers.forEach((p) => {
                const count = data.appointments.filter((a) => a.provider_id === p.id && !isCancelled(a)).length;
                html += `<div class="col-head"><span class="dot" style="background:${p.color};color:${p.color}"></span>${esc(p.name)}<span class="count">${count}건</span></div>`;
            });

            html += '<div class="axis" id="axis"></div>';

            providers.forEach((p) => {
                html += `<div class="column" data-provider="${p.id}"></div>`;
            });

            grid.innerHTML = html;

            const axis = $('axis');
            const height = axis.getBoundingClientRect().height;
            const perMinute = height / (r.end - r.start);
            grid.style.setProperty('--hour-h', perMinute * 60 + 'px');

            for (let h = 0; h <= hours; h++) {
                const label = document.createElement('div');
                label.className = 'hour';
                label.style.top = h * 60 * perMinute + 'px';
                label.textContent = fmt(r.start + h * 60);
                axis.appendChild(label);
            }

            const current = nowMinute();

            providers.forEach((p) => {
                const column = grid.querySelector(`.column[data-provider="${p.id}"]`);

                (data.day.breaks || []).forEach((b) => {
                    const el = document.createElement('div');
                    el.className = 'break';
                    el.style.top = (toMinute(b.start) - r.start) * perMinute + 'px';
                    el.style.height = (toMinute(b.end) - toMinute(b.start)) * perMinute + 'px';
                    column.appendChild(el);
                });

                const items = data.appointments.filter((a) => a.provider_id === p.id);

                layoutLanes(items).forEach(({a, lane, lanes}) => {
                    const el = document.createElement('div');
                    const h = (a.end_minute - a.start_minute) * perMinute;
                    const classes = ['appt'];

                    if (isCancelled(a)) classes.push('cancelled');
                    else if (a.end_minute <= current) classes.push('past');
                    else if (a.start_minute <= current) classes.push('current');
                    if (h < 54) classes.push('short');
                    if (h < 30) classes.push('tiny');

                    el.className = classes.join(' ');
                    el.style.setProperty('--c', statusColor(a, p.color));
                    el.style.top = (a.start_minute - r.start) * perMinute + 1 + 'px';
                    el.style.height = Math.max(h - 2, 16) + 'px';

                    // Overlapping appointments of the same provider are shown side by side.
                    if (lanes > 1) {
                        el.style.left = `calc(${(lane / lanes) * 100}% + 0.15vw)`;
                        el.style.right = 'auto';
                        el.style.width = `calc(${100 / lanes}% - 0.3vw)`;
                    }
                    el.innerHTML =
                        `<div class="n">${esc(a.customer)}${a.status ? `<span class="badge">${esc(a.status)}</span>` : ''}</div>` +
                        `<div class="s">${esc(a.service)}</div>` +
                        `<div class="t">${a.start} – ${a.end}</div>`;
                    column.appendChild(el);
                });
            });

            if (!providers.length || !data.appointments.length) {
                const empty = document.createElement('div');
                empty.className = 'empty';
                empty.innerHTML = '<b>오늘은 아직 예약이 없습니다</b><span>새 예약이 들어오면 여기에 바로 표시됩니다</span>';
                grid.appendChild(empty);
            }

            // Now line
            if (current >= r.start && current <= r.end) {
                const line = document.createElement('div');
                line.id = 'now-line';
                const axisTop = axis.offsetTop;
                line.style.top = axisTop + (current - r.start) * perMinute + 'px';
                line.dataset.time = fmt(Math.floor(current));
                grid.appendChild(line);
            }
        }

        function renderSide() {
            const current = nowMinute();
            const providerById = Object.fromEntries(data.providers.map((p) => [p.id, p]));
            const active = data.appointments.filter((a) => !isCancelled(a));
            const serving = active.filter((a) => a.start_minute <= current && a.end_minute > current);
            const upcoming = active.filter((a) => a.start_minute > current);
            const done = active.filter((a) => a.end_minute <= current);

            $('stat-total').textContent = active.length;
            $('stat-now').textContent = serving.length;
            $('stat-left').textContent = upcoming.length;
            $('stat-done').textContent = done.length;

            $('serving-list').innerHTML = serving.length
                ? serving.map((a) => {
                    const p = providerById[a.provider_id] || {name: '-', color: '#888'};
                    const color = statusColor(a, p.color);
                    const progress = Math.min(100, ((current - a.start_minute) / (a.end_minute - a.start_minute)) * 100);
                    const left = Math.max(0, Math.ceil(a.end_minute - current));
                    return `<div class="serving" style="--c:${color}">
                        <div class="bar"></div>
                        <div><div class="who">${esc(a.customer)}</div><div class="what">${esc(a.service)} · ${esc(p.name)}</div></div>
                        <div class="left"><b>${left}분</b>남음</div>
                        <div class="progress"><span style="width:${progress}%"></span></div>
                    </div>`;
                }).join('')
                : '<div class="muted-line">지금 시술 중인 손님이 없습니다</div>';

            const maxNext = 5;
            $('next-count').textContent = upcoming.length > maxNext ? `외 ${upcoming.length - maxNext}건` : '';
            $('next-list').innerHTML = upcoming.length
                ? upcoming.slice(0, maxNext).map((a) => {
                    const p = providerById[a.provider_id] || {name: '-'};
                    const mins = Math.round(a.start_minute - current);
                    const inText = mins >= 60 ? `${Math.floor(mins / 60)}시간 ${mins % 60 ? (mins % 60) + '분 ' : ''}후` : `${mins}분 후`;
                    return `<div class="next">
                        <div class="time">${a.start}</div>
                        <div><div class="who">${esc(a.customer)}</div><div class="what">${esc(a.service)} · ${esc(p.name)}</div></div>
                        <div class="in ${mins <= 15 ? 'soon' : ''}">${inText}</div>
                    </div>`;
                }).join('')
                : '<div class="muted-line">오늘 남은 예약이 없습니다</div>';

            const max = Math.max(1, ...data.week.map((w) => w.count));
            $('week-total').textContent = '총 ' + data.week.reduce((sum, w) => sum + w.count, 0) + '건';
            $('week-bars').innerHTML = data.week.map((w, i) => `
                <div class="wb ${i === 0 ? 'today' : ''}">
                    <div class="c">${w.count}</div>
                    <div class="bar" style="height:${(w.count / max) * 62}%"></div>
                    <div class="d">${esc(w.label)}</div>
                </div>`).join('');

            const alert = $('requests-alert');
            alert.textContent = data.pending_requests > 0 ? `🔔 승인 대기 중인 변경 요청 ${data.pending_requests}건 · ` : '';
            alert.classList.toggle('show', data.pending_requests > 0);
        }

        function render() {
            $('date-text').textContent = data.date_text;
            renderSchedule();
            renderSide();
        }

        async function load() {
            try {
                const response = await fetch(DATA_URL + (DATA_URL.includes('?') ? '&' : '?') + '_=' + Date.now(), {
                    credentials: 'same-origin',
                    headers: {'Accept': 'application/json'},
                });

                if (!response.ok) throw new Error('HTTP ' + response.status);

                data = await response.json();
                serverOffset = new Date(data.now.replace(' ', 'T')).getTime() - Date.now();
                $('offline').style.display = 'none';
                $('updated').textContent = '업데이트 ' + data.now.slice(11, 16);
                render();
            } catch (error) {
                $('offline').style.display = 'inline';
            }
        }

        // Fullscreen button and idle cursor / controls.
        $('fullscreen').addEventListener('click', () => {
            if (!document.fullscreenElement) document.documentElement.requestFullscreen?.();
            else document.exitFullscreen?.();
        });

        let idleTimer;
        const wake = () => {
            document.body.classList.remove('idle');
            clearTimeout(idleTimer);
            idleTimer = setTimeout(() => {
                if (!$('tv-panel')?.classList.contains('open')) document.body.classList.add('idle');
            }, 3000);
        };
        ['mousemove', 'mousedown', 'touchstart', 'keydown'].forEach((type) => document.addEventListener(type, wake));
        wake();

        $('tv-toggle')?.addEventListener('click', () => $('tv-panel').classList.toggle('open'));
        $('tv-copy')?.addEventListener('click', () => {
            const input = $('tv-url');
            input.select();
            (navigator.clipboard?.writeText(input.value) || Promise.reject()).then(
                () => ($('tv-copy').textContent = '복사됨 ✓'),
                () => document.execCommand('copy'),
            );
        });

        if (new URLSearchParams(location.search).has('tv')) $('tv-panel')?.classList.add('open');

        render();
        $('updated').textContent = '업데이트 ' + data.now.slice(11, 16);
        tickClock();
        setInterval(tickClock, 1000);
        setInterval(load, REFRESH_MS);
        setInterval(() => {
            // Move the now line and update the countdowns.
            renderSchedule();
            renderSide();
        }, 60 * 1000);
        window.addEventListener('resize', () => renderSchedule());
        setTimeout(() => location.reload(), 6 * 60 * 60 * 1000); // Pick up page updates on an always-on screen.
    })();
</script>
</body>
</html>
