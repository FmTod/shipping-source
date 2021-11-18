<?php

namespace FmTod\Shipping\Models;

/**
 * Class Duration.
 *
 * @property int $days
 * @property string $terms
 * @property string $delivery_by
 */
class Duration extends Model
{
    protected array $fillable = [
        'days',
        'terms',
    ];

    protected static array $rules = [
        'days' => 'required|int',
        'terms' => 'sometimes|nullable',
        'delivery_by' => 'sometimes|nullable',
    ];

    /**
     * Get a calculated term string if it's empty.
     *
     * @return string
     */
    public function getTermsAttribute(): string
    {
        if (empty($this->attributes['terms'])) {
            return $this->delivery_by
                ? "Delivered by $this->delivery_by"
                : "$this->days Transit Day".($this->days > 1 ? 's' : '');
        }

        return $this->attributes['terms'];
    }
}
