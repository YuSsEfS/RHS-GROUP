<script>
document.addEventListener('DOMContentLoaded', function () {
    function normalizeTag(value) {
        return String(value || '').trim().replace(/\s+/g, ' ');
    }

    function normalizeForSearch(value) {
        return normalizeTag(value).normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
    }

    function isNoiseTag(value) {
        return ['', 'et', '&', 'and', 'ou', 'or'].includes(normalizeForSearch(value));
    }

    function escapeHtml(value) {
        return String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    document.querySelectorAll('.js-tag-field').forEach(function (field) {
        if (field.dataset.rhsTagReady === '1') {
            return;
        }

        field.dataset.rhsTagReady = '1';

        const hiddenInput = field.querySelector('input[type="hidden"]');
        const tagBox = field.querySelector('.tag-input-wrap');
        const input = field.querySelector('.tag-input');
        const suggestions = field.querySelector('.tag-suggestions');
        const suggestionList = JSON.parse(field.dataset.suggestions || '[]');

        if (!hiddenInput || !tagBox || !input || !suggestions) {
            return;
        }

        let tags = (hiddenInput.value || '')
            .split(/[,;|\/]+/)
            .map(normalizeTag)
            .filter(Boolean)
            .filter((value, index, array) => array.findIndex((item) => normalizeForSearch(item) === normalizeForSearch(value)) === index);

        function syncHiddenInput() {
            hiddenInput.value = tags.join(', ');
        }

        function closestSuggestion(value) {
            const query = normalizeForSearch(value);

            return suggestionList.find((item) => normalizeForSearch(item) === query)
                || suggestionList.find((item) => normalizeForSearch(item).startsWith(query))
                || suggestionList.find((item) => normalizeForSearch(item).includes(query))
                || value;
        }

        function renderTags() {
            tagBox.querySelectorAll('.tag-chip').forEach((el) => el.remove());

            tags.forEach((tag, index) => {
                const chip = document.createElement('span');
                chip.className = 'tag-chip';
                chip.innerHTML = `<span>${escapeHtml(tag)}</span><button type="button" class="tag-remove" data-index="${index}">&times;</button>`;
                tagBox.insertBefore(chip, input);
            });

            tagBox.querySelectorAll('.tag-remove').forEach((button) => {
                button.addEventListener('click', function () {
                    tags.splice(Number(this.dataset.index), 1);
                    renderTags();
                });
            });

            syncHiddenInput();
        }

        function addTag(value) {
            value = normalizeTag(value);

            if (!value || isNoiseTag(value)) {
                return;
            }

            if (!tags.some((tag) => normalizeForSearch(tag) === normalizeForSearch(value))) {
                tags.push(value);
                renderTags();
            }

            input.value = '';
            suggestions.style.display = 'none';
        }

        function showSuggestions(value) {
            const query = normalizeForSearch(value);

            if (!query) {
                suggestions.style.display = 'none';
                return;
            }

            const results = suggestionList
                .filter((item) => normalizeForSearch(item).includes(query))
                .filter((item) => !tags.some((tag) => normalizeForSearch(tag) === normalizeForSearch(item)))
                .slice(0, 8);

            if (!results.length) {
                suggestions.style.display = 'none';
                return;
            }

            suggestions.innerHTML = results.map((item) => `<div class="tag-suggestion" data-value="${escapeHtml(item)}">${escapeHtml(item)}</div>`).join('');
            suggestions.style.display = 'block';
            suggestions.querySelectorAll('.tag-suggestion').forEach((item) => {
                item.addEventListener('mousedown', function (event) {
                    event.preventDefault();
                    addTag(this.dataset.value);
                });
            });
        }

        input.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ',' || event.key === ';') {
                event.preventDefault();
                addTag(closestSuggestion(input.value));
            }

            if (event.key === 'Backspace' && input.value === '' && tags.length) {
                tags.pop();
                renderTags();
            }
        });

        input.addEventListener('input', function () {
            showSuggestions(input.value);
        });

        input.addEventListener('blur', function () {
            setTimeout(function () {
                if (input.value.trim() !== '') {
                    addTag(closestSuggestion(input.value));
                }
                suggestions.style.display = 'none';
            }, 150);
        });

        tagBox.addEventListener('click', function () {
            input.focus();
        });

        renderTags();
    });
});
</script>
