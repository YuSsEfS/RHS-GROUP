@extends('admin.layouts.app')
@section('title','Admin – Message')
@section('page_title','Message')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin-messages.css') }}">
@endpush

@section('content')
<div class="am-wrap">

  <div class="am-header">
    <div>
      <h2 class="am-title">{{ $message->subject ?? 'Sans sujet' }}</h2>
      <p class="am-subtitle">Détails du message envoyé depuis le formulaire Contact.</p>
    </div>

    <div class="am-actions">
      <a href="{{ route('admin.messages.index') }}" class="am-btn am-btn-ghost">
        ← Retour
      </a>
    </div>
  </div>

  <div class="am-detail-grid">

    {{-- LEFT: Message --}}
    <div class="am-card">
      <div class="am-detail-head">
        <div class="am-contact">
          <div class="am-avatar">
            {{ strtoupper(mb_substr($message->name, 0, 1)) }}
          </div>
          <div class="am-contact-meta">
            <div class="am-name">{{ $message->name }}</div>
            <a class="am-email" href="mailto:{{ $message->email }}">{{ $message->email }}</a>
            @if(!empty($message->phone))
              <div class="am-phone">{{ $message->phone }}</div>
            @endif
          </div>
        </div>

        <div class="am-meta-right">
          <div class="am-badge {{ $message->is_read ? 'am-badge-read' : 'am-badge-unread' }}">
            {{ $message->is_read ? 'Lu' : 'Non lu' }}
          </div>
          <div class="am-date">
            {{ optional($message->created_at)->format('d/m/Y H:i') ?? '—' }}
          </div>
        </div>
      </div>

      <div class="am-divider"></div>

      <div class="am-message">
        {{ $message->message }}
      </div>
    </div>

    {{-- RIGHT: Quick info --}}
    <aside class="am-card am-side">
      <h3 class="am-side-title">Informations</h3>

      <ul class="am-info">
        <li>
          <span class="am-info-label">Nom</span>
          <span class="am-info-value">{{ $message->name }}</span>
        </li>
        <li>
          <span class="am-info-label">Email</span>
          <span class="am-info-value">
            <a class="am-link" href="mailto:{{ $message->email }}">{{ $message->email }}</a>
          </span>
        </li>
        <li>
          <span class="am-info-label">Téléphone</span>
          <span class="am-info-value">{{ $message->phone ?? '—' }}</span>
        </li>
        <li>
          <span class="am-info-label">Sujet</span>
          <span class="am-info-value">{{ $message->subject ?? '—' }}</span>
        </li>
      </ul>

     <div class="am-side-actions">

  {{-- Reply normally --}}
  <a class="am-btn" href="mailto:{{ $message->email }}?subject={{ urlencode('Re: ' . ($message->subject ?? 'Votre message')) }}">
    Répondre par email
    <span class="am-arrow">→</span>
  </a>

  {{-- Acknowledgement email --}}
  <a
  class="am-btn am-btn-soft"
  href="mailto:{{ $message->email }}
    ?subject={{ rawurlencode('Confirmation de réception de votre message – RHS GROUP') }}
    &body={{ rawurlencode(
      "Bonjour {$message->name},\n\n".
      "Nous vous remercions pour votre message et pour l’intérêt que vous portez à RHS GROUP.\n\n".
      "Nous vous confirmons avoir bien reçu votre demande via notre formulaire de contact.\n".
      "Notre équipe est en cours de traitement et reviendra vers vous dans les plus brefs délais.\n\n".
      "Si votre demande est urgente, n’hésitez pas à nous le préciser en réponse à cet email.\n\n".
      "Cordialement,\n".
      "L’équipe RHS GROUP"
    ) }}"
>
  Confirmer la réception
  <span class="am-arrow">✓</span>
</a>


</div>

      
    </aside>

  </div>

</div>
@endsection
