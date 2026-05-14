@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="{{ asset('css/teacher-indes.css') }}">
<link rel="stylesheet" href="{{ asset('css/admin.css') }}">

<div class="teacher-page">
  <div class="teacher-shell">
    <header class="teacher-header">
      <div class="teacher-title">Панель администратора <span class="teacher-subtitle">Главная</span></div>
    </header>

    <section class="admin-profile-stepik">
      <aside class="admin-profile-stepik-left">
        <div class="admin-profile-stepik-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr((string) $adminUser->name, 0, 2)) }}</div>
        <h2>{{ $adminUser->name }}</h2>
        <p>{{ $adminUser->email }}</p>

        <div class="admin-profile-stepik-badges">
          <span class="admin-profile-badge">Роль: {{ $adminUser->role }}</span>
          <span class="admin-profile-badge">Системный доступ</span>
        </div>

        <div class="admin-profile-stepik-actions">
          <a class="btn btn-outline btn-sm" href="{{ route('admin.users.page') }}">Пользователи</a>
          <a class="btn btn-primary btn-sm" href="{{ route('admin.moderation.page') }}">Модерация</a>
        </div>
      </aside>

      <div class="admin-profile-stepik-right">
        <section class="teacher-card admin-metrics admin-metrics--stepik">
          <article class="admin-metric"><span class="admin-metric-label">Пользователи</span><strong class="admin-metric-value">{{ $totalUsers }}</strong></article>
          <article class="admin-metric"><span class="admin-metric-label">Словарь (bxr)</span><strong class="admin-metric-value">{{ $totalDictionaryEntries }}</strong></article>
          <article class="admin-metric"><span class="admin-metric-label">Отзывы на модерации</span><strong class="admin-metric-value">{{ $totalPendingReviews }}</strong></article>
          <article class="admin-metric"><span class="admin-metric-label">Жалобы (новые)</span><strong class="admin-metric-value">{{ $totalPendingReports }}</strong></article>
        </section>

        <section class="teacher-card">
          <h2 class="teacher-section-title">Разделы админки</h2>
          <div class="teacher-courses-grid">
            <article class="teacher-course-card"><div class="teacher-course-card-body"><div class="teacher-course-card-kicker">Ресурс</div><h3 class="teacher-course-card-title">Роли пользователей</h3><p class="teacher-course-card-copy">Поиск, фильтрация и изменение ролей пользователей.</p></div><div class="teacher-course-card-actions"><a class="btn btn-primary btn-sm" href="{{ route('admin.users.page') }}">Открыть</a></div></article>
            <article class="teacher-course-card"><div class="teacher-course-card-body"><div class="teacher-course-card-kicker">Ресурс</div><h3 class="teacher-course-card-title">База знаний</h3><p class="teacher-course-card-copy">Добавление слов, импорт/экспорт CSV и удаление записей.</p></div><div class="teacher-course-card-actions"><a class="btn btn-primary btn-sm" href="{{ route('admin.knowledge.page') }}">Открыть</a></div></article>
            <article class="teacher-course-card"><div class="teacher-course-card-body"><div class="teacher-course-card-kicker">Ресурс</div><h3 class="teacher-course-card-title">Модерация</h3><p class="teacher-course-card-copy">Модерация отзывов курсов и жалоб форума.</p></div><div class="teacher-course-card-actions"><a class="btn btn-primary btn-sm" href="{{ route('admin.moderation.page') }}">Открыть</a></div></article>
          </div>
        </section>
      </div>
    </section>
  </div>
</div>
@endsection
