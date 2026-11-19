/* ----------------------------------------------------------------------------
 * Easy!Appointments - Online Appointment Scheduler
 *
 * Korean locale data for the third-party UI libraries (FullCalendar, Select2, Trumbowyg), which do not ship with
 * Korean translations in this installation. The data is applied only when the active language is Korean.
 * ---------------------------------------------------------------------------- */

(function () {
    'use strict';

    const fullCalendarLocale = {
        code: 'ko',
        buttonText: {
            prev: '이전',
            next: '다음',
            today: '오늘',
            year: '연도',
            month: '월',
            week: '주',
            day: '일',
            list: '목록',
        },
        weekText: '주',
        allDayText: '종일',
        moreLinkText: '개',
        noEventsText: '일정이 없습니다',
    };

    const select2Language = {
        errorLoading: () => '결과를 불러올 수 없습니다.',
        inputTooLong: (args) => `${args.input.length - args.maximum}자를 삭제해 주세요.`,
        inputTooShort: (args) => `${args.minimum - args.input.length}자 이상 입력해 주세요.`,
        loadingMore: () => '불러오는 중…',
        maximumSelected: (args) => `최대 ${args.maximum}개까지 선택할 수 있습니다.`,
        noResults: () => '결과가 없습니다.',
        searching: () => '검색 중…',
        removeAllItems: () => '모두 삭제',
        removeItem: () => '항목 삭제',
        search: () => '검색',
    };

    const trumbowygLanguage = {
        viewHTML: 'HTML 보기',
        undo: '실행 취소',
        redo: '다시 실행',
        formatting: '서식',
        p: '본문',
        blockquote: '인용문',
        code: '코드',
        header: '제목',
        bold: '굵게',
        italic: '기울임',
        strikethrough: '취소선',
        underline: '밑줄',
        strong: '굵게',
        em: '기울임',
        del: '취소선',
        superscript: '위 첨자',
        subscript: '아래 첨자',
        unorderedList: '글머리 기호 목록',
        orderedList: '번호 목록',
        insertImage: '이미지 넣기',
        link: '링크',
        createLink: '링크 만들기',
        unlink: '링크 삭제',
        _self: '현재 창',
        _blank: '새 창',
        justifyLeft: '왼쪽 정렬',
        justifyCenter: '가운데 정렬',
        justifyRight: '오른쪽 정렬',
        justifyFull: '양쪽 정렬',
        horizontalRule: '가로줄 넣기',
        removeformat: '서식 지우기',
        fullscreen: '전체 화면',
        close: '닫기',
        submit: '확인',
        reset: '취소',
        required: '필수',
        description: '설명',
        title: '제목',
        text: '텍스트',
        target: '대상',
        width: '너비',
    };

    // The library globals are all defined once the DOM is parsed, and this listener is registered before the page
    // modules, so the locale data is in place before any calendar, dropdown or editor gets created.
    document.addEventListener('DOMContentLoaded', () => {
        if (window.FullCalendar && Array.isArray(window.FullCalendar.globalLocales)) {
            window.FullCalendar.globalLocales.push(fullCalendarLocale);
        }

        if (typeof window.vars !== 'function' || window.vars('language_code') !== 'ko') {
            return;
        }

        if (window.jQuery && jQuery.fn.select2) {
            jQuery.fn.select2.defaults.set('language', select2Language);
        }

        // Trumbowyg editors are created without a "lang" option, so they use the "en" texts.
        if (window.jQuery && jQuery.trumbowyg && jQuery.trumbowyg.langs) {
            Object.assign(jQuery.trumbowyg.langs.en, trumbowygLanguage);
        }
    });
})();
