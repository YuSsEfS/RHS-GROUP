@extends('admin.layouts.app')

@section('title', 'Utilisateurs')
@section('page_title', 'Utilisateurs')
@section('page_subtitle', 'Gestion des comptes admin, employee et client avec validation et permissions.')

@section('top_actions')
    <a href="{{ route('admin.users.create') }}" class="admin-btn admin-btn-primary">Créer un utilisateur</a>
@endsection

@section('content')
    <div class="admin-card" style="padding:20px; margin-bottom:18px;">
        <form method="GET" style="display:grid; grid-template-columns:2fr 1fr 1fr auto; gap:12px;">
            <input class="admin-input" type="text" name="q" value="{{ $q }}" placeholder="Rechercher nom ou email" style="width:100%;">

            <select class="admin-input" name="role" style="height:44px;">
                <option value="all">Tous les rôles</option>
                @foreach($roles as $value => $label)
                    <option value="{{ $value }}" @selected($role === $value)>{{ $label }}</option>
                @endforeach
            </select>

            <select class="admin-input" name="status" style="height:44px;">
                <option value="all">Tous les statuts</option>
                @foreach($statuses as $value => $label)
                    <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                @endforeach
            </select>

            <button class="admin-btn admin-btn-primary" type="submit">Filtrer</button>
        </form>
    </div>

    <div class="admin-card" style="padding:0; overflow:hidden;">
        <table style="width:100%; border-collapse:collapse;">
            <thead>
                <tr style="background:#f8fafc; text-align:left;">
                    <th style="padding:14px 16px;">Utilisateur</th>
                    <th style="padding:14px 16px;">Rôle</th>
                    <th style="padding:14px 16px;">Statut</th>
                    <th style="padding:14px 16px;">Permissions</th>
                    <th style="padding:14px 16px;">Approbation</th>
                    <th style="padding:14px 16px; width:220px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr style="border-top:1px solid rgba(15,23,42,.08);">
                        <td style="padding:14px 16px;">
                            <div style="font-weight:800;">{{ $user->name }}</div>
                            <div style="color:#64748b;">{{ $user->email }}</div>
                        </td>
                        <td style="padding:14px 16px;">{{ ucfirst($user->role) }}</td>
                        <td style="padding:14px 16px;">
                            <span class="admin-chip">{{ ucfirst($user->status) }}</span>
                        </td>
                        <td style="padding:14px 16px;">{{ count($user->permissions ?? []) }}</td>
                        <td style="padding:14px 16px;">
                            @if($user->approved_at)
                                {{ $user->approved_at->format('d/m/Y H:i') }}
                            @else
                                —
                            @endif
                        </td>
                        <td style="padding:14px 16px;">
                            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                                <a href="{{ route('admin.users.edit', $user) }}" class="admin-btn admin-btn-ghost">Modifier</a>
                                @if(!$user->is(auth()->user()))
                                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Supprimer cet utilisateur ?');">
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
                        <td colspan="6" style="padding:18px 16px;">Aucun utilisateur trouvé.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top:18px;">
        {{ $users->links() }}
    </div>
@endsection
