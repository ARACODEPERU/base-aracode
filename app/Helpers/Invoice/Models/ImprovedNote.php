<?php

namespace App\Helpers\Invoice\Models;

use Greenter\Model\Sale\Note;
use DateTimeInterface; // Importa DateTimeInterface

class ImprovedNote extends Note
{
    private $idDocAfectado;

    private $fechaDocAfectado;

    public function getIdDocAfectado(): ?string
    {
        return $this->idDocAfectado;
    }

    /**
     * @param string $tipDocAfectado
     *
     * @return Note
     */
    public function setIdDocAfectado( $idDocAfectado): Note
    {
        $this->idDocAfectado = $idDocAfectado;

        return $this;
    }

    public function getFechaDocAfectado(): ?DateTimeInterface
    {
        return $this->fechaDocAfectado;
    }

    /**
     * @param string $tipDocAfectado
     *
     * @return Note
     */
    public function setFechaDocAfectado(?DateTimeInterface $fechaDocAfectado): Note
    {
        $this->fechaDocAfectado = $fechaDocAfectado;

        return $this;
    }

}
