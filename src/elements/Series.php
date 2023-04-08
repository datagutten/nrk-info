<?php

namespace datagutten\nrk\elements;

use datagutten\nrk\exceptions\NRKexception;
use datagutten\nrk\PSAPI;
use datagutten\nrk\SeriesInfo;
use RuntimeException;

class Series
{
    public array $series;
    public string $seriesType;
    public array $seasons;
    public SeriesInfo $info;
    protected PSAPI $psapi;

    public function __construct(array $series, PSAPI $psapi)
    {
        $this->series = $series;
        $this->seriesType = $series['seriesType'];
        //$this->info = $info;
        $this->psapi = $psapi;
    }

    public function slug()
    {
        return $this->series[$this->seriesType]['urlFriendlySeriesId'];
    }

    /**
     * @param $season_num
     * @return mixed
     * @throws NRKexception Error fetching data
     */
    public function episodes($season_num = null)
    {
        if (empty($season))
            $season_uri = $this->firstSeason()['href'];
        else
            throw new RuntimeException('Season selection not implemented');
        if (!empty($this->seasons[$season_num]))
            $season = $this->seasons[$season_num];
        else
        {
            $season = $this->psapi->get_json($season_uri);
            $this->seasons[$season_num] = $season;
        }

        if (!empty($season['_embedded']['episodes']))
            return $season['_embedded']['episodes'];
        elseif ($season['_embedded']['instalments'])
            return $season['_embedded']['instalments'];
        else
            throw new RuntimeException('No episodes found');
    }

    /**
     * @return
     * @throws NRKexception
     */
    public function firstEpisode(): Episode
    {
        $episodes = $this->episodes();
        return Episode::episode($episodes[0]);
    }

    /**
     * @return int
     * @throws NRKexception
     */
    public function firstEpisodeYear()
    {
        $episode = $this->firstEpisode();
        return $episode['productionYear'];
    }

    public function save(): void
    {
        //$file = files::path_join(__DIR__, '..', 'series', $this->series['sequential']['urlFriendlySeriesId'] . '.json');
        $file = static::file($this->slug());
        file_put_contents($file, json_encode($this->series));
    }

    public function categories()
    {
        return $this->series[$this->seriesType]['category'];
    }

    public function firstSeason()
    {
        return $this->series['_links']['seasons'][0];
    }

    public function title()
    {
        return $this->series[$this->seriesType]['titles']['title'];
    }

    public function subTitle()
    {
        return $this->series[$this->seriesType]['titles']['subtitle'];
    }

    public function str()
    {
        return sprintf('%s: %s (%s)', $this->title(), $this->categories()['name'], $this->firstEpisodeYear());
    }

    public function url(): string
    {
        return str_replace('{&autoplay}', '', $this->series['_links']['share']['href']);
    }
}