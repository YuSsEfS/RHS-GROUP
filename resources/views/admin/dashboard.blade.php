@extends('admin.layouts.app')
@section('title','Admin – Dashboard')
@section('page_title','Dashboard')

@section('page_subtitle')
Bienvenue sur votre tableau de bord d'administration
@endsection

@section('content')

  {{-- KPI GRID --}}
  <div class="dash-kpis">

    <a class="dash-kpi" href="{{ route('admin.offers.index') }}">
      <div class="dash-kpi-top">
        <div class="dash-kpi-label">Offres</div>
        <div class="dash-kpi-icon">
          <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M7 7h10v14H7V7Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M9 3h6v4H9V3Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M9 12h6M9 16h6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
          </svg>
        </div>
      </div>
      <div class="dash-kpi-value">{{ $offersCount }}</div>
      <div class="dash-kpi-foot">
        <span class="dash-kpi-hint">Gérer les offres</span>
        <span class="dash-kpi-arrow">→</span>
      </div>
      <span class="dash-kpi-accent"></span>
    </a>

    <a class="dash-kpi" href="{{ route('admin.applications.index') }}">
      <div class="dash-kpi-top">
        <div class="dash-kpi-label">Candidatures non lues</div>
        <div class="dash-kpi-icon">
          <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M16 11c1.66 0 3-1.57 3-3.5S17.66 4 16 4s-3 1.57-3 3.5S14.34 11 16 11Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M8 11c1.66 0 3-1.57 3-3.5S9.66 4 8 4 5 5.57 5 7.5 6.34 11 8 11Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M4 20c0-2.76 2.24-5 5-5h2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
            <path d="M13 15h2c2.76 0 5 2.24 5 5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
          </svg>
        </div>
      </div>
      <div class="dash-kpi-value">{{ $appsUnread }}</div>
      <div class="dash-kpi-foot">
        <span class="dash-kpi-hint">Voir les candidatures</span>
        <span class="dash-kpi-arrow">→</span>
      </div>
      <span class="dash-kpi-accent"></span>
    </a>

    <a class="dash-kpi" href="{{ route('admin.messages.index') }}">
      <div class="dash-kpi-top">
        <div class="dash-kpi-label">Messages non lus</div>
        <div class="dash-kpi-icon">
          <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M21 14c0 1.1-.9 2-2 2H8l-5 4V5c0-1.1.9-2 2-2h14c1.1 0 2 .9 2 2v9Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </div>
      </div>
      <div class="dash-kpi-value">{{ $msgsUnread }}</div>
      <div class="dash-kpi-foot">
        <span class="dash-kpi-hint">Lire les messages</span>
        <span class="dash-kpi-arrow">→</span>
      </div>
      <span class="dash-kpi-accent"></span>
    </a>

  </div>

  {{-- QUICK ACTIONS + RECENT ACTIVITY --}}
  <div class="dash-grid">

    <section class="dash-card">
      <div class="dash-card-head">
        <div>
          <h2 class="dash-card-title">Actions rapides</h2>
          <p class="dash-card-sub">Accédez rapidement aux sections principales</p>
        </div>
      </div>

      <div class="dash-actions">
        <a class="dash-action" href="{{ route('admin.offers.index') }}">
          <span class="dash-action-ico">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M7 7h10v14H7V7Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M9 3h6v4H9V3Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </span>
          <div class="dash-action-text">
            <div class="dash-action-title">Gérer les offres</div>
            <div class="dash-action-sub">Créer / modifier / publier</div>
          </div>
          <span class="dash-action-go">→</span>
        </a>

        <a class="dash-action" href="{{ route('admin.applications.index') }}">
          <span class="dash-action-ico">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M16 11c1.66 0 3-1.57 3-3.5S17.66 4 16 4s-3 1.57-3 3.5S14.34 11 16 11Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M8 11c1.66 0 3-1.57 3-3.5S9.66 4 8 4 5 5.57 5 7.5 6.34 11 8 11Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </span>
          <div class="dash-action-text">
            <div class="dash-action-title">Candidatures</div>
            <div class="dash-action-sub">Lire, filtrer, répondre</div>
          </div>
          <span class="dash-action-go">→</span>
        </a>

        <a class="dash-action" href="{{ route('admin.messages.index') }}">
          <span class="dash-action-ico">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M21 14c0 1.1-.9 2-2 2H8l-5 4V5c0-1.1.9-2 2-2h14c1.1 0 2 .9 2 2v9Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </span>
          <div class="dash-action-text">
            <div class="dash-action-title">Messages</div>
            <div class="dash-action-sub">Support & formulaires</div>
          </div>
          <span class="dash-action-go">→</span>
        </a>

        <a class="dash-action" href="{{ route('admin.content.index') }}">
          <span class="dash-action-ico">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M7 3h10v18H7V3Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M10 7h4M10 11h4M10 15h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
            </svg>
          </span>
          <div class="dash-action-text">
            <div class="dash-action-title">Contenu site</div>
            <div class="dash-action-sub">Modifier les sections</div>
          </div>
          <span class="dash-action-go">→</span>
        </a>
      </div>
    </section>

    <section class="dash-card">
      <div class="dash-card-head">
        <div>
          <h2 class="dash-card-title">Activité récente</h2>
          <p class="dash-card-sub">Résumé des nouveautés (placeholder)</p>
        </div>
      </div>

      <div class="dash-empty">
        <div class="dash-empty-ico">🕒</div>
        <div class="dash-empty-title">Aucune activité récente</div>
        <div class="dash-empty-sub">Quand vous recevrez des candidatures ou messages, elles apparaîtront ici.</div>
      </div>
    </section>

  </div>

@endsection
