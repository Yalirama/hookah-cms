<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use Illuminate\Http\Request;

class PushController extends Controller
{
    public function subscribe(Request $request)
    {
        $d = $request->validate([
            'endpoint'    => 'required|string|max:500',
            'keys.p256dh' => 'required|string',
            'keys.auth'   => 'required|string',
        ]);

        PushSubscription::updateOrCreate(
            ['endpoint' => $d['endpoint']],
            [
                'user_id'    => auth()->id(),
                'p256dh'     => $d['keys']['p256dh'],
                'auth'       => $d['keys']['auth'],
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
            ]
        );

        return response()->json(['ok' => true]);
    }

    public function unsubscribe(Request $request)
    {
        PushSubscription::where('endpoint', $request->input('endpoint'))
            ->where('user_id', auth()->id())
            ->delete();

        return response()->json(['ok' => true]);
    }

    public function toggleShift(Request $request)
    {
        $user = $request->user();
        $user->forceFill(['on_shift' => $request->boolean('on')])->save();

        return response()->json(['on' => (bool) $user->on_shift]);
    }
}