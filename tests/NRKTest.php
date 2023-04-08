<?php


use datagutten\nrk;
use PHPUnit\Framework\TestCase;

class NRKTest extends TestCase
{

    public function testSeries()
    {
        $nrk = new nrk\NRK();
        $series = $nrk->series('https://tv.nrk.no/serie/110');
        $this->assertInstanceOf(nrk\elements\Series::class , $series);
        $this->assertEquals('110', $series->title());
    }
}
