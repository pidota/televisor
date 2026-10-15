<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', User::class);

        $users = User::query()
            ->with('roles:id,name,label')
            ->orderBy('name')
            ->paginate(20);

        return view('users.index', [
            'users' => $users,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('users.form', [
            'user' => new User,
            'mode' => 'create',
            'roles' => $this->assignableRoles(),
            'currentRole' => 'operator',
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = User::query()->create([
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->toString(),
            'password' => $request->string('password')->toString(),
        ]);

        $this->syncRole($user, $request->string('role')->toString());

        return redirect()
            ->route('users.index')
            ->with('success', 'Usuario creado correctamente.');
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        $user->load('roles:id,name,label');

        return view('users.form', [
            'user' => $user,
            'mode' => 'edit',
            'roles' => $this->assignableRoles(),
            'currentRole' => $user->roles->first()?->name ?? 'operator',
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $user->name = $request->string('name')->toString();
        $user->email = $request->string('email')->toString();

        if ($request->filled('password')) {
            $user->password = $request->string('password')->toString();
        }

        $user->save();

        $this->syncRole($user, $request->string('role')->toString());

        return redirect()
            ->route('users.index')
            ->with('success', 'Usuario actualizado.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('success', 'Usuario eliminado.');
    }

    /**
     * @return list<Role>
     */
    private function assignableRoles(): array
    {
        return Role::query()
            ->whereIn('name', ['admin', 'operator'])
            ->orderBy('name')
            ->get(['id', 'name', 'label'])
            ->all();
    }

    private function syncRole(User $user, string $roleName): void
    {
        $role = Role::query()->where('name', $roleName)->firstOrFail();
        $user->roles()->sync([$role->id]);
    }
}
