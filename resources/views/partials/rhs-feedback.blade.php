<div class="rhs-feedback-root" id="rhs-feedback-root" aria-live="polite" aria-atomic="true">
    <div class="rhs-toast-stack" id="rhs-toast-stack"></div>
    <div class="rhs-confirm-backdrop" id="rhs-confirm-backdrop" hidden>
        <div class="rhs-confirm-card" id="rhs-confirm-card" role="dialog" aria-modal="true" aria-labelledby="rhs-confirm-title" aria-describedby="rhs-confirm-message">
            <div class="rhs-confirm-icon" id="rhs-confirm-icon" aria-hidden="true">!</div>
            <div class="rhs-confirm-copy">
                <div class="rhs-confirm-title" id="rhs-confirm-title">Confirmation</div>
                <div class="rhs-confirm-message" id="rhs-confirm-message"></div>
            </div>
            <div class="rhs-confirm-actions">
                <button type="button" class="rhs-confirm-cancel" id="rhs-confirm-cancel">Annuler</button>
                <button type="button" class="rhs-confirm-ok" id="rhs-confirm-ok">Confirmer</button>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        if (window.__rhsFeedbackInstalled) {
            return;
        }

        window.__rhsFeedbackInstalled = true;

        const toastStack = document.getElementById('rhs-toast-stack');
        const confirmBackdrop = document.getElementById('rhs-confirm-backdrop');
        const confirmCard = document.getElementById('rhs-confirm-card');
        const confirmIcon = document.getElementById('rhs-confirm-icon');
        const confirmTitle = document.getElementById('rhs-confirm-title');
        const confirmMessage = document.getElementById('rhs-confirm-message');
        const confirmCancel = document.getElementById('rhs-confirm-cancel');
        const confirmOk = document.getElementById('rhs-confirm-ok');

        const iconMap = {
            info: 'i',
            success: '&#10003;',
            error: '!',
            warning: '!'
        };

        window.rhsToast = function (message, type) {
            if (!toastStack || !message) {
                return;
            }

            const variant = type || 'info';
            const toast = document.createElement('div');
            toast.className = 'rhs-toast rhs-toast-' + variant;
            toast.innerHTML = '' +
                '<div class="rhs-toast-icon">' + (iconMap[variant] || iconMap.info) + '</div>' +
                '<div class="rhs-toast-body">' + String(message) + '</div>' +
                '<button type="button" class="rhs-toast-close" aria-label="Fermer">&times;</button>';

            const close = function () {
                toast.classList.remove('is-visible');
                window.setTimeout(function () {
                    toast.remove();
                }, 180);
            };

            toast.querySelector('.rhs-toast-close').addEventListener('click', close);

            toastStack.appendChild(toast);

            window.requestAnimationFrame(function () {
                toast.classList.add('is-visible');
            });

            window.setTimeout(close, 3400);
        };

        window.alert = function (message) {
            window.rhsToast(String(message || ''), 'info');
        };

        function extractConfirmMessage(value) {
            const match = String(value || '').match(/confirm\((['"])(.*?)\1\)/);
            return match ? match[2] : '';
        }

        function isDangerMessage(message) {
            return /supprimer|delete|effacer|archive|ecrasera|écrasera/i.test(String(message || ''));
        }

        function clearInlineConfirms() {
            document.querySelectorAll('[onsubmit*="confirm("]').forEach(function (form) {
                const message = form.dataset.rhsConfirm || extractConfirmMessage(form.getAttribute('onsubmit'));
                if (message) {
                    form.dataset.rhsConfirm = message;
                }
                form.removeAttribute('onsubmit');
                form.onsubmit = null;
            });

            document.querySelectorAll('[onclick*="confirm("]').forEach(function (element) {
                const message = element.dataset.rhsConfirm || extractConfirmMessage(element.getAttribute('onclick'));
                if (message) {
                    element.dataset.rhsConfirm = message;
                }
                element.removeAttribute('onclick');
                element.onclick = null;
            });

            document.querySelectorAll('form').forEach(function (form) {
                const methodInput = form.querySelector('input[name="_method"]');
                const method = methodInput ? String(methodInput.value || '').toUpperCase() : '';
                if (!form.dataset.rhsConfirm && method === 'DELETE') {
                    form.dataset.rhsConfirm = 'Confirmer la suppression ?';
                }
            });
        }

        window.rhsConfirm = function (message, options) {
            options = options || {};

            if (!confirmBackdrop || !confirmCard || !confirmMessage || !confirmOk || !confirmCancel) {
                return Promise.resolve(true);
            }

            const variant = options.variant || (isDangerMessage(message) ? 'danger' : 'warning');
            const previousFocus = document.activeElement;

            confirmCard.className = 'rhs-confirm-card rhs-confirm-' + variant;
            confirmTitle.textContent = options.title || (variant === 'danger' ? 'Action sensible' : 'Confirmation');
            confirmMessage.textContent = String(message || 'Confirmer cette action ?');
            confirmOk.textContent = options.okText || (variant === 'danger' ? 'Oui, confirmer' : 'Confirmer');
            confirmCancel.textContent = options.cancelText || 'Annuler';
            confirmIcon.textContent = variant === 'success' ? '✓' : '!';
            confirmBackdrop.hidden = false;

            return new Promise(function (resolve) {
                let settled = false;

                function close(value) {
                    if (settled) {
                        return;
                    }

                    settled = true;
                    confirmBackdrop.classList.remove('is-visible');
                    document.removeEventListener('keydown', onKeydown);
                    confirmBackdrop.removeEventListener('click', onBackdrop);
                    confirmOk.removeEventListener('click', onOk);
                    confirmCancel.removeEventListener('click', onCancel);

                    window.setTimeout(function () {
                        confirmBackdrop.hidden = true;
                        if (previousFocus && typeof previousFocus.focus === 'function') {
                            previousFocus.focus({ preventScroll: true });
                        }
                        resolve(value);
                    }, 180);
                }

                function onOk() {
                    close(true);
                }

                function onCancel() {
                    close(false);
                }

                function onBackdrop(event) {
                    if (event.target === confirmBackdrop) {
                        close(false);
                    }
                }

                function onKeydown(event) {
                    if (event.key === 'Escape') {
                        event.preventDefault();
                        close(false);
                    }
                }

                confirmOk.addEventListener('click', onOk);
                confirmCancel.addEventListener('click', onCancel);
                confirmBackdrop.addEventListener('click', onBackdrop);
                document.addEventListener('keydown', onKeydown);

                window.requestAnimationFrame(function () {
                    confirmBackdrop.classList.add('is-visible');
                    confirmOk.focus({ preventScroll: true });
                });
            });
        };

        clearInlineConfirms();

        document.addEventListener('submit', function (event) {
            const form = event.target;
            if (form && form.getAttribute && !form.dataset.rhsConfirm) {
                const inlineMessage = extractConfirmMessage(form.getAttribute('onsubmit'));
                if (inlineMessage) {
                    form.dataset.rhsConfirm = inlineMessage;
                    form.removeAttribute('onsubmit');
                    form.onsubmit = null;
                }
            }

            if (!form || !form.dataset || !form.dataset.rhsConfirm || form.dataset.rhsConfirmed === '1') {
                return;
            }

            event.preventDefault();
            event.stopImmediatePropagation();

            const submitter = event.submitter || null;
            const message = form.dataset.rhsConfirm;

            window.rhsConfirm(message).then(function (confirmed) {
                if (!confirmed) {
                    return;
                }

                form.dataset.rhsConfirmed = '1';
                if (typeof form.requestSubmit === 'function') {
                    if (submitter) {
                        form.requestSubmit(submitter);
                    } else {
                        form.requestSubmit();
                    }
                } else {
                    form.submit();
                }
            });
        }, true);

        document.addEventListener('click', function (event) {
            const trigger = event.target.closest('[data-rhs-confirm], [onclick*="confirm("]');
            if (!trigger || trigger.tagName === 'FORM' || trigger.dataset.rhsConfirmed === '1') {
                return;
            }

            if (!trigger.dataset.rhsConfirm) {
                const inlineMessage = extractConfirmMessage(trigger.getAttribute('onclick'));
                if (inlineMessage) {
                    trigger.dataset.rhsConfirm = inlineMessage;
                    trigger.removeAttribute('onclick');
                    trigger.onclick = null;
                }
            }

            if (!trigger.dataset.rhsConfirm) {
                return;
            }

            const form = trigger.form || null;

            event.preventDefault();
            event.stopImmediatePropagation();

            window.rhsConfirm(trigger.dataset.rhsConfirm).then(function (confirmed) {
                if (!confirmed) {
                    return;
                }

                trigger.dataset.rhsConfirmed = '1';

                if (form) {
                    form.dataset.rhsConfirmed = '1';
                    if (typeof form.requestSubmit === 'function') {
                        form.requestSubmit(trigger);
                    } else {
                        form.submit();
                    }
                    return;
                }

                if (trigger.href) {
                    window.location.href = trigger.href;
                    return;
                }

                trigger.click();
            });
        }, true);
    })();
</script>
