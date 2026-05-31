@once
    @php($bulkDownloadUrl = $bulkDownloadUrl ?? null)
    <div class="rhs-chat-modal" data-chat-modal hidden>
        <div class="rhs-chat-modal-backdrop" data-chat-modal-close></div>
        <section class="rhs-chat-modal-panel" role="dialog" aria-modal="true" aria-labelledby="rhs-chat-modal-title" tabindex="-1">
            <header class="rhs-chat-modal-head">
                <div>
                    <span data-chat-modal-kicker>Piece jointe</span>
                    <strong id="rhs-chat-modal-title" data-chat-modal-title>Pieces jointes</strong>
                </div>
                <div class="rhs-chat-modal-actions">
                    <form method="POST" action="{{ $bulkDownloadUrl ?: '#' }}" class="rhs-chat-modal-bulk-form" data-chat-modal-bulk-form {{ $bulkDownloadUrl ? '' : 'hidden' }}>
                        @csrf
                        <button type="submit" class="rhs-chat-modal-bulk-download" data-chat-modal-bulk-download disabled title="Telecharger la selection">
                            <svg viewBox="0 0 24 24" width="19" height="19" fill="none" aria-hidden="true">
                                <path d="M12 3v11m0 0 4-4m-4 4-4-4M5 19h14" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <span data-chat-modal-selected-count>0</span>
                        </button>
                    </form>
                    <a href="#" class="rhs-chat-modal-download" data-chat-modal-download download title="Telecharger ce fichier" hidden>
                        <svg viewBox="0 0 24 24" width="19" height="19" fill="none" aria-hidden="true">
                            <path d="M12 3v11m0 0 4-4m-4 4-4-4M5 19h14" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                    <button type="button" class="rhs-chat-modal-close" data-chat-modal-close aria-label="Fermer">x</button>
                </div>
            </header>
            <div class="rhs-chat-modal-body" data-chat-modal-body></div>
        </section>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const modal = document.querySelector('[data-chat-modal]');
                if (!modal) {
                    return;
                }

                const panel = modal.querySelector('.rhs-chat-modal-panel');
                const title = modal.querySelector('[data-chat-modal-title]');
                const kicker = modal.querySelector('[data-chat-modal-kicker]');
                const body = modal.querySelector('[data-chat-modal-body]');
                const download = modal.querySelector('[data-chat-modal-download]');
                const bulkForm = modal.querySelector('[data-chat-modal-bulk-form]');
                const bulkButton = modal.querySelector('[data-chat-modal-bulk-download]');
                const selectedCount = modal.querySelector('[data-chat-modal-selected-count]');
                let activeSelection = new Set();

                const escapeHtml = function (value) {
                    return String(value || '').replace(/[&<>"']/g, function (char) {
                        return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[char];
                    });
                };

                const attachmentFromNode = function (node) {
                    return {
                        id: node.getAttribute('data-attachment-id') || '',
                        url: node.getAttribute('data-attachment-url') || '#',
                        previewUrl: node.getAttribute('data-preview-url') || '',
                        downloadUrl: node.getAttribute('data-download-url') || node.getAttribute('data-attachment-url') || '#',
                        name: node.getAttribute('data-attachment-name') || 'Piece jointe',
                        type: node.getAttribute('data-attachment-type') || 'Fichier',
                        size: node.getAttribute('data-attachment-size') || 'Fichier',
                        kind: node.getAttribute('data-attachment-kind') || 'file',
                    };
                };

                const renderPreview = function (item) {
                    if (item.kind === 'image') {
                        return '<img src="' + escapeHtml(item.url) + '" alt="' + escapeHtml(item.name) + '">';
                    }

                    if (item.kind === 'video') {
                        return '<video controls preload="metadata"><source src="' + escapeHtml(item.url) + '"></video>';
                    }

                    if (item.kind === 'pdf') {
                        return '<iframe src="' + escapeHtml(item.url) + '" title="' + escapeHtml(item.name) + '"></iframe>';
                    }

                    if (item.kind === 'office' && item.previewUrl) {
                        return '<iframe class="rhs-office-frame" src="' + escapeHtml(item.previewUrl) + '" title="' + escapeHtml(item.name) + '"></iframe>';
                    }

                    return '<div class="rhs-chat-modal-file-icon">' + escapeHtml(item.type) + '</div>';
                };

                const openModal = function (items, activeIndex, mode) {
                    const wasHidden = modal.hidden;
                    const safeItems = items.filter(Boolean);
                    const active = safeItems[activeIndex] || safeItems[0];

                    if (!active) {
                        return;
                    }

                    modal.hidden = false;
                    document.body.classList.add('rhs-chat-modal-open');
                    kicker.textContent = mode === 'all' ? safeItems.length + ' piece(s) jointe(s)' : active.type + ' - ' + active.size;
                    title.textContent = mode === 'all' ? 'Toutes les pieces jointes' : active.name;
                    download.href = active.downloadUrl;
                    download.hidden = false;

                    if (wasHidden) {
                        activeSelection = new Set(safeItems.map(function (item) {
                            return item.id;
                        }).filter(Boolean));
                    }

                    const syncSelection = function () {
                        if (!bulkForm || !bulkButton || !selectedCount) {
                            return;
                        }

                        bulkForm.querySelectorAll('input[name="attachments[]"]').forEach(function (input) {
                            input.remove();
                        });

                        activeSelection.forEach(function (id) {
                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = 'attachments[]';
                            input.value = id;
                            bulkForm.appendChild(input);
                        });

                        selectedCount.textContent = activeSelection.size;
                        bulkButton.disabled = activeSelection.size === 0;
                    };

                    body.innerHTML =
                        '<div class="rhs-chat-modal-preview">' + renderPreview(active) + '</div>' +
                        (safeItems.length > 1
                            ? '<div class="rhs-chat-modal-strip">' + safeItems.map(function (item, index) {
                                return '<div class="rhs-chat-modal-thumb ' + (index === activeIndex ? 'is-active' : '') + '">' +
                                    '<label><input type="checkbox" ' + (activeSelection.has(item.id) ? 'checked' : '') + ' data-chat-modal-select="' + index + '"><span></span></label>' +
                                    '<button type="button" data-chat-modal-thumb="' + index + '">' +
                                        '<span>' + escapeHtml(item.type) + '</span><strong>' + escapeHtml(item.name) + '</strong><small>' + escapeHtml(item.size) + '</small>' +
                                    '</button>' +
                                '</div>';
                            }).join('') + '</div>'
                            : '');

                    body.querySelectorAll('[data-chat-modal-thumb]').forEach(function (button) {
                        button.addEventListener('click', function () {
                            openModal(safeItems, Number(button.getAttribute('data-chat-modal-thumb')), mode);
                        });
                    });

                    body.querySelectorAll('[data-chat-modal-select]').forEach(function (checkbox) {
                        checkbox.addEventListener('change', function () {
                            const item = safeItems[Number(checkbox.getAttribute('data-chat-modal-select'))];

                            if (!item?.id) {
                                return;
                            }

                            if (checkbox.checked) {
                                activeSelection.add(item.id);
                            } else {
                                activeSelection.delete(item.id);
                            }

                            syncSelection();
                        });
                    });

                    syncSelection();

                    window.setTimeout(function () {
                        panel.focus();
                    }, 0);
                };

                document.addEventListener('click', function (event) {
                    const opener = event.target.closest('[data-chat-media-open]');
                    const allAttachmentsOpener = event.target.closest('[data-chat-open-attachments]');

                    if (opener) {
                        const card = opener.closest('[data-chat-attachment-item]');
                        const galleryId = card?.getAttribute('data-gallery-id');
                        const galleryNodes = galleryId
                            ? Array.from(document.querySelectorAll('[data-chat-attachment-item][data-gallery-id="' + CSS.escape(galleryId) + '"]'))
                            : [card];
                        const items = galleryNodes.map(attachmentFromNode);
                        const index = Math.max(0, galleryNodes.indexOf(card));

                        event.preventDefault();
                        openModal(items, index, items.length > 1 ? 'all' : 'single');
                    }

                    if (allAttachmentsOpener) {
                        const nodes = Array.from(document.querySelectorAll('[data-chat-side-attachment]'));
                        const items = nodes.map(attachmentFromNode);
                        const openerId = allAttachmentsOpener.getAttribute('data-attachment-id');
                        const startIndex = Math.max(0, nodes.findIndex(function (node) {
                            return node.getAttribute('data-attachment-id') === openerId;
                        }));

                        event.preventDefault();
                        openModal(items, startIndex, 'all');
                    }
                });

                modal.querySelectorAll('[data-chat-modal-close]').forEach(function (close) {
                    close.addEventListener('click', function () {
                        modal.hidden = true;
                        body.innerHTML = '';
                        activeSelection = new Set();
                        document.body.classList.remove('rhs-chat-modal-open');
                    });
                });

                document.addEventListener('keydown', function (event) {
                    if (event.key === 'Escape' && !modal.hidden) {
                        modal.hidden = true;
                        body.innerHTML = '';
                        activeSelection = new Set();
                        document.body.classList.remove('rhs-chat-modal-open');
                    }
                });
            });
        </script>
    @endpush
@endonce
