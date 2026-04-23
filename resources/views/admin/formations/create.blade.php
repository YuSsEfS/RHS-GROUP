@extends('admin.layouts.app')
@section('title','Admin – Nouvelle formation')
@section('page_title','Nouvelle formation')

@section('page_subtitle')
Créez une nouvelle formation et publiez-la sur le site
@endsection

@section('top_actions')
<a class="btn btn-ghost" href="{{ route('admin.formations.index') }}">
  <span class="btn-ico" aria-hidden="true">
    <svg viewBox="0 0 24 24" fill="none">
      <path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
  </span>
  Retour
</a>
@endsection

@section('content')
<div class="panel">
  <div class="panel-head">
    <div class="panel-title">
      Nouvelle formation
      <span class="panel-badge">Création</span>
    </div>
  </div>

  <div class="panel-body">
    <form method="POST" action="{{ route('admin.formations.store') }}" class="form">
      @csrf

      {{-- FORM FIELDS --}}
      @include('admin.formations.form')

      {{-- ACTIONS --}}
      <div class="form-actions">
        <a href="{{ route('admin.formations.index') }}" class="btn btn-light">Annuler</a>
        <button type="submit" class="btn btn-primary">
          <span class="btn-ico" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none">
              <path d="M5 12l5 5L20 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </span>
          Enregistrer la formation
        </button>
      </div>
    </form>
  </div>
</div>
@endsection
