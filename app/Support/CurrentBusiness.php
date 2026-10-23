<?php

namespace App\Support;

use App\Models\Business;

/**
 * Guarda el negocio del usuario que está haciendo la petición.
 * Lo llena el middleware SetCurrentBusiness y lo usan el BusinessScope
 * y cualquier parte del código que necesite saber "en qué negocio estoy".
 */
class CurrentBusiness
{
    private ?Business $business = null;

    public function set(?Business $business): void
    {
        $this->business = $business;
    }

    public function get(): ?Business
    {
        return $this->business;
    }

    public function id(): ?int
    {
        return $this->business?->id;
    }
}
