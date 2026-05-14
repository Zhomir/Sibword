@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="{{ asset('css/teacher-indes.css') }}">
<link rel="stylesheet" href="{{ asset('css/admin.css') }}">

<div class="teacher-page"><div class="teacher-shell">
  <header class="teacher-header">
    <div class="teacher-title">Админка <span class="teacher-subtitle">База знаний</span></div>
    <nav class="teacher-nav">
      <a class="teacher-nav-link" href="{{ route('admin.index') }}">Главная</a>
      <a class="teacher-nav-link" href="{{ route('admin.users.page') }}">Роли</a>
      <a class="teacher-nav-link is-active" href="{{ route('admin.knowledge.page') }}">База знаний</a>
      <a class="teacher-nav-link" href="{{ route('admin.moderation.page') }}">Модерация</a>
    </nav>
  </header>

  <div id="knowledge-status-wrap">
    @if (session('admin_status'))<div class="teacher-card admin-status admin-status--success">{{ session('admin_status') }}</div>@endif
    @if ($errors->any())<div class="teacher-card admin-status admin-status--error">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
  </div>

  <section class="teacher-card">
    <div class="admin-users-head">
      <h2 class="teacher-section-title">База знаний</h2>
      <p class="teacher-muted">Добавляйте слова, импортируйте CSV и управляйте словарем платформы.</p>
    </div>

    <form id="knowledge-add-form" action="{{ route('admin.knowledge.store') }}" method="POST" class="teacher-field">@csrf
      <div class="teacher-grid admin-form-grid"><div><label class="teacher-label">Слово</label><input type="text" name="word" class="teacher-input" required></div><div><label class="teacher-label">Перевод</label><input type="text" name="translation" class="teacher-input" required></div><div><label class="teacher-label">Транскрипция</label><input type="text" name="transcription" class="teacher-input"></div><div><label class="teacher-label">Сложность</label><input type="number" step="0.01" min="0" max="9.99" name="complexity_index" class="teacher-input" value="0"></div></div>
      <div class="teacher-actions"><button type="submit" class="btn btn-success">Добавить</button><a href="{{ route('admin.knowledge.export') }}" class="btn btn-outline btn-sm">Экспорт CSV</a></div>
    </form>

    <form id="knowledge-import-form" action="{{ route('admin.knowledge.import') }}" method="POST" enctype="multipart/form-data" class="teacher-field admin-import-form">@csrf
      <label class="teacher-label">Импорт CSV</label><input type="file" name="csv_file" accept=".csv,.txt" class="teacher-file" required>
      <div class="teacher-actions"><button type="submit" class="btn btn-primary btn-sm">Импортировать</button></div>
    </form>

    <form id="knowledge-filter-form" action="{{ route('admin.knowledge.page') }}" method="GET" class="admin-toolbar teacher-field">
      <div><label class="teacher-label" for="word_search">Поиск</label><input id="word_search" type="text" name="word_search" value="{{ $wordFilters['search'] }}" class="teacher-input"></div>
      <div><label class="teacher-label" for="word_per_page">На странице</label><select id="word_per_page" name="word_per_page" class="teacher-select">@foreach([10,25,50,100] as $size)<option value="{{ $size }}" @selected((int)$wordFilters['per_page'] === $size)>{{ $size }}</option>@endforeach</select></div>
      <div class="admin-toolbar-actions"><button class="btn btn-primary btn-sm" type="submit">Применить</button></div>
    </form>

    <div id="knowledge-table-wrap">
      @include('admin.partials.knowledge-table', ['dictionaryEntries' => $dictionaryEntries])
    </div>
  </section>
</div></div>
@endsection

@section('scripts')
<script>
(() => {
  const statusWrap = document.getElementById('knowledge-status-wrap');
  const tableWrap = document.getElementById('knowledge-table-wrap');
  const filterForm = document.getElementById('knowledge-filter-form');
  const addForm = document.getElementById('knowledge-add-form');
  const importForm = document.getElementById('knowledge-import-form');
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  const showStatus = (message, isError = false) => {
    if (!statusWrap) return;
    const cls = isError ? 'admin-status--error' : 'admin-status--success';
    statusWrap.innerHTML = `<div class="teacher-card admin-status ${cls}">${message}</div>`;
  };

  const fetchTable = async () => {
    if (!filterForm || !tableWrap) return;
    const query = new URLSearchParams(new FormData(filterForm)).toString();
    const url = `${filterForm.action}?${query}`;
    const response = await fetch(url, {
      method: 'GET',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    });
    const data = await response.json();
    if (!response.ok || !data.success) throw new Error(data.message || 'Не удалось обновить таблицу.');
    tableWrap.innerHTML = data.table_html || '';
  };

  const submitPostForm = async (form) => {
    const response = await fetch(form.action, {
      method: 'POST',
      body: new FormData(form),
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrfToken
      }
    });
    const data = await response.json().catch(() => ({}));
    if (!response.ok || !data.success) {
      if (data && data.errors) {
        const flatErrors = Object.values(data.errors).flat().filter(Boolean);
        if (flatErrors.length > 0) {
          throw new Error(flatErrors.join(' '));
        }
      }
      throw new Error(data.message || 'Операция не выполнена.');
    }
    return data;
  };

  filterForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    try {
      await fetchTable();
    } catch (error) {
      showStatus(error.message, true);
    }
  });

  addForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    try {
      const data = await submitPostForm(addForm);
      addForm.reset();
      const complexityInput = addForm.querySelector('input[name="complexity_index"]');
      if (complexityInput) complexityInput.value = '0';
      await fetchTable();
      showStatus(data.message || 'Слово добавлено.');
    } catch (error) {
      showStatus(error.message, true);
    }
  });

  importForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    try {
      const data = await submitPostForm(importForm);
      importForm.reset();
      await fetchTable();
      showStatus(data.message || 'Импорт завершен.');
    } catch (error) {
      showStatus(error.message, true);
    }
  });

  document.addEventListener('submit', async (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement)) return;
    if (!form.classList.contains('js-knowledge-delete-form')) return;
    event.preventDefault();

    try {
      const data = await submitPostForm(form);
      await fetchTable();
      showStatus(data.message || 'Запись удалена.');
    } catch (error) {
      showStatus(error.message, true);
    }
  });
})();
</script>
@endsection
