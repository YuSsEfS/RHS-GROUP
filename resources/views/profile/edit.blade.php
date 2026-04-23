@extends('layouts.app')

@section('title', 'Profile')

@section('content')
<div class="container" style="padding:60px 0;">
    <h1 style="font-size:32px;font-weight:800;margin-bottom:18px;">Profile</h1>

    @if (session('status') === 'profile-updated')
        <div style="background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46;padding:12px 16px;border-radius:12px;margin-bottom:18px;">
            Profile updated successfully.
        </div>
    @endif

    <form method="POST" action="{{ route('profile.update') }}" style="max-width:520px;">
        @csrf
        @method('PATCH')

        <div style="margin-bottom:14px;">
            <label style="display:block;font-weight:700;margin-bottom:6px;">Name</label>
            <input name="name" value="{{ old('name', $user->name) }}" required
                   style="width:100%;padding:12px 14px;border-radius:12px;border:1px solid #e5e7eb;">
            @error('name') <div style="color:#dc2626;margin-top:6px;">{{ $message }}</div> @enderror
        </div>

        <div style="margin-bottom:18px;">
            <label style="display:block;font-weight:700;margin-bottom:6px;">Email</label>
            <input name="email" value="{{ old('email', $user->email) }}" required
                   style="width:100%;padding:12px 14px;border-radius:12px;border:1px solid #e5e7eb;">
            @error('email') <div style="color:#dc2626;margin-top:6px;">{{ $message }}</div> @enderror
        </div>

        <button type="submit"
                style="background:#e23b31;color:#fff;border:none;padding:12px 18px;border-radius:12px;font-weight:800;cursor:pointer;">
            Save
        </button>
    </form>
</div>
@endsection
