import React, { useEffect, useState } from 'react';
import Authenticated from '@/Layouts/Authenticated';
import { Head, useForm } from '@inertiajs/inertia-react';
import Label from "@/Components/Label";
import Input from "@/Components/Input";
import InputError from "@/Components/InputError";
import Button from "@/Components/Button";

export default function Home(props) {
    const [dateRanges, setDateRanges] = useState({
        'sinceMin': props.minReservation,
        'sinceMax': props.minReservation,
        'tillMin': props.minReservation,
        'tillMax': props.maxReservation,
    });

    const { data, setData, post, delete: destroy, processing, errors } = useForm({
        reservedRange: '',
        reservedSince: (new Date()).toISOString().split('T')[0],
        reservedTill: (new Date()).toISOString().split('T')[0],
    });

    useEffect(() => {
        setDateRanges({
            'sinceMin': props.minReservation,
            'sinceMax': data.reservedTill,
            'tillMin': data.reservedSince,
            'tillMax': props.maxReservation,
        })
    }, [data.reservedSince, data.reservedTill])

    const onHandleChange = (event) => {
        setData(event.target.name, event.target.value);
    };

    const onHandleCancel = (reservationId, event) => {
        event.preventDefault();
        destroy(`/reservation/${reservationId}`);
    }

    const submit = (e) => {
        e.preventDefault();

        post(route('make-reservation'));
    };

    const reservationsTable = props.reservations.data.map((reservation) => (
        <div key={reservation.id} className="flex flex-row pb-2">
            <span>{reservation.reservedSince} - {reservation.reservedTill}</span>
            <button
                className="inline-flex items-center px-4 py-2 bg-gray-900 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest active:bg-gray-900 transition ease-in-out duration-150 bg-red-500 hover:bg-red-700 text-white font-bold py-1 px-2 ml-2 rounded text-sm"
                onClick={(e) => onHandleCancel(reservation.id, e)}
            >
                Cancel reservation
            </button>
        </div>
    ));

    return (
        <Authenticated
            auth={props.auth}
            errors={props.errors}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Reservations</h2>}
        >
            <Head>
                <title>Reservations</title>
            </Head>

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6 bg-white border-b border-gray-200">{reservationsTable}</div>
                    </div>
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <form className="p-6" onSubmit={submit}>
                            <div className="flex flex-row">
                                <div className="mr-4">
                                    <Label forInput="reservedSince" value="Reservation since" />

                                    <Input
                                        type="date"
                                        name="reservedSince"
                                        min={dateRanges.sinceMin}
                                        max={dateRanges.sinceMax}
                                        value={data.reservedSince}
                                        className="mt-1 block w-full"
                                        handleChange={onHandleChange}
                                    />

                                    <InputError message={errors.reservedSince} className="mt-2" />
                                </div>

                                <div>
                                    <Label forInput="reservedTill" value="Reservation till" />

                                    <Input
                                        type="date"
                                        name="reservedTill"
                                        min={dateRanges.tillMin}
                                        max={dateRanges.tillMax}
                                        value={data.reservedTill}
                                        className="mt-1 block w-full"
                                        handleChange={onHandleChange}
                                    />

                                    <InputError message={errors.reservedTill} className="mt-2" />
                                </div>
                            </div>
                            <InputError message={errors.reservedRange} className="mt-2" />

                            <div className="flex items-center mt-4">
                                <Button processing={processing}>
                                    Make reservation
                                </Button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Authenticated>
    );
}
