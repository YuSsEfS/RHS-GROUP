@extends('admin.layouts.app')

@section('title', 'Requetes internes')
@section('page_title', 'Requetes internes')
@section('page_subtitle', 'Suivez les demandes RH et administratives envoyees par les employes.')

@section('content')
    <div class="panel" style="margin-bottom:18px;">
        <div class="panel-body">
            <form method="GET" class="table-controls">
                <div class="table-filter">
                    <select name="employee">
                        <option value="all">Tous les employes</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}" @selected($employeeId === (string) $employee->id)>{{ $employee->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="table-filter">
                    <select name="category">
                        <option value="all">Toutes les categories</option>
                        @foreach($categories as $value => $label)
                            <option value="{{ $value }}" @selected($category === $value)>{{ $label }}</option>
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
                <div class="table-ctrl-actions"><button class="btn btn-primary btn-sm" type="submit">Filtrer</button></div>
            </form>
        </div>
    </div>

    <div class="panel">
        <div class="panel-head"><div class="panel-title">Requetes internes <span class="panel-badge">{{ $requests->total() }}</span></div></div>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Employe</th>
                        <th>Categorie</th>
                        <th>Sujet</th>
                        <th>Statut</th>
                        <th>Message</th>
                        <th class="th-actions">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $requestItem)
                        <tr>
                            <td>{{ $requestItem->user?->name }}</td>
                            <td>{{ $categories[$requestItem->category] ?? $requestItem->category }}</td>
                            <td>{{ $requestItem->subject }}</td>
                            <td><span class="pill {{ $requestItem->status === 'resolved' ? 'pill-success' : ($requestItem->status === 'rejected' ? 'pill-danger' : 'pill-neutral') }}">{{ $statuses[$requestItem->status] ?? $requestItem->status }}</span></td>
                            <td>{{ \Illuminate\Support\Str::limit($requestItem->message, 90) }}</td>
                            <td class="td-actions"><a href="{{ route('admin.employee-internal-requests.edit', $requestItem) }}" class="btn btn-primary btn-sm">Gerer</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><div class="table-empty"><div class="table-empty-title">Aucune requete interne trouvee.</div></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div style="margin-top:18px;">{{ $requests->links() }}</div>
@endsection
