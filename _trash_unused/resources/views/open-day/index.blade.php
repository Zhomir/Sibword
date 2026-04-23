@extends('layouts.app')

@section('content')
<div id="learning-container" class="learning-wrapper">
    <div class="learning-header">
        <a href="/" class="lesson-close-link" aria-label="Закрыть урок">✕</a>
        <div class="learning-progress">
            <div id="lesson-progress" class="learning-progress-bar"></div>
        </div>
        <div class="user-hp">❤ 3</div>
    </div>

    <div id="scene-canvas" class="lesson-stage">
        <div id="theory-block" class="scene-content"></div>
        <div id="practice-block" class="task-content is-hidden"></div>
    </div>

    <div class="lesson-controls">
        <button id="next-step" class="main-btn lesson-btn" type="button">Продолжить</button>
        <button id="skip-step" class="main-btn lesson-btn lesson-btn-secondary is-hidden" type="button">Не могу сейчас</button>
    </div>
</div>

<script src="{{ asset('js/lesson-engine.js') }}"></script>
<script src="{{ asset('js/open-day-index-init.js') }}"></script>
@endsection
