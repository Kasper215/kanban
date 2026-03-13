<?php

namespace App\Http\Middleware;

use App\Events\BoardUpdated;
use App\Models\Board;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DispatchBoardEvent
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // выполняем контроллер
        $response = $next($request);

        // достаём board_uuid из сессии
        $boardUuid = session('board_uuid');

        if ($boardUuid) {
            $board = Board::where('uuid', $boardUuid)->first();

            if ($board) {
                BoardUpdated::dispatch($board);
            }
        }

        return $response;
    }
}
