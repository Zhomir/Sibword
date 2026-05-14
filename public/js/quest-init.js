document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('quest-root');
    if (!root) return;

    const dataUrl = root.getAttribute('data-url');
    if (!dataUrl) return;

    const textEl = document.getElementById('quest-text');
    const optionsEl = document.getElementById('quest-options');

    if (textEl) {
        textEl.textContent = 'Загружаем новеллу...';
        textEl.classList.add('quest-state-loading');
    }
    if (optionsEl) {
        optionsEl.innerHTML = '';
    }

    fetch(dataUrl)
        .then((response) => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then((data) => {
            if (textEl) {
                textEl.classList.remove('quest-state-loading');
            }
            if (!window.questEngine) {
                window.questEngine = new QuestEngine(data);
            }

            document.addEventListener('mousemove', (event) => {
                const x = event.clientX / window.innerWidth;
                const y = event.clientY / window.innerHeight;
                root.style.backgroundPosition = `${x * 20}px ${y * 20}px`;
            });
        })
        .catch((error) => {
            console.error('Ошибка загрузки новеллы:', error);
            if (textEl) {
                textEl.textContent = 'Не удалось загрузить данные новеллы. Попробуйте обновить страницу.';
                textEl.classList.remove('quest-state-loading');
                textEl.classList.add('quest-state-error');
            }

            if (optionsEl) {
                optionsEl.innerHTML = '';
                const retryBtn = document.createElement('button');
                retryBtn.className = 'option-btn';
                retryBtn.textContent = 'Попробовать снова';
                retryBtn.addEventListener('click', () => window.location.reload());
                optionsEl.appendChild(retryBtn);
            }
        });
});
