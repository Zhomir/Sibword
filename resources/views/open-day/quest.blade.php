@extends('layouts.app')

@section('content')
<div id="quest-root" class="quest-page" data-url="{{ asset('data/quest.json') }}">
    <div class="quest-shell">
        <div class="feature-card quest-card">
            <div class="quest-layout">
                <section class="quest-story">
                    <div class="quest-toolbar quest-toolbar--in-card">
                        <button id="quest-back" class="option-btn quest-toolbar-btn" title="Назад" aria-label="Назад" disabled>↩️</button>
                        <button id="quest-audio-toggle" class="option-btn quest-toolbar-btn" title="Звук" aria-label="Звук">🔊</button>
                        <button id="quest-restart" class="option-btn quest-toolbar-btn" title="Заново" aria-label="Заново">🔄</button>
                    </div>

                    <div id="quest-image" class="quest-image"></div>
                    <div id="quest-speaker" class="quest-speaker">Рассказчик</div>
                    <div id="quest-text" class="quest-text">Загрузка новеллы...</div>
                    <div id="quest-comment" class="quest-comment"></div>
                    <div id="quest-options" class="options-grid quest-options"></div>

                    <div class="quest-progress-track">
                        <div id="quest-progress" class="quest-progress-bar"></div>
                    </div>
                </section>

                <aside id="quest-dictionary" class="quest-dictionary quest-dictionary--sidebar">
                    <h3 class="quest-dictionary-title">Словарь сцены</h3>
                    <div id="quest-dictionary-list" class="quest-dictionary-list"></div>
                </aside>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('js/novel-engine.js') }}"></script>
<script src="{{ asset('js/quest-init.js') }}"></script>
@endsection
