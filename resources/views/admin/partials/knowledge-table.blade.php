<div class="admin-table-wrap admin-table-wrap--tall">
  <table class="admin-table">
    <thead>
      <tr class="admin-table-head-row">
        <th class="admin-th">Слово</th>
        <th class="admin-th">Перевод</th>
        <th class="admin-th">Транскрипция</th>
        <th class="admin-th">Сложность</th>
        <th class="admin-th">Действие</th>
      </tr>
    </thead>
    <tbody>
      @forelse ($dictionaryEntries as $entry)
      <tr class="admin-table-row">
        <td class="admin-td">{{ $entry->word }}</td>
        <td class="admin-td">{{ $entry->translation }}</td>
        <td class="admin-td">{{ $entry->transcription ?: '-' }}</td>
        <td class="admin-td">{{ number_format((float) $entry->complexity_index, 2) }}</td>
        <td class="admin-td">
          <form action="{{ route('admin.knowledge.delete', $entry) }}" method="POST" class="js-knowledge-delete-form">
            @csrf
            <button type="submit" class="btn btn-danger btn-sm">Удалить</button>
          </form>
        </td>
      </tr>
      @empty
      <tr class="admin-table-row">
        <td colspan="5" class="admin-td teacher-muted">По фильтру записей нет.</td>
      </tr>
      @endforelse
    </tbody>
  </table>
</div>
