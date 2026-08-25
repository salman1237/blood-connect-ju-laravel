<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * API twin of web's NotificationController — same underlying
 * illuminate/notifications database records (every notification's
 * toArray() already carries a 'message' string and, where relevant, a
 * 'request_id' for deep-linking, the same two fields the web view reads).
 * Not paginated like the web page — capped at the most recent 50, the same
 * simplification already used for /donors and /requests.
 */
class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()->notifications()->latest()->take(50)->get();

        return response()->json($notifications->map(fn ($notification) => [
            'id' => $notification->id,
            'message' => $notification->data['message'] ?? '',
            'request_id' => $notification->data['request_id'] ?? null,
            'read_at' => $notification->read_at?->toIso8601String(),
            'created_at' => $notification->created_at->toIso8601String(),
        ]));
    }

    public function markAsRead(Request $request, string $notification): Response
    {
        $request->user()->notifications()->findOrFail($notification)->markAsRead();

        return response()->noContent();
    }

    public function markAllAsRead(Request $request): Response
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->noContent();
    }
}
