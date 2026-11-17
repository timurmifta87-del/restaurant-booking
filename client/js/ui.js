const $ = (selector, root = document) => root.querySelector(selector);

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, (c) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    })[c]);
}

/** Время показываем так, как оно записано на сервере (без пересчёта часового пояса) */
function formatDate(iso) {
    return iso ? new Date(iso.slice(0, 19)).toLocaleString('ru-RU', { dateStyle: 'short', timeStyle: 'short' }) : '';
}

function formatMoney(value) {
    return Number(value).toLocaleString('ru-RU', { maximumFractionDigits: 2 }) + ' ₸';
}

/** Сообщение вверху страницы: type = success | error | info */
function notify(message, type = 'info') {
    const box = $('#notice');
    if (!box) return alert(message);
    box.className = `notice notice-${type}`;
    box.textContent = message;
    box.hidden = false;
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function clearNotice() {
    const box = $('#notice');
    if (box) box.hidden = true;
}

/** Ошибки 422 рядом с полями формы: <small class="field-error" data-for="name"> */
function showFieldErrors(form, errors) {
    clearFieldErrors(form);
    Object.entries(errors).forEach(([field, messages]) => {
        const key = field.split('.')[0];
        const slot = form.querySelector(`[data-for="${key}"]`);
        const input = form.querySelector(`[name="${key}"]`);
        if (slot) slot.textContent = messages.join(' ');
        if (input) input.classList.add('invalid');
    });
}

function clearFieldErrors(form) {
    form.querySelectorAll('.field-error').forEach((el) => (el.textContent = ''));
    form.querySelectorAll('.invalid').forEach((el) => el.classList.remove('invalid'));
}

/** Проверка обязательных полей на клиенте до отправки */
function checkRequired(form) {
    const errors = {};
    form.querySelectorAll('[required]').forEach((input) => {
        if (!input.value.trim()) errors[input.name] = ['Заполните это поле.'];
    });
    showFieldErrors(form, errors);
    return Object.keys(errors).length === 0;
}

function handleError(error, form = null) {
    if (error.status === 422 && form) {
        showFieldErrors(form, error.errors);
        notify(error.message, 'error');
    } else if (error.status !== 401) {
        notify(error.message, 'error');
    }
}

/** Пагинация по блоку meta из ответа Laravel */
function renderPagination(container, meta, onPage) {
    container.innerHTML = '';
    if (!meta || meta.last_page <= 1) {
        container.textContent = meta ? `Всего: ${meta.total}` : '';
        return;
    }
    const prev = document.createElement('button');
    prev.textContent = '← Назад';
    prev.disabled = meta.current_page === 1;
    prev.onclick = () => onPage(meta.current_page - 1);

    const info = document.createElement('span');
    info.textContent = `Страница ${meta.current_page} из ${meta.last_page} · всего ${meta.total}`;

    const next = document.createElement('button');
    next.textContent = 'Вперёд →';
    next.disabled = meta.current_page === meta.last_page;
    next.onclick = () => onPage(meta.current_page + 1);

    container.append(prev, info, next);
}

/** Шапка с навигацией, именем и ролью. requireAuth — пускать только после входа */
async function initLayout({ requireAuth = true, adminOnly = false } = {}) {
    if (requireAuth && !auth.token()) {
        location.href = 'login.html';
        return null;
    }

    let user = auth.user();
    if (auth.token()) {
        try {
            const res = await api('/user');
            user = res.data;
            auth.save(auth.token(), user);
        } catch (e) {
            if (requireAuth) return null;
        }
    }

    if (adminOnly && user?.role !== 'admin') {
        location.href = 'index.html';
        return null;
    }

    const header = $('#header');
    if (header) {
        const isAdmin = user?.role === 'admin';
        header.innerHTML = `
            <a class="brand" href="index.html">Ресторан · бронирование</a>
            <nav>
                ${user ? '<a href="index.html">Брони</a><a href="new-booking.html">Забронировать</a>' : ''}
                <a href="menu.html">Меню</a>
                ${isAdmin ? '<a href="halls.html">Залы</a><a href="stats.html">Статистика</a>' : ''}
            </nav>
            <div class="user">
                ${user
                    ? `<span>${escapeHtml(user.name)} · <b>${escapeHtml(user.role_label)}</b></span>
                       <button id="logout" class="secondary">Выйти</button>`
                    : '<a href="login.html">Войти</a>'}
            </div>`;
        const btn = $('#logout');
        if (btn) {
            btn.onclick = async () => {
                try { await api('/logout', { method: 'POST' }); } catch (e) {}
                auth.clear();
                location.href = 'login.html';
            };
        }
        const current = location.pathname.split('/').pop() || 'index.html';
        header.querySelectorAll('nav a').forEach((a) => {
            if (a.getAttribute('href') === current) a.classList.add('active');
        });
    }

    document.body.classList.toggle('is-admin', user?.role === 'admin');
    return user;
}
