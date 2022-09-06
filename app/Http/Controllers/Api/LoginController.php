<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Http\JsonResponse;

class LoginController extends Controller
{
    /**
     * Handle an incoming authentication request.
     *
     * @param LoginRequest $request
     *
     * @return JsonResponse
     */
    public function store(LoginRequest $request): JsonResponse
    {
        $request->authenticate();

        /** @var MorphMany $userTokens */
        $userTokens = $request->user()->tokens();
        $userTokens->delete();

        $userToken = $request->user()->createToken('user-token', ['create', 'destroy']);

        return new JsonResponse($userToken->plainTextToken);
    }
}
