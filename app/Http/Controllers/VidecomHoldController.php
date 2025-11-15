<?php

namespace App\Http\Controllers;

use App\Http\Requests\VidecomHoldRequest;
use App\Services\TravelNDC\Exceptions\TravelNdcException;
use App\Services\TravelNDC\TravelNdcService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;

class VidecomHoldController extends Controller
{
    public function __construct(private readonly TravelNdcService $travelNdcService)
    {
    }

    public function __invoke(VidecomHoldRequest $request): RedirectResponse
    {
        $offerPayload = $request->decodedOffer();

        if (!$this->travelNdcService->canHoldVidecomOffer($offerPayload)) {
            return redirect()->back()->withErrors([
                'hold' => 'This offer cannot be held via Videcom.',
            ]);
        }

        $details = [
            'passenger_title' => $request->string('passenger_title'),
            'passenger_first_name' => $request->string('passenger_first_name'),
            'passenger_last_name' => $request->string('passenger_last_name'),
            'contact_email' => $request->string('contact_email'),
            'contact_phone' => $request->string('contact_phone'),
            'seat_count' => Arr::get($offerPayload, 'passenger_summary.total', 1),
        ];

        try {
            $pnr = $this->travelNdcService->holdVidecomOffer($offerPayload, $details);
        } catch (TravelNdcException $exception) {
            return redirect()->back()->withErrors([
                'hold' => $exception->getMessage(),
            ]);
        }

        return redirect()->back()->with([
            'videcomHold' => $pnr,
            'scrollTo' => 'videcom-hold',
        ]);
    }
}
