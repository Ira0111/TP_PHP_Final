<?php

class Review
{
    private int $review_id;
    private string $note;
    private ?string $comment;
    private string $created_at;
    private string $updated_at;
    private int $user_id;
    private int $media_id;

    public function __construct(array $data)
    {
        foreach ($data as $key => $value) {
            $method = "set" . ucfirst($key);
            if (method_exists($this, $method)) {
                $this->$method($value);
            }
        }
    }

    // GETTERS
    public function getReview_id(): int
    {
        return $this->review_id;
    }
    public function getNote(): string
    {
        return $this->note;
    }
    public function getComment(): ?string
    {
        return $this->comment;
    }
    public function getCreated_at(): string
    {
        return $this->created_at;
    }
    public function getUpdated_at(): string
    {
        return $this->updated_at;
    }
    public function getUser_id(): int
    {
        return $this->user_id;
    }
    public function getMedia_id(): int
    {
        return $this->media_id;
    }

    // SETTERS
    public function setReview_id($v)
    {
        $this->review_id = (int)$v;
    }
    public function setNote($v)
    {
        $this->note = $v;
    }
    public function setComment($v)
    {
        $this->comment = $v;
    }
    public function setCreated_at($v)
    {
        $this->created_at = $v;
    }
    public function setUpdated_at($v)
    {
        $this->updated_at = $v;
    }
    public function setUser_id($v)
    {
        $this->user_id = (int)$v;
    }
    public function setMedia_id($v)
    {
        $this->media_id = (int)$v;
    }
}
