class LessonEngine {
    constructor(lessonData) {
        this.lessonData = lessonData;
        this.lessonId = Number(lessonData.lessonId || 0);
        this.completeUrl = lessonData.completeUrl || '/dashboard';
        this.completeRequestUrl = lessonData.completeRequestUrl || null;
        this.csrfToken = lessonData.csrfToken || null;
        this.currentStepIndex = 0;
        this.hp = 3;
        this.score = 0;
        
        // Р’СЂРµРјРµРЅРЅРѕРµ С…СЂР°РЅРёР»РёС‰Рµ РґР»СЏ С‚РµРєСѓС‰РµРіРѕ РѕС‚РІРµС‚Р° РїРѕР»СЊР·РѕРІР°С‚РµР»СЏ
        this.currentAnswer = null; 
        this.selectedMatch = null;

        // Р­Р»РµРјРµРЅС‚С‹ РёРЅС‚РµСЂС„РµР№СЃР°
        this.theoryBlock = document.getElementById('theory-block');
        this.practiceBlock = document.getElementById('practice-block');
        this.progressBar = document.getElementById('lesson-progress');
        this.nextBtn = document.getElementById('next-step');
        this.skipBtn = document.getElementById('skip-step');
        this.stepCounterEl = document.getElementById('lesson-step-counter');
        this.modeBadgeEl = document.getElementById('lesson-mode-badge');
        this.hpEl = document.getElementById('lesson-hp');

        // РњР°СЃСЃРёРІ РґР»СЏ С„РёРєСЃР°С†РёРё РЅРµРїСЂР°РІРёР»СЊРЅС‹С…/РїСЂРѕРїСѓС‰РµРЅРЅС‹С… РѕС‚РІРµС‚РѕРІ
        this.wrongAnswers = [];
        this.stepResultsMap = new Map();

        // Р РµР¶РёРј РїРѕРІС‚РѕСЂР°: РїРѕСЃР»Рµ РѕСЃРЅРѕРІРЅРѕРіРѕ РїСЂРѕС…РѕРґР° РїРѕРєР°Р·С‹РІР°РµРј С‚РѕР»СЊРєРѕ РѕС€РёР±РѕС‡РЅС‹Рµ Р·Р°РґР°РЅРёСЏ
        this.retryMode = false;
        this.retryQueue = [];

        // РўРёРїС‹ Р·Р°РґР°РЅРёР№, РєРѕС‚РѕСЂС‹Рµ РїРѕР»СЊР·РѕРІР°С‚РµР»СЊ РѕС‚РєР»СЋС‡РёР» (РЅР°РїСЂРёРјРµСЂ, Р°СѓРґРёРѕ/РіРѕР»РѕСЃ)
        this.disabledTypes = new Set();
        
        this.init();
    }

    init() {
        document.body.classList.add('lesson-active');

        if (!Array.isArray(this.lessonData.steps)) {
            this.lessonData.steps = [];
        }

        this.updateLessonMeta();
        this.updateHpMeta();

        if (this.nextBtn) {
            this.nextBtn.addEventListener('click', () => this.nextStep());
        }
        if (this.skipBtn) {
            this.skipBtn.addEventListener('click', () => this.skipCurrentStep());
        }

        if (!this.theoryBlock || !this.practiceBlock || !this.progressBar || !this.nextBtn) {
            return;
        }

        if (!this.lessonData.steps.length) {
            this.renderEmptyState();
            return;
        }

        this.renderStep();
    }

    renderEmptyState() {
        this.theoryBlock.style.display = 'block';
        this.practiceBlock.style.display = 'none';
        this.theoryBlock.innerHTML = `
            <div class="lesson-empty-state">
                <h2>Урок пока пуст</h2>
                <p>Преподавателю нужно добавить хотя бы один шаг, чтобы урок можно было пройти.</p>
            </div>
        `;
        this.nextBtn.disabled = true;
        this.nextBtn.textContent = 'Нет шагов';
        if (this.skipBtn) {
            this.skipBtn.style.display = 'none';
        }
        if (this.stepCounterEl) {
            this.stepCounterEl.textContent = 'Шагов нет';
        }
        if (this.modeBadgeEl) {
            this.modeBadgeEl.textContent = 'Ожидание контента';
            this.modeBadgeEl.classList.remove('is-retry');
        }
        this.progressBar.style.width = '0%';
    }

    renderStep() {
        const step = this.lessonData.steps[this.currentStepIndex];
        if (!step) {
            this.renderEmptyState();
            return;
        }
        this.currentAnswer = null; // РЎР±СЂРѕСЃ РѕС‚РІРµС‚Р°
        this.updateProgress();

        // РџРѕРєР°Р·С‹РІР°РµРј РєРЅРѕРїРєСѓ "РќРµ РјРѕРіСѓ СЃРµР№С‡Р°СЃ" С‚РѕР»СЊРєРѕ РґР»СЏ Р°СѓРґРёРѕ/РіРѕР»РѕСЃРѕРІС‹С… Р·Р°РґР°РЅРёР№
        if (this.skipBtn) {
            const canSkip = step.type === 'task' && (step.task_type === 'audio_pick' || step.task_type === 'voice');
            this.skipBtn.style.display = canSkip ? 'inline-block' : 'none';
        }

        if (step.type === 'dialog' || step.type === 'theory') {
            this.showTheory(step);
        } else if (step.type === 'task') {
            this.showPractice(step);
        }
    }

