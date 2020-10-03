<?php

namespace Drupal\representative_match\Service;

/**
 * Provides an interface for services retrieving local representative data from external sources.
 */
interface RepresentativeApiInterface {
  public function getCandidateList(string $postal_code): array;
}
