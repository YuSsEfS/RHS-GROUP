@extends('dashboard.layouts.app')

@section('title', 'Demandes RH')
@section('brand', 'RHS Employe')
@section('brand_sub', 'Demandes RH internes')
@section('page_title', 'Mes demandes RH internes')
@section('page_copy', 'Envoyez vos besoins RH ou administratifs et consultez la reponse de l administration.')

@section('sidebar')
    @include('employee._sidebar')
@endsection

@section('top_badge')
    <span class="portal-badge">{{ $requests->whereIn('status', ['pending', 'in_progress'])->count() }} ouvertes</span>
@endsection

@section('content')
    <div class="portal-split">
        <section class="portal-card">
            <h3 style="margin:0 0 8px;">Nouvelle demande RH</h3>

            <form method="POST" action="{{ route('employee.internal-requests.store') }}" class="portal-form-grid">
                @csrf
                <div>
                    <label for="category">Categorie</label>
                    <select id="category" name="category">
                        @foreach($categories as $value => $label)
                            <option value="{{ $value }}" @selected(old('category') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="subject">Sujet</label>
                    <input id="subject" name="subject" type="text" value="{{ old('subject') }}" placeholder="Ex: Attestation de salaire">
                </div>
                <div class="full">
                    <label for="message">Message</label>
                    <textarea id="message" name="message" rows="6" placeholder="Precisez votre besoin RH ou administratif">{{ old('message') }}</textarea>
                </div>
                <div class="full form-actions-inline">
                    <button type="submit" class="admin-btn admin-btn-primary portal-btn-auto">Envoyer la demande</button>
                </div>
            </form>
        </section>

        <section class="portal-card">
            <h3 style="margin:0 0 8px;">Historique</h3>
            <div class="portal-timeline">
                @forelse($requests as $requestItem)
                    <article class="portal-record">
                        <div class="portal-record-top">
                            <div>
                                <strong>{{ $requestItem->subject }}</strong>
                                <div class="portal-copy">{{ $categories[$requestItem->category] ?? $requestItem->category }}</div>
                            </div>
                            <span class="portal-status {{ $requestItem->status === 'resolved' ? 'is-success' : ($requestItem->status === 'rejected' ? 'is-danger' : 'is-warning') }}">
                                {{ $statuses[$requestItem->status] ?? $requestItem->status }}
                            </span>
                        </div>
                        <p class="portal-record-copy">{{ $requestItem->message }}</p>
                        @if($requestItem->admin_notes)
                            <div class="portal-note">{{ $requestItem->admin_notes }}</div>
                        @endif
                    </article>
                @empty
                    <div class="portal-empty">
                        <div class="portal-empty-title">Aucune demande RH interne</div>
                        <div class="portal-empty-copy">Vos echanges RH apparaitront ici apres la premiere demande.</div>
                    </div>
                @endforelse
            </div>
        </section>
    </div>
@endsection
