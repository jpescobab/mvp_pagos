<?php

namespace App\Http\Controllers\Notificaciones;

use App\Http\Controllers\Controller;
use App\Http\Resources\Notificaciones\NotificacionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NotificacionController extends Controller
{
    /** Notificaciones recientes devueltas al abrir la campana. */
    private const LIMITE = 20;

    /**
     * Lista las notificaciones del usuario autenticado. Opera siempre sobre
     * `$request->user()`, nunca sobre un id ajeno: el aislamiento es estructural.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $notificaciones = $request->user()
            ->notifications()
            ->latest()
            ->limit(self::LIMITE)
            ->get();

        return NotificacionResource::collection($notificaciones);
    }

    /**
     * Marca como leídas todas las notificaciones no leídas del usuario.
     */
    public function marcarLeidas(Request $request): RedirectResponse|JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        if ($request->wantsJson()) {
            return response()->json(['ok' => true]);
        }

        return back();
    }
}
