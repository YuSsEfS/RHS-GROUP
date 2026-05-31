@extends('admin.layouts.app')

@section('title', 'Recherche globale')
@section('page_title', 'Recherche globale')
@section('page_subtitle', 'Resultats transverses sur les modules admin: clients, offres, matching, CV Bank, base externe, candidatures et messages.')

@section('content')
    <section class="panel">
        <div class="panel-body">
            <form method="GET" action="{{ route('admin.search.index') }}" class="table-search" style="max-width:none; width:100%;">
                <span class="table-search-ico">
                    <svg viewBox="0 0 24 24" fill="none"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.8"/><path d="m20 20-3.5-3.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                </span>
                <input type="search" name="q" value="{{ $q }}" placeholder="Rechercher partout dans le dashboard admin">
            </form>
        </div>
    </section>

    <div class="portal-stack" style="margin-top:18px;">
        @foreach($results as $group => $items)
            <section class="panel">
                <div class="panel-head"><div class="panel-title">{{ $group }}</div></div>
                <div class="panel-body">
                    <div class="portal-timeline">
                        @foreach($items as $item)
                            <article class="portal-record">
                                <div class="portal-record-top">
                                    <strong>{{ $item['label'] }}</strong>
                                    <a href="{{ $item['url'] }}" class="btn btn-ghost btn-sm">Ouvrir</a>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>
        @endforeach

        @if($q !== '' && empty($results))
            <div class="portal-empty">
                <div class="portal-empty-title">Aucun resultat</div>
                <div class="portal-empty-copy">Aucun element ne correspond a votre recherche.</div>
            </div>
        @endif
    </div>
@endsection
