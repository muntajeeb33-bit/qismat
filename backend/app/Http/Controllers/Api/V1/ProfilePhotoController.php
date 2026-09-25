<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Models\ProfilePhoto;
use App\Services\ProfilePhotoAccess;
use App\Services\ProfilePhotoProcessor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProfilePhotoController extends Controller
{
    use RespondsWithJson;

    public function index(Request $request)
    {
        return $this->success(
            $request->user()->profilePhotos()->get()->map(fn (ProfilePhoto $photo) => $this->payload($photo))
        );
    }

    public function store(Request $request, ProfilePhotoProcessor $processor)
    {
        $data = $request->validate([
            'photo' => ['required', 'file', 'image', 'mimes:jpeg,png,webp', 'max:8192', 'dimensions:min_width=400,min_height=400,max_width=4096,max_height=4096'],
            'visibility' => ['sometimes', 'in:members,matches,private,hidden'],
            'is_primary' => ['sometimes', 'boolean'],
        ]);

        $processed = $processor->process($data['photo']);
        $path = 'users/'.$request->user()->id.'/'.Str::uuid().'.'.$processed['extension'];

        try {
            $photo = DB::transaction(function () use ($request, $data, $processed, $path) {
                $photos = ProfilePhoto::query()
                    ->where('user_id', $request->user()->id)
                    ->lockForUpdate()
                    ->get();

                abort_if($photos->count() >= 6, 422, 'A profile can contain up to six photos.');

                $primary = ($data['is_primary'] ?? false) || $photos->isEmpty();
                if ($primary) {
                    ProfilePhoto::query()->where('user_id', $request->user()->id)->update(['is_primary' => false]);
                }

                abort_unless(Storage::disk('profile_photos')->put($path, $processed['contents']), 500, 'The photo could not be stored.');

                return ProfilePhoto::create([
                    'user_id' => $request->user()->id,
                    'disk' => 'profile_photos',
                    'path' => $path,
                    'mime_type' => $processed['mime_type'],
                    'width' => $processed['width'],
                    'height' => $processed['height'],
                    'size_bytes' => $processed['size_bytes'],
                    'is_primary' => $primary,
                    'visibility' => $data['visibility'] ?? 'members',
                    'moderation_status' => 'pending',
                    'sort_order' => ($photos->max('sort_order') ?? -1) + 1,
                ]);
            });
        } catch (\Throwable $error) {
            Storage::disk('profile_photos')->delete($path);
            throw $error;
        }

        return $this->success($this->payload($photo), 'Photo uploaded for moderation.', 201);
    }

    public function update(Request $request, ProfilePhoto $photo)
    {
        $this->ownerOnly($request, $photo);
        $data = $request->validate([
            'visibility' => ['sometimes', 'in:members,matches,private,hidden'],
            'is_primary' => ['sometimes', 'boolean'],
        ]);
        abort_if($data === [], 422, 'Choose a visibility or primary-photo change.');
        abort_if(($data['is_primary'] ?? null) === false && $photo->is_primary, 422, 'Choose another photo as primary instead.');

        DB::transaction(function () use ($request, $photo, $data) {
            ProfilePhoto::query()->where('user_id', $request->user()->id)->lockForUpdate()->get();
            if ($data['is_primary'] ?? false) {
                ProfilePhoto::query()->where('user_id', $request->user()->id)->update(['is_primary' => false]);
            }
            $photo->update($data);
        });

        return $this->success($this->payload($photo->refresh()), 'Photo settings updated.');
    }

    public function reorder(Request $request)
    {
        $data = $request->validate([
            'photo_ids' => ['required', 'array', 'min:1', 'max:6'],
            'photo_ids.*' => ['required', 'integer', 'distinct'],
        ]);
        $photos = ProfilePhoto::query()->where('user_id', $request->user()->id)->get();
        $submitted = collect($data['photo_ids'])->sort()->values()->all();
        $owned = $photos->pluck('id')->sort()->values()->all();
        abort_unless($submitted === $owned, 422, 'The order must contain every profile photo exactly once.');

        DB::transaction(function () use ($request, $data) {
            ProfilePhoto::query()->where('user_id', $request->user()->id)->lockForUpdate()->get();
            foreach ($data['photo_ids'] as $order => $photoId) {
                ProfilePhoto::query()->where('user_id', $request->user()->id)->whereKey($photoId)->update(['sort_order' => $order]);
            }
        });

        return $this->success(
            $request->user()->profilePhotos()->get()->map(fn (ProfilePhoto $photo) => $this->payload($photo)),
            'Photo order updated.'
        );
    }

    public function destroy(Request $request, ProfilePhoto $photo)
    {
        $this->ownerOnly($request, $photo);
        $disk = $photo->disk;
        $path = $photo->path;

        DB::transaction(function () use ($request, $photo) {
            ProfilePhoto::query()->where('user_id', $request->user()->id)->lockForUpdate()->get();
            $wasPrimary = $photo->is_primary;
            $photo->delete();
            if ($wasPrimary) {
                ProfilePhoto::query()
                    ->where('user_id', $request->user()->id)
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->first()?->update(['is_primary' => true]);
            }
        });
        Storage::disk($disk)->delete($path);

        return $this->success(null, 'Photo deleted.');
    }

    public function content(Request $request, ProfilePhoto $photo, ProfilePhotoAccess $access)
    {
        abort_unless($access->canView($request->user(), $photo), 404);
        abort_unless(Storage::disk($photo->disk)->exists($photo->path), 404);

        return Storage::disk($photo->disk)->response($photo->path, null, [
            'Content-Type' => $photo->mime_type,
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function ownerOnly(Request $request, ProfilePhoto $photo): void
    {
        abort_unless($photo->user_id === $request->user()->id, 404);
    }

    private function payload(ProfilePhoto $photo): array
    {
        return [
            'id' => $photo->id,
            'content_url' => url('/api/v1/profile/photos/'.$photo->id.'/content'),
            'mime_type' => $photo->mime_type,
            'width' => $photo->width,
            'height' => $photo->height,
            'size_bytes' => $photo->size_bytes,
            'is_primary' => (bool) $photo->is_primary,
            'visibility' => $photo->visibility,
            'moderation_status' => $photo->moderation_status,
            'moderation_feedback' => $photo->moderation_feedback,
            'sort_order' => $photo->sort_order,
            'created_at' => $photo->created_at,
        ];
    }
}
