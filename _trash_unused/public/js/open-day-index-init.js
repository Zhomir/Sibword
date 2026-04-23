document.addEventListener('DOMContentLoaded', () => {
    document.body.classList.add('lesson-active');

    const openDayData = {
        steps: [
            {
                type: 'dialog',
                name: 'Тамир (Охотник)',
                character_img: '/img/hunter.png',
                text: 'Эжэй! (Привет!) Заблудился в наших лесах, нухэр (друг)? Чтобы пройти дальше, нужно знать язык тайги.',
            },
            {
                type: 'task',
                task_type: 'word_order',
                question: 'Как охотник приветствует друга? Собери фразу: "Сайн байна, нухэр!"',
                options: ['байна', 'нухэр', 'Сайн'],
                correct_answer: ['Сайн', 'байна', 'нухэр'],
            },
            {
                type: 'dialog',
                name: 'Тамир',
                character_img: '/img/hunter.png',
                text: 'Сэбэр! (Отлично). Теперь проверим твою интуицию. Видишь то дерево? Это лиственница.',
            },
            {
                type: 'task',
                task_type: 'audio_pick',
                question: 'Как на бурятском звучит "Лиственница"?',
                audio_url: '/audio/shinese.mp3',
                options: ['Шэнэлен (Лиственница)', 'Нарһан (Сосна)', 'Хулан (Береза)'],
                correct_idx: 0,
            },
            {
                type: 'dialog',
                name: 'Тамир',
                character_img: '/img/hunter.png',
                text: 'Ты способный ученик. Приходи в нашу школу "Слово Сибири", там я научу тебя всему остальному!',
            },
        ],
    };

    const engine = new LessonEngine(openDayData);
    engine.finishLesson = function () {
        alert('Поздравляем! Вы прошли вводный квест.');
        window.location.href = '/register';
    };
});

