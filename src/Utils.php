<?php

namespace datagutten\nrk;

use datagutten\nrk\exceptions\NRKException;
use datagutten\video_tools\EpisodeFormat;
use DateTimeImmutable;
use RuntimeException;
use TypeError;

class Utils
{
    public static function alphabet(): array
    {
        $letters = range('a', 'z');
        $letters += ['æ', 'ø', 'å'];
        return $letters;
    }

    public static function parse_date(int|string|null $timestamp): DateTimeImmutable
    {
        if (empty($timestamp))
            throw new RuntimeException('Empty timestamp');
        preg_match('#Date\((-?[0-9]+)([+-][0-9]+)\)#', $timestamp, $matches);
        if (empty($matches))
            throw new RuntimeException('Invalid timestamp');
        $timestamp = $matches[1] / 1000;
        $datetime = new DateTimeImmutable();
        return $datetime->setTimestamp(intval($timestamp)); //TODO: Add timezone
    }

    /**
     * Parse element type and ID from URL
     * @param string $url
     * @return array list($element, $id) = Utils::parse_url($url);
     * @throws NRKException
     */
    public static function parse_url(string $url): array
    {
        if (preg_match('#/(\w+)/([A-ZÆØÅ]+\d{3,})#', $url, $matches))
            return array_slice($matches, 1);
        elseif (preg_match('#/(serie)/([\w-]+)#', $url, $matches))
            return array_slice($matches, 1);
        else
            throw new NRKException('Unable to get ID from URL');
    }

    /**
     * Get ID from URL
     *
     * @param string $url URL
     * @return string ID
     * @throws NRKexception
     */
    public static function get_id(string $url): string
    {
        preg_match('^([A-Z]+[0-9]{3,})^', $url, $result);
        if (!isset($result[1]))
        {
            $page = file_get_contents($url);
            preg_match('/data-program-id="([A-Z]+[0-9]+)"/', $page, $matches);
            if (!empty($matches))
                return $matches[1];
            else
                throw new NRKexception('Unable to find ID');
        } else
            return $result[1];
    }

    /**
     * Parse season and episode from program information and return EpisodeFormat object
     * @param array $info Program info from PSAPI::program_info()
     * @return EpisodeFormat EpisodeFormat object
     * @see PSAPI::program_info()
     */
    public static function parse_season_episode(array $info): EpisodeFormat
    {
        $episode = new EpisodeFormat();
        if (isset($info['seriesTitle']))
            $episode->series = $info['seriesTitle'];
        if (isset($info['seasonNumber']))
            $episode->season = (int)$info['seasonNumber'];
        //'season_title'=> $season['titles']['title'],
        if (isset($info['episodeNumber']))
            $episode->episode = $info['episodeNumber'];

        //Prefer title if it differs from series name
        if (!empty($episode->series) && $info['title'] != $episode->series)
            $info['episodeTitle'] = $info['title'];

        $bad_match = False;

        if (isset($info['episodeTitle']))
        {
            foreach (['#([0-9]+)[\.\s]*(.+)#', '#(.+?)[\.\s]*([0-9]+)#'] as $pattern)
            {
                if (preg_match($pattern, $info['episodeTitle'], $matches) && !empty($episode->episode))
                {
                    if ($matches[1] == $episode->episode && $matches[2] != 'episode')
                        $episode->title = $matches[2];
                    elseif ($matches[2] == $episode->episode && $matches[1] != 'episode')
                        $episode->title = $matches[1];
                    else
                        $bad_match = True;
                }
            }
            if (empty($episode->title) && !$bad_match)
                $episode->title = $info['episodeTitle'];
        } elseif (isset($info['title']))
            $episode->title = $info['title'];

        if (isset($info['shortDescription']))
            $episode->description = $info['shortDescription'];
        if (empty($episode->episode) && !empty($episode->description)) // Parse season and episode from description
        {
            if (preg_match('/Sesong ([0-9]+) \(([0-9]+):[0-9]+\)/', $episode->description, $matches))
            {
                $episode->episode = (int)$matches[2];
                $episode->season = (int)$matches[1];
            }
        }
        if (!empty($info['episodeNumberOrDate']))
        {
            try
            {
                $episode->date = DateTimeImmutable::createFromFormat('!d.m.Y', $info['episodeNumberOrDate']);
            }
            catch (TypeError $e)
            {
            }
        }
        return $episode;
    }
}