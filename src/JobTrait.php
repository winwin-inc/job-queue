<?php


namespace winwin\jobQueue;

use kuiper\helper\Arrays;
use kuiper\helper\Text;

trait JobTrait
{
    /**
     * AbstractJob constructor.
     *
     * @param array $arguments
     */
    public function __construct($arguments)
    {
        if (!is_array($arguments)) {
            throw new \InvalidArgumentException("expect an array, got " . gettype($arguments));
        }
        foreach ($arguments as $name => $value) {
            $field = lcfirst(Text::camelCase($name));
            if (property_exists($this, $field)) {
                $this->{$field} = $value;
            }
        }
        $this->initialize();
    }

    private function initialize(): void
    {
    }

    public function jsonSerialize()
    {
        return Arrays::toArray($this, true, true);
    }
}
