<?php

namespace datagutten\nrk;


use datagutten\nrk\elements\Program;
use datagutten\nrk\elements\Series;

class NRK
{
    public PSAPI $psapi;

    public function __construct()
    {
        $this->psapi = new PSAPI();
    }

    /**
     * @param string $slug
     * @return Series
     * @throws exceptions\NRKException Unable to get series information
     */
    public function series(string $slug): Series
    {
        $slug = preg_replace('#.+/serie/([\w_]+)/?#', '$1', $slug);
        try
        {
            $series = $this->psapi->series($slug);
        }
        catch (exceptions\NRKException $e)
        {
            $e->setMessage('Unable to get series information: ' . $e->getMessage());
            throw $e;
        }
        return new Series($series, $this->psapi);
    }

    /**
     * Get program
     * @param string $url
     * @return Program Program object
     * @throws exceptions\NRKException
     */
    public function program(string $url): Program
    {
        list($element_type, $id) = Utils::parse_url($url);
        $program = $this->psapi->program_info($id);
        $program_obj = Program::program($program);
        $program_obj->episode = Utils::parse_season_episode($program);
        return $program_obj;
    }
}