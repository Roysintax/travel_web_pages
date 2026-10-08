document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-image-upload]').forEach(input => {
        const preview = document.getElementById(input.dataset.previewTarget);
        if (!preview) return;
        const original = [...preview.childNodes].map(node => node.cloneNode(true));
        let generation = 0;
        input.addEventListener('change', () => {
            const currentGeneration = ++generation;
            const files = [...input.files];
            preview.replaceChildren();
            if (!files.length) {
                preview.append(...original.map(node => node.cloneNode(true)));
                return;
            }
            const maximum = input.multiple ? 5 : 1;
            if (files.length > maximum || files.some(file => file.size > 2 * 1024 * 1024)) {
                const error = document.createElement('p');
                error.className = 'field-error';
                error.setAttribute('role', 'alert');
                error.textContent = `Pilih maksimal ${maximum} file, masing-masing tidak lebih dari 2 MB.`;
                preview.append(error);
                input.value = '';
                return;
            }
            files.forEach(file => {
                const figure = document.createElement('figure');
                const caption = document.createElement('figcaption');
                caption.textContent = file.name;
                figure.append(caption);
                preview.append(figure);
                if (!['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/avif'].includes(file.type)) return;
                const reader = new FileReader();
                reader.addEventListener('load', () => {
                    if (generation !== currentGeneration) return;
                    const image = document.createElement('img');
                    image.src = reader.result;
                    image.alt = 'Preview ' + file.name;
                    figure.prepend(image);
                });
                reader.readAsDataURL(file);
            });
        });
    });
    const toggle = document.querySelector('.mobile-menu');
    const sidebar = document.querySelector('.sidebar');
    const main = document.querySelector('.main');
    const mobile = window.matchMedia('(max-width: 760px)');
    const close = (restoreFocus = true) => {
        document.body.classList.remove('sidebar-open');
        toggle?.setAttribute('aria-expanded', 'false');
        if (main) main.inert = false;
        if (sidebar) sidebar.inert = mobile.matches;
        if (restoreFocus) toggle?.focus();
    };
    close(false);
    mobile.addEventListener('change', () => close(false));
    toggle?.addEventListener('click', () => {
        if (document.body.classList.contains('sidebar-open')) {
            close();
            return;
        }
        document.body.classList.add('sidebar-open');
        toggle.setAttribute('aria-expanded', 'true');
        sidebar.inert = false;
        main.inert = true;
        sidebar.querySelector('a')?.focus();
    });
    document.querySelector('[data-close-sidebar]')?.addEventListener('click', () => close());
    document.querySelector('.mobile-close')?.addEventListener('click', () => close());
    document.addEventListener('keydown', event => {
        if (!document.body.classList.contains('sidebar-open')) return;
        if (event.key === 'Escape') close();
        if (event.key === 'Tab') {
            const focusable = [...sidebar.querySelectorAll('a[href],button')].filter(element => element.getClientRects().length);
            const first = focusable[0];
            const last = focusable[focusable.length - 1];
            if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
            if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
        }
    });
    const dialog = document.querySelector('#delete-dialog');
    let pending = null;
    document.querySelectorAll('[data-delete-form]').forEach(form => form.addEventListener('submit', event => {
        event.preventDefault();
        pending = form;
        dialog.returnValue = '';
        dialog.showModal();
    }));
    dialog?.addEventListener('close', () => {
        if (dialog.returnValue === 'confirm' && pending) pending.submit();
        pending = null;
    });
});
