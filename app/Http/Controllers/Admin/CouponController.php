<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Plan;
use App\Models\Tool;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CouponController extends Controller
{
    public function index()
    {
        $coupons = Coupon::query()->orderByDesc('created_at')->get();

        return view('admin.coupons.index', compact('coupons'));
    }

    public function create()
    {
        return view('admin.coupons.form', $this->formData(new Coupon([
            'type' => Coupon::TYPE_PERCENT,
            'is_active' => true,
        ])));
    }

    public function store(Request $request)
    {
        $coupon = $this->save(new Coupon, $request);

        return redirect()->route('admin.coupons.index')
            ->with('success', "Coupon \"{$coupon->code}\" created.");
    }

    public function edit(Coupon $coupon)
    {
        return view('admin.coupons.form', $this->formData($coupon));
    }

    public function update(Request $request, Coupon $coupon)
    {
        $coupon = $this->save($coupon, $request);

        return redirect()->route('admin.coupons.index')
            ->with('success', "Coupon \"{$coupon->code}\" updated.");
    }

    public function toggle(Coupon $coupon)
    {
        $coupon->update(['is_active' => ! $coupon->is_active]);

        return back()->with('success', 'Coupon status updated.');
    }

    protected function formData(Coupon $coupon): array
    {
        return [
            'coupon' => $coupon,
            'plans' => Plan::where('is_active', true)->orderBy('sort_order')->get(),
            'tools' => Tool::where('is_active', true)->orderBy('sort_order')->get(),
            'durations' => config('pricing.durations'),
        ];
    }

    protected function save(Coupon $coupon, Request $request): Coupon
    {
        $durationKeys = array_map('strval', array_keys(config('pricing.durations')));

        $data = $request->validate([
            'code' => [
                'required',
                'string',
                'max:40',
                'alpha_dash',
                Rule::unique('coupons', 'code')->ignore($coupon->id),
            ],
            'type' => ['required', Rule::in([Coupon::TYPE_PERCENT, Coupon::TYPE_FIXED])],
            'value' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['nullable', Rule::in(['inr', 'usd'])],
            'max_uses' => ['nullable', 'integer', 'min:1'],
            'max_uses_per_user' => ['nullable', 'integer', 'min:1'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'description' => ['nullable', 'string', 'max:255'],
            'allowed_plan_ids' => ['nullable', 'array'],
            'allowed_plan_ids.*' => ['integer', 'exists:plans,id'],
            'allowed_tool_ids' => ['nullable', 'array'],
            'allowed_tool_ids.*' => ['integer', 'exists:tools,id'],
            'allowed_duration_months' => ['nullable', 'array'],
            'allowed_duration_months.*' => [Rule::in($durationKeys)],
            'required_plan_ids' => ['nullable', 'array'],
            'required_plan_ids.*' => ['integer', 'exists:plans,id'],
            'required_tool_ids' => ['nullable', 'array'],
            'required_tool_ids.*' => ['integer', 'exists:tools,id'],
        ]);

        if ($data['type'] === Coupon::TYPE_PERCENT) {
            $request->validate(['value' => ['max:100']]);
            $data['currency'] = null;
        }

        $data['code'] = strtoupper($data['code']);
        $data['is_active'] = $request->boolean('is_active');
        $data['allowed_plan_ids'] = $this->normalizeIds($data['allowed_plan_ids'] ?? null);
        $data['allowed_tool_ids'] = $this->normalizeIds($data['allowed_tool_ids'] ?? null);
        $data['allowed_duration_months'] = $this->normalizeDurations($data['allowed_duration_months'] ?? null);
        $data['required_plan_ids'] = $this->normalizeIds($data['required_plan_ids'] ?? null);
        $data['required_tool_ids'] = $this->normalizeIds($data['required_tool_ids'] ?? null);

        $coupon->fill($data)->save();

        return $coupon;
    }

    protected function normalizeIds(?array $ids): ?array
    {
        if ($ids === null || $ids === []) {
            return null;
        }

        return array_values(array_unique(array_map('intval', $ids)));
    }

    protected function normalizeDurations(?array $months): ?array
    {
        if ($months === null || $months === []) {
            return null;
        }

        return array_values(array_unique(array_map('intval', $months)));
    }
}
