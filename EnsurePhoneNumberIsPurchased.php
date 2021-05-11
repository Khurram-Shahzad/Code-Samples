<?php

namespace App\Http\Middleware;

class EnsurePhoneNumberIsPurchased
{
    public function handle(Request $request, Closure $next)
    {
        $userRole = optional(auth()->user()->role)->name;
        if (in_array($userRole, ['admin', 'loan-officer', 'agent'])) {
            $phoneNumbersCount = Cache::rememberForever('phoneNumbersCount', function () {
                return PhoneNumber::where('user_id', auth()->id())->count();
            });
            if (! $phoneNumbersCount) {
                return redirect()->route('phone');
            }
        }

        return $next($request);
    }
}
