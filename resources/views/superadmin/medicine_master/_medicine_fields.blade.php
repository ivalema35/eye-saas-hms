@php $prefix = $prefix ?? ''; @endphp
<div class="row g-3">
    <div class="col-md-6">
        <label class="hms-label">Medicine Type <span style="color:#E53E3E">*</span></label>
        <select name="master_medicine_type_id" id="{{ $prefix }}MedicineType" class="hms-select" required>
            <option value="">— Select Type —</option>
            @foreach($allTypes as $t)
                <option value="{{ $t->id }}">{{ $t->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="hms-label">Medicine Name <span style="color:#E53E3E">*</span></label>
        <input type="text" name="name" id="{{ $prefix }}MedicineName" class="hms-input" placeholder="e.g. Moxifloxacin" @if($prefix === '') required @else required @endif>
    </div>
    <div class="col-md-6">
        <label class="hms-label">Medicine Dosage <span style="color:#E53E3E">*</span></label>
        <select name="master_dosage_id" id="{{ $prefix }}MedicineDosage" class="hms-select" required>
            <option value="">— Select Dosage —</option>
            @foreach($allDosages as $d)
                <option value="{{ $d->id }}">{{ $d->dosage }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="hms-label">Duration</label>
        <input type="text" name="duration" id="{{ $prefix }}MedicineDuration" class="hms-input" placeholder="e.g. 7 days">
    </div>
    <div class="col-md-6">
        <label class="hms-label">Medicine Qty</label>
        <input type="text" name="qty" id="{{ $prefix }}MedicineQty" class="hms-input" placeholder="e.g. 1 bottle">
    </div>
    <div class="col-md-6">
        <label class="hms-label">Price</label>
        <input type="number" step="0.01" min="0" name="price" id="{{ $prefix }}MedicinePrice" class="hms-input" placeholder="e.g. 120.00">
    </div>
    <div class="col-md-6">
        <label class="hms-label">Company</label>
        <input type="text" name="company" id="{{ $prefix }}MedicineCompany" class="hms-input" placeholder="e.g. Sun Pharma">
    </div>
    <div class="col-12">
        <label class="hms-label">Composition</label>
        <textarea name="composition" id="{{ $prefix }}MedicineComposition" class="hms-input" rows="2" placeholder="e.g. Moxifloxacin 0.5% w/v"></textarea>
    </div>
</div>
