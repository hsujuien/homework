(function () {
    const dimensionKeys = ['display', 'performance', 'storage', 'camera', 'battery', 'communication', 'features'];
    const palette = [
        '#0f766e',
        '#dc2626',
        '#2563eb',
        '#ca8a04',
        '#7c3aed',
        '#0891b2'
    ];

    document.querySelectorAll('[data-nav-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const nav = document.querySelector('[data-nav]');
            if (nav) {
                nav.classList.toggle('open');
            }
        });
    });

    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-show-more-phones]');
        if (!button) {
            return;
        }

        const target = button.getAttribute('data-phone-list-target') || '[data-phone-list]';
        const list = document.querySelector(target);
        if (!list) {
            return;
        }

        const pageSize = Math.max(1, Number(button.getAttribute('data-page-size')) || 8);
        const hiddenPhones = Array.from(list.querySelectorAll('[data-extra-phone]')).filter((card) => {
            return card.hidden || card.classList.contains('is-hidden') || card.hasAttribute('hidden');
        });

        hiddenPhones.slice(0, pageSize).forEach((card) => {
            card.hidden = false;
            card.removeAttribute('hidden');
            card.classList.remove('is-hidden');
        });

        const hasMore = Array.from(list.querySelectorAll('[data-extra-phone]')).some((card) => {
            return card.hidden || card.classList.contains('is-hidden') || card.hasAttribute('hidden');
        });

        if (!hasMore) {
            const wrap = button.closest('[data-show-more-wrap]');
            if (wrap) {
                wrap.hidden = true;
            }
        }
    });

    function parseJsonAttribute(element, name, fallback) {
        try {
            return JSON.parse(element.getAttribute(name) || '');
        } catch (error) {
            return fallback;
        }
    }

    function drawRadar(canvas) {
        const labels = parseJsonAttribute(canvas, 'data-labels', []);
        const series = parseJsonAttribute(canvas, 'data-series', []);
        const ctx = canvas.getContext('2d');
        if (!ctx || !labels.length || !series.length) {
            return;
        }

        const cssWidth = canvas.clientWidth || canvas.width;
        const cssHeight = canvas.clientHeight || canvas.height;
        const dpr = window.devicePixelRatio || 1;
        canvas.width = Math.round(cssWidth * dpr);
        canvas.height = Math.round(cssHeight * dpr);
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
        ctx.clearRect(0, 0, cssWidth, cssHeight);

        const cx = cssWidth / 2;
        const cy = cssHeight / 2 + 8;
        const radius = Math.min(cssWidth, cssHeight) * 0.32;
        const sides = labels.length;
        const startAngle = -Math.PI / 2;

        function point(index, value) {
            const angle = startAngle + (Math.PI * 2 * index) / sides;
            const distance = radius * (value / 100);
            return {
                x: cx + Math.cos(angle) * distance,
                y: cy + Math.sin(angle) * distance
            };
        }

        ctx.lineWidth = 1;
        ctx.font = '13px system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';

        for (let ring = 1; ring <= 4; ring += 1) {
            ctx.beginPath();
            for (let i = 0; i < sides; i += 1) {
                const p = point(i, ring * 25);
                if (i === 0) {
                    ctx.moveTo(p.x, p.y);
                } else {
                    ctx.lineTo(p.x, p.y);
                }
            }
            ctx.closePath();
            ctx.strokeStyle = ring === 4 ? '#9ca3af' : '#d8dee8';
            ctx.stroke();
        }

        for (let i = 0; i < sides; i += 1) {
            const outer = point(i, 100);
            ctx.beginPath();
            ctx.moveTo(cx, cy);
            ctx.lineTo(outer.x, outer.y);
            ctx.strokeStyle = '#e3e8ef';
            ctx.stroke();

            const label = point(i, 119);
            ctx.fillStyle = '#334155';
            ctx.fillText(labels[i], label.x, label.y);
        }

        series.forEach((item, index) => {
            const color = palette[index % palette.length];
            ctx.beginPath();
            dimensionKeys.forEach((key, i) => {
                const value = Math.max(0, Math.min(100, Number(item.scores ? item.scores[key] : 0)));
                const p = point(i, value);
                if (i === 0) {
                    ctx.moveTo(p.x, p.y);
                } else {
                    ctx.lineTo(p.x, p.y);
                }
            });
            ctx.closePath();
            ctx.fillStyle = color + '24';
            ctx.strokeStyle = color;
            ctx.lineWidth = 2;
            ctx.fill();
            ctx.stroke();

            dimensionKeys.forEach((key, i) => {
                const value = Math.max(0, Math.min(100, Number(item.scores ? item.scores[key] : 0)));
                const p = point(i, value);
                ctx.beginPath();
                ctx.arc(p.x, p.y, 3, 0, Math.PI * 2);
                ctx.fillStyle = color;
                ctx.fill();
            });
        });

        const legendY = cssHeight - Math.max(26, series.length * 20);
        series.forEach((item, index) => {
            const y = legendY + index * 20;
            ctx.fillStyle = palette[index % palette.length];
            ctx.fillRect(18, y - 6, 12, 12);
            ctx.fillStyle = '#475569';
            ctx.textAlign = 'left';
            ctx.fillText(item.name || `手機 ${index + 1}`, 38, y);
        });
    }

    function renderAllCharts() {
        document.querySelectorAll('canvas[data-radar]').forEach(drawRadar);
    }

    window.addEventListener('resize', renderAllCharts);
    renderAllCharts();
})();
