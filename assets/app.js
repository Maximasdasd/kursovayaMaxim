'use strict';
const slides = [...document.querySelectorAll('.slide')];
for (const slide of slides) {
    slide.addEventListener('click', (event) => {
        if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        event.preventDefault();
        slides.forEach((el) => el.classList.toggle('active', el === slide));
        const { image, title, kind, alt, cta, measure, value } = slide.dataset;
        const img = document.querySelector('#hero-image');
        img.src = image;
        img.alt = alt;
        document.querySelector('#hero-title').textContent = title;
        document.querySelector('#hero-kind').textContent = kind;
        document.querySelector('#hero-measure').textContent = measure;
        document.querySelector('#hero-value').textContent = value;
        const link = document.querySelector('#hero-cta');
        link.firstChild.textContent = cta + ' ';
        link.href = slide.href;
    });
}
document.querySelectorAll('[data-view]').forEach((button) =>
    button.addEventListener('click', () => {
        document
            .querySelector('#main-photo')
            .classList.toggle('zoom', button.dataset.view === 'zoom');
        document
            .querySelectorAll('[data-view]')
            .forEach((b) => b.setAttribute('aria-pressed', String(b === button)));
    }),
);
const area = document.querySelector('#area');
if (area)
    area.addEventListener('input', () => {
        const n = area.valueAsNumber;
        const packs = Math.ceil(
            Math.round(n * 1000) / Math.round(Number(area.dataset.coverage) * 1000),
        );
        const out = document.querySelector('#estimate');
        if (!Number.isFinite(n) || n <= 0) {
            out.textContent = '';
            return;
        }
        const amount = new Intl.NumberFormat('ru-RU', {
            style: 'currency',
            currency: 'RUB',
            maximumFractionDigits: 2,
        }).format((packs * Number(area.dataset.price)) / 100);
        const received = Number((packs * Number(area.dataset.coverage)).toFixed(3)).toLocaleString(
            'ru-RU',
        );
        out.textContent = packs + ' уп. · ' + received + ' м² · ' + amount;
        document.querySelector('#buy-qty').value = packs;
    });
const filters = document.querySelector('#filters');
if (filters) {
    const panel = document.querySelector('.filterpanel');
    if (matchMedia('(max-width: 760px)').matches) panel.open = false;
    let controller;
    async function update(url, push = true) {
        controller?.abort();
        const current = new AbortController();
        controller = current;
        const info = document.querySelector('#filter-status');
        info.textContent = 'Обновляем каталог…';
        try {
            const res = await fetch(url, {
                signal: current.signal,
                headers: { 'X-Sloy-Fragment': 'catalog' },
            });
            if (!res.ok) throw new Error('catalog');
            const data = await res.json();
            const page = new DOMParser().parseFromString(data.html, 'text/html');
            const listing = page.querySelector('#listing');
            if (!listing) throw new Error('catalog');
            document.querySelector('#listing').replaceWith(listing);
            document.querySelector('.active-filters')?.remove();
            if (data.selected.length) {
                const active = document.createElement('p');
                active.className = 'active-filters';
                active.textContent = 'Выбрано: ' + data.selected.join(' · ');
                listing.before(active);
            }
            document.title = data.title;
            document.querySelector('meta[name=description]').content = data.description;
            document.querySelector('link[rel=canonical]')?.remove();
            document.querySelector('meta[name=robots]')?.remove();
            if (data.canonical) {
                const canonical = document.createElement('link');
                canonical.rel = 'canonical';
                canonical.href = data.canonical;
                document.head.append(canonical);
            } else {
                const directive = document.createElement('meta');
                directive.name = 'robots';
                directive.content = 'noindex, follow';
                document.head.append(directive);
            }
            if (push) history.pushState({}, '', url);
            info.textContent = '';
        } catch (e) {
            if (e.name !== 'AbortError')
                info.textContent = 'Не удалось обновить список. Нажмите «Применить» ещё раз.';
        }
    }
    function apply() {
        const params = new URLSearchParams(new FormData(filters));
        for (const [k, v] of [...params]) if (!v) params.delete(k);
        const url = new URL(filters.action);
        url.search = params.toString();
        update(url);
    }
    filters.addEventListener('submit', (e) => {
        e.preventDefault();
        apply();
    });
    filters.addEventListener('change', (e) => {
        if (e.target.type !== 'search') apply();
    });
    document.addEventListener('click', (e) => {
        const a = e.target.closest('.pagination a');
        if (a && !e.ctrlKey && !e.metaKey && !e.shiftKey) {
            e.preventDefault();
            update(a.href);
        }
    });
    window.addEventListener('popstate', () => location.reload());
}
document.querySelector('.search')?.addEventListener('toggle', function () {
    if (this.open) this.querySelector('input').focus();
});
