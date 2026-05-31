@extends('admin.layouts.app')

@section('title', 'Utilisateurs')
@section('page_title', 'Utilisateurs')
@section('page_subtitle', 'Gestion des comptes admin, employee et client avec validation et permissions.')

@section('top_actions')
    <a href="{{ route('admin.users.create') }}" class="admin-btn admin-btn-primary">Creer un utilisateur</a>
@endsection

@section('content')
    <div class="admin-card ui-filter-panel">
        <form method="GET" class="ui-filter-grid">
            <input class="admin-input" type="text" name="q" value="{{ $q }}" placeholder="Rechercher nom ou email">

            <select class="admin-input" name="role">
                <option value="all">Tous les roles</option>
                @foreach($roles as $value => $label)
                    <option value="{{ $value }}" @selected($role === $value)>{{ $label }}</option>
                @endforeach
            </select>

            <select class="admin-input" name="status">
                <option value="all">Tous les statuts</option>
                @foreach($statuses as $value => $label)
                    <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                @endforeach
            </select>

            <div class="table-ctrl-actions">
                <button class="admin-btn admin-btn-primary" type="submit">Filtrer</button>
                <a href="{{ route('admin.users.index') }}" class="admin-btn admin-btn-ghost">Reinitialiser</a>
            </div>
        </form>
    </div>

    <div class="admin-card ui-table-shell">
        <div class="table-wrap ui-table-scroll ui-table-sticky">
            <table class="table">
                <thead>
                    <tr>
                        <th>Utilisateur</th>
                        <th>Role</th>
                        <th>Statut</th>
                        <th>Permissions</th>
                        <th>Approbation</th>
                        <th class="th-actions" style="width:220px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td>
                                <div class="ui-table-meta">
                                    <strong>{{ $user->name }}</strong>
                                    <span>{{ $user->email }}</span>
                                </div>
                            </td>
                            <td>{{ ucfirst($user->role) }}</td>
                            <td>
                                <span class="admin-chip">{{ ucfirst($user->status) }}</span>
                            </td>
                            <td>{{ count($user->permissions ?? []) }}</td>
                            <td>
                                @if($user->approved_at)
                                    {{ $user->approved_at->format('d/m/Y H:i') }}
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                <div class="td-actions">
                                    <a href="{{ route('admin.users.edit', $user) }}" class="admin-btn admin-btn-ghost">Modifier</a>
                                    @if(!$user->is(auth()->user()))
                                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}" data-rhs-confirm="Supprimer cet utilisateur ?" onsubmit="return confirm('Supprimer cet utilisateur ?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="admin-btn admin-btn-danger">Supprimer</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="ui-empty-state">
                                    <div class="ui-empty-title">Aucun utilisateur trouve</div>
                                    <div class="ui-empty-copy">Ajustez vos filtres ou creez un nouvel utilisateur depuis le bouton d action.</div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div style="margin-top:18px;">
        {{ $users->links() }}
    </div>
@endsection
