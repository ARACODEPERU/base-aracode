import '../sass/torneos-landing.scss';
import { animate, stagger } from 'animejs';

const prefersReducedMotion = () =>
    typeof window !== 'undefined' && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

const running = [];

const track = (instance) => {
    if (instance) {
        running.push(instance);
    }
};

const cleanupAll = () => {
    running.forEach((instance) => {
        try {
            instance?.pause?.();
            instance?.cancel?.();
        } catch {
            /* ignore */
        }
    });
    running.length = 0;
};

const parseStatNumber = (raw) => {
    const text = String(raw ?? '').trim();
    const match = text.match(/-?\d+(\.\d+)?/);
    return match ? parseFloat(match[0]) : null;
};

function initMobileNav() {
    const toggle = document.querySelector('[data-se-menu-toggle]');
    const panel = document.querySelector('[data-se-mobile-nav]');
    if (!toggle || !panel) {
        return;
    }

    const close = () => {
        panel.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
    };

    toggle.addEventListener('click', () => {
        const open = panel.classList.toggle('is-open');
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        document.body.style.overflow = open ? 'hidden' : '';
    });

    panel.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', close);
    });
}

function initHeaderScroll() {
    const header = document.querySelector('.se-header');
    if (!header) {
        return;
    }

    const onScroll = () => {
        header.classList.toggle('is-scrolled', window.scrollY > 24);
    };

    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
}

function initNavHighlight() {
    const links = document.querySelectorAll('.se-nav a[href^="#"], .se-mobile-nav a[href^="#"]');
    const sections = [...links]
        .map((a) => {
            const id = a.getAttribute('href')?.slice(1);
            const el = id ? document.getElementById(id) : null;
            return el ? { link: a, el } : null;
        })
        .filter(Boolean);

    if (!sections.length) {
        return;
    }

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) {
                    return;
                }
                const id = entry.target.id;
                document.querySelectorAll('.se-nav a').forEach((a) => {
                    a.classList.toggle('is-active', a.getAttribute('href') === `#${id}`);
                });
            });
        },
        { rootMargin: '-40% 0px -50% 0px', threshold: 0 },
    );

    sections.forEach(({ el }) => observer.observe(el));
}

function runHeroEntrance() {
    const heroTargets = document.querySelectorAll('[data-se-hero]');
    if (!heroTargets.length) {
        return;
    }

    track(
        animate(heroTargets, {
            opacity: [0, 1],
            y: ['32px', '0px'],
            scale: [0.97, 1],
            duration: 1000,
            ease: 'outExpo',
            delay: stagger(90, { start: 120 }),
        }),
    );

    const statValues = document.querySelectorAll('[data-se-count]');
    statValues.forEach((el) => {
        const end = parseStatNumber(el.dataset.seCount ?? el.textContent);
        if (end === null) {
            return;
        }
        const decimals = String(end).includes('.') ? 1 : 0;
        const counter = { val: 0 };
        track(
            animate(counter, {
                val: [0, end],
                duration: 1400,
                ease: 'outExpo',
                delay: 400,
                onUpdate: () => {
                    el.textContent = counter.val.toFixed(decimals);
                },
            }),
        );
    });
}

function runAmbientMotion() {
    const orbs = document.querySelectorAll('.se-ambient__orb');
    orbs.forEach((el, index) => {
        track(
            animate(el, {
                x: ['0px', `${(index % 2 === 0 ? 1 : -1) * (30 + index * 12)}px`, '0px'],
                y: ['0px', `${-20 - index * 15}px`, '0px'],
                scale: [1, 1.08, 1],
                opacity: [0.5, 0.85, 0.5],
                duration: 9000 + index * 2000,
                ease: 'inOutSine',
                loop: true,
            }),
        );
    });

    const grid = document.querySelector('.se-ambient__grid');
    if (grid) {
        track(
            animate(grid, {
                opacity: [0, 0.35],
                duration: 2000,
                ease: 'outQuad',
            }),
        );
    }
}

