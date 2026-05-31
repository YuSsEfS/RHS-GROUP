@extends('layouts.app')
@section('title', $formation->title . ' – RHS GROUP')

@section('content')
<div class="formation-detail">

    {{-- ================= HERO ================= --}}
    <section class="formation-hero">
        <div class="container">
            <h1>
                {{ $formation->title }}
            </h1>

            @if($formation->subtitle)
                <p data-cms-key="formation.detail.subtitle">
                    {{ $formation->subtitle }}
                </p>
            @endif
        </div>
    </section>

    {{-- ================= CONTENT ================= --}}
    <section class="formation-content">
        <div class="container formation-grid">

            {{-- MAIN --}}
            <div class="formation-main">

                @if($formation->objectives)
                    <h2 data-cms-key="formation.detail.objectives_title">Objectifs de la formation</h2>

                    <ul>
                        @foreach(json_decode($formation->objectives, true) as $objective)
                            <li><span aria-hidden="true">?</span> {{ $objective }}</li>
                        @endforeach
                    </ul>
                @endif

                @if($formation->program)
                    <h2 data-cms-key="formation.detail.program_title">Programme</h2>
                    <p>{{ $formation->program }}</p>
                @endif

                @if($formation->description)
                    <h2 data-cms-key="formation.detail.description_title">Description</h2>
                    <p>{{ $formation->description }}</p>
                @endif
            </div>

            {{-- SIDEBAR --}}
            <aside class="formation-sidebar">
                <div class="formation-box">

                    @if($formation->duration)
                        <p>
                            <strong data-cms-key="formation.detail.duration_label">Durée :</strong>
                            {{ $formation->duration }}
                        </p>
                    @endif

                    @if($formation->format)
                        <p>
                            <strong data-cms-key="formation.detail.format_label">Format :</strong>
                            {{ $formation->format }}
                        </p>
                    @endif

                    @if($formation->public)
                        <p>
                            <strong data-cms-key="formation.detail.public_label">Public cible:</strong>
                            {{ $formation->public }}
                        </p>
                    @endif

                    @if($formation->domain)
                        <p>
                            <strong data-cms-key="formation.detail.domain_label">Domaine :</strong>
                            {{ $formation->domain }}
                        </p>
                    @endif

                    <a href="{{ route('contact') }}" class="btn-primary" data-cms-key="formation.detail.cta">
                        Demander le programme
                    </a>

                </div>
            </aside>

        </div>
    </section>

</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/formation-detail.css') }}">
@endpush