    showTheory(data) {
        this.theoryBlock.style.display = 'block';
        this.practiceBlock.style.display = 'none';
        this.nextBtn.textContent = 'Продолжить';

        if (data.type === 'theory') {
            const media = data.media || {};
            const imageHtml = media.image_url ? `<img src="${media.image_url}" alt="" class="lesson-media-image">` : '';
            const audioHtml = media.audio_url ? `<audio controls preload="none" class="lesson-media-audio"><source src="${media.audio_url}"></audio>` : '';
            const videoHtml = media.video_file_url
                ? `<video controls preload="metadata" class="lesson-media-video"><source src="${media.video_file_url}"></video>`
                : (media.video_url ? `<a href="${media.video_url}" target="_blank" rel="noopener noreferrer" class="lesson-media-link">Открыть видео</a>` : '');

            this.theoryBlock.innerHTML = `
                <div class="lesson-theory-card animate-fade-in">
                    ${imageHtml}
                    <div class="lesson-theory-kicker">Теория</div>
                    <h2 class="lesson-theory-title">${data.title || 'Теоретический блок'}</h2>
                    <div class="lesson-theory-content">${data.content || ''}</div>
                    <div class="lesson-theory-media">${audioHtml}${videoHtml}</div>
                </div>
            `;
            return;
        }

        this.theoryBlock.innerHTML = `
            <div class="character-wrap animate-fade-in">
                <img src="${data.character_img}" class="char-img">
                <div class="dialog-bubble">
                    <div class="char-name">${data.name}</div>
                    <div class="char-text">${data.text}</div>
                </div>
            </div>
        `;
    }

    /**
     * РџРѕР»СЊР·РѕРІР°С‚РµР»СЊ РѕС‚РјРµС‡Р°РµС‚, С‡С‚Рѕ РЅРµ РјРѕР¶РµС‚ РІС‹РїРѕР»РЅРёС‚СЊ С‚РµРєСѓС‰РµРµ Р·Р°РґР°РЅРёРµ (Р°СѓРґРёРѕ/РіРѕР»РѕСЃ).
     * РџРѕРјРµС‡Р°РµРј РєР°Рє "РїСЂРѕРїСѓС‰РµРЅРЅРѕРµ" Рё Р±РѕР»СЊС€Рµ РЅРµ РїРѕРєР°Р·С‹РІР°РµРј С‚Р°РєРѕР№ С‚РёРї РґРѕ РєРѕРЅС†Р° СѓСЂРѕРєР°.
     */
    skipCurrentStep() {
        const step = this.lessonData.steps[this.currentStepIndex];
        if (!(step && step.type === 'task')) return;

        // Р—Р°РїРѕРјРёРЅР°РµРј С‚РёРї вЂ“ Р±РѕР»СЊС€Рµ РЅРµ РїРѕРєР°Р·С‹РІР°РµРј С‚Р°РєРёРµ Р·Р°РґР°РЅРёСЏ
        if (step.task_type) {
            this.disabledTypes.add(step.task_type);
        }

        // Р¤РёРєСЃРёСЂСѓРµРј РїСЂРѕРїСѓСЃРє С‚РѕР»СЊРєРѕ РїСЂРё РїРµСЂРІРѕРј РїСЂРѕС…РѕРґРµ (РІ retry РЅРµ РґСѓР±Р»РёСЂСѓРµРј)
        if (!this.retryMode) {
            this.wrongAnswers.push({
                index: this.currentStepIndex,
                step_id: Number(step.step_id || 0) || null,
                task_type: step.task_type,
                question: step.question || null,
                status: 'skipped'
            });
        }

        this.storeStepResult(step, {
            is_correct: false,
            status: 'skipped',
            answer: null,
        });

        this.advanceStep();
    }

    showPractice(data) {
        this.theoryBlock.style.display = 'none';
        this.practiceBlock.style.display = 'block';
        this.nextBtn.textContent = 'Проверить';
        this.nextBtn.style.visibility = '';
        this.nextBtn.disabled = false;
        const retryBadge = this.retryMode ? '<p class="retry-badge">Работа над ошибками</p>' : '';
        this.practiceBlock.innerHTML = retryBadge + `<h2 class="task-title">${data.question}</h2>`;

        const container = document.createElement('div');
        container.className = `task-container task-${data.task_type}`;
        this.practiceBlock.appendChild(container);

        switch(data.task_type) {
            case 'word_order':       this.renderWordOrder(container, data); break;
            case 'matching':         this.renderMatching(container, data); break;
            case 'fill_blanks':      this.renderFillBlanks(container, data); break;
            case 'audio_pick':       this.renderAudioPick(container, data); break;
            case 'flashcards':       this.renderFlashcards(container, data); break;
            case 'translate':        this.renderTranslate(container, data); break;
            case 'voice':            this.renderVoice(container, data); break;
            case 'multiple_choice':  this.renderMultipleChoice(container, data); break;
        }
    }

