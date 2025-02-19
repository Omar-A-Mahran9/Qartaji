<?php
namespace App\Traits;

use App\Models\Translation;

trait HasTranslations
{
    public function translations()
    {
        return $this->morphMany(Translation::class, 'translatable');
    }


    /**
     * Get all translations for a specific field.
     */
    public function getAllTranslations(string $field): array
    {
        return $this->translations
            ->where('field', $field)
            ->pluck('value', 'locale')
            ->toArray();
    }



    /**
     * Bulk insert translations for multiple fields.
     */
    public function addTranslations(array $translations): void
    {
        foreach ($translations as $field => $values) {
            foreach ($values as $locale => $value) {
                $this->addTranslation($field, $locale, $value);
            }
        }
    }

    /**
     * Get translations as an associative array.
     */
    public function getTranslationsArray(): array
    {
        return $this->translations
            ->groupBy('field')
            ->mapWithKeys(function ($items, $field) {
                return [$field => $items->pluck('value', 'locale')->toArray()];
            })
            ->toArray();
    }
    /**
     * Automatically store translations based on app locale.
     */
    public function addTranslation(string $field, string $locale, ?string $value): void
    {
        \Log::info('Attempting to save translation', [
            'field' => $field,
            'locale' => $locale,
            'value' => $value,
            'model_id' => $this->id,
            'model_type' => get_class($this),
        ]);
        if (!empty($value)) {
            $this->translations()->updateOrCreate(
                ['field' => $field, 'locale' => $locale],
                ['value' => $value]
            );
        }
    }

    /**
     * Retrieve translation for the current locale.
     */
    public function translate(string $field, ?string $locale = null): ?string
    {
        $locale = $locale ?? app()->getLocale();
        return $this->translations()
            ->where('field', $field)
            ->where('locale', $locale)
            ->value('value');
    }
}
