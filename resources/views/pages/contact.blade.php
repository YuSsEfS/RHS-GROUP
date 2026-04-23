@extends('layouts.app')
@section('title','Contact – RHS GROUP')

@section('content')

<div class="contact-page">

    {{-- ================= HERO ================= --}}
    <section class="contact-hero" data-reveal>
        <div class="container contact-hero-inner">

            <p class="contact-eyebrow" data-cms-key="contact.hero.eyebrow">CONTACT</p>

            <h1 class="contact-title" data-cms-key="contact.hero.title">
                Parlons de votre<br>
                projet <span data-cms-key="contact.hero.title_span">RH</span>
            </h1>

            <p class="contact-subtitle" data-cms-key="contact.hero.subtitle">
                Une question, un besoin urgent, ou un accompagnement sur-mesure ?
                Notre équipe est à votre écoute.
            </p>

            <div class="contact-hero-actions">
                <a class="btn-red magnetic"
                   href="tel:+212522400808"
                   data-cms-key="contact.hero.btn_call">
                    Appeler maintenant
                </a>

                <a class="btn-outline-red magnetic"
                   href="#contact-form"
                   data-cms-key="contact.hero.btn_message">
                    Envoyer un message
                </a>
            </div>

        </div>
    </section>


    {{-- ================= CONTACT GRID ================= --}}
    <section class="contact-section">
        <div class="container contact-grid">

            {{-- LEFT --}}
            <div class="contact-left">

                {{-- CARDS --}}
                <div class="contact-cards">

                    {{-- PHONE --}}
                    <article class="contact-card" data-reveal>
                        <div class="contact-card-icon">
                            <img src="{{ asset('images/icons/phone.svg') }}"
                                 alt="Téléphone"
                                 data-cms-img="contact.card.phone.icon">
                        </div>
                        <div class="contact-card-body">
                            <p class="contact-card-label" data-cms-key="contact.card.phone.label">Téléphone</p>
                            <a class="contact-card-value"
                               href="tel:+212522400808"
                               data-cms-key="contact.card.phone.value">
                                05 22 40 08 08
                            </a>
                            <p class="contact-card-hint" data-cms-key="contact.card.phone.hint">
                                Lundi – Vendredi · 8h30 – 17h30
                            </p>
                        </div>
                    </article>

                    {{-- HOURS --}}
                    <article class="contact-card" data-reveal>
                        <div class="contact-card-icon">
                            <img src="{{ asset('images/icons/clock.svg') }}"
                                 alt="Horaires"
                                 data-cms-img="contact.card.hours.icon">
                        </div>
                        <div class="contact-card-body">
                            <p class="contact-card-label" data-cms-key="contact.card.hours.label">Horaires</p>
                            <p class="contact-card-value" data-cms-key="contact.card.hours.value">
                                8h30 – 17h30
                            </p>
                            <p class="contact-card-hint" data-cms-key="contact.card.hours.hint">
                                Service client disponible en journée
                            </p>
                        </div>
                    </article>

                    {{-- ADDRESS --}}
                    <article class="contact-card" data-reveal>
                        <div class="contact-card-icon">
                            <img src="{{ asset('images/icons/location.svg') }}"
                                 alt="Adresse"
                                 data-cms-img="contact.card.address.icon">
                        </div>
                        <div class="contact-card-body">
                            <p class="contact-card-label" data-cms-key="contact.card.address.label">Adresse</p>
                            <a class="contact-card-value"
                               target="_blank"
                               href="https://maps.app.goo.gl/Agf9jXdeAp9ULgDv7"
                               data-cms-key="contact.card.address.value">
                                137 Bd Moulay Ismaïl, Casablanca
                            </a>
                            <p class="contact-card-hint" data-cms-key="contact.card.address.hint">
                                Cliquez pour ouvrir Google Maps
                            </p>
                        </div>
                    </article>

                    {{-- EMAIL --}}
                    <article class="contact-card" data-reveal>
                        <div class="contact-card-icon">
                            <img src="{{ asset('images/icons/idea.svg') }}"
                                 alt="Email"
                                 data-cms-img="contact.card.email.icon">
                        </div>
                        <div class="contact-card-body">
                            <p class="contact-card-label" data-cms-key="contact.card.email.label">Email</p>
                            <a class="contact-card-value"
                               href="mailto:contact@rhsgroup.ma"
                               data-cms-key="contact.card.email.value">
                                contact@rhsgroup.ma
                            </a>
                            <p class="contact-card-hint" data-cms-key="contact.card.email.hint">
                                Réponse rapide aux demandes professionnelles
                            </p>
                        </div>
                    </article>

                </div>

                {{-- MAP --}}
                <div class="contact-map" data-reveal>
                    <div class="contact-map-head">
                        <h3 data-cms-key="contact.map.title">Nous trouver</h3>
                        <a target="_blank"
                           href="https://maps.app.goo.gl/Agf9jXdeAp9ULgDv7"
                           class="contact-map-link"
                           data-cms-key="contact.map.link">
                            Ouvrir sur Google Maps →
                        </a>
                    </div>

                    <div class="contact-map-frame">
                        <iframe
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"
                            src="https://www.google.com/maps?q=137%20Bd%20Moulay%20Isma%C3%AFl%2C%20Casablanca&output=embed">
                        </iframe>
                    </div>
                </div>

            </div>

            

            {{-- RIGHT --}}

            <div class="contact-right" id="contact-form">
{{-- SUCCESS MESSAGE --}}
            @if(session('success'))
                <div class="contact-success" data-reveal>
                    {{ session('success') }}
                </div>
            @endif
                <div class="contact-form-card" data-reveal>
                    <p class="contact-form-eyebrow" data-cms-key="contact.form.eyebrow">FORMULAIRE</p>

                    <h2 class="contact-form-title" data-cms-key="contact.form.title">
                        Envoyez-nous un <span data-cms-key="contact.form.title_span">message</span>
                    </h2>

                    <p class="contact-form-subtitle" data-cms-key="contact.form.subtitle">
                        Décrivez votre besoin : recrutement, travail temporaire, formation, coaching ou conseil RH.
                    </p>

                    {{-- FORM (locked) --}}
                    <form class="contact-form" action="{{ route('contact.store') }}" method="POST">
                        @csrf
                         <div class="form-grid">
                            <div class="form-field">
                                <label>Nom complet</label>
                                <input type="text" name="name" placeholder="Votre nom" required>
                            </div>

                            <div class="form-field">
                                <label>Email</label>
                                <input type="email" name="email" placeholder="ex: nom@email.com" required>
                            </div>

                            <div class="form-field">
                                <label>Téléphone</label>
                                <input type="tel" name="phone" placeholder="ex: 06xx xx xx xx">
                            </div>

                            <div class="form-field">
                                <label>Objet</label>
                                <select name="subject" required>
                                    <option value="" selected disabled>Choisir un sujet</option>
                                    <option>Travail temporaire</option>
                                    <option>Recrutement</option>
                                    <option>Formation</option>
                                    <option>Coaching</option>
                                    <option>Conseil RH</option>
                                    <option>Autre</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-field">
                            <label>Message</label>
                            <textarea name="message" rows="6" placeholder="Expliquez votre besoin..." required></textarea>
                        </div>

                        <label class="form-consent">
<input type="checkbox" name="consent" required>
                            <span>J’accepte d’être recontacté(e) par RHS GROUP concernant ma demande.</span>
                        </label>

                        <button class="btn-red form-submit magnetic" type="submit">
                            Envoyer le message
                        </button>

                        <p class="form-note">
                            Ou appelez-nous directement au <a href="tel:+212522400808">05 22 40 08 08</a>.
                        </p>
                    </form>
                </div>
                    </form>
                </div>

                {{-- MINI CTA --}}
                <div class="contact-mini-cta" data-reveal>
                    <div class="mini-cta-text">
                        <h3 data-cms-key="contact.mini.title">Besoin urgent ?</h3>
                        <p data-cms-key="contact.mini.desc">
                            Nous pouvons mobiliser rapidement des profils adaptés à votre activité.
                        </p>
                    </div>
                    <a class="btn-outline-red magnetic"
                       href="tel:+212522400808"
                       data-cms-key="contact.mini.btn">
                        Appeler
                    </a>
                </div>

            </div>

        </div>
    </section>

</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/contact.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('js/reveal.js') }}" defer></script>
<script src="{{ asset('js/magnetic.js') }}" defer></script>
@endpush
