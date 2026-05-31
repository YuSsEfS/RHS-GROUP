@once
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const normalize = function (value) {
                    return String(value || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim();
                };

                document.querySelectorAll('[data-message-search-panel][hidden]').forEach(function (panel) {
                    panel.style.display = 'none';
                });

                const listSearch = document.querySelector('[data-chat-list-search]');
                const listItems = Array.from(document.querySelectorAll('[data-chat-item]'));
                const listFilters = Array.from(document.querySelectorAll('[data-chat-list-filter]'));
                let activeListFilter = 'all';

                const filterConversationList = function () {
                    const term = normalize(listSearch ? listSearch.value : '');

                    listItems.forEach(function (item) {
                        const haystack = normalize(item.getAttribute('data-chat-search'));
                        const matchesSearch = !term || haystack.includes(term);
                        const matchesFilter = activeListFilter === 'all'
                            || (activeListFilter === 'unread' && item.getAttribute('data-chat-unread') === '1')
                            || (activeListFilter === 'group' && item.getAttribute('data-chat-group') === '1');

                        const visible = matchesSearch && matchesFilter;
                        item.hidden = !visible;
                        if (visible) {
                            item.style.removeProperty('display');
                        } else {
                            item.style.setProperty('display', 'none', 'important');
                        }
                    });
                };

                if (listSearch) {
                    listSearch.addEventListener('input', filterConversationList);
                }

                listFilters.forEach(function (button) {
                    button.addEventListener('click', function () {
                        activeListFilter = button.getAttribute('data-chat-list-filter') || 'all';
                        listFilters.forEach(function (item) {
                            item.classList.toggle('is-active', item === button);
                        });
                        filterConversationList();
                    });
                });

                filterConversationList();

                const messageToggle = document.querySelector('[data-message-search-toggle]');
                const messagePanel = document.querySelector('[data-message-search-panel]');
                const messageInput = document.querySelector('[data-message-search-input]');
                const messageCount = document.querySelector('[data-message-search-count]');
                const messages = Array.from(document.querySelectorAll('[data-message-list] .rhs-chat-row'));

                const filterMessages = function () {
                    const term = normalize(messageInput ? messageInput.value : '');
                    let visible = 0;

                    messages.forEach(function (row) {
                        const matches = !term || normalize(row.textContent).includes(term);
                        row.hidden = !matches;
                        if (matches) {
                            row.style.removeProperty('display');
                        } else {
                            row.style.setProperty('display', 'none', 'important');
                        }
                        row.classList.toggle('is-search-match', Boolean(term && matches));
                        if (matches) {
                            visible++;
                        }
                    });

                    if (messageCount) {
                        messageCount.textContent = visible + (visible > 1 ? ' resultats' : ' resultat');
                    }
                };

                if (messageToggle && messagePanel && messageInput) {
                    messageToggle.addEventListener('click', function () {
                        messagePanel.hidden = !messagePanel.hidden;
                        if (!messagePanel.hidden) {
                            messagePanel.style.display = '';
                            messageInput.focus();
                            filterMessages();
                        } else {
                            messagePanel.style.display = 'none';
                            messageInput.value = '';
                            filterMessages();
                        }
                    });

                    messageInput.addEventListener('input', filterMessages);
                    messageInput.addEventListener('keydown', function (event) {
                        if (event.key === 'Escape') {
                            messagePanel.hidden = true;
                            messagePanel.style.display = 'none';
                            messageInput.value = '';
                            filterMessages();
                        }
                    });
                }
            });
        </script>
    @endpush
@endonce
