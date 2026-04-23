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
                <p>
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
                    <h2>Objectifs de la formation</h2>

                    <ul>
                        @foreach(json_decode($formation->objectives, true) as $objective)
                            <li>✔ {{ $objective }}</li>
                        @endforeach
                    </ul>
                @endif

                @if($formation->program)
                    <h2>Programme</h2>
                    <p>{{ $formation->program }}</p>
                @endif

                @if($formation->description)
                    <h2>Description</h2>
                    <p>{{ $formation->description }}</p>
                @endif
            </div>

            {{-- SIDEBAR --}}
            <aside class="formation-sidebar">
                <div class="formation-box">

                    @if($formation->duration)
                        <p>
                            <strong>Durée :</strong>
                            {{ $formation->duration }}
                        </p>
                    @endif

                    @if($formation->format)
                        <p>
                            <strong>Format :</strong>
                            {{ $formation->format }}
                        </p>
                    @endif

                    @if($formation->public)
                        <p>
                            <strong>Public cible:</strong>
                            {{ $formation->public }}
                        </p>
                    @endif

                    @if($formation->domain)
                        <p>
                            <strong>Domaine :</strong>
                            {{ $formation->domain }}
                        </p>
                    @endif

                    <a href="{{ route('contact') }}" class="btn-primary">
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
