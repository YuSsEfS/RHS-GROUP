  @extends('admin.layouts.app')
  @section('title','Admin – Nouvelle offre')
  @section('page_title','Nouvelle offre')

  @section('page_subtitle')
  Créez une nouvelle offre et publiez-la sur le site
  @endsection

  @section('top_actions')
    <a class="btn btn-ghost" href="{{ route('admin.offers.index') }}">
      <span class="btn-ico" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none">
          <path d="M15 18l-6-6 6-6"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"/>
        </svg>
      </span>
      Retour
    </a>
  @endsection

  @section('content')

    <div class="panel">
      <div class="panel-head">
        <div class="panel-title">
          Nouvelle offre
          <span class="panel-badge">Création</span>
        </div>
      </div>

      <div class="panel-body">
       <form method="POST"
      action="{{ route('admin.offers.store') }}"
      class="form"
      enctype="multipart/form-data">
  @csrf

  {{-- FORM FIELDS --}}
  @include('admin.offers._form', ['offer' => null])


          {{-- ACTIONS --}}
          <div class="form-actions">
            <a href="{{ route('admin.offers.index') }}"
              class="btn btn-light">
              Annuler
            </a>

            <button type="submit"
                    class="btn btn-primary">
              <span class="btn-ico" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none">
                  <path d="M5 12l5 5L20 7"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"/>
                </svg>
              </span>
              Publier l’offre
            </button>
          </div>
        </form>
      </div>
    </div>

  @endsection
