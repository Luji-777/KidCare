<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\App;

class SetAppLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        // 1. قراءة اللغة من الهيدر (إذا ما بعت شي الفرونت إند، الافتراضي هو الإنكليزي en)
        $locale = $request->header('Accept-Language', config('app.locale'));

        // 2. التأكد أن اللغة المدعومة هي إما عربي أو إنكليزي فقط
        if (in_array($locale, ['ar', 'en'])) {
            App::setLocale($locale); // هون اللارافيل بيقلب لغة السيستم بالكامل!
        }

        return $next($request);
    }
}
