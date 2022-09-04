<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StoreReservationRequest;
use App\Http\Resources\V1\ReservationCollection;
use App\Http\Resources\V1\ReservationResource;
use App\Models\Reservation;
use App\Models\Vacancy;
use Auth;
use Date;
use DateTimeImmutable;
use DB;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Throwable;

class ReservationController extends Controller
{
    private const DEFAULT_VACANCIES_PER_DAY = 10;

    public function index(): ReservationCollection
    {
        $user = Auth::user();
        $reservations = $user?->reservations()->get();
        return new ReservationCollection($reservations);
    }

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
     * Store a newly created resource in storage.
     *
     * @param StoreReservationRequest $request
     *
     * @return ReservationResource
     * @throws Throwable
     */
    public function store(StoreReservationRequest $request): ReservationResource
    {
        $haveNotEnoughVacancies = DB::table('vacancies')
            ->where('vacancy_date', '>=', $request['reservedSince'])
            ->where('vacancy_date', '<=', $request['reservedTill'])
            ->where('remaining_vacancies', '=', 0)
            ->limit(1)
            ->get();

        if ($haveNotEnoughVacancies->count()) {
            throw ValidationException::withMessages(['There are not enough vacancies for selected date range.']);
        }

        try {
            DB::beginTransaction();

            $vacancyDate = DateTimeImmutable::createFromFormat('Y-m-d', $request['reservedSince']);
            $vacancyTill = DateTimeImmutable::createFromFormat('Y-m-d', $request['reservedTill']);

            while ($vacancyDate <= $vacancyTill) {
                $vacancyDateFormatted =  $vacancyDate->format('Y-m-d');
                $vacancy = Vacancy::where(['vacancy_date' => $vacancyDateFormatted])->first();
                if ($vacancy) {
                    $vacancy->decrement('remaining_vacancies');
                    $vacancy->save();
                } else {
                    Vacancy::create([
                        'vacancy_date' => $vacancyDateFormatted,
                        'remaining_vacancies' => self::DEFAULT_VACANCIES_PER_DAY - 1,
                    ]);
                }

                $vacancyDate = $vacancyDate->modify('+1 day');
            }

            $user = Auth::user();
            $reservation = new Reservation($request->all());
            $user?->reservations()->save($reservation);
            DB::commit();
            return new ReservationResource($reservation);
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
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

    /**
     * Remove the specified resource from storage.
     *
     * @param Reservation  $reservation
     *
     * @return Response
     */
    public function destroy(Reservation $reservation)
    {
        //
    }
}
