<?php

namespace App\Models\Tenants\Traits;

use App\Exceptions\TamperedRecordException;
use Illuminate\Support\Facades\Log;

trait TamperProof
{
    /**
     * Flag indicating if the record has been tampered with.
     * Defined as a public property so Eloquent doesn't treat it as a database column.
     *
     * @var bool
     */
    public bool $is_tampered = false;

    /**
     * Boot the tamper-proof trait for a model.
     *
     * @return void
     */
    public static function bootTamperProof()
    {
        static::saving(function ($model) {
            $model->signature = $model->generateSignature();
        });

        static::retrieved(function ($model) {
            if ($model->signature && $model->signature !== $model->generateSignature()) {
                // Tampering detected!
                Log::critical("TAMPERING DETECTED on " . get_class($model) . " ID: {$model->id}");
                $model->is_tampered = true;
                
                // You could optionally throw an exception here to completely block access:
                // throw new \Exception("Tampered record detected!");
            } else {
                $model->is_tampered = false;
            }
        });
    }

    /**
     * Generate a cryptographic signature for the model's current state.
     *
     * @return string
     */
    public function generateSignature(): string
    {
        // Get the properties that need to be protected from the model
        $protectedAttributes = $this->tamperProtected ?? [];
        
        if (empty($protectedAttributes)) {
            return '';
        }

        $payload = [];
        foreach ($protectedAttributes as $attribute) {
            // Convert to string to ensure consistent hashing even if DB types change (e.g. float to string)
            $payload[$attribute] = (string) $this->getAttribute($attribute);
        }

        // Add the primary key if it exists to tie the signature to this specific row
        if ($this->exists) {
            $payload[$this->getKeyName()] = (string) $this->getKey();
        }

        $payloadJson = json_encode($payload);
        
        // Generate HMAC using the APP_KEY as the secret
        return hash_hmac('sha256', $payloadJson, config('app.key'));
    }
}
