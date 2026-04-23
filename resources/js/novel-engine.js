class QuestEngine {
    constructor(data) {
        this.data = data;
        this.currentNodeKey = data.start_node;

        this.speakerEl = document.getElementById('quest-speaker');
        this.textEl = document.getElementById('quest-text');
        this.optionsEl = document.getElementById('quest-options');
        this.commentEl = document.getElementById('quest-comment');
        this.restartBtn = document.getElementById('quest-restart');

        if (!this.speakerEl || !this.textEl || !this.optionsEl || !this.commentEl) {
            console.error('QuestEngine: не найдены нужные DOM-элементы.');
            return;
        }

        if (this.restartBtn) {
            this.restartBtn.addEventListener('click', () => this.restart());
        }

        this.renderNode();
    }

    get currentNode() {
        return this.data.scenes[this.currentNodeKey];
    }

    renderNode() {
        const node = this.currentNode;

        if (!node) {
            this.textEl.textContent = 'Ошибка: узел новеллы не найден.';
            this.optionsEl.innerHTML = '';
            this.commentEl.textContent = '';
            return;
        }

        this.speakerEl.textContent = node.speaker || 'Рассказчик';
        this.textEl.textContent = node.text || '';
        this.commentEl.textContent = '';
        this.optionsEl.innerHTML = '';

        if (node.is_end) {
            const endBtn = document.createElement('button');
            endBtn.className = 'main-btn';
            endBtn.textContent = 'Начать заново';
            endBtn.addEventListener('click', () => this.restart());
            this.optionsEl.appendChild(endBtn);
            return;
        }

        if (!node.options || !node.options.length) {
            const errorText = document.createElement('div');
            errorText.textContent = 'Нет доступных вариантов ответа.';
            errorText.style.color = '#fff';
            this.optionsEl.appendChild(errorText);
            return;
        }

        node.options.forEach(option => {
            const btn = document.createElement('button');
            btn.className = 'option-btn';
            btn.textContent = option.text;

            btn.addEventListener('click', () => {
                this.handleOptionClick(option);
            });

            this.optionsEl.appendChild(btn);
        });
    }

    handleOptionClick(option) {
        const buttons = this.optionsEl.querySelectorAll('.option-btn');
        buttons.forEach(btn => btn.disabled = true);

        this.commentEl.textContent = option.comment || '';

        setTimeout(() => {
            this.currentNodeKey = option.next;
            this.renderNode();
        }, 900);
    }

    restart() {
        this.currentNodeKey = this.data.start_node;
        this.renderNode();
    }
}

document.addEventListener('DOMContentLoaded', () => {
    fetch('/data/quest.json')
        .then(response => {
            if (!response.ok) {
                throw new Error('Не удалось загрузить quest.json');
            }
            return response.json();
        })
        .then(data => {
            new QuestEngine(data);
        })
        .catch(error => {
            console.error('Ошибка загрузки новеллы:', error);

            const textEl = document.getElementById('quest-text');
            if (textEl) {
                textEl.textContent = 'Не удалось загрузить данные новеллы.';
            }
        });
});