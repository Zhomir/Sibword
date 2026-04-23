(function () {
    try {
        var savedTheme = window.localStorage.getItem('sibword_theme');
        var isExplicit = window.localStorage.getItem('sibword_theme_explicit') === '1';
        var hasValidTheme = savedTheme === 'dark' || savedTheme === 'light';
        var theme = isExplicit && hasValidTheme ? savedTheme : 'light';
        document.documentElement.setAttribute('data-theme', theme);
    } catch (error) {
        document.documentElement.setAttribute('data-theme', 'light');
    }
})();
