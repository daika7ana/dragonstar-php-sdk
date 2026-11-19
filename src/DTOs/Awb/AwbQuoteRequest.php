<?php

declare(strict_types=1);

namespace Daika7ana\DragonStar\DTOs\Awb;

use Daika7ana\DragonStar\Enums\CodPaymentType;
use Daika7ana\DragonStar\Enums\ShipmentType;

/**
 * Payload for POST /v1/awb/quote. Nothing is created.
 *
 * `codPaymentType` is required once `codAmount` is above 0 — there is no
 * default — and `account` cannot be combined with `reverseShipment`.
 */
final readonly class AwbQuoteRequest
{
    public function __construct(
        public ShipmentType $shipmentType,
        public string $recipientCounty,
        public string $recipientCity,
        public ?float $weight = null,
        public ?float $length = null,
        public ?float $width = null,
        public ?float $height = null,
        public ?float $codAmount = null,
        public ?CodPaymentType $codPaymentType = null,
        public ?float $insuredValue = null,
        public bool $returnAwb = false,
        public bool $returnDocuments = false,
        public bool $returnPackaging = false,
        public bool $returnParcel = false,
        public bool $smsNotification = false,
        public bool $openOnDelivery = false,
        public bool $saturdayDelivery = false,
        public bool $deliverToBranch = false,
        public bool $recipientPays = false,
        public bool $reverseShipment = false,
        public ?string $notes = null,
        public ?string $documentDetails = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'shipmentType' => $this->shipmentType->value,
            'recipientCounty' => $this->recipientCounty,
            'recipientCity' => $this->recipientCity,
            'weight' => $this->weight,
            'length' => $this->length,
            'width' => $this->width,
            'height' => $this->height,
            'codAmount' => $this->codAmount,
            'codPaymentType' => $this->codPaymentType?->value,
            'insuredValue' => $this->insuredValue,
            'returnAwb' => $this->returnAwb,
            'returnDocuments' => $this->returnDocuments,
            'returnPackaging' => $this->returnPackaging,
            'returnParcel' => $this->returnParcel,
            'smsNotification' => $this->smsNotification,
            'openOnDelivery' => $this->openOnDelivery,
            'saturdayDelivery' => $this->saturdayDelivery,
            'deliverToBranch' => $this->deliverToBranch,
            'recipientPays' => $this->recipientPays,
            'reverseShipment' => $this->reverseShipment,
            'notes' => $this->notes,
            'documentDetails' => $this->documentDetails,
        ];

        return array_filter($data, static fn(mixed $value): bool => $value !== null);
    }
}
