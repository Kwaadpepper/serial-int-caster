<?php

declare(strict_types=1);

namespace Kwaadpepper\Serial;

use Kwaadpepper\Serial\Converters\BaseConverter;
use Kwaadpepper\Serial\Shufflers\Shuffler;

final class SerialCasterBuilder
{
    /** @var \Kwaadpepper\Serial\Converters\BaseConverter */
    private $converter;

    /** @var \Kwaadpepper\Serial\Shufflers\Shuffler|null */
    private $shuffler = null;

    /** @var string|null */
    private $chars = null;

    /** @var integer */
    private $seed = 0;

    /** @var integer */
    private $length = 6;

    /**
     * SerialCasterBuilder constructor.
     *
     * @param \Kwaadpepper\Serial\Converters\BaseConverter $converter
     */
    public function __construct(BaseConverter $converter)
    {
        $this->converter = $converter;
    }

    /**
     * Set the shuffler for the SerialCaster.
     *
     * @param \Kwaadpepper\Serial\Shufflers\Shuffler $shuffler
     * @return $this
     */
    public function withShuffler(Shuffler $shuffler): self
    {
        $this->shuffler = $shuffler;
        return $this;
    }

    /**
     * Set the characters used for serial generation.
     *
     * @param string $chars
     * @return $this
     */
    public function withChars(string $chars): self
    {
        $this->chars = $chars;
        return $this;
    }

    /**
     * Set the seed for the shuffler.
     *
     * @param integer $seed
     * @return $this
     */
    public function withSeed(int $seed): self
    {
        $this->seed = $seed;
        return $this;
    }

    /**
     * Set the length of the serial.
     *
     * @param integer $length
     * @return $this
     */
    public function withLength(int $length): self
    {
        $this->length = $length;
        return $this;
    }

    /**
     * Build the SerialCaster instance.
     *
     * @return \Kwaadpepper\Serial\SerialCaster
     */
    public function build(): SerialCaster
    {
        return new SerialCaster(
            $this->converter,
            $this->shuffler,
            $this->chars,
            $this->seed,
            $this->length
        );
    }
}
