<?php

namespace App\Support;

use App\Models\ChangeRequest;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ChangeRequestNotifier
{
    public static function toAdmins(string $subject, string $body): void
    {
        $emails = User::where('is_admin', true)->pluck('email')->all();

        if ($emails === []) {
            return;
        }

        self::send($emails, $subject, $body);
    }

    public static function toCustomer(ChangeRequest $changeRequest, string $subject, string $body): void
    {
        self::send([$changeRequest->user->email], $subject, $body);
    }

    private static function send(array $to, string $subject, string $body): void
    {
        try {
            Mail::raw($body, fn ($message) => $message->to($to)->subject($subject));
        } catch (Throwable $e) {
            report($e);
        }
    }
}
