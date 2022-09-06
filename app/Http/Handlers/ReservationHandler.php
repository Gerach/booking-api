<?php

declare(strict_types=1);

namespace App\Http\Handlers;

use App\Http\Requests\V1\StoreReservationRequest;
use App\Http\Resources\V1\ReservationCollection;
use App\Http\Resources\V1\ReservationResource;
use App\Models\Reservation;
use App\Models\Vacancy;
use Auth;
use Carbon\CarbonImmutable;
use DB;
use Illuminate\Validation\ValidationException;
use Throwable;

final class ReservationHandler
{
    private const DEFAULT_VACANCIES_PER_DAY = 10;

    public function handleFetch(): ReservationCollection
    {
        $user = Auth::user();
        $reservations = $user?->reservations()->get();
        return new ReservationCollection($reservations);
    }

    /**
     * @param StoreReservationRequest $request
     *
     * @return ReservationResource
     * @throws Throwable
     */
    public function handleCreate(StoreReservationRequest $request): ReservationResource
    {
        $this->validateCreate($request);

        try {
            DB::beginTransaction();

            $vacancyDate = CarbonImmutable::createFromFormat('Y-m-d', $request['reservedSince']);
            $vacancyTill = CarbonImmutable::createFromFormat('Y-m-d', $request['reservedTill']);

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

    public function handleDestroy(Reservation $reservation): void
    {
        try {
            DB::beginTransaction();

            $vacancyDate = CarbonImmutable::createFromFormat('Y-m-d', $reservation->reserved_since);
            $vacancyTill = CarbonImmutable::createFromFormat('Y-m-d', $reservation->reserved_till);

            while ($vacancyDate <= $vacancyTill) {
                $vacancyDateFormatted =  $vacancyDate->format('Y-m-d');
                $vacancy = Vacancy::where(['vacancy_date' => $vacancyDateFormatted])->first();
                if ($vacancy) {
                    $vacancy->increment('remaining_vacancies');
                    $vacancy->save();
                }

                $vacancyDate = $vacancyDate->modify('+1 day');
            }

            $reservation->delete();
            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function validateCreate(StoreReservationRequest $request): void
    {
        $haveNotEnoughVacancies = DB::table('vacancies')
            ->where('vacancy_date', '>=', $request['reservedSince'])
            ->where('vacancy_date', '<=', $request['reservedTill'])
            ->where('remaining_vacancies', '=', 0)
            ->limit(1)
            ->get();

        if (!$haveNotEnoughVacancies->count()) {
            return;
        }

        throw ValidationException::withMessages([
            'reservedRange' => 'There are not enough vacancies for selected date range.',
        ]);
    }
}
