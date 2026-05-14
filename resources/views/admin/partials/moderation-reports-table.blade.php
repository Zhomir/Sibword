<div class="admin-table-wrap admin-table-wrap--tall">
  <table class="admin-table">
    <thead>
      <tr class="admin-table-head-row">
        <th class="admin-th">Дата</th>
        <th class="admin-th">Статус</th>
        <th class="admin-th">Причина</th>
        <th class="admin-th">Действия</th>
      </tr>
    </thead>
    <tbody>
      @forelse($moderationReports as $report)
      <tr class="admin-table-row">
        <td class="admin-td">{{ optional($report->created_at)->format('d.m.Y H:i') }}</td>
        <td class="admin-td">
          <span class="admin-report-status {{ $report->status === 'resolved' ? 'is-resolved' : 'is-pending' }}">
            {{ $report->status === 'resolved' ? 'Обработана' : 'Новая' }}
          </span>
        </td>
        <td class="admin-td">{{ $report->reason ?: '-' }}</td>
        <td class="admin-td">
          @if($report->status !== 'resolved')
            <div class="admin-report-actions">
              <form method="POST" action="{{ route('admin.moderation.hide', ['moderationAction' => $report->id]) }}" class="js-report-action-form">
                @csrf
                <button type="submit" class="btn btn-danger btn-sm">Скрыть</button>
              </form>
              <form method="POST" action="{{ route('admin.moderation.restore', ['moderationAction' => $report->id]) }}" class="js-report-action-form">
                @csrf
                <button type="submit" class="btn btn-outline btn-sm">Вернуть</button>
              </form>
            </div>
          @else
            <span class="teacher-muted">Закрыта</span>
          @endif
        </td>
      </tr>
      @empty
      <tr class="admin-table-row"><td colspan="4" class="admin-td teacher-muted">Жалоб нет.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
