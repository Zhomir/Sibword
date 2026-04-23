import QuestEngine from './novel-engine';

document.addEventListener('DOMContentLoaded', async () => {
    const questRoot = document.getElementById('quest-root');

    if (!questRoot) {
        return;
    }

    try {
        const response = await fetch('/data/quest.json');
        const questData = await response.json();

        new QuestEngine(questData);
    } catch (error) {
        console.error('Ошибка загрузки новеллы:', error);

        const textEl = document.getElementById('quest-text');
        if (textEl) {
            textEl.textContent = 'Не удалось загрузить данные новеллы.';
        }
    }
});