<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hospital\ChiefComplaint;
use App\Models\Hospital\Duration;
use App\Models\Hospital\Kco;
use App\Models\Hospital\MasterAc;
use App\Models\Hospital\MasterAdvice;
use App\Models\Hospital\MasterAxis;
use App\Models\Hospital\MasterConj;
use App\Models\Hospital\MasterCornea;
use App\Models\Hospital\MasterCoverTest;
use App\Models\Hospital\MasterDiagnosis;
use App\Models\Hospital\MasterDisc;
use App\Models\Hospital\MasterEm;
use App\Models\Hospital\MasterFr;
use App\Models\Hospital\MasterHno;
use App\Models\Hospital\MasterIris;
use App\Models\Hospital\MasterLens;
use App\Models\Hospital\MasterLid;
use App\Models\Hospital\MasterNct;
use App\Models\Hospital\MasterNrvn;
use App\Models\Hospital\MasterPnvn;
use App\Models\Hospital\MasterPupil;
use App\Models\Hospital\MasterSac;
use App\Models\Hospital\MasterSphCyl;
use App\Models\Hospital\MasterVn;
use App\Models\Hospital\MasterVngl;
use App\Models\Hospital\MasterVnst;
use App\Models\Hospital\CaseType;
use App\Models\Hospital\Referrer;
use App\Models\Hospital\OT\OtChargeHead;
use App\Models\Hospital\OT\OtLensOption;
use App\Models\Hospital\OT\OtSlot;
use App\Support\PhoneRules;
use App\Models\Hospital\OT\OtSurgeryType;
use App\Models\Hospital\OT\OtType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MasterApiController extends Controller
{
    // ── Type → Model map ──────────────────────────────────────────────────────

    private function detailMap(): array
    {
        return [
            // Clinical
            'complaints' => ChiefComplaint::class,
            'chief-complaints' => ChiefComplaint::class,
            'kcos' => Kco::class,
            'hno' => MasterHno::class,
            'diagnoses' => MasterDiagnosis::class,
            'diagnosis' => MasterDiagnosis::class,
            'advices' => MasterAdvice::class,
            'advice' => MasterAdvice::class,
            // Vision
            'vn' => MasterVn::class,
            'vngl' => MasterVngl::class,
            'vnst' => MasterVnst::class,
            'pnvn' => MasterPnvn::class,
            'nrvn' => MasterNrvn::class,
            'sph_cyl' => MasterSphCyl::class,
            'axis' => MasterAxis::class,
            'nct' => MasterNct::class,
            // Posterior
            'disc' => MasterDisc::class,
            'fr' => MasterFr::class,
            // Anterior
            'sac' => MasterSac::class,
            'lid' => MasterLid::class,
            'conj' => MasterConj::class,
            'cornea' => MasterCornea::class,
            'ac' => MasterAc::class,
            'iris' => MasterIris::class,
            'pupil' => MasterPupil::class,
            'lens' => MasterLens::class,
            'em' => MasterEm::class,
            'covertest' => MasterCoverTest::class,
            // Basic masters
            'durations' => Duration::class,
            // OT masters
            'lens-options' => OtLensOption::class,
            'ot-types' => OtType::class,
        ];
    }

    /**
     * Some models use a different DB column name than `value`.
     * Map type → actual column name used as the display value.
     */
    private function valueField(string $type): string
    {
        return match ($type) {
            'durations' => 'duration',
            'lens-options' => 'name',
            'ot-types' => 'name',
            default => 'value',
        };
    }

    private function hasFavourite(string $type): bool
    {
        return in_array($type, [
            'complaints',
            'chief-complaints',
            'kcos',
            'hno',
            'diagnoses',
            'diagnosis',
            'advices',
            'advice',
            'sac',
            'lid',
            'conj',
            'cornea',
            'ac',
            'iris',
            'pupil',
            'lens',
            'em',
            'covertest',
            'disc',
            'fr',
        ], true);
    }

    private function resolveDetail(string $type): string
    {
        $map = $this->detailMap();
        abort_if(!array_key_exists($type, $map), 404, "Master type '$type' not found.");
        return $map[$type];
    }

    private function ok(mixed $data, string $message = 'OK', int $code = 200): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $data, 'message' => $message], $code);
    }

    private function err(string $message, int $code = 422): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $message], $code);
    }

    private function isSeeded(object $record): bool
    {
        try {
            return isset($record->is_seeded) && (bool) $record->is_seeded;
        } catch (\Throwable) {
            return false;
        }
    }

    // ── CRUD ──────────────────────────────────────────────────────────────────

    public function detailIndex(string $slug, string $type): JsonResponse
    {
        $model = $this->resolveDetail($type);
        $hasFav = $this->hasFavourite($type);
        $valField = $this->valueField($type);

        $query = $model::query();
        $hasFav
            ? $query->orderByDesc('is_favourite')->orderBy($valField)
            : $query->orderBy('id'); // preserve seeder insertion order (clinical/numeric order)

        $data = $query->get()->map(fn($r) => [
            'id' => $r->id,
            'value' => $r->{$valField},
            'is_favourite' => $hasFav ? (bool) $r->is_favourite : null,
            'is_seeded' => $this->isSeeded($r),
        ]);

        return $this->ok($data);
    }

    public function detailStore(Request $request, string $slug, string $type): JsonResponse
    {
        $model = $this->resolveDetail($type);
        $hasFav = $this->hasFavourite($type);
        $valField = $this->valueField($type);

        $request->validate(['value' => ['required', 'string', 'max:255']]);

        $payload = [$valField => trim($request->string('value'))];
        if ($hasFav) {
            $payload['is_favourite'] = $request->boolean('is_favourite', false);
        }

        $record = $model::create($payload);

        return $this->ok([
            'id' => $record->id,
            'value' => $record->{$valField},
            'is_favourite' => $hasFav ? (bool) $record->is_favourite : null,
            'is_seeded' => false,
        ], 'Added successfully.', 201);
    }

    public function detailUpdate(Request $request, string $slug, string $type, int $id): JsonResponse
    {
        $model = $this->resolveDetail($type);
        $hasFav = $this->hasFavourite($type);
        $valField = $this->valueField($type);
        $record = $model::findOrFail($id);

        if ($this->isSeeded($record)) {
            return $this->err('Seeded values cannot be edited.', 403);
        }

        $request->validate(['value' => ['required', 'string', 'max:255']]);

        $payload = [$valField => trim($request->string('value'))];
        if ($hasFav) {
            $payload['is_favourite'] = $request->boolean('is_favourite', (bool) $record->is_favourite);
        }

        $record->update($payload);

        return $this->ok([
            'id' => $record->id,
            'value' => $record->{$valField},
            'is_favourite' => $hasFav ? (bool) $record->is_favourite : null,
            'is_seeded' => false,
        ]);
    }

    public function detailDestroy(string $slug, string $type, int $id): JsonResponse
    {
        $model = $this->resolveDetail($type);
        $record = $model::findOrFail($id);

        if ($this->isSeeded($record)) {
            return $this->err('Seeded values cannot be deleted.', 403);
        }

        $record->delete();

        return $this->ok(null, 'Deleted successfully.');
    }

    public function detailToggleFavourite(string $slug, string $type, int $id): JsonResponse
    {
        abort_unless($this->hasFavourite($type), 404);

        $record = $this->resolveDetail($type)::findOrFail($id);
        $record->update(['is_favourite' => !$record->is_favourite]);

        return $this->ok(['is_favourite' => (bool) $record->is_favourite]);
    }

    // ── Case Types ────────────────────────────────────────────────────────────

    public function caseTypeIndex(string $slug): JsonResponse
    {
        $data = CaseType::query()->orderBy('case_type')->get()
            ->map(fn($r) => [
                'id' => $r->id,
                'case_type' => $r->case_type,
                'case_fee' => (float) $r->case_fee,
            ]);

        return $this->ok($data);
    }

    public function caseTypeStore(Request $request, string $slug): JsonResponse
    {
        $request->validate([
            'case_type' => ['required', 'string', 'max:255'],
            'case_fee' => ['required', 'numeric', 'min:0'],
        ]);

        $record = CaseType::create([
            'case_type' => trim($request->string('case_type')),
            'case_fee' => $request->input('case_fee'),
        ]);

        return $this->ok([
            'id' => $record->id,
            'case_type' => $record->case_type,
            'case_fee' => (float) $record->case_fee,
        ], 'Case type added.', 201);
    }

    public function caseTypeUpdate(Request $request, string $slug, int $id): JsonResponse
    {
        $request->validate([
            'case_type' => ['required', 'string', 'max:255'],
            'case_fee' => ['required', 'numeric', 'min:0'],
        ]);

        $record = CaseType::findOrFail($id);
        $record->update([
            'case_type' => trim($request->string('case_type')),
            'case_fee' => $request->input('case_fee'),
        ]);

        return $this->ok([
            'id' => $record->id,
            'case_type' => $record->case_type,
            'case_fee' => (float) $record->case_fee,
        ]);
    }

    public function caseTypeDestroy(string $slug, int $id): JsonResponse
    {
        CaseType::findOrFail($id)->delete();

        return $this->ok(null, 'Case type deleted.');
    }

    // ── Referrers ─────────────────────────────────────────────────────────────

    public function referrerIndex(string $slug): JsonResponse
    {
        $data = Referrer::query()->orderBy('name')->get()
            ->map(fn($r) => [
                'id' => $r->id,
                'name' => $r->name,
                'contact' => $r->contact,
            ]);

        return $this->ok($data);
    }

    public function referrerStore(Request $request, string $slug): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact' => PhoneRules::nullable(),
        ]);

        $record = Referrer::create([
            'name' => trim($request->string('name')),
            'contact' => $request->filled('contact') ? trim($request->string('contact')) : null,
        ]);

        return $this->ok([
            'id' => $record->id,
            'name' => $record->name,
            'contact' => $record->contact,
        ], 'Referrer added.', 201);
    }

    public function referrerUpdate(Request $request, string $slug, int $id): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact' => PhoneRules::nullable(),
        ]);

        $record = Referrer::findOrFail($id);
        $record->update([
            'name' => trim($request->string('name')),
            'contact' => $request->filled('contact') ? trim($request->string('contact')) : null,
        ]);

        return $this->ok([
            'id' => $record->id,
            'name' => $record->name,
            'contact' => $record->contact,
        ]);
    }

    public function referrerDestroy(string $slug, int $id): JsonResponse
    {
        Referrer::findOrFail($id)->delete();

        return $this->ok(null, 'Referrer deleted.');
    }

    // ── OT Slots ──────────────────────────────────────────────────────────────

    public function otSlotIndex(string $slug): JsonResponse
    {
        $data = OtSlot::query()->orderBy('slot_name')->get()
            ->map(fn($r) => [
                'id' => $r->id,
                'slot_name' => $r->slot_name,
                'start_time' => $r->start_time,
                'end_time' => $r->end_time,
            ]);

        return $this->ok($data);
    }

    public function otSlotStore(Request $request, string $slug): JsonResponse
    {
        $request->validate([
            'slot_name'  => ['required', 'string', 'max:255'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time'   => ['required', 'date_format:H:i', 'after:start_time'],
        ]);

        $record = OtSlot::create([
            'slot_name' => trim($request->string('slot_name')),
            'start_time' => $request->input('start_time'),
            'end_time' => $request->input('end_time'),
        ]);

        return $this->ok([
            'id' => $record->id,
            'slot_name' => $record->slot_name,
            'start_time' => $record->start_time,
            'end_time' => $record->end_time,
        ], 'OT slot added.', 201);
    }

    public function otSlotUpdate(Request $request, string $slug, int $id): JsonResponse
    {
        $request->validate([
            'slot_name'  => ['required', 'string', 'max:255'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time'   => ['required', 'date_format:H:i', 'after:start_time'],
        ]);

        $record = OtSlot::findOrFail($id);
        $record->update([
            'slot_name' => trim($request->string('slot_name')),
            'start_time' => $request->input('start_time'),
            'end_time' => $request->input('end_time'),
        ]);

        return $this->ok([
            'id' => $record->id,
            'slot_name' => $record->slot_name,
            'start_time' => $record->start_time,
            'end_time' => $record->end_time,
        ]);
    }

    public function otSlotDestroy(string $slug, int $id): JsonResponse
    {
        OtSlot::findOrFail($id)->delete();

        return $this->ok(null, 'OT slot deleted.');
    }

    // ── OT Charge Heads ───────────────────────────────────────────────────────

    public function otChargeHeadIndex(string $slug): JsonResponse
    {
        $data = OtChargeHead::query()->orderBy('charge_name')->get()
            ->map(fn($r) => [
                'id' => $r->id,
                'charge_name' => $r->charge_name,
                'percentage' => (float) $r->percentage,
                'is_active' => (bool) $r->is_active,
            ]);

        return $this->ok($data);
    }

    public function otChargeHeadStore(Request $request, string $slug): JsonResponse
    {
        $request->validate([
            'charge_name' => ['required', 'string', 'max:255'],
            'percentage' => ['required', 'numeric', 'between:0,100'],
            'is_active' => ['boolean'],
        ]);

        $record = OtChargeHead::create([
            'charge_name' => trim($request->string('charge_name')),
            'percentage' => $request->input('percentage'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return $this->ok([
            'id' => $record->id,
            'charge_name' => $record->charge_name,
            'percentage' => (float) $record->percentage,
            'is_active' => (bool) $record->is_active,
        ], 'Charge head added.', 201);
    }

    public function otChargeHeadUpdate(Request $request, string $slug, int $id): JsonResponse
    {
        $request->validate([
            'charge_name' => ['required', 'string', 'max:255'],
            'percentage' => ['required', 'numeric', 'between:0,100'],
            'is_active' => ['boolean'],
        ]);

        $record = OtChargeHead::findOrFail($id);
        $record->update([
            'charge_name' => trim($request->string('charge_name')),
            'percentage' => $request->input('percentage'),
            'is_active' => $request->boolean('is_active', (bool) $record->is_active),
        ]);

        return $this->ok([
            'id' => $record->id,
            'charge_name' => $record->charge_name,
            'percentage' => (float) $record->percentage,
            'is_active' => (bool) $record->is_active,
        ]);
    }

    public function otChargeHeadDestroy(string $slug, int $id): JsonResponse
    {
        OtChargeHead::findOrFail($id)->delete();

        return $this->ok(null, 'Charge head deleted.');
    }

    // ── OT Lens Options ───────────────────────────────────────────────────────
    // NOTE: this data was already reachable read/write via the generic
    // masters/detail/{type} mechanism (detailMap() has 'lens-options' =>
    // OtLensOption::class) — but gated by permission:master.eye_exam, the wrong
    // permission (web gates this under role:admin, alongside every other OT
    // master). Found during a full app-parity audit (2026-08-04). These dedicated,
    // correctly-permissioned methods are the fix — mirrors the otSlot*/
    // otChargeHead* pattern exactly. The generic detail/{type} route is left as-is
    // (untouched, no behavior change) since removing it could break something else
    // already relying on it.

    public function otLensOptionIndex(string $slug): JsonResponse
    {
        $data = OtLensOption::query()
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get()
            ->map(fn ($r) => ['id' => $r->id, 'name' => $r->name, 'is_active' => (bool) $r->is_active]);

        return $this->ok($data);
    }

    public function otLensOptionStore(Request $request, string $slug): JsonResponse
    {
        $tenantId = (int) app('tenant')->id;

        $request->validate([
            'name' => [
                'required', 'string', 'max:150',
                Rule::unique('ot_lens_options', 'name')
                    ->where(fn ($q) => $q->where('tenant_id', $tenantId)->whereNull('deleted_at')),
            ],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $record = OtLensOption::create([
            'tenant_id' => $tenantId,
            'name' => trim($request->string('name')),
            'is_active' => $request->boolean('is_active', false),
        ]);

        return $this->ok(['id' => $record->id, 'name' => $record->name, 'is_active' => (bool) $record->is_active], 'Lens option added.', 201);
    }

    public function otLensOptionUpdate(Request $request, string $slug, int $id): JsonResponse
    {
        $tenantId = (int) app('tenant')->id;
        $record = OtLensOption::query()->whereNull('deleted_at')->findOrFail($id);

        $request->validate([
            'name' => [
                'required', 'string', 'max:150',
                Rule::unique('ot_lens_options', 'name')
                    ->ignore($record->id)
                    ->where(fn ($q) => $q->where('tenant_id', $tenantId)->whereNull('deleted_at')),
            ],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $record->update([
            'name' => trim($request->string('name')),
            'is_active' => $request->boolean('is_active', (bool) $record->is_active),
        ]);

        return $this->ok(['id' => $record->id, 'name' => $record->name, 'is_active' => (bool) $record->is_active]);
    }

    public function otLensOptionDestroy(string $slug, int $id): JsonResponse
    {
        OtLensOption::query()->whereNull('deleted_at')->findOrFail($id)->update([
            'deleted_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->ok(null, 'Lens option deleted.');
    }

    // ── OT Type (broad category, e.g. Cataract/Squint) CRUD ─────────────────────
    // Same permission-mismatch fix as OT Lens Options above — otTypesList() below
    // (pre-existing) is a read-only dropdown helper and stays as-is; these are new.

    public function otTypeIndex(string $slug): JsonResponse
    {
        $data = OtType::query()
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get()
            ->map(fn ($r) => ['id' => $r->id, 'name' => $r->name]);

        return $this->ok($data);
    }

    public function otTypeStore(Request $request, string $slug): JsonResponse
    {
        $tenantId = (int) app('tenant')->id;

        $request->validate([
            'name' => [
                'required', 'string', 'max:150',
                Rule::unique('ot_types', 'name')
                    ->where(fn ($q) => $q->where('tenant_id', $tenantId)->whereNull('deleted_at')),
            ],
        ]);

        $record = OtType::create(['tenant_id' => $tenantId, 'name' => trim($request->string('name'))]);

        return $this->ok(['id' => $record->id, 'name' => $record->name], 'OT type added.', 201);
    }

    public function otTypeUpdate(Request $request, string $slug, int $id): JsonResponse
    {
        $tenantId = (int) app('tenant')->id;
        $record = OtType::query()->whereNull('deleted_at')->findOrFail($id);

        $request->validate([
            'name' => [
                'required', 'string', 'max:150',
                Rule::unique('ot_types', 'name')
                    ->ignore($record->id)
                    ->where(fn ($q) => $q->where('tenant_id', $tenantId)->whereNull('deleted_at')),
            ],
        ]);

        $record->update(['name' => trim($request->string('name'))]);

        return $this->ok(['id' => $record->id, 'name' => $record->name]);
    }

    public function otTypeDestroy(string $slug, int $id): JsonResponse
    {
        OtType::query()->whereNull('deleted_at')->findOrFail($id)->update([
            'deleted_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->ok(null, 'OT type deleted.');
    }

    // ── OT Surgery Types ──────────────────────────────────────────────────────

    public function otTypesList(string $slug): JsonResponse
    {
        $data = OtType::query()->orderBy('name')->get()
            ->map(fn($r) => ['id' => $r->id, 'name' => $r->name]);

        return $this->ok($data);
    }

    public function otSurgeryTypeIndex(string $slug): JsonResponse
    {
        $data = OtSurgeryType::query()
            ->with('otType')
            ->orderBy('surgery_name')
            ->get()
            ->map(fn($r) => [
                'id' => $r->id,
                'ot_type_id' => $r->ot_type_id,
                'ot_type_name' => $r->otType?->name ?? '',
                'surgery_name' => $r->surgery_name,
            ]);

        return $this->ok($data);
    }

    public function otSurgeryTypeStore(Request $request, string $slug): JsonResponse
    {
        $request->validate([
            'ot_type_id' => ['required', 'integer', 'exists:ot_types,id'],
            'surgery_name' => ['required', 'string', 'max:255'],
        ]);

        $record = OtSurgeryType::create([
            'ot_type_id' => $request->integer('ot_type_id'),
            'surgery_name' => trim($request->string('surgery_name')),
        ]);

        $record->load('otType');

        return $this->ok([
            'id' => $record->id,
            'ot_type_id' => $record->ot_type_id,
            'ot_type_name' => $record->otType?->name ?? '',
            'surgery_name' => $record->surgery_name,
        ], 'Surgery type added.', 201);
    }

    public function otSurgeryTypeUpdate(Request $request, string $slug, int $id): JsonResponse
    {
        $request->validate([
            'ot_type_id' => ['required', 'integer', 'exists:ot_types,id'],
            'surgery_name' => ['required', 'string', 'max:255'],
        ]);

        $record = OtSurgeryType::findOrFail($id);
        $record->update([
            'ot_type_id' => $request->integer('ot_type_id'),
            'surgery_name' => trim($request->string('surgery_name')),
        ]);

        $record->load('otType');

        return $this->ok([
            'id' => $record->id,
            'ot_type_id' => $record->ot_type_id,
            'ot_type_name' => $record->otType?->name ?? '',
            'surgery_name' => $record->surgery_name,
        ]);
    }

    public function otSurgeryTypeDestroy(string $slug, int $id): JsonResponse
    {
        OtSurgeryType::findOrFail($id)->delete();

        return $this->ok(null, 'Surgery type deleted.');
    }
}
