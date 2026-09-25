<?php

namespace App\Http\Controllers;

use App\Models\Record;
use App\Models\User;
use Illuminate\Http\Request;

class InterestController extends Controller
{
    /**
     * Records the logged in user is interested in.
     */
    public function index(Request $request)
    {
        $records = $request->user()->interests()
            ->with(['artist', 'label'])
            ->orderByPivot('created_at', 'desc')
            ->get();

        return view('interests.index', ['records' => $records]);
    }

    /**
     * Mark or unmark the record as interesting for the logged in user.
     */
    public function update(Request $request, Record $record)
    {
        $interested = $request->validate(['interested' => ['required', 'boolean']])['interested'];

        if ($interested) {
            $request->user()->interests()->syncWithoutDetaching([$record->id]);
        } else {
            $request->user()->interests()->detach($record->id);
        }

        return back()->with('info', $interested
            ? 'Interesse an '.$record->title.' gespeichert'
            : 'Interesse an '.$record->title.' entfernt');
    }

    /**
     * Admin overview of all interests, optionally filtered by user.
     */
    public function overview(Request $request)
    {
        $users = User::whereHas('interests')->orderBy('name')->get(['id', 'name', 'email']);
        $selectedUser = $request->filled('user') ? $users->firstWhere('id', $request->integer('user')) : null;

        $records = Record::query()
            ->whereHas('interestedUsers', fn ($query) => $selectedUser ? $query->whereKey($selectedUser->id) : $query)
            ->with(['artist', 'label', 'interestedUsers' => fn ($query) => $query->orderBy('name')])
            ->orderBy('title')
            ->get();

        return view('interests.overview', [
            'users' => $users,
            'selectedUser' => $selectedUser,
            'records' => $records,
        ]);
    }
}