    // 1. РЎР±РѕСЂРєР° С„СЂР°Р·С‹ (РєР»РёРє + drag-and-drop)
    renderWordOrder(container, data) {
        const opts = [...data.options].sort(() => Math.random() - 0.5);
        container.innerHTML = `
            <p class="task-hint">Кликай по словам или перетаскивай их в зону ответа</p>
            <div id="answer-zone" class="drop-zone"></div>
            <div id="words-pool" class="words-pool"></div>
        `;
        const pool = container.querySelector('#words-pool');
        const zone = container.querySelector('#answer-zone');

        opts.forEach(word => {
            const chip = document.createElement('div');
            chip.className = 'word-chip';
            chip.textContent = word;
            chip.draggable = true;
            chip.dataset.word = word;

            chip.ondragstart = (e) => { e.dataTransfer.setData('text/plain', word); e.target.classList.add('dragging'); };
            chip.ondragend = (e) => e.target.classList.remove('dragging');

            chip.onclick = () => {
                if (chip.parentElement === pool) zone.appendChild(chip);
                else pool.appendChild(chip);
            };
            pool.appendChild(chip);
        });

        zone.ondragover = (e) => { e.preventDefault(); zone.classList.add('drop-over'); };
        zone.ondragleave = () => zone.classList.remove('drop-over');
        zone.ondrop = (e) => {
            e.preventDefault();
            zone.classList.remove('drop-over');
            const word = e.dataTransfer.getData('text/plain');
            const chip = container.querySelector(`[data-word="${word}"]`);
            if (chip && chip.parentElement === pool) zone.appendChild(chip);
        };
    }

    // 2. РЎРѕРїРѕСЃС‚Р°РІР»РµРЅРёРµ РїР°СЂ (Matching) вЂ” РґРІР° СЂРµР¶РёРјР°: РїР°СЂС‹ РєР°СЂС‚РѕС‡РµРє РёР»Рё В«СЃР»РѕРІРѕ + 3 РІР°СЂРёР°РЅС‚Р°В»
    renderMatching(container, data) {
        if (data.word && data.options && data.correct_idx != null) {
            // Р РµР¶РёРј В«РѕРґРЅРѕ СЃР»РѕРІРѕ вЂ” С‚СЂРё РІР°СЂРёР°РЅС‚Р°В»
            const opts = [...data.options].sort(() => Math.random() - 0.5);
            container.innerHTML = `
                <p class="task-hint">Выбери правильный перевод</p>
                <div class="match-word">${data.word}</div>
                <div class="options-grid" id="match-opts"></div>
            `;
            opts.forEach((opt, idx) => {
                const origIdx = data.options.indexOf(opt);
                const btn = document.createElement('button');
                btn.className = 'option-btn';
                btn.textContent = opt;
                btn.dataset.idx = String(origIdx);
                btn.onclick = () => {
                    this.currentAnswer = origIdx;
                    container.querySelectorAll('.option-btn').forEach(b => b.classList.remove('selected'));
                    btn.classList.add('selected');
                };
                container.querySelector('#match-opts').appendChild(btn);
            });
        } else {
            // Р РµР¶РёРј В«РґРІРµ РєРѕР»РѕРЅРєРё вЂ” РїРѕРґРѕР±СЂР°С‚СЊ РїР°СЂС‹В»
            const items = [...(data.left || []).map(i => ({...i, side: 'l'})), 
                           ...(data.right || []).map(i => ({...i, side: 'r'}))]
                           .sort(() => Math.random() - 0.5);

            const grid = document.createElement('div');
            grid.className = 'matching-grid';
            items.forEach(item => {
                const card = document.createElement('div');
                card.className = 'match-card';
                card.textContent = item.text;
                card.dataset.id = item.id;

                card.onclick = () => {
                    if (card.classList.contains('matched')) return;
                    if (!this.selectedMatch) {
                        this.selectedMatch = card;
                        card.classList.add('selected');
                    } else {
                        if (this.selectedMatch !== card && this.selectedMatch.dataset.id === card.dataset.id) {
                            card.classList.add('matched');
                            this.selectedMatch.classList.add('matched');
                            this.selectedMatch.classList.remove('selected');
                        } else {
                            this.selectedMatch.classList.remove('selected');
                        }
                        this.selectedMatch = null;
                    }
                };
                grid.appendChild(card);
            });
            container.appendChild(grid);
        }
    }

