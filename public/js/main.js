document.addEventListener('DOMContentLoaded', function () {
    if (window.AOS) {
        AOS.init({
            duration: 1000,
            once: true,
            easing: 'ease-in-out'
        });
    }

    const body = document.body;
    const nav = document.querySelector('.nav-container');
    const themeToggle = document.getElementById('theme-toggle');
    const themeLabel = document.getElementById('theme-toggle-label');
    const hamburger = document.querySelector('.hamburger-menu');
    const navLinks = document.querySelector('.nav-links');

    const applyTheme = (theme) => {
        const safeTheme = theme === 'dark' ? 'dark' : 'light';
        document.documentElement.setAttribute('data-theme', safeTheme);
        body.setAttribute('data-theme', safeTheme);

        if (themeLabel) {
            themeLabel.textContent = safeTheme === 'dark' ? 'Темная' : 'Светлая';
        }

        if (themeToggle) {
            themeToggle.setAttribute('aria-pressed', safeTheme === 'dark' ? 'true' : 'false');
        }
    };

    try {
        const savedTheme = window.localStorage.getItem('sibword_theme');
        const isExplicit = window.localStorage.getItem('sibword_theme_explicit') === '1';
        const hasValidTheme = savedTheme === 'dark' || savedTheme === 'light';
        applyTheme(isExplicit && hasValidTheme ? savedTheme : 'light');
    } catch (error) {
        applyTheme('light');
    }

    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            const current = body.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
            const next = current === 'dark' ? 'light' : 'dark';
            applyTheme(next);
            try {
                window.localStorage.setItem('sibword_theme', next);
                window.localStorage.setItem('sibword_theme_explicit', '1');
            } catch (error) {
                // ignore localStorage write issues
            }
        });
    }

    const handleScroll = () => {
        if (!nav) return;
        if (window.scrollY > 30) {
            nav.classList.add('scrolled');
        } else {
            nav.classList.remove('scrolled');
        }
    };
    window.addEventListener('scroll', handleScroll);
    handleScroll();

    if (hamburger && navLinks) {
        hamburger.addEventListener('click', () => {
            hamburger.classList.toggle('active');
            navLinks.classList.toggle('active');
        });
    }

    document.querySelectorAll('.nav-links a').forEach((link) => {
        link.addEventListener('click', () => {
            hamburger?.classList.remove('active');
            navLinks?.classList.remove('active');
        });
    });

    if (window.Swiper && document.querySelector('.swiper-container')) {
        new Swiper('.swiper-container', {
            effect: 'coverflow',
            grabCursor: true,
            centeredSlides: true,
            slidesPerView: 'auto',
            coverflowEffect: {
                rotate: 5,
                stretch: -50,
                depth: 150,
                modifier: 2.5,
                slideShadows: true
            },
            pagination: {
                el: '.swiper-pagination',
                clickable: true
            },
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev'
            },
            breakpoints: {
                320: { slidesPerView: 1.2, spaceBetween: 10 },
                768: { slidesPerView: 2, spaceBetween: 20 },
                1024: { slidesPerView: 3, spaceBetween: 30 }
            }
        });
    }

    document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
        anchor.addEventListener('click', function (e) {
            const target = document.querySelector(this.getAttribute('href'));
            if (!target) return;
            e.preventDefault();
            target.scrollIntoView({ behavior: 'smooth' });
        });
    });

    window.addEventListener('mousemove', (e) => {
        const x = (e.clientX / window.innerWidth - 0.5) * 20;
        const y = (e.clientY / window.innerHeight - 0.5) * 20;
        document.querySelectorAll('.bg-deco').forEach((el) => {
            el.style.transform = `translate(${x}px, ${y}px)`;
        });
    });
});

