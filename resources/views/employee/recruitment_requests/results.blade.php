@extends('dashboard.layouts.app')

@section('title', 'Resultats du matching')
@section('brand', 'RHS Employe')
@section('brand_sub', 'Suivi recrutement')
@section('page_title', 'Resultats du matching')
@section('page_copy', 'Consultez les CV compatibles avec votre demande assignee et ouvrez les profils autorises depuis cet espace.')

@section('sidebar')
    @include('employee._sidebar')
@endsection

@section('top_badge')
    <a href="{{ route('employee.recruitment-requests.show', $recruitmentRequest) }}" class="admin-btn admin-btn-ghost portal-btn-auto">Retour a la demande</a>
@endsection

@section('content')
@php
    $breakdownLabels = [
        'title_fit' => 'Adequation du poste',
        'education_fit' => 'Formation',
        'experience_fit' => 'Experience',
        'age_fit' => 'Age',
        'skills_fit' => 'Competences',
        'language_fit' => 'Langues',
        'location_fit' => 'Localisation',
        'availability_fit' => 'Disponibilite',
        'overall_consistency' => 'Cohesion globale',
    ];

    $matchingStatus = $recruitmentRequest->resolveMatchingStatus() ?? \App\Models\RecruitmentRequest::MATCHING_STATUS_PENDING;
    $matchingStatusLabels = \App\Models\RecruitmentRequest::availableMatchingStatuses();
    $jobStatusLabels = \App\Models\RecruitmentRequest::availableJobStatuses();
    $currentOffer = request('offer', $offerId ?? ($recruitmentRequest->job_offer_id ?: 'all'));
    $currentFolder = request('folder', $folderId ?? 'all');
    $currentSearch = $search ?? request('q', '');
    $refreshUrl = route('employee.recruitment-requests.results', ['recruitmentRequest' => $recruitmentRequest->id, 'offer' => $currentOffer, 'folder' => $currentFolder, 'q' => $currentSearch]);
    $matchSuggestUrl = route('employee.recruitment-requests.results.suggest', ['recruitmentRequest' => $recruitmentRequest->id, 'folder' => $currentFolder]);
    $shouldAutoRefresh = in_array($matchingStatus, [
        \App\Models\RecruitmentRequest::MATCHING_STATUS_PENDING,
        \App\Models\RecruitmentRequest::MATCHING_STATUS_PROCESSING,
    ], true);
@endphp