function initScrollReveal() {
    const items = document.querySelectorAll('[data-se-reveal]');
    if (!items.length) {
        return;
    }

    const revealed = new WeakSet();

    const observer = new IntersectionObserver(
        (entries) => {
            const visible = entries.filter((e) => e.isIntersecting).map((e) => e.target);
            if (!visible.length) {
                return;
            }
            track(
                animate(visible, {
                    opacity: [0, 1],
                    y: ['28px', '0px'],
                    duration: 700,
                    ease: 'outCubic',
                    delay: stagger(60, { from: 'first' }),
                }),
            );
            visible.forEach((el) => revealed.add(el));
        },
        { threshold: 0.12, rootMargin: '0px 0px -8% 0px' },
    );

    items.forEach((el) => observer.observe(el));

    return () => observer.disconnect();
}

function initBrandHover() {
    const brand = document.querySelector('.se-brand__icon');
    if (!brand) {
        return;
    }
    brand.addEventListener('mouseenter', () => {
        track(
            animate(brand, {
                rotate: ['0deg', '-8deg', '8deg', '0deg'],
                scale: [1, 1.08, 1],
                duration: 500,
                ease: 'outElastic(1, .6)',
            }),
        );
    });
}

function initGalleryLightbox() {
    const overlay = document.querySelector('[data-se-lightbox]');
    const items = document.querySelectorAll('[data-se-gallery-open]');
    if (!overlay || !items.length) {
        return;
    }

    const mediaContainer = overlay.querySelector('.se-lightbox__media');
    const labelEl = overlay.querySelector('.se-lightbox__label');
    const closeBtn = overlay.querySelector('[data-se-lightbox-close]');
    const body = document.body;

    const open = (item) => {
        const type = item.dataset.mediaType;
        const url = item.dataset.mediaUrl;
        const mime = item.dataset.mediaMime || '';
        const label = item.dataset.mediaLabel || '';

        mediaContainer.innerHTML = '';

        if (type === 'video') {
            const video = document.createElement('video');
            video.src = url;
            video.controls = true;
            video.autoplay = true;
            video.playsInline = true;
            mediaContainer.appendChild(video);
        } else {
            const img = document.createElement('img');
            img.src = url;
            img.alt = 'Foto de la galería';
            mediaContainer.appendChild(img);
        }

        labelEl.textContent = label;
        overlay.classList.add('is-open');
        overlay.setAttribute('aria-hidden', 'false');
        body.style.overflow = 'hidden';
    };

    const close = () => {
        const video = mediaContainer.querySelector('video');
        if (video) {
            video.pause();
            video.removeAttribute('src');
            video.load();
        }
        mediaContainer.innerHTML = '';
        overlay.classList.remove('is-open');
        overlay.setAttribute('aria-hidden', 'true');
        body.style.overflow = '';
    };

    items.forEach((item) => {
        item.addEventListener('click', () => open(item));
    });

    closeBtn?.addEventListener('click', close);

    overlay.addEventListener('click', (event) => {
        if (event.target === overlay) {
            close();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            close();
        }
    });
}

/* -------------------------------------------------------------------------
 * Splash publicitario (patrocinio de la empresa)
 * ---------------------------------------------------------------------- */

function initSplash() {
    const splash = document.querySelector('[data-se-splash]');
    if (!splash) {
        return;
    }

    const minVisible = Number.parseInt(splash.style.getPropertyValue('--se-splash-ms'), 10) || 0;
    const startedAt = performance.now();
    let finished = false;

    document.body.style.overflow = 'hidden';

    const hide = () => {
        if (finished) {
            return;
        }
        finished = true;
        document.body.style.overflow = '';
        splash.classList.add('is-hiding');
        window.setTimeout(() => {
            splash.classList.add('is-hidden');
            splash.remove();
        }, 460);
    };

    const scheduleHide = () => {
        const elapsed = performance.now() - startedAt;
        window.setTimeout(hide, Math.max(0, minVisible - elapsed));
    };

    if (document.readyState === 'complete') {
        scheduleHide();
    } else {
        window.addEventListener('load', scheduleHide, { once: true });
        // Red de seguridad: si "load" nunca llega, el splash no bloquea la landing.
        window.setTimeout(scheduleHide, minVisible + 3000);
    }
}

/* -------------------------------------------------------------------------
 * Modal con el detalle partido a partido de un jugador
 * ---------------------------------------------------------------------- */

const PLAYER_CATEGORY_LABELS = {
    player: 'Mejor jugador',
    scorer: 'Goleador de la temporada',
    goalkeeper: 'Mejor arquero',
};

const escapeHtml = (value) =>
    String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');

