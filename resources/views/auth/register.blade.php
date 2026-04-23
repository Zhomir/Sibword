@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="{{ asset('css/auth-login.css') }}">
<div class="auth-page">
    <div class="auth-overlay"></div>
    <div class="auth-card" data-aos="zoom-in">
        <div class="auth-header">
            <h2>Регистрация <span>Нүхэр</span></h2>
            <p>Создай аккаунт, чтобы начать путь в изучении языка</p>
        </div>

        <form action="{{ route('register') }}" method="POST" class="auth-form">
            @csrf

            <div class="input-group">
                <label>Как вас зовут?</label>
                <input type="text" name="name" value="{{ old('name') }}" required placeholder="Имя или никнейм">
                @error('name') <span class="error">{{ $message }}</span> @enderror
            </div>

            <div class="input-group">
                <label>Ваш Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required placeholder="example@mail.ru">
                @error('email') <span class="error">{{ $message }}</span> @enderror
            </div>

            <div class="input-group">
                <label>Пароль</label>
                <input type="password" name="password" required placeholder="••••••••">
                @error('password') <span class="error">{{ $message }}</span> @enderror
            </div>

            <div class="input-group">
                <label>Повторите пароль</label>
                <input type="password" name="password_confirmation" required placeholder="••••••••">
            </div>

            <button type="submit" class="auth-btn">Создать аккаунт</button>
        </form>

        <div class="auth-footer">
            <p>Уже есть аккаунт? <a href="{{ route('login') }}">Войти</a></p>
        </div>
    </div>
</div>
@endsection
