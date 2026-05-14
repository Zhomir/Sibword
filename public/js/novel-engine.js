class QuestEngine {
    constructor(data) {
        this.data = data || { start_node: '', scenes: {} };
        this.currentNodeKey = this.data.start_node;
        this.history = [];
        this.audioPlayers = {};
        this.audioMuted = false;

        this.speakerEl = document.getElementById('quest-speaker');
        this.textEl = document.getElementById('quest-text');
        this.optionsEl = document.getElementById('quest-options');
        this.commentEl = document.getElementById('quest-comment');
        this.imageEl = document.getElementById('quest-image');
        this.dictionaryWrapEl = document.getElementById('quest-dictionary');
        this.dictionaryListEl = document.getElementById('quest-dictionary-list');
        this.progressEl = document.getElementById('quest-progress');
        this.restartBtn = document.getElementById('quest-restart');
        this.backBtn = document.getElementById('quest-back');
        this.audioToggleBtn = document.getElementById('quest-audio-toggle');

        if (!this.speakerEl || !this.textEl || !this.optionsEl || !this.commentEl) {
            return;
        }

        this.ensureTooltip();
        this.bindInlineWordTooltipEvents();
        this.enhanceScenario();

        this.restartBtn?.addEventListener('click', () => this.restart());
        this.backBtn?.addEventListener('click', () => this.goBack());
        this.audioToggleBtn?.addEventListener('click', () => this.toggleAudio());

        this.renderNode();
    }

    get currentNode() {
        return this.data.scenes?.[this.currentNodeKey];
    }

    ensureTooltip() {
        this.tooltipEl = document.createElement('div');
        this.tooltipEl.className = 'quest-word-tooltip';
        document.body.appendChild(this.tooltipEl);
    }

    bindInlineWordTooltipEvents() {
        this.textEl.addEventListener('mouseover', (event) => {
            const target = event.target.closest('.quest-inline-word');
            if (!target) return;
            const translation = target.getAttribute('data-translation') || '';
            this.showWordTooltip(translation, event.clientX, event.clientY);
        });

        this.textEl.addEventListener('mousemove', (event) => {
            const target = event.target.closest('.quest-inline-word');
            if (!target || this.tooltipEl.style.display !== 'block') return;
            this.moveWordTooltip(event.clientX, event.clientY);
        });

        this.textEl.addEventListener('mouseout', (event) => {
            if (!event.target.closest('.quest-inline-word')) return;
            this.hideWordTooltip();
        });
    }

    showWordTooltip(text, x, y) {
        this.tooltipEl.textContent = text;
        this.tooltipEl.style.display = 'block';
        this.moveWordTooltip(x, y);
    }

    moveWordTooltip(x, y) {
        const offset = 14;
        this.tooltipEl.style.left = `${x + offset}px`;
        this.tooltipEl.style.top = `${y + offset}px`;
    }

    hideWordTooltip() {
        this.tooltipEl.style.display = 'none';
    }

    escapeRegExp(value) {
        return String(value).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    highlightDictionaryWords(text, dictionary) {
        if (!Array.isArray(dictionary) || !dictionary.length) {
            return this.escapeHtml(text || '');
        }

        let highlighted = this.escapeHtml(text || '');
        const sortedWords = [...dictionary]
            .filter((item) => item?.word && item?.translation)
            .sort((a, b) => String(b.word).length - String(a.word).length);

        for (const item of sortedWords) {
            const word = String(item.word);
            const translation = this.escapeHtml(item.translation);
            const regex = new RegExp(this.escapeRegExp(this.escapeHtml(word)), 'gi');
            highlighted = highlighted.replace(regex, (match) => {
                return `<span class="quest-inline-word" data-translation="${translation}" data-word="${this.escapeHtml(word)}">${match}</span>`;
            });
        }

        return highlighted;
    }

    renderNode() {
        const node = this.currentNode;
        if (!node) {
            this.textEl.textContent = 'Ошибка: узел новеллы не найден.';
            this.optionsEl.innerHTML = '';
            this.commentEl.textContent = '';
            this.renderImage(null);
            this.renderDictionary({ dictionary: [] });
            return;
        }

        if (this.history[this.history.length - 1] !== this.currentNodeKey) {
            this.history.push(this.currentNodeKey);
        }

        this.speakerEl.textContent = node.speaker || 'Рассказчик';
        const dictionary = Array.isArray(node.dictionary) ? node.dictionary : [];
        this.textEl.classList.remove('quest-state-loading', 'quest-state-error');
        this.textEl.innerHTML = this.highlightDictionaryWords(node.text || '', dictionary);
        this.commentEl.textContent = '';
        this.renderImage(node.image || null);
        this.renderDictionary(node);
        this.updateProgress();

        if (node.audio) {
            this.playAudio(node.audio);
        } else {
            this.stopAllAudio();
        }

        this.optionsEl.innerHTML = '';
        if (node.is_end) {
            this.renderEnding();
            this.updateBackButton();
            return;
        }

        if (!Array.isArray(node.options) || !node.options.length) {
            const noOptions = document.createElement('div');
            noOptions.textContent = 'Нет доступных вариантов ответа.';
            noOptions.style.color = 'inherit';
            noOptions.style.opacity = '0.9';
            this.optionsEl.appendChild(noOptions);
            this.updateBackButton();
            return;
        }

        node.options.forEach((option) => {
            const btn = document.createElement('button');
            btn.className = 'option-btn';
            btn.textContent = option.text;
            btn.addEventListener('click', () => this.handleOptionClick(option));
            this.optionsEl.appendChild(btn);
        });

        this.updateBackButton();
    }

    renderImage(imageUrl) {
        if (!this.imageEl) return;
        this.imageEl.innerHTML = '';

        if (!imageUrl) return;

        const img = document.createElement('img');
        img.src = imageUrl;
        img.alt = this.currentNode?.speaker || 'Сцена';
        img.style.maxWidth = '100%';
        img.style.maxHeight = '300px';
        img.style.borderRadius = '12px';
        img.style.boxShadow = '0 4px 20px rgba(0,0,0,0.5)';
        img.style.border = '2px solid rgba(255,215,0,0.3)';
        img.style.objectFit = 'cover';
        this.imageEl.appendChild(img);
    }

    renderDictionary(node) {
        if (!this.dictionaryWrapEl || !this.dictionaryListEl) return;

        const dictionary = Array.isArray(node.dictionary) ? node.dictionary : [];
        this.dictionaryListEl.innerHTML = '';

        if (!dictionary.length) {
            this.dictionaryWrapEl.style.display = 'none';
            return;
        }

        this.dictionaryWrapEl.style.display = 'block';
        dictionary.forEach((item) => {
            const row = document.createElement('div');
            row.className = 'quest-dictionary-row';
            row.innerHTML = `
                <span class="quest-dictionary-word">${this.escapeHtml(item.word || '')}</span>
                <span class="quest-dictionary-translation">${this.escapeHtml(item.translation || '')}</span>
            `;
            row.addEventListener('click', () => this.highlightWordInText(item.word || ''));
            this.dictionaryListEl.appendChild(row);
        });
    }

    highlightWordInText(word) {
        if (!word) return;
        const normalized = String(word).toLowerCase();
        const matches = Array.from(this.textEl.querySelectorAll('.quest-inline-word'))
            .filter((el) => String(el.dataset.word || '').toLowerCase().includes(normalized));

        matches.forEach((el) => {
            el.classList.add('word-flash');
            setTimeout(() => el.classList.remove('word-flash'), 900);
        });
    }

    renderEnding() {
        const wrap = document.createElement('div');
        wrap.style.display = 'flex';
        wrap.style.gap = '12px';
        wrap.style.flexWrap = 'wrap';

        const restartBtn = document.createElement('button');
        restartBtn.className = 'main-btn';
        restartBtn.textContent = 'Начать заново';
        restartBtn.addEventListener('click', () => this.restart());

        const shareBtn = document.createElement('button');
        shareBtn.className = 'option-btn';
        shareBtn.textContent = 'Поделиться';
        shareBtn.style.background = 'transparent';
        shareBtn.addEventListener('click', () => this.shareEnding());

        wrap.appendChild(restartBtn);
        wrap.appendChild(shareBtn);
        this.optionsEl.appendChild(wrap);
    }

    playAudio(audioUrl) {
        this.stopAllAudio();
        try {
            const audio = new Audio(audioUrl);
            audio.loop = true;
            audio.volume = 0.5;
            audio.muted = this.audioMuted;
            audio.play().catch(() => {});
            this.audioPlayers[this.currentNodeKey] = audio;
        } catch (_error) {
            // no-op
        }
    }

    stopAllAudio() {
        Object.values(this.audioPlayers).forEach((audio) => {
            audio.pause();
            audio.currentTime = 0;
        });
        this.audioPlayers = {};
    }

    toggleAudio() {
        this.audioMuted = !this.audioMuted;
        Object.values(this.audioPlayers).forEach((audio) => {
            audio.muted = this.audioMuted;
        });
        if (this.audioToggleBtn) {
            this.audioToggleBtn.textContent = this.audioMuted ? 'Звук выкл' : 'Звук вкл';
        }
    }

    handleOptionClick(option) {
        const buttons = this.optionsEl.querySelectorAll('.option-btn');
        buttons.forEach((btn) => {
            btn.disabled = true;
            btn.style.opacity = '0.5';
        });

        this.commentEl.textContent = option.comment || '';
        setTimeout(() => {
            this.currentNodeKey = option.next;
            this.renderNode();
        }, 260);
    }

    goBack() {
        if (this.history.length <= 1) return;
        this.history.pop();
        this.currentNodeKey = this.history.pop();
        this.renderNode();
    }

    updateBackButton() {
        if (!this.backBtn) return;
        const enabled = this.history.length > 1;
        this.backBtn.disabled = !enabled;
        this.backBtn.style.opacity = enabled ? '1' : '0.5';
        this.backBtn.style.cursor = enabled ? 'pointer' : 'not-allowed';
    }

    updateProgress() {
        if (!this.progressEl || !this.data.scenes) return;
        const total = Math.max(Object.keys(this.data.scenes).length, 1);
        const current = Math.min(this.history.length, total);
        const percent = Math.round((current / total) * 100);
        this.progressEl.style.width = `${percent}%`;
    }

    shareEnding() {
        const payload = {
            title: 'Бурятская сказка "Алтан загаһан"',
            text: 'Я прошёл интерактивную новеллу по бурятской сказке о золотой рыбке.',
            url: window.location.href,
        };
        if (navigator.share) {
            navigator.share(payload).catch(() => {});
            return;
        }
        navigator.clipboard?.writeText(window.location.href);
    }

    restart() {
        this.stopAllAudio();
        this.history = [];
        this.currentNodeKey = this.data.start_node;
        this.renderNode();
    }

    enhanceScenario() {
        const scenes = this.data.scenes || {};

        if (scenes.prologue?.text) {
            scenes.prologue.text = String(scenes.prologue.text)
                .replace('русскую сказку', 'бурятскую народную сказку');
        }

        if (scenes.house_request?.options && !scenes.house_request.options.some((opt) => opt.next === 'modest_path')) {
            scenes.house_request.options.push({
                text: 'Сказать старухе, что лучше жить скромно',
                next: 'modest_path',
                comment: 'Попробуем решить мирно'
            });
        }

        if (scenes.queen_request?.options && !scenes.queen_request.options.some((opt) => opt.next === 'quiet_reflection')) {
            scenes.queen_request.options.push({
                text: 'Молча уйти к морю и подумать',
                next: 'quiet_reflection',
                comment: 'Иногда пауза помогает'
            });
        }

        scenes.modest_path = scenes.modest_path || {
            speaker: 'Старик',
            text: 'Скромная жизнь тоже может быть счастливой. Главное — мир в доме.',
            dictionary: [
                { word: 'скромная жизнь', translation: 'простая и спокойная жизнь' },
                { word: 'мир в доме', translation: 'согласие в семье' }
            ],
            options: [
                { text: 'Старуха прислушалась', next: 'sea_house', comment: 'Конфликт стал мягче' },
                { text: 'Старуха снова требует больше', next: 'noble_request', comment: 'Жадность возвращается' }
            ]
        };

        scenes.quiet_reflection = scenes.quiet_reflection || {
            speaker: 'Рассказчик',
            text: 'Старик сел у моря и долго молчал. Волны будто подсказали: не каждое желание стоит исполнять.',
            dictionary: [
                { word: 'волны', translation: 'море и его движение' },
                { word: 'желание', translation: 'хотение, просьба' }
            ],
            options: [
                { text: 'Вернуться домой без новой просьбы', next: 'final_scene', comment: 'Мудрый выбор' },
                { text: 'Все же позвать рыбку', next: 'sea_storm_call', comment: 'Сюжет идет по рискованной ветке' }
            ]
        };
    }
}