const formatNumber = (value) => {
    const number = Number(value ?? 0);
    if (!Number.isFinite(number)) {
        return '0';
    }
    return String(Math.round(number * 100) / 100);
};

// Mismo signo menos (−) que usa la interfaz para los valores negativos.
const formatSigned = (value) => {
    const text = formatNumber(value);
    return text.startsWith('-') ? `−${text.slice(1)}` : text;
};

const playerInitials = (name) =>
    String(name ?? '')
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('') || 'J';

function initPlayerModal() {
    const modal = document.querySelector('[data-se-player-modal]');
    if (!modal) {
        return;
    }

    const urlTemplate = modal.dataset.playerUrl || '';
    const bodyEl = modal.querySelector('[data-se-modal-body]');
    const nameEl = modal.querySelector('[data-se-modal-name]');
    const metaEl = modal.querySelector('[data-se-modal-meta]');
    const categoryEl = modal.querySelector('[data-se-modal-category]');
    const avatarEl = modal.querySelector('[data-se-modal-avatar]');
    const closeBtn = modal.querySelector('.se-modal__close');

    if (!urlTemplate || !bodyEl || !nameEl || !categoryEl || !avatarEl) {
        return;
    }

    let lastFocused = null;
    let requestToken = 0;

    const buildUrl = (playerId, category) =>
        `${urlTemplate.replace('__PLAYER__', encodeURIComponent(playerId))}?categoria=${encodeURIComponent(category)}`;

    const summaryItems = (category, summary) => {
        const items = [{ label: 'Partidos', value: summary.matches_played ?? 0 }];

        if (category === 'goalkeeper') {
            items.push({ label: 'Atajadas', value: summary.saves ?? 0 });
        } else {
            items.push({ label: 'Goles', value: summary.goals ?? 0 });
            items.push({ label: 'Asistencias', value: summary.assists ?? 0 });
        }

        items.push({ label: 'MVP', value: summary.mvp ?? 0 });
        items.push({ label: 'Valla invicta', value: summary.clean_sheet ?? 0 });

        if (Number(summary.sanctions ?? 0) > 0) {
            items.push({ label: 'Sanciones', value: summary.sanctions });
        }

        if (summary.points !== null && summary.points !== undefined) {
            items.push({ label: 'Puntaje', value: formatSigned(summary.points), accent: true });
        }

        return items;
    };

    const renderSummary = (category, summary) => `
        <div class="se-modal__summary">
            ${summaryItems(category, summary)
                .map(
                    (item) => `
                        <div class="se-modal__stat${item.accent ? ' se-modal__stat--accent' : ''}">
                            <span class="se-modal__stat-value">${escapeHtml(item.value)}</span>
                            <span class="se-modal__stat-label">${escapeHtml(item.label)}</span>
                        </div>`,
                )
                .join('')}
        </div>`;

    const renderReasons = (category, reasons, summary) => {
        if (!reasons.length) {
            return '';
        }

        const title =
            category === 'scorer'
                ? 'Por qué está en la tabla de goleadores'
                : 'Cómo se calculó su puntaje';

        const rows = reasons
            .map((reason) => {
                // Los conceptos informativos (sin valor unitario) muestran solo su cantidad.
                const hasUnit = reason.unit !== null && reason.unit !== undefined;
                const calculation = hasUnit
                    ? `${escapeHtml(reason.count)} × ${escapeHtml(formatSigned(reason.unit))}`
                    : escapeHtml(reason.count);

                return `
                    <li class="se-modal__reason">
                        <span class="se-modal__reason-concept">${escapeHtml(reason.concept)}</span>
                        <span class="se-modal__reason-detail">
                            ${calculation}
                            ${reason.points === null || reason.points === undefined
                                ? ''
                                : `<strong>${escapeHtml(formatSigned(reason.points))} pts</strong>`}
                        </span>
                    </li>`;
            })
            .join('');

        const total =
            summary.points === null || summary.points === undefined
                ? ''
                : `<p class="se-modal__reason-total">Total: <strong>${escapeHtml(formatSigned(summary.points))} pts</strong></p>`;

        return `
            <section class="se-modal__section">
                <h4 class="se-modal__section-title">${escapeHtml(title)}</h4>
                <ul class="se-modal__reasons">${rows}</ul>
                ${total}
            </section>`;
    };

    const renderListSection = (title, items, emptyText) => `
        <section class="se-modal__section">
            <h4 class="se-modal__section-title">${escapeHtml(title)}</h4>
            ${items.length
                ? `<ul class="se-modal__list">${items.map((item) => `<li>${item}</li>`).join('')}</ul>`
                : `<p class="se-modal__empty">${escapeHtml(emptyText)}</p>`}
        </section>`;

    const renderHighlights = (category, highlights) => {
        const sections = [];

        const mvpMatches = highlights.mvp_matches || [];
        if (category !== 'scorer' || mvpMatches.length) {
            sections.push(
                renderListSection(
                    'Partidos donde fue MVP',
                    mvpMatches.map((label) => `<span class="se-modal__badge se-modal__badge--mvp">MVP</span> ${escapeHtml(label)}`),
                    'Todavía no fue elegido MVP.',
                ),
            );
        }

        if (category !== 'goalkeeper') {
            const scoringMatches = highlights.scoring_matches || [];
            sections.push(
                renderListSection(
                    'Partidos donde anotó',
                    scoringMatches.map(
                        (row) =>
                            `<span class="se-modal__badge se-modal__badge--goal">${escapeHtml(row.goals)} ${Number(row.goals) === 1 ? 'gol' : 'goles'}</span> ${escapeHtml(row.label)}`,
                    ),
                    'Todavía no anotó goles.',
                ),
            );
        }

        if (category === 'goalkeeper') {
            const cleanSheets = highlights.clean_sheet_matches || [];
            sections.push(
                renderListSection(
                    'Partidos con valla invicta',
                    cleanSheets.map((label) => `<span class="se-modal__badge se-modal__badge--clean">VI</span> ${escapeHtml(label)}`),
                    'Todavía no terminó un partido con valla invicta.',
                ),
            );
        }

        return sections.join('');
    };

    const renderBreakdown = (category, breakdown) => {
        const isGoalkeeper = category === 'goalkeeper';
        // Los goleadores se ordenan por goles: su tabla no tiene columna de puntaje.
        const hasPoints = breakdown.some((row) => row.points !== null && row.points !== undefined);

        const columns = isGoalkeeper
            ? ['Fecha', 'Partido', 'Atajadas', 'MVP', 'Valla inv.', 'Sanc.']
            : ['Fecha', 'Partido', 'Goles', 'Asist.', 'MVP', 'Valla inv.', 'Sanc.'];

        if (hasPoints) {
            columns.push('Pts');
        }

        const head = columns.map((column) => `<th scope="col">${escapeHtml(column)}</th>`).join('');

        const rows = breakdown
            .map((row) => {
                const flag = (active, text, modifier) =>
                    active
                        ? `<span class="se-modal__badge se-modal__badge--${modifier}">${escapeHtml(text)}</span>`
                        : '<span class="se-modal__muted">—</span>';

                const cells = [
                    `<td>${escapeHtml(row.date || 'Sin fecha')}</td>`,
                    `<td class="se-modal__match">${escapeHtml(row.label)}</td>`,
                ];

                if (isGoalkeeper) {
                    cells.push(`<td>${escapeHtml(formatNumber(row.saves ?? 0))}</td>`);
                } else {
                    cells.push(`<td>${escapeHtml(formatNumber(row.goals ?? 0))}</td>`);
                    cells.push(`<td>${escapeHtml(formatNumber(row.assists ?? 0))}</td>`);
                }

                cells.push(`<td>${flag(row.is_mvp, 'MVP', 'mvp')}</td>`);
                cells.push(`<td>${flag(row.clean_sheet, 'VI', 'clean')}</td>`);
                cells.push(
                    `<td>${Number(row.sanctions ?? 0) > 0 ? escapeHtml(formatNumber(row.sanctions)) : '<span class="se-modal__muted">—</span>'}</td>`,
                );
                if (hasPoints) {
                    cells.push(
                        `<td>${row.points === null || row.points === undefined ? '<span class="se-modal__muted">—</span>' : escapeHtml(formatSigned(row.points))}</td>`,
                    );
                }

                return `<tr>${cells.join('')}</tr>`;
            })
            .join('');

        return `
            <section class="se-modal__section">
                <h4 class="se-modal__section-title">Partido a partido</h4>
                ${breakdown.length
                    ? `<div class="se-modal__table-wrap">
                            <table class="se-modal__table">
                                <thead><tr>${head}</tr></thead>
                                <tbody>${rows}</tbody>
                            </table>
                        </div>`
                    : '<p class="se-modal__empty">Todavía no hay partidos registrados para este jugador.</p>'}
            </section>`;
    };

    const render = (player, category, data) => {
        const info = data.player || {};
        const summary = data.summary || {};
        const resolvedCategory = data.category || category;

        categoryEl.textContent = PLAYER_CATEGORY_LABELS[resolvedCategory] || 'Detalle del jugador';
        nameEl.textContent = info.name || player.name || 'Jugador';

        metaEl.textContent = [
            info.team_name,
            info.position,
            info.jersey_number ? `#${info.jersey_number}` : null,
        ]
            .filter(Boolean)
            .join(' · ');

        avatarEl.innerHTML = info.photo
            ? `<img src="${escapeHtml(info.photo)}" alt="${escapeHtml(info.name || 'Jugador')}" loading="lazy">`
            : `<span class="se-modal__avatar-initials">${escapeHtml(playerInitials(info.name))}</span>`;

        bodyEl.innerHTML = [
            renderSummary(resolvedCategory, summary),
            renderReasons(resolvedCategory, data.reasons || [], summary),
            renderHighlights(resolvedCategory, data.highlights || {}),
            renderBreakdown(resolvedCategory, data.breakdown || []),
        ].join('');
    };

    const close = () => {
        requestToken += 1;
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        bodyEl.innerHTML = '';
        avatarEl.innerHTML = '';

        if (lastFocused && typeof lastFocused.focus === 'function') {
            lastFocused.focus();
        }
        lastFocused = null;
    };

    const open = async (trigger) => {
        const playerId = trigger.dataset.playerId;
        const category = trigger.dataset.playerCategory || 'player';
        if (!playerId) {
            return;
        }

        lastFocused = document.activeElement;
        requestToken += 1;
        const token = requestToken;

        categoryEl.textContent = PLAYER_CATEGORY_LABELS[category] || 'Detalle del jugador';
        nameEl.textContent = trigger.dataset.playerName || 'Jugador';
        metaEl.textContent = '';
        avatarEl.innerHTML = `<span class="se-modal__avatar-initials">${escapeHtml(playerInitials(trigger.dataset.playerName))}</span>`;
        bodyEl.innerHTML =
            '<p class="se-modal__state"><i class="fas fa-circle-notch fa-spin" aria-hidden="true"></i> Cargando detalles…</p>';

        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        // El foco entra al diálogo apenas se abre (el botón de cierre es el primero).
        closeBtn?.focus();

        try {
            const response = await fetch(buildUrl(playerId, category), {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const payload = await response.json();

            if (token !== requestToken) {
                return;
            }

            if (!payload || !payload.success || !payload.data) {
                throw new Error('Respuesta sin datos');
            }

            render(
                { id: playerId, name: trigger.dataset.playerName },
                category,
                payload.data,
            );
        } catch {
            if (token !== requestToken) {
                return;
            }

            bodyEl.innerHTML =
                '<p class="se-modal__state se-modal__state--error"><i class="fas fa-triangle-exclamation" aria-hidden="true"></i> No pudimos cargar el detalle. Vuelve a intentarlo.</p>';
        }
    };

    document.querySelectorAll('[data-se-player-open]').forEach((trigger) => {
        trigger.addEventListener('click', () => open(trigger));
    });

    modal.querySelectorAll('[data-se-player-close]').forEach((element) => {
        element.addEventListener('click', close);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && modal.classList.contains('is-open')) {
            close();
        }
    });
}

function boot() {
    const body = document.body;
    if (!body.classList.contains('se-landing')) {
        return;
    }

    if (prefersReducedMotion()) {
        body.classList.add('se-motion-off');
        document.querySelectorAll('[data-se-reveal]').forEach((el) => {
            el.style.opacity = '1';
            el.style.transform = 'none';
        });
        initSplash();
        initMobileNav();
        initHeaderScroll();
        initNavHighlight();
        initGalleryLightbox();
        initPlayerModal();
        return;
    }

    initSplash();
    initMobileNav();
    initHeaderScroll();
    initNavHighlight();
    initBrandHover();
    initGalleryLightbox();
    initPlayerModal();
    runAmbientMotion();
    runHeroEntrance();
    initScrollReveal();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}

window.addEventListener('pagehide', cleanupAll);
