@extends('superadmin.layouts.app')
@section('title', 'Edit: ' . $tenant->name)
@section('page-header', 'Edit Hospital')

@section('page-actions')
    <a href="{{ route('superadmin.hospitals.show', $tenant) }}" class="hms-btn hms-btn-secondary hms-btn-sm">
        <i class="bi bi-arrow-left"></i> Back
    </a>
@endsection

@section('content')
<div style="max-width:680px">
    <div class="hms-card">
        <div class="hms-card-header">
            <h3 class="hms-card-title">Edit Hospital: {{ $tenant->name }}</h3>
        </div>

        @include('superadmin.tenants._form', [
            'tenant' => $tenant,
            'action' => route('superadmin.hospitals.update', $tenant),
            'cancelUrl' => route('superadmin.hospitals.show', $tenant),
            'cancelLabel' => 'Cancel',
        ])
    </div>
</div>
@endsection
