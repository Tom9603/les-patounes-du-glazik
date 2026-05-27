document.addEventListener('DOMContentLoaded', function() {

    var observerOptions = {
        root: null,
        rootMargin: '0px',
        threshold: 0.1
    };

    var observer = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
            }
        });
    }, observerOptions);

    var animatableElements = document.querySelectorAll('.animate-on-scroll, .fade-in-left, .fade-in-right');
    animatableElements.forEach(function(el) {
        observer.observe(el);
    });

    // Hero parallax effect
    var hero = document.getElementById('hero');
    var ticking = false;

    function updateParallax() {
        if (!hero) return;

        var scrolled = window.pageYOffset;
        var heroHeight = hero.offsetHeight;

        if (scrolled < heroHeight) {
            hero.style.opacity = 1 - (scrolled / heroHeight);
            hero.style.transform = 'scale(' + (1 - (scrolled / heroHeight) * 0.2) + ')';
        }

        ticking = false;
    }

    window.addEventListener('scroll', function() {
        if (!ticking) {
            window.requestAnimationFrame(updateParallax);
            ticking = true;
        }
    });

    // Service cards hover
    var serviceCards = document.querySelectorAll('.service-card');
    serviceCards.forEach(function(card) {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-8px)';
        });
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });

    // Smooth scroll for anchor links
    var anchorLinks = document.querySelectorAll('a[href^="#"]');
    anchorLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
            var href = this.getAttribute('href');
            if (href && href !== '#' && href !== '') {
                e.preventDefault();
                var target = document.querySelector(href);
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }
        });
    });

    // Navbar background on scroll
    var navbar = document.querySelector('.navbar');

    window.addEventListener('scroll', function() {
        if (!navbar) return;
        if (window.pageYOffset > 100) {
            navbar.style.backgroundColor = 'rgba(255, 255, 255, 0.95)';
            navbar.style.boxShadow = '0 2px 10px rgba(0, 0, 0, 0.05)';
        } else {
            navbar.style.backgroundColor = 'rgba(255, 255, 255, 0.9)';
            navbar.style.boxShadow = 'none';
        }
    });

    // Stagger animation for pricing rows
    var pricingRows = document.querySelectorAll('.pricing-row');
    var pricingObserver = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry, index) {
            if (entry.isIntersecting) {
                setTimeout(function() {
                    entry.target.classList.add('is-visible');
                }, index * 50);
            }
        });
    }, observerOptions);
    pricingRows.forEach(function(row) { pricingObserver.observe(row); });

    // Hero fade-in on load
    setTimeout(function() {
        document.querySelectorAll('.fade-in-left, .fade-in-right').forEach(function(el) {
            el.classList.add('is-visible');
        });
    }, 100);

    // Review cards hover
    var reviewCards = document.querySelectorAll('.review-card');
    reviewCards.forEach(function(card) {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.02)';
        });
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
        });
    });

    // CTA buttons pulse on scroll
    var ctaButtons = document.querySelectorAll('.cta-buttons .btn');
    var ctaObserver = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                entry.target.style.animation = 'pulse 2s infinite';
            }
        });
    }, observerOptions);
    ctaButtons.forEach(function(btn) { ctaObserver.observe(btn); });

    var style = document.createElement('style');
    style.textContent = '@keyframes pulse { 0%, 100% { box-shadow: 0 0 0 0 rgba(232, 154, 92, 0.4); } 50% { box-shadow: 0 0 0 10px rgba(232, 154, 92, 0); } }';
    document.head.appendChild(style);

    // FAQ accordion
    var faqItems = document.querySelectorAll('.faq-question');
    faqItems.forEach(function(btn) {
        btn.addEventListener('click', function() {
            var answer = this.nextElementSibling;
            var isOpen = this.getAttribute('aria-expanded') === 'true';

            faqItems.forEach(function(other) {
                other.setAttribute('aria-expanded', 'false');
                other.nextElementSibling.hidden = true;
            });

            if (!isOpen) {
                this.setAttribute('aria-expanded', 'true');
                answer.hidden = false;
            }
        });
    });

    // Nav scroll spy
    var navLinks = document.querySelectorAll('.nav-link[href^="#"]');
    var sections = Array.from(navLinks).map(function(link) {
        return document.querySelector(link.getAttribute('href'));
    });

    function updateActiveNav() {
        var scrollY = window.pageYOffset;
        var navbarHeight = navbar ? navbar.offsetHeight : 80;
        var activeIndex = -1;

        sections.forEach(function(section, i) {
            if (!section) return;
            if (section.offsetTop - navbarHeight - 40 <= scrollY) {
                activeIndex = i;
            }
        });

        navLinks.forEach(function(link, i) {
            link.classList.toggle('nav-link--active', i === activeIndex);
        });
    }

    window.addEventListener('scroll', updateActiveNav, { passive: true });
    updateActiveNav();

    // Lazy load images
    var images = document.querySelectorAll('img[data-src]');
    var imageObserver = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                var img = entry.target;
                if (img.dataset.src) {
                    img.src = img.dataset.src;
                    img.removeAttribute('data-src');
                }
                imageObserver.unobserve(img);
            }
        });
    });
    images.forEach(function(img) { imageObserver.observe(img); });
});
