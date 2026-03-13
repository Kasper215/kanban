<?php

namespace App\Listeners;

use App\Events\BoardUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use NotificationChannels\WebPush\PushSubscription;

class SendBoardUpdateNotification
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     * @throws \ErrorException
     */
    public function handle(BoardUpdated $event): void
    {
        $board = $event->board;

        $auth = [
            'VAPID' => [
                'subject' => 'mailto:'.env("VAPID_SUBJECT"),
                'publicKey' => env('VAPID_PUBLIC_KEY'),
                'privateKey' => env('VAPID_PRIVATE_KEY'),
            ],
        ];

        // ВАЖНО: включаем legacy mode
        $webPush = new WebPush($auth, [
            'timeout' => 20,
            'reuseVAPIDHeaders' => true,
            'automaticPadding' => false,
            'localAuthenticationTag' => false,
            'legacy' => true, // <‑‑ вот это спасает OpenServer
        ]);

        $payload = json_encode([
            'title' => 'Тестовое уведомление',
            'body'  => 'Это пуш всем подписанным клиентам',
            'url'   => '/',
        ]);

        $subscriptions = PushSubscription::where("board_id", $board->id)
            ->get();

        foreach ($subscriptions as $sub) {
            $subscription = Subscription::create([
                'endpoint' => $sub->endpoint,
                'publicKey' => $sub->public_key,
                'authToken' => $sub->auth_token,
                'contentEncoding' => $sub->content_encoding,
            ]);

            $webPush->sendOneNotification($subscription, $payload);
        }
    }
}
