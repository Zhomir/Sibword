<div class="admin-table-wrap admin-table-wrap--tall">
  <table class="admin-table">
    <thead>
      <tr class="admin-table-head-row">
        <th class="admin-th">Курс</th>
        <th class="admin-th">Пользователь</th>
        <th class="admin-th">Оценка</th>
        <th class="admin-th">Статус</th>
        <th class="admin-th">Действие</th>
      </tr>
    </thead>
    <tbody>
      @forelse($courseReviews as $review)
      <tr class="admin-table-row">
        <td class="admin-td">{{ $review->course->title ?? 'Курс удален' }}</td>
        <td class="admin-td">{{ $review->author->name ?? 'Пользователь' }}</td>
        <td class="admin-td">{{ (int) $review->rating }}/5</td>
        <td class="admin-td">{{ $review->is_approved ? 'Одобрен' : 'Скрыт' }}</td>
        <td class="admin-td">
          @if(!$review->is_approved)
            <form method="POST" action="{{ route('admin.reviews.approve', ['courseReview' => $review->id]) }}" class="js-review-action-form">
              @csrf
              <button type="submit" class="btn btn-success btn-sm">Одобрить</button>
            </form>
          @else
            <form method="POST" action="{{ route('admin.reviews.reject', ['courseReview' => $review->id]) }}" class="js-review-action-form">
              @csrf
              <button type="submit" class="btn btn-outline btn-sm">Скрыть</button>
            </form>
          @endif
        </td>
      </tr>
      @empty
      <tr class="admin-table-row"><td colspan="5" class="admin-td teacher-muted">Отзывов нет.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
