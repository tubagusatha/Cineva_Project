document.addEventListener('DOMContentLoaded', function() {

    // Navbar scroll effect
    const navbar = document.querySelector('.navbar');
    if (navbar) {
        window.addEventListener('scroll', function() {
            navbar.classList.toggle('scrolled', window.scrollY > 40);
        });
        if (window.scrollY > 40) navbar.classList.add('scrolled');
    }

    // User dropdown (Robust click, outside click, escape key, stopPropagation)
    const userMenu = document.getElementById('nav-user-menu');
    const userBtn = document.getElementById('nav-user-btn');
    const userDropdown = document.getElementById('nav-user-dropdown');

    if (userBtn && userDropdown) {
        userBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            const isOpen = userDropdown.classList.contains('show');
            userDropdown.classList.toggle('show');
            userBtn.setAttribute('aria-expanded', !isOpen);
        });

        userDropdown.addEventListener('click', function(e) {
            // Let links navigate, but don't close prematurely if clicking empty space
            e.stopPropagation();
        });

        document.addEventListener('click', function(e) {
            if (!userMenu || !userMenu.contains(e.target)) {
                userDropdown.classList.remove('show');
                userBtn.setAttribute('aria-expanded', 'false');
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                userDropdown.classList.remove('show');
                userBtn.setAttribute('aria-expanded', 'false');
            }
        });
    }

    // Mobile nav drawer & hamburger toggle
    const hamburgerBtn = document.getElementById('hamburger-btn');
    const mobileNav = document.getElementById('mobile-nav');
    const mobileClose = document.getElementById('mobile-nav-close');
    const mobileBackdrop = document.getElementById('mobile-nav-backdrop');

    function openMobileNav() {
        if (!mobileNav) return;
        mobileNav.classList.add('open');
        mobileBackdrop?.classList.add('show');
        hamburgerBtn?.classList.add('is-active');
        hamburgerBtn?.setAttribute('aria-expanded', 'true');
        document.body.classList.add('no-scroll');
    }

    function closeMobileNav() {
        if (!mobileNav) return;
        mobileNav.classList.remove('open');
        mobileBackdrop?.classList.remove('show');
        hamburgerBtn?.classList.remove('is-active');
        hamburgerBtn?.setAttribute('aria-expanded', 'false');
        document.body.classList.remove('no-scroll');
    }

    function toggleMobileNav() {
        if (mobileNav?.classList.contains('open')) {
            closeMobileNav();
        } else {
            openMobileNav();
        }
    }

    if (hamburgerBtn) {
        hamburgerBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            toggleMobileNav();
        });
    }

    if (mobileClose) {
        mobileClose.addEventListener('click', function(e) {
            e.stopPropagation();
            closeMobileNav();
        });
    }

    if (mobileBackdrop) {
        mobileBackdrop.addEventListener('click', closeMobileNav);
    }

    // Close when clicking nav links inside mobile drawer
    mobileNav?.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', closeMobileNav);
    });

    // Close on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && mobileNav?.classList.contains('open')) {
            closeMobileNav();
        }
    });

    // Horizontal scroll with mouse drag
    document.querySelectorAll('.movie-row').forEach(row => {
        let isDown = false, startX, scrollLeft;

        row.addEventListener('mousedown', e => {
            isDown = true;
            row.style.cursor = 'grabbing';
            startX = e.pageX - row.offsetLeft;
            scrollLeft = row.scrollLeft;
        });

        row.addEventListener('mouseleave', () => { isDown = false; row.style.cursor = 'grab'; });
        row.addEventListener('mouseup', () => { isDown = false; row.style.cursor = 'grab'; });

        row.addEventListener('mousemove', e => {
            if (!isDown) return;
            e.preventDefault();
            const x = e.pageX - row.offsetLeft;
            row.scrollLeft = scrollLeft - (x - startX) * 1.5;
        });

        row.style.cursor = 'grab';
    });


    // Hero Slider
    const slides = document.querySelectorAll('.hero-slide');
    const dots = document.querySelectorAll('.slider-dot');
    const prevBtn = document.querySelector('.slider-arrow.prev');
    const nextBtn = document.querySelector('.slider-arrow.next');
    
    if (slides.length > 1) {
        let currentSlide = 0;
        let slideInterval;

        const showSlide = (index) => {
            slides.forEach(slide => slide.classList.remove('active'));
            dots.forEach(dot => dot.classList.remove('active'));
            
            slides[index].classList.add('active');
            dots[index].classList.add('active');
            currentSlide = index;
        };

        const nextSlide = () => {
            let next = currentSlide + 1;
            if (next >= slides.length) next = 0;
            showSlide(next);
        };

        const prevSlide = () => {
            let prev = currentSlide - 1;
            if (prev < 0) prev = slides.length - 1;
            showSlide(prev);
        };

        const startSlider = () => {
            slideInterval = setInterval(nextSlide, 5000); // 5 seconds
        };

        const resetSlider = () => {
            clearInterval(slideInterval);
            startSlider();
        };

        if (nextBtn) {
            nextBtn.addEventListener('click', () => {
                nextSlide();
                resetSlider();
            });
        }

        if (prevBtn) {
            prevBtn.addEventListener('click', () => {
                prevSlide();
                resetSlider();
            });
        }

        dots.forEach((dot, index) => {
            dot.addEventListener('click', () => {
                showSlide(index);
                resetSlider();
            });
        });

        startSlider();
    }

});
