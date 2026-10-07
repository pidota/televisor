<?php

namespace App\Http\Controllers;

use App\Enums\MediaAssetType;
use App\Http\Requests\Media\StoreMediaAssetRequest;
use App\Http\Requests\Media\UpdateMediaAssetRequest;
use App\Models\MediaAsset;
use App\Services\MediaLibraryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaAssetController extends Controller
{
    public function __construct(
        private readonly MediaLibraryService $mediaLibrary,
    ) {}

    public function videos(Request $request): View
    {
        return $this->index($request, MediaAssetType::Video, 'Videos', 'content.videos');
    }

    public function images(Request $request): View
    {
        return $this->index($request, MediaAssetType::Image, 'Imágenes', 'content.images');
    }

    private function index(Request $request, MediaAssetType $type, string $title, string $routeName): View
    {
        $this->authorize('viewAny', MediaAsset::class);

        $query = MediaAsset::query()
            ->where('type', $type)
            ->with('uploader:id,name')
            ->orderByDesc('created_at');

        $search = $request->string('q')->trim()->toString();
        $status = $request->string('status')->toString();

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('original_filename', 'like', "%{$search}%");
            });
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        return view('media.index', [
            'title' => $title,
            'type' => $type,
            'storeRoute' => $routeName.'.store',
            'assets' => $query->paginate(12)->withQueryString(),
            'filters' => [
                'q' => $search,
                'status' => $status,
            ],
        ]);
    }

    public function store(StoreMediaAssetRequest $request, string $type): RedirectResponse
    {
        try {
            $this->mediaLibrary->store(
                $request->file('file'),
                $request->mediaType(),
                $request->user(),
                $request->string('name')->trim()->toString() ?: null
            );
        } catch (ValidationException $exception) {
            throw $exception;
        }

        $redirectRoute = $type === 'videos' ? 'content.videos' : 'content.images';

        return redirect()
            ->route($redirectRoute)
            ->with('success', 'Archivo subido correctamente.');
    }

    public function show(MediaAsset $mediaAsset): View
    {
        $this->authorize('view', $mediaAsset);

        $mediaAsset->load('uploader:id,name');

        return view('media.show', ['media' => $mediaAsset]);
    }

    public function edit(MediaAsset $mediaAsset): View
    {
        $this->authorize('update', $mediaAsset);

        return view('media.edit', ['media' => $mediaAsset]);
    }

    public function update(UpdateMediaAssetRequest $request, MediaAsset $mediaAsset): RedirectResponse
    {
        $this->mediaLibrary->updateMetadata($mediaAsset, $request->validated(), $request->user());

        return redirect()
            ->route('media.show', $mediaAsset)
            ->with('success', 'Contenido actualizado.');
    }

    public function preview(MediaAsset $mediaAsset): StreamedResponse
    {
        $this->authorize('view', $mediaAsset);

        if ($mediaAsset->status !== \App\Enums\MediaAssetStatus::Ready) {
            abort(404);
        }

        return Storage::disk($mediaAsset->disk)->response(
            $mediaAsset->path,
            $mediaAsset->original_filename,
            [
                'Content-Type' => $mediaAsset->mime_type,
                'Content-Disposition' => 'inline; filename="'.addslashes($mediaAsset->original_filename).'"',
            ]
        );
    }

    public function destroy(MediaAsset $mediaAsset): RedirectResponse
    {
        $this->authorize('delete', $mediaAsset);

        $type = $mediaAsset->type;

        try {
            $this->mediaLibrary->delete($mediaAsset, auth()->user());
        } catch (ValidationException $exception) {
            return back()->with('error', $exception->validator->errors()->first('media'));
        }

        $route = $type === MediaAssetType::Video ? 'content.videos' : 'content.images';

        return redirect()
            ->route($route)
            ->with('success', 'Contenido eliminado.');
    }
}
