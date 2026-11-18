// Small progressive enhancements. Everything works without JavaScript.

// Mobile menu toggle.
document.querySelectorAll('.menu-toggle').forEach((button) => {
    const nav = document.getElementById(button.getAttribute('aria-controls'));
    if (!nav) return;
    button.hidden = false;
    button.addEventListener('click', () => {
        const open = button.getAttribute('aria-expanded') !== 'true';
        button.setAttribute('aria-expanded', String(open));
        nav.classList.toggle('is-open', open);
    });
});

// Product gallery: clicking a thumbnail shows it in the main image.
document.querySelectorAll('[data-gallery]').forEach((gallery) => {
    const main = gallery.querySelector('[data-gallery-main]');
    const thumbs = gallery.querySelectorAll('[data-gallery-thumb]');
    if (!main) return;

    thumbs.forEach((thumb) => {
        thumb.addEventListener('click', () => {
            if (thumb.getAttribute('aria-current') === 'true') return;
            main.classList.add('is-loading');
            main.addEventListener('load', () => main.classList.remove('is-loading'), { once: true });
            main.srcset = thumb.dataset.srcset || '';
            main.src = thumb.dataset.src;
            main.alt = thumb.dataset.alt || '';
            thumbs.forEach((t) => t.removeAttribute('aria-current'));
            thumb.setAttribute('aria-current', 'true');
        });
    });
});

// Cart: choosing a destination updates the totals straight away.
document.querySelectorAll('form[data-autosubmit]').forEach((form) => {
    form.addEventListener('change', () => form.requestSubmit());
});
