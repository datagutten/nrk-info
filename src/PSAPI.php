<?php

namespace datagutten\nrk;

use datagutten\nrk\exceptions\NRKexception;
use DateInterval;
use InvalidArgumentException;
use WpOrg\Requests;

/**
 * Class for PSAPI calls
 */
class PSAPI
{
    public Requests\Session $session;

    function __construct()
    {
        $this->session = new Requests\Session('https://psapi.nrk.no/', array('app-version-ios' => '186', 'User-Agent' => 'NRK TV/4.9.8 (iPhone; iOS 10.3.3; mobile; Scale/2.00)_app_'));
    }

    /**
     * Do a HTTP GET request
     * @param $url
     * @return Requests\Response
     * @throws NRKException HTTP error
     */
    public function get($url): Requests\Response
    {
        if (empty($url))
            throw new InvalidArgumentException("Empty URL");
        try
        {
            $response = $this->session->get($url);
            $response->throw_for_status();
            return $response;
        }
        catch (Requests\Exception $e)
        {
            throw new NRKexception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Do an HTTP GET request and return decoded JSON data
     * @param string $url
     * @return array
     * @throws NRKexception Error fetching data
     */
    public function get_json(string $url): array
    {
        try
        {
            return $this->get($url)->decode_body();
        }
        catch (Requests\Exception $e)
        {
            throw new NRKexception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Get API key
     * @param string $url
     * @return string API key
     * @throws NRKException Error fetching data
     */
    function get_api_key(string $url = 'https://tv.nrk.no'): string
    {
        $response = $this->get($url);
        preg_match('/data-api-key="([a-f0-9]+)"/', $response->body, $matches);
        if (!empty($matches[1]))
            return $matches[1];
        else
            throw new NRKexception('Unable to get api key');
    }

    /**
     * Get playback manifest
     * @param string $id Program ID
     * @return array Program manifest
     * @throws NRKexception Error fetching data
     */
    public function manifest(string $id, $eea_portability = false): array
    {
        try
        {
            return $this->get_json(sprintf('/playback/manifest/program/%s%s', $id, $eea_portability ? '?eea-portability=true' : ''));
        }
        catch (NRKexception $e)
        {
            $e->setMessage(sprintf('Unable to get manifest for %s', $id));
            throw $e;
        }
    }

    /**
     * Get playback metadata
     * @param $id
     * @return array
     * @throws NRKexception Error fetching data
     */
    function playback_metadata($id): array
    {
        return $this->get_json('/playback/metadata/program/' . $id);
    }

    /**
     * Get info about a program
     * @param $id
     * @return array
     * @throws NRKexception Error fetching data
     */
    function program_info($id): array
    {
        return $this->get_json(sprintf('/programs/%s', $id));
    }

    /**
     * @param string $series_id
     * @param ?string $season season id
     * @return array
     * @throws NRKexception Error fetching data
     */
    public function series(string $series_id, string $season = null): array
    {
        $uri = '/tv/catalog/series/' . $series_id;
        if (!empty($season))
            $uri .= sprintf('/seasons/%s', $season);

        return $this->get_json($uri);
    }

    /**
     * Get program recommendations
     * @param string $id Program id
     * @return array
     * @throws NRKexception
     */
    public function recommendations(string $id): array
    {
        return $this->get_json('/tv/recommendations/' . $id);
    }

    /**
     * Get index points (chapters)
     * @param array $manifest Playback manifest
     * @return array Index points in a format to be passed to video_tools
     * @throws NRKexception Metadata download failed
     * @see \datagutten\video_tools\video::mkvmerge_chapters
     */
    public function get_chapters(array $manifest): array
    {
        $metadata_url = $manifest['_links']['metadata']['href'];
        $metadata = $this->get_json($metadata_url);
        $points = [];
        foreach ($metadata['preplay']['indexPoints'] as $point)
        {
            if (preg_match('/(PT.*?(\d+))\.(\d+)S/', $point['startPoint'], $matches))
            {
                $interval = new DateInterval($matches[1] . 'S');
                $interval->f = floatval(sprintf('0.%d', $matches[3]));
            } else
                $interval = new DateInterval($point['startPoint']);
            $points[] = [$point['title'], $interval];

        }
        return $points;
    }
}