    // 3. РџСЂРѕРїСѓСЃРєРё (Fill in blanks) вЂ” РѕРґРЅРѕ РёР»Рё РЅРµСЃРєРѕР»СЊРєРѕ РјРµСЃС‚
    renderFillBlanks(container, data) {
        const blanks = data.blanks;
        if (blanks && Array.isArray(blanks) && blanks.length > 0) {
            this._fillBlanksMulti(container, data);
        } else {
            this._fillBlanksSingle(container, data);
        }
    }

    _fillBlanksSingle(container, data) {
        const sentence = document.createElement('div');
        sentence.className = 'sentence-blank';
        sentence.innerHTML = (data.sentence || '').replace('___', '<span id="blank-slot-0" class="blank-slot">...</span>');

        const options = document.createElement('div');
        options.className = 'options-pool';
        (data.options || []).forEach(opt => {
            const btn = document.createElement('button');
            btn.className = 'word-chip';
            btn.textContent = opt;
            btn.onclick = () => {
                const slot = document.getElementById('blank-slot-0');
                if (slot) { slot.textContent = opt; slot.dataset.value = opt; }
                this.currentAnswer = opt;
            };
            options.appendChild(btn);
        });
        container.appendChild(sentence);
        container.appendChild(options);
    }

    _fillBlanksMulti(container, data) {
        const parts = data.blanks;
        const blankIndices = parts.map((p, i) => p.text == null ? i : -1).filter(i => i >= 0);
        const sentence = document.createElement('div');
        sentence.className = 'sentence-blank';
        let html = '';
        parts.forEach((p, i) => {
            if (p.text != null) {
                html += p.text;
            } else {
                html += `<span id="blank-slot-${i}" class="blank-slot" data-idx="${i}">...</span>`;
            }
        });
        sentence.innerHTML = html;

        const optionsWrap = document.createElement('div');
        optionsWrap.className = 'options-pool';
        blankIndices.forEach(idx => {
            const p = parts[idx];
            if (!p || p.text != null) return;
            (p.options || []).forEach(opt => {
                const btn = document.createElement('button');
                btn.className = 'word-chip';
                btn.textContent = opt;
                btn.dataset.idx = String(idx);
                btn.onclick = () => {
                    const slot = document.getElementById(`blank-slot-${idx}`);
                    if (slot) { slot.textContent = opt; slot.dataset.value = opt; }
                    this._updateMultiBlankAnswer(container, blankIndices);
                };
                optionsWrap.appendChild(btn);
            });
        });
        container.appendChild(sentence);
        container.appendChild(optionsWrap);
    }

    _updateMultiBlankAnswer(container, indices) {
        const vals = indices.map(i => {
            const s = document.getElementById(`blank-slot-${i}`);
            return s && s.dataset.value ? s.dataset.value : null;
        });
        const allFilled = vals.every(v => v);
        if (allFilled) this.currentAnswer = vals;
    }

    // 4. РђСѓРґРёРѕ-РІС‹Р±РѕСЂ (С‚РµРєСЃС‚ РёР»Рё РёР·РѕР±СЂР°Р¶РµРЅРёСЏ)
    renderAudioPick(container, data) {
        container.innerHTML = `
            <button class="audio-play-btn" id="play-audio" type="button">Слушать аудио</button>
            <p class="task-hint">Выбери правильный ответ</p>
            <div class="options-grid options-grid--media" id="audio-opts"></div>
        `;
        const audio = new Audio(data.audio_url);
        container.querySelector('#play-audio').onclick = () => audio.play();

        const useImages = data.image_options && Array.isArray(data.image_options) && data.image_options.length > 0;
        const opts = useImages ? data.image_options : (data.options || []);

        opts.forEach((opt, idx) => {
            const btn = document.createElement('button');
            btn.className = 'option-btn';
            if (useImages && typeof opt === 'object' && opt.src) {
                const img = document.createElement('img');
                img.src = opt.src;
                img.alt = opt.alt || '';
                img.className = 'option-image';
                btn.appendChild(img);
                if (opt.label) {
                    const lbl = document.createElement('span');
                    lbl.textContent = opt.label;
                    lbl.className = 'option-label';
                    btn.appendChild(lbl);
                }
            } else {
                btn.textContent = opt;
            }
            btn.onclick = () => {
                this.currentAnswer = idx;
                container.querySelectorAll('.option-btn').forEach(b => b.classList.remove('selected'));
                btn.classList.add('selected');
            };
            container.querySelector('#audio-opts').appendChild(btn);
        });
    }

