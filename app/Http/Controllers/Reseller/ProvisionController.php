<?php

namespace App\Http\Controllers\Reseller;

use App\Http\Controllers\Controller;
use App\Models\Tool;
use App\Rules\AllowedEmail;
use App\Services\ResellerPricingService;
use App\Services\ResellerProvisionService;
use Illuminate\Http\Request;
use RuntimeException;

class ProvisionController extends Controller
{
    public function __construct(
        protected ResellerProvisionService $provisions,
        protected ResellerPricingService $pricing,
    ) {}

    public function create(Request $request)
    {
        $catalog = $this->pricing->catalogForReseller($request->user());

        return view('reseller.provision', [
            'catalog' => $catalog,
            'durations' => [1, 3, 6, 12],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:190', new AllowedEmail],
            'tool_id' => ['required', 'exists:tools,id'],
            'duration_months' => ['required', 'integer', 'in:1,3,6,12'],
            'confirm_charge' => ['accepted'],
        ], [
            'confirm_charge.accepted' => 'Please confirm the charge amount before provisioning.',
        ]);

        $tool = Tool::findOrFail($data['tool_id']);

        try {
            $result = $this->provisions->provision(
                $request->user(),
                $data['email'],
                $tool,
                (int) $data['duration_months'],
            );
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $flash = [
            'success' => $result['message'],
        ];

        if ($result['was_new_user'] && $result['plain_password']) {
            $flash['credentials'] = [
                'access_url' => $result['access_url'],
                'email' => strtolower($data['email']),
                'password' => $result['plain_password'],
            ];
        }

        return redirect()->route('reseller.provision.create')->with($flash);
    }

    public function previewCharge(Request $request)
    {
        $data = $request->validate([
            'tool_id' => ['required', 'exists:tools,id'],
            'duration_months' => ['required', 'integer', 'in:1,3,6,12'],
        ]);

        $tool = Tool::findOrFail($data['tool_id']);
        $charge = $this->pricing->chargeForMonths(
            $request->user(),
            $tool,
            (int) $data['duration_months'],
        );

        if ($charge === null) {
            return response()->json(['ok' => false, 'message' => 'Tool not available at reseller pricing.'], 422);
        }

        return response()->json([
            'ok' => true,
            'charge' => $charge,
            'label' => '₹'.number_format($charge, 2),
        ]);
    }
}
