<?php

namespace Drupal\representative_match\Service;

use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\RequestException;

/**
 * An implementation of the service retrieving local representative data
 * designed to work with Open North Represent API (http://represent.opennorth.ca/api/).
 *
 * Code of this class has been ported from the Represent module (https://www.drupal.org/project/represent)
 * except for the methods implementing RepresentativeApiInterface.
 */
class OpenNorthRepresentApi implements RepresentativeApiInterface {

  /**
   * @return array
   *   The available representative sets
   */
  function representRepresentativeSets()
  {
    return $this->representResourceSets('representative-sets');
  }

  /**
   * @return array
   *   The available elections
   */
  function representElections()
  {
    return $this->representResourceSets('elections');
  }

  /**
   * @return array
   *   The available boundary sets
   */
  function representBoundarySets()
  {
    return $this->representResourceSets('boundary-sets');
  }

  /**
   * @param string $set
   *   The machine name of a representative set, eg "house-of-commons"
   * @return array
   *   The representatives in the representative set
   */
  function representRepresentativesBySet($set, $fields = array())
  {
    return $this->representResourcesBySet($set, 'representatives');
  }

  /**
   * @param string $set
   *   The machine name of an election, eg "house-of-commons"
   * @return array
   *   The representatives in the election
   */
  function representCandidatesBySet($set, $fields = array())
  {
    return $this->representResourcesBySet($set, 'candidates');
  }

  /**
   * @param string $set
   *   The machine name of a boundary set, eg "federal-electoral-districts"
   * @return array
   *   The boundaries in the boundary set
   */
  function representBoundariesBySet($set)
  {
    return $this->representResourcesBySet($set, 'boundaries');
  }

  /**
   * Returns the representatives matching the given postal code and belonging to
   * one of the given representative sets.
   *
   * @param string $postal_code
   *   A postal code
   * @param array $sets (optional)
   *   Machine names of representative sets, eg "house-of-commons"
   * @return array
   *   Matching representatives
   */
  function representRepresentativesByPostalCode($postal_code, $sets = array())
  {
    return $this->representResourcesByPostalCode($postal_code, 'representatives', 'representative_set', $sets);
  }

  /**
   * Returns the candidates matching the given postal code and belonging to one of
   * the given candidate sets.
   *
   * @param string $postal_code
   *   A postal code
   * @param array $sets (optional)
   *   Machine names of elections, eg "house-of-commons"
   * @return array
   *   Matching candidates
   */
  function representCandidatesByPostalCode($postal_code, $sets = array())
  {
    return $this->representResourcesByPostalCode($postal_code, 'candidates', 'election', $sets);
  }

  /**
   * Returns the boundaries containing the given postal code and belonging to one
   * one of the given boundary sets.
   *
   * @param string $postal_code
   *   A postal code
   * @param array $sets (optional)
   *   Machine names of resource sets, eg "federal-electoral-districts"
   * @return array
   *   Matching boundaries
   */
  function representBoundariesByPostalCode($postal_code, $sets = array())
  {
    return $this->representResourcesByPostalCode($postal_code, 'boundaries', 'boundary_set', $sets);
  }

  /**
   * @param string $plural
   *   The plural resource name
   * @return array
   *   The available resource sets
   */
  function representResourceSets($plural)
  {
    return $this->representObjects("${plural}/?limit=0");
  }

  /**
   * @param string $set
   *   The machine name of a resource set, eg "house-of-commons" or
   *   "federal-electoral-districts"
   * @param string $plural
   *   The plural resource name
   * @return array
   *   The resources in the resource set
   */
  function representResourcesBySet($set, $plural)
  {
    return $this->representObjects("${plural}/${set}/?limit=0");
  }

  /**
   * @param string $postal_code
   *   A postal code
   * @param array $sets (optional)
   *   Machine names of resource sets, eg "house-of-commons" or
   *   "federal-electoral-districts"
   * @param string $plural
   *   The plural resource name
   * @param string $singular
   *   The singular resource name
   * @return array
   *   The matching resources
   */
  function representResourcesByPostalCode($postal_code, $plural, $singular, $sets = array())
  {
    // Get the JSON response.
    $postal_code = self::representFormatPostalCode($postal_code);
    $json = $this->representSendRequest("postcodes/${postal_code}/");

    // Find the matching resources.
    $matches = [];
    if ($json) {
      $set_field = "${singular}_url";
      if (!is_array($sets)) {
        $sets = [$sets];
      }

      foreach (["${plural}_centroid", "${plural}_concordance"] as $field) {
        if (isset($json->$field)) {
          foreach ($json->$field as $match) {
            $set = $this->representGetMachineName($match->related->$set_field);
            if (empty($sets) || in_array($set, $sets)) {
              $matches[$set][] = $match;
            }
          }
        }
      }
    }
    return $matches;
  }

  /**
   * @param string $path
   *    A path
   * @return array
   *    The resources in the response
   */
  function representObjects($path)
  {
    $json = $this->representSendRequest($path);
    if ($json) {
      return $json->objects;
    }
    return array();
  }

  /**
   * @param string $path
   *   A path
   * @return object|bool
   *   The JSON as a PHP object, or FALSE if an error occurred
   */
  function representSendRequest($path)
  {
    $cid = "represent:$path";

    $cache = \Drupal::cache()->get($cid);
    if ($cache) {
      return $cache->data;
    }

    try {
      $response = \Drupal::httpClient()->get("https://represent.opennorth.ca/$path")->getBody();
      $json = json_decode($response->getContents());
      \Drupal::cache()->set($cid, $json, strtotime('+1 week'));
      return $json;
    } catch (BadResponseException $e) {
      watchdog_exception('Represent API', 'Unexpected HTTP code "@code" on @path', array(
        '@code' => $e->getResponse()->getStatusCode(),
        '@path' => $path,
      ));
    } catch (RequestException $e) {
      watchdog_exception('Represent API', 'Unexpected error "@error" on @path', array(
        '@error' => $e->getMessage(),
        '@path' => $path,
      ));
    }
    return false;
  }

  /**
   * Formats a postal code as "A1A1A1", ie uppercase without spaces.
   *
   * @param string $postal_code
   *   A postal code
   * @return string
   *   A formatted postal code
   */
  static function representFormatPostalCode($postal_code)
  {
    return preg_replace('/[^A-Z0-9]/', '', strtoupper($postal_code));
  }

  /**
   * @param string $path
   *   A path
   * @return string
   *   The name of the resource in the path
   */
  function representGetMachineName($path)
  {
    return preg_replace('@^/[^/]+/([^/]+).+$@', '\1', $path);
  }


  public function getCandidateList(string $postal_code): array
  {
    $cache = \Drupal::cache()->get($postal_code);
    if ($cache) {
      return $cache->data;
    } else {
      $sets = $this->representRepresentativesByPostalCode($postal_code);
      $candidate_list = [];
      foreach($sets as $candidates) {
        foreach ($candidates as $candidate) {
          if (!(isset($candidate_list[$candidate->representative_set_name]))) {
            $candidate_list[$candidate->representative_set_name] = [];
          }
          $candidate_list[$candidate->representative_set_name][] = [
            'name' => $candidate->name,
            'email' => $candidate->email,
            'office' => $candidate->elected_office,
            'offices' => $candidate->offices
            ];
        }

        // This will work because candidates were originally grouped by set.
      }
      ksort($candidate_list);

      \Drupal::cache()->set($postal_code, $candidate_list, strtotime('+1 week'));
      return $candidate_list;
    }
  }
}
