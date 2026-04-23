@extends('admin.layouts.app')
@section('title','Admin – Modifier offre')
@section('page_title','Modifier offre')

@section('page_subtitle')
Modifiez les informations de l’offre puis enregistrez vos changements
@endsection

@section('top_actions')
  <a class="btn btn-ghost" href="{{ route('admin.offers.index') }}">
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
        Modifier l’offre
        <span class="panel-badge">#{{ $offer->id }}</span>
      </div>

      <div class="panel-tools">
        <span class="meta">
          <span class="meta-dot {{ $offer->is_active ? 'is-on' : 'is-off' }}"></span>
          {{ $offer->is_active ? 'Actif' : 'Inactif' }}
        </span>
      </div>
    </div>

    <div class="panel-body">
    <form method="POST"
      action="{{ route('admin.offers.update', $offer->id) }}"
      class="form"
      enctype="multipart/form-data">
  @csrf
  @method('PUT')

  @include('admin.offers._form', ['offer' => $offer])


        <div class="form-actions">
          <a class="btn btn-ghost" href="{{ route('admin.offers.index') }}">Annuler</a>

          <button class="btn btn-primary" type="submit">
            <span class="btn-ico" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none">
                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M17 21v-8H7v8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M7 3v5h8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </span>
            Enregistrer
          </button>
        </div>
      </form>
    </div>
  </div>

@endsection