    // 4.1. РњРЅРѕР¶РµСЃС‚РІРµРЅРЅС‹Р№ РІС‹Р±РѕСЂ (Р±РµР· Р°СѓРґРёРѕ)
    renderMultipleChoice(container, data) {
        const raw = data.options || [];
        const opts = raw.map((text, origIdx) => ({ text, origIdx })).sort(() => Math.random() - 0.5);
        const optionsWrap = document.createElement('div');
        optionsWrap.className = 'options-grid';
        container.appendChild(optionsWrap);

        const isMulti = Array.isArray(data.correct_indices);
        this.currentAnswer = isMulti ? [] : null;

        opts.forEach(({ text, origIdx }) => {
            const btn = document.createElement('button');
            btn.className = 'option-btn';
            btn.textContent = text;
            btn.dataset.origIdx = String(origIdx);

            btn.onclick = () => {
                if (isMulti) {
                    btn.classList.toggle('selected');
                    const arr = Array.isArray(this.currentAnswer) ? this.currentAnswer : [];
                    if (btn.classList.contains('selected')) {
                        if (!arr.includes(origIdx)) arr.push(origIdx);
                    } else {
                        const i = arr.indexOf(origIdx);
                        if (i !== -1) arr.splice(i, 1);
                    }
                    this.currentAnswer = arr;
                } else {
                    this.currentAnswer = origIdx;
                    optionsWrap.querySelectorAll('.option-btn').forEach(b => b.classList.remove('selected'));
                    btn.classList.add('selected');
                }
            };

            optionsWrap.appendChild(btn);
        });
    }

    // 5. Р¤Р»СЌС€-РєР°СЂС‚С‹ (РћР±СѓС‡Р°СЋС‰РёР№ С‚РёРї, РІСЃРµРіРґР° РІРµСЂРЅРѕ)
    renderFlashcards(container, data) {
        const frontContent = data.image_url
            ? `<img src="${data.image_url}" alt="" class="flashcard-image"><span class="flashcard-word">${data.word}</span>`
            : (data.word || '');
        const backContent = data.translation || '';
        const hintHtml = data.hint ? `<p class="flashcard-hint">Подсказка: ${data.hint}</p>` : '';
        container.innerHTML = `
            <div class="flip-card" id="f-card" tabindex="0">
                <div class="flip-card-inner">
                    <div class="flip-card-front">${frontContent}</div>
                    <div class="flip-card-back">${backContent}</div>
                </div>
            </div>
            <p class="flashcard-helper">Нажми на карточку или Space, чтобы перевернуть</p>
            ${hintHtml}
        `;
        const card = container.querySelector('#f-card');
        const flip = () => {
            card.classList.toggle('flipped');
            this.currentAnswer = true;
        };
        card.onclick = (e) => { e.stopPropagation(); flip(); };
        card.onkeydown = (e) => { if (e.code === 'Space' || e.code === 'Enter') { e.preventDefault(); flip(); } };
        card.focus();
    }

    // 6. РџРµСЂРµРІРѕРґ (Р’РІРѕРґ С‚РµРєСЃС‚Р°)
    renderTranslate(container, data) {
        container.innerHTML = `
            <div class="target-sentence">${data.sentence}</div>
            <input type="text" class="translate-input" id="tr-input" placeholder="Введите перевод...">
        `;
        this.currentAnswer = "text_input";
    }

    // 7. Р“РѕР»РѕСЃ (Web Speech API)
    renderVoice(container, data) {
        container.innerHTML = `
            <div class="voice-target">${data.word}</div>
            <button class="audio-play-btn" id="mic-btn" type="button">Начать говорить</button>
            <div id="voice-status" class="voice-status"></div>
        `;
        const btn = container.querySelector('#mic-btn');
        btn.onclick = () => {
            if (!(window.SpeechRecognition || window.webkitSpeechRecognition)) {
                document.getElementById('voice-status').textContent = 'Голосовой ввод не поддерживается в этом браузере.';
                return;
            }
            btn.textContent = "Слушаю...";
            // РџСЂРѕСЃС‚РµР№С€Р°СЏ СЂРµР°Р»РёР·Р°С†РёСЏ SpeechRecognition
            const recognition = new (window.SpeechRecognition || window.webkitSpeechRecognition)();
            recognition.lang = 'ru-RU'; // Р”Р»СЏ Р±СѓСЂСЏС‚СЃРєРѕРіРѕ РЅСѓР¶РЅР° РєР°СЃС‚РѕРјРЅР°СЏ РјРѕРґРµР»СЊ, РїРѕРєР° СЃС‚Р°РІРёРј RU
            recognition.onresult = (event) => {
                const result = event.results[0][0].transcript;
                document.getElementById('voice-status').textContent = "Вы сказали: " + result;
                this.currentAnswer = result.toLowerCase();
                btn.textContent = "Повторить";
            };
            recognition.start();
        };
    }

