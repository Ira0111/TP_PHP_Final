<?php

class Media
{
    private int $id;
    private string $title;
    private string $type;
    private ?string $description = null;
    private string $image = '';
    private ?string $date = null;
    private ?string $createdAt = null;
    private ?string $apiId = null;
    private ?string $apiSource = null;

    public function __construct(array $data = [])
    {
        $this->hydrate($data);
    }

    /**
     * Hydrate l'objet à partir d'un tableau associatif issu de la BDD.
     * Cas particulier : la colonne "media_id" correspond au setter setId().
     */
    public function hydrate(array $data): void
    {
        if (isset($data['media_id'])) {
            $this->setId((int) $data['media_id']);
            unset($data['media_id']);
        }

        foreach ($data as $key => $value) {
            $method = "set" . str_replace('_', '', ucwords($key, '_'));
            if (method_exists($this, $method)) {
                $this->$method($value);
            }
        }
    }

    // ─── Getters / Setters ───

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getImage(): string
    {
        return $this->image;
    }

    public function setImage(string $image): self
    {
        $this->image = $image;
        return $this;
    }

    public function getDate(): ?string
    {
        return $this->date;
    }

    public function setDate(?string $date): self
    {
        $this->date = $date;
        return $this;
    }

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?string $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getApiId(): ?string
    {
        return $this->apiId;
    }

    public function setApiId(?string $apiId): self
    {
        $this->apiId = $apiId;
        return $this;
    }

    public function getApiSource(): ?string
    {
        return $this->apiSource;
    }

    public function setApiSource(?string $apiSource): self
    {
        $this->apiSource = $apiSource;
        return $this;
    }

    public function getYear(): ?string
    {
        if (!$this->date) {
            return null;
        }
        return substr($this->date, 0, 4);
    }

    public function getSlug(): string
    {
        return match ($this->type) {
            'movie' => 'film',
            'game'  => 'jeu',
            'book'  => 'livre',
            default => $this->type,
        };
    }
}