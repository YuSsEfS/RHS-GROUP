@once
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                if ('scrollRestoration' in window.history) {
                    window.history.scrollRestoration = 'manual';
                }

                window.scrollTo(0, 0);

                const formatSize = (bytes) => {
                    if (!bytes) {
                        return 'Fichier';
                    }

                    if (bytes >= 1048576) {
                        return `${(bytes / 1048576).toFixed(1).replace('.', ',')} Mo`;
                    }

                    return `${Math.max(1, Math.round(bytes / 1024))} Ko`;
                };

                const syncFileInput = (input, files) => {
                    const transfer = new DataTransfer();
                    files.forEach((file) => transfer.items.add(file));
                    input.files = transfer.files;
                };

                const clipboardImageFiles = (event) => {
                    const items = Array.from(event.clipboardData?.items || []);

                    return items
                        .filter((item) => item.kind === 'file' && item.type.startsWith('image/'))
                        .map((item, index) => {
                            const file = item.getAsFile();

                            if (!file) {
                                return null;
                            }

                            const extension = (file.type.split('/')[1] || 'png').replace('jpeg', 'jpg');
                            return new File(
                                [file],
                                `screenshot-${new Date().toISOString().replace(/[:.]/g, '-')}-${index + 1}.${extension}`,
                                { type: file.type || 'image/png', lastModified: Date.now() }
                            );
                        })
                        .filter(Boolean);
                };

                document.querySelectorAll('[data-rhs-chat-composer]').forEach((composer) => {
                    const input = composer.querySelector('[data-rhs-chat-file-input]');
                    const trigger = composer.querySelector('[data-rhs-chat-file-trigger]');
                    const mention = composer.querySelector('[data-rhs-chat-mention]');
                    const textarea = composer.querySelector('textarea[name="body"]');
                    const preview = composer.querySelector('[data-rhs-chat-file-preview]');
                    const name = composer.querySelector('[data-rhs-chat-file-name]');
                    const meta = composer.querySelector('[data-rhs-chat-file-meta]');
                    const clear = composer.querySelector('[data-rhs-chat-file-clear]');
                    const priorityValue = composer.querySelector('[data-chat-priority-value]');
                    const priorityOptions = Array.from(composer.querySelectorAll('[data-chat-priority-option]'));

                    priorityOptions.forEach((button) => {
                        button.addEventListener('click', () => {
                            if (priorityValue) {
                                priorityValue.value = button.getAttribute('data-chat-priority-option') || priorityValue.value;
                            }

                            priorityOptions.forEach((option) => {
                                option.classList.toggle('is-active', option === button);
                            });
                        });
                    });

                    if (!input || !preview || !name || !meta || !clear) {
                        return;
                    }

                    if (trigger) {
                        trigger.addEventListener('click', () => input.click());
                    }

                    if (mention && textarea) {
                        mention.addEventListener('click', () => {
                            const start = textarea.selectionStart || textarea.value.length;
                            const end = textarea.selectionEnd || start;
                            const prefix = start > 0 && !/\s$/.test(textarea.value.slice(0, start)) ? ' @' : '@';
                            textarea.value = textarea.value.slice(0, start) + prefix + textarea.value.slice(end);
                            textarea.focus();
                            textarea.selectionStart = textarea.selectionEnd = start + prefix.length;
                        });
                    }

                    if (textarea) {
                        textarea.addEventListener('keydown', (event) => {
                            if (event.key !== 'Enter' || event.shiftKey) {
                                return;
                            }

                            event.preventDefault();
                            if (textarea.value.trim() !== '' || (input.files && input.files.length > 0)) {
                                composer.requestSubmit();
                            }
                        });
                    }

                    let files = [];
                    let list = composer.querySelector('[data-rhs-chat-file-list]');

                    if (!list) {
                        list = document.createElement('div');
                        list.className = 'rhs-chat-file-preview-list';
                        list.setAttribute('data-rhs-chat-file-list', '');
                        preview.insertBefore(list, clear);
                    }

                    const render = () => {
                        const totalSize = files.reduce((sum, file) => sum + (file.size || 0), 0);

                        list.innerHTML = '';

                        if (!files.length) {
                            preview.hidden = true;
                            name.textContent = '';
                            meta.textContent = '';
                            input.value = '';
                            return;
                        }

                        name.textContent = files.length > 1
                            ? `${files.length} fichiers selectionnes`
                            : files[0].name;
                        meta.textContent = `${formatSize(totalSize)} au total`;

                        files.forEach((file, index) => {
                            const extension = file.name.includes('.')
                                ? file.name.split('.').pop().toUpperCase()
                                : 'DOC';
                            const item = document.createElement('div');
                            item.className = 'rhs-chat-file-chip';

                            const icon = document.createElement('span');
                            icon.className = 'rhs-chat-file-chip-icon';
                            icon.textContent = extension.slice(0, 4);

                            const copy = document.createElement('span');
                            copy.className = 'rhs-chat-file-chip-copy';
                            copy.innerHTML = `<strong></strong><small>${formatSize(file.size)}</small>`;
                            copy.querySelector('strong').textContent = file.name;

                            const remove = document.createElement('button');
                            remove.type = 'button';
                            remove.className = 'rhs-chat-file-chip-remove';
                            remove.setAttribute('aria-label', 'Retirer ce fichier');
                            remove.textContent = '×';
                            remove.addEventListener('click', () => {
                                files.splice(index, 1);
                                syncFileInput(input, files);
                                render();
                            });

                            item.append(icon, copy, remove);
                            list.appendChild(item);
                        });

                        preview.hidden = false;
                    };

                    input.addEventListener('change', () => {
                        files = input.files ? Array.from(input.files) : [];
                        render();
                    });

                    composer.addEventListener('paste', (event) => {
                        const pastedImages = clipboardImageFiles(event);

                        if (!pastedImages.length) {
                            return;
                        }

                        event.preventDefault();
                        files = files.concat(pastedImages).slice(0, 30);
                        syncFileInput(input, files);
                        render();

                        if (textarea) {
                            textarea.focus();
                        }
                    });

                    clear.addEventListener('click', () => {
                        files = [];
                        syncFileInput(input, files);
                        render();
                    });
                });

                document.querySelectorAll('[data-conversation-create-form]').forEach((form) => {
                    const input = form.querySelector('[data-create-file-input]');
                    const textarea = form.querySelector('textarea[name="body"]');
                    const summary = form.querySelector('[data-create-file-summary]');

                    if (!input || !textarea) {
                        return;
                    }

                    const refreshSummary = () => {
                        if (!summary) {
                            return;
                        }

                        const files = Array.from(input.files || []);
                        summary.textContent = files.length
                            ? files.length + (files.length > 1 ? ' fichiers selectionnes' : ' fichier selectionne')
                            : 'Glissez vos fichiers ici';
                    };

                    form.addEventListener('paste', (event) => {
                        const pastedImages = clipboardImageFiles(event);

                        if (!pastedImages.length || !form.contains(document.activeElement)) {
                            return;
                        }

                        event.preventDefault();
                        const files = Array.from(input.files || []).concat(pastedImages).slice(0, 30);
                        syncFileInput(input, files);
                        refreshSummary();
                        textarea.focus();
                    });

                    input.addEventListener('change', refreshSummary);
                });

                document.querySelectorAll('.rhs-chat-messages').forEach((messages) => {
                    const scrollToBottom = () => {
                        messages.scrollTop = messages.scrollHeight;
                    };

                    scrollToBottom();
                    window.requestAnimationFrame(() => {
                        scrollToBottom();
                        messages.classList.remove('is-preparing-scroll');
                    });
                    window.setTimeout(() => {
                        scrollToBottom();
                        messages.classList.remove('is-preparing-scroll');
                    }, 120);
                    messages.querySelectorAll('img, video').forEach((media) => {
                        media.addEventListener('load', scrollToBottom, { once: true });
                        media.addEventListener('loadedmetadata', scrollToBottom, { once: true });
                    });
                });

                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const conversationQueues = new Map();

                const isNearBottom = (list) => {
                    return list.scrollHeight - list.scrollTop - list.clientHeight < 160;
                };

                const updateMessageList = (list, payload, forceScroll = false) => {
                    if (!payload) {
                        return;
                    }

                    if (typeof payload.unread_conversations !== 'undefined') {
                        if (window.RhsNotificationWorker && typeof window.RhsNotificationWorker.apply === 'function') {
                            window.RhsNotificationWorker.apply({
                                items: {
                                    conversations: Number(payload.unread_conversations || 0)
                                }
                            });
                        }

                        document.querySelectorAll('[data-conversation-notification-badge]').forEach((badge) => {
                            const unread = Number(payload.unread_conversations || 0);
                            badge.textContent = String(unread);
                            badge.hidden = unread <= 0;
                            badge.style.display = unread > 0 ? 'inline-flex' : 'none';
                        });

                        document.querySelectorAll('[data-chat-unread-total]').forEach((badge) => {
                            const unread = Number(payload.unread_conversations || 0);
                            badge.textContent = String(unread);
                            badge.hidden = unread <= 0;
                            badge.style.display = unread > 0 ? 'inline-flex' : 'none';
                        });
                    }

                    const shouldScroll = forceScroll || isNearBottom(list);

                    if (typeof payload.append_html === 'string') {
                        list.insertAdjacentHTML('beforeend', payload.append_html);
                    } else if (typeof payload.html === 'string') {
                        list.innerHTML = payload.html;
                    } else {
                        return;
                    }

                    list.dataset.chatLastMessageId = payload.last_message_id || '';
                    list.dataset.chatMessageCount = String(payload.messages_count || 0);
                    list.dataset.chatMessagesVersion = payload.messages_version || '';

                    if (shouldScroll) {
                        const scrollToBottom = () => {
                            list.scrollTop = list.scrollHeight;
                        };

                        scrollToBottom();
                        window.requestAnimationFrame(scrollToBottom);
                    }
                };

                const fetchJson = async (url, options = {}) => {
                    const response = await fetch(url, {
                        credentials: 'same-origin',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
                            ...(options.headers || {}),
                        },
                        ...options,
                    });

                    const data = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        throw new Error(data.message || 'Action impossible pour le moment.');
                    }

                    return data;
                };

                const enqueueConversationTask = (conversationId, task) => {
                    const key = String(conversationId || 'global');
                    const previous = conversationQueues.get(key) || Promise.resolve();
                    const next = previous.catch(() => undefined).then(task);
                    conversationQueues.set(key, next.finally(() => {
                        if (conversationQueues.get(key) === next) {
                            conversationQueues.delete(key);
                        }
                    }));

                    return next;
                };

                document.querySelectorAll('[data-rhs-chat-composer]').forEach((composer) => {
                    const list = document.querySelector('[data-message-list]');
                    const textarea = composer.querySelector('textarea[name="body"]');
                    const fileInput = composer.querySelector('[data-rhs-chat-file-input]');
                    const submit = composer.querySelector('[type="submit"]');

                    if (!list) {
                        return;
                    }

                    composer.addEventListener('submit', (event) => {
                        event.preventDefault();

                        const hasText = textarea && textarea.value.trim() !== '';
                        const hasFiles = fileInput && fileInput.files && fileInput.files.length > 0;

                        if (!hasText && !hasFiles) {
                            return;
                        }

                        const formData = new FormData(composer);
                        const conversationId = list.dataset.chatConversationId;
                        const previousBody = textarea ? textarea.value : '';
                        const previousFiles = fileInput ? fileInput.files : null;

                        if (submit) {
                            submit.disabled = true;
                        }

                        enqueueConversationTask(conversationId, async () => {
                            const payload = await fetchJson(composer.action, {
                                method: 'POST',
                                body: formData,
                            });

                            if (textarea) {
                                textarea.value = '';
                            }

                            if (fileInput) {
                                fileInput.value = '';
                                fileInput.dispatchEvent(new Event('change', { bubbles: true }));
                            }

                            updateMessageList(list, payload, true);
                        }).catch((error) => {
                            if (textarea && !textarea.value.trim()) {
                                textarea.value = previousBody;
                            }

                            if (previousFiles && fileInput && previousFiles.length) {
                                const transfer = new DataTransfer();
                                Array.from(previousFiles).forEach((file) => transfer.items.add(file));
                                fileInput.files = transfer.files;
                                fileInput.dispatchEvent(new Event('change', { bubbles: true }));
                            }

                            window.alert(error.message);
                        }).finally(() => {
                            if (submit) {
                                submit.disabled = false;
                            }

                            if (textarea) {
                                textarea.focus();
                            }
                        });
                    });
                });

                document.addEventListener('submit', (event) => {
                    const form = event.target.closest('.rhs-chat-delete-form, .rhs-chat-media-delete');

                    if (!form) {
                        return;
                    }

                    const list = document.querySelector('[data-message-list]');

                    if (!list) {
                        return;
                    }

                    event.preventDefault();

                    const conversationId = list.dataset.chatConversationId;
                    enqueueConversationTask(conversationId, async () => {
                        const payload = await fetchJson(form.action, {
                            method: 'POST',
                            body: new FormData(form),
                        });

                        updateMessageList(list, payload, false);
                    }).catch((error) => {
                        window.alert(error.message);
                    });
                });

                document.querySelectorAll('[data-message-list][data-chat-refresh-url]').forEach((list) => {
                    let inFlight = false;

                    const refresh = async () => {
                        if (inFlight || document.hidden) {
                            return;
                        }

                        inFlight = true;

                        try {
                            const url = new URL(list.dataset.chatRefreshUrl, window.location.origin);
                            url.searchParams.set('last_message_id', list.dataset.chatLastMessageId || '');
                            url.searchParams.set('messages_count', list.dataset.chatMessageCount || '0');
                            url.searchParams.set('messages_version', list.dataset.chatMessagesVersion || '');

                            const payload = await fetchJson(url.toString(), { method: 'GET' });
                            const nextLastId = String(payload.last_message_id || '');
                            const nextCount = String(payload.messages_count || 0);
                            const nextVersion = String(payload.messages_version || '');

                            if (
                                nextLastId !== String(list.dataset.chatLastMessageId || '')
                                || nextCount !== String(list.dataset.chatMessageCount || '0')
                                || nextVersion !== String(list.dataset.chatMessagesVersion || '')
                            ) {
                                updateMessageList(list, payload, false);
                            }
                        } catch (error) {
                            // Keep polling quiet during temporary network/session interruptions.
                        } finally {
                            inFlight = false;
                        }
                    };

                    window.setInterval(refresh, 1800);
                    document.addEventListener('visibilitychange', () => {
                        if (!document.hidden) {
                            refresh();
                        }
                    });
                });
            });
        </script>
    @endpush
@endonce
