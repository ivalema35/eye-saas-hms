<?php

namespace App\Http\Controllers\Hospital\Master;

use App\Http\Controllers\Controller;
use App\Models\Hospital\ChiefComplaint;
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
use App\Services\Auth\RolePermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DetailMasterController extends Controller
{
    public function __construct(private readonly RolePermissionService $perm) {}

    /**
     * Maps URL {type} slugs to their Eloquent model classes.
     *
     * @return array<string, class-string>
     */
    protected function modelMap(): array
    {
        return [
            'complaints' => ChiefComplaint::class,
            'chief-complaints' => ChiefComplaint::class,
            'kcos' => Kco::class,
            'diagnosis' => MasterDiagnosis::class,
            'diagnoses' => MasterDiagnosis::class,
            'advice' => MasterAdvice::class,
            'advices' => MasterAdvice::class,
            'vn' => MasterVn::class,
            'vngl' => MasterVngl::class,
            'vnst' => MasterVnst::class,
            'pnvn' => MasterPnvn::class,
            'nrvn' => MasterNrvn::class,
            'sph_cyl' => MasterSphCyl::class,
            'axis' => MasterAxis::class,
            'nct' => MasterNct::class,
            'disc' => MasterDisc::class,
            'fr' => MasterFr::class,
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
            'hno' => MasterHno::class,
        ];
    }

    /** @return class-string */
    protected function resolveModel(string $type): string
    {
        $map = $this->modelMap();
        abort_if(!array_key_exists($type, $map), 404, 'Master type not found.');

        return $map[$type];
    }

    private function isAdviceType(string $type): bool
    {
        return in_array($type, ['advice', 'advices'], true);
    }

    private function isComplaintType(string $type): bool
    {
        return in_array($type, ['complaints', 'chief-complaints'], true);
    }

    private function isKcoType(string $type): bool
    {
        return in_array($type, ['kcos'], true);
    }

    private function hasFavourite(string $type): bool
    {
        return $this->isComplaintType($type)
            || $this->isKcoType($type)
            || in_array($type, ['hno'], true)
            || in_array($type, ['sac', 'lid', 'conj', 'cornea', 'ac', 'iris', 'pupil', 'lens', 'em', 'covertest'], true)
            || in_array($type, ['disc', 'fr'], true)
            || in_array($type, ['diagnosis', 'diagnoses', 'advice', 'advices'], true);
    }

    /**
     * Maps URL {type} slugs to their per-card permission feature key
     * (see eye_exam_master_types() in config/permission_matrix.php).
     */
    private function featureKeyForType(string $type): string
    {
        return match ($type) {
            'complaints', 'chief-complaints' => 'eye_exam_chief_complaints',
            'kcos' => 'eye_exam_kco',
            'hno' => 'eye_exam_hno',
            'diagnosis', 'diagnoses' => 'eye_exam_diagnosis',
            'advice', 'advices' => 'eye_exam_advice',
            'vn' => 'eye_exam_vn',
            'vngl' => 'eye_exam_vngl',
            'vnst' => 'eye_exam_vnst',
            'pnvn' => 'eye_exam_pnvn',
            'nrvn' => 'eye_exam_nrvn',
            'sph_cyl' => 'eye_exam_sph_cyl',
            'axis' => 'eye_exam_axis',
            'nct' => 'eye_exam_nct',
            'disc' => 'eye_exam_disc',
            'fr' => 'eye_exam_fr',
            'sac' => 'eye_exam_sac',
            'lid' => 'eye_exam_lid',
            'conj' => 'eye_exam_conj',
            'cornea' => 'eye_exam_cornea',
            'ac' => 'eye_exam_ac',
            'iris' => 'eye_exam_iris',
            'pupil' => 'eye_exam_pupil',
            'lens' => 'eye_exam_lens',
            'em' => 'eye_exam_em',
            'covertest' => 'eye_exam_covertest',
            default => abort(404, 'Master type not found.'),
        };
    }

    private function authorizeType(string $type, string $action): void
    {
        $feature = $this->featureKeyForType($type);
        abort_unless($this->perm->can("{$feature}_{$action}"), 403, 'Access denied.');
    }

    private function authorizeAnyType(string $type): void
    {
        $feature = $this->featureKeyForType($type);
        abort_unless(
            $this->perm->canAny(["{$feature}_view", "{$feature}_add", "{$feature}_edit", "{$feature}_delete"]),
            403,
            'Access denied.'
        );
    }

    public function index(string $slug, string $type): View
    {
        $this->authorizeAnyType($type);

        $modelClass = $this->resolveModel($type);
        $instance = new $modelClass;

        $excludeCols = ['tenant_id', 'diagnosis_id', 'is_favourite'];
        $columns = array_values(array_diff($instance->getFillable(), $excludeCols));
        $titleOverrides = [
            'hno' => 'H/O',
            'kcos' => 'K/C/O',
            'vn' => 'V/N',
            'vngl' => 'Vn C GL',
            'vnst' => 'Vn C ST',
            'pnvn' => 'PH NV/N',
            'nrvn' => 'NR V/N',
            'sph_cyl' => 'SPH / CYL',
            'nct' => 'NCT (IOP)',
        ];
        $title = $titleOverrides[$type] ?? Str::headline($type);
        $routeGroup = 'hospital.masters.detail';

        $withRelations = $this->isAdviceType($type) ? ['diagnoses'] : [];
        $query = $modelClass::with($withRelations);
        if ($this->hasFavourite($type)) {
            $query->orderByDesc('is_favourite')->orderBy('value');
        } else {
            $query->latest();
        }
        $records = $query->get();
        $diagnoses = $this->isAdviceType($type) ? MasterDiagnosis::orderBy('value')->get() : collect();

        $feature = $this->featureKeyForType($type);
        $canAdd = $this->perm->can("{$feature}_add");
        $canEdit = $this->perm->can("{$feature}_edit");
        $canDelete = $this->perm->can("{$feature}_delete");
        $canWrite = $canAdd || $canEdit || $canDelete;

        return view(
            'hospital.masters.dynamic_index',
            compact('records', 'columns', 'title', 'type', 'slug', 'routeGroup', 'diagnoses', 'canWrite', 'canAdd', 'canEdit', 'canDelete')
        );
    }

    public function store(Request $request, string $slug, string $type): RedirectResponse
    {
        $this->authorizeType($type, 'add');

        $modelClass = $this->resolveModel($type);
        $excludeCols = ['tenant_id', 'diagnosis_id', 'is_favourite'];
        $columns = array_values(array_diff((new $modelClass)->getFillable(), $excludeCols));
        $validated = $request->validate(array_fill_keys($columns, ['required', 'string', 'max:255']));

        if ($this->hasFavourite($type)) {
            $validated['is_favourite'] = $request->boolean('is_favourite');
        }

        $record = $modelClass::create($validated);

        if ($this->isAdviceType($type)) {
            $request->validate([
                'diagnosis_ids' => ['nullable', 'array'],
                'diagnosis_ids.*' => ['exists:tbl_master_diagnosis,id'],
            ]);
            $record->diagnoses()->sync($request->input('diagnosis_ids', []));
        }

        return redirect()->back()->with('success', Str::headline($type) . ' added successfully.');
    }

    public function update(Request $request, string $slug, string $type, int $id): RedirectResponse
    {
        $this->authorizeType($type, 'edit');

        $modelClass = $this->resolveModel($type);
        $record = $modelClass::findOrFail($id);

        if (property_exists($record, 'casts') && isset($record->getCasts()['is_seeded']) && $record->is_seeded) {
            return redirect()->back()->with('error', 'Default seeded values cannot be edited.');
        }
        $excludeCols = ['tenant_id', 'diagnosis_id', 'is_favourite'];
        $columns = array_values(array_diff((new $modelClass)->getFillable(), $excludeCols));
        $validated = $request->validate(array_fill_keys($columns, ['required', 'string', 'max:255']));

        if ($this->hasFavourite($type)) {
            $validated['is_favourite'] = $request->boolean('is_favourite');
        }

        $record->update($validated);

        if ($this->isAdviceType($type)) {
            $request->validate([
                'diagnosis_ids' => ['nullable', 'array'],
                'diagnosis_ids.*' => ['exists:tbl_master_diagnosis,id'],
            ]);
            $record->diagnoses()->sync($request->input('diagnosis_ids', []));
        }

        return redirect()->back()->with('success', Str::headline($type) . ' updated successfully.');
    }

    public function toggleFavourite(string $slug, string $type, int $id)
    {
        abort_unless($this->hasFavourite($type), 404);
        $this->authorizeType($type, 'edit');

        $record = $this->resolveModel($type)::findOrFail($id);
        $record->update(['is_favourite' => !$record->is_favourite]);

        return response()->json(['is_favourite' => $record->is_favourite]);
    }

    public function syncByDiagnosis(Request $request, string $slug, string $type): RedirectResponse
    {
        abort_unless($this->isAdviceType($type), 404);
        $this->authorizeType($type, 'edit');

        $request->validate([
            'link_diagnosis_id' => ['required', 'exists:tbl_master_diagnosis,id'],
            'link_advice_ids' => ['nullable', 'array'],
            'link_advice_ids.*' => ['exists:tbl_master_advice,id'],
        ]);

        $diagnosisId = (int) $request->link_diagnosis_id;
        $selectedIds = array_map('intval', $request->input('link_advice_ids', []));
        $tenantAdviceIds = MasterAdvice::pluck('id')->toArray();

        DB::table('tbl_advice_diagnoses')
            ->where('diagnosis_id', $diagnosisId)
            ->whereIn('advice_id', $tenantAdviceIds)
            ->delete();

        $validIds = array_values(array_intersect($selectedIds, $tenantAdviceIds));
        if (!empty($validIds)) {
            DB::table('tbl_advice_diagnoses')->insert(
                array_map(fn($id) => ['advice_id' => $id, 'diagnosis_id' => $diagnosisId], $validIds)
            );
        }

        return redirect()->back()->with('success', 'Advice links updated successfully.');
    }

    public function destroy(string $slug, string $type, int $id): RedirectResponse
    {
        $this->authorizeType($type, 'delete');

        $modelClass = $this->resolveModel($type);
        $record = $modelClass::findOrFail($id);

        if (isset($record->getCasts()['is_seeded']) && $record->is_seeded) {
            return redirect()->back()->with('error', 'Default seeded values cannot be deleted.');
        }

        $record->delete();

        return redirect()->back()->with('success', Str::headline($type) . ' deleted successfully.');
    }
}