<section class="portal-card portal-card--spaced">
    <div class="portal-toolbar">
        <div>
            <h3 class="portal-title-tight">Liste des CV compatibles</h3>
            <p class="portal-copy portal-copy-tight">Resultats du matching pour {{ $recruitmentRequest->position_title ?: 'la demande en cours' }}.</p>
        </div>

        <form method="GET" class="portal-form-grid portal-form-grid-compact" action="{{ route('employee.recruitment-requests.results', $recruitmentRequest) }}" autocomplete="off">
            <div class="portal-search-field">
                <label for="employee-match-results-search">Recherche CV</label>
                <div class="table-search match-results-search">
                    <span class="table-search-ico" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M21 21l-4.3-4.3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            <path d="M11 19a8 8 0 1 1 0-16 8 8 0 0 1 0 16Z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </span>
                    <input
                        id="employee-match-results-search"
                        type="search"
                        name="q"
                        value="{{ $currentSearch }}"
                        placeholder="Nom, email, telephone, poste..."
                        autocomplete="off"
                        spellcheck="false"
                        data-results-url="{{ route('employee.recruitment-requests.results', $recruitmentRequest) }}"
                        data-suggest-url="{{ $matchSuggestUrl }}"
                    >
                    <div id="employee-match-results-suggest" class="search-suggest match-results-suggest" hidden></div>
                </div>
            </div>

            <div>
                <label for="offer">Offre liee</label>
                <select name="offer" id="offer">
                    <option value="all" {{ (string) $currentOffer === 'all' ? 'selected' : '' }}>Toutes les offres</option>
                    @foreach(($offers ?? collect()) as $offer)
                        <option value="{{ $offer->id }}" {{ (string) $currentOffer === (string) $offer->id ? 'selected' : '' }}>
                            {{ $offer->title }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="folder">Dossier CV</label>
                <select name="folder" id="folder">
                    <option value="all" {{ (string) $currentFolder === 'all' ? 'selected' : '' }}>Tous les dossiers</option>
                    @foreach(($folders ?? collect()) as $folder)
                        <option value="{{ $folder->id }}" {{ (string) $currentFolder === (string) $folder->id ? 'selected' : '' }}>
                            {{ $folder->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="portal-form-actions">
                <button class="admin-btn admin-btn-primary portal-btn-auto" type="submit">Filtrer</button>
                <a class="admin-btn admin-btn-ghost portal-btn-auto" href="{{ route('employee.recruitment-requests.results', $recruitmentRequest) }}">Reinitialiser</a>
            </div>
        </form>
    </div>

    <div class="portal-mini-list">
        <div class="portal-mini-item"><span class="portal-status is-muted">CV trouves</span><div class="portal-mini-copy" id="employee-matching-results-found">{{ $matchesTotal ?? (method_exists($matches, 'total') ? $matches->total() : count($matches)) }}</div></div>
        <div class="portal-mini-item"><span class="portal-status is-info">Offre</span><div class="portal-mini-copy">{{ $recruitmentRequest->jobOffer?->title ?? '-' }}</div></div>
        <div class="portal-mini-item"><span class="portal-status is-warning">Dossier</span><div class="portal-mini-copy">{{ (string) $currentFolder === 'all' ? 'Tous' : (optional(($folders ?? collect())->firstWhere('id', (int) $currentFolder))->name ?? '-') }}</div></div>
        <div class="portal-mini-item"><span class="portal-status is-muted">Traitement</span><div class="portal-mini-copy">{{ $matchingStatusLabels[$matchingStatus] ?? ucfirst($matchingStatus) }}</div></div>
        <div class="portal-mini-item"><span class="portal-status is-info">Selectionnes</span><div class="portal-mini-copy" id="employee-selected-count" data-selected-total="{{ $selectedMatchesCount ?? 0 }}">{{ $selectedMatchesCount ?? 0 }}</div></div>
        @if($recruitmentRequest->matching_job_status)
            <div class="portal-mini-item"><span class="portal-status is-info">Job queue</span><div class="portal-mini-copy">{{ $jobStatusLabels[$recruitmentRequest->matching_job_status] ?? $recruitmentRequest->matching_job_status }}</div></div>
        @endif
    </div>

    <div class="portal-form-actions" style="justify-content:flex-start;">
        <a class="admin-btn admin-btn-ghost portal-btn-auto" href="{{ $refreshUrl }}">Rafraichir</a>
    </div>

    @if($matchingStatus === \App\Models\RecruitmentRequest::MATCHING_STATUS_PENDING || $matchingStatus === \App\Models\RecruitmentRequest::MATCHING_STATUS_PROCESSING)
        <div class="admin-alert" style="margin-bottom:14px;">
            Le matching est en cours. Les resultats apparaitront ici automatiquement.
        </div>
    @elseif($matchingStatus === \App\Models\RecruitmentRequest::MATCHING_STATUS_FAILED)
        <div class="admin-alert admin-alert-danger" style="margin-bottom:14px;">
            Le matching a echoue. {{ $recruitmentRequest->resolveMatchingError() ?: 'Veuillez prevenir l administration pour relancer le traitement.' }}
        </div>
    @endif

    @if($matchingStatus === \App\Models\RecruitmentRequest::MATCHING_STATUS_COMPLETED)
        <form method="POST" action="{{ route('employee.recruitment-requests.download-selected', $recruitmentRequest) }}" id="employee-download-selected-form">
            @csrf

            <div class="rhs-autoselect-card">
                <div>
                    <strong>Selection rapide</strong>
                    <span>Selectionnez automatiquement les premiers profils visibles.</span>
                </div>
                <div class="rhs-autoselect-controls">
                    <select id="employee-match-auto-select-preset" class="form-select select-theme">
                        <option value="">Nombre de CV</option>
                        <option value="10">10 premiers</option>
                        <option value="20">20 premiers</option>
                        <option value="40">40 premiers</option>
                        <option value="50">50 premiers</option>
                    </select>
                    <input id="employee-match-auto-select-custom" class="form-input" type="number" min="1" max="500" placeholder="Nombre libre">
                    <button class="admin-btn admin-btn-ghost portal-btn-auto" type="button" id="employee-match-auto-select-apply">Selectionner</button>
                    <button class="admin-btn admin-btn-ghost portal-btn-auto" type="button" id="employee-match-auto-select-clear">Vider</button>
                </div>
            </div>

            <div class="portal-form-actions" style="justify-content:flex-end;margin-bottom:14px;">
                <button class="admin-btn admin-btn-primary portal-btn-auto" type="submit">
                    Telecharger les CV selectionnes
                </button>
            </div>

        <div class="table-wrap">
            <table class="table table-safe">
                <thead>
                    <tr>
                        <th>Candidat</th>
                        <th>Email</th>
                        <th>Dossier</th>
                        <th>Score final</th>
                        <th>Resume</th>
                        <th>Actions</th>
                        <th>Selection</th>
                    </tr>
                </thead>
                <tbody id="employee-match-results-tbody">
                    @forelse($matches as $match)
                        @php
                            $fullBreakdown = is_array($match->score_breakdown ?? null)
                                ? $match->score_breakdown
                                : (json_decode($match->score_breakdown ?? '[]', true) ?: []);

                            $meta = is_array($fullBreakdown['_meta'] ?? null) ? $fullBreakdown['_meta'] : [];
                            unset($fullBreakdown['_meta']);

                            $localScore = isset($meta['local_score']) ? (float) $meta['local_score'] : null;
                            $aiScore = array_key_exists('ai_score', $meta) && $meta['ai_score'] !== null ? (float) $meta['ai_score'] : null;
                            $finalScore = isset($meta['final_score']) ? (float) $meta['final_score'] : (float) $match->score;
                            $aiAvailable = (bool) ($meta['ai_available'] ?? false);
                            $lastAnalysis = $meta['last_analysis'] ?? null;
                        @endphp
                        <tr>
                            <td>
                                <div class="match-candidate">
                                    <strong>{{ $match->cv->candidate_name ?? 'Candidat inconnu' }}</strong>
                                    <small>{{ $match->cv->phone ?? 'Telephone non disponible' }}</small>
                                </div>
                            </td>
                            <td><span class="pill pill-neutral">{{ $match->cv->email ?? '-' }}</span></td>
                            <td><span class="pill pill-neutral">{{ $match->cv->folder?->name ?? '-' }}</span></td>
                            <td><span class="match-score">{{ number_format($finalScore, 0) }}%</span></td>
                            <td>
                                <div class="match-summary">{{ $match->summary ?: 'Resume non disponible.' }}</div>

                                <div class="match-status-row">
                                    @if($aiAvailable)
                                        <span class="match-status match-status-ai">Analyse IA validee : {{ number_format($aiScore ?? 0, 0) }}%</span>
                                    @elseif(!is_null($aiScore))
                                        <span class="match-status match-status-local">Analyse complementaire : {{ number_format($aiScore, 0) }}%</span>
                                    @else
                                        <span class="match-status match-status-local">Score local</span>
                                    @endif

                                    @if(!is_null($localScore))
                                        <span class="match-status match-status-neutral">Local : {{ number_format($localScore, 0) }}%</span>
                                    @endif

                                    <span class="match-status match-status-neutral">Final : {{ number_format($finalScore, 0) }}%</span>

                                    @if($match->ai_analysis_status)
                                        <span class="match-status match-status-neutral">
                                            IA : {{ $jobStatusLabels[$match->ai_analysis_status] ?? $match->ai_analysis_status }}
                                        </span>
                                    @endif

                                    @if($lastAnalysis)
                                        <span class="match-status match-status-neutral">Analyse : {{ $lastAnalysis }}</span>
                                    @endif
                                </div>

                                @if(!empty($fullBreakdown))
                                    <div class="match-breakdown">
                                        @foreach($fullBreakdown as $key => $value)
                                            <span class="match-tag">
                                                {{ $breakdownLabels[$key] ?? ucfirst(str_replace('_', ' ', $key)) }} :
                                                {{ rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.') }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td>
                                <a class="admin-btn admin-btn-ghost portal-btn-auto" href="{{ route('employee.recruitment-requests.matches.open', ['recruitmentRequest' => $recruitmentRequest, 'match' => $match]) }}" target="_blank" rel="noopener">
                                    Ouvrir
                                </a>
                            </td>
                            <td>
                                <input type="hidden" name="visible_matches[]" value="{{ $match->id }}">
                                <input
                                    type="checkbox"
                                    name="selected_matches[]"
                                    value="{{ $match->id }}"
                                    class="match-checkbox js-employee-match-checkbox"
                                    {{ $match->selected ? 'checked' : '' }}
                                >
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="match-empty">Aucun resultat disponible pour cette demande.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(method_exists($matches, 'hasPages') && $matches->hasPages())
            <div class="pagination-wrap" id="employee-match-results-pagination">
                {{ $matches->links() }}
            </div>
        @else
            <div class="pagination-wrap" id="employee-match-results-pagination" hidden></div>
        @endif
        </form>
    @else
        <div class="match-processing-box">
            <strong>Traitement asynchrone en cours</strong>
            <p>Vous pouvez revenir plus tard: cette page affichera l etat en base et les resultats des qu ils seront termines.</p>
        </div>
    @endif
</section>
@endsection

@push('styles')
<style>
    .portal-search-field {
        min-width: min(430px, 100%);
    }

    .match-results-search {
        position: relative;
        width: min(430px, 100%);
        min-width: min(430px, 100%);
        z-index: 80;
    }

    .match-results-search input {
        width: 100% !important;
        min-height: 52px !important;
        height: 52px !important;
        padding: 0 18px 0 46px !important;
        border-radius: 18px !important;
        border: 1px solid #dbe3ee !important;
        background: rgba(255, 255, 255, .96) !important;
        color: #0f172a !important;
        box-shadow: 0 14px 34px rgba(15, 23, 42, .04) !important;
        font-weight: 800 !important;
    }

    .match-results-search input::placeholder {
        color: #8b9ab1 !important;
        font-weight: 800 !important;
    }

    .match-results-search input:focus {
        outline: none !important;
        border-color: rgba(239, 35, 60, .32) !important;
        box-shadow: 0 0 0 4px rgba(239, 35, 60, .08), 0 18px 42px rgba(15, 23, 42, .08) !important;
    }

    .match-results-search .table-search-ico {
        left: 16px;
        color: #94a3b8;
    }

    .match-results-search.is-loading input {
        padding-right: 88px;
    }

    .match-results-search.is-loading::after {
        content: "Recherche";
        position: absolute;
        right: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #64748b;
        font-size: 11px;
        font-weight: 900;
    }

    .match-results-suggest {
        position: absolute !important;
        top: calc(100% + 10px) !important;
        left: 0 !important;
        right: 0 !important;
        z-index: 120 !important;
        display: grid;
        gap: 4px;
        max-height: 340px;
        overflow: auto;
        padding: 10px !important;
        border: 1px solid #e5eaf1 !important;
        border-radius: 18px !important;
        background: rgba(255, 255, 255, .98) !important;
        box-shadow: 0 22px 60px rgba(15, 23, 42, .16) !important;
    }

    .match-results-suggest[hidden] {
        display: none !important;
    }

    .match-results-suggest .suggest-item {
        width: 100% !important;
        display: block !important;
        padding: 12px 14px !important;
        border: 0 !important;
        border-radius: 14px !important;
        background: transparent !important;
        color: #0f172a !important;
        text-align: left !important;
        text-decoration: none !important;
        cursor: pointer !important;
        box-shadow: none !important;
    }

    .match-results-suggest .suggest-item:hover,
    .match-results-suggest .suggest-item:focus {
        outline: none !important;
        background: #fff1f2 !important;
        color: #ef233c !important;
    }

    .match-results-suggest .suggest-title {
        color: inherit !important;
        font-size: 13px !important;
        line-height: 1.25 !important;
        font-weight: 900 !important;
    }

    .match-results-suggest .suggest-meta {
        margin-top: 5px !important;
        color: #64748b !important;
        font-size: 12px !important;
        line-height: 1.35 !important;
        font-weight: 750 !important;
    }

    @media (max-width: 900px) {
        .portal-search-field,
        .match-results-search {
            width: 100%;
            min-width: 0;
        }
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('employee-download-selected-form');
    const counter = document.getElementById('employee-selected-count');
    let checkboxes = Array.from(document.querySelectorAll('.js-employee-match-checkbox'));
    let initialSelectedTotal = counter ? Number(counter.dataset.selectedTotal || 0) : 0;
    let initialVisibleSelected = checkboxes.filter(function (checkbox) {
        return checkbox.checked;
    }).length;

    const refreshCount = function () {
        if (!counter) {
            return;
        }

        const visibleSelected = checkboxes.filter(function (checkbox) {
            return checkbox.checked;
        }).length;

        counter.textContent = Math.max(0, initialSelectedTotal - initialVisibleSelected + visibleSelected);
    };

    const bindMatchCheckboxes = function () {
        checkboxes = Array.from(document.querySelectorAll('.js-employee-match-checkbox'));
        initialSelectedTotal = counter ? Number(counter.dataset.selectedTotal || counter.textContent || 0) : 0;
        initialVisibleSelected = checkboxes.filter(function (checkbox) {
            return checkbox.checked;
        }).length;
        checkboxes.forEach(function (checkbox) {
            checkbox.addEventListener('change', refreshCount);
        });
    };

    bindMatchCheckboxes();

    const searchInput = document.getElementById('employee-match-results-search');
    const suggestBox = document.getElementById('employee-match-results-suggest');
    const searchShell = searchInput ? searchInput.closest('.match-results-search') : null;
    const filterForm = searchInput ? searchInput.closest('form') : null;
    const tbody = document.getElementById('employee-match-results-tbody');
    const pagination = document.getElementById('employee-match-results-pagination');
    const resultsFound = document.getElementById('employee-matching-results-found');
    let searchTimer = null;
    let suggestTimer = null;
    let resultsAborter = null;
    let suggestAborter = null;

    const escapeHtml = function (value) {
        return String(value || '').replace(/[&<>"']/g, function (char) {
            return ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            })[char];
        });
    };

    const currentResultsUrl = function (pageUrl) {
        const url = new URL(pageUrl || (searchInput?.dataset.resultsUrl || window.location.href), window.location.origin);
        const params = new FormData(filterForm);
        params.forEach(function (value, key) {
            if (value !== '') url.searchParams.set(key, value);
            else url.searchParams.delete(key);
        });
        return url;
    };

    const hideSuggest = function () {
        if (!suggestBox) return;
        suggestBox.hidden = true;
        suggestBox.innerHTML = '';
    };

    const renderSuggest = function (items) {
        if (!suggestBox || !searchInput || !Array.isArray(items) || items.length === 0) {
            hideSuggest();
            return;
        }

        suggestBox.innerHTML = items.map(function (item) {
            return '<button type="button" class="suggest-item" data-value="' + escapeHtml(item.value || item.title || '') + '">' +
                '<div class="suggest-title">' + escapeHtml(item.title || 'Candidat') + '</div>' +
                '<div class="suggest-meta">' + escapeHtml(item.meta || '') + '</div>' +
            '</button>';
        }).join('');
        suggestBox.hidden = false;

        suggestBox.querySelectorAll('.suggest-item').forEach(function (button) {
            button.addEventListener('click', function () {
                searchInput.value = button.dataset.value || '';
                hideSuggest();
                fetchResults();
            });
        });
    };

    const fetchSuggest = async function () {
        if (!searchInput || !searchInput.dataset.suggestUrl) return;
        const q = searchInput.value.trim();
        if (q.length < 2) {
            hideSuggest();
            return;
        }

        if (suggestAborter) suggestAborter.abort();
        suggestAborter = new AbortController();

        const url = new URL(searchInput.dataset.suggestUrl, window.location.origin);
        url.searchParams.set('q', q);

        const response = await fetch(url, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            signal: suggestAborter.signal
        });

        if (!response.ok) throw new Error('Suggestions indisponibles.');
        renderSuggest(await response.json());
    };

    const bindPaginationLinks = function () {
        if (!pagination) return;
        pagination.querySelectorAll('a[href]').forEach(function (link) {
            if (link.dataset.boundAjax === '1') return;
            link.dataset.boundAjax = '1';
            link.addEventListener('click', function (event) {
                event.preventDefault();
                fetchResults(link.href).catch(function () {
                    window.location.href = link.href;
                });
            });
        });
    };

    const applyResultsDocument = function (html, url) {
        const doc = new DOMParser().parseFromString(html, 'text/html');
        const nextBody = doc.getElementById('employee-match-results-tbody');
        const nextPagination = doc.getElementById('employee-match-results-pagination');
        const nextFound = doc.getElementById('employee-matching-results-found');
        const nextSelected = doc.getElementById('employee-selected-count');

        if (tbody && nextBody) tbody.innerHTML = nextBody.innerHTML;
        if (pagination && nextPagination) {
            pagination.innerHTML = nextPagination.innerHTML;
            pagination.hidden = nextPagination.hidden;
        }
        if (resultsFound && nextFound) resultsFound.textContent = nextFound.textContent;
        if (counter && nextSelected) {
            counter.textContent = nextSelected.textContent;
            counter.dataset.selectedTotal = nextSelected.dataset.selectedTotal || nextSelected.textContent || '0';
        }

        bindMatchCheckboxes();
        bindPaginationLinks();
        window.history.replaceState({}, '', url.toString());
    };

    const fetchResults = async function (pageUrl) {
        if (!searchInput || !tbody) return;
        if (resultsAborter) resultsAborter.abort();
        resultsAborter = new AbortController();
        const url = currentResultsUrl(pageUrl);
        searchShell?.classList.add('is-loading');

        try {
            const response = await fetch(url, {
                headers: {
                    'Accept': 'text/html',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                signal: resultsAborter.signal
            });

            if (!response.ok) throw new Error('Recherche indisponible.');
            applyResultsDocument(await response.text(), url);
        } finally {
            searchShell?.classList.remove('is-loading');
        }
    };

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimer);
            clearTimeout(suggestTimer);
            searchTimer = window.setTimeout(function () {
                fetchResults().catch(function () {});
            }, 260);
            suggestTimer = window.setTimeout(function () {
                fetchSuggest().catch(hideSuggest);
            }, 160);
        });

        searchInput.addEventListener('focus', function () {
            fetchSuggest().catch(hideSuggest);
        });

        document.addEventListener('click', function (event) {
            if (!suggestBox || !searchInput) return;
            if (!suggestBox.contains(event.target) && event.target !== searchInput) {
                hideSuggest();
            }
        });
    }

    bindPaginationLinks();

    const autoPreset = document.getElementById('employee-match-auto-select-preset');
    const autoCustom = document.getElementById('employee-match-auto-select-custom');
    const autoApply = document.getElementById('employee-match-auto-select-apply');
    const autoClear = document.getElementById('employee-match-auto-select-clear');

    if (autoApply) {
        autoApply.addEventListener('click', function () {
            const requested = Number(autoCustom?.value || autoPreset?.value || 0);
            const safeCount = Math.max(0, Math.min(requested, checkboxes.length));

            checkboxes.forEach(function (checkbox, index) {
                checkbox.checked = index < safeCount;
            });

            refreshCount();
        });
    }

    if (autoPreset && autoCustom) {
        autoPreset.addEventListener('change', function () {
            autoCustom.value = autoPreset.value || '';
        });
    }

    if (autoClear) {
        autoClear.addEventListener('click', function () {
            checkboxes.forEach(function (checkbox) {
                checkbox.checked = false;
            });
            if (autoPreset) autoPreset.value = '';
            if (autoCustom) autoCustom.value = '';
            refreshCount();
        });
    }

    if (form) {
        form.addEventListener('submit', function () {
            const button = form.querySelector('button[type="submit"]');

            if (button) {
                button.disabled = true;
                button.textContent = 'Preparation du telechargement...';
            }
        });
    }

    if (@json($shouldAutoRefresh)) {
        window.setTimeout(function () {
            window.location.reload();
        }, 10000);
    }
});
</script>
@endpush