    /**
     * РџРµСЂРµС…РѕРґ Рє СЃР»РµРґСѓСЋС‰РµРјСѓ РґРѕСЃС‚СѓРїРЅРѕРјСѓ С€Р°РіСѓ.
     * Р’ retry-СЂРµР¶РёРјРµ РёРґС‘Рј РїРѕ РѕС‡РµСЂРµРґРё РѕС€РёР±РѕС‡РЅС‹С… Р·Р°РґР°РЅРёР№.
     */
    advanceStep() {
        if (this.retryMode) {
            const idx = this.retryQueue.indexOf(this.currentStepIndex);
            if (idx < this.retryQueue.length - 1) {
                this.currentStepIndex = this.retryQueue[idx + 1];
                this.renderStep();
                return;
            }
            this.finishLesson();
            return;
        }

        const lastIndex = this.lessonData.steps.length - 1;
        while (this.currentStepIndex < lastIndex) {
            this.currentStepIndex++;
            const next = this.lessonData.steps[this.currentStepIndex];
            if (!(next.type === 'task' && this.disabledTypes.has(next.task_type))) {
                this.renderStep();
                return;
            }
        }
        // РљРѕРЅРµС† РѕСЃРЅРѕРІРЅРѕРіРѕ РїСЂРѕС…РѕРґР°: РµСЃС‚СЊ РѕС€РёР±РєРё в†’ РїРѕРІС‚РѕСЂ РѕС€РёР±РѕС‡РЅС‹С… Р·Р°РґР°РЅРёР№
        const wrongIndices = [...new Set(this.wrongAnswers.map(w => w.index))];
        if (wrongIndices.length > 0) {
            this.retryMode = true;
            this.retryQueue = wrongIndices;
            this.currentStepIndex = this.retryQueue[0];
            this.renderStep();
            return;
        }
        this.finishLesson();
    }

    /**
     * РџРѕРєР°Р·С‹РІР°РµС‚ РІРёР·СѓР°Р»СЊРЅС‹Р№ С„РёРґР±РµРє (РїСЂР°РІРёР»СЊРЅРѕ/РЅРµРїСЂР°РІРёР»СЊРЅРѕ) Рё С‡РµСЂРµР· 1.5 СЃ РїРµСЂРµС…РѕРґРёС‚ РґР°Р»СЊС€Рµ.
     */
    showFeedback(isCorrect, correctAnswerText) {
        const fb = document.createElement('div');
        fb.className = 'answer-feedback ' + (isCorrect ? 'correct' : 'wrong');
        const correctStr = correctAnswerText != null
            ? (Array.isArray(correctAnswerText) ? correctAnswerText.join(', ') : String(correctAnswerText))
            : '';
        fb.textContent = isCorrect ? 'Правильно!' : (correctStr ? 'Неверно. Правильно: ' + correctStr : 'Неверно.');
        this.practiceBlock.appendChild(fb);
        this.nextBtn.style.visibility = 'hidden';
        this.nextBtn.disabled = true;
        setTimeout(() => {
            this.advanceStep();
        }, 1500);
    }

    /**
     * РџРµСЂРµС…РѕРґ РїРѕ РєРЅРѕРїРєРµ "РџСЂРѕРІРµСЂРёС‚СЊ"/"РџСЂРѕРґРѕР»Р¶РёС‚СЊ": С„РёРєСЃРёСЂСѓРµРј СЂРµР·СѓР»СЊС‚Р°С‚, РїРѕРєР°Р·С‹РІР°РµРј С„РёРґР±РµРє, РёРґС‘Рј РґР°Р»СЊС€Рµ.
     */
    nextStep() {
        const step = this.lessonData.steps[this.currentStepIndex];
        if (step.type === 'task') {
            const userAnswer = this.collectUserAnswer(step);
            const isCorrect = this.checkAnswer(step, userAnswer);

            this.storeStepResult(step, {
                is_correct: isCorrect,
                status: isCorrect ? 'correct' : 'wrong',
                answer: userAnswer,
            });

            if (!isCorrect && !this.retryMode) {
                this.wrongAnswers.push({
                    index: this.currentStepIndex,
                    step_id: Number(step.step_id || 0) || null,
                    task_type: step.task_type,
                    question: step.question || null,
                    userAnswer,
                    correctAnswer: this.extractCorrectAnswer(step),
                    status: 'wrong'
                });
            }

            const correctText = this.extractCorrectAnswer(step);
            this.showFeedback(isCorrect, correctText);
            return;
        }

        this.advanceStep();
    }

