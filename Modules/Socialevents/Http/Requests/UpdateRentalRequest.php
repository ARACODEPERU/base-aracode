<?php

namespace Modules\Socialevents\Http\Requests;

class UpdateRentalRequest extends StoreRentalRequest
{
    /**
     * En edición, la propia reserva no debe contar como solapamiento.
     */
    protected function excludeRentalId(): ?int
    {
        return (int) $this->route('id');
    }
}
