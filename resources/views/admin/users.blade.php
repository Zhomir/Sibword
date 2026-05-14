@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="{{ asset('css/teacher-indes.css') }}">
<link rel="stylesheet" href="{{ asset('css/admin.css') }}">

<div class="teacher-page"><div class="teacher-shell">
  <header class="teacher-header">
    <div class="teacher-title">Админка <span class="teacher-subtitle">Роли пользователей</span></div>
    <nav class="teacher-nav">
      <a class="teacher-nav-link" href="{{ route('admin.index') }}">Главная</a>
      <a class="teacher-nav-link is-active" href="{{ route('admin.users.page') }}">Роли</a>
      <a class="teacher-nav-link" href="{{ route('admin.knowledge.page') }}">База знаний</a>
      <a class="teacher-nav-link" href="{{ route('admin.moderation.page') }}">Модерация</a>
    </nav>
  </header>

  <div id="users-status-wrap">
    @if (session('admin_status'))<div class="teacher-card admin-status admin-status--success">{{ session('admin_status') }}</div>@endif
    @if ($errors->any())<div class="teacher-card admin-status admin-status--error">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
  </div>

  <section class="teacher-card">
    <div class="admin-users-head">
      <h2 class="teacher-section-title">Роли пользователей</h2>
      <p class="teacher-muted">Управляйте доступом и ролями участников платформы.</p>
    </div>
    <form id="users-filter-form" action="{{ route('admin.users.page') }}" method="GET" class="admin-toolbar teacher-field">
      <div><label for="user_search" class="teacher-label">Поиск</label><input id="user_search" type="text" name="user_search" value="{{ $userFilters['search'] }}" class="teacher-input"></div>
      <div><label for="user_role" class="teacher-label">Роль</label><select id="user_role" name="user_role" class="teacher-select"><option value="">Все</option><option value="student" @selected($userFilters['role'] === 'student')>Студент</option><option value="teacher" @selected($userFilters['role'] === 'teacher')>Преподаватель</option><option value="admin" @selected($userFilters['role'] === 'admin')>Администратор</option></select></div>
      <div><label for="user_per_page" class="teacher-label">На странице</label><select id="user_per_page" name="user_per_page" class="teacher-select">@foreach([10,25,50,100] as $size)<option value="{{ $size }}" @selected((int)$userFilters['per_page'] === $size)>{{ $size }}</option>@endforeach</select></div>
      <div class="admin-toolbar-actions"><button class="btn btn-primary btn-sm" type="submit">Применить</button></div>
    </form>

    <div id="users-table-wrap">@include('admin.partials.users-table', ['users' => $users])</div>
  </section>
</div></div>
@endsection

@section('scripts')
<script>
(() => {
  const statusWrap = document.getElementById('users-status-wrap');
  const tableWrap = document.getElementById('users-table-wrap');
  const filterForm = document.getElementById('users-filter-form');
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  const showStatus = (m,e=false)=>statusWrap.innerHTML=`<div class="teacher-card admin-status ${e?'admin-status--error':'admin-status--success'}">${m}</div>`;
  const reload = async()=>{const q=new URLSearchParams(new FormData(filterForm)).toString(); const r=await fetch(`${filterForm.action}?${q}`,{headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}}); const d=await r.json(); if(!r.ok||!d.success) throw new Error(d.message||'Ошибка обновления.'); tableWrap.innerHTML=d.table_html||'';};
  filterForm?.addEventListener('submit', async (e)=>{e.preventDefault(); try{await reload();}catch(err){showStatus(err.message,true);}});
  document.addEventListener('submit', async (e)=>{const f=e.target; if(!(f instanceof HTMLFormElement)||!f.classList.contains('js-user-role-form')) return; e.preventDefault(); try{const r=await fetch(f.action,{method:'POST',body:new FormData(f),headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':csrfToken}}); const d=await r.json().catch(()=>({})); if(!r.ok||!d.success) throw new Error(d.message||'Не удалось сохранить роль.'); await reload(); showStatus(d.message||'Роль обновлена.');}catch(err){showStatus(err.message,true);}});
})();
</script>
@endsection
