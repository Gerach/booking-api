<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Handlers\ReservationHandler;
use App\Http\Requests\V1\StoreReservationRequest;
use App\Http\Resources\V1\ReservationCollection;
use App\Http\Resources\V1\ReservationResource;
use App\Models\Reservation;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\RedirectResponse;
use Throwable;

class ReservationController extends Controller
{
    public function __construct(
        private readonly ReservationHandler $reservationHandler,
    )
    {
    }

    public function index(): ReservationCollection
    {
        return $this->reservationHandler->handleFetch();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreReservationRequest $request
     *
     * @return RedirectResponse
     * @throws Throwable
     */
    public function store(StoreReservationRequest $request): RedirectResponse
    {
        $this->reservationHandler->handleCreate($request);

        return redirect()->intended(RouteServiceProvider::HOME);
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
        return new ReservationResource($reservation);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param $reservationId
     *
     * @return RedirectResponse
     * @throws Throwable
     */
    public function destroy($reservationId): RedirectResponse
    {
        $reservation = Reservation::find($reservationId);
        $this->reservationHandler->handleDestroy($reservation);

        return redirect()->intended(RouteServiceProvider::HOME);
    }
}
