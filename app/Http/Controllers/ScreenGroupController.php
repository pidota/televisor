<?php

namespace App\Http\Controllers;

use App\Enums\ScreenStatus;
use App\Http\Requests\ScreenGroup\StoreScreenGroupRequest;
use App\Http\Requests\ScreenGroup\SyncScreenGroupMembersRequest;
use App\Http\Requests\ScreenGroup\UpdateScreenGroupRequest;
use App\Models\Screen;
use App\Models\ScreenGroup;
use App\Services\ScreenGroupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;

class ScreenGroupController extends Controller
{
    public function __construct(
        private readonly ScreenGroupService $groups,
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', ScreenGroup::class);

        $groups = ScreenGroup::query()
            ->withCount('screens')
            ->orderBy('name')
            ->paginate(15);

        return view('screen-groups.index', compact('groups'));
    }

    public function create(): View
    {
        $this->authorize('create', ScreenGroup::class);

        return view('screen-groups.create');
    }

    public function store(StoreScreenGroupRequest $request): RedirectResponse
    {
        $group = $this->groups->create($request->validated(), $request->user());

        return redirect()
            ->route('screen-groups.show', $group)
            ->with('success', 'Grupo creado.');
    }

    public function show(ScreenGroup $screenGroup): View
    {
        $this->authorize('view', $screenGroup);

        $screenGroup->load(['screens:id,name,location', 'playlistAssignments.playlist:id,name']);

        $allScreens = Screen::query()
            ->where('status', ScreenStatus::Active)
            ->orderBy('name')
            ->get(['id', 'name', 'location']);

        $memberIds = $screenGroup->screens->pluck('id')->all();

        return view('screen-groups.show', [
            'group' => $screenGroup,
            'allScreens' => $allScreens,
            'memberIds' => $memberIds,
        ]);
    }

    public function edit(ScreenGroup $screenGroup): View
    {
        $this->authorize('update', $screenGroup);

        return view('screen-groups.edit', ['group' => $screenGroup]);
    }

    public function update(UpdateScreenGroupRequest $request, ScreenGroup $screenGroup): RedirectResponse
    {
        $this->groups->update($screenGroup, $request->validated(), $request->user());

        return redirect()
            ->route('screen-groups.show', $screenGroup)
            ->with('success', 'Grupo actualizado.');
    }

    public function syncMembers(SyncScreenGroupMembersRequest $request, ScreenGroup $screenGroup): RedirectResponse
    {
        $this->groups->syncScreens($screenGroup, $request->input('screens', []), $request->user());

        return back()->with('success', 'Pantallas del grupo actualizadas.');
    }

    public function destroy(ScreenGroup $screenGroup): RedirectResponse
    {
        $this->authorize('delete', $screenGroup);

        try {
            $this->groups->delete($screenGroup, auth()->user());
        } catch (ValidationException $e) {
            return back()->with('error', $e->validator->errors()->first('group'));
        }

        return redirect()
            ->route('screen-groups.index')
            ->with('success', 'Grupo eliminado.');
    }
}
