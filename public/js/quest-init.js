document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('quest-root');
    if (!root) return;

    const dataUrl = root.getAttribute('data-url');
    if (!dataUrl) return;

    fetch(dataUrl)
        .then((response) => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then((data) => {
            if (!window.questEngine) {
                window.questEngine = new QuestEngine(data);
            }

            document.addEventListener('mousemove', (e) => {
                const x = e.clientX / window.innerWidth;
                const y = e.clientY / window.innerHeight;
                root.style.backgroundPosition = `${x * 20}px ${y * 20}px`;
            });
        })
        .catch((error) => {
            console.error('Ошибка загрузки новеллы:', error);
            const textEl = document.getElementById('quest-text');
            if (textEl) {
                textEl.textContent = 'Не удалось загрузить данные новеллы. Попробуйте обновить страницу.';
            }

            const optionsEl = document.getElementById('quest-options');
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

