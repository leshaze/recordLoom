<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\Invitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

/**
 * Users are only created by admins (there is no registration). New users get an invitation mail
 * with a link to set their password.
 */
class UserController extends Controller
{
    public function index(): View
    {
        return view('admin.users.index', [
            'users' => User::withCount(['records', 'assignedRecords'])->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.users.form', ['user' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules());

        $user = new User;
        $user->name = $data['name'];
        $user->email = strtolower($data['email']);
        $user->role = Role::from($data['role']);
        $user->sees_all_records = $request->boolean('sees_all_records');
        // Unusable until the user sets his own password through the invitation.
        $user->password = Str::random(64);
        $user->save();

        return $this->sendInvitation($user, __('Benutzer :name wurde angelegt und eingeladen.', ['name' => $user->name]));
    }

    public function edit(User $user): View
    {
        return view('admin.users.form', ['user' => $user->loadCount('records')]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate($this->rules($user));
        $role = Role::from($data['role']);

        if ($user->isAdmin() && $role !== Role::Admin && $this->isLastAdmin($user)) {
            return back()->withInput()->with('error', __('Der letzte Admin kann seine Rolle nicht verlieren.'));
        }
        if ($role === Role::Seller && $user->records()->exists()) {
            return back()->withInput()->with('error', __(':name hat eigene Platten und kann deshalb kein Verkäufer werden.', ['name' => $user->name]));
        }

        $user->name = $data['name'];
        $user->email = strtolower($data['email']);
        $user->role = $role;
        $user->sees_all_records = $request->boolean('sees_all_records');
        $user->save();

        return redirect()->route('admin.users.index')->with('info', __('Benutzer :name wurde gespeichert.', ['name' => $user->name]));
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('error', __('Du kannst dich nicht selbst löschen.'));
        }
        if ($user->records()->exists()) {
            return back()->with('error', __(':name hat noch eigene Platten und kann deshalb nicht gelöscht werden.', ['name' => $user->name]));
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('info', __('Benutzer :name wurde gelöscht.', ['name' => $user->name]));
    }

    public function invite(User $user): RedirectResponse
    {
        return $this->sendInvitation($user, __('Die Einladung an :name wurde erneut verschickt.', ['name' => $user->name]));
    }

    /**
     * Sends the invitation. When mail does not work the admin gets the link to pass it on himself.
     */
    private function sendInvitation(User $user, string $message): RedirectResponse
    {
        $token = Password::broker()->createToken($user);
        $redirect = redirect()->route('admin.users.index');

        try {
            $user->notify(new Invitation($token));
        } catch (Throwable $exception) {
            report($exception);

            return $redirect->with('error', __('Die Einladungsmail konnte nicht verschickt werden.'))
                ->with('invitation_link', ['name' => $user->name, 'url' => Invitation::url($user, $token)]);
        }

        return $redirect->with('info', $message);
    }

    private function isLastAdmin(User $user): bool
    {
        return User::where('role', Role::Admin->value)->whereKeyNot($user->id)->doesntExist();
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(?User $user = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'role' => ['required', Rule::enum(Role::class)],
            'sees_all_records' => ['boolean'],
        ];
    }
}
