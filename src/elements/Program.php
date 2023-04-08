<?php

namespace datagutten\nrk\elements;

use datagutten\nrk\exceptions;
use datagutten\nrk\utils;
use datagutten\video_tools\EpisodeFormat;
use DateInterval;
use DateTimeImmutable;
use Exception;

class Program
{
    public string $id;
    public string $title;
    public ?string $originalTitle;
    public int $sourceMedium;
    public DateInterval $duration;
    public string $shortDescription;
    public string $longDescription;

    public DateTimeImmutable $firstAired;
    public DateTimeImmutable $availableFrom;
    public DateTimeImmutable $availableTo;
    public DateTimeImmutable $releaseDateOnDemand;

    public bool $hasRightsNow;
    public string $availability;
    public string $category;

    public int $productionYear;
    protected static array $keys = ['id', 'title', 'originalTitle', 'sourceMedium', 'shortDescription', 'longDescription', 'productionYear'];
    public array $info;
    /**
     * @var EpisodeFormat Episode information
     */
    public EpisodeFormat $episode;

    public function __construct($program)
    {
        $this->info = $program;
        foreach (static::$keys as $key)
        {
            if (!empty($program[$key]))
                $this->$key = $program[$key];
        }
        $duration = preg_replace('/([0-9]+)\.[0-9]+S/', '$1S', $program['duration']); //Remove decimals from seconds
        $this->duration = new DateInterval($duration);
    }

    /**
     * @throws Exception
     */
    public static function program(array $program): static
    {
        $obj = new static($program);
        foreach (static::$keys as $key)
        {
            if (!empty($program[$key]))
                $obj->$key = $program[$key];
        }

        if (!empty($program['firstTimeTransmitted']))
            $obj->firstAired = utils::parse_date($program['firstTimeTransmitted']['actualTransmissionDate']);
        if (!empty($program['category']))
            $obj->category = $program['category']['displayValue'];
        if (!empty($program['releaseDateOnDemand']))
            $obj->releaseDateOnDemand = new DateTimeImmutable($program['releaseDateOnDemand']);

        $obj->availableFrom = utils::parse_date($program['usageRights']['availableFrom']);
        $obj->availableTo = utils::parse_date($program['usageRights']['availableTo']);
        $obj->availability = $program['availability']['status'];

        //$obj->productionYear = $program['productionYear'];
        return $obj;
    }

    /**
     * @param array $program
     * @return static
     * @throws exceptions\NRKException Unable to parse date
     */
    public static function episode(array $program): static
    {
        $obj = new static($program);
        foreach (static::$keys as $key)
        {
            if (!empty($program[$key]))
                $obj->$key = $program[$key];
        }
        $obj->id = $program['prfId'];
        $obj->title = $program['titles']['title'];
        $obj->shortDescription = $program['titles']['subtitle'];
        try
        {
            if (!empty($program['releaseDateOnDemand']))
                $obj->releaseDateOnDemand = new DateTimeImmutable($program['releaseDateOnDemand']);
            if (!empty($program['usageRights']['from']['date']))
                $obj->availableFrom = new DateTimeImmutable($program['usageRights']['from']['date']);
            if (!empty($program['usageRights']['to']['date']))
                $obj->availableTo = new DateTimeImmutable($program['usageRights']['to']['date']);
        }
        catch (Exception $e)
        {
            throw new \datagutten\nrk\exceptions\NRKException('Unable to parse date', $e->getCode(), $e);
        }
        return $obj;
    }

    public function url(): string
    {
        return sprintf('https://tv.nrk.no/program/%s', $this->id);
    }

    public function timeLeft(): DateInterval
    {
        $now = new DateTimeImmutable();
        return $now->diff($this->availableFrom);
    }

    /**
     * @return DateInterval
     * @throws exceptions\NRKException Unable to get available time
     */
    public function timeToAvailable(): DateInterval
    {
        if (empty($this->availableFrom))
            throw new \datagutten\nrk\exceptions\NRKException('Unable to get available time');

        return $this->availableFrom->diff(new DateTimeImmutable());
    }

    /**
     * Is the program scheduled to be available in the future?
     * @return bool
     * @throws exceptions\NRKException Unable to get available time
     */
    public function coming(): bool
    {
        return /*$this->timeToAvailable()->days > 0 &&*/ $this->timeToAvailable()->invert == 1;
    }

    /**
     * Is the program produced this year?
     * @return bool
     */
    public function is_new(): bool
    {
        return $this->productionYear == date('Y');
    }

    /**
     * @param bool $coming
     * @return string
     * @throws exceptions\NRKException Unable to get available time
     */
    public function availabilityString(bool $coming = true): string
    {
        if ($coming && $this->coming() && !$this->is_new())
            return sprintf("%s\t%s\t%d\t%s\n", $this->title, $this->availableFrom->format('Y-m-d'), $this->year(), $this->url());
        else
            return '';
    }

    public function year(): int
    {
        if (!empty($this->productionYear))
            return $this->productionYear;
        else
            return intval($this->firstAired->format('Y'));
    }

}