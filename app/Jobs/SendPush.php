<?php

namespace App\Jobs;

use App\Models\PushSubscription;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class SendPush implements ShouldQueue
{
    use Queueable;

    public function __construct(public array $userIds, public array $payload) {}

    public function handle(): void
    {
        $subs = PushSubscription::whereIn('user_id', $this->userIds)->get();
        if ($subs->isEmpty()) return;

        $webPush = new WebPush(['VAPID' => [
            'subject'    => config('webpush.subject'),
            'publicKey'  => config('webpush.public_key'),
            'privateKey' => config('webpush.private_key'),
        ]]);

        foreach ($subs as $s) {
            $webPush->queueNotification(
                Subscription::create([
                    'endpoint'        => $s->endpoint,
                    'publicKey'       => $s->p256dh,
                    'authToken'       => $s->auth,
                    'contentEncoding' => 'aes128gcm',
                ]),
                json_encode($this->payload)
            );
        }

        foreach ($webPush->flush() as $report) {
            if ($report->isSubscriptionExpired()) {
                PushSubscription::where('endpoint', $report->getEndpoint())->delete();
            }
        }
    }
}