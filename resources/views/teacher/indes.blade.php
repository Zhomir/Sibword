@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="{{ asset('css/teacher-indes.css') }}">
<div class="teacher-page">
    <div class="teacher-shell">
        @php
            $pageMeta = [
                'student_dashboard' => ['title' => 'Личный кабинет', 'subtitle' => 'Обучение и прогресс'],
                'teacher_courses' => ['title' => 'Мои курсы', 'subtitle' => 'Структура и наполнение'],
                'teacher_panel' => ['title' => 'Редактор курса', 'subtitle' => 'Создание уроков и аналитика'],
                'lesson_view' => ['title' => 'Прохождение урока', 'subtitle' => 'Теория, практика и комментарии'],
            ];
            $meta = $pageMeta[$page] ?? ['title' => 'Панель', 'subtitle' => 'Рабочее пространство'];
        @endphp
        <header class="teacher-header">
            <div class="teacher-title">{{ $meta['title'] }} <span class="teacher-subtitle">{{ $meta['subtitle'] }}</span></div>
            <nav class="teacher-nav" aria-label="Навигация по разделам">
                @if(auth()->user()?->isStudent())
                    <a href="{{ route('teacher.indes', ['page' => 'student_dashboard']) }}" class="teacher-nav-link {{ $page === 'student_dashboard' ? 'is-active' : '' }}">Студент</a>
                @endif

                @if(auth()->user()?->isTeacher())
                    <a href="{{ route('teacher.indes', ['page' => 'teacher_courses']) }}" class="teacher-nav-link {{ $page === 'teacher_courses' ? 'is-active' : '' }}">Курсы</a>
                    <a href="{{ route('teacher.indes', ['page' => 'teacher_panel']) }}" class="teacher-nav-link {{ $page === 'teacher_panel' ? 'is-active' : '' }}">Преподаватель</a>
                @endif
            </nav>
        </header>

        @if (session('student_status'))
            <div class="teacher-card">
                <p class="teacher-muted">{{ session('student_status') }}</p>
            </div>
        @endif

        @if ($page === 'student_dashboard')
            @include('teacher.pages.student-dashboard')
        @elseif ($page === 'teacher_courses')
            @include('teacher.pages.teacher-courses')
        @elseif ($page === 'teacher_panel')
            @include('teacher.pages.teacher-panel-analytics')
            @include('teacher.pages.teacher-panel')
        @elseif ($page === 'lesson_view')
            @include('teacher.pages.lesson-view')
        @endif
    </div>
</div>

@include('teacher.partials.page-scripts')
@endsection
