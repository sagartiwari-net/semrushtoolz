<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePlanRequest;
use App\Http\Requests\Admin\UpdatePlanRequest;
use App\Models\Plan;
use App\Models\Tool;
use App\Services\AdminUserQueryService;

class PlanController extends Controller
{
    public function __construct(
        protected AdminUserQueryService $userQueries,
    ) {}

    public function index()
    {
        $plans = Plan::with('tools')->orderBy('sort_order')->get();
        $subscriberCounts = $this->userQueries->planSubscriberCounts();

        return view('admin.plans.index', compact('plans', 'subscriberCounts'));
    }

    public function create()
    {
        return view('admin.plans.form', [
            'plan' => new Plan(['is_active' => true, 'show_on_homepage' => true, 'display_group' => 'main']),
            'tools' => Tool::orderBy('sort_order')->get(),
        ]);
    }

    public function store(StorePlanRequest $request)
    {
        $plan = $this->savePlan(new Plan, $request);

        return redirect()->route('admin.plans.index')
            ->with('success', "Plan \"{$plan->name}\" created.");
    }

    public function edit(Plan $plan)
    {
        $plan->load('tools');

        return view('admin.plans.form', [
            'plan' => $plan,
            'tools' => Tool::orderBy('sort_order')->get(),
        ]);
    }

    public function update(UpdatePlanRequest $request, Plan $plan)
    {
        $plan = $this->savePlan($plan, $request);

        return redirect()->route('admin.plans.index')
            ->with('success', "Plan \"{$plan->name}\" updated.");
    }

    public function toggle(Plan $plan)
    {
        $plan->update(['is_active' => ! $plan->is_active]);

        return back()->with('success', 'Plan status updated.');
    }

    protected function savePlan(Plan $plan, StorePlanRequest|UpdatePlanRequest $request): Plan
    {
        $data = $request->validated();

        if (isset($data['features_text'])) {
            $data['features'] = collect(explode("\n", $data['features_text']))
                ->map(fn ($line) => trim($line))
                ->filter()
                ->values()
                ->all();
            unset($data['features_text']);
        }

        if (array_key_exists('amember_product_ids', $data)) {
            $raw = trim((string) $data['amember_product_ids']);
            $data['amember_product_ids'] = $raw === ''
                ? null
                : array_values(array_unique(array_map('intval', array_filter(array_map('trim', explode(',', $raw))))));
        }

        $toolIds = $data['tool_ids'] ?? [];
        unset($data['tool_ids']);

        $data['is_featured'] = $request->boolean('is_featured');
        $data['is_active'] = $request->boolean('is_active');
        $data['show_on_homepage'] = $request->boolean('show_on_homepage');
        $data['is_bundle'] = $request->boolean('is_bundle');
        $data['is_trial'] = $request->boolean('is_trial');

        if (! $plan->exists && ($data['sort_order'] ?? 0) === 0) {
            $data['sort_order'] = (int) Plan::min('sort_order') - 1;
        }

        $plan->fill($data)->save();
        $plan->tools()->sync($toolIds);

        return $plan->fresh();
    }
}
