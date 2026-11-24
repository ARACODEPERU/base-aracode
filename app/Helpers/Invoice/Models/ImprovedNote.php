<?php

namespace App\Helpers\Invoice\Models;

use Greenter\Model\Sale\Note;
use DateTimeInterface; // Importa DateTimeInterface

class ImprovedNote extends Note
{
    private $idDocAfectado;

    private $fechaDocAfectado;

    private $desMotivo;

    public function getIdDocAfectado(): ?string
    {
        return $this->idDocAfectado;
    }

    public function setIdDocAfectado(?int $idDocAfectado): Note
    {
        $this->idDocAfectado = $idDocAfectado;

        return $this;
    }

    public function getFechaDocAfectado(): ?DateTimeInterface
    {
        return $this->fechaDocAfectado;
    }

    public function setFechaDocAfectado(?DateTimeInterface $fechaDocAfectado): Note
    {
        $this->fechaDocAfectado = $fechaDocAfectado;

        return $this;
    }

    public function getDesTypeMotivo(): ?string
    {
        return $this->desMotivo;
    }

    public function setDesTypeMotivo(?string $desMotivo): Note
    {
        $this->desMotivo = $desMotivo;

        return $this;
    }

}
