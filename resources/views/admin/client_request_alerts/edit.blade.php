@extends('admin.layouts.app')

@section('title', 'Repondre a la relance client')
@section('page_title', 'Repondre a la relance client')
@section('page_subtitle', 'Choisissez une reponse rapide, ajoutez un commentaire personnalise et mettez a jour le statut de la relance.')

@section('content')
    <div class="panel">
        <div class="panel-body">
            <div class="info-grid">
                <div class="info-item"><div class="info-label">Client</div><div class="info-value">{{ $alert->clientUser?->name ?: '-' }}</div></div>
                <div class="info-item"><div class="info-label">Demande</div><div class="info-value">{{ $alert->recruitmentRequest?->position_title ?: '-' }}</div></div>
                <div class="info-item"><div class="info-label">Statut actuel</div><div class="info-value">{{ $statuses[$alert->status] ?? $alert->status }}</div></div>
                <div class="info-item"><div class="info-label">Date</div><div class="info-value">{{ $alert->created_at->format('d/m/Y H:i') }}</div></div>
            </div>

            <div class="message-box" style="margin-top:18px;">
                <div class="message-title">Message client</div>
                <div class="message-content">{{ $alert->message ?: 'Relance sans message complementaire.' }}</div>
            </div>

            <form method="POST" action="{{ route('admin.client-request-alerts.update', $alert) }}" style="margin-top:24px;">
                @csrf
                @method('PUT')
                <div style="display:grid; gap:18px;">
                    <div>
                        <label class="admin-label" for="status">Statut de la relance</label>
                        <select class="admin-input" id="status" name="status" style="width:100%; height:44px;">
                            @foreach($statuses as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', $alert->status) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="admin-label" for="quick_response">Reponse rapide</label>
                        <select class="admin-input" id="quick_response" name="quick_response" style="width:100%; height:44px;">
                            <option value="">Aucune</option>
                            @foreach($quickResponses as $response)
                                <option value="{{ $response }}" @selected(old('quick_response') === $response)>{{ $response }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="admin-label" for="admin_response">Reponse personnalisee</label>
                        <textarea class="admin-input" id="admin_response" name="admin_response" rows="6" style="width:100%; padding:14px;">{{ old('admin_response', $alert->admin_response) }}</textarea>
                    </div>
                </div>

                <div class="action-row" style="margin-top:24px;">
                    <button type="submit" class="admin-btn admin-btn-primary" style="width:auto;">Envoyer la reponse</button>
                    <a href="{{ route('admin.client-request-alerts.index') }}" class="admin-btn admin-btn-ghost" style="width:auto;">Retour</a>
                </div>
            </form>
        </div>
    </div>
@endsection