    /**
     * РЎС‡РёС‚С‹РІР°РµРј РѕС‚РІРµС‚ РїРѕР»СЊР·РѕРІР°С‚РµР»СЏ РґР»СЏ СЂР°Р·РЅС‹С… С‚РёРїРѕРІ Р·Р°РґР°РЅРёР№.
     */
    collectUserAnswer(step) {
        switch(step.task_type) {
            case 'word_order':
                return Array.from(document.querySelectorAll('#answer-zone .word-chip'))
                    .map(el => el.textContent.trim());

            case 'matching':
                if (step.word && step.options != null) return this.currentAnswer;
                return document.querySelectorAll('.match-card.matched').length;

            case 'fill_blanks':
                if (step.blanks && Array.isArray(step.blanks)) {
                    const indices = step.blanks.map((p, i) => p.text == null ? i : -1).filter(i => i >= 0);
                    return indices.map(i => {
                        const s = document.getElementById(`blank-slot-${i}`);
                        return s && s.dataset.value ? s.dataset.value : null;
                    });
                }
                return this.currentAnswer;

            case 'audio_pick':
            case 'multiple_choice':
                return this.currentAnswer;

            case 'flashcards':
                return this.currentAnswer === true;

            case 'translate':
                const input = document.getElementById('tr-input');
                return input ? input.value.toLowerCase().trim() : '';

            case 'voice':
                return this.currentAnswer;

            default:
                return null;
        }
    }

    /**
     * РЎРѕРїРѕСЃС‚Р°РІР»СЏРµРј РѕС‚РІРµС‚ СЃ СЌС‚Р°Р»РѕРЅРѕРј.
     */
    checkAnswer(step, userAnswer) {
        switch(step.task_type) {
            case 'word_order':
                const userArr = Array.isArray(userAnswer) ? userAnswer : [];
                return JSON.stringify(userArr) === JSON.stringify(step.correct_answer);
            
            case 'matching':
                if (step.word && step.options != null)
                    return userAnswer === step.correct_idx;
                return userAnswer === ((step.left || []).length * 2);
            
            case 'fill_blanks':
                if (Array.isArray(userAnswer) && Array.isArray(step.correct_answers))
                    return JSON.stringify(userAnswer) === JSON.stringify(step.correct_answers);
                return userAnswer === step.correct_answer;
            
            case 'audio_pick':
                return userAnswer === step.correct_idx;
            
            case 'multiple_choice':
                if (Array.isArray(step.correct_indices)) {
                    if (!Array.isArray(this.currentAnswer)) return false;
                    const sortArr = arr => [...arr].sort((a, b) => a - b);
                    const userSorted = sortArr(this.currentAnswer);
                    const correctSorted = sortArr(step.correct_indices);
                    return JSON.stringify(userSorted) === JSON.stringify(correctSorted);
                }
                return this.currentAnswer === step.correct_idx;
            
            case 'flashcards':
                return userAnswer === true;

            case 'translate':
                return Array.isArray(step.correct_answers)
                    ? step.correct_answers.includes(userAnswer)
                    : false;

            case 'voice':
                return userAnswer && userAnswer.includes(step.correct_word.toLowerCase());

            default:
                return false;
        }
    }

    /**
     * Р’РѕР·РІСЂР°С‰Р°РµРј "РїСЂР°РІРёР»СЊРЅС‹Р№ РѕС‚РІРµС‚" РґР»СЏ СЂР°Р·РЅС‹С… С‚РёРїРѕРІ вЂ“ РїСЂРёРіРѕРґРёС‚СЃСЏ РґР»СЏ СЂР°Р±РѕС‚С‹ РЅР°Рґ РѕС€РёР±РєР°РјРё.
     */
    extractCorrectAnswer(step) {
        switch(step.task_type) {
            case 'word_order':
                return step.correct_answer;
            case 'fill_blanks':
                return step.correct_answers || step.correct_answer;
            case 'audio_pick':
                return step.correct_idx;
            case 'multiple_choice':
                return Array.isArray(step.correct_indices)
                    ? step.correct_indices
                    : step.correct_idx;
            case 'translate':
                return step.correct_answers || [];
            case 'matching':
                if (step.word && step.options && step.correct_idx != null)
                    return step.options[step.correct_idx];
                return null;
            case 'voice':
            default:
                return null;
        }
    }

    stepResultKey(step) {
        if (step && Number.isInteger(Number(step.step_id)) && Number(step.step_id) > 0) {
            return `step:${Number(step.step_id)}`;
        }
        return `idx:${this.currentStepIndex}`;
    }

    storeStepResult(step, payload = {}) {
        if (!(step && step.type === 'task')) {
            return;
        }

        const stepId = Number(step.step_id || 0);
        const entry = {
            step_id: stepId > 0 ? stepId : null,
            step_index: this.currentStepIndex,
            is_correct: Boolean(payload.is_correct),
            status: String(payload.status || (payload.is_correct ? 'correct' : 'wrong')),
            answer: payload.answer ?? null,
        };

        this.stepResultsMap.set(this.stepResultKey(step), entry);
    }

    buildCompletionPayload() {
        const stepResults = Array.from(this.stepResultsMap.values());
        const totalTaskSteps = stepResults.length;
        const correctTaskSteps = stepResults.filter(item => item.is_correct).length;
        const scorePercent = totalTaskSteps > 0
            ? Math.round((correctTaskSteps / totalTaskSteps) * 10000) / 100
            : null;

        return {
            action: 'complete_lesson',
            lesson_id: this.lessonId > 0 ? this.lessonId : null,
            score_percent: scorePercent,
            wrong_count: this.wrongAnswers.length,
            step_results: stepResults,
        };
    }

