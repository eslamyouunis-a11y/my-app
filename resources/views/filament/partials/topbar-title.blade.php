<div class="fi-topbar-title px-3 py-2" data-fi-topbar-title></div>

<script>
    (() => {
        const setTitle = () => {
            const target = document.querySelector('[data-fi-topbar-title]');
            if (!target) return;
            const activeItem = document.querySelector('.fi-main-sidebar .fi-sidebar-item.fi-active');
            const icon = activeItem?.querySelector('.fi-sidebar-item-icon, .fi-sidebar-item-button svg');
            const labelText = activeItem?.querySelector('.fi-sidebar-item-label')?.textContent?.trim();
            const badgeText = activeItem?.querySelector('.fi-badge')?.textContent?.trim();
            const heading = document.querySelector('.fi-header-heading');
            const breadcrumb = document.querySelector('.fi-breadcrumbs li:last-child');
            const fallbackText = (heading?.textContent || breadcrumb?.textContent || '').trim();

            const wrapper = document.createElement('div');
            wrapper.className = 'fi-topbar-title-inner';

            if (badgeText) {
                const count = document.createElement('div');
                count.className = 'fi-topbar-title-count';
                count.textContent = badgeText;
                wrapper.appendChild(count);
            }

            if (icon) {
                const iconWrapper = document.createElement('span');
                iconWrapper.className = 'fi-topbar-title-icon';
                iconWrapper.appendChild(icon.cloneNode(true));
                wrapper.appendChild(iconWrapper);
            }

            if (labelText || fallbackText) {
                const label = document.createElement('span');
                label.className = 'fi-topbar-title-text';
                label.textContent = labelText || fallbackText;
                wrapper.appendChild(label);
            }

            target.innerHTML = '';
            target.appendChild(wrapper);
        };

        document.addEventListener('DOMContentLoaded', setTitle);
        document.addEventListener('livewire:navigated', setTitle);
        document.addEventListener('livewire:navigate', setTitle);
    })();
</script>
