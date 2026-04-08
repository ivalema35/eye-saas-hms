@extends('hospital.layouts.app')
@section('title', 'Hospital Settings')
@section('page-header', 'Hospital Settings')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0" style="color: var(--color-primary);">
        <i class="bi bi-gear-fill me-2"></i> Hospital Settings
    </h4>
</div>

<div class="card premium-card border-0 shadow-sm">
    <div class="card-header bg-white p-0 border-bottom">
        <ul class="nav nav-tabs px-3 pt-3 border-0" id="settingsTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active fw-bold px-4" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button" role="tab">
                    <i class="bi bi-building me-2"></i> Profile
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold px-4" id="billing-tab" data-bs-toggle="tab" data-bs-target="#billing" type="button" role="tab">
                    <i class="bi bi-receipt me-2"></i> Billing
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold px-4" id="print-tab" data-bs-toggle="tab" data-bs-target="#print" type="button" role="tab">
                    <i class="bi bi-printer me-2"></i> Print Settings
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold px-4" id="pagination-tab" data-bs-toggle="tab" data-bs-target="#pagination" type="button" role="tab">
                    <i class="bi bi-table me-2"></i> Pagination
                </button>
            </li>
        </ul>
    </div>

    <div class="card-body p-4 bg-light">
        <form action="{{ route('hospital.settings.update', ['slug' => $slug]) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="tab-content bg-white p-4 rounded-3 border shadow-sm" id="settingsTabsContent">

                <div class="tab-pane fade show active" id="profile" role="tabpanel">
                    <div class="row g-4">
                        <div class="col-md-12 mb-2 d-flex align-items-center gap-4 flex-wrap">
                            <div class="bg-light border rounded-3 d-flex align-items-center justify-content-center"
                                 style="width: 120px; height: 120px; overflow: hidden;">
                                @if(!empty($settings['hospital_logo']))
                                     <img src="{{ \Illuminate\Support\Facades\Storage::url($settings['hospital_logo']) }}"
                                         alt="Hospital Logo"
                                         class="img-fluid"
                                         style="max-height: 100%;">
                                @else
                                    <i class="bi bi-image text-muted fs-1"></i>
                                @endif
                            </div>
                            <div>
                                <label class="form-label fw-bold">Hospital Logo</label>
                                <input type="file" name="hospital_logo" class="form-control clinical-input @error('hospital_logo') is-invalid @enderror" accept="image/*">
                                <small class="text-muted d-block mt-1">Stored in storage/app/public/tenants/{tenant_id}/logo</small>
                                @error('hospital_logo')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Hospital Name <span class="text-danger">*</span></label>
                            <input type="text" name="hospital_name" class="form-control clinical-input @error('hospital_name') is-invalid @enderror"
                                   value="{{ old('hospital_name', $settings['hospital_name'] ?? '') }}" required>
                            @error('hospital_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contact Number <span class="text-danger">*</span></label>
                            <input type="text" name="hospital_phone" class="form-control clinical-input @error('hospital_phone') is-invalid @enderror"
                                   value="{{ old('hospital_phone', $settings['hospital_phone'] ?? '') }}" required>
                            @error('hospital_phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Official Email <span class="text-danger">*</span></label>
                            <input type="email" name="hospital_email" class="form-control clinical-input @error('hospital_email') is-invalid @enderror"
                                   value="{{ old('hospital_email', $settings['hospital_email'] ?? '') }}" required>
                            @error('hospital_email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Full Address <span class="text-danger">*</span></label>
                            <input type="text" name="hospital_address" class="form-control clinical-input @error('hospital_address') is-invalid @enderror"
                                   value="{{ old('hospital_address', $settings['hospital_address'] ?? '') }}" required>
                            @error('hospital_address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="billing" role="tabpanel">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label">Invoice Prefix <span class="text-danger">*</span></label>
                            <input type="text" name="invoice_prefix" class="form-control clinical-input @error('invoice_prefix') is-invalid @enderror"
                                   value="{{ old('invoice_prefix', $settings['invoice_prefix'] ?? 'INV-') }}" required>
                            @error('invoice_prefix')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Default Tax Percentage (%) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="tax_percentage" class="form-control clinical-input @error('tax_percentage') is-invalid @enderror"
                                   value="{{ old('tax_percentage', $settings['tax_percentage'] ?? '0') }}" required>
                            @error('tax_percentage')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="print" role="tabpanel">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label">Print Header Note</label>
                            <input type="text" name="print_header_note" class="form-control clinical-input @error('print_header_note') is-invalid @enderror"
                                   value="{{ old('print_header_note', $settings['print_header_note'] ?? '') }}"
                                   placeholder="e.g. Eye Care with Compassion">
                            @error('print_header_note')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Print Footer Note</label>
                            <input type="text" name="print_footer_note" class="form-control clinical-input @error('print_footer_note') is-invalid @enderror"
                                   value="{{ old('print_footer_note', $settings['print_footer_note'] ?? '') }}"
                                   placeholder="e.g. Thank you for your visit">
                            @error('print_footer_note')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="pagination" role="tabpanel">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label">Default Table Pagination (Per Page) <span class="text-danger">*</span></label>
                            @php $currentLimit = (string) old('pagination_limit', $settings['pagination_limit'] ?? '25'); @endphp
                            <select name="pagination_limit" class="form-select clinical-input @error('pagination_limit') is-invalid @enderror" required>
                                <option value="10" {{ $currentLimit === '10' ? 'selected' : '' }}>10 Records</option>
                                <option value="25" {{ $currentLimit === '25' ? 'selected' : '' }}>25 Records</option>
                                <option value="50" {{ $currentLimit === '50' ? 'selected' : '' }}>50 Records</option>
                                <option value="100" {{ $currentLimit === '100' ? 'selected' : '' }}>100 Records</option>
                            </select>
                            @error('pagination_limit')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

            </div>

            <div class="mt-4 text-end">
                <button type="submit" class="btn btn-primary px-5 fw-bold">
                    <i class="bi bi-save me-2"></i> Save All Settings
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
