<?php

namespace FluxErp\Actions\Media;

use FluxErp\Actions\BaseAction;
use FluxErp\Http\Requests\ReplaceMediaRequest;
use FluxErp\Models\Media;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ReplaceMedia extends BaseAction
{
    public function __construct(array $data)
    {
        parent::__construct($data);
        $this->rules = (new ReplaceMediaRequest())->rules();
    }

    public static function models(): array
    {
        return [Media::class];
    }

    public function execute(): Model
    {
        $mediaItem = Media::query()
            ->whereKey($this->data['id'])
            ->first();

        $customProperties = CustomProperties::get($this->data, $mediaItem->model_type);
        $diskName = $this->data['disk'] ?? (
            $mediaItem->model->getRegisteredMediaCollections()
                ->where('name', $mediaItem->collection_name)
                ->first()
                ?->diskName ?: config('media-library.disk_name')
        );

        $file = $this->data['media'];
        $mediaItem->name = $this->data['name'];

        DeleteMedia::make(['id' => $this->data['id']])->execute();

        if ($this->data['media_type'] ?? false) {
            $fileAdder = $mediaItem->model->{'addMediaFrom' . $this->data['media_type']}($file);
        } else {
            $fileAdder = $mediaItem->model->addMedia($file instanceof UploadedFile ? $file->path() : $file);
        }

        $media = $fileAdder
            ->setName($this->data['name'])
            ->usingFileName($this->data['file_name'])
            ->withCustomProperties($customProperties)
            ->storingConversionsOnDisk(config('flux.media.conversion'))
            ->toMediaCollection(collectionName: $mediaItem->collection_name, diskName: $diskName);

        $media->forceFill([
            'id' => $this->data['id'],
        ]);
        $media->save();

        return $media->withoutRelations();
    }

    public function validate(): static
    {
        $this->data['model_type'] = Media::query()
            ->whereKey($this->data['id'] ?? null)
            ->first()
            ?->model_type;

        parent::validate();

        $mediaItem = Media::query()
            ->whereKey($this->data['id'])
            ->first();

        $this->data['file_name'] = $this->data['file_name'] ?? (
            $this->data['media'] instanceof UploadedFile ?
                $this->data['media']->getClientOriginalName() :
                hash('sha512', microtime() . Str::uuid())
        );
        $this->data['name'] = $this->data['name'] ?? $this->data['file_name'];
        $this->data['collection_name'] ??= 'default';

        if (Media::query()
            ->where('model_type', $mediaItem->model_type)
            ->where('model_id', $mediaItem->model_id)
            ->where('collection_name', $mediaItem->collection_name)
            ->where('name', $this->data['name'])
            ->where('id', '!=', $this->data['id'])
            ->exists()
        ) {
            throw ValidationException::withMessages([
                'filename' => [__('File name already exists')],
            ])->errorBag('replaceMedia');
        }

        return $this;
    }
}
