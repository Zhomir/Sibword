<div class="admin-table-wrap admin-table-wrap--tall">
  <table class="admin-table">
    <thead>
      <tr class="admin-table-head-row">
        <th class="admin-th">Пользователь</th>
        <th class="admin-th">Email</th>
        <th class="admin-th">Роль</th>
      </tr>
    </thead>
    <tbody>
      @forelse ($users as $user)
      <tr class="admin-table-row">
        <td class="admin-td">{{ $user->name }}</td>
        <td class="admin-td">{{ $user->email }}</td>
        <td class="admin-td">
          <form action="{{ route('admin.users.role', $user) }}" method="POST" class="teacher-inline-input admin-inline-center js-user-role-form">
            @csrf
            <select name="role" class="teacher-select admin-role-select">
              <option value="student" @selected($user->role === 'student')>Студент</option>
              <option value="teacher" @selected($user->role === 'teacher')>Преподаватель</option>
              <option value="admin" @selected($user->role === 'admin')>Администратор</option>
            </select>
            <button type="submit" class="btn btn-primary btn-sm">Сохранить</button>
          </form>
        </td>
      </tr>
      @empty
      <tr class="admin-table-row">
        <td colspan="3" class="admin-td teacher-muted">Нет пользователей по фильтру.</td>
      </tr>
      @endforelse
    </tbody>
  </table>
</div>
