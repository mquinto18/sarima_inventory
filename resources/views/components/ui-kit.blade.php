<!-- Shared confirm dialog + toast notification + button-loading helpers.
     Included once via layouts/app.blade.php so every page gets the same
     look/behavior instead of native alert()/confirm() or one-off toasts. -->
<div class="confirm-overlay" id="confirmOverlay">
    <div class="modal-card confirm-modal">
        <div class="confirm-modal-icon" id="confirmModalIcon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 9v4M12 17h.01M10.29 3.86l-8.18 14.18A1.5 1.5 0 0 0 3.5 20.5h17a1.5 1.5 0 0 0 1.39-2.46L13.71 3.86a1.5 1.5 0 0 0-2.42 0z"
                    stroke-linecap="round" stroke-linejoin="round" />
            </svg>
        </div>
        <div class="confirm-modal-title" id="confirmModalTitle">Are you sure?</div>
        <div class="confirm-modal-message" id="confirmModalMessage"></div>
        <div class="confirm-modal-actions">
            <button type="button" class="btn-action" id="confirmModalCancel" style="background:#6c757d;">Cancel</button>
            <button type="button" class="btn-action reject" id="confirmModalConfirm">Confirm</button>
        </div>
    </div>
</div>

<div class="app-toast" id="appToast" role="status" aria-live="polite">
    <div class="app-toast-icon" id="appToastIcon"></div>
    <div class="app-toast-body">
        <div class="app-toast-title" id="appToastTitle">Success</div>
        <div class="app-toast-message" id="appToastMessage"></div>
    </div>
    <button type="button" class="app-toast-close" id="appToastClose" aria-label="Close">&times;</button>
</div>

<script>
    (function () {
        var ICONS = {
            success: '<svg viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.2"><circle cx="12" cy="12" r="10" opacity="0.15" fill="#16a34a" stroke="none"/><path d="M8 12.5l3 3 5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            error: '<svg viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2.2"><circle cx="12" cy="12" r="10" opacity="0.15" fill="#ef4444" stroke="none"/><path d="M15 9l-6 6M9 9l6 6" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            info: '<svg viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2.2"><circle cx="12" cy="12" r="10" opacity="0.15" fill="#6366f1" stroke="none"/><path d="M12 8h.01M12 11v5" stroke-linecap="round" stroke-linejoin="round"/></svg>'
        };
        var BORDER_COLOR = { success: '#16a34a', error: '#ef4444', info: '#6366f1' };
        var toastTimer = null;

        window.showToast = function (message, type, title) {
            type = type || 'success';
            var toast = document.getElementById('appToast');
            if (!toast) return;
            document.getElementById('appToastIcon').innerHTML = ICONS[type] || ICONS.success;
            document.getElementById('appToastTitle').textContent = title || (type === 'error' ? 'Error' : type === 'info' ? 'Notice' : 'Success');
            // textContent, not innerHTML: toast messages carry user-supplied
            // data (product names, account names), so interpolating markup here
            // would be a stored-XSS hole. Newlines still render as line breaks
            // via `white-space: pre-line` on .app-toast-message.
            document.getElementById('appToastMessage').textContent = message;
            toast.style.borderLeftColor = BORDER_COLOR[type] || BORDER_COLOR.success;
            toast.classList.add('show');

            clearTimeout(toastTimer);
            toastTimer = setTimeout(function () {
                toast.classList.remove('show');
            }, type === 'error' ? 7000 : 4000);
        };

        document.addEventListener('DOMContentLoaded', function () {
            var closeBtn = document.getElementById('appToastClose');
            if (closeBtn) {
                closeBtn.addEventListener('click', function () {
                    document.getElementById('appToast').classList.remove('show');
                    clearTimeout(toastTimer);
                });
            }
        });

        // confirmDialog(message, {title, confirmText, danger}) -> Promise<boolean>
        window.confirmDialog = function (message, options) {
            options = options || {};
            return new Promise(function (resolve) {
                var overlay = document.getElementById('confirmOverlay');
                var confirmBtn = document.getElementById('confirmModalConfirm');
                var cancelBtn = document.getElementById('confirmModalCancel');

                document.getElementById('confirmModalTitle').textContent = options.title || 'Are you sure?';
                document.getElementById('confirmModalMessage').textContent = message || '';
                confirmBtn.textContent = options.confirmText || 'Confirm';
                confirmBtn.className = 'btn-action ' + (options.danger === false ? 'edit' : 'reject');

                function cleanup(result) {
                    overlay.classList.remove('show');
                    confirmBtn.removeEventListener('click', onConfirm);
                    cancelBtn.removeEventListener('click', onCancel);
                    overlay.removeEventListener('click', onOverlayClick);
                    resolve(result);
                }

                function onConfirm() { cleanup(true); }
                function onCancel() { cleanup(false); }
                function onOverlayClick(e) { if (e.target === overlay) cleanup(false); }

                confirmBtn.addEventListener('click', onConfirm);
                cancelBtn.addEventListener('click', onCancel);
                overlay.addEventListener('click', onOverlayClick);
                overlay.classList.add('show');
            });
        };

        // setButtonLoading(button, true, 'Saving...') / setButtonLoading(button, false)
        window.setButtonLoading = function (button, loading, loadingText) {
            if (!button) return;
            if (loading) {
                button.dataset.originalText = button.dataset.originalText || button.innerHTML;
                // Pin the current width before swapping the label, otherwise the
                // button visibly snaps to the width of "Deleting..." and back.
                if (!button.dataset.originalMinWidth) {
                    button.dataset.originalMinWidth = button.style.minWidth || '';
                    button.style.minWidth = button.offsetWidth + 'px';
                }
                button.disabled = true;
                button.innerHTML = '<span class="spinner-inline"></span>' + (loadingText || 'Please wait...');
            } else {
                button.disabled = false;
                if (button.dataset.originalText) {
                    button.innerHTML = button.dataset.originalText;
                }
                if (button.dataset.originalMinWidth !== undefined) {
                    button.style.minWidth = button.dataset.originalMinWidth;
                    delete button.dataset.originalMinWidth;
                }
            }
        };
    })();
</script>
