<?php

namespace FmTod\Shipping\Models;

use FmTod\Shipping\Enums\LabelType;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @property string $name
 * @property string $content
 * @property string $extension
 * @property string $type
 */
class Label extends Model implements Responsable
{
    protected array $fillable = [
        'name',
        'content',
        'extension',
        'type',
    ];

    protected array $attributes = [
        'type' => LabelType::Url,
        'extension' => 'pdf',
    ];

    protected array $rules = [
        'name' => ['required', 'string'],
        'content' => ['required', 'string'],
        'extension' => ['required', 'string'],
        'type' => ['required', 'string'],
    ];

    protected bool $validateOnFill = true;

    /**
     * Create an HTTP response that represents the object.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse|\Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function toResponse($request): HttpResponse|RedirectResponse|StreamedResponse
    {
        if ($this->type === LabelType::Base64) {
            $base64 = Str::replace('data:application/pdf;base64,', '', $this->content);
            $fileContent = base64_decode($base64, true);
            $fileName = "$this->name.$this->extension";

            return Response::make($fileContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$fileName.'"'
            ]);
        }

        if ($this->type === LabelType::File) {
            return Response::stream(fn() => file_get_contents($this->content));
        }

        return Response::redirectTo($this->content);
    }
}
