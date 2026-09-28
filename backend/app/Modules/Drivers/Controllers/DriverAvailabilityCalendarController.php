<?php

declare(strict_types=1);

namespace App\Modules\Drivers\Controllers;

use App\Core\Organizations\OrganizationContext;
use App\Models\User;
use App\Modules\Drivers\Requests\DecideDriverAvailabilityDayRequest;
use App\Modules\Drivers\Requests\StoreDriverAvailabilityDayRequest;
use App\Modules\Drivers\Services\DriverAvailabilityCalendarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DriverAvailabilityCalendarController
{
    public function __construct(private readonly DriverAvailabilityCalendarService $calendar) {}

    public function index(Request $request, OrganizationContext $context): JsonResponse
    {
        $data = $request->validate(['month' => ['required', 'date_format:Y-m']]);

        return response()->json(['data' => $this->calendar->month(
            $this->actor($request), $context->requireId(), (string) $data['month'],
        )]);
    }

    public function store(StoreDriverAvailabilityDayRequest $request, OrganizationContext $context): JsonResponse
    {
        return response()->json(['data' => $this->calendar->submit(
            $this->actor($request), $context->requireId(), $request->validated(),
        )], 201);
    }

    private function actor(Request $request): User
    {
        $actor = $request->user();
        if (! $actor instanceof User) {
            abort(401);
        }

        return $actor;
    }

    public function decide(DecideDriverAvailabilityDayRequest $request, OrganizationContext $context, int $day): JsonResponse
    {
        return response()->json(['data' => $this->calendar->decide(
            $this->actor($request), $context->requireId(), $day, $request->validated(),
        )]);
    }
}