    updateProgress() {
        if (!this.lessonData.steps.length || !this.progressBar) {
            return;
        }
        let percent;
        if (this.retryMode && this.retryQueue.length > 0) {
            const idx = this.retryQueue.indexOf(this.currentStepIndex);
            percent = ((idx + 1) / this.retryQueue.length) * 100;
        } else {
            percent = ((this.currentStepIndex + 1) / this.lessonData.steps.length) * 100;
        }
        this.progressBar.style.width = `${Math.min(100, percent)}%`;
        this.updateLessonMeta();
    }

    updateLessonMeta() {
        if (!this.lessonData.steps.length) return;

        if (this.stepCounterEl) {
            if (this.retryMode && this.retryQueue.length > 0) {
                const idx = this.retryQueue.indexOf(this.currentStepIndex);
                this.stepCounterEl.textContent = `Повтор ${Math.max(1, idx + 1)} из ${this.retryQueue.length}`;
            } else {
                this.stepCounterEl.textContent = `Шаг ${this.currentStepIndex + 1} из ${this.lessonData.steps.length}`;
            }
        }

        if (this.modeBadgeEl) {
            this.modeBadgeEl.textContent = this.retryMode ? 'Работа над ошибками' : 'Основной проход';
            this.modeBadgeEl.classList.toggle('is-retry', this.retryMode);
        }
    }

    updateHpMeta() {
        if (this.hpEl) {
            this.hpEl.textContent = `❤ ${this.hp}`;
        }
    }

    finishLesson() {
        const root = document.getElementById('learning-container') || document.body;

        if (this.wrongAnswers && this.wrongAnswers.length > 0) {
            const overlay = document.createElement('div');
            overlay.className = 'wrong-answers-overlay';

            const card = document.createElement('div');
            card.className = 'lesson-summary-card';

            const title = document.createElement('h2');
            title.textContent = 'Урок завершён';
            title.className = 'lesson-summary-title';

            const subtitle = document.createElement('p');
            subtitle.textContent = 'Статистика по ошибкам (' + this.wrongAnswers.length + '):';
            subtitle.className = 'lesson-summary-subtitle';

            const list = document.createElement('div');
            list.className = 'lesson-summary-list';

            this.wrongAnswers.forEach(item => {
                const row = document.createElement('div');
                row.className = 'lesson-summary-item';

                const header = document.createElement('div');
                header.className = 'lesson-summary-item-head';

                const q = document.createElement('div');
                q.textContent = item.question || 'Задание';
                q.className = 'lesson-summary-question';

                const badge = document.createElement('span');
                badge.textContent = item.status === 'skipped' ? 'Пропущено' : 'Ошибка';
                badge.className = 'lesson-summary-badge ' + (item.status === 'skipped' ? 'is-skipped' : 'is-wrong');

                header.appendChild(q);
                header.appendChild(badge);
                row.appendChild(header);

                if (item.status === 'wrong' && (item.userAnswer != null || item.correctAnswer != null)) {
                    const details = document.createElement('div');
                    details.className = 'lesson-summary-details';
                    const parts = [];
                    if (item.userAnswer != null) parts.push('Ваш ответ: ' + (Array.isArray(item.userAnswer) ? item.userAnswer.join(', ') : String(item.userAnswer)));
                    if (item.correctAnswer != null) parts.push('Правильно: ' + (Array.isArray(item.correctAnswer) ? item.correctAnswer.join(', ') : String(item.correctAnswer)));
                    details.textContent = parts.join(' • ');
                    row.appendChild(details);
                }

                list.appendChild(row);
            });

            const footer = document.createElement('div');
            footer.className = 'lesson-summary-footer';

            const closeBtn = document.createElement('button');
            closeBtn.textContent = 'Завершить урок';
            closeBtn.className = 'main-btn lesson-summary-btn';
            closeBtn.onclick = async () => {
                await this.completeLessonRequest(this.buildCompletionPayload());
                root.removeChild(overlay);
                alert('Урок окончен. +50 XP');
                window.location.href = this.completeUrl;
            };
            footer.appendChild(closeBtn);

            card.appendChild(title);
            card.appendChild(subtitle);
            card.appendChild(list);
            card.appendChild(footer);
            overlay.appendChild(card);
            root.appendChild(overlay);
            return;
        }

        this.completeLessonRequest(this.buildCompletionPayload()).finally(() => {
            alert('Урок окончен. +50 XP');
            window.location.href = this.completeUrl;
        });
    }

    async completeLessonRequest(payload = {}) {
        if (!this.completeRequestUrl || !this.csrfToken) return Promise.resolve();
        try {
            await fetch(this.completeRequestUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                },
                body: JSON.stringify(payload),
            });
        } catch (error) {
            console.warn('Complete lesson request failed', error);
        }
    }
}

