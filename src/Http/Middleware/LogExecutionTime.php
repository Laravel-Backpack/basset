<?php

namespace Backpack\Basset\Http\Middleware;

use Backpack\Basset\Facades\Basset;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LogExecutionTime
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        return $next($request);
    }

    /**
     * Logs the Basset execution time
     *
     * @return void
     */
    public function terminate(): void
    {
        Log::info('Basset run '.Basset::getTotalCalls().' times, with an exeuction time of '.Basset::getLoadingTime());
    }
}
