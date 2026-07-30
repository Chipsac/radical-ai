<?php

namespace App\Http\Controllers;

use App\Support\HelpLibrary;
use Illuminate\Http\Request;

class HelpController extends Controller
{
    /** Record that a user has dismissed a tip or finished a tour. */
    public function markSeen(Request $request)
    {
        $data = $request->validate([
            'key' => ['required', 'string', 'max:80'],
        ]);

        $request->user()->markSeen($data['key']);

        return response()->json(['ok' => true]);
    }

    /** Reset every dismissed tip — "show me the tips again". */
    public function reset(Request $request)
    {
        $request->user()->forceFill(['ui_state' => null])->save();

        return back()->with('status', 'Help tips and tours have been reset.');
    }

    public function articles(Request $request)
    {
        $query = trim((string) $request->query('q'));
        $articles = collect(HelpLibrary::articles());

        if ($query !== '') {
            $needle = mb_strtolower($query);
            $articles = $articles->filter(fn ($a) => str_contains(mb_strtolower(
                $a['title'].' '.$a['body'].' '.$a['keywords'].' '.$a['section']
            ), $needle))->values();
        }

        return response()->json([
            'articles' => $articles->groupBy('section'),
            'count' => $articles->count(),
        ]);
    }
}
