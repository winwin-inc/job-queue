<?php


namespace winwin\jobQueue;

class JobId
{
    /**
     * @var string
     */
    private $jobType;

    /**
     * @var int
     */
    private $jobId;

    /**
     * JobId constructor.
     * @param string $jobType
     * @param int $jobId
     */
    public function __construct(string $jobType, int $jobId)
    {
        $this->jobType = $jobType;
        $this->jobId = $jobId;
    }

    public function getId(): string
    {
        return $this->jobType . ':' . $this->jobId;
    }

    public function __toString()
    {
        return $this->getId();
    }

    public static function fromString(string $jobId): JobId
    {
        $part = explode(":", $jobId, 2);
        if (2 !== count($part) || !is_numeric($part[1]) || !JobType::hasValue($part[0])) {
            throw new \InvalidArgumentException("'$jobId' is not a valid job id, should match type:id");
        }
        return new self($part[0], (int)$part[1]);
    }
}
