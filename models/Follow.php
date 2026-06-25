<?php

class Follow
{
    private int $follow_id;
    private int $user_id;
    private int $media_id;
    private string $status;
    private ?int $progress_detail;
    private string $created_at;
    private string $updated_at;
    private string $type;

    public function __construct(array $data)
    {
        foreach ($data as $key => $value) {
            $method = "set" . ucfirst($key);
            if (method_exists($this, $method)) {
                $this->$method($value);
            }
        }
    }

    public function getFollow_id(): int
    {
        return $this->follow_id;
    }
    public function getUser_id(): int
    {
        return $this->user_id;
    }
    public function getMedia_id(): int
    {
        return $this->media_id;
    }
    public function getStatus(): string
    {
        return $this->status;
    }
    public function getProgress_detail(): ?int
    {
        return $this->progress_detail;
    }
    public function getCreated_at(): string
    {
        return $this->created_at;
    }
    public function getUpdated_at(): string
    {
        return $this->updated_at;
    }
    public function getType(): string
    {
        return $this->type;
    }

    public function setFollow_id($v)
    {
        $this->follow_id = (int)$v;
    }
    public function setUser_id($v)
    {
        $this->user_id = (int)$v;
    }
    public function setMedia_id($v)
    {
        $this->media_id = (int)$v;
    }
    public function setStatus($v)
    {
        $this->status = $v;
    }
    public function setProgress_detail($v)
    {
        $this->progress_detail = $v !== null ? (int)$v : null;
    }
    public function setCreated_at($v)
    {
        $this->created_at = $v;
    }
    public function setUpdated_at($v)
    {
        $this->updated_at = $v;
    }
    public function setType($v): void
    {
        $this->type = $v;
    }
}
