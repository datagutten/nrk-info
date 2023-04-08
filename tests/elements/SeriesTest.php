<?php

namespace elements;

use datagutten\nrk;
use PHPUnit\Framework\TestCase;

class SeriesTest extends TestCase
{
    public function testFirstEpisode()
    {
        $nrk = new nrk\NRK();
        $series = $nrk->series('https://tv.nrk.no/serie/110');
        $episode = $series->firstEpisode();
        $this->assertInstanceOf(nrk\elements\Program::class, $episode);
    }
}
