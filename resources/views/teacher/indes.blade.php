@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="{{ asset('css/teacher-indes.css') }}">
<div class="teacher-page">
    <div class="teacher-shell">
        @php
            $pageMeta = [
                'student_dashboard' => ['title' => 'Профиль студента', 'subtitle' => 'Личная информация и активный курс'],
                'student_profile' => ['title' => 'Профиль студента', 'subtitle' => 'Личная информация и активный курс'],
                'student_courses' => ['title' => 'Выбранные курсы', 'subtitle' => 'Ваши курсы, модули и уроки'],
                'student_progress' => ['title' => 'Прогресс и ачивки', 'subtitle' => 'Статистика обучения и достижения'],
                'student_catalog' => ['title' => 'Каталог курсов', 'subtitle' => 'Поиск, выбор и сортировка'],
                'teacher_home' => ['title' => 'Профиль преподавателя', 'subtitle' => 'Управление рабочими разделами'],
                'teacher_courses' => ['title' => 'Мои курсы', 'subtitle' => 'Структура и наполнение'],
                'teacher_analytics' => ['title' => 'Аналитика', 'subtitle' => 'Статистика и проблемные зоны обучения'],
                'teacher_panel' => ['title' => 'Редактор курса', 'subtitle' => 'Создание уроков и аналитика'],
                'teacher_achievements' => ['title' => 'Курсовые ачивки', 'subtitle' => 'Награды за завершение курсов'],
                'lesson_view' => ['title' => 'Прохождение урока', 'subtitle' => 'Теория, практика и комментарии'],
            ];
            $meta = $pageMeta[$page] ?? ['title' => 'Панель', 'subtitle' => 'Рабочее пространство'];
        @endphp
        <header class="teacher-header">
            <div class="teacher-title">{{ $meta['title'] }} <span class="teacher-subtitle">{{ $meta['subtitle'] }}</span></div>
            <nav class="teacher-nav" aria-label="Навигация по разделам">
                @if(auth()->user()?->isStudent())
                    <a href="{{ route('student.profile') }}" class="teacher-nav-link {{ in_array($page, ['student_dashboard', 'student_profile'], true) ? 'is-active' : '' }}">Профиль</a>
                    <a href="{{ route('student.courses') }}" class="teacher-nav-link {{ $page === 'student_courses' ? 'is-active' : '' }}">Мои курсы</a>
                    <a href="{{ route('student.progress') }}" class="teacher-nav-link {{ $page === 'student_progress' ? 'is-active' : '' }}">Прогресс</a>
                    <a href="{{ route('student.catalog') }}" class="teacher-nav-link {{ $page === 'student_catalog' ? 'is-active' : '' }}">Каталог</a>
                @endif

                @if(auth()->user()?->isTeacher())
                    <a href="{{ route('teacher.home') }}" class="teacher-nav-link {{ $page === 'teacher_home' ? 'is-active' : '' }}">Профиль</a>
                    <a href="{{ route('teacher.analytics.page') }}" class="teacher-nav-link {{ $page === 'teacher_analytics' ? 'is-active' : '' }}">Аналитика</a>
                    <a href="{{ route('teacher.courses.page') }}" class="teacher-nav-link {{ $page === 'teacher_courses' ? 'is-active' : '' }}">Курсы</a>
                    <a href="{{ route('teacher.panel.page') }}" class="teacher-nav-link {{ $page === 'teacher_panel' ? 'is-active' : '' }}">Редактор</a>
                    <a href="{{ route('teacher.achievements.page') }}" class="teacher-nav-link {{ $page === 'teacher_achievements' ? 'is-active' : '' }}">Ачивки</a>
                @endif
            </nav>
        </header>

        @if (session('student_status'))
            <div class="teacher-card">
                <p class="ui-alert success">{{ session('student_status') }}</p>
            </div>
        @endif

        @if ($page === 'student_dashboard' || $page === 'student_profile')
            @include('teacher.pages.student-profile')
        @elseif ($page === 'student_courses')
            @include('teacher.pages.student-courses')
        @elseif ($page === 'student_progress')
            @include('teacher.pages.student-progress')
        @elseif ($page === 'student_catalog')
            @include('teacher.pages.student-catalog')
        @elseif ($page === 'teacher_home')
            @include('teacher.pages.teacher-home')
        @elseif ($page === 'teacher_analytics')
            @include('teacher.pages.teacher-panel-analytics')
        @elseif ($page === 'teacher_courses')
            @include('teacher.pages.teacher-courses')
        @elseif ($page === 'teacher_panel')
            @include('teacher.pages.teacher-panel')
        @elseif ($page === 'teacher_achievements')
            @include('teacher.pages.teacher-achievements')
        @elseif ($page === 'lesson_view')
            @include('teacher.pages.lesson-view')
        @endif
    </div>
</div>

@include('teacher.partials.page-scripts')
@endsection

