<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Handlers\ReservationHandler;
use App\Http\Requests\V1\StoreReservationRequest;
use App\Http\Resources\V1\ReservationCollection;
use App\Http\Resources\V1\ReservationResource;
use App\Models\Reservation;
use Auth;
use Illuminate\Http\Response;
use Throwable;

class ReservationController extends Controller
{
    public function __construct(
        private readonly ReservationHandler $reservationHandler,
    )
    {
    }

    /**
     * @return ReservationCollection
     */
    public function index(): ReservationCollection
    {
        return $this->reservationHandler->handleFetch();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreReservationRequest $request
     *
     * @return ReservationResource
     * @throws Throwable
     */
    public function store(StoreReservationRequest $request): ReservationResource
    {
        return $this->reservationHandler->handleCreate($request);
    }

    /**
     * Display the specified resource.
     *
     * @param Reservation $reservation
     *
     * @return ReservationResource
     */
    public function show(Reservation $reservation): ReservationResource
    {
        if (Auth::user()?->getAuthIdentifier() !== $reservation->user_id) {
            abort(401);
        }
        return new ReservationResource($reservation);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Reservation $reservation
     *
     * @return Response
     * @throws Throwable
     */
    public function destroy(Reservation $reservation): Response
    {
        if (Auth::user()?->getAuthIdentifier() !== $reservation->user_id) {
            abort(401);
        }

        $this->reservationHandler->handleDestroy($reservation);

        return new Response();
    }
}
