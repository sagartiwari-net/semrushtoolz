<?php

namespace App\Http\Controllers\Reseller;

use App\Http\Controllers\Controller;
use App\Models\ResellerProvision;
use App\Services\ResellerCancelService;
use Illuminate\Http\Request;
use RuntimeException;

class CancelController extends Controller
{
    public function __construct(
        protected ResellerCancelService $cancels,
    ) {}

    public function destroy(Request $request, ResellerProvision $provision)
    {
        abort_unless((int) $provision->reseller_user_id === (int) $request->user()->id, 404);

        try {
            $result = $this->cancels->cancelByReseller($request->user(), $provision);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('reseller.reports.index', ['tab' => 'cancelled'])
            ->with(
                'success',
                'Access cancelled. ₹'.number_format($result['refund'], 2).' refunded to your balance. Record kept in Cancelled report.'
            );
    }
}
