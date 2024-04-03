<?php

namespace App\Http\Controllers;

use App\Exceptions\AuthException;
use App\Http\Requests\AuthLoginRequest;
use App\Http\Requests\AuthRegisterRequest;
use App\Http\Requests\CreateSubscriptionRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{



    public function register(AuthRegisterRequest $request)
    {

        $_creation_data =[
            'email' => $request->input('email'),
            'password' => Hash::make($request->input('password'))
        ];

        $contexts =['name', 'address1', 'city', 'region', 'country', 'phone', 'tax_office', 'tax_number'];
        foreach ($contexts as $context) {
            if($request->has($context)) {
                $_creation_data[$context] = $request->input($context);
            }
        }


        User::factory()->create($_creation_data);

        if (!$token = auth()->attempt(['email' => $request->input('email'), 'password' => $request->input('password')])) {
            throw new AuthException('Register failed, cannot authenticate', 401);
        }

        if($request->has('credit_card') && $request->has('expire_month') && $request->has('expire_year') && $request->has('cvv')) {

            $subscription = new SubscriptionController();
            $_request_create_subscription = new CreateSubscriptionRequest([
                'credit_card'  => $request->input('credit_card'),
                'expire_month' => $request->input('expire_month'),
                'expire_year'  => $request->input('expire_year'),
                'cvv'          => $request->input('cvv')
            ]);

            return $subscription->createSubscription($_request_create_subscription);
        }


        return $this->respondWithToken($token);

    }

    /**
     * Get a JWT via given credentials.
     *
     * @return JsonResponse
     */
    public function login(AuthLoginRequest $request)
    {
        $credentials = $request->only('email', 'password');

        $token = Auth::attempt($credentials);

        if (! $token) {
            throw new AuthException('Login failed, credentials not match', 401);
        }

        return $this->respondWithToken($token);
    }

    /**
     * Get the authenticated User.
     *
     * @return JsonResponse
     */
    public function me() {
        return response()->json(auth()->user());
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return JsonResponse
     */
    public function logout()
    {
        auth()->logout();
        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     *
     * @return JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param string $token
     *
     * @return JsonResponse
     */
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => auth()->factory()->getTTL() * 60,
            'user'         => auth()->user()->id,
        ]);
    }
}
