<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Database\QueryException;

class GoogleAuthController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callbackGoogle()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
            $existingUser = User::where('google_ID', $googleUser->getId())->orWhere('email', $googleUser->getEmail())->first();

            if (!$existingUser) {
                try {
                    $newUser = User::create([
                        'name' => $googleUser->getName(),
                        'email' => $googleUser->getEmail(),
                        'google_ID' => $googleUser->getId(),
                    ]);

                    Auth::login($newUser);

                    return redirect()->intended('dashboard');
                } catch (QueryException $e) {
                    if ($e->getCode() == 23000) {
                        // Duplicate entry error
                        return redirect()->route('login')->withErrors(['email' => 'User already exists.']);
                    }
                    // Other database error
                    return redirect()->route('login')->withErrors(['error' => 'Something went wrong!']);
                }
            } else {
                Auth::login($existingUser);

                return redirect()->intended('dashboard');
            }
        } catch (\Throwable $th) {
            return redirect()->route('login')->withErrors(['error' => 'Something went wrong! ' . $th->getMessage()]);
        }
    }
}
