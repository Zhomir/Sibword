@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="{{ asset('css/teacher-indes.css') }}">
<link rel="stylesheet" href="{{ asset('css/admin.css') }}">

<div class="teacher-page"><div class="teacher-shell">
  <header class="teacher-header">
    <div class="teacher-title">Админка <span class="teacher-subtitle">Модерация</span></div>
    <nav class="teacher-nav">
      <a class="teacher-nav-link" href="{{ route('admin.index') }}">Главная</a>
      <a class="teacher-nav-link" href="{{ route('admin.users.page') }}">Роли</a>
      <a class="teacher-nav-link" href="{{ route('admin.knowledge.page') }}">База знаний</a>
      <a class="teacher-nav-link is-active" href="{{ route('admin.moderation.page') }}">Модерация</a>
    </nav>
  </header>

  <div id="moderation-status-wrap">@if (session('admin_status'))<div class="teacher-card admin-status admin-status--success">{{ session('admin_status') }}</div>@endif</div>

  <section class="teacher-card teacher-field">
    <div class="admin-users-head">
      <h2 class="teacher-section-title">Модерация отзывов курсов</h2>
      <p class="teacher-muted">Подтверждайте полезные отзывы и скрывайте некорректные.</p>
    </div>
    <form id="moderation-reviews-filter-form" action="{{ route('admin.moderation.page') }}" method="GET" class="admin-toolbar teacher-field">
      <div><label class="teacher-label" for="review_status">Статус отзывов</label><select id="review_status" name="review_status" class="teacher-select"><option value="pending" @selected(($reviewFilters['status'] ?? 'pending') === 'pending')>Неодобренные</option><option value="approved" @selected(($reviewFilters['status'] ?? '') === 'approved')>Одобренные</option><option value="all" @selected(($reviewFilters['status'] ?? '') === 'all')>Все</option></select></div>
      <input type="hidden" name="report_status" value="{{ $reportFilters['status'] }}">
      <div class="admin-toolbar-actions"><button class="btn btn-primary btn-sm" type="submit">Применить</button></div>
    </form>
    <div id="moderation-reviews-table-wrap">@include('admin.partials.moderation-reviews-table', ['courseReviews' => $courseReviews])</div>
  </section>

  <section class="teacher-card teacher-field">
    <div class="admin-users-head">
      <h2 class="teacher-section-title">Модерация форума</h2>
      <p class="teacher-muted">Проверяйте жалобы и принимайте решения по публикациям.</p>
    </div>
    <form id="moderation-reports-filter-form" action="{{ route('admin.moderation.page') }}" method="GET" class="admin-toolbar teacher-field">
      <input type="hidden" name="review_status" value="{{ $reviewFilters['status'] }}">
      <div><label class="teacher-label" for="report_status">Статус жалоб</label><select id="report_status" name="report_status" class="teacher-select"><option value="pending" @selected(($reportFilters['status'] ?? 'pending') === 'pending')>Новые</option><option value="resolved" @selected(($reportFilters['status'] ?? '') === 'resolved')>Обработанные</option><option value="all" @selected(($reportFilters['status'] ?? '') === 'all')>Все</option></select></div>
      <div class="admin-toolbar-actions"><button class="btn btn-primary btn-sm" type="submit">Применить</button></div>
    </form>
    <div id="moderation-reports-table-wrap">@include('admin.partials.moderation-reports-table', ['moderationReports' => $moderationReports])</div>
  </section>
</div></div>
@endsection

@section('scripts')
<script>
(() => {
  const statusWrap = document.getElementById('moderation-status-wrap');
  const reviewsForm = document.getElementById('moderation-reviews-filter-form');
  const reportsForm = document.getElementById('moderation-reports-filter-form');
  const reviewsWrap = document.getElementById('moderation-reviews-table-wrap');
  const reportsWrap = document.getElementById('moderation-reports-table-wrap');
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  const show = (m,e=false)=>statusWrap.innerHTML=`<div class="teacher-card admin-status ${e?'admin-status--error':'admin-status--success'}">${m}</div>`;
  const reload = async()=>{ const qs = new URLSearchParams([...new FormData(reviewsForm).entries(), ...new FormData(reportsForm).entries()]).toString(); const r=await fetch(`${reviewsForm.action}?${qs}`,{headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}}); const d=await r.json(); if(!r.ok||!d.success) throw new Error(d.message||'Ошибка обновления.'); reviewsWrap.innerHTML=d.reviews_html||''; reportsWrap.innerHTML=d.reports_html||''; };
  reviewsForm?.addEventListener('submit', async (e)=>{e.preventDefault(); try{await reload();}catch(err){show(err.message,true);}});
  reportsForm?.addEventListener('submit', async (e)=>{e.preventDefault(); try{await reload();}catch(err){show(err.message,true);}});
  document.addEventListener('submit', async (e)=>{ const f=e.target; if(!(f instanceof HTMLFormElement)) return; if(!f.classList.contains('js-review-action-form')&&!f.classList.contains('js-report-action-form')) return; e.preventDefault(); try{ const r=await fetch(f.action,{method:'POST',body:new FormData(f),headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':csrfToken}}); const d=await r.json().catch(()=>({})); if(!r.ok||!d.success) throw new Error(d.message||'Операция не выполнена.'); await reload(); show(d.message||'Готово.'); }catch(err){ show(err.message,true);} });
})();
</script>
@endsection
