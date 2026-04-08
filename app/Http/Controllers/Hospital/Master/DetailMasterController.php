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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DetailMasterController extends Controller
{
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
        ];
    }

    /** @return class-string */
    protected function resolveModel(string $type): string
    {
        $map = $this->modelMap();
        abort_if(! array_key_exists($type, $map), 404, 'Master type not found.');

        return $map[$type];
    }

    public function index(string $slug, string $type): View
    {
        $modelClass = $this->resolveModel($type);
        $instance = new $modelClass;
        $records = $modelClass::latest()->get();
        $columns = array_values(array_diff($instance->getFillable(), ['tenant_id']));
        $title = Str::headline($type);
        $routeGroup = 'hospital.masters.detail';

        return view('hospital.masters.dynamic_index',
            compact('records', 'columns', 'title', 'type', 'slug', 'routeGroup'));
    }

    public function store(Request $request, string $slug, string $type): RedirectResponse
    {
        $modelClass = $this->resolveModel($type);
        $columns = array_values(array_diff((new $modelClass)->getFillable(), ['tenant_id']));
        $validated = $request->validate(array_fill_keys($columns, ['required', 'string', 'max:255']));

        $modelClass::create($validated);

        return redirect()->back()->with('success', Str::headline($type).' added successfully.');
    }

    public function update(Request $request, string $slug, string $type, int $id): RedirectResponse
    {
        $modelClass = $this->resolveModel($type);
        $record = $modelClass::findOrFail($id);
        $columns = array_values(array_diff((new $modelClass)->getFillable(), ['tenant_id']));
        $validated = $request->validate(array_fill_keys($columns, ['required', 'string', 'max:255']));

        $record->update($validated);

        return redirect()->back()->with('success', Str::headline($type).' updated successfully.');
    }

    public function destroy(string $slug, string $type, int $id): RedirectResponse
    {
        $modelClass = $this->resolveModel($type);
        $modelClass::findOrFail($id)->delete();

        return redirect()->back()->with('success', Str::headline($type).' deleted successfully.');
    }
}
