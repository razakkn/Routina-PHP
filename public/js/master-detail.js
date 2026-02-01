(function(){
    const mq = window.matchMedia('(min-width: 900px) and (orientation: landscape)');

    function isLiteMode() {
        const conn = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
        return !!(conn && (conn.saveData || /2g/.test(conn.effectiveType || '')));
    }

    function initShell(shell) {
        if (!shell) return;
        const list = shell.querySelector('.md-list');
        const detail = shell.querySelector('.md-detail');
        const detailContent = shell.querySelector('.md-detail-content') || detail;
        const backBtn = shell.querySelector('.md-back');
        const cache = new Map();

        function applyMode() {
            const split = mq.matches;
            shell.classList.toggle('md-split', split);
            if (split) {
                shell.classList.remove('md-drill');
                if (backBtn) backBtn.setAttribute('aria-hidden', 'true');
            } else {
                if (backBtn) backBtn.removeAttribute('aria-hidden');
            }
        }

        function showList() {
            shell.classList.remove('md-drill');
        }

        function showDetail() {
            if (!mq.matches) {
                shell.classList.add('md-drill');
            }
        }

        function setDetailHtml(html) {
            if (detailContent) {
                detailContent.innerHTML = html;
            }
            showDetail();
        }

        function loadDetail(url) {
            if (!url) return;
            if (cache.has(url)) {
                setDetailHtml(cache.get(url));
                return;
            }
            if (detailContent) {
                detailContent.innerHTML = '<div class="text-muted">Loading…</div>';
            }
            fetch(url, { headers: { 'X-Requested-With': 'fetch' } })
                .then((res) => res.ok ? res.text() : Promise.reject(res.status))
                .then((html) => {
                    cache.set(url, html);
                    setDetailHtml(html);
                })
                .catch(() => {
                    if (detailContent) {
                        detailContent.innerHTML = '<div class="text-muted">Unable to load details.</div>';
                    }
                });
        }

        shell.addEventListener('click', (e) => {
            const target = e.target;
            if (!(target instanceof Element)) return;
            const item = target.closest('.md-item');
            const templateBtn = target.closest('[data-detail-template]');
            if (item && item.getAttribute('data-detail-url')) {
                e.preventDefault();
                loadDetail(item.getAttribute('data-detail-url'));
                return;
            }
            if (templateBtn) {
                const key = templateBtn.getAttribute('data-detail-template');
                if (!key) return;
                const template = document.getElementById('md-template-' + key);
                if (template) {
                    setDetailHtml(template.innerHTML);
                }
            }
        });

        if (backBtn) {
            backBtn.addEventListener('click', () => showList());
        }

        if (isLiteMode()) {
            shell.classList.add('md-lite');
        }

        applyMode();
        mq.addEventListener('change', applyMode);
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.md-shell').forEach(initShell);
    });
})();