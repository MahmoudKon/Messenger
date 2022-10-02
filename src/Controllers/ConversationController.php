<?php

namespace Messenger\Chat\Controllers;

use App\Http\Controllers\Controller;
use Messenger\Chat\Traits\UploadFile;

class ConversationController extends Controller
{
    use UploadFile;

    public function index()
    {
        if (request()->ajax()) return $this->conversations();
        return view('messenger.index');
    }

    public function conversations()
    {
        $users = config('auth.providers.users.model')::exceptAuth()->search()
                        ->hasConversationWithAuth()
                        ->with([
                            'conversations' => function($query) { $query->onlyWithAuth(); }
                        ])->paginate(8);

        $next_page = $users->currentPage() + 1;
        $next_page = $next_page <= $users->lastPage() ? $next_page : null;

        $users = $users->sortByDesc(function($user) {
            if (isset($user->conversations[0]))
                return $user->conversations[0]->last_message_id;
        });

        return response()->json([
            'view' => view('messenger.includes.users', compact('users'))->render(),
            'next_page' => $next_page
        ]);
    }

    public function users()
    {
        $users = config('auth.providers.users.model')::exceptAuth()->search()
                        ->with([
                            'conversations' => function($query) { $query->onlyWithAuth(); }
                        ])->orderBy('last_seen', 'DESC')->paginate(8);

        $next_page = $users->currentPage() + 1;
        $next_page = $next_page <= $users->lastPage() ? $next_page : null;

        return response()->json([
            'view' => view('messenger.includes.users', compact('users'))->render(),
            'next_page' => $next_page
        ]);
    }

    public function updateLastSeen()
    {
        config('auth.providers.users.model')::find(request('user_id'))->update(['last_seen' => now()]);
        return 'updated';
    }

    public function userDetails($id)
    {
        $user = config('auth.providers.users.model')::fingOrFail($id);
        return view('messenger.includes.show', compact('user'));
    }
}
