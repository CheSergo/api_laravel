<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FrontHashValidator
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // dd($request->header('whokilledkenny'));
        $hash = '******************************************************'; 
        if ($request->header('whokilledkenny') == $hash) {
            return $next($request);
        } else {
            return response()->json([
                'status'    => '403',
                'message'   => 'Access denied',
            ], '403');
        }
    }
}
