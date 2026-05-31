@once
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const endpoint = @json($notificationEndpoint ?? null);

            if (!endpoint) {
                return;
            }

            let inFlight = false;

            const setBadge = function (badge, count) {
                const unread = Number(count || 0);
                badge.textContent = String(unread);
                badge.hidden = unread <= 0;
                badge.style.display = unread > 0 ? 'inline-flex' : 'none';
            };

            const applyNotifications = function (payload) {
                const unreadConversations = Number(payload?.items?.conversations || 0);

                document.querySelectorAll('[data-conversation-notification-badge]').forEach(function (badge) {
                    setBadge(badge, unreadConversations);
                });

                document.querySelectorAll('[data-chat-unread-total]').forEach(function (badge) {
                    setBadge(badge, unreadConversations);
                });
            };

            const refreshNotifications = async function () {
                if (inFlight || document.hidden) {
                    return;
                }

                inFlight = true;

                try {
                    const response = await fetch(endpoint, {
                        credentials: 'same-origin',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (!response.ok) {
                        throw new Error('Notifications indisponibles.');
                    }

                    applyNotifications(await response.json());
                } catch (error) {
                    // Keep the worker quiet during short network/session interruptions.
                } finally {
                    inFlight = false;
                }
            };

            window.RhsNotificationWorker = {
                refresh: refreshNotifications,
                apply: applyNotifications
            };

            refreshNotifications();
            window.setInterval(refreshNotifications, 2500);
            document.addEventListener('visibilitychange', function () {
                if (!document.hidden) {
                    refreshNotifications();
                }
            });
        });
    </script>
@endonce
