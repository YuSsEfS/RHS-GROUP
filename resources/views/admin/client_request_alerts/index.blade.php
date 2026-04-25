@extends('admin.layouts.app')

@section('title', 'Relances clients')
@section('page_title', 'Relances clients')
@section('page_subtitle', 'Consultez les relances, filtrez-les et repondez sans impacter le statut metier de la demande.')

@section('content')
    <div class="panel" style="margin-bottom:18px;">
        <div class="panel-body">
            <form method="GET" class="table-controls">
                <div class="table-filter">
                    <select name="client">
                        <option value="all">Tous les clients</option>
                        @foreach($clients as $clientOption)
                            <option value="{{ $clientOption->id }}" @selected($client === (string) $clientOption->id)>{{ $clientOption->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="table-filter">
                    <select name="status">
                        <option value="all">Tous les statuts</option>
                        @foreach($statuses as $value => $label)
                            <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="table-filter">
                    <input class="admin-input" type="number" name="request" value="{{ $requestId }}" placeholder="ID demande" style="height:44px;">
                </div>
                <div class="table-ctrl-actions">
                    <button class="btn btn-primary btn-sm" type="submit">Filtrer</button>
                </div>
            </form>
        </div>
    </div>

    <div class="panel">
        <div class="panel-head">
            <div class="panel-title">Relances recues <span class="panel-badge">{{ $alerts->total() }}</span></div>
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Client</th>
                        <th>Demande</th>
                        <th>Statut relance</th>
                        <th>Message</th>
                        <th>Date</th>
                        <th class="th-actions">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($alerts as $alert)
                        <tr>
                            <td>{{ $alert->clientUser?->name ?: '-' }}</td>
                            <td>
                                <div class="cell-main">
                                    <div class="cell-title">{{ $alert->recruitmentRequest?->position_title ?: '-' }}</div>
                                    <div class="cell-sub">Demande #{{ $alert->recruitment_request_id }}</div>
                                </div>
                            </td>
                            <td><span class="pill {{ $alert->status === 'processed' ? 'pill-success' : ($alert->status === 'viewed' ? 'pill-neutral' : 'pill-danger') }}">{{ $statuses[$alert->status] ?? $alert->status }}</span></td>
                            <td>{{ \Illuminate\Support\Str::limit($alert->message ?: 'Sans message', 90) }}</td>
                            <td>{{ $alert->created_at->format('d/m/Y H:i') }}</td>
                            <td class="td-actions"><a href="{{ route('admin.client-request-alerts.edit', $alert) }}" class="btn btn-primary btn-sm">Repondre</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><div class="table-empty"><div class="table-empty-title">Aucune relance client.</div></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div style="margin-top:18px;">{{ $alerts->links() }}</div>
@endsection
