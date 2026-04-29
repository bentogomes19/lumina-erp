const animatedCounters = new WeakSet();
const animatedBars = new WeakSet();
const animatedCards = new WeakSet();

const prefersReducedMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

const parseDisplayNumber = (text) => {
    const trimmed = text.trim();

    if (!/^-?\d{1,3}(?:\.\d{3})*(?:,\d+)?%?$|^-?\d+(?:,\d+)?%?$/.test(trimmed)) {
        return null;
    }

    const hasPercent = trimmed.endsWith('%');
    const numeric = trimmed.replace('%', '').replace(/\./g, '').replace(',', '.');
    const value = Number.parseFloat(numeric);

    if (!Number.isFinite(value)) {
        return null;
    }

    const decimalPart = trimmed.replace('%', '').split(',')[1];

    return {
        value,
        decimals: decimalPart ? decimalPart.length : 0,
        suffix: hasPercent ? '%' : '',
    };
};

const formatDisplayNumber = (value, decimals, suffix) => {
    return value.toLocaleString('pt-BR', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
    }) + suffix;
};

const animateCounter = (element) => {
    if (animatedCounters.has(element)) {
        return;
    }

    const parsed = parseDisplayNumber(element.textContent);

    if (!parsed) {
        return;
    }

    animatedCounters.add(element);

    if (prefersReducedMotion()) {
        return;
    }

    const duration = 900;
    const start = performance.now();

    const tick = (time) => {
        const progress = Math.min((time - start) / duration, 1);
        const eased = 1 - Math.pow(1 - progress, 3);
        const current = parsed.value * eased;

        element.textContent = formatDisplayNumber(current, parsed.decimals, parsed.suffix);

        if (progress < 1) {
            requestAnimationFrame(tick);
        } else {
            element.textContent = formatDisplayNumber(parsed.value, parsed.decimals, parsed.suffix);
        }
    };

    element.textContent = formatDisplayNumber(0, parsed.decimals, parsed.suffix);
    requestAnimationFrame(tick);
};

const animateProgressBar = (bar) => {
    if (animatedBars.has(bar)) {
        return;
    }

    const targetWidth = bar.style.width;

    if (!targetWidth || !targetWidth.includes('%')) {
        return;
    }

    animatedBars.add(bar);

    if (prefersReducedMotion()) {
        return;
    }

    bar.dataset.luminaTargetWidth = targetWidth;
    bar.style.width = '0%';
    bar.classList.add('lumina-progress-bar');

    requestAnimationFrame(() => {
        requestAnimationFrame(() => {
            bar.style.width = targetWidth;
        });
    });
};

const prepareCard = (card, index) => {
    if (animatedCards.has(card)) {
        return;
    }

    animatedCards.add(card);

    if (prefersReducedMotion()) {
        return;
    }

    card.classList.add('lumina-card-enter');
    card.style.setProperty('--lumina-enter-delay', `${Math.min(index * 45, 360)}ms`);
};

const revealCard = (card) => {
    card.classList.add('is-visible');
};

const bindPointerAnimation = (element) => {
    if (element.dataset.luminaPointerBound === 'true') {
        return;
    }

    element.dataset.luminaPointerBound = 'true';

    element.addEventListener('pointerenter', () => element.classList.add('is-js-hovered'));
    element.addEventListener('pointerleave', () => element.classList.remove('is-js-hovered'));
    element.addEventListener('focusin', () => element.classList.add('is-js-hovered'));
    element.addEventListener('focusout', () => element.classList.remove('is-js-hovered'));
};

const runStudentAnimations = () => {
    const cards = [
        ...document.querySelectorAll('.ms-card, .ac-card, .grades-card, .grades-empty-global'),
    ].filter((card) => !card.matches('[data-lumina-no-enter], [data-lumina-no-enter] *'));

    cards.forEach(prepareCard);

    if ('IntersectionObserver' in window && !prefersReducedMotion()) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) {
                    return;
                }

                revealCard(entry.target);
                observer.unobserve(entry.target);
            });
        }, { threshold: 0.12 });

        cards.forEach((card) => observer.observe(card));
    } else {
        cards.forEach(revealCard);
    }

    document
        .querySelectorAll('.ms-stats-grid .ms-card p:first-child, [data-animate-counter]')
        .forEach(animateCounter);

    document
        .querySelectorAll('.ms-freq-bar-track > div, .ms-bar-track > div')
        .forEach(animateProgressBar);

    document
        .querySelectorAll('a.ms-shortcut, a.ms-subject-card, .ms-period-btn, .ac-tab, .ac-nav-btn, .ac-filter-pill')
        .forEach(bindPointerAnimation);
};

document.addEventListener('DOMContentLoaded', runStudentAnimations);
document.addEventListener('livewire:navigated', runStudentAnimations);
document.addEventListener('livewire:update', runStudentAnimations);
