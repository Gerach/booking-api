<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\V1\StoreReservationRequest;
use App\Http\Resources\V1\ReservationCollection;
use App\Http\Resources\V1\ReservationResource;
use App\Models\Reservation;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Http\Response;

class LoginController extends Controller
{
//    public function index(LoginRequest $request): ReservationResource
//    {
//        return new ReservationResource(Reservation::all());
//    }

//    /**
//     * Show the form for creating a new resource.
//     *
//     * @return Response
//     */
//    public function create()
//    {
//        //
//    }

    /**
     * Handle an incoming authentication request.
     *
     * @param LoginRequest $request
     *
     * @return Response
     */
    public function store(LoginRequest $request): Response
    {
        $request->authenticate();

        /** @var MorphMany $userTokens */
        $userTokens = $request->user()->tokens();
        $userTokens->delete();

        $userToken = $request->user()->createToken('user-token', ['create', 'destroy']);

        return new Response($userToken->plainTextToken);
    }

//    /**
//     * Display the specified resource.
//     *
//     * @param Reservation $reservation
//     *
//     * @return ReservationResource
//     */
//    public function show(Reservation $reservation): ReservationResource
//    {
//        return new ReservationResource($reservation);
//    }

//    /**
//     * Show the form for editing the specified resource.
//     *
//     * @param Reservation $reservation
//     *
//     * @return Response
//     */
//    public function edit(Reservation $reservation)
//    {
//        //
//    }

//    /**
//     * Update the specified resource in storage.
//     *
//     * @param  \App\Http\Requests\V1\UpdateReservationRequest  $request
//     * @param Reservation $reservation
//     *
//     * @return Response
//     */
//    public function update(UpdateReservationRequest $request, Reservation $reservation)
//    {
//        //
//    }

//    /**
//     * Remove the specified resource from storage.
//     *
//     * @param Reservation  $reservation
//     *
//     * @return Response
//     */
//    public function destroy(Reservation $reservation)
//    {
//        //
//    }
}
