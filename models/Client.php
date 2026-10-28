
<?php

class Client
{
    private ?int $id;
    private string $emri;
    private string $mbiemri;
    private ?string $telefoni;
    private ?string $email;
    private ?string $adresa;
    private ?string $dataRegjistrimit;

    public function __construct(
        ?int $id,
        string $emri,
        string $mbiemri,
        ?string $telefoni = null,
        ?string $email = null,
        ?string $adresa = null,
        ?string $dataRegjistrimit = null
    ) {
        $this->id = $id;
        $this->emri = $emri;
        $this->mbiemri = $mbiemri;
        $this->telefoni = $telefoni;
        $this->email = $email;
        $this->adresa = $adresa;
        $this->dataRegjistrimit = $dataRegjistrimit;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            isset($data['klient_id']) ? (int) $data['klient_id'] : null,
            (string) ($data['emri'] ?? ''),
            (string) ($data['mbiemri'] ?? ''),
            $data['telefoni'] ?? null,
            $data['email'] ?? null,
            $data['adresa'] ?? null,
            $data['data_regjistrimit'] ?? null
        );
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmri(): string
    {
        return $this->emri;
    }

    public function getMbiemri(): string
    {
        return $this->mbiemri;
    }

    public function getFullName(): string
    {
        return trim($this->emri . ' ' . $this->mbiemri);
    }

    public function getTelefoni(): ?string
    {
        return $this->telefoni;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getAdresa(): ?string
    {
        return $this->adresa;
    }

    public function getDataRegjistrimit(): ?string
    {
        return $this->dataRegjistrimit;
    }
}
