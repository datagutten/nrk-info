<?php

namespace datagutten\nrk\elements;

use datagutten\nrk\Utils;
use datagutten\video_tools\EpisodeFormat;

class Episode extends Program
{
    public Program $program;

    /*    function __construct($program)
        {
            $this->program = Program::episode($program);
            $this->format = utils::parse_season_episode();
            parent::__construct($program);
        }*/
    public EpisodeFormat $format;

    public static function episode(array $program): static
    {
        $episode = parent::episode($program);
        $episode->format = Utils::parse_season_episode($program);
        return $episode;
    }
}