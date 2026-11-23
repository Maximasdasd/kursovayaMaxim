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
        document.querySelector('#buy-qty').dispatchEvent(new Event('input', { bubbles: true }));
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


const formatRubles = (kopecks) => new Intl.NumberFormat('ru-RU', {
    style: 'currency', currency: 'RUB', maximumFractionDigits: 2,
}).format(kopecks / 100);
const quantityInput = document.querySelector('#buy-qty');
if (quantityInput) {
    quantityInput.addEventListener('input', () => {
        document.querySelector('#product-total').textContent = quantityInput.validity.valid
            ? formatRubles(quantityInput.valueAsNumber * Number(quantityInput.dataset.price))
            : 'Укажите количество';
    });
}

const cartStatus = document.querySelector('#cart-status');
const cartCheckout = document.querySelector('#cart-checkout');
const timers = new Map();
const versions = new WeakMap();
let cartQueue = Promise.resolve();
let pendingRequests = 0;
let cartUnconfirmed = false;

function updateCartLock() {
    if (cartCheckout) {
        const invalidQuantity = [...document.querySelectorAll('.cart-quantity [name="qty"]')].some((input) => !input.validity.valid);
        cartCheckout.setAttribute('aria-disabled', String(pendingRequests > 0 || timers.size > 0 || cartUnconfirmed || invalidQuantity));
    }
}

function previewCart() {
    let total = 0;
    document.querySelectorAll('[data-cart-id]').forEach((row) => {
        const input = row.querySelector('[name="qty"]');
        if (!input) return;
        const qty = input.validity.valid ? input.valueAsNumber : Number(input.defaultValue);
        const sum = qty * Number(row.dataset.price);
        row.querySelector('[data-line-total]').textContent = formatRubles(sum);
        total += sum;
    });
    const output = document.querySelector('[data-cart-total]');
    if (output) output.textContent = formatRubles(total);
}

function submitCart(form) {
    if (!form.reportValidity()) return;
    clearTimeout(timers.get(form));
    timers.delete(form);
    const data = new FormData(form);
    const version = versions.get(form) || 0;
    const isAdd = data.get('action') === 'add';
    const isRemove = data.get('action') === 'remove';
    const button = form.querySelector('button');
    const status = isAdd ? document.querySelector('#purchase-status') : cartStatus;
    if (isAdd || isRemove) button.disabled = true;
    pendingRequests++;
    updateCartLock();
    if (status) status.textContent = 'Сохраняем…';
    cartQueue = cartQueue.then(async () => {
        try {
            const response = await fetch(form.getAttribute('action'), {
                method: 'POST', body: data, credentials: 'same-origin',
                headers: { 'X-Sloy-Request': 'cart' },
            });
            if (response.status === 403) throw new Error('Сеанс изменился. Обновите страницу.');
            const result = await response.json();
            if (!Array.isArray(result.items)) throw new Error(result.error || 'Не удалось сохранить корзину.');
            document.querySelectorAll('[data-cart-count]').forEach((node) => node.textContent = result.count);
            const current = document.querySelector('[data-cart-id="' + data.get('id') + '"]');
            const item = result.items.find((line) => String(line.id) === data.get('id'));
            if (current && (versions.get(form) || 0) === version) {
                if (!item) {
                    current.remove();
                } else {
                    const input = current.querySelector('[name="qty"]');
                    input.value = item.qty;
                    input.defaultValue = item.qty;
                    current.dataset.price = item.price;
                }
            }
            previewCart();
            const empty = document.querySelector('#cart-empty');
            if (empty && result.count === 0 && !document.querySelector('[data-cart-id]')) {
                empty.hidden = false;
                document.querySelector('#cart-content').hidden = true;
            }
            cartUnconfirmed = false;
            if (status) status.textContent = result.ok ? (isAdd ? 'Товар добавлен. Корзина доступна в шапке сайта.' : 'Корзина обновлена.') : result.error;
        } catch (error) {
            cartUnconfirmed = true;
            if (status) status.textContent = error.message === 'Failed to fetch'
                ? 'Не получен ответ. Обновите корзину перед повторной отправкой.' : error.message;
        } finally {
            pendingRequests--;
            if (isAdd || isRemove) button.disabled = false;
            updateCartLock();
        }
    });
}

document.querySelectorAll('.cart-quantity').forEach((form) => {
    const input = form.querySelector('[name="qty"]');
    input.addEventListener('input', () => {
        versions.set(form, (versions.get(form) || 0) + 1);
        clearTimeout(timers.get(form));
        timers.delete(form);
        previewCart();
        if (input.validity.valid) {
            timers.set(form, setTimeout(() => submitCart(form), 250));
            if (cartStatus) cartStatus.textContent = 'Проверяем количество…';
        } else {
            cartUnconfirmed = true;
            if (cartStatus) cartStatus.textContent = 'Введите целое положительное количество.';
        }
        updateCartLock();
    });
});

document.querySelectorAll('.buy-form, .cart-quantity, .cart-remove').forEach((form) => {
    form.addEventListener('submit', (event) => {
        event.preventDefault();
        if (form.querySelector('button').disabled) return;
        if (form.classList.contains('cart-remove')) {
            const quantityForm = form.closest('[data-cart-id]').querySelector('.cart-quantity');
            clearTimeout(timers.get(quantityForm));
            timers.delete(quantityForm);
        }
        submitCart(form);
    });
});
if (cartCheckout) {
    cartCheckout.addEventListener('click', (event) => {
        if (cartCheckout.getAttribute('aria-disabled') === 'true') {
            event.preventDefault();
            cartStatus.textContent = cartUnconfirmed
                ? 'Подтвердите количество кнопкой «Обновить» или обновите страницу.'
                : 'Дождитесь сохранения корзины.';
        }
    });
}

document.querySelectorAll('[data-confirm]').forEach((button) => {
    button.closest('form').addEventListener('submit', (event) => {
        if (!confirm(button.dataset.confirm)) event.preventDefault();
    });
});
