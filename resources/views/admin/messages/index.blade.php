@extends('admin.layouts.app')
@section('title','Admin – Messages')
@section('page_title','Messages')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin-messages.css') }}">
@endpush

@section('content')
<div class="am-wrap">

  {{-- Header / Actions --}}
  <div class="am-header">
    <div>
      <h2 class="am-title">Messages reçus</h2>
      <p class="am-subtitle">Demandes envoyées depuis le formulaire Contact.</p>
    </div>

    <div class="am-actions">
      <div class="am-chip">
        Total : <strong>{{ $messages->total() }}</strong>
      </div>
    </div>
  </div>

  {{-- Table Card --}}
  <div class="am-card">
    <div class="am-table-wrap">
      <table class="am-table">
        <thead>
          <tr>
            <th>Contact</th>
            <th>Sujet</th>
            <th>Statut</th>
            <th>Date</th>
            <th class="am-th-right">Action</th>
          </tr>
        </thead>

        <tbody>
          @forelse($messages as $m)
            <tr class="{{ $m->is_read ? '' : 'is-unread' }}">
              <td>
                <div class="am-contact">
                  <div class="am-avatar">
                    {{ strtoupper(mb_substr($m->name, 0, 1)) }}
                  </div>
                  <div class="am-contact-meta">
                    <div class="am-name">{{ $m->name }}</div>
                    <a class="am-email" href="mailto:{{ $m->email }}">{{ $m->email }}</a>
                    @if(!empty($m->phone))
                      <div class="am-phone">{{ $m->phone }}</div>
                    @endif
                  </div>
                </div>
              </td>

              <td>
                <div class="am-subject">
                  {{ $m->subject ?? 'Sans sujet' }}
                </div>
                <div class="am-snippet">
                  {{ \Illuminate\Support\Str::limit($m->message, 85) }}
                </div>
              </td>

              <td>
                @if($m->is_read)
                  <span class="am-badge am-badge-read">Lu</span>
                @else
                  <span class="am-badge am-badge-unread">Non lu</span>
                @endif
              </td>

              <td>
                <div class="am-date">
                  {{ optional($m->created_at)->format('d/m/Y') ?? '—' }}
                </div>
                <div class="am-time">
                  {{ optional($m->created_at)->format('H:i') ?? '' }}
                </div>
              </td>

              <td class="am-td-right">
                <a class="am-btn" href="{{ route('admin.messages.show', $m->id) }}">
                  Ouvrir
                  <span class="am-arrow">→</span>
                </a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5">
                <div class="am-empty">
                  <h3>Aucun message</h3>
                  <p>Les messages envoyés depuis la page Contact apparaîtront ici.</p>
                </div>
              </td>
            </tr>
          @endforelse
        </tbody>

      </table>
    </div>
  </div>

  {{-- Pagination --}}
  <div class="am-pagination">
    {{ $messages->links() }}
  </div>

</div>
@endsection
