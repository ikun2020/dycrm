(function () {
    const endpoint = '/admin/sample-shipment-badge';
    let isRefreshing = false;

    function findSampleNavigationLinks() {
        return Array.from(document.querySelectorAll('.fi-sidebar a[href]')).filter((link) => {
            try {
                return new URL(link.href, window.location.href).pathname.replace(/\/+$/, '') === '/admin/samples';
            } catch (error) {
                return false;
            }
        });
    }

    function styleCustomBadge(badge) {
        badge.dataset.dycrmSampleBadge = 'true';
        badge.style.display = 'inline-flex';
        badge.style.alignItems = 'center';
        badge.style.justifyContent = 'center';
        badge.style.minWidth = '1.25rem';
        badge.style.height = '1.25rem';
        badge.style.paddingInline = '0.375rem';
        badge.style.borderRadius = '0.5rem';
        badge.style.border = '1px solid #fecdd3';
        badge.style.background = '#fff1f2';
        badge.style.color = '#dc2626';
        badge.style.fontSize = '0.75rem';
        badge.style.fontWeight = '700';
        badge.style.lineHeight = '1';
        badge.style.marginInlineStart = 'auto';
    }

    function resetNativeTextBadge(textBadge) {
        textBadge.removeAttribute('data-dycrm-sample-badge');
        textBadge.style.removeProperty('display');
        textBadge.style.removeProperty('align-items');
        textBadge.style.removeProperty('justify-content');
        textBadge.style.removeProperty('min-width');
        textBadge.style.removeProperty('height');
        textBadge.style.removeProperty('padding-inline');
        textBadge.style.removeProperty('border-radius');
        textBadge.style.removeProperty('border');
        textBadge.style.removeProperty('background');
        textBadge.style.removeProperty('color');
        textBadge.style.removeProperty('font-size');
        textBadge.style.removeProperty('font-weight');
        textBadge.style.removeProperty('line-height');
        textBadge.style.removeProperty('margin-inline-start');
    }

    function findOrCreateBadge(link) {
        const customBadge = link.querySelector('[data-dycrm-sample-badge]');

        if (customBadge) {
            const parent = customBadge.parentElement;

            if (parent && parent !== link && parent.textContent.trim() === customBadge.textContent.trim()) {
                resetNativeTextBadge(customBadge);

                return {
                    container: parent,
                    text: customBadge,
                    custom: false,
                };
            }

            return {
                container: customBadge,
                text: customBadge,
                custom: true,
            };
        }

        const textBadge = Array.from(link.querySelectorAll('span')).find((element) => {
            const text = element.textContent.trim();

            return /^\d+$/.test(text) && ! element.children.length;
        });

        if (textBadge) {
            resetNativeTextBadge(textBadge);

            const parent = textBadge.parentElement;
            const container = parent &&
                parent !== link &&
                parent.textContent.trim() === textBadge.textContent.trim()
                    ? parent
                    : textBadge;

            return {
                container,
                text: textBadge,
                custom: false,
            };
        }

        const badge = document.createElement('span');
        link.appendChild(badge);
        styleCustomBadge(badge);

        return {
            container: badge,
            text: badge,
            custom: true,
        };
    }

    function updateBadges(count) {
        findSampleNavigationLinks().forEach((link) => {
            const badge = findOrCreateBadge(link);

            if (count > 0) {
                badge.text.textContent = String(count);
                badge.container.hidden = false;

                if (badge.custom) {
                    badge.container.style.display = 'inline-flex';
                } else {
                    badge.container.style.removeProperty('display');
                }

                return;
            }

            badge.text.textContent = '';
            badge.container.hidden = true;
            badge.container.style.display = 'none';
        });
    }

    async function refreshBadge() {
        if (isRefreshing) {
            return;
        }

        isRefreshing = true;

        try {
            const response = await fetch(endpoint, {
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (! response.ok) {
                return;
            }

            const payload = await response.json();
            updateBadges(Number(payload.count || 0));
        } catch (error) {
            // Keep navigation quiet if the transient badge request fails.
        } finally {
            isRefreshing = false;
        }
    }

    function scheduleRefresh(delay = 150) {
        window.setTimeout(refreshBadge, delay);
    }

    window.addEventListener('dycrm-sample-shipment-badge-refresh', () => scheduleRefresh(0));

    document.addEventListener('livewire:init', () => {
        if (! window.Livewire?.on) {
            return;
        }

        window.Livewire.on('dycrm-sample-shipment-badge-refresh', () => scheduleRefresh(0));
    });
})();
