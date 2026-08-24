<?php

namespace App\Services\Fcm;

enum FcmSendResult
{
    case Sent;
    /** FCM reported this token as unregistered/invalid — safe to delete it. */
    case InvalidToken;
    /** Anything else (network, auth, transient FCM error) — not the token's fault, don't delete it. */
    case Failed;
}
