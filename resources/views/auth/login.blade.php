@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="{{ asset('css/auth-login.css') }}">
<div class="auth-page">
    <div class="auth-overlay"></div>

    <div class="auth-card" data-aos="zoom-in">
        <div class="auth-header">
            <h2>С возвращением, <span>Нүхэр!</span></h2>
            <p>Войдите в систему, чтобы продолжить обучение</p>
        </div>

        <form action="{{ route('login') }}" method="POST" class="auth-form">
            @csrf

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

            <button type="submit" class="auth-btn">Войти в кабинет</button>
        </form>

        <div class="auth-footer">
            <p>Еще нет аккаунта? <a href="{{ route('register') }}">Зарегистрироваться</a></p>
        </div>
    </div>
</div>
@endsection